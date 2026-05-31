<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Venta;
use App\Models\Atraso;
use App\Models\Pago;
use App\Models\PadreFamilia;
use App\Models\RolPermiso;
use App\Models\CursoMateriaDocente;
use App\Models\Nota;
use App\Models\NotaPeriodo;
use Carbon\Carbon;
use DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        // Encargado de escaneo: solo la app de escaneo
        if ($user->us_entidad_tipo === 'escaneo') {
            return redirect()->route('escaneo.index');
        }

        // Admin (rol_id=1): dashboard completo
        if ($user->rol_id == 1) {
            return $this->dashboardAdmin();
        }

        // Docente: dashboard de sus materias/notas
        if ($user->us_entidad_tipo === 'docente') {
            return $this->dashboardDocente($user);
        }

        // Padre: redirigir a su portal
        if ($user->us_entidad_tipo === 'padre') {
            return redirect()->route('padre-portal.dashboard');
        }

        // Otros roles: dashboard basado en permisos
        return $this->dashboardPorPermisos($user);
    }

    private function dashboardAdmin()
    {
        $year = date('Y');
        $totalEstudiantes = Estudiante::visible()->count();
        $totalCursos = Curso::visible()->count();
        $totalDocentes = Docente::visible()->count();
        $totalPadres = PadreFamilia::where('pfam_estado', 1)->count();
        $totalInscripciones = Inscripcion::where('insc_gestion', $year)->where('insc_estado', '!=', 0)->count();

        $inscripcionesMes = Inscripcion::whereMonth('insc_fecha', Carbon::now()->month)
            ->whereYear('insc_fecha', $year)->where('insc_estado', '!=', 0)->sum('insc_monto_pagado');

        $mensualidadesMes = Pago::whereMonth('pagos_fecha', Carbon::now()->month)
            ->whereYear('pagos_fecha', $year)->where('pagos_estado', 1)->sum('pagos_precio');

        $ventasMes = Venta::whereMonth('venta_fecha', Carbon::now()->month)
            ->whereYear('venta_fecha', $year)->where('venta_estado', 'completado')->sum('venta_preciototal');

        $asistenciasHoy = Asistencia::whereDate('asis_fecha', Carbon::today())->count();
        $atrasosHoy = Atraso::whereDate('atraso_fecha', Carbon::today())->count();

        // Notas pendientes de aprobación
        $notasPendientes = Nota::where('nota_estado', 1)->distinct('curmatdoc_id', 'periodo_id')->count();

        $estadoInscripciones = [
            'pendientes' => Inscripcion::where('insc_estado', 1)->where('insc_gestion', $year)->count(),
            'pagadas' => Inscripcion::where('insc_estado', 2)->where('insc_gestion', $year)->count(),
            'anuladas' => Inscripcion::where('insc_estado', 0)->where('insc_gestion', $year)->count()
        ];

        // ── Periodos de la gestión (para acotar el ranking) ──
        $periodoIds = NotaPeriodo::where('periodo_gestion', $year)->pluck('periodo_id')->all() ?: [0];

        // ── Ranking: mejores 10 estudiantes (promedio anual de notas aprobadas) ──
        $mejoresEstudiantes = DB::table('colegio_estudiantes as e')
            ->join('colegio_notas as n', DB::raw('n.est_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('e.est_codigo COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('colegio_cursos as c', 'c.cur_codigo', '=', 'e.cur_codigo')
            ->where('n.nota_estado', 2)
            ->whereIn('n.periodo_id', $periodoIds)
            ->where('e.est_visible', 1)
            ->select(
                'e.est_codigo',
                DB::raw("CONCAT(e.est_apellidos,' ',e.est_nombres) as nombre"),
                'c.cur_nombre',
                DB::raw('ROUND(AVG(n.nota_promedio_trimestral),1) as promedio')
            )
            ->groupBy('e.est_codigo', 'e.est_apellidos', 'e.est_nombres', 'c.cur_nombre')
            ->orderByDesc('promedio')
            ->limit(10)->get();

        // ── Estudiantes en riesgo: promedio anual < 51 ──
        $estudiantesEnRiesgo = DB::table('colegio_estudiantes as e')
            ->join('colegio_notas as n', DB::raw('n.est_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('e.est_codigo COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('colegio_cursos as c', 'c.cur_codigo', '=', 'e.cur_codigo')
            ->where('n.nota_estado', 2)
            ->whereIn('n.periodo_id', $periodoIds)
            ->where('e.est_visible', 1)
            ->select(
                'e.est_codigo',
                DB::raw("CONCAT(e.est_apellidos,' ',e.est_nombres) as nombre"),
                'c.cur_nombre',
                DB::raw('ROUND(AVG(n.nota_promedio_trimestral),1) as promedio')
            )
            ->groupBy('e.est_codigo', 'e.est_apellidos', 'e.est_nombres', 'c.cur_nombre')
            ->havingRaw('AVG(n.nota_promedio_trimestral) < 51')
            ->orderBy('promedio')
            ->limit(10)->get();

        // ── Recaudación acumulada del año ──
        $recaudacionAnual = [
            'mensualidades' => (float) Pago::whereYear('pagos_fecha', $year)->where('pagos_estado', 1)->sum('pagos_precio'),
            'inscripciones' => (float) Inscripcion::where('insc_gestion', $year)->where('insc_estado', '!=', 0)->sum('insc_monto_pagado'),
            'ventas'        => (float) Venta::whereYear('venta_fecha', $year)->where('venta_estado', 'completado')->sum('venta_preciototal'),
        ];
        $recaudacionAnual['total'] = $recaudacionAnual['mensualidades'] + $recaudacionAnual['inscripciones'] + $recaudacionAnual['ventas'];

        // ── Indicadores operativos ──
        $observadosActivos = \App\Models\EstudianteObservado::where('obs_gestion', $year)->where('obs_activo', 1)->count();
        $permisosHoy = \App\Models\Permiso::where('permiso_estado', 1)
            ->whereDate('permiso_fecha_inicio', '<=', Carbon::today())
            ->whereDate('permiso_fecha_fin', '>=', Carbon::today())->count();

        // ── Próximos eventos de agenda (próximos 30 días) ──
        $proximosEventos = \App\Models\Agenda::where('age_estado', 1)
            ->whereBetween('age_fechahora', [Carbon::now(), Carbon::now()->addDays(30)])
            ->orderBy('age_fechahora')->limit(6)
            ->get(['age_titulo', 'age_tipo', 'age_fechahora']);

        return view('home', compact(
            'totalEstudiantes', 'totalCursos', 'totalDocentes', 'totalPadres',
            'totalInscripciones', 'inscripcionesMes', 'mensualidadesMes', 'ventasMes',
            'asistenciasHoy', 'atrasosHoy', 'notasPendientes', 'estadoInscripciones',
            'mejoresEstudiantes', 'estudiantesEnRiesgo', 'recaudacionAnual',
            'observadosActivos', 'permisosHoy', 'proximosEventos'
        ));
    }

    private function dashboardDocente($user)
    {
        $docCodigo = $user->us_entidad_id;
        $year = date('Y');

        $asignaciones = $docCodigo
            ? CursoMateriaDocente::with(['curso', 'materia'])->where('doc_codigo', $docCodigo)->where('curmatdoc_estado', 1)->get()
            : collect();

        $periodos = NotaPeriodo::activo()->gestion($year)->orderBy('periodo_numero')->get();

        // Contar notas por estado
        $notasBorrador = 0; $notasEnviadas = 0; $notasAprobadas = 0; $notasRechazadas = 0;
        if ($docCodigo) {
            $curmatdocIds = $asignaciones->pluck('curmatdoc_id');
            $notasBorrador = Nota::whereIn('curmatdoc_id', $curmatdocIds)->where('nota_estado', 0)->distinct('curmatdoc_id', 'periodo_id')->count();
            $notasEnviadas = Nota::whereIn('curmatdoc_id', $curmatdocIds)->where('nota_estado', 1)->distinct('curmatdoc_id', 'periodo_id')->count();
            $notasAprobadas = Nota::whereIn('curmatdoc_id', $curmatdocIds)->where('nota_estado', 2)->distinct('curmatdoc_id', 'periodo_id')->count();
            $notasRechazadas = Nota::whereIn('curmatdoc_id', $curmatdocIds)->where('nota_estado', 3)->distinct('curmatdoc_id', 'periodo_id')->count();
        }

        $totalEstudiantes = 0;
        foreach ($asignaciones as $a) {
            $totalEstudiantes += Estudiante::visible()->where('cur_codigo', $a->cur_codigo)->count();
        }

        return view('home', compact(
            'asignaciones', 'periodos', 'totalEstudiantes',
            'notasBorrador', 'notasEnviadas', 'notasAprobadas', 'notasRechazadas'
        ));
    }

    private function dashboardPorPermisos($user)
    {
        $permisos = RolPermiso::where('rol_id', $user->rol_id)
            ->where('perm_ver', 1)
            ->with('modulo')
            ->get();

        $modulos = $permisos->map(function($p) {
            return [
                'nombre' => $p->modulo->mod_nombre ?? '',
                'slug' => $p->modulo->mod_slug ?? '',
                'icono' => $p->modulo->mod_icono ?? 'fas fa-circle',
                'puede_crear' => $p->perm_crear,
                'puede_editar' => $p->perm_editar,
            ];
        });

        return view('home', compact('modulos'));
    }
}
