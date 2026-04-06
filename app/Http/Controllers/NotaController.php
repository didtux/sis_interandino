<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaDetalle;
use App\Models\NotaPeriodo;
use App\Models\NotaDimension;
use App\Models\CursoMateriaDocente;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $query = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->where('curmatdoc_estado', 1);

        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id) {
            $query->where('doc_codigo', $user->us_entidad_id);
        }
        if ($request->filled('cur_codigo')) {
            $curCodigos = is_array($request->cur_codigo) ? $request->cur_codigo : [$request->cur_codigo];
            $query->whereIn('cur_codigo', $curCodigos);
        }
        if ($request->filled('mat_codigo')) {
            $matCodigos = is_array($request->mat_codigo) ? $request->mat_codigo : [$request->mat_codigo];
            $query->whereIn('mat_codigo', $matCodigos);
        }
        if ($request->filled('buscar')) {
            $query->whereHas('docente', function($q) use ($request) {
                $q->where('doc_nombres', 'like', '%'.$request->buscar.'%')
                  ->orWhere('doc_apellidos', 'like', '%'.$request->buscar.'%');
            });
        }
        if ($request->filled('estado')) {
            $estadoFiltro = intval($request->estado);
            $query->whereHas('notas', fn($q) => $q->where('nota_estado', $estadoFiltro));
        }

        $asignaciones = $query->get();
        $cursos = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
        $materias = \App\Models\Materia::visible()->orderBy('mat_nombre')->get();

        return view('notas.index', compact('asignaciones', 'periodos', 'dimensiones', 'cursos', 'materias'));
    }

    public function calificar($curmatdocId, $periodoId)
    {
        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        // Verificar si estamos dentro del rango del periodo
        $hoy = now()->toDateString();
        $enRango = $hoy >= $periodo->periodo_fecha_inicio->toDateString() && $hoy <= $periodo->periodo_fecha_fin->toDateString();

        $estudiantes = Estudiante::visible()
            ->where('colegio_estudiantes.cur_codigo', $asignacion->cur_codigo)
            ->leftJoin('colegio_lista_curso', function ($j) use ($gestion, $asignacion) {
                $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo')
                  ->where('colegio_lista_curso.lista_gestion', $gestion)
                  ->where('colegio_lista_curso.cur_codigo', $asignacion->cur_codigo);
            })
            ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero')
            ->orderBy('colegio_lista_curso.lista_numero')
            ->orderBy('colegio_estudiantes.est_apellidos')->get();

        $notasExistentes = Nota::with('detalles')
            ->where('curmatdoc_id', $curmatdocId)
            ->where('periodo_id', $periodoId)->get()->keyBy('est_codigo');

        $estadoNotas = $notasExistentes->isNotEmpty() ? $notasExistentes->first()->nota_estado : 0;
        $esEditable = $enRango && in_array($estadoNotas, [0, 3]) || !$notasExistentes->count();

        // Datos de auditoría para mostrar al docente
        $notaInfo = $notasExistentes->isNotEmpty() ? $notasExistentes->first() : null;
        $observacionAdmin = $notaInfo->nota_observacion ?? null;
        $fechaAprobacion = $notaInfo->nota_fecha_aprobacion ?? null;
        $aprobadoPor = null;
        if ($notaInfo && $notaInfo->nota_aprobado_por) {
            $aprobadoPor = \App\Models\User::find($notaInfo->nota_aprobado_por);
        }

        return view('notas.calificar', compact(
            'asignacion', 'periodo', 'dimensiones', 'estudiantes',
            'notasExistentes', 'estadoNotas', 'enRango', 'esEditable',
            'observacionAdmin', 'fechaAprobacion', 'aprobadoPor'
        ));
    }

    public function guardar(Request $request)
    {
        $curmatdocId = $request->input('curmatdoc_id');
        $periodoId = $request->input('periodo_id');
        $notasInput = $request->input('notas', []);
        $accion = $request->input('accion', 'guardar');

        $asignacion = CursoMateriaDocente::findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);

        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        // Validar rango de fechas
        $hoy = now()->toDateString();
        if ($hoy < $periodo->periodo_fecha_inicio->toDateString() || $hoy > $periodo->periodo_fecha_fin->toDateString()) {
            return back()->with('error', 'No se pueden registrar notas fuera del rango del periodo (' . $periodo->periodo_fecha_inicio->format('d/m/Y') . ' - ' . $periodo->periodo_fecha_fin->format('d/m/Y') . ')');
        }

        $gestion = $periodo->periodo_gestion;
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        foreach ($notasInput as $estCodigo => $dimData) {
            $notaData = ['nota_estado' => $accion === 'enviar' ? 1 : 0];
            if ($accion === 'enviar') {
                $notaData['nota_enviado_por'] = auth()->user()->us_id;
                $notaData['nota_fecha_envio'] = now();
            } else {
                $notaData['nota_guardado_por'] = auth()->user()->us_id;
                $notaData['nota_fecha_guardado'] = now();
            }

            $nota = Nota::updateOrCreate(
                ['periodo_id' => $periodoId, 'curmatdoc_id' => $curmatdocId, 'est_codigo' => $estCodigo],
                $notaData
            );

            $promedioTrimestral = 0;

            foreach ($dimensiones as $dim) {
                $valores = $dimData[$dim->dimension_id] ?? [];
                $suma = 0;
                $count = 0;

                for ($col = 1; $col <= $dim->dimension_columnas; $col++) {
                    $val = isset($valores[$col]) && $valores[$col] !== '' ? floatval($valores[$col]) : null;
                    NotaDetalle::updateOrCreate(
                        ['nota_id' => $nota->nota_id, 'dimension_id' => $dim->dimension_id, 'columna_num' => $col],
                        ['detalle_valor' => $val ?? 0]
                    );
                    if ($val !== null && $val > 0) {
                        $suma += $val;
                        $count++;
                    }
                }

                // Promedio: si hay notas, promedio de las ingresadas; si solo 1 columna, es el valor directo
                $promDim = $count > 0 ? ($dim->dimension_columnas == 1 ? $suma : $suma / $count) : 0;
                $promedioTrimestral += $promDim;
            }

            $nota->update([
                'nota_promedio_trimestral' => round($promedioTrimestral, 2)
            ]);
        }

        $msg = $accion === 'enviar' ? 'Notas enviadas para aprobación' : 'Notas guardadas como borrador';
        return redirect()->route('notas.calificar', [$curmatdocId, $periodoId])->with('success', $msg);
    }

    public function aprobar(Request $request, $curmatdocId, $periodoId)
    {
        $accion = $request->input('accion');
        Nota::where('curmatdoc_id', $curmatdocId)->where('periodo_id', $periodoId)->update([
            'nota_estado' => $accion === 'aprobar' ? 2 : 3,
            'nota_fecha_aprobacion' => now(),
            'nota_aprobado_por' => auth()->user()->us_id,
            'nota_observacion' => $request->input('observacion'),
        ]);
        $msg = $accion === 'aprobar' ? 'Notas aprobadas exitosamente' : 'Notas rechazadas';
        return redirect()->route('notas.calificar', [$curmatdocId, $periodoId])->with('success', $msg);
    }

    public function configuracion()
    {
        $gestion = date('Y');
        $periodos = NotaPeriodo::where('periodo_gestion', $gestion)->orderBy('periodo_numero')->get();
        $dimensiones = NotaDimension::where('dimension_gestion', $gestion)->orderBy('dimension_orden')->get();
        return view('notas.configuracion', compact('periodos', 'dimensiones', 'gestion'));
    }

    public function guardarPeriodo(Request $request)
    {
        $request->validate([
            'periodo_nombre' => 'required|max:50',
            'periodo_numero' => 'required|integer|min:1',
            'periodo_fecha_inicio' => 'required|date',
            'periodo_fecha_fin' => 'required|date|after:periodo_fecha_inicio',
        ]);
        NotaPeriodo::updateOrCreate(
            ['periodo_id' => $request->periodo_id],
            $request->only('periodo_nombre', 'periodo_numero', 'periodo_fecha_inicio', 'periodo_fecha_fin', 'periodo_gestion', 'periodo_estado')
        );
        return redirect()->route('notas.configuracion')->with('success', 'Periodo guardado');
    }

    public function eliminarPeriodo($id)
    {
        if (Nota::where('periodo_id', $id)->exists()) {
            return back()->with('error', 'No se puede eliminar, tiene notas asociadas');
        }
        NotaPeriodo::findOrFail($id)->delete();
        return redirect()->route('notas.configuracion')->with('success', 'Periodo eliminado');
    }

    public function guardarDimension(Request $request)
    {
        $request->validate([
            'dimension_nombre' => 'required|max:50',
            'dimension_valor_max' => 'required|integer|min:1|max:100',
            'dimension_columnas' => 'required|integer|min:1|max:10',
        ]);
        NotaDimension::updateOrCreate(
            ['dimension_id' => $request->dimension_id],
            $request->only('dimension_nombre', 'dimension_valor_max', 'dimension_columnas', 'dimension_orden', 'dimension_gestion', 'dimension_estado')
        );
        return redirect()->route('notas.configuracion')->with('success', 'Dimensión guardada');
    }

    public function eliminarDimension($id)
    {
        NotaDimension::findOrFail($id)->delete();
        return redirect()->route('notas.configuracion')->with('success', 'Dimensión eliminada');
    }
}
