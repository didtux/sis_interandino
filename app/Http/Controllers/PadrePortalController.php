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
    private function getPadre()
    {
        $user = auth()->user();
        return PadreFamilia::where('pfam_codigo', $user->us_entidad_id)->firstOrFail();
    }

    private function getEstudiantes()
    {
        return $this->getPadre()->estudiantes()->with('curso')->where('est_visible', 1)->get();
    }

    // ── Dashboard ──────────────────────────────────────────────────
    public function dashboard()
    {
        $padre = $this->getPadre();
        $estudiantes = $this->getEstudiantes();
        $gestion = date('Y');

        $alertas = [];
        $resumen = [];

        foreach ($estudiantes as $est) {
            $info = ['estudiante' => $est, 'mora_mensualidad' => false, 'mora_transporte' => false, 'promedio' => 0, 'faltas' => 0];

            // Mora mensualidades
            $mesActual = (int)date('m');
            $mesesEscolares = range(2, min($mesActual, 11));
            $pagos = Pago::where('est_codigo', $est->est_codigo)->where('pagos_estado', 1)->get();
            $mesesPagados = [];
            foreach ($pagos as $p) {
                foreach ($p->meses_cubiertos as $m) $mesesPagados[] = $m;
            }
            $mesesPendientes = array_diff($mesesEscolares, array_unique($mesesPagados));
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
            $resumenTrim = (new \App\Services\AsistenciaResumenService())
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
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();

        $notasData = [];
        if ($estSeleccionado) {
            $curmatdocs = CursoMateriaDocente::with('materia')
                ->where('cur_codigo', $estSeleccionado->cur_codigo)->where('curmatdoc_estado', 1)->get();

            foreach ($curmatdocs as $cmd) {
                $matNotas = ['materia' => $cmd->materia->mat_nombre, 'trimestres' => [], 'promedio' => 0];
                $suma = 0; $cnt = 0;
                foreach ($periodos as $p) {
                    $nota = Nota::where('est_codigo', $estSeleccionado->est_codigo)
                        ->where('curmatdoc_id', $cmd->curmatdoc_id)->where('periodo_id', $p->periodo_id)
                        ->where('nota_estado', 2)->first();
                    $val = $nota ? round($nota->nota_promedio_trimestral) : 0;
                    $matNotas['trimestres'][$p->periodo_numero] = $val;
                    if ($val > 0) { $suma += $val; $cnt++; }
                }
                $matNotas['promedio'] = $cnt > 0 ? round($suma / $cnt) : 0;
                $notasData[] = $matNotas;
            }
        }

        return view('padre-portal.notas', compact('estudiantes', 'estSeleccionado', 'periodos', 'notasData', 'gestion'));
    }

    // ── Asistencia ─────────────────────────────────────────────────
    public function asistencia(Request $request)
    {
        $estudiantes = $this->getEstudiantes();
        $estSeleccionado = $request->est_codigo ? $estudiantes->firstWhere('est_codigo', $request->est_codigo) : $estudiantes->first();
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $asistData = [];
        if ($estSeleccionado) {
            // Servicio unificado: turno mañana, dedup por fecha, L-V sin feriados.
            // DT = presencias + licencias (atrasos cuentan como asistencia); TOT = días hábiles calendario.
            $resumenTrim = (new \App\Services\AsistenciaResumenService())
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

        $mensualidades = collect();
        $inscripcion = null;
        $pagosTransporte = collect();

        if ($estSeleccionado) {
            $mensualidades = Pago::where('est_codigo', $estSeleccionado->est_codigo)->where('pagos_estado', 1)->orderBy('pagos_fecha', 'desc')->get();
            $inscripcion = Inscripcion::where('est_codigo', $estSeleccionado->est_codigo)->where('insc_estado', 1)->where('insc_gestion', date('Y'))->first();
            $pagosTransporte = PagoTransporte::where('est_codigo', $estSeleccionado->est_codigo)->orderBy('tpago_fecha_pago', 'desc')->get();
        }

        return view('padre-portal.pagos', compact('estudiantes', 'estSeleccionado', 'mensualidades', 'inscripcion', 'pagosTransporte'));
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
