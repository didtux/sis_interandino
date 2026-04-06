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

        // Admin (rol_id=1): dashboard completo
        if ($user->rol_id == 1) {
            return $this->dashboardAdmin();
        }

        // Docente: dashboard de sus materias/notas
        if ($user->us_entidad_tipo === 'docente') {
            return $this->dashboardDocente($user);
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

        // Gráficos
        $inscripcionesPorMes = Inscripcion::select(
                DB::raw('MONTH(insc_fecha) as mes'), DB::raw('COUNT(*) as total')
            )->where('insc_fecha', '>=', Carbon::now()->subMonths(6))
            ->groupBy('mes')->orderBy('mes')->get();

        $estudiantesPorCurso = Curso::visible()
            ->withCount(['estudiantes' => fn($q) => $q->visible()])->get();

        $ventasPorCategoria = DB::table('ventas_ventas')
            ->join('ventas_productos', 'ventas_ventas.prod_codigo', '=', 'ventas_productos.prod_codigo')
            ->join('ventas_categorias', 'ventas_productos.categ_codigo', '=', 'ventas_categorias.categ_codigo')
            ->select('ventas_categorias.categ_nombre', DB::raw('SUM(ventas_ventas.venta_preciototal) as total'))
            ->where('ventas_ventas.venta_fecha', '>=', Carbon::now()->subDays(30))
            ->where('ventas_ventas.venta_estado', 'completado')
            ->groupBy('ventas_categorias.categ_nombre')->get();

        $estadoInscripciones = [
            'pendientes' => Inscripcion::where('insc_estado', 1)->where('insc_gestion', $year)->count(),
            'pagadas' => Inscripcion::where('insc_estado', 2)->where('insc_gestion', $year)->count(),
            'anuladas' => Inscripcion::where('insc_estado', 0)->where('insc_gestion', $year)->count()
        ];

        return view('home', compact(
            'totalEstudiantes', 'totalCursos', 'totalDocentes', 'totalPadres',
            'totalInscripciones', 'inscripcionesMes', 'mensualidadesMes', 'ventasMes',
            'asistenciasHoy', 'atrasosHoy', 'notasPendientes',
            'inscripcionesPorMes', 'estudiantesPorCurso', 'ventasPorCategoria', 'estadoInscripciones'
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
