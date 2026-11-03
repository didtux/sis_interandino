<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use App\Models\Curso;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PagoController extends Controller
{
    public function index()
    {
        // Excluir pagos de estudiantes retirados (est_visible = 0)
        $query = Pago::with('estudiante.curso', 'padreFamilia')
            ->whereHas('estudiante', fn($q) => $q->where('est_visible', 1));

        // Caja abre el módulo para ver lo del día: si no vino ningún filtro, el
        // rango arranca en hoy. Con ?todos=1 se ve el histórico completo.
        $sinFiltros = !request()->hasAny(['fecha_inicio', 'fecha_fin', 'cur_codigo', 'est_codigo', 'estado', 'todos', 'page']);
        $fechaInicio = request('fecha_inicio', $sinFiltros ? date('Y-m-d') : null);
        $fechaFin    = request('fecha_fin',    $sinFiltros ? date('Y-m-d') : null);

        if (!empty($fechaInicio)) {
            $query->whereDate('pagos_fecha', '>=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $query->whereDate('pagos_fecha', '<=', $fechaFin);
        }
        if (request()->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) {
                $q->where('cur_codigo', request('cur_codigo'));
            });
        }
        if (request()->filled('est_codigo')) {
            $query->where('est_codigo', request('est_codigo'));
        }
        if (request('estado') === '0') {
            $query->where('pagos_estado', 0);
        } elseif (request('estado') === 'activos') {
            $query->where('pagos_estado', 1);
        }

        $pagos = $query->orderBy('pagos_fecha', 'desc')->paginate(20);

        // Agrupar pagos por pagos_codigo para recibos conjuntos y vista agrupada
        $codigosEnPagina = $pagos->pluck('pagos_codigo')->unique()->values();
        $pagosRecibo = [];
        $codigosConjuntos = []; // códigos que tienen >1 pago
        if ($codigosEnPagina->isNotEmpty()) {
            $todos = Pago::with('estudiante.curso')
                ->whereIn('pagos_codigo', $codigosEnPagina)
                ->orderBy('est_codigo')
                ->get();
            foreach ($todos->groupBy('pagos_codigo') as $codigo => $grupo) {
                $items = $grupo->map(function($p) {
                    return [
                        'pagos_id' => $p->pagos_id,
                        'estudiante' => ($p->estudiante->est_nombres ?? '') . ' ' . ($p->estudiante->est_apellidos ?? ''),
                        'curso' => $p->estudiante->curso->cur_nombre ?? 'N/A',
                        'concepto' => $p->concepto,
                        'monto' => $p->pagos_precio - $p->pagos_descuento,
                        'estado' => $p->pagos_estado,
                    ];
                })->values()->toArray();
                $pagosRecibo[$codigo] = $items;
                if (count($items) > 1) {
                    $codigosConjuntos[] = $codigo;
                }
            }
        }

        $estudiantes = Estudiante::visible()->get();
        $cursos = Curso::visible()->get();
        return view('pagos.index', compact('pagos', 'estudiantes', 'cursos', 'pagosRecibo', 'codigosConjuntos', 'fechaInicio', 'fechaFin', 'sinFiltros'));
    }

    /**
     * Gestión que se cobra: la vigente o, si se pide, la anterior (para cobrar
     * deudas del año que terminó después de pasar a la gestión nueva).
     */
    private function gestionCobro(Request $request): int
    {
        $vigente = gestion_actual();
        $pedida = (int) $request->input('gestion', $vigente);
        return $pedida === $vigente - 1 ? $pedida : $vigente;
    }

    public function create(Request $request)
    {
        $year = $this->gestionCobro($request);
        $esAnterior = $year < gestion_actual();
        // En la gestión anterior ya terminada se deben todos los meses.
        $mesCorte = $esAnterior ? 12 : mes_de_gestion();
        $gestionesCobro = [gestion_actual()];
        if (Inscripcion::where('insc_gestion', gestion_actual() - 1)->whereIn('insc_estado', [1, 2])->exists()) {
            $gestionesCobro[] = gestion_actual() - 1;
        }

        // El listado de mora enlaza con ?est_codigo=..., pero nadie lo leía y el
        // cajero tenía que volver a buscar al alumno a mano.
        $estPreseleccionado = $request->query('est_codigo');

        // Cargar padres que tienen estudiantes inscritos
        $padres = PadreFamilia::activo()
            ->whereHas('estudiantes', function($q) use ($year, $esAnterior) {
                // En la gestión anterior se incluye a los no reinscritos (ocultos): pueden deber.
                if (!$esAnterior) $q->where('est_visible', 1);
                $q->whereHas('inscripcion', function($qi) use ($year) {
                      $qi->where('insc_gestion', $year)->whereIn('insc_estado', [1, 2]);
                  });
            })
            ->orderBy('pfam_nombres')
            ->get();

        // Preparar datos de estudiantes inscritos con su info de pagos
        $estudiantes = Estudiante::query()
            ->when(!$esAnterior, fn($q) => $q->where('est_visible', 1))
            ->with([
                'curso',
                'padres',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)
                      ->whereIn('insc_estado', [1, 2])
                      ->with(['descuentos', 'padreFamilia']);
                }
            ])
            ->whereHas('inscripcion', function($q) use ($year) {
                $q->where('insc_gestion', $year)->whereIn('insc_estado', [1, 2]);
            })
            ->get();

        $estudiantesData = [];
        foreach ($estudiantes as $est) {
            if (!$est->inscripcion) continue;

            $insc = $est->inscripcion;
            $montoFinal = $insc->insc_monto_final ?? 0;
            $montoInscripcion = $insc->insc_monto_pagado ?? 0;
            $esSoloRegistro = $montoInscripcion == 0;
            $montoMensualidad = $montoFinal > 0 ? $montoFinal / 10 : 0;
            // Descuento de inscripción solo aplica a febrero si hubo pago real de inscripción
            $cuotaFebrero = $esSoloRegistro ? $montoMensualidad : max(0, $montoMensualidad - $montoInscripcion);

            // Pagos activos del año
            $pagos = Pago::where('est_codigo', $est->est_codigo)
                ->deGestion($year)
                ->where('pagos_estado', 1)
                ->orderBy('pagos_fecha', 'asc')
                ->get();

            $saldoAcumulado = $pagos->sum('pagos_precio');

            // Determinar meses pagados por concepto (fuente de verdad)
            $mesesMap = [2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre'];
            $mesesPagados = [];
            foreach ($pagos as $p) {
                foreach ($mesesMap as $num => $nombre) {
                    if (stripos($p->concepto, $nombre) !== false && !in_array($num, $mesesPagados)) {
                        $mesesPagados[] = $num;
                    }
                }
            }
            sort($mesesPagados);

            // Historial de pagos para mostrar en la vista
            $historial = $pagos->map(function($p) {
                return [
                    'fecha' => $p->pagos_fecha ? $p->pagos_fecha->format('d/m/Y') : '-',
                    'concepto' => $p->concepto,
                    'monto' => $p->pagos_precio,
                    'codigo' => $p->pagos_codigo,
                ];
            })->values()->toArray();

            // Descuentos aplicados
            $descuentos = $insc->descuentos->map(function($d) {
                return $d->desc_nombre . ' (-Bs. ' . number_format($d->pivot->inscdesc_monto_descuento, 2) . ')';
            })->toArray();

            // Saldo pendiente: solo meses disponibles (no vencidos) × mensualidad - pagado
            $mesActualNum = $mesCorte;
            // Caso especial: el periodo arranca en insc_mes_inicio; los meses anteriores no cuentan.
            $mesInicioCasoEspecial = ($insc->insc_caso_especial ?? 0) == 1
                ? (int) $insc->insc_mes_inicio
                : null;
            // Determinar primer mes del estudiante: el menor entre mes actual y primer mes pagado
            $primerMesPagado = !empty($mesesPagados) ? min($mesesPagados) : $mesActualNum;
            $mesInicioEst = max($primerMesPagado, 2); // mínimo febrero
            if ($mesInicioCasoEspecial) {
                $mesInicioEst = max($mesInicioEst, $mesInicioCasoEspecial);
            }
            $mesesVencidos = 0;
            for ($mv = 2; $mv < $mesInicioEst; $mv++) {
                if (!in_array($mv, $mesesPagados)) $mesesVencidos++;
            }
            // También contar vencidos entre mesInicioEst y mesActual que no estén pagados
            for ($mv = $mesInicioEst; $mv < $mesActualNum; $mv++) {
                if (!in_array($mv, $mesesPagados)) $mesesVencidos++;
            }
            $mesesCobrables = 10 - $mesesVencidos;
            // SALDO: misma fórmula que el REPORTE DE INSCRIPCIONES (única fuente de verdad):
            //   total pagado = inscripción + mensualidades ; saldo = monto_final − total pagado.
            // No se reduce por "meses vencidos" (eso daba cifras distintas al reporte).
            $totalPagadoReal = $saldoAcumulado + ($esSoloRegistro ? 0 : $montoInscripcion);
            $montoACobrar = $montoFinal;
            $saldoPendiente = max(0, $montoFinal - $totalPagadoReal);

            $estudiantesData[$est->est_codigo] = [
                'est_codigo' => $est->est_codigo,
                'nombre' => $est->est_nombres . ' ' . $est->est_apellidos,
                'apellidos' => $est->est_apellidos ?? '',
                'nombres' => $est->est_nombres ?? '',
                'ci' => $est->est_ci ?? '',
                'curso' => $est->curso->cur_nombre ?? 'N/A',
                'mensualidad' => $montoMensualidad,
                'cuota_febrero' => $cuotaFebrero,
                'solo_registro' => $esSoloRegistro,
                'meses_pagados' => $mesesPagados,
                'primer_mes' => $mesInicioEst,
                'sin_factura' => $insc->insc_sin_factura ?? 0,
                'padres' => $est->padres->pluck('pfam_codigo')->toArray(),
                'monto_total' => $insc->insc_monto_total ?? 0,
                'monto_descuento' => $insc->insc_monto_descuento ?? 0,
                'monto_final' => $montoFinal,
                'monto_inscripcion' => $montoInscripcion,
                'total_mensualidades' => $saldoAcumulado,
                'total_pagado' => $totalPagadoReal,
                'saldo_pendiente' => $saldoPendiente,
                'meses_cobrables' => $mesesCobrables,
                'monto_a_cobrar' => $montoACobrar,
                'descuentos' => $descuentos,
                'historial' => $historial,
            ];
        }

        return view('pagos.create', compact('padres', 'estudiantesData', 'estPreseleccionado', 'year', 'mesCorte', 'gestionesCobro'));
    }

    public function store(Request $request)
    {
        // Crear padre nuevo si aplica
        if ($request->filled('pfam_nombre_nuevo')) {
            $padre = PadreFamilia::create([
                'pfam_codigo' => 'Pad' . str_pad(PadreFamilia::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
                'pfam_nombres' => $request->pfam_nombre_nuevo,
                'pfam_ci' => '0000000',
                'pfam_estado' => 1
            ]);
            $pfamCodigo = $padre->pfam_codigo;
        } else {
            $request->validate(['pfam_codigo' => 'required']);
            $pfamCodigo = $request->pfam_codigo;
        }

        $estudiantesInput = $request->input('estudiantes', []);
        if (empty($estudiantesInput)) {
            return back()->withErrors(['error' => 'Debe seleccionar al menos un estudiante.'])->withInput();
        }

        $year = $this->gestionCobro($request);
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // Un solo código de recibo para toda la transacción (se reserva más
        // abajo, dentro de la transacción del cobro)
        $sinFactura = intval($estudiantesInput[array_key_first($estudiantesInput)]['sin_factura'] ?? 0);

        // ── Datos de método de pago (nivel transacción) ──
        $metodo = in_array($request->pagos_metodo, ['EFECTIVO', 'QR', 'MIXTO']) ? $request->pagos_metodo : 'EFECTIVO';
        $comprobantePath = null;
        if ($request->hasFile('pagos_comprobante')) {
            $comprobantePath = $request->file('pagos_comprobante')->store('pagos/comprobantes', 'public');
        }
        $transferenciaNro  = $request->input('pagos_transferencia_nro');
        $transferenciaHora = $request->input('pagos_transferencia_hora');
        $reciboNro         = $request->input('pagos_recibo_nro');
        $viaWhatsapp       = $request->boolean('pagos_via_whatsapp') ? 1 : 0;

        // 1ª pasada: armar la lista de cuotas a cobrar y el total general
        $cuotas = [];
        foreach ($estudiantesInput as $estData) {
            $estCodigo = $estData['est_codigo'];
            $mesInicio = intval($estData['mes']);
            $cantidadCuotas = intval($estData['cantidad_cuotas']);
            $sinFactura = intval($estData['sin_factura'] ?? 0);

            $insc = Inscripcion::where('est_codigo', $estCodigo)
                ->where('insc_gestion', $year)->whereIn('insc_estado', [1, 2])->first();
            $montoFinal = $insc ? ($insc->insc_monto_final ?? 0) : 0;
            $montoInscripcion = $insc ? ($insc->insc_monto_pagado ?? 0) : 0;
            $esSoloRegistro = $montoInscripcion == 0;
            $montoMensualidad = $montoFinal > 0 ? $montoFinal / 10 : 0;
            $cuotaFebrero = $esSoloRegistro ? $montoMensualidad : max(0, $montoMensualidad - $montoInscripcion);

            // Meses a pagar: a partir del mes elegido (libre, aunque esté vencido), saltando pagados
            $mesesAPagar = [];
            for ($m = $mesInicio; $m <= 11 && count($mesesAPagar) < $cantidadCuotas; $m++) {
                $existe = Pago::where('est_codigo', $estCodigo)
                    ->deGestion($year)->where('pagos_estado', 1)
                    ->where(function ($q) use ($m, $mesesNombres) {
                        $q->where('pagos_mes', $m)->orWhere(function ($q2) use ($m, $mesesNombres) {
                            $q2->whereNull('pagos_mes')->where('concepto', 'like', '%' . $mesesNombres[$m] . '%');
                        });
                    })->exists();
                if ($existe) continue;
                $mesesAPagar[] = $m;
            }
            if (empty($mesesAPagar)) {
                $est = Estudiante::where('est_codigo', $estCodigo)->first();
                return back()->withErrors(['error' => ($est->est_nombres ?? '') . ' no tiene meses disponibles para pagar.'])->withInput();
            }
            foreach ($mesesAPagar as $mes) {
                $cuotas[] = [
                    'est'   => $estCodigo,
                    'insc'  => $insc->insc_codigo ?? null,
                    'mes'   => $mes,
                    'monto' => ($mes === 2) ? $cuotaFebrero : $montoMensualidad,
                    'sf'    => $sinFactura ? 1 : 0,
                ];
            }
        }

        $totalGeneral = array_sum(array_column($cuotas, 'monto'));

        // Validar y preparar reparto efectivo/QR para MIXTO
        $efRatio = 1.0;
        if ($metodo === 'MIXTO') {
            $montoEf = (float) $request->input('pagos_monto_efectivo', 0);
            $montoQr = (float) $request->input('pagos_monto_qr', 0);
            if (abs(($montoEf + $montoQr) - $totalGeneral) > 0.5) {
                return back()->withErrors(['error' => 'En pago MIXTO, efectivo (' . $montoEf . ') + QR (' . $montoQr . ') debe igualar el total (' . $totalGeneral . ').'])->withInput();
            }
            $efRatio = $totalGeneral > 0 ? $montoEf / $totalGeneral : 0;
        } elseif ($metodo === 'QR') {
            $efRatio = 0.0;
        }

        // 2ª pasada: crear los pagos con los datos de método. Número de recibo y
        // cuotas se guardan juntos: si algo falla, no queda un recibo a medias
        // ni un número consumido.
        DB::transaction(function () use ($cuotas, $metodo, $efRatio, $sinFactura, $pfamCodigo, $year, $mesesNombres,
                                         $comprobantePath, $transferenciaNro, $transferenciaHora, $reciboNro, $viaWhatsapp) {
        $codigoRecibo = \App\Services\ReciboNumerador::siguiente($sinFactura ? 'MEN-TAL' : 'MEN-REC');
        foreach ($cuotas as $c) {
            $montoCuota = $c['monto'];
            if ($metodo === 'EFECTIVO') { $ef = $montoCuota; $qr = 0; }
            elseif ($metodo === 'QR')   { $ef = 0; $qr = $montoCuota; }
            else { $ef = round($montoCuota * $efRatio, 2); $qr = round($montoCuota - $ef, 2); }

            Pago::create([
                'pagos_codigo' => $codigoRecibo,
                'men_codigo' => 'PAGO' . str_pad(Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                'est_codigo' => $c['est'],
                'pfam_codigo' => $pfamCodigo,
                'prod_codigo' => 'MENSUALIDAD',
                'pagos_precio' => $montoCuota,
                'pagos_nombres' => 'Mensualidad ' . $mesesNombres[$c['mes']],
                'pagos_usuario' => auth()->user()->us_codigo ?? 'ADMIN',
                'pagos_descuento' => 0,
                'concepto' => 'Mensualidad ' . $mesesNombres[$c['mes']],
                'tipo' => 1,
                'pagos_fecha' => now(),
                'pagos_gestion' => $year,
                'pagos_mes' => $c['mes'],
                'insc_codigo' => $c['insc'],
                'pagos_sin_factura' => $c['sf'],
                'pagos_metodo' => $metodo,
                'pagos_monto_efectivo' => $ef,
                'pagos_monto_qr' => $qr,
                'pagos_comprobante' => $comprobantePath,
                'pagos_transferencia_nro' => $transferenciaNro,
                'pagos_transferencia_hora' => $transferenciaHora,
                'pagos_recibo_nro' => $reciboNro,
                'pagos_via_whatsapp' => $viaWhatsapp,
            ]);
        }
        });

        return redirect()->route('pagos.index')->with('success', 'Pago(s) registrado(s) exitosamente');
    }

    public function show($id)
    {
        $pago = Pago::with('estudiante', 'padreFamilia')->findOrFail($id);
        return view('pagos.show', compact('pago'));
    }

    public function reportePdf(Request $request)
    {
        $query = Pago::with('estudiante.curso', 'padreFamilia')->where('pagos_estado', 1);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('pagos_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('pagos_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }

        // groupBy respeta el orden de llegada: ordenando por est_codigo los cursos
        // salian en el orden en que apareciera su primer alumno. Se ordena por
        // cur_orden en PHP (cur_orden vive en otra tabla y con otra colacion).
        $pagos = $query->get()->sortBy([
            fn($a, $b) => (optional(optional($a->estudiante)->curso)->cur_orden ?? 9999)
                       <=> (optional(optional($b->estudiante)->curso)->cur_orden ?? 9999),
            fn($a, $b) => strcmp(optional(optional($a->estudiante)->curso)->cur_nombre ?? '',
                                 optional(optional($b->estudiante)->curso)->cur_nombre ?? ''),
            fn($a, $b) => strcmp(optional($a->estudiante)->est_apellidos ?? '',
                                 optional($b->estudiante)->est_apellidos ?? ''),
        ])->values();

        $pdf = Pdf::loadView('pagos.reporte-mensualidades-pdf', compact('pagos', 'request'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('reporte-mensualidades-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Antes devolvía un `storage/app/reporte-pagos.xlsx` fijo que no existe (el
     * botón siempre reventaba), ignorando además los filtros de la pantalla.
     * Ahora usa PagosExport, que estaba escrito y sin usar.
     */
    public function reporteExcel(Request $request)
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'est_codigo', 'cur_codigo', 'concepto']);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PagosExport($filtros),
            'listado-pagos-' . date('Y-m-d') . '.xlsx'
        );
    }

    public function getPadresByEstudiante($est_codigo)
    {
        $estudiante = Estudiante::where('est_codigo', $est_codigo)->with('padres')->first();
        return response()->json($estudiante ? $estudiante->padres : []);
    }

    public function getEstudianteInscripcion($est_codigo)
    {
        $year = gestion_actual();
        $estudiante = Estudiante::where('est_codigo', $est_codigo)
            ->with([
                'padres',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)
                      ->whereIn('insc_estado', [1, 2])
                      ->with('descuentos');
                }
            ])
            ->first();
        
        if ($estudiante && $estudiante->inscripcion) {
            $pagosRealizados = Pago::where('est_codigo', $est_codigo)
                ->deGestion($year)
                ->where('pagos_estado', 1)
                ->count();
            
            $estudiante->inscripcion->pagos_realizados = $pagosRealizados;
        }
        
        return response()->json([
            'inscripcion' => $estudiante->inscripcion ?? null,
            'padres' => $estudiante->padres ?? []
        ]);
    }

    public function anular(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);
        // Anular todos los pagos con el mismo código (pago conjunto). Queda quién,
        // cuándo y por qué: antes sólo se ponía el estado en 0.
        Pago::where('pagos_codigo', $pago->pagos_codigo)->where('pagos_estado', 1)->update([
            'pagos_estado'   => 0,
            'anulado_por'    => auth()->user()->us_id,
            'anulado_fecha'  => now(),
            'anulado_motivo' => mb_substr(trim((string) $request->input('motivo')) ?: 'Sin motivo indicado', 0, 255),
        ]);

        return response()->json(['success' => true, 'message' => 'Pago(s) anulado(s)']);
    }

    public function resumenAnual()
    {
        $cursos = Curso::visible()->get();
        return view('pagos.resumen-anual', compact('cursos'));
    }

    public function resumenAnualPdf(Request $request)
    {
        $year = $request->year ?? gestion_actual();
        
        $query = Pago::with('estudiante.curso')
            ->deGestion($year)
            ->where('pagos_estado', 1);

        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }

        $pagos = $query->get();
        
        $cursos = $request->filled('cur_codigo') 
            ? Curso::where('cur_codigo', $request->cur_codigo)->get()
            : Curso::visible()->ordenado()->get();
        
        $pdf = Pdf::loadView('pagos.resumen-anual-pdf', compact('pagos', 'year', 'cursos'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('resumen-anual-' . $year . '.pdf');
    }

    public function resumenAnualExcel(Request $request)
    {
        $year = $request->year ?? gestion_actual();
        $query = Estudiante::visible()->with(['curso', 'pagos' => function($q) use ($year) {
            // Los anulados no son ingresos: el PDF ya los excluia y el Excel no,
            // asi que los dos reportes no cuadraban.
            $q->deGestion($year)->where('pagos_estado', 1);
        }]);

        if ($request->filled('cur_codigo')) {
            $query->where('cur_codigo', $request->cur_codigo);
        }

        // Orden de cursos real (cur_orden), no alfabético por código: antes
        // salía 1ro Primaria, 1ro Secundaria, 2do Primaria… e Inicial al final.
        // Se ordena en PHP porque cur_orden está en otra tabla y colación.
        $estudiantes = $query->get()->sortBy([
            fn($a, $b) => ($a->curso->cur_orden ?? 9999) <=> ($b->curso->cur_orden ?? 9999),
            fn($a, $b) => strcmp($a->curso->cur_nombre ?? '', $b->curso->cur_nombre ?? ''),
            fn($a, $b) => strcmp($a->est_apellidos . ' ' . $a->est_nombres, $b->est_apellidos . ' ' . $b->est_nombres),
        ])->values();
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResumenAnualExport($estudiantes, $year), 
            'resumen-anual-' . $year . '.xlsx'
        );
    }

    /**
     * Estudiantes con al menos un mes impago entre el inicio de cuotas y el mes
     * de corte. Antes se miraba un único mes, así que un alumno que debía marzo
     * pero había pagado abril no salía en la lista. Ahora la deuda se acumula.
     *
     * El inicio de cuotas es febrero, salvo Registro Especial (caso especial),
     * donde arranca en insc_mes_inicio: los meses previos no se le cobran.
     *
     * Deja calculado en cada estudiante, para que la pantalla y el PDF muestren
     * lo mismo: mora_meses_pagados, mora_meses_pendientes, mora_mensualidad y
     * mora_deuda.
     */
    private function calcularMora(int $mesCorte, ?string $curCodigo, int $year)
    {
        // La cuenta vive en MoraService: la comparten caja, la pre-inscripción y
        // el portal de padres, para que todos muestren la misma deuda.
        return (new \App\Services\MoraService())->enMora($mesCorte, $year, $curCodigo);
    }

    public function mora()
    {
        $year = (int) gestion_actual();
        $mesActual = request()->filled('mes') ? (int) request('mes') : mes_de_gestion();

        $estudiantesEnMora = $this->calcularMora($mesActual, request('cur_codigo'), $year);
        $deudaTotal = $estudiantesEnMora->sum('mora_deuda');

        $cursos = Curso::visible()->get();
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return view('pagos.mora', compact('estudiantesEnMora', 'cursos', 'mesActual', 'mesesNombres', 'deudaTotal'));
    }

    public function moraPdf(Request $request)
    {
        $year = (int) gestion_actual();
        $mesActual = $request->filled('mes') ? (int) $request->mes : mes_de_gestion();

        $estudiantesEnMora = $this->calcularMora($mesActual, $request->cur_codigo, $year);
        $deudaTotal = $estudiantesEnMora->sum('mora_deuda');

        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $pdf = Pdf::loadView('pagos.mora-pdf', compact('estudiantesEnMora', 'mesActual', 'mesesNombres', 'year', 'deudaTotal'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('estudiantes-mora-' . date('Y-m-d') . '.pdf');
    }
}
