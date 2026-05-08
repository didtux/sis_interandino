<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\ListaCurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'registrados'); // registrados | retirados | todos
        $query = Estudiante::with('curso');

        if ($estado === 'registrados')   $query->where('colegio_estudiantes.est_visible', 1);
        elseif ($estado === 'retirados') $query->where('colegio_estudiantes.est_visible', 0);

        // Filtro para estudiantes incompletos
        if ($request->filled('incompletos')) {
            $year = date('Y');
            $estudiantesConInscripcion = \App\Models\Inscripcion::where('insc_gestion', $year)
                ->where('insc_codigo', 'like', 'INSC%')
                ->pluck('est_codigo')
                ->toArray();

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
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('curso')) {
            $query->where('colegio_estudiantes.cur_codigo', $request->curso);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('colegio_estudiantes.est_nombres', 'like', "%{$buscar}%")
                  ->orWhere('colegio_estudiantes.est_apellidos', 'like', "%{$buscar}%")
                  ->orWhere('colegio_estudiantes.est_codigo', 'like', "%{$buscar}%")
                  ->orWhere('colegio_estudiantes.est_ci', 'like', "%{$buscar}%");
            });
        }

        // Si filtra por curso, ordena por lista_numero de la gestión actual
        if ($request->filled('curso')) {
            $gestion = date('Y');
            $estudiantes = $query->leftJoin('colegio_lista_curso', function($j) use ($gestion){
                    $j->whereRaw('colegio_lista_curso.est_codigo COLLATE utf8mb4_unicode_ci = colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci')
                      ->where('colegio_lista_curso.lista_gestion', '=', $gestion);
                })
                ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero')
                ->orderByRaw('colegio_lista_curso.lista_numero IS NULL ASC')
                ->orderBy('colegio_lista_curso.lista_numero', 'asc')
                ->orderBy('colegio_estudiantes.est_apellidos')
                ->paginate(20)->withQueryString();
        } else {
            $estudiantes = $query->orderBy('est_apellidos')
                ->orderBy('est_nombres')
                ->paginate(20)->withQueryString();
        }

        $cursos = Curso::visible()->ordenado()->get();

        return view('estudiantes.index', compact('estudiantes', 'cursos', 'estado'));
    }

    public function toggleEstado($id)
    {
        $est = Estudiante::findOrFail($id);
        $est->est_visible = $est->est_visible == 1 ? 0 : 1;
        $est->save();
        $msg = $est->est_visible == 1 ? 'Estudiante ACTIVADO' : 'Estudiante RETIRADO';
        return back()->with('success', $msg);
    }

    public function subirLista($id)
    {
        $this->moverLista($id, -1);
        return back()->with('success', 'Estudiante movido arriba');
    }

    public function bajarLista($id)
    {
        $this->moverLista($id, +1);
        return back()->with('success', 'Estudiante movido abajo');
    }

    private function moverLista($estId, $delta)
    {
        $est = Estudiante::findOrFail($estId);
        $gestion = date('Y');

        $reg = ListaCurso::where('est_codigo', $est->est_codigo)
            ->where('lista_gestion', $gestion)
            ->first();
        if (!$reg) return;

        $vecino = ListaCurso::where('cur_codigo', $reg->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->where('lista_numero', $reg->lista_numero + $delta)
            ->first();

        if ($vecino) {
            $tmp = $reg->lista_numero;
            $reg->lista_numero    = $vecino->lista_numero;
            $vecino->lista_numero = $tmp;
            $reg->save();
            $vecino->save();
        }
    }

    public function reprobados(Request $request)
    {
        $cursos    = Curso::visible()->ordenado()->get();
        $cursoCod  = $request->input('curso');
        $periodoId = $request->input('periodo_id');
        $gestion   = date('Y');
        $rows      = collect();

        if ($cursoCod && $periodoId) {
            $rows = DB::select("
                SELECT lc.lista_numero,
                       e.est_codigo,
                       CONCAT(e.est_apellidos,' ',e.est_nombres) AS nombre,
                       e.est_visible,
                       SUM(CASE WHEN ROUND(n.nota_promedio_trimestral) < 51 THEN 1 ELSE 0 END) AS materias_reprobadas
                FROM colegio_lista_curso lc
                JOIN colegio_estudiantes e ON e.est_codigo COLLATE utf8mb4_unicode_ci = lc.est_codigo COLLATE utf8mb4_unicode_ci
                LEFT JOIN colegio_notas n  ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci AND n.periodo_id = ?
                WHERE lc.cur_codigo = ? AND lc.lista_gestion = ?
                GROUP BY lc.lista_numero, e.est_codigo, e.est_apellidos, e.est_nombres, e.est_visible
                HAVING materias_reprobadas > 0
                ORDER BY lc.lista_numero ASC
            ", [$periodoId, $cursoCod, $gestion]);
        }

        $periodos = DB::table('notas_config_periodos')
            ->where('periodo_gestion', $gestion)
            ->orderBy('periodo_numero')
            ->get();

        return view('estudiantes.reprobados', compact('cursos', 'cursoCod', 'periodoId', 'rows', 'periodos'));
    }

    public function listadoContactos(Request $request)
    {
        $cursoCod = $request->input('curso');
        if (!$cursoCod) abort(400, 'Falta curso');

        $curso = Curso::where('cur_codigo', $cursoCod)->firstOrFail();
        $rows  = DB::table('colegio_estudiantes as e')
            ->leftJoin('rela_estudiantespadres as rp', function($j){
                $j->on('rp.est_id','=','e.est_codigo')->where('rp.estpad_estado',1);
            })
            ->leftJoin('cole_padresfamilia as p','p.pfam_codigo','=','rp.pfam_id')
            ->where('e.cur_codigo', $cursoCod)
            ->where('e.est_visible', 1)
            ->orderBy('e.est_apellidos')
            ->select('e.est_codigo','e.est_nombres','e.est_apellidos',
                     'p.pfam_nombres', 'p.pfam_numeroscelular as pfam_telefono', 'p.pfam_parentesco')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.contactos-pdf', compact('curso','rows'))
            ->setPaper('letter');
        return $pdf->stream('contactos-'.$curso->cur_codigo.'.pdf');
    }

    public function codigosQR(Request $request)
    {
        $cursoCod = $request->input('curso');
        if (!$cursoCod) abort(400, 'Falta curso');

        $curso = Curso::where('cur_codigo', $cursoCod)->firstOrFail();
        $estudiantes = Estudiante::where('cur_codigo', $cursoCod)
            ->where('est_visible', 1)
            ->orderBy('est_apellidos')->orderBy('est_nombres')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.qr-pdf', compact('curso','estudiantes'))
            ->setPaper('letter');
        return $pdf->stream('qr-'.$curso->cur_codigo.'.pdf');
    }

    public function listadoExcel(Request $request)
    {
        $cursoCod = $request->input('curso');
        $query = Estudiante::with('curso','padres')->where('est_visible', 1);
        if ($cursoCod) $query->where('cur_codigo', $cursoCod);
        $estudiantes = $query->orderBy('est_apellidos')->orderBy('est_nombres')->get();

        $filename = 'estudiantes-'.($cursoCod ?: 'todos').'-'.date('Y-m-d').'.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
        return response()->stream(function() use ($estudiantes) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Codigo','Apellidos','Nombres','CI','Curso','Sexo','FechaNac','Telefono','Padre/Tutor','Tel.Padre']);
            foreach ($estudiantes as $e) {
                $padre = $e->padres->first();
                fputcsv($out, [
                    $e->est_codigo, $e->est_apellidos, $e->est_nombres, $e->est_ci,
                    $e->curso->cur_nombre ?? '', $e->est_sexo ?? '', $e->est_fechanac ?? '',
                    $e->est_celular ?? '',
                    $padre->pfam_nombres ?? '', $padre->pfam_numeroscelular ?? '',
                ]);
            }
            fclose($out);
        }, 200, $headers);
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
            $estudiante->delete();
            return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado definitivamente');
        } catch (\Exception $e) {
            return redirect()->route('estudiantes.index')->with('error', 'No se pudo eliminar el estudiante (puede tener registros asociados): ' . $e->getMessage());
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
        // Incluye retirados; el PDF los marca en rojo con badge RETIRADO
        $query = Estudiante::with('curso')->orderBy('est_apellidos');
        
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
