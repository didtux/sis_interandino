<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteKardex;
use App\Models\Inscripcion;
use App\Models\CursoMateriaDocente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstudianteKardexController extends Controller
{
    /**
     * Roles administrativos/staff que pueden ver y registrar anotaciones de TODO estudiante:
     * Administrador(1), Director General(9), Directora Académica(10), Secretaría(11),
     * Psicopedagogía(12) y Regencia(14).
     */
    private function esDireccion()
    {
        $u = auth()->user();
        return $u && in_array($u->rol_id, [1, 9, 10, 11, 12, 14]);
    }

    private function esDocente()
    {
        $u = auth()->user();
        return $u && $u->us_entidad_tipo === 'docente';
    }

    /** Estudiantes a los que un docente tiene acceso (los cursos que dicta). */
    private function estudiantesDelDocente($docCodigo, $gestion = null)
    {
        $gestion = $gestion ?: date('Y');
        $cursos = CursoMateriaDocente::where('doc_codigo', $docCodigo)
            ->where('curmatdoc_estado', 1)
            ->pluck('cur_codigo')->unique();

        return Inscripcion::whereIn('cur_codigo', $cursos)
            ->where('insc_gestion', $gestion)
            ->where('insc_estado', 1)
            ->pluck('est_codigo')->unique()->values();
    }

    public function index(Request $request)
    {
        $u = auth()->user();
        if (!$this->esDireccion() && !$this->esDocente()) {
            abort(403, 'Acceso restringido al kardex.');
        }

        $curCodigo = $request->input('cur_codigo');
        $estCodigo = $request->input('est_codigo');
        $tipo      = $request->input('tipo');
        $fechaIni  = $request->input('fecha_ini');
        $fechaFin  = $request->input('fecha_fin');

        $q = EstudianteKardex::with(['estudiante', 'docente', 'curso'])
            ->where('ek_estado', 1)
            ->orderByDesc('ek_fecha')
            ->orderByDesc('ek_id');

        // Docente: solo estudiantes de sus cursos + solo sus propios registros para edición
        if ($this->esDocente() && !$this->esDireccion()) {
            $ests = $this->estudiantesDelDocente($u->us_entidad_id);
            $q->whereIn('est_codigo', $ests);
        }

        if ($curCodigo)             $q->where('cur_codigo', $curCodigo);
        if ($estCodigo)             $q->where('est_codigo', $estCodigo);
        if ($tipo)                  $q->where('ek_tipo', $tipo);
        if ($fechaIni)              $q->whereDate('ek_fecha', '>=', $fechaIni);
        if ($fechaFin)              $q->whereDate('ek_fecha', '<=', $fechaFin);

        $registros = $q->paginate(20)->withQueryString();

        $cursos = $this->esDocente() && !$this->esDireccion()
            ? Curso::whereIn('cur_codigo', CursoMateriaDocente::where('doc_codigo', $u->us_entidad_id)
                ->pluck('cur_codigo')->unique())->orderBy('cur_nombre')->get()
            : Curso::orderBy('cur_nombre')->get();

        // Lista completa de estudiantes para el filtro (Select2 + filtrado dinámico por curso
        // del lado cliente). Para docentes, solo sus estudiantes.
        $estQuery = Estudiante::where('est_visible', 1);
        if ($this->esDocente() && !$this->esDireccion()) {
            $estQuery->whereIn('est_codigo', $this->estudiantesDelDocente($u->us_entidad_id));
        }
        $estudiantes = $estQuery->orderBy('est_apellidos')->orderBy('est_nombres')
            ->get(['est_codigo', 'est_apellidos', 'est_nombres', 'cur_codigo']);

        return view('kardex-estudiante.index', compact(
            'registros','cursos','estudiantes','curCodigo','estCodigo','tipo','fechaIni','fechaFin'
        ));
    }

    public function store(Request $request)
    {
        $u = auth()->user();
        if (!$this->esDireccion() && !$this->esDocente()) abort(403);

        $request->validate([
            'est_codigo'      => 'required|exists:colegio_estudiantes,est_codigo',
            'ek_fecha'        => 'required|date',
            'ek_tipo'         => 'required|in:ACADEMICO,CONDUCTUAL,FELICITACION,OBSERVACION,COMPROMISO',
            'ek_titulo'       => 'required|max:150',
            'ek_descripcion'  => 'nullable|max:2000',
            'ek_acuerdo'      => 'nullable|max:2000',
            'ek_archivo'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Si es docente, validar que el estudiante esté en sus cursos
        if ($this->esDocente() && !$this->esDireccion()) {
            $ests = $this->estudiantesDelDocente($u->us_entidad_id);
            if (!$ests->contains($request->est_codigo)) {
                abort(403, 'No tienes asignado a este estudiante.');
            }
        }

        // Curso actual del estudiante
        $curCodigo = $request->input('cur_codigo')
            ?: Inscripcion::where('est_codigo', $request->est_codigo)
                ->where('insc_gestion', date('Y'))->where('insc_estado', 1)
                ->orderByDesc('insc_id')->value('cur_codigo');

        $data = [
            'est_codigo'        => $request->est_codigo,
            'cur_codigo'        => $curCodigo,
            'ek_fecha'          => $request->ek_fecha,
            'ek_tipo'           => $request->ek_tipo,
            'ek_categoria'      => $request->ek_categoria,
            'ek_titulo'         => $request->ek_titulo,
            'ek_descripcion'    => $request->ek_descripcion,
            'ek_acuerdo'        => $request->ek_acuerdo,
            'ek_visible_padre'  => $request->boolean('ek_visible_padre', true) ? 1 : 0,
            'doc_codigo'        => $this->esDocente() ? $u->us_entidad_id : $request->doc_codigo,
            'mat_codigo'        => $request->mat_codigo,
            'ek_registrado_por' => $u->us_id,
            'ek_estado'         => 1,
        ];

        if ($request->hasFile('ek_archivo')) {
            $f = $request->file('ek_archivo');
            $name = 'ek_'.time().'.'.$f->getClientOriginalExtension();
            $f->move(public_path('uploads/kardex-estudiante'), $name);
            $data['ek_archivo'] = $name;
        }

        EstudianteKardex::create($data);
        return back()->with('success', 'Anotación registrada en el kardex.');
    }

    public function update(Request $request, $id)
    {
        $u = auth()->user();
        $row = EstudianteKardex::findOrFail($id);

        // Solo quien lo creó o dirección
        if (!$this->esDireccion() && $row->ek_registrado_por != $u->us_id) {
            abort(403, 'Solo el autor o dirección pueden editar.');
        }

        $request->validate([
            'ek_titulo'      => 'required|max:150',
            'ek_descripcion' => 'nullable|max:2000',
            'ek_acuerdo'     => 'nullable|max:2000',
        ]);

        $row->fill($request->only(['ek_titulo','ek_descripcion','ek_acuerdo','ek_categoria','ek_tipo','ek_visible_padre']));
        $row->save();

        return back()->with('success', 'Anotación actualizada.');
    }

    public function destroy($id)
    {
        if (!$this->esDireccion()) abort(403, 'Solo dirección puede eliminar.');
        $row = EstudianteKardex::findOrFail($id);
        $row->ek_estado = 0;
        $row->save();
        return back()->with('success', 'Anotación eliminada.');
    }

    /** Endpoint para el padre: marcar como vista. */
    public function marcarVistoPadre($id)
    {
        $u = auth()->user();
        if ($u->us_entidad_tipo !== 'padre') abort(403);

        $row = EstudianteKardex::findOrFail($id);
        // Verificar que el estudiante sea hijo de este padre
        $hijo = Estudiante::where('est_codigo', $row->est_codigo)
            ->where('pfam_codigo', $u->us_entidad_id)->first();
        if (!$hijo) abort(403, 'No corresponde a uno de tus hijos.');

        $row->ek_visto_padre = 1;
        $row->ek_visto_padre_at = now();
        $row->save();
        return back()->with('success', 'Marcado como visto.');
    }

    public function reportePdf(Request $request)
    {
        if (!$this->esDireccion() && !$this->esDocente()) abort(403);

        $estCodigo = $request->input('est_codigo');
        $curCodigo = $request->input('cur_codigo');

        $q = EstudianteKardex::with(['estudiante','docente'])
            ->where('ek_estado', 1)->orderBy('ek_fecha');
        if ($estCodigo) $q->where('est_codigo', $estCodigo);
        if ($curCodigo) $q->where('cur_codigo', $curCodigo);

        $registros = $q->get();
        $estudiante = $estCodigo ? Estudiante::where('est_codigo', $estCodigo)->first() : null;
        $curso = $curCodigo ? Curso::where('cur_codigo', $curCodigo)->first() : null;

        $pdf = Pdf::loadView('kardex-estudiante.reporte-pdf',
            compact('registros','estudiante','curso'))->setPaper('letter');
        return $pdf->stream('kardex-estudiante.pdf');
    }
}
