<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Curso;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with('curso')->visible();
        
        // Filtro para estudiantes incompletos
        if ($request->filled('incompletos')) {
            $year = date('Y');
            // Buscar estudiantes con inscripciones de carga masiva
            $estudiantesConInscripcion = \App\Models\Inscripcion::where('insc_gestion', $year)
                ->where('insc_codigo', 'like', 'INSC%')
                ->pluck('est_codigo')
                ->toArray();
            
            // SOLO mostrar estudiantes de la carga masiva
            if (!empty($estudiantesConInscripcion)) {
                $query->whereIn('est_codigo', $estudiantesConInscripcion)
                    ->where(function($q) {
                        $q->whereNull('cur_codigo')
                          ->orWhereNull('est_fechanac')
                          ->orWhereNull('est_lugarnac')
                          ->orWhere('est_ci', '')
                          ->orWhere('est_ci', 'like', '%sin%')
                          ->orWhere('est_ci', 'like', '%pendiente%')
                          ->orWhere('est_ci', 'like', '%0000000%');
                    });
            } else {
                // Si no hay inscripciones de carga masiva, no mostrar nada
                $query->whereRaw('1 = 0');
            }
        }
        
        if ($request->filled('curso')) {
            $query->where('cur_codigo', $request->curso);
        }
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('est_nombres', 'like', "%{$buscar}%")
                  ->orWhere('est_apellidos', 'like', "%{$buscar}%")
                  ->orWhere('est_codigo', 'like', "%{$buscar}%")
                  ->orWhere('est_ci', 'like', "%{$buscar}%");
            });
        }
        
        $estudiantes = $query->paginate(20);
        $cursos = Curso::visible()->get();
        
        return view('estudiantes.index', compact('estudiantes', 'cursos'));
    }

    public function create()
    {
        $cursos = Curso::visible()->get();
        
        // Generar código correlativo automático
        $ultimoEstudiante = Estudiante::orderBy('est_codigo', 'desc')->first();
        
        if ($ultimoEstudiante && preg_match('/Est(\d+)/', $ultimoEstudiante->est_codigo, $matches)) {
            $ultimoNumero = intval($matches[1]);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        $codigoGenerado = 'Est' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
        
        return view('estudiantes.create', compact('cursos', 'codigoGenerado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required|unique:colegio_estudiantes,est_codigo',
            'cur_codigo' => 'required',
            'est_nombres' => 'required|max:60',
            'est_apellidos' => 'nullable|max:30',
            'est_ci' => 'nullable|max:20',
            'est_foto' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('est_foto');
        
        if ($request->hasFile('est_foto')) {
            $data['est_foto'] = $request->file('est_foto')->store('estudiantes', 'public');
        }

        Estudiante::create($data);
        return redirect()->route('estudiantes.index')->with('success', 'Estudiante creado exitosamente');
    }

    public function show($id)
    {
        $estudiante = Estudiante::with('curso', 'asistencias')->findOrFail($id);
        return view('estudiantes.show', compact('estudiante'));
    }

    public function edit($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $cursos = Curso::visible()->get();
        return view('estudiantes.edit', compact('estudiante', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'est_nombres' => 'required|max:60',
            'est_apellidos' => 'nullable|max:30',
            'est_ci' => 'nullable|max:20',
            'est_foto' => 'nullable|image|max:2048'
        ]);

        $estudiante = Estudiante::findOrFail($id);
        $data = $request->except(['est_foto', 'est_codigo']); // Excluir est_codigo para que no se modifique
        
        if ($request->hasFile('est_foto')) {
            $data['est_foto'] = $request->file('est_foto')->store('estudiantes', 'public');
        }

        $estudiante->update($data);
        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado exitosamente');
    }

    public function destroy($id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);
            $estudiante->est_visible = 0;
            $estudiante->save();
            return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('estudiantes.index')->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    public function kardex($id)
    {
        $estudiante = Estudiante::with('curso', 'padres')->findOrFail($id);

        $listaCurso = \DB::table('colegio_lista_curso')
            ->where('est_codigo', $estudiante->est_codigo)
            ->where('lista_gestion', date('Y'))
            ->first();
        $numero = $listaCurso->lista_numero ?? '';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.kardex-pdf', compact('estudiante', 'numero'))
            ->setPaper('letter');

        return $pdf->stream('kardex-' . $estudiante->est_codigo . '.pdf');
    }

    public function reporteGeneral(Request $request)
    {
        $query = Estudiante::with('curso')->visible()->orderBy('est_apellidos');
        
        if ($request->filled('curso')) {
            $query->where('cur_codigo', $request->curso);
            $curso = Curso::where('cur_codigo', $request->curso)->first();
        } else {
            $curso = null;
        }
        
        $estudiantes = $query->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.reporte-general-pdf', compact('estudiantes', 'curso'))
            ->setPaper('letter', 'landscape');
        
        return $pdf->stream('reporte-estudiantes-' . date('Y-m-d') . '.pdf');
    }
    
    public function getPadres($est_codigo)
    {
        $estudiante = Estudiante::where('est_codigo', $est_codigo)->firstOrFail();
        $padres = $estudiante->padres()->get(['cole_padresfamilia.pfam_codigo', 'cole_padresfamilia.pfam_nombres', 'cole_padresfamilia.pfam_ci']);
        return response()->json($padres);
    }
}
