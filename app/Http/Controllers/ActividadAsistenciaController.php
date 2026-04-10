<?php
namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadRegistro;
use App\Models\Estudiante;
use App\Models\Curso;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ActividadAsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Actividad::withCount('categorias');
        if ($request->filled('buscar')) {
            $query->where('act_nombre', 'like', '%'.$request->buscar.'%');
        }
        $actividades = $query->orderBy('act_fecha', 'desc')->paginate(20);
        return view('actividades-asistencia.index', compact('actividades'));
    }

    public function create()
    {
        return view('actividades-asistencia.create');
    }

    public function store(Request $request)
    {
        $request->validate(['act_nombre' => 'required|max:200', 'act_fecha' => 'required|date']);
        Actividad::create([
            'act_nombre' => $request->act_nombre,
            'act_descripcion' => $request->act_descripcion,
            'act_fecha' => $request->act_fecha,
            'act_creado_por' => auth()->user()->us_id,
        ]);
        return redirect()->route('actividades-asistencia.index')->with('success', 'Actividad creada');
    }

    public function show($id, Request $request)
    {
        $actividad = Actividad::with('categoriasActivas.registros.estudiante.curso')->findOrFail($id);
        $cursos = Curso::visible()->orderBy('cur_nombre')->get();

        // Filtros
        $registrosQuery = ActividadRegistro::with('estudiante.curso', 'categoria')
            ->whereHas('categoria', fn($q) => $q->where('act_id', $id));

        if ($request->filled('cur_codigo')) {
            $registrosQuery->whereHas('estudiante', fn($q) => $q->where('cur_codigo', $request->cur_codigo));
        }
        if ($request->filled('buscar_est')) {
            $registrosQuery->whereHas('estudiante', fn($q) => $q->where('est_nombres', 'like', '%'.$request->buscar_est.'%')->orWhere('est_apellidos', 'like', '%'.$request->buscar_est.'%'));
        }
        if ($request->filled('actcat_id')) {
            $registrosQuery->where('actcat_id', $request->actcat_id);
        }

        $registros = $registrosQuery->orderBy('actreg_fecha_registro', 'desc')->get();

        // Faltas: estudiantes que NO están en ningún registro de esta actividad
        $estRegistrados = ActividadRegistro::whereHas('categoria', fn($q) => $q->where('act_id', $id))
            ->pluck('est_codigo')->unique();

        $faltasQuery = Estudiante::visible()->whereNotIn('est_codigo', $estRegistrados);
        if ($request->filled('cur_codigo_faltas')) {
            $faltasQuery->where('cur_codigo', $request->cur_codigo_faltas);
        }
        $faltas = $faltasQuery->with('curso')->orderBy('est_apellidos')->get();

        $tab = $request->input('tab', 'registros');

        return view('actividades-asistencia.show', compact('actividad', 'registros', 'faltas', 'cursos', 'tab'));
    }

    public function edit($id)
    {
        $actividad = Actividad::findOrFail($id);
        return view('actividades-asistencia.edit', compact('actividad'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['act_nombre' => 'required|max:200', 'act_fecha' => 'required|date']);
        Actividad::findOrFail($id)->update($request->only('act_nombre', 'act_descripcion', 'act_fecha', 'act_estado'));
        return redirect()->route('actividades-asistencia.show', $id)->with('success', 'Actividad actualizada');
    }

    public function destroy($id)
    {
        $act = Actividad::findOrFail($id);
        ActividadRegistro::whereHas('categoria', fn($q) => $q->where('act_id', $id))->delete();
        ActividadCategoria::where('act_id', $id)->delete();
        $act->delete();
        return redirect()->route('actividades-asistencia.index')->with('success', 'Actividad eliminada');
    }

    // Categorías
    public function storeCategoria(Request $request, $actId)
    {
        $request->validate(['actcat_nombre' => 'required|max:200']);
        ActividadCategoria::create(['act_id' => $actId, 'actcat_nombre' => $request->actcat_nombre, 'actcat_descripcion' => $request->actcat_descripcion]);
        return back()->with('success', 'Categoría agregada');
    }

    public function destroyCategoria($id)
    {
        $cat = ActividadCategoria::findOrFail($id);
        ActividadRegistro::where('actcat_id', $id)->delete();
        $cat->delete();
        return back()->with('success', 'Categoría eliminada');
    }

    // Registrar asistencia en una categoría
    public function registrar($catId)
    {
        $categoria = ActividadCategoria::with('actividad')->findOrFail($catId);
        $registros = ActividadRegistro::with('estudiante.curso')->where('actcat_id', $catId)->orderBy('actreg_fecha_registro', 'desc')->get();
        $estudiantes = Estudiante::visible()->orderBy('est_apellidos')->get();
        return view('actividades-asistencia.registrar', compact('categoria', 'registros', 'estudiantes'));
    }

    // Guardar registro (AJAX - búsqueda o QR)
    public function guardarRegistro(Request $request)
    {
        $request->validate(['actcat_id' => 'required', 'est_codigo' => 'required']);

        $existe = ActividadRegistro::where('actcat_id', $request->actcat_id)->where('est_codigo', $request->est_codigo)->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Este estudiante ya fue registrado en esta categoría']);
        }

        $est = Estudiante::where('est_codigo', $request->est_codigo)->with('curso')->first();
        if (!$est) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado']);
        }

        ActividadRegistro::create([
            'actcat_id' => $request->actcat_id,
            'est_codigo' => $request->est_codigo,
            'actreg_hora' => now()->format('H:i:s'),
            'actreg_observacion' => $request->observacion,
            'actreg_registrado_por' => auth()->user()->us_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada',
            'estudiante' => [
                'codigo' => $est->est_codigo,
                'nombre' => $est->est_nombres . ' ' . $est->est_apellidos,
                'curso' => $est->curso->cur_nombre ?? 'N/A',
                'hora' => now()->format('H:i:s'),
            ]
        ]);
    }

    public function eliminarRegistro($id)
    {
        ActividadRegistro::findOrFail($id)->delete();
        return back()->with('success', 'Registro eliminado');
    }

    // API: buscar estudiante por código (QR)
    public function buscarEstudiante($codigo)
    {
        $est = Estudiante::where('est_codigo', $codigo)->with('curso')->first();
        if (!$est) return response()->json(['found' => false]);
        return response()->json([
            'found' => true,
            'est_codigo' => $est->est_codigo,
            'nombre' => $est->est_nombres . ' ' . $est->est_apellidos,
            'curso' => $est->curso->cur_nombre ?? 'N/A',
        ]);
    }

    public function actualizarObservacion(Request $request, $id)
    {
        ActividadRegistro::findOrFail($id)->update(['actreg_observacion' => $request->observacion]);
        return response()->json(['success' => true]);
    }

    public function reportePdf($id, Request $request)
    {
        $actividad = Actividad::with('categoriasActivas')->findOrFail($id);
        $tab = $request->input('tab', 'registros');

        if ($tab == 'registros') {
            $query = ActividadRegistro::with('estudiante.curso', 'categoria')
                ->whereHas('categoria', fn($q) => $q->where('act_id', $id));
            if ($request->filled('cur_codigo')) {
                $query->whereHas('estudiante', fn($q) => $q->where('cur_codigo', $request->cur_codigo));
            }
            if ($request->filled('buscar_est')) {
                $query->whereHas('estudiante', fn($q) => $q->where('est_nombres', 'like', '%'.$request->buscar_est.'%')->orWhere('est_apellidos', 'like', '%'.$request->buscar_est.'%'));
            }
            if ($request->filled('actcat_id')) {
                $query->where('actcat_id', $request->actcat_id);
            }
            $registros = $query->orderBy('actreg_fecha_registro', 'desc')->get();
            $faltas = collect();
        } else {
            $estRegistrados = ActividadRegistro::whereHas('categoria', fn($q) => $q->where('act_id', $id))
                ->pluck('est_codigo')->unique();
            $faltasQuery = Estudiante::visible()->whereNotIn('est_codigo', $estRegistrados);
            if ($request->filled('cur_codigo_faltas')) {
                $faltasQuery->where('cur_codigo', $request->cur_codigo_faltas);
            }
            $faltas = $faltasQuery->with('curso')->orderBy('est_apellidos')->get();
            $registros = collect();
        }

        $cursos = Curso::visible()->orderBy('cur_nombre')->get();
        $filtros = [];
        if ($request->filled('cur_codigo')) $filtros[] = 'Curso: ' . ($cursos->firstWhere('cur_codigo', $request->cur_codigo)->cur_nombre ?? $request->cur_codigo);
        if ($request->filled('cur_codigo_faltas')) $filtros[] = 'Curso: ' . ($cursos->firstWhere('cur_codigo', $request->cur_codigo_faltas)->cur_nombre ?? $request->cur_codigo_faltas);
        if ($request->filled('actcat_id')) $filtros[] = 'Categoría: ' . ($actividad->categoriasActivas->firstWhere('actcat_id', $request->actcat_id)->actcat_nombre ?? '');
        if ($request->filled('buscar_est')) $filtros[] = 'Estudiante: ' . $request->buscar_est;

        $pdf = Pdf::loadView('actividades-asistencia.reporte-pdf', compact('actividad', 'registros', 'faltas', 'tab', 'filtros'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('actividad-' . $actividad->act_id . '-' . $tab . '.pdf');
    }
}
