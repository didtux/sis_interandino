<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use App\Models\Curso;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $query = Inscripcion::with(['estudiante.padres', 'curso', 'padreFamilia', 'pagos', 'descuentos']);

        // Gestión: sin este filtro la grilla mezclaba años y los totales de abajo
        // sumaban inscripciones de gestiones anteriores. Por defecto, la actual.
        $gestiones = Inscripcion::select('insc_gestion')->distinct()
            ->orderBy('insc_gestion', 'desc')->pluck('insc_gestion')->filter()->values();
        $gestion = $request->input('gestion', gestion_actual());
        if ($gestion !== 'todas') {
            $query->where('insc_gestion', $gestion);
        }

        if ($request->buscar) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('est_nombres', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_apellidos', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_ci', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->pfam_codigo) {
            $query->where('pfam_codigo', $request->pfam_codigo);
        }

        if ($request->estado === '0') {
            $query->where('insc_estado', 0);
        } elseif ($request->estado === 'activas') {
            $query->where('insc_estado', '!=', 0);
        }

        if ($request->tipo_factura === '1') {
            $query->where('insc_sin_factura', 1);
        } elseif ($request->tipo_factura === '0') {
            $query->where('insc_sin_factura', 0);
        }

        if ($request->descuento === 'con_descuento') {
            $query->whereHas('descuentos');
        } elseif ($request->descuento === 'sin_descuento') {
            $query->whereDoesntHave('descuentos');
        } elseif ($request->descuento && is_numeric($request->descuento)) {
            $query->whereHas('descuentos', function($q) use ($request) {
                $q->where('desc_id', $request->descuento);
            });
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->paginate(50);
        $descuentos = \App\Models\Descuento::where('desc_estado', 1)->get();

        // Precargar total de mensualidades pagadas por estudiante
        // (del mismo año que se está mirando, no siempre del actual)
        $year = $gestion === 'todas' ? gestion_actual() : (int) $gestion;
        $mesActualNum = mes_de_gestion();
        $estCodigos = $inscripciones->pluck('est_codigo')->unique()->toArray();
        $mensualidadesPagadas = Pago::whereIn('est_codigo', $estCodigos)
            ->deGestion($year)
            ->where('pagos_estado', 1)
            ->selectRaw('est_codigo, SUM(pagos_precio) as total_mensualidades')
            ->groupBy('est_codigo')
            ->pluck('total_mensualidades', 'est_codigo');

        // Precargar meses pagados por concepto para determinar primer_mes y vencidos
        $mesesMap = [2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre'];
        $primerMesPorEst = [];
        if (!empty($estCodigos)) {
            $pagosConcepto = Pago::whereIn('est_codigo', $estCodigos)
                ->deGestion($year)
                ->where('pagos_estado', 1)
                ->select('est_codigo', 'concepto')
                ->get();
            foreach ($pagosConcepto as $p) {
                foreach ($mesesMap as $num => $nombre) {
                    if (stripos($p->concepto, $nombre) !== false) {
                        if (!isset($primerMesPorEst[$p->est_codigo]) || $num < $primerMesPorEst[$p->est_codigo]) {
                            $primerMesPorEst[$p->est_codigo] = $num;
                        }
                    }
                }
            }
        }

        return view('inscripciones.index', compact('inscripciones', 'descuentos', 'mensualidadesPagadas', 'primerMesPorEst', 'mesActualNum', 'gestiones', 'gestion'));
    }

    public function reportes(Request $request)
    {
        $estudiantes = Estudiante::visible()->get();
        
        $query = Inscripcion::with(['estudiante', 'curso', 'padreFamilia']);

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->estado !== null && $request->estado !== '') {
            $query->where('insc_estado', $request->estado);
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->get();

        // Mensualidades pagadas
        $year = gestion_actual();
        $estCodigos = $inscripciones->pluck('est_codigo')->unique()->toArray();
        $mensualidadesPagadas = Pago::whereIn('est_codigo', $estCodigos)
            ->deGestion($year)
            ->where('pagos_estado', 1)
            ->selectRaw('est_codigo, SUM(pagos_precio) as total_mensualidades')
            ->groupBy('est_codigo')
            ->pluck('total_mensualidades', 'est_codigo');

        return view('inscripciones.reportes', compact('estudiantes', 'inscripciones', 'mensualidadesPagadas'));
    }

    public function reportePdf(Request $request)
    {
        $query = Inscripcion::with(['estudiante', 'curso', 'padreFamilia', 'descuentos']);

        // Misma gestión que la grilla de la que se exporta (ver index()).
        $gestion = $request->input('gestion', gestion_actual());
        if ($gestion !== 'todas') {
            $query->where('insc_gestion', $gestion);
        }

        if ($request->buscar) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('est_nombres', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_apellidos', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_ci', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->pfam_codigo) {
            $query->where('pfam_codigo', $request->pfam_codigo);
        }

        if ($request->estado === '0') {
            $query->where('insc_estado', 0);
        } elseif ($request->estado === 'activas') {
            $query->where('insc_estado', '!=', 0);
        }

        if ($request->tipo_factura === '1') {
            $query->where('insc_sin_factura', 1);
        } elseif ($request->tipo_factura === '0') {
            $query->where('insc_sin_factura', 0);
        }

        if ($request->descuento === 'con_descuento') {
            $query->whereHas('descuentos');
        } elseif ($request->descuento === 'sin_descuento') {
            $query->whereDoesntHave('descuentos');
        } elseif ($request->descuento && is_numeric($request->descuento)) {
            $query->whereHas('descuentos', function($q) use ($request) {
                $q->where('desc_id', $request->descuento);
            });
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->get();

        // Mensualidades pagadas por estudiante
        $year = $gestion === 'todas' ? gestion_actual() : (int) $gestion;
        $estCodigos = $inscripciones->pluck('est_codigo')->unique()->toArray();
        $mensualidadesPagadas = Pago::whereIn('est_codigo', $estCodigos)
            ->deGestion($year)
            ->where('pagos_estado', 1)
            ->selectRaw('est_codigo, SUM(pagos_precio) as total_mensualidades')
            ->groupBy('est_codigo')
            ->pluck('total_mensualidades', 'est_codigo');

        // Totales excluyendo anuladas y solo registro
        $activas = $inscripciones->where('insc_estado', '!=', 0);
        $total = $activas->sum('insc_monto_final');
        $pagado = 0;
        $saldo = 0;
        foreach ($activas as $i) {
            $mens = $mensualidadesPagadas[$i->est_codigo] ?? 0;
            $pagadoEst = $i->insc_monto_pagado + $mens;
            $pagado += $pagadoEst;
            $saldo += max(0, $i->insc_monto_final - $pagadoEst);
        }

        $pdf = \PDF::loadView('inscripciones.reporte-pdf', compact('inscripciones', 'total', 'pagado', 'saldo', 'request', 'mensualidadesPagadas'));
        return $pdf->stream('inscripciones_' . time() . '.pdf');
    }

    public function create()
    {
        $gestion = (int) gestion_actual();

        // Estudiantes que YA tienen inscripción activa en la gestión actual
        $yaInscritos = Inscripcion::where('insc_gestion', $gestion)
            ->whereIn('insc_estado', [1, 2])
            ->pluck('est_codigo')
            ->all();

        // Sólo se muestran los que aún no tienen registro en esta gestión. Se
        // suman los de la gestión anterior que quedaron ocultos al pasar de año
        // por no haberse reinscrito: así se los puede inscribir tarde.
        $deAnterior = \App\Models\EstudianteGestion::where('ges_anio', $gestion - 1)->pluck('est_codigo');
        $estudiantes = Estudiante::where(fn($q) => $q->where('est_visible', 1)->orWhereIn('est_codigo', $deAnterior))
            ->whereNotIn('est_codigo', $yaInscritos)
            ->orderBy('est_apellidos')->orderBy('est_nombres')
            ->get();

        $padres = PadreFamilia::where('pfam_estado', 1)->get();
        $cursos = Curso::visible()->get();
        $descuentos = \App\Models\Descuento::where('desc_estado', 1)->get();
        return view('inscripciones.create', compact('estudiantes', 'padres', 'cursos', 'descuentos', 'gestion'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'cur_codigo' => 'required|exists:colegio_cursos,cur_codigo',
            'insc_monto_total' => 'required|numeric|min:0',
            'insc_monto_pagado' => 'nullable|numeric|min:0|max:500',
            'insc_gestion' => 'required'
        ]);

        // Verificar duplicados
        $existe = Inscripcion::where('est_codigo', $request->est_codigo)
            ->where('insc_gestion', $request->insc_gestion)
            ->where('insc_estado', '!=', 0)
            ->exists();
        if ($existe) {
            return back()->withErrors(['error' => 'Ya existe una inscripción activa para este estudiante en la gestión ' . $request->insc_gestion . '.'])->withInput();
        }

        // ── Lista negra de observados (bloqueo) ──
        // Por código de estudiante, o por CI (caso de observados agregados sin estar inscritos).
        $obs = \App\Models\EstudianteObservado::vigentePara($request->est_codigo, (int) $request->insc_gestion);
        if (!$obs) {
            $ciEst = \App\Models\Estudiante::where('est_codigo', $request->est_codigo)->value('est_ci');
            $obs = \App\Models\EstudianteObservado::vigentePorCi($ciEst, (int) $request->insc_gestion);
        }
        if ($obs) {
            // Override solo para dirección (Admin/Director General/Directora Académica) si envía "override=1"
            $user = auth()->user();
            $puedeOverride = $user->tieneCapacidad('observados.autorizar');
            if (!$puedeOverride || !$request->boolean('override_observado')) {
                return back()->withErrors([
                    'error' => 'ESTUDIANTE BLOQUEADO. Motivo ('.$obs->obs_motivo_tipo.'): '.$obs->obs_motivo.'. Solo la dirección puede levantar el bloqueo.'
                ])->withInput();
            }
        }

        // Padre nuevo: si ya existe uno con ese CI se reutiliza (antes se creaba
        // siempre otro, con CI 0000000, y la familia quedaba partida).
        if ($request->filled('pfam_nombre_nuevo')) {
            $padre = PadreFamilia::obtenerOCrear($request->pfam_nombre_nuevo, $request->pfam_ci_nuevo);
            $padre->vincularHijo($request->est_codigo);
            $pfamCodigo = $padre->pfam_codigo;
        } else {
            $request->validate(['pfam_codigo' => 'required|exists:cole_padresfamilia,pfam_codigo']);
            $pfamCodigo = $request->pfam_codigo;
        }

        $montoTotal = $request->insc_monto_total;
        $montoDescuento = $request->insc_monto_descuento ?? 0;
        $montoFinal = $request->insc_monto_final ?? $montoTotal;
        $montoPagado = min($request->insc_monto_pagado ?? 0, 500);
        $sinFactura = $request->insc_sin_factura ?? 0;
        $soloRegistro = $request->has('insc_solo_registro');
        // Caso Especial: inscripción retroactiva, los meses previos a insc_mes_inicio
        // NO se contabilizan como vencidos.
        $casoEspecial  = $request->has('insc_caso_especial');
        $mesInicio     = $casoEspecial ? max(2, min(12, (int) ($request->insc_mes_inicio ?? mes_de_gestion()))) : null;

        // El mes al que se imputa la primera mensualidad no puede ser anterior al
        // mes actual, salvo en Registro Especial, donde el piso es el mes de inicio
        // retroactivo. Hasta ahora esto sólo existía en la vista (option disabled)
        // y el backend aceptaba cualquier mes que le llegara.
        if ($soloRegistro && $request->filled('insc_mes_destino')) {
            $mesDestinoReq = (int) $request->insc_mes_destino;
            $mesMinimo     = $casoEspecial ? $mesInicio : mes_de_gestion();
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            if ($mesDestinoReq < $mesMinimo) {
                return back()->withErrors(['error' =>
                    'El mes destino de la primera mensualidad (' . ($meses[$mesDestinoReq] ?? $mesDestinoReq) . ') es anterior a ' .
                    ($meses[$mesMinimo] ?? $mesMinimo) . '. ' .
                    ($casoEspecial
                        ? 'En Registro Especial no puede ser anterior al mes de inicio retroactivo.'
                        : 'Para imputarlo a un mes anterior marcá "Registro Especial" e indicá el mes de inicio.')
                ])->withInput();
            }
        }

        // Inscripción, descuento y primer pago se guardan juntos: si algo falla
        // no queda una inscripción sin su pago ni un número de recibo consumido.
        return DB::transaction(function () use ($request, $pfamCodigo, $montoTotal, $montoDescuento, $montoFinal,
                                                  $montoPagado, $sinFactura, $soloRegistro, $casoEspecial, $mesInicio) {
        // Código secuencial INSC000XXX
        $codigoInsc = \App\Services\ReciboNumerador::siguiente('INSC');

        // Si es solo registro, el pago va a mensualidad, no a inscripción
        $montoPagadoInsc = $soloRegistro ? 0 : $montoPagado;

        $inscripcion = Inscripcion::create([
            'insc_codigo' => $codigoInsc,
            'est_codigo' => $request->est_codigo,
            'pfam_codigo' => $pfamCodigo,
            'cur_codigo' => $request->cur_codigo,
            'insc_gestion' => $request->insc_gestion,
            'insc_monto_total' => $montoTotal,
            'insc_monto_descuento' => $montoDescuento,
            'insc_monto_final' => $montoFinal,
            'insc_monto_pagado' => $montoPagadoInsc,
            'insc_saldo' => $montoFinal - $montoPagadoInsc,
            'insc_concepto' => $request->insc_concepto
                . ($soloRegistro ? ' (Fuera de periodo - solo registro)' : '')
                . ($casoEspecial ? ' (Caso Especial - inicio retroactivo)' : ''),
            'insc_estado' => 1,
            'insc_usuario' => auth()->user()->us_codigo,
            'insc_sin_factura' => $sinFactura,
            'insc_caso_especial' => $casoEspecial ? 1 : 0,
            'insc_mes_inicio' => $mesInicio,
        ]);

        // Registrar descuento si existe
        if ($request->desc_id) {
            $inscripcion->descuentos()->attach($request->desc_id, [
                'inscdesc_monto_descuento' => $montoDescuento
            ]);
        }

        // Historial por gestión. Si es la gestión vigente, la ficha pasa al curso
        // de la inscripción: antes la inscripción no tocaba el curso del alumno
        // y quedaban fichas en un curso e inscripciones en otro.
        \App\Models\EstudianteGestion::registrar($request->est_codigo, (int) $request->insc_gestion, $request->cur_codigo, $codigoInsc);
        if ((int) $request->insc_gestion === gestion_actual()) {
            Estudiante::where('est_codigo', $request->est_codigo)->update(['cur_codigo' => $request->cur_codigo, 'est_visible' => 1]);
        }

        $prefijoRecibo = $sinFactura ? 'TAL' : 'REC';

        if ($soloRegistro && $montoPagado > 0) {
            // FUERA DE TIEMPO: los 500 van como primera mensualidad del mes seleccionado.
            // El mes destino no puede ser anterior al mes actual, salvo Registro
            // Especial, donde el piso es el mes de inicio retroactivo. Hasta ahora
            // esto sólo estaba en la vista (option disabled) y el backend aceptaba
            // cualquier mes que le mandaran.
            $mesDestino = intval($request->insc_mes_destino ?? mes_de_gestion());
            $mesesNombresArr = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $mesNombre = $mesesNombresArr[$mesDestino] ?? 'Mes';

            // Código recibo mensualidad (contador con bloqueo, sin duplicados)
            $codigoRecibo = \App\Services\ReciboNumerador::siguiente('MEN-' . $prefijoRecibo);

            Pago::create([
                'pagos_codigo' => $codigoRecibo,
                'men_codigo' => 'MEN' . str_pad(Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                'est_codigo' => $request->est_codigo,
                'pfam_codigo' => $pfamCodigo,
                'prod_codigo' => 'MENSUALIDAD',
                'pagos_precio' => $montoPagado,
                'pagos_nombres' => 'Mensualidad ' . $mesNombre,
                'pagos_usuario' => auth()->user()->us_codigo ?? 'ADMIN',
                'pagos_descuento' => 0,
                'concepto' => 'Mensualidad ' . $mesNombre,
                'tipo' => 1,
                'pagos_fecha' => now(),
                'pagos_gestion' => (int) $request->insc_gestion,
                'pagos_mes' => $mesDestino,
                'insc_codigo' => $inscripcion->insc_codigo,
                'pagos_metodo' => 'EFECTIVO',
                'pagos_monto_efectivo' => $montoPagado,
                'pagos_sin_factura' => $sinFactura ? 1 : 0
            ]);

        } elseif ($montoPagado > 0) {
            // NORMAL: pago de inscripción
            $codigoRecibo = \App\Services\ReciboNumerador::siguiente('INS-' . $prefijoRecibo);

            $ultimoPago = InscripcionPago::where('inscpago_codigo', 'REGEXP', '^PAGO[0-9]{6}$')
                ->orderByRaw('CAST(SUBSTRING(inscpago_codigo, 5) AS UNSIGNED) DESC')
                ->first();
            $numPago = $ultimoPago ? intval(substr($ultimoPago->inscpago_codigo, 4)) + 1 : 1;
            $codigoPago = 'PAGO' . str_pad($numPago, 6, '0', STR_PAD_LEFT);

            InscripcionPago::create([
                'inscpago_codigo' => $codigoPago,
                'insc_codigo' => $inscripcion->insc_codigo,
                'inscpago_monto' => $montoPagado,
                'inscpago_concepto' => 'Pago inicial de inscripción',
                'inscpago_usuario' => auth()->user()->us_codigo,
                'inscpago_recibo' => $codigoRecibo
            ]);
        }

        return redirect()->route('inscripciones.index')->with('success', 'Inscripción registrada' . ($soloRegistro ? ' (fuera de periodo - pago registrado como mensualidad)' : ''));
        });
    }

    public function registrarPago(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        
        $request->validate([
            'monto' => 'required|numeric|min:0|max:' . $inscripcion->insc_saldo
        ]);

        return DB::transaction(function () use ($request, $inscripcion) {
        // Código secuencial recibo (contador con bloqueo, sin duplicados)
        $prefijoRecibo = ($inscripcion->insc_sin_factura ?? 0) ? 'TAL' : 'REC';
        $codigoRecibo = \App\Services\ReciboNumerador::siguiente('INS-' . $prefijoRecibo);

        // Código secuencial pago
        $ultimoPago = InscripcionPago::where('inscpago_codigo', 'REGEXP', '^PAGO[0-9]{6}$')
            ->orderByRaw('CAST(SUBSTRING(inscpago_codigo, 5) AS UNSIGNED) DESC')
            ->first();
        $numPago = $ultimoPago ? intval(substr($ultimoPago->inscpago_codigo, 4)) + 1 : 1;
        $codigoPago = 'PAGO' . str_pad($numPago, 6, '0', STR_PAD_LEFT);

        InscripcionPago::create([
            'inscpago_codigo' => $codigoPago,
            'insc_codigo' => $inscripcion->insc_codigo,
            'inscpago_monto' => $request->monto,
            'inscpago_concepto' => $request->concepto ?? 'Pago de inscripción',
            'inscpago_usuario' => auth()->user()->us_codigo,
            'inscpago_recibo' => $codigoRecibo
        ]);

        $inscripcion->insc_monto_pagado += $request->monto;
        $inscripcion->insc_saldo -= $request->monto;
        
        if ($inscripcion->insc_saldo <= 0) {
            $inscripcion->insc_estado = 2;
        }
        
        $inscripcion->save();

        return redirect()->back()->with('success', 'Pago registrado');
        });
    }

    public function anular($id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->insc_estado = 0;
        $inscripcion->save();
        
        return response()->json(['success' => true, 'message' => 'Inscripción anulada']);
    }

    public function actualizarDescuento(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        
        // Padre nuevo: se reutiliza si ya existe con ese CI.
        if ($request->filled('pfam_nombre_nuevo')) {
            $padre = PadreFamilia::obtenerOCrear($request->pfam_nombre_nuevo, $request->pfam_ci_nuevo);
            $padre->vincularHijo($inscripcion->est_codigo);

            $inscripcion->pfam_codigo = $padre->pfam_codigo;
        } elseif ($request->filled('pfam_codigo')) {
            $inscripcion->pfam_codigo = $request->pfam_codigo;
        }
        
        $request->validate(['desc_id' => 'required|exists:descuentos,desc_id']);
        
        $descuento = \App\Models\Descuento::findOrFail($request->desc_id);
        
        // Calcular nuevo monto con descuento. Los descuentos por familia son un
        // monto fijo (250 Bs) cargado como 0,05 %: por porcentaje daban 2,38 Bs.
        $montoTotal = $inscripcion->insc_monto_total;
        $montoDescuento = $descuento->montoSobre((float) $montoTotal);
        $montoFinal = $montoTotal - $montoDescuento;
        
        // Actualizar inscripción
        $inscripcion->insc_monto_descuento = $montoDescuento;
        $inscripcion->insc_monto_final = $montoFinal;
        $inscripcion->insc_saldo = $montoFinal - $inscripcion->insc_monto_pagado;
        $inscripcion->save();
        
        // Actualizar relación con descuento
        $inscripcion->descuentos()->sync([
            $descuento->desc_id => ['inscdesc_monto_descuento' => $montoDescuento]
        ]);
        
        return redirect()->route('inscripciones.index')->with('success', 'Inscripción actualizada');
    }

    public function eliminarCargaMasiva(Request $request)
    {
        $year = gestion_actual();
        $eliminados = 0;
        $estudiantesEliminados = 0;
        
        // Obtener inscripciones de la gestión actual con código INSC
        $inscripciones = Inscripcion::where('insc_gestion', $year)
            ->where('insc_codigo', 'like', 'INSC%')
            ->get();
        
        $estudiantesCodigos = [];
        
        foreach($inscripciones as $insc) {
            $estudiantesCodigos[] = $insc->est_codigo;
            
            // Eliminar pagos de mensualidades relacionados
            \App\Models\Pago::where('est_codigo', $insc->est_codigo)
                ->deGestion($year)
                ->delete();
            
            // Eliminar pagos de inscripción
            InscripcionPago::where('insc_codigo', $insc->insc_codigo)->delete();
            
            // Eliminar relación con descuentos
            $insc->descuentos()->detach();
            
            // Eliminar inscripción
            $insc->delete();
            $eliminados++;
        }
        
        // Eliminar estudiantes creados en la carga (que no tienen otras inscripciones NI pagos de transporte)
        foreach(array_unique($estudiantesCodigos) as $estCodigo) {
            $estudiante = Estudiante::where('est_codigo', $estCodigo)->first();
            if($estudiante) {
                // Verificar si tiene otras inscripciones
                $tieneOtrasInscripciones = Inscripcion::where('est_codigo', $estCodigo)->exists();
                
                // Verificar si tiene pagos de transporte
                $tienePagosTransporte = \App\Models\PagoTransporte::where('est_codigo', $estCodigo)->exists();
                
                if(!$tieneOtrasInscripciones && !$tienePagosTransporte) {
                    // Eliminar relación con padres
                    \DB::table('rela_estudiantespadres')->where('est_id', $estCodigo)->delete();
                    
                    // Eliminar estudiante
                    $estudiante->delete();
                    $estudiantesEliminados++;
                }
            }
        }
        
        return redirect()->route('inscripciones.index')->with('success', "Eliminados: $eliminados inscripciones y $estudiantesEliminados estudiantes");
    }

    public function cargarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
            'mes_inscripcion' => 'required|integer|min:1|max:12'
        ]);
        
        $mesInscripcion = intval($request->mes_inscripcion);
        $file = $request->file('archivo');
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $data = $sheet->toArray(null, true, true, true);
        
        $year = gestion_actual();
        $registrados = 0;
        $saltados = [
            'anulados' => 0,
            'sin_datos' => 0,
            'monto_cero' => 0,
            'ya_existe' => 0
        ];
        
        foreach($data as $index => $row) {
            if($index == 1) continue;
            
            $numFactura = trim($row['B'] ?? ''); // Columna B: Número factura
            $ci = trim($row['C'] ?? ''); // Columna C: CI
            $nombreCompleto = trim($row['D'] ?? ''); // Columna D: NOMBRE
            $cursoNombre = trim($row['E'] ?? ''); // Columna E: CURSO
            
            // Debug: registrar fila 62
            if($index == 62) {
                \Log::info('Fila 62 Excel', [
                    'ci' => $ci,
                    'nombre' => $nombreCompleto,
                    'curso' => $cursoNombre,
                    'row_completo' => $row
                ]);
            }
            
            // Validar que al menos tenga CI o nombre
            if(empty($ci) && empty($nombreCompleto)) {
                $saltados['sin_datos']++;
                continue;
            }
            
            // Si el nombre contiene "anulado", saltar ANTES de crear estudiante
            if(stripos($nombreCompleto, 'anulado') !== false) {
                $saltados['anulados']++;
                continue;
            }
            
            // Si el apellido es "ANULADO", saltar
            $partes = explode(' ', $nombreCompleto, 2);
            $apellidos = $partes[0] ?? '';
            if(strtoupper(trim($apellidos)) === 'ANULADO') {
                $saltados['anulados']++;
                continue;
            }
            
            // Determinar si es sin factura (TAL) o con factura (REC)
            $sinFactura = empty($numFactura) ? 1 : 0;
            $prefijoRecibo = $sinFactura ? 'TAL' : 'REC';
            
            // Limpiar CI: extraer solo números
            $ciLimpio = preg_replace('/[^0-9]/', '', $ci);
            
            // Buscar estudiante por CI (prioritario) o por nombre
            $estudiante = null;
            $estudianteExistente = false;
            
            // 1. Buscar por CI si existe
            if(!empty($ciLimpio) && strlen($ciLimpio) >= 5) {
                $estudiante = Estudiante::whereRaw('REGEXP_REPLACE(est_ci, "[^0-9]", "") = ?', [$ciLimpio])->first();
                if($estudiante) {
                    $estudianteExistente = true;
                }
            }
            
            // 2. Si no encuentra por CI, buscar por nombre completo (búsqueda estricta)
            if(!$estudiante && !empty($nombreCompleto)) {
                $nombreLimpio = strtoupper(trim($nombreCompleto));
                $palabras = array_filter(explode(' ', $nombreLimpio));
                
                // Buscar estudiante que coincida con al menos 70% de las palabras
                if(count($palabras) >= 3) {
                    $estudiantes = Estudiante::all();
                    $mejorCoincidencia = null;
                    $maxPorcentaje = 0;
                    
                    foreach($estudiantes as $est) {
                        $nombreCompletoEst = strtoupper($est->est_nombres . ' ' . $est->est_apellidos);
                        $apellidosNombresEst = strtoupper($est->est_apellidos . ' ' . $est->est_nombres);
                        $coincidencias = 0;
                        
                        foreach($palabras as $palabra) {
                            if(strpos($nombreCompletoEst, $palabra) !== false || strpos($apellidosNombresEst, $palabra) !== false) {
                                $coincidencias++;
                            }
                        }
                        
                        $porcentaje = $coincidencias / count($palabras);
                        // Requerir al menos 70% de coincidencia y mínimo 3 palabras
                        if($coincidencias >= 3 && $porcentaje >= 0.7 && $porcentaje > $maxPorcentaje) {
                            $maxPorcentaje = $porcentaje;
                            $mejorCoincidencia = $est;
                        }
                    }
                    
                    if($mejorCoincidencia) {
                        $estudiante = $mejorCoincidencia;
                        $estudianteExistente = true;
                    }
                }
            }
            
            // Buscar curso del Excel (solo para estudiantes nuevos)
            $cursoExcel = null;
            if(!empty($cursoNombre)) {
                $cursoExcel = Curso::where('cur_nombre', 'like', '%' . $cursoNombre . '%')->first();
                if(!$cursoExcel) {
                    $cursoExcel = Curso::where('cur_nombre', 'like', '%Kinder%')
                        ->orWhere('cur_nombre', 'like', '%PreKinder%')
                        ->first();
                }
                if(!$cursoExcel) {
                    $cursoExcel = Curso::visible()->first();
                }
            }
            
            // Si no encuentra, crear estudiante básico
            if(!$estudiante) {
                $palabras = array_filter(explode(' ', trim($nombreCompleto)));
                $totalPalabras = count($palabras);
                
                // Asumir: primeras 2 palabras = nombres, últimas 2 = apellidos
                if($totalPalabras >= 3) {
                    $nombres = implode(' ', array_slice($palabras, 0, 2));
                    $apellidos = implode(' ', array_slice($palabras, 2));
                } else {
                    $nombres = $palabras[0] ?? $nombreCompleto;
                    $apellidos = $palabras[1] ?? '';
                }
                
                // Obtener último código de estudiante
                $ultimoEstudiante = Estudiante::orderBy('est_codigo', 'desc')->first();
                $nuevoNumero = 1;
                if($ultimoEstudiante && preg_match('/Est(\d+)/', $ultimoEstudiante->est_codigo, $matches)) {
                    $nuevoNumero = intval($matches[1]) + 1;
                }
                
                $estudiante = Estudiante::create([
                    'est_codigo' => 'Est' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT),
                    'est_nombres' => $nombres,
                    'est_apellidos' => $apellidos,
                    'est_ci' => $ci,
                    'cur_codigo' => $cursoExcel ? $cursoExcel->cur_codigo : Curso::visible()->first()->cur_codigo,
                    'est_visible' => 1,
                    'est_fecha' => now()
                ]);
                $estudianteExistente = false;
            }
            
            // Eliminar registros fantasma ANTES de verificar
            Inscripcion::where('insc_gestion', $year)
                ->where(function($q) {
                    $q->whereNull('est_codigo')
                      ->orWhere('est_codigo', '')
                      ->orWhereNull('pfam_codigo')
                      ->orWhere('pfam_codigo', '');
                })
                ->delete();
            
            // Verificar si ya existe inscripción válida
            $inscripcionExistente = Inscripcion::where('est_codigo', $estudiante->est_codigo)
                ->where('insc_gestion', $year)
                ->where('insc_estado', '!=', 0)
                ->first();
            
            if($inscripcionExistente) {
                // Actualizar inscripción existente con nuevos datos
                $inscripcionExistente->update([
                    'insc_monto_total' => $montoTotal,
                    'insc_monto_descuento' => $montoDescuento,
                    'insc_monto_final' => $montoFinal,
                    'insc_monto_pagado' => 300,
                    'insc_saldo' => $montoFinal - 300,
                    'insc_sin_factura' => $sinFactura
                ]);
                
                // Actualizar descuento si existe
                if($descuento) {
                    $inscripcionExistente->descuentos()->sync([
                        $descuento->desc_id => ['inscdesc_monto_descuento' => $montoDescuento]
                    ]);
                }
                
                \Log::info('Inscripción actualizada', [
                    'fila' => $index,
                    'estudiante_codigo' => $estudiante->est_codigo,
                    'inscripcion_codigo' => $inscripcionExistente->insc_codigo
                ]);
                
                $registrados++; // Contar como registrado
                continue;
            }
            
            // Cargar padre asignado al estudiante (recargar relación)
            $estudiante->load('padres');
            $padre = $estudiante->padres->first();
            
            // Si no tiene padre asignado, crear uno por defecto
            if(!$padre) {
                $padre = PadreFamilia::firstOrCreate(
                    ['pfam_nombres' => 'PADRE EJEMPLO'],
                    [
                        'pfam_codigo' => 'Pad' . str_pad(PadreFamilia::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
                        'pfam_ci' => '0000000',
                        'pfam_estado' => 1
                    ]
                );
                
                \DB::table('rela_estudiantespadres')->insertOrIgnore([
                    'est_id' => $estudiante->est_codigo,
                    'pfam_id' => $padre->pfam_codigo,
                    'estpad_estado' => 1
                ]);
            }
            
            $montoFinal = abs(floatval($row['I'] ?? 0)); // Columna I: SUB TOTAL
            $montoPagado = abs(floatval($row['J'] ?? 0)); // Columna J: PAGADO
            $saldo = abs(floatval($row['K'] ?? 0)); // Columna K: SALDO
            $montoTotal = $montoPagado + $saldo; // Total = Pagado + Saldo
            $porcentajeDesc = abs(floatval($row['L'] ?? 0)); // Columna L: % DESCUENTO
            $montoDescuento = abs(floatval($row['M'] ?? 0)); // Columna M: DESCUENTO
            $tipoDescuento = trim($row['G'] ?? ''); // Columna G: TIPO DESCUENTO
            $mesesPagados = intval($row['P'] ?? 0); // Columna P: MESES PAGADOS
            
            if($montoFinal <= 0) {
                $saltados['monto_cero']++;
                continue;
            }
            
            $descuento = null;
            if($montoDescuento > 0 && !empty($tipoDescuento)) {
                $descuento = \App\Models\Descuento::firstOrCreate(
                    ['desc_nombre' => $tipoDescuento],
                    [
                        'desc_codigo' => 'DESC' . str_pad(\App\Models\Descuento::max('desc_id') + 1, 4, '0', STR_PAD_LEFT),
                        'desc_porcentaje' => $porcentajeDesc,
                        'desc_estado' => 1
                    ]
                );
            }
            
            $inscripcion = Inscripcion::create([
                'insc_codigo' => 'INSC' . str_pad(Inscripcion::max('insc_id') + 1, 6, '0', STR_PAD_LEFT),
                'est_codigo' => $estudiante->est_codigo,
                'pfam_codigo' => $padre->pfam_codigo,
                'cur_codigo' => $estudiante->cur_codigo,
                'insc_gestion' => $year,
                'insc_monto_total' => $montoTotal,
                'insc_monto_descuento' => $montoDescuento,
                'insc_monto_final' => $montoFinal,
                'insc_monto_pagado' => 300,
                'insc_saldo' => $montoFinal - 300,
                'insc_concepto' => 'Inscripción ' . $year,
                'insc_estado' => 1,
                'insc_usuario' => auth()->user()->us_codigo,
                'insc_sin_factura' => $sinFactura
            ]);
            
            \Log::info('Inscripción creada', [
                'fila' => $index,
                'ci_excel' => $ci,
                'nombre_excel' => $nombreCompleto,
                'estudiante_codigo' => $estudiante->est_codigo,
                'inscripcion_codigo' => $inscripcion->insc_codigo
            ]);
            
            if($descuento) {
                $inscripcion->descuentos()->attach($descuento->desc_id, ['inscdesc_monto_descuento' => $montoDescuento]);
            }
            
            // Pago de inscripción (300 Bs)
            $codigoRecibo = \App\Services\ReciboNumerador::siguiente('INS-' . $prefijoRecibo);
            
            InscripcionPago::create([
                'inscpago_codigo' => 'PAGO' . str_pad(InscripcionPago::max('inscpago_id') + 1, 6, '0', STR_PAD_LEFT),
                'insc_codigo' => $inscripcion->insc_codigo,
                'inscpago_monto' => 300,
                'inscpago_concepto' => 'Pago de inscripción',
                'inscpago_usuario' => auth()->user()->us_codigo,
                'inscpago_recibo' => $codigoRecibo
            ]);
            
            if($mesesPagados > 0) {
                $montoMensualidad = $montoFinal / 10;
                $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                
                // Primera mensualidad = mensualidad - 300 (pago de inscripción)
                $primeraMensualidad = $montoMensualidad - 300;
                
                for($i = 0; $i < $mesesPagados; $i++) {
                    $mes = $mesInscripcion + $i;
                    if($mes > 12) break;
                    
                    // Primer pago es la primera mensualidad (ajustada), resto son cuotas completas
                    $montoPago = ($i == 0) ? $primeraMensualidad : $montoMensualidad;
                    
                    $codigoPago = \App\Services\ReciboNumerador::siguiente('MEN-' . $prefijoRecibo);
                    
                    $fechaPago = \Carbon\Carbon::create($year, $mesInscripcion, 15);
                    
                    \App\Models\Pago::create([
                        'pagos_codigo' => $codigoPago,
                        'men_codigo' => 'MEN' . str_pad(\App\Models\Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                        'est_codigo' => $estudiante->est_codigo,
                        'pfam_codigo' => $padre->pfam_codigo,
                        'prod_codigo' => 'MENSUALIDAD',
                        'pagos_precio' => $montoPago,
                        'pagos_nombres' => 'Mensualidad ' . $mesesNombres[$mes],
                        'pagos_usuario' => auth()->user()->us_codigo,
                        'pagos_descuento' => 0,
                        'concepto' => 'Mensualidad ' . $mesesNombres[$mes],
                        'tipo' => 1,
                        'pagos_fecha' => $fechaPago,
                        'pagos_gestion' => (int) $year,
                        'pagos_mes' => $mes,
                        'insc_codigo' => $inscripcion->insc_codigo,
                        'pagos_monto_efectivo' => $montoPago,
                        'pagos_sin_factura' => $sinFactura
                    ]);
                }
            }
            
            $registrados++;
        }
        
        $mensaje = "Registrados: $registrados | Anulados: {$saltados['anulados']} | Sin datos: {$saltados['sin_datos']} | Monto 0: {$saltados['monto_cero']} | Ya existe: {$saltados['ya_existe']}";
        
        return redirect()->route('inscripciones.index')->with('success', $mensaje);
    }
}
