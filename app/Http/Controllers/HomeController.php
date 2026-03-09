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
        // Estadísticas generales
        $totalEstudiantes = Estudiante::visible()->count();
        $totalCursos = Curso::visible()->count();
        $totalDocentes = Docente::visible()->count();
        $totalInscripciones = Inscripcion::where('insc_estado', '!=', 0)->count();

        // Inscripciones del mes actual
        $inscripcionesMes = Inscripcion::whereMonth('insc_fecha', Carbon::now()->month)
            ->whereYear('insc_fecha', Carbon::now()->year)
            ->sum('insc_monto_total');

        $inscripcionesPendientes = Inscripcion::where('insc_saldo', '>', 0)
            ->where('insc_estado', 1)
            ->sum('insc_saldo');

        // Ventas del mes
        $ventasMes = Venta::whereMonth('venta_fecha', Carbon::now()->month)
            ->whereYear('venta_fecha', Carbon::now()->year)
            ->sum('venta_preciototal');

        // Asistencias de hoy
        $asistenciasHoy = Asistencia::whereDate('asis_fecha', Carbon::today())->count();
        $atrasosHoy = Atraso::whereDate('atraso_fecha', Carbon::today())->count();

        // Gráfico: Inscripciones por mes (últimos 6 meses)
        $inscripcionesPorMes = Inscripcion::select(
                DB::raw('MONTH(insc_fecha) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->where('insc_fecha', '>=', Carbon::now()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Gráfico: Estudiantes por curso
        $estudiantesPorCurso = Curso::visible()
            ->withCount(['estudiantes' => function($q) {
                $q->visible();
            }])
            ->get();

        // Gráfico: Ventas por categoría (últimos 30 días)
        $ventasPorCategoria = DB::table('ventas_ventas')
            ->join('ventas_productos', 'ventas_ventas.prod_codigo', '=', 'ventas_productos.prod_codigo')
            ->join('ventas_categorias', 'ventas_productos.categ_codigo', '=', 'ventas_categorias.categ_codigo')
            ->select('ventas_categorias.categ_nombre', DB::raw('SUM(ventas_ventas.venta_preciototal) as total'))
            ->where('ventas_ventas.venta_fecha', '>=', Carbon::now()->subDays(30))
            ->groupBy('ventas_categorias.categ_nombre')
            ->get();

        // Gráfico: Estado de inscripciones
        $estadoInscripciones = [
            'pendientes' => Inscripcion::where('insc_estado', 1)->where('insc_saldo', '>', 0)->count(),
            'pagadas' => Inscripcion::where('insc_estado', 2)->count(),
            'canceladas' => Inscripcion::where('insc_estado', 0)->count()
        ];

        return view('home', compact(
            'totalEstudiantes',
            'totalCursos',
            'totalDocentes',
            'totalInscripciones',
            'inscripcionesMes',
            'inscripcionesPendientes',
            'ventasMes',
            'asistenciasHoy',
            'atrasosHoy',
            'inscripcionesPorMes',
            'estudiantesPorCurso',
            'ventasPorCategoria',
            'estadoInscripciones'
        ));
    }
}
