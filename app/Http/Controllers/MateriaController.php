<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\Curso;
use App\Models\MateriaCurso;
use App\Models\CursoMateria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $materias = Materia::visible()->orderBy('mat_campo')->orderBy('mat_orden')->paginate(20);

        // Grupos globales (legacy) derivados de mat_campo
        $todasMaterias = Materia::visible()->orderBy('mat_campo')->orderBy('mat_orden')->orderBy('mat_nombre')->get();
        $grupos = $todasMaterias
            ->filter(fn($m) => !empty(trim((string) $m->mat_campo)))
            ->groupBy(fn($m) => trim((string) $m->mat_campo))
            ->map(function ($materias, $campo) {
                $cntProm = $materias->where('mat_promediable', 1)->count();
                return (object) [
                    'campo'        => $campo,
                    'materias'     => $materias->values(),
                    'total'        => $materias->count(),
                    'promediables' => $cntProm,
                ];
            })
            ->sortKeys()
            ->values();

        // === Tab "Por Curso" ===
        $cursos = Curso::visible()->ordenado()->get();
        $cursoSeleccionado = null;
        $matCursoConfig = collect();   // colección de filas para la tabla del curso
        $camposSugeridos = $grupos->pluck('campo')->merge([
            'COMUNIDAD Y SOCIEDAD', 'CIENCIA Y TECNOLOGÍA',
            'VIDA TIERRA Y TERRITORIO', 'COSMOS Y PENSAMIENTO'
        ])->unique()->values();

        if ($request->filled('cur_codigo')) {
            $cursoSeleccionado = Curso::where('cur_codigo', $request->cur_codigo)->first();
            if ($cursoSeleccionado) {
                // Materias asignadas al curso (vía colegio_curso_materia)
                $matCodigos = CursoMateria::where('cur_codigo', $cursoSeleccionado->cur_codigo)
                    ->where('curmat_estado', 1)->pluck('mat_codigo');

                $materiasCurso = Materia::whereIn('mat_codigo', $matCodigos)->get()->keyBy('mat_codigo');

                $config = MateriaCurso::where('cur_codigo', $cursoSeleccionado->cur_codigo)
                    ->get()->keyBy('mat_codigo');

                $matCursoConfig = $materiasCurso->map(function($m) use ($config) {
                    $cfg = $config->get($m->mat_codigo);
                    return (object) [
                        'mat_codigo'  => $m->mat_codigo,
                        'mat_nombre'  => $m->mat_nombre,
                        'campo'       => $cfg->matc_campo ?? $m->mat_campo,
                        'orden'       => $cfg->matc_orden ?? $m->mat_orden ?? 999,
                        'promediable' => (int) ($cfg->matc_promediable ?? 0),
                    ];
                })->sortBy('orden')->values();
            }
        }

        return view('materias.index', compact(
            'materias', 'grupos', 'todasMaterias',
            'cursos', 'cursoSeleccionado', 'matCursoConfig', 'camposSugeridos'
        ));
    }

    /**
     * Guarda la configuración (campo, orden, promediable) de TODAS las materias
     * de un curso en una sola transacción.
     */
    public function guardarPorCurso(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required|exists:colegio_cursos,cur_codigo',
            'config'     => 'required|array|min:1',
        ]);

        $curCodigo = $request->cur_codigo;

        DB::transaction(function() use ($request, $curCodigo) {
            foreach ($request->config as $matCodigo => $row) {
                MateriaCurso::updateOrCreate(
                    ['cur_codigo' => $curCodigo, 'mat_codigo' => $matCodigo],
                    [
                        'matc_campo'       => $row['campo'] ?? null,
                        'matc_orden'       => (int) ($row['orden'] ?? 999),
                        'matc_promediable' => !empty($row['promediable']) ? 1 : 0,
                        'matc_estado'      => 1,
                    ]
                );
            }
        });

        return redirect()
            ->to(route('materias.index', ['tab' => 'por-curso', 'cur_codigo' => $curCodigo]) . '#tabPorCurso')
            ->with('success', 'Configuración guardada para el curso.');
    }

    /**
     * Copia la configuración por curso desde otro curso (para acelerar setup).
     */
    public function copiarConfigCurso(Request $request)
    {
        $request->validate([
            'cur_codigo_destino' => 'required|exists:colegio_cursos,cur_codigo',
            'cur_codigo_origen'  => 'required|exists:colegio_cursos,cur_codigo|different:cur_codigo_destino',
        ]);

        $destino = $request->cur_codigo_destino;
        $origen  = $request->cur_codigo_origen;

        $matsDestino = CursoMateria::where('cur_codigo', $destino)
            ->where('curmat_estado', 1)->pluck('mat_codigo')->all();
        $origenConfig = MateriaCurso::where('cur_codigo', $origen)->get()->keyBy('mat_codigo');

        $copiados = 0;
        foreach ($matsDestino as $matCodigo) {
            $src = $origenConfig->get($matCodigo);
            if (!$src) continue;
            MateriaCurso::updateOrCreate(
                ['cur_codigo' => $destino, 'mat_codigo' => $matCodigo],
                [
                    'matc_campo'       => $src->matc_campo,
                    'matc_orden'       => $src->matc_orden,
                    'matc_promediable' => $src->matc_promediable,
                    'matc_estado'      => 1,
                ]
            );
            $copiados++;
        }

        return redirect()
            ->to(route('materias.index', ['tab' => 'por-curso', 'cur_codigo' => $destino]) . '#tabPorCurso')
            ->with('success', "Se copiaron $copiados materias de configuración desde el curso origen.");
    }

    public function create()
    {
        return view('materias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'mat_codigo' => 'required|unique:colegio_materias,mat_codigo',
            'mat_nombre' => 'required|max:50'
        ]);

        $data = $request->all();
        $data['mat_promediable'] = $request->has('mat_promediable') ? 1 : ($request->filled('mat_campo') ? 1 : 1);
        Materia::create($data);
        return redirect()->route('materias.index')->with('success', 'Materia creada exitosamente');
    }

    public function edit($id)
    {
        $materia = Materia::findOrFail($id);
        return view('materias.edit', compact('materia'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['mat_nombre' => 'required|max:50']);

        $materia = Materia::findOrFail($id);
        $data = $request->all();
        $data['mat_promediable'] = $request->has('mat_promediable') ? 1 : 0;
        $materia->update($data);
        return redirect()->route('materias.index')->with('success', 'Materia actualizada exitosamente');
    }

    public function destroy($id)
    {
        Materia::findOrFail($id)->update(['mat_visible' => 0]);
        return redirect()->route('materias.index')->with('success', 'Materia eliminada exitosamente');
    }

    // ── Asignar campo a varias materias a la vez ────────────────────
    public function asignarCampo(Request $request)
    {
        $request->validate([
            'mat_campo' => 'required|max:60',
            'materias'  => 'required|array|min:1',
        ]);
        $campo = trim($request->mat_campo);
        Materia::whereIn('mat_codigo', $request->materias)->update(['mat_campo' => $campo]);
        return redirect()->route('materias.index', ['tab' => 'asociar'])
            ->with('success', count($request->materias) . ' materias asignadas al campo "' . $campo . '"');
    }

    // ── Marcar qué materias de un campo suman al promedio ───────────
    public function guardarPromediables(Request $request)
    {
        $request->validate(['mat_campo' => 'required']);
        $campo = trim($request->mat_campo);
        $promediables = $request->input('promediables', []);

        // Materias del campo: las marcadas → mat_promediable=1; las no marcadas → 0
        $materiasCampo = Materia::where('mat_campo', $campo)->pluck('mat_codigo');
        foreach ($materiasCampo as $cod) {
            Materia::where('mat_codigo', $cod)
                ->update(['mat_promediable' => in_array($cod, $promediables) ? 1 : 0]);
        }

        return redirect()
            ->to(route('materias.index', ['tab' => 'grupos']) . '#tabGrupos')
            ->with('success', 'Promediables actualizados para "' . $campo . '"');
    }
}
