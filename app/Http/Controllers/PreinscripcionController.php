<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Descuento;
use App\Models\Estudiante;
use App\Models\EstudianteGestion;
use App\Models\Gestion;
use App\Models\Preinscripcion;
use App\Models\Tarifa;
use App\Services\CursoProgresion;
use App\Services\PreinscripcionService;
use App\Support\GestionActual;
use Illuminate\Http\Request;

/**
 * Pre-inscripción para la gestión siguiente (reserva de cupo).
 *
 * Se habilita cuando una gestión posterior a la vigente tiene la inscripción
 * abierta (Parametrización → Gestiones). Alumnos antiguos: se eligen de la
 * nómina actual y el curso destino se sugiere con el curso siguiente. Alumnos
 * nuevos: sus datos quedan en la pre-inscripción y la ficha se crea al activar
 * la gestión (si se crearan antes aparecerían en las listas del año en curso).
 */
class PreinscripcionController extends Controller
{
    private function gestionDestino(Request $request): ?int
    {
        return $request->filled('gestion') ? (int) $request->gestion : GestionActual::inscripcionAbierta();
    }

    public function index(Request $request)
    {
        $gestion = $this->gestionDestino($request) ?? gestion_actual() + 1;
        $q = Preinscripcion::with('estudiante', 'cursoDestino', 'cursoOrigen', 'pagos', 'padre')->where('ges_anio', $gestion);
        if ($request->filled('estado')) $q->where('preinsc_estado', $request->estado);
        if ($request->filled('cur_destino')) $q->where('cur_destino', $request->cur_destino);
        if ($request->filled('buscar')) {
            $b = '%' . trim($request->buscar) . '%';
            $ests = Estudiante::where('est_apellidos', 'like', $b)->orWhere('est_nombres', 'like', $b)->orWhere('est_ci', 'like', $b)->pluck('est_codigo');
            $q->where(fn($w) => $w->whereIn('est_codigo', $ests)->orWhere('pre_apellidos', 'like', $b)
                ->orWhere('pre_nombres', 'like', $b)->orWhere('pre_ci', 'like', $b)->orWhere('preinsc_codigo', 'like', $b));
        }
        $pres = $q->orderByDesc('preinsc_id')->get();
        $conteo = Preinscripcion::where('ges_anio', $gestion)->selectRaw('preinsc_estado, COUNT(*) n')->groupBy('preinsc_estado')->pluck('n', 'preinsc_estado');
        $cursos = Curso::visible()->ordenado()->get();
        $abierta = Gestion::where('ges_anio', $gestion)->value('ges_inscripcion_abierta');
        return view('preinscripciones.index', compact('pres', 'gestion', 'conteo', 'cursos', 'abierta'));
    }

    public function create(Request $request)
    {
        $gestion = $this->gestionDestino($request);
        if (!$gestion) {
            return redirect()->route('preinscripciones.index')->with('error',
                'No hay ninguna gestión con la inscripción abierta. Ábrala en Parametrización → Gestiones.');
        }
        $g = Gestion::where('ges_anio', $gestion)->first();
        $yaPre = Preinscripcion::where('ges_anio', $gestion)->vigentes()->whereNotNull('est_codigo')->pluck('est_codigo');
        $estudiantes = Estudiante::visible()->with('curso')->whereNotIn('est_codigo', $yaPre)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get();
        $cursos = Curso::visible()->ordenado()->get();
        $tarifas = Tarifa::where('ges_anio', $gestion)->whereNotNull('cur_codigo')->pluck('tar_monto_anual', 'cur_codigo');
        $descuentos = Descuento::vigentePara($gestion)->orderBy('desc_nombre')->get();
        // Reserva que corresponde a cada curso destino (Tarifas → Gestión → Parámetros)
        $reservas = $cursos->mapWithKeys(fn($c) => [$c->cur_codigo => Tarifa::reservaPara($gestion, $c->cur_codigo)]);
        $puedeCambiarMonto = auth()->user()->tieneCapacidad('preinscripcion.cambiar_monto');
        return view('preinscripciones.create', compact('gestion', 'g', 'estudiantes', 'cursos', 'tarifas', 'descuentos', 'reservas', 'puedeCambiarMonto'));
    }

    /** Datos para el formulario al elegir un alumno antiguo (AJAX). */
    public function controles(Request $request, string $est)
    {
        $gestion = $this->gestionDestino($request) ?? gestion_actual() + 1;
        $c = (new PreinscripcionService())->controles($est, $gestion);
        $curActual = EstudianteGestion::cursoDe($est, gestion_actual());
        $destino = CursoProgresion::siguiente((string) $curActual);
        $tarifa = $destino ? optional(Tarifa::para($gestion, $destino))->tar_monto_anual : null;
        return response()->json([
            'curso_actual'   => optional(Curso::where('cur_codigo', $curActual)->first())->cur_nombre,
            'destino'        => $destino,
            'egresa'         => $curActual && CursoProgresion::esUltimoGrado($curActual),
            'tarifa'         => $tarifa,
            'observado'      => $c['observado'] ? ['motivo' => $c['observado']->obs_motivo, 'tipo' => $c['observado']->obs_motivo_tipo] : null,
            'puede_override' => auth()->user()->tieneCapacidad('observados.autorizar'),
            'deuda'          => round($c['deuda'], 2),
            'meses_deuda'    => $c['meses_deuda'],
            'bloquea_mora'   => parametro('preinsc_mora', 'AVISO') === 'BLOQUEO',
            'ya_preinscrito' => optional($c['ya_preinscrito'])->preinsc_codigo,
        ]);
    }

    public function store(Request $request)
    {
        $gestion = (int) $request->input('gestion');
        $g = Gestion::where('ges_anio', $gestion)->where('ges_inscripcion_abierta', 1)->first();
        if (!$g) return back()->withErrors(['error' => 'La inscripción para ' . $gestion . ' no está abierta.'])->withInput();

        $d = $request->validate([
            'tipo'               => 'required|in:ANTIGUO,NUEVO',
            'est_codigo'         => 'required_if:tipo,ANTIGUO|nullable|exists:colegio_estudiantes,est_codigo',
            'pre_nombres'        => 'required_if:tipo,NUEVO|nullable|max:80',
            'pre_apellidos'      => 'required_if:tipo,NUEVO|nullable|max:80',
            'pre_ci'             => 'nullable|max:20',
            'pre_fecha_nac'      => 'nullable|date',
            'pre_ue_procedencia' => 'nullable|max:120',
            'padre_nombre'       => 'required_if:tipo,NUEVO|nullable|max:120',
            'padre_ci'           => 'nullable|max:20',
            'cur_destino'        => 'required|exists:colegio_cursos,cur_codigo',
            'desc_id'            => 'nullable|exists:descuentos,desc_id',
            'monto'              => 'required|numeric|min:0',
            'metodo'             => 'required|in:EFECTIVO,QR,MIXTO',
            'monto_efectivo'     => 'nullable|numeric|min:0',
            'monto_qr'           => 'nullable|numeric|min:0',
            'transferencia_nro'  => 'nullable|max:40',
            'sin_factura'        => 'nullable|boolean',
            'obs'                => 'nullable|max:1000',
        ], ['required_if' => 'El campo :attribute es obligatorio.']);

        // Sin la capacidad, se cobra exactamente la reserva fijada para el curso
        [$reserva] = Tarifa::reservaPara($gestion, $d['cur_destino']);
        if (!auth()->user()->tieneCapacidad('preinscripcion.cambiar_monto') && abs((float) $d['monto'] - $reserva) > 0.001) {
            if ($d['metodo'] === 'MIXTO') {
                return back()->withErrors(['error' => 'La reserva para ese curso es Bs ' . number_format($reserva, 2) . '. Su rol no puede cobrar otro monto.'])->withInput();
            }
            $d['monto'] = $reserva;
        }

        if ($d['tipo'] === 'ANTIGUO') {
            $c = (new PreinscripcionService())->controles($d['est_codigo'], $gestion);
            if ($c['ya_preinscrito']) {
                return back()->withErrors(['error' => 'Ya tiene la pre-inscripción ' . $c['ya_preinscrito']->preinsc_codigo . ' para ' . $gestion . '.'])->withInput();
            }
            if ($c['observado'] && !($request->boolean('override_observado') && auth()->user()->tieneCapacidad('observados.autorizar'))) {
                return back()->withErrors(['error' => 'ESTUDIANTE OBSERVADO: ' . $c['observado']->obs_motivo . '. Sólo dirección puede autorizar la pre-inscripción.'])->withInput();
            }
            if ($c['deuda'] > 0 && parametro('preinsc_mora', 'AVISO') === 'BLOQUEO') {
                return back()->withErrors(['error' => 'Debe Bs ' . number_format($c['deuda'], 2) . ' de mensualidades ' . gestion_actual() . '. Con la regla del colegio no se puede pre-inscribir hasta regularizar.'])->withInput();
            }
        }

        try {
            $pre = (new PreinscripcionService())->crear($gestion, $d, auth()->user()->us_id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $recibo = optional($pre->pagos->first())->prepago_recibo;
        return redirect()->route('preinscripciones.show', $pre->preinsc_id)
            ->with('success', 'Pre-inscripción ' . $pre->preinsc_codigo . ' registrada' . ($recibo ? ' — recibo ' . $recibo : '') . '.')
            ->with('abrir_recibo', $recibo ? route('recibos.mostrar', ['reserva', $recibo]) : null);
    }

    public function show($id)
    {
        $pre = Preinscripcion::with('estudiante.curso', 'cursoDestino', 'cursoOrigen', 'pagos', 'padre', 'descuento')->findOrFail($id);
        $tarifa = Tarifa::para((int) $pre->ges_anio, $pre->cur_destino);
        $cursos = Curso::visible()->ordenado()->get();
        $devoluciones = \App\Models\Devolucion::where('preinsc_id', $pre->preinsc_id)->get();
        return view('preinscripciones.show', compact('pre', 'tarifa', 'cursos', 'devoluciones'));
    }

    /** Cambio de curso destino (p.ej. tras reprobar, o a otro paralelo). */
    public function cambiarDestino(Request $request, $id)
    {
        $pre = Preinscripcion::findOrFail($id);
        $request->validate(['cur_destino' => 'required|exists:colegio_cursos,cur_codigo']);
        if (!in_array($pre->preinsc_estado, ['RESERVADA', 'CONFIRMADA', 'AJUSTAR'])) {
            return back()->with('error', 'La pre-inscripción está ' . $pre->preinsc_estado . '.');
        }
        $pre->update([
            'cur_destino' => $request->cur_destino,
            'preinsc_estado' => $pre->preinsc_estado === 'AJUSTAR' ? 'CONFIRMADA' : $pre->preinsc_estado,
            'monto_anual_ref' => optional(Tarifa::para((int) $pre->ges_anio, $request->cur_destino))->tar_monto_anual,
        ]);
        return back()->with('success', 'Curso destino actualizado.');
    }

    public function convertir(Request $request, $id)
    {
        $pre = Preinscripcion::findOrFail($id);
        try {
            $insc = (new PreinscripcionService())->convertir($pre, auth()->user()->us_id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Inscripción ' . $insc->insc_codigo . ' creada para ' . $pre->ges_anio . '. La reserva quedó como pago a cuenta (se descuenta de febrero).');
    }

    /** Convierte todas las pre-inscripciones confirmadas (alumnos antiguos) de la gestión. */
    public function convertirConfirmadas(Request $request)
    {
        $gestion = (int) $request->input('gestion');
        $ok = 0; $errores = [];
        foreach (Preinscripcion::where('ges_anio', $gestion)->where('preinsc_tipo', 'ANTIGUO')->where('preinsc_estado', 'CONFIRMADA')->get() as $pre) {
            try {
                (new PreinscripcionService())->convertir($pre, auth()->user()->us_id);
                $ok++;
            } catch (\RuntimeException $e) {
                $errores[] = $pre->preinsc_codigo . ': ' . $e->getMessage();
            }
        }
        $msg = "Inscripciones creadas: {$ok}.";
        return back()->with($errores ? 'error' : 'success', $msg . ($errores ? ' Con problemas: ' . implode(' | ', array_slice($errores, 0, 5)) : ''));
    }

    public function anular(Request $request, $id)
    {
        $request->validate(['motivo' => 'required|max:255']);
        try {
            (new PreinscripcionService())->anular(Preinscripcion::findOrFail($id), $request->motivo, auth()->user()->us_id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pre-inscripción anulada.');
    }
}
