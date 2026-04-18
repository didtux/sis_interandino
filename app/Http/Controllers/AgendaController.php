<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Estudiante;
use App\Models\Curso;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    private function generarCodigo()
    {
        $ultimo = Agenda::orderBy('age_id', 'desc')->first();
        $num = $ultimo ? intval(substr($ultimo->age_codigo, 3)) + 1 : 1;
        return 'AGE' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = Agenda::activo()->with('estudiante.curso');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('age_titulo', 'like', "%$buscar%")
                  ->orWhereHas('estudiante', fn($e) => $e->where('est_nombres', 'like', "%$buscar%")->orWhere('est_apellidos', 'like', "%$buscar%"));
            });
        }
        if ($request->filled('tipo')) {
            $query->where('age_tipo', $request->tipo);
        }

        $agendas = $query->orderBy('age_fechahora', 'desc')->paginate(20)->appends($request->query());

        // Datos para calendario
        $eventos = Agenda::activo()->get()->map(fn($a) => [
            'id' => $a->age_id,
            'title' => $a->age_titulo,
            'start' => $a->age_fechahora ? $a->age_fechahora->format('Y-m-d\TH:i:s') : null,
            'color' => $a->age_tipo == 1 ? '#667eea' : '#ffc107',
            'url' => route('agenda.show', $a->age_id),
        ]);

        return view('agenda.index', compact('agendas', 'eventos'));
    }

    public function create()
    {
        $codigo = $this->generarCodigo();
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_apellidos')->get();
        $cursos = Curso::visible()->orderBy('cur_nombre')->get();
        return view('agenda.create', compact('codigo', 'estudiantes', 'cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'age_tipo' => 'required',
            'age_titulo' => 'required|max:50',
            'age_detalles' => 'required',
            'age_fechahora' => 'required',
        ]);

        Agenda::create([
            'age_codigo' => $this->generarCodigo(),
            'age_tipo' => $request->age_tipo,
            'est_codigo' => $request->est_codigo,
            'curso_codigo' => $request->curso_codigo,
            'prof_codigo' => $request->prof_codigo,
            'age_titulo' => $request->age_titulo,
            'age_detalles' => $request->age_detalles,
            'age_fechahora' => $request->age_fechahora,
        ]);

        return redirect()->route('agenda.index')->with('success', 'Registro creado exitosamente');
    }

    public function show($id)
    {
        $agenda = Agenda::with('estudiante.curso', 'estudiante.padres')->findOrFail($id);
        return view('agenda.show', compact('agenda'));
    }

    public function edit($id)
    {
        $agenda = Agenda::findOrFail($id);
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_apellidos')->get();
        $cursos = Curso::visible()->orderBy('cur_nombre')->get();
        return view('agenda.edit', compact('agenda', 'estudiantes', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'age_tipo' => 'required',
            'age_titulo' => 'required|max:50',
            'age_detalles' => 'required',
            'age_fechahora' => 'required',
        ]);

        $agenda = Agenda::findOrFail($id);
        $agenda->update([
            'age_tipo' => $request->age_tipo,
            'est_codigo' => $request->est_codigo,
            'curso_codigo' => $request->curso_codigo,
            'prof_codigo' => $request->prof_codigo,
            'age_titulo' => $request->age_titulo,
            'age_detalles' => $request->age_detalles,
            'age_fechahora' => $request->age_fechahora,
        ]);

        return redirect()->route('agenda.show', $id)->with('success', 'Registro actualizado exitosamente');
    }

    public function destroy($id)
    {
        Agenda::findOrFail($id)->update(['age_estado' => 0]);
        return redirect()->route('agenda.index')->with('success', 'Registro eliminado exitosamente');
    }

    // API: padres por estudiante
    public function padresPorEstudiante($est_codigo)
    {
        $est = Estudiante::where('est_codigo', $est_codigo)->first();
        if (!$est) return response()->json([]);
        $padres = $est->padres()->get(['cole_padresfamilia.pfam_codigo', 'cole_padresfamilia.pfam_nombres', 'cole_padresfamilia.pfam_numeroscelular', 'cole_padresfamilia.pfam_correo']);
        return response()->json($padres);
    }
}
