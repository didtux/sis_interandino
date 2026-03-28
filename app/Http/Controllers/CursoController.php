<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\CursoMateria;
use App\Models\CursoMateriaDocente;
use App\Models\ListaCurso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        $cursos = Curso::visible()->withCount('estudiantes')->paginate(15);
        return view('cursos.index', compact('cursos'));
    }

    public function create()
    {
        return view('cursos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required|unique:colegio_cursos,cur_codigo',
            'cur_nombre' => 'required|max:20'
        ]);

        Curso::create($request->all());
        return redirect()->route('cursos.index')->with('success', 'Curso creado exitosamente');
    }

    public function show($id)
    {
        $gestion = date('Y');

        $curso = Curso::with([
            'estudiantes' => function($q) {
                $q->where('est_visible', 1)->orderBy('est_apellidos')->orderBy('est_nombres');
            },
            'cursoMaterias.materia',
            'cursoMateriaDocentes.docente',
            'cursoMateriaDocentes.materia',
        ])->findOrFail($id);

        // Lista de curso para la gestión actual
        $lista = ListaCurso::where('cur_codigo', $curso->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        // Materias y docentes disponibles para asignar
        $materias = Materia::visible()->orderBy('mat_nombre')->get();
        $docentes = Docente::visible()->orderBy('doc_apellidos')->orderBy('doc_nombres')->get();

        // Materias ya asignadas al curso (para el select)
        $materiasAsignadas = $curso->cursoMaterias->pluck('mat_codigo')->toArray();

        return view('cursos.show', compact('curso', 'lista', 'materias', 'docentes', 'materiasAsignadas', 'gestion'));
    }

    public function edit($id)
    {
        $curso = Curso::findOrFail($id);
        return view('cursos.edit', compact('curso'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cur_nombre' => 'required|max:20'
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update($request->all());
        return redirect()->route('cursos.index')->with('success', 'Curso actualizado exitosamente');
    }

    public function destroy($id)
    {
        $curso = Curso::findOrFail($id);
        $curso->update(['cur_visible' => 0]);
        return redirect()->route('cursos.index')->with('success', 'Curso eliminado exitosamente');
    }

    /**
     * Guardar/actualizar lista de curso (números de lista)
     */
    public function guardarLista(Request $request, $id)
    {
        $curso = Curso::findOrFail($id);
        $gestion = date('Y');
        $numeros = $request->input('numeros', []);

        // Eliminar lista actual de esta gestión
        ListaCurso::where('cur_codigo', $curso->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->delete();

        // Insertar nuevos números
        foreach ($numeros as $estCodigo => $numero) {
            if ($numero !== null && $numero !== '') {
                ListaCurso::create([
                    'cur_codigo' => $curso->cur_codigo,
                    'est_codigo' => $estCodigo,
                    'lista_numero' => (int)$numero,
                    'lista_gestion' => $gestion,
                ]);
            }
        }

        return redirect()->route('cursos.show', $id)->with('success', 'Lista de curso actualizada');
    }

    /**
     * Auto-generar lista alfabética
     */
    public function autoLista($id)
    {
        $curso = Curso::findOrFail($id);
        $gestion = date('Y');

        $estudiantes = $curso->estudiantes()
            ->where('est_visible', 1)
            ->orderBy('est_apellidos')
            ->orderBy('est_nombres')
            ->get();

        ListaCurso::where('cur_codigo', $curso->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->delete();

        $num = 1;
        foreach ($estudiantes as $est) {
            ListaCurso::create([
                'cur_codigo' => $curso->cur_codigo,
                'est_codigo' => $est->est_codigo,
                'lista_numero' => $num++,
                'lista_gestion' => $gestion,
            ]);
        }

        return redirect()->route('cursos.show', $curso->cur_id)->with('success', 'Lista generada alfabéticamente');
    }

    /**
     * Asignar/desasignar materias al curso
     */
    public function asignarMaterias(Request $request, $id)
    {
        $curso = Curso::findOrFail($id);
        $materiasSeleccionadas = $request->input('materias', []);

        // Desactivar todas las materias actuales
        CursoMateria::where('cur_codigo', $curso->cur_codigo)->update(['curmat_estado' => 0]);

        // Activar/crear las seleccionadas
        foreach ($materiasSeleccionadas as $matCodigo) {
            CursoMateria::updateOrCreate(
                ['cur_codigo' => $curso->cur_codigo, 'mat_codigo' => $matCodigo],
                ['curmat_estado' => 1]
            );
        }

        // Desactivar docentes de materias que ya no están asignadas
        CursoMateriaDocente::where('cur_codigo', $curso->cur_codigo)
            ->whereNotIn('mat_codigo', $materiasSeleccionadas)
            ->update(['curmatdoc_estado' => 0]);

        return redirect()->route('cursos.show', $id)->with('success', 'Materias actualizadas');
    }

    /**
     * Asignar docente a una materia del curso
     */
    public function asignarDocente(Request $request, $id)
    {
        $curso = Curso::findOrFail($id);

        $request->validate([
            'mat_codigo' => 'required',
            'doc_codigo' => 'required',
        ]);

        CursoMateriaDocente::updateOrCreate(
            ['cur_codigo' => $curso->cur_codigo, 'mat_codigo' => $request->mat_codigo],
            ['doc_codigo' => $request->doc_codigo, 'curmatdoc_estado' => 1]
        );

        return redirect()->route('cursos.show', $id)->with('success', 'Docente asignado');
    }

    /**
     * Quitar docente de una materia del curso
     */
    public function quitarDocente($id, $matCodigo)
    {
        $curso = Curso::findOrFail($id);

        CursoMateriaDocente::where('cur_codigo', $curso->cur_codigo)
            ->where('mat_codigo', $matCodigo)
            ->update(['curmatdoc_estado' => 0]);

        return redirect()->route('cursos.show', $id)->with('success', 'Docente removido');
    }
}
