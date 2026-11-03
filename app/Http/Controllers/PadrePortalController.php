<?php

namespace App\Http\Controllers;

use App\Models\PadreFamilia;
use App\Models\Estudiante;
use App\Models\Nota;
use App\Models\NotaPeriodo;
use App\Models\CursoMateriaDocente;
use App\Models\Asistencia;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\Pago;
use App\Models\PagoTransporte;
use App\Models\Inscripcion;
use App\Models\RegistroEnfermeria;
use App\Models\CasoPsicopedagogia;
use App\Models\MateriaGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PadrePortalController extends Controller
{
    /**
     * Instancia compartida del resumen de asistencia: cachea lo que no depende
     * del estudiante, así el bucle por hijos no repite las mismas consultas.
     *
     * @var array<string, \App\Services\AsistenciaResumenService>
     */
    private array $resumenServices = [];

    private function resumenService(string $turno = 'Mañana'): \App\Services\AsistenciaResumenService
    {
        return $this->resumenServices[$turno] ??= new \App\Services\AsistenciaResumenService($turno);
    }

    private function getPadre()
    {
        $user = auth()->user();
        return PadreFamilia::where('pfam_codigo', $user->us_entidad_id)->firstOrFail();
    }

    /** Hijos del padre (incluye los de sus registros duplicados por CI). */
    private function getEstudiantes()
    {
        return $this->getPadre()->hijos();
    }

    // ── Dashboard ──────────────────────────────────────────────────
    public function dashboard()
    {
        $padre = $this->getPadre();
        $estudiantes = $this->getEstudiantes();
        $gestion = gestion_actual();

        $alertas = [];
        $resumen = [];

        foreach ($estudiantes as $est) {
            $info = ['estudiante' => $est, 'mora_mensualidad' => false, 'mora_transporte' => false, 'promedio' => 0, 'faltas' => 0];

            // Mora mensualidades: misma cuenta que caja (MoraService). Antes el
            // portal tenía la suya, sin filtrar la gestión.
            $mora = (new \App\Services\MoraService())->deEstudiante($est->est_codigo, $gestion);
            $mesesPendientes = $mora->mora_meses_pendientes ?? [];
            $info['bloqueo'] = (new \App\Services\BloqueoNotasService())->estado($est->est_codigo, $gestion);
            if (count($mesesPendientes) > 0) {
                $info['mora_mensualidad'] = true;
                $info['meses_pendientes'] = count($mesesPendientes);
                $alertas[] = ['tipo' => 'mensualidad', 'est' => $est->est_nombres, 'meses' => count($mesesPendientes)];
            }

            // Mora transporte
            $ruta = $est->rutaTransporte;
            if ($ruta) {
                $ultimoPago = PagoTransporte::where('est_codigo', $est->est_codigo)->where('tpago_estado', 'vigente')->orderBy('tpago_fecha_fin', 'desc')->first();
                if (!$ultimoPago || \Carbon\Carbon::parse($ultimoPago->tpago_fecha_fin)->isPast()) {
                    $info['mora_transporte'] = true;
                    $alertas[] = ['tipo' => 'transporte', 'est' => $est->est_nombres];
                }
            }

            // Promedio general
            $notas = Nota::where('est_codigo', $est->est_codigo)->where('nota_estado', 2)
                ->whereHas('periodo', fn($q) => $q->where('periodo_gestion', $gestion))
                ->pluck('nota_promedio_trimestral');
            $info['promedio'] = $notas->count() > 0 ? round($notas->avg(), 1) : 0;

            // Faltas totales — vía servicio unificado (mismo cálculo que boletín/concejo).
            $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
            $resumenTrim = $this->resumenService()
                ->resumenPorTrimestre($est->est_codigo, $periodos);
            $info['faltas'] = collect($resumenTrim)->sum('faltas');

            $resumen[] = $info;
        }

        return view('padre-portal.dashboard', compact('padre', 'estudiantes', 'resumen', 'alertas', 'gestion'));
    }

    // ── Hijos (información completa) ──────────────────────────────
    public function hijos()
    {
        $estudiantes = $this->getEstudiantes();
        return view('padre-portal.hijos', compact('estudiantes'));
    }

    // ── Notas ──────────────────────────────────────────────────────
    public function notas(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $gestion = gestion_actual();
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $notasData = [];
        $bloqueo = ['bloqueado' => false];
        if ($estSeleccionado) {
            // Bloqueo por mora (parámetros del colegio): no se cargan las notas.
            $bloqueo = (new \App\Services\BloqueoNotasService())->estado($estSeleccionado->est_codigo, $gestion);
            if (!$bloqueo['bloqueado']) {
                // Mismos números que el boletín (BoletinNotasService).
                $filas = (new \App\Services\BoletinNotasService())
                    ->notasEstudiante($estSeleccionado->est_codigo, $estSeleccionado->cur_codigo, $periodos);
                foreach ($filas as $f) {
                    $notasData[] = ['materia' => $f['nombre'], 'trimestres' => $f['trimestres'], 'promedio' => $f['promedio']];
                }
            }
        }

        return view('padre-portal.notas', compact('estudiantes', 'estSeleccionado', 'periodos', 'notasData', 'gestion', 'bloqueo'));
    }

    // ── Asistencia ─────────────────────────────────────────────────
    public function asistencia(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();
        $gestion = gestion_actual();
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $asistData = [];
        if ($estSeleccionado) {
            // Servicio unificado: turno mañana, dedup por fecha, L-V sin feriados.
            // DT = presencias + licencias (atrasos cuentan como asistencia); TOT = días hábiles calendario.
            $resumenTrim = $this->resumenService()
                ->resumenPorTrimestre($estSeleccionado->est_codigo, $periodos);

            foreach ($periodos as $p) {
                $r = $resumenTrim[$p->periodo_numero] ?? null;
                if (!$r) continue;
                $presencias = $r['presencias'];
                $licencias  = $r['licencias_dias'];
                $atrasos    = $r['atrasos'];
                $faltas     = $r['faltas'];
                $diasTrab   = $presencias + $licencias;          // Días trabajados (DT)
                $totalDias  = $r['dias_habiles_calendario'];      // Total hábiles (TOT)

                $asistData[$p->periodo_numero] = compact(
                    'diasTrab', 'presencias', 'atrasos', 'licencias', 'faltas', 'totalDias'
                );
            }
        }

        return view('padre-portal.asistencia', compact('estudiantes', 'estSeleccionado', 'periodos', 'asistData', 'gestion'));
    }

    // ── Permisos ───────────────────────────────────────────────────
    public function permisos(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $permisos = collect();
        if ($estSeleccionado) {
            $permisos = Permiso::where('estud_codigo', $estSeleccionado->est_codigo)
                ->orderBy('permiso_fecha_inicio', 'desc')->get();
        }

        return view('padre-portal.permisos', compact('estudiantes', 'estSeleccionado', 'permisos'));
    }

    // ── Pagos ──────────────────────────────────────────────────────
    public function pagos(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $gestion = (int) gestion_actual();
        $mensualidades = collect();
        $inscripcion = null;
        $pagosTransporte = collect();
        $totalMensualidades = 0;
        $saldoInscripcion = 0;

        if ($estSeleccionado) {
            // Acotado a la gestión: la grilla de la vista se titula con el año en
            // curso, y sin este filtro un pago de "Marzo" del año pasado marcaba
            // marzo de este año como pagado (meses_cubiertos sólo mira el texto
            // del concepto, no la fecha).
            $mensualidades = Pago::where('est_codigo', $estSeleccionado->est_codigo)
                ->where('pagos_estado', 1)
                ->deGestion($gestion)
                ->orderBy('pagos_fecha', 'desc')
                ->get();

            $inscripcion = Inscripcion::where('est_codigo', $estSeleccionado->est_codigo)
                ->whereIn('insc_estado', [1, 2])
                ->where('insc_gestion', $gestion)
                ->first();

            // Los recibos anulados no son pagos: al padre le aparecían igual.
            $pagosTransporte = PagoTransporte::where('est_codigo', $estSeleccionado->est_codigo)
                ->where('tpago_estado', '!=', 'cancelado')
                ->orderBy('tpago_fecha_pago', 'desc')
                ->get();

            // Saldo con la misma fórmula que caja (PagoController::create, "única
            // fuente de verdad"): monto_final − (inscripción + mensualidades).
            // insc_saldo sólo lo bajan los pagos de inscripción, así que el padre
            // veía una cifra distinta a la de secretaría.
            $totalMensualidades = (float) $mensualidades->sum('pagos_precio');
            $montoFinal   = (float) ($inscripcion->insc_monto_final ?? $inscripcion->insc_monto_total ?? 0);
            $pagadoInscr  = (float) ($inscripcion->insc_monto_pagado ?? 0);
            $saldoInscripcion = max(0, $montoFinal - ($pagadoInscr + $totalMensualidades));
        }

        return view('padre-portal.pagos', compact(
            'estudiantes', 'estSeleccionado', 'mensualidades', 'inscripcion',
            'pagosTransporte', 'totalMensualidades', 'saldoInscripcion', 'gestion'
        ));
    }

    /** Publicaciones del colegio de un tipo, filtradas por el curso de cada hijo. */
    public function publicaciones(Request $request, string $tipo)
    {
        $tipo = strtoupper($tipo);
        abort_unless(isset(\App\Models\Publicacion::TIPOS[$tipo]), 404);
        $estudiantes = $this->getEstudiantes();
        $gestion = gestion_actual();
        $todas = \App\Models\Publicacion::with('curso')->visiblesPara($gestion)->where('pub_tipo', $tipo)
            ->orderBy('pub_orden')->orderByDesc('pub_fecha')->get();
        $publicaciones = $todas->filter(fn($p) => $estudiantes->contains(
            fn($e) => $p->aplicaA($e->cur_codigo, optional($e->curso)->cur_nivel)
        ) || $estudiantes->isEmpty() && !$p->cur_codigo && !$p->niv_nombre)->values();
        [$titulo, $icono] = \App\Models\Publicacion::TIPOS[$tipo];
        return view('padre-portal.publicaciones', compact('publicaciones', 'tipo', 'titulo', 'icono', 'estudiantes', 'gestion'));
    }

    /** Agenda del curso de cada hijo (tabla agenda) más la agenda institucional publicada. */
    public function agenda(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();
        $eventos = collect();
        if ($estSeleccionado) {
            $eventos = \App\Models\Agenda::where('age_estado', 1)
                ->where(fn($q) => $q->where('curso_codigo', $estSeleccionado->cur_codigo)->orWhere('est_codigo', $estSeleccionado->est_codigo))
                ->where('age_fechahora', '>=', now()->subDays(30))
                ->orderBy('age_fechahora')->limit(200)->get();
        }
        $institucional = \App\Models\Publicacion::visiblesPara(gestion_actual())->where('pub_tipo', 'AGENDA')->orderByDesc('pub_fecha')->get();
        return view('padre-portal.agenda', compact('estudiantes', 'estSeleccionado', 'eventos', 'institucional'));
    }

    // ── Enfermería ─────────────────────────────────────────────────
    public function enfermeria(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $registros = collect();
        if ($estSeleccionado) {
            $registros = RegistroEnfermeria::where('est_codigo', $estSeleccionado->est_codigo)
                ->where('enf_estado', 1)->orderBy('enf_fecha', 'desc')->limit(50)->get();
        }

        return view('padre-portal.enfermeria', compact('estudiantes', 'estSeleccionado', 'registros'));
    }

    // ── Psicopedagogía ────────────────────────────────────────────
    public function psicopedagogia(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $casos = collect();
        if ($estSeleccionado) {
            $casos = CasoPsicopedagogia::where('est_codigo', $estSeleccionado->est_codigo)
                ->where('psico_estado', 1)->orderBy('psico_fecha', 'desc')->limit(50)->get();
        }

        return view('padre-portal.psicopedagogia', compact('estudiantes', 'estSeleccionado', 'casos'));
    }

    public function kardex(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo
            ? $estudiantes->firstWhere('est_codigo', $request->est_codigo)
            : $estudiantes->first();

        $registros = collect();
        if ($estSeleccionado) {
            $registros = \App\Models\EstudianteKardex::with('docente')
                ->where('est_codigo', $estSeleccionado->est_codigo)
                ->where('ek_estado', 1)
                ->where('ek_visible_padre', 1)
                ->orderByDesc('ek_fecha')->orderByDesc('ek_id')
                ->paginate(20);
        }

        return view('padre-portal.kardex', compact('estudiantes', 'estSeleccionado', 'registros'));
    }
}
