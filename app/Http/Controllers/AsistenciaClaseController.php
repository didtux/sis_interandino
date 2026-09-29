<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaClase;
use App\Models\CursoMateriaDocente;
use App\Models\NotaPeriodo;
use App\Models\Estudiante;
use App\Models\ListaCurso;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AsistenciaClaseController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

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
            // Búsqueda por tokens resolviendo doc_codigos en query separada para evitar
            // "Illegal mix of collations" al cruzar colegio_curso_materia_docente con colegio_docentes.
            $tokens = preg_split('/\s+/', trim($request->buscar), -1, PREG_SPLIT_NO_EMPTY);
            $docQ = \DB::table('colegio_docentes');
            foreach ($tokens as $t) {
                $docQ->where(function ($qq) use ($t) {
                    $qq->where('doc_nombres', 'like', "%$t%")
                       ->orWhere('doc_apellidos', 'like', "%$t%")
                       ->orWhere('doc_codigo', 'like', "%$t%");
                });
            }
            $docCodigos = $docQ->pluck('doc_codigo')->all();
            $query->whereIn('doc_codigo', $docCodigos ?: ['__none__']);
        }

        $asignaciones = $query->get();

        // Filtrar cursos y materias según el rol
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id) {
            $misAsignaciones = CursoMateriaDocente::where('curmatdoc_estado', 1)
                ->where('doc_codigo', $user->us_entidad_id)->get();
            $curCodigos = $misAsignaciones->pluck('cur_codigo')->unique();
            $matCodigos = $misAsignaciones->pluck('mat_codigo')->unique();
            $cursos = \App\Models\Curso::visible()->whereIn('cur_codigo', $curCodigos)->orderBy('cur_nombre')->get();
            $materias = \App\Models\Materia::visible()->whereIn('mat_codigo', $matCodigos)->orderBy('mat_nombre')->get();
        } else {
            $cursos = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
            $materias = \App\Models\Materia::visible()->orderBy('mat_nombre')->get();
        }

        return view('notas.asistencia-clases.index', compact('asignaciones', 'periodos', 'cursos', 'materias'));
    }

    public function vistaGeneral($curmatdocId, $periodoId, Request $request)
    {
        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;

        $user = auth()->user();
        $esDocente = $user->us_entidad_tipo === 'docente';
        if ($esDocente && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        $hoy = now()->toDateString();
        $enRangoPeriodo = $hoy >= $periodo->periodo_fecha_inicio->toDateString() && $hoy <= $periodo->periodo_fecha_fin->toDateString();

        $queryEst = Estudiante::visible()
            ->where('colegio_estudiantes.cur_codigo', $asignacion->cur_codigo)
            ->leftJoin('colegio_lista_curso', function ($j) use ($gestion, $asignacion) {
                $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo')
                  ->where('colegio_lista_curso.lista_gestion', $gestion)
                  ->where('colegio_lista_curso.cur_codigo', $asignacion->cur_codigo);
            })
            ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero');

        if ($request->filled('buscar_est')) {
            $queryEst->where(function($q) use ($request) {
                $q->where('colegio_estudiantes.est_nombres', 'like', '%'.$request->buscar_est.'%')
                  ->orWhere('colegio_estudiantes.est_apellidos', 'like', '%'.$request->buscar_est.'%');
            });
        }

        $estudiantes = $queryEst->orderBy('colegio_lista_curso.lista_numero')
            ->orderBy('colegio_estudiantes.est_apellidos')->get();

        $queryAsis = AsistenciaClase::where('curmatdoc_id', $curmatdocId)->where('periodo_id', $periodoId);
        if ($request->filled('fecha_desde')) {
            $queryAsis->where('asiscl_fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $queryAsis->where('asiscl_fecha', '<=', $request->fecha_hasta);
        }

        $todasAsis = $queryAsis->get();
        $fechas = $todasAsis->pluck('asiscl_fecha')->map(fn($f) => $f->format('Y-m-d'))->unique()->sort()->values();
        $asistencias = $todasAsis->groupBy('est_codigo')->map(fn($items) => $items->keyBy(fn($a) => $a->asiscl_fecha->format('Y-m-d')));
        $totales = $todasAsis->groupBy('est_codigo')->map(fn($items) => $items->groupBy('asiscl_estado')->map(fn($g) => $g->count()));

        return view('notas.asistencia-clases.vista-general', compact(
            'asignacion', 'periodo', 'estudiantes', 'fechas', 'asistencias', 'totales', 'enRangoPeriodo', 'esDocente'
        ));
    }

    public function registrar($curmatdocId, $periodoId, Request $request)
    {
        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;

        $user = auth()->user();
        $esDocente = $user->us_entidad_tipo === 'docente';
        if ($esDocente && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        $fecha = $request->input('fecha', now()->toDateString());
        $hoy = now()->toDateString();
        $enRango = $fecha >= $periodo->periodo_fecha_inicio->toDateString() && $fecha <= $periodo->periodo_fecha_fin->toDateString();
        $puedeEditar = $enRango;

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

        $asistencias = AsistenciaClase::where('curmatdoc_id', $curmatdocId)
            ->where('asiscl_fecha', $fecha)->get()->keyBy('est_codigo');

        // Fechas ya registradas en este periodo
        $fechasRegistradas = AsistenciaClase::where('curmatdoc_id', $curmatdocId)
            ->where('periodo_id', $periodoId)
            ->selectRaw('DISTINCT asiscl_fecha')
            ->orderBy('asiscl_fecha', 'desc')
            ->pluck('asiscl_fecha')->map(fn($f) => $f->format('Y-m-d'));

        $yaRegistrada = $asistencias->isNotEmpty();
        // Docente: crear en cualquier fecha del periodo, editar solo hoy
        // Admin: crear y editar cualquier fecha del periodo
        if ($esDocente && $yaRegistrada && $fecha != $hoy) {
            $puedeEditar = false;
        }

        return view('notas.asistencia-clases.registrar', compact(
            'asignacion', 'periodo', 'estudiantes', 'asistencias',
            'fecha', 'enRango', 'puedeEditar', 'esDocente', 'fechasRegistradas', 'yaRegistrada'
        ));
    }

    /**
     * Pantalla de escaneo QR para que el docente tome la asistencia DE SU CLASE.
     *
     * El escaneo que ya existía (EscaneoAsistenciaController) es el de portería:
     * escribe en colegio_asistencia, no distingue materia y además le da 403 al
     * docente. Este escribe en notas_asistencia_clases, que es la tabla de la
     * asistencia por clase, y respeta los mismos permisos que registrar().
     */
    public function qr($curmatdocId, $periodoId, Request $request)
    {
        [$asignacion, $periodo] = $this->asignacionAutorizada($curmatdocId, $periodoId);

        $gestion = $periodo->periodo_gestion;
        $fecha   = $request->input('fecha', now()->toDateString());
        $enRango = $fecha >= $periodo->periodo_fecha_inicio->toDateString()
                && $fecha <= $periodo->periodo_fecha_fin->toDateString();

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

        $asistencias = AsistenciaClase::where('curmatdoc_id', $curmatdocId)
            ->where('asiscl_fecha', $fecha)->get()->keyBy('est_codigo');

        return view('notas.asistencia-clases.qr', compact(
            'asignacion', 'periodo', 'estudiantes', 'asistencias', 'fecha', 'enRango'
        ));
    }

    /**
     * Marca un estudiante por QR. El código escaneado es el est_codigo, el mismo
     * que imprime la hoja de QR de estudiantes.
     */
    public function qrMarcar(Request $request)
    {
        $request->validate([
            'curmatdoc_id' => 'required',
            'periodo_id'   => 'required',
            'fecha'        => 'required|date',
            'codigo'       => 'required',
            'estado'       => 'nullable|in:P,A,F,L',
        ]);

        [$asignacion, $periodo] = $this->asignacionAutorizada($request->curmatdoc_id, $request->periodo_id);

        $fecha  = $request->fecha;
        $estado = $request->input('estado', 'P');
        $user   = auth()->user();
        $hoy    = now()->toDateString();

        if ($fecha < $periodo->periodo_fecha_inicio->toDateString() || $fecha > $periodo->periodo_fecha_fin->toDateString()) {
            return response()->json(['success' => false, 'message' => 'La fecha está fuera del rango del periodo.']);
        }

        // Misma regla que guardar(): el docente sólo edita el día de hoy.
        $esDocente = $user->us_entidad_tipo === 'docente';
        $yaExiste  = AsistenciaClase::where('curmatdoc_id', $asignacion->curmatdoc_id)
            ->where('asiscl_fecha', $fecha)->exists();
        if ($esDocente && $yaExiste && $fecha != $hoy) {
            return response()->json(['success' => false, 'message' => 'Solo puede editar la asistencia del día actual.']);
        }

        $codigo = trim((string) $request->codigo);
        $estudiante = Estudiante::visible()->where('est_codigo', $codigo)->first();
        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado o inactivo: ' . $codigo]);
        }
        // Que sea de ESTE curso: el QR de otro curso no puede colarse en la clase.
        if ($estudiante->cur_codigo !== $asignacion->cur_codigo) {
            return response()->json([
                'success' => false,
                'message' => ($estudiante->est_apellidos . ' ' . $estudiante->est_nombres) . ' no pertenece a este curso.',
            ]);
        }

        $previo = AsistenciaClase::where('curmatdoc_id', $asignacion->curmatdoc_id)
            ->where('est_codigo', $codigo)->where('asiscl_fecha', $fecha)->first();
        $repetido = $previo !== null;

        AsistenciaClase::updateOrCreate(
            ['curmatdoc_id' => $asignacion->curmatdoc_id, 'est_codigo' => $codigo, 'asiscl_fecha' => $fecha],
            [
                'periodo_id'            => $periodo->periodo_id,
                'asiscl_estado'         => $estado,
                'asiscl_registrado_por' => $user->us_id,
            ]
        );

        $numero = ListaCurso::where('est_codigo', $codigo)
            ->where('lista_gestion', $periodo->periodo_gestion)
            ->where('cur_codigo', $asignacion->cur_codigo)->value('lista_numero');

        return response()->json([
            'success'  => true,
            'repetido' => $repetido,
            'message'  => $repetido ? 'Actualizado' : 'Registrado',
            'estudiante' => [
                'codigo' => $codigo,
                'numero' => $numero,
                'nombre' => trim($estudiante->est_apellidos . ' ' . $estudiante->est_nombres),
                'estado' => $estado,
                'hora'   => now()->format('H:i:s'),
            ],
            'total_marcados' => AsistenciaClase::where('curmatdoc_id', $asignacion->curmatdoc_id)
                ->where('asiscl_fecha', $fecha)->count(),
        ]);
    }

    /**
     * Asignación + periodo, verificando que el docente logueado sea el titular.
     * Misma comprobación repetida en registrar(), vistaGeneral() y guardar().
     */
    private function asignacionAutorizada($curmatdocId, $periodoId): array
    {
        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo    = NotaPeriodo::findOrFail($periodoId);

        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id
            && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }
        return [$asignacion, $periodo];
    }

    public function guardar(Request $request)
    {
        $curmatdocId = $request->input('curmatdoc_id');
        $periodoId = $request->input('periodo_id');
        $fecha = $request->input('fecha');
        $estados = $request->input('estados', []);

        $asignacion = CursoMateriaDocente::findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);

        $user = auth()->user();
        $esDocente = $user->us_entidad_tipo === 'docente';
        if ($esDocente && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        $hoy = now()->toDateString();
        $yaExiste = AsistenciaClase::where('curmatdoc_id', $curmatdocId)
            ->where('asiscl_fecha', $fecha)->exists();
        if ($esDocente && $yaExiste && $fecha != $hoy) {
            return back()->with('error', 'Solo puede editar la asistencia del día actual. Para fechas anteriores solo puede crear registros nuevos.');
        }

        if ($fecha < $periodo->periodo_fecha_inicio->toDateString() || $fecha > $periodo->periodo_fecha_fin->toDateString()) {
            return back()->with('error', 'La fecha está fuera del rango del periodo.');
        }

        foreach ($estados as $estCodigo => $estado) {
            if (!in_array($estado, ['P', 'A', 'F', 'L'])) continue;
            AsistenciaClase::updateOrCreate(
                ['curmatdoc_id' => $curmatdocId, 'est_codigo' => $estCodigo, 'asiscl_fecha' => $fecha],
                [
                    'periodo_id' => $periodoId,
                    'asiscl_estado' => $estado,
                    'asiscl_observacion' => $request->input("obs.$estCodigo"),
                    'asiscl_registrado_por' => $user->us_id,
                ]
            );
        }

        return redirect()->route('asistencia-clases.vista-general', [$curmatdocId, $periodoId])
            ->with('success', 'Asistencia del ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y') . ' registrada correctamente');
    }

    public function reportePdf($curmatdocId, $periodoId, Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;

        $lista = ListaCurso::where('cur_codigo', $asignacion->cur_codigo)
            ->where('lista_gestion', $gestion)->pluck('lista_numero', 'est_codigo');

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

        $queryAsis = AsistenciaClase::where('curmatdoc_id', $curmatdocId)->where('periodo_id', $periodoId);
        if ($request->filled('fecha_desde')) $queryAsis->where('asiscl_fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $queryAsis->where('asiscl_fecha', '<=', $request->fecha_hasta);

        $todasAsis = $queryAsis->get();
        $fechas = $todasAsis->pluck('asiscl_fecha')->map(fn($f) => $f->format('Y-m-d'))->unique()->sort()->values();
        $asistencias = $todasAsis->groupBy('est_codigo')->map(fn($items) => $items->keyBy(fn($a) => $a->asiscl_fecha->format('Y-m-d')));
        $totales = $todasAsis->groupBy('est_codigo')->map(fn($items) => $items->groupBy('asiscl_estado')->map(fn($g) => $g->count()));

        $pdf = Pdf::loadView('notas.asistencia-clases.reporte-pdf', compact(
            'asignacion', 'periodo', 'estudiantes', 'fechas', 'asistencias', 'totales', 'lista', 'gestion'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('asistencia-clases-' . $asignacion->cur_codigo . '-' . $periodo->periodo_numero . '.pdf');
    }
}
