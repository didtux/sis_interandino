<?php

namespace App\Http\Controllers;

use App\Models\RegistroEnfermeria;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EnfermeriaController extends Controller
{
    public function index(Request $request)
    {
        $query = RegistroEnfermeria::with('estudiante.curso', 'docente')->activo();
        
        if ($request->filled('tipo_persona')) {
            $query->where('enf_tipo_persona', $request->tipo_persona);
        }
        
        if ($request->filled('cur_codigo')) {
            $query->where('enf_tipo_persona', 'ESTUDIANTE')->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }
        
        if ($request->filled('doc_codigo')) {
            $query->where('doc_codigo', $request->doc_codigo);
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('enf_fecha', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('enf_fecha', '<=', $request->fecha_fin);
        }
        
        $registros = $query->orderBy('enf_fecha', 'desc')->orderBy('enf_hora', 'desc')->paginate(20);
        $estudiantes = Estudiante::visible()->orderBy('est_nombres')->get();
        $docentes = \App\Models\Docente::visible()->orderBy('doc_nombres')->get();
        $cursos = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
        
        return view('enfermeria.index', compact('registros', 'estudiantes', 'docentes', 'cursos'));
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_nombres')->get();
        $docentes = \App\Models\Docente::visible()->orderBy('doc_nombres')->get();
        return view('enfermeria.create', compact('estudiantes', 'docentes'));
    }

    public function buscarEstudiante($codigo)
    {
        $estudiante = Estudiante::with('curso')->where('est_codigo', $codigo)->first();
        
        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado']);
        }
        
        return response()->json([
            'success' => true,
            'estudiante' => [
                'codigo' => $estudiante->est_codigo,
                'nombres' => $estudiante->est_nombres . ' ' . $estudiante->est_apellidos,
                'ci' => $estudiante->est_ci,
                'curso' => $estudiante->curso->cur_nombre ?? 'N/A'
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'enf_tipo_persona' => 'required|in:ESTUDIANTE,DOCENTE',
            'enf_fecha' => 'required|date',
            'enf_hora' => 'required',
            'enf_dx_detalle' => 'required|string'
        ]);

        $data = [
            'enf_codigo' => 'ENF' . time(),
            'enf_tipo_persona' => $request->enf_tipo_persona,
            'enf_fecha' => $request->enf_fecha,
            'enf_hora' => $request->enf_hora,
            'enf_dx_detalle' => $request->enf_dx_detalle,
            'enf_medicamentos' => $request->enf_medicamentos,
            'enf_observaciones' => $request->enf_observaciones,
            'enf_registrado_por' => auth()->user()->us_codigo
        ];

        if ($request->enf_tipo_persona == 'ESTUDIANTE') {
            $data['est_codigo'] = $request->est_codigo;
        } else {
            $data['doc_codigo'] = $request->doc_codigo;
        }

        RegistroEnfermeria::create($data);

        return redirect()->route('enfermeria.index')->with('success', 'Registro creado exitosamente');
    }

    public function edit($id)
    {
        $registro = RegistroEnfermeria::with('estudiante.curso', 'docente')->findOrFail($id);
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_nombres')->get();
        $docentes = \App\Models\Docente::visible()->orderBy('doc_nombres')->get();
        return view('enfermeria.edit', compact('registro', 'estudiantes', 'docentes'));
    }

    public function update(Request $request, $id)
    {
        $registro = RegistroEnfermeria::findOrFail($id);
        
        $registro->update([
            'enf_fecha' => $request->enf_fecha,
            'enf_hora' => $request->enf_hora,
            'enf_dx_detalle' => $request->enf_dx_detalle,
            'enf_medicamentos' => $request->enf_medicamentos,
            'enf_observaciones' => $request->enf_observaciones
        ]);

        return redirect()->route('enfermeria.index')->with('success', 'Registro actualizado');
    }

    public function destroy($id)
    {
        RegistroEnfermeria::findOrFail($id)->update(['enf_estado' => 0]);
        return redirect()->back()->with('success', 'Registro eliminado');
    }

    public function reportePdf(Request $request)
    {
        $query = RegistroEnfermeria::with('estudiante.curso')->activo()->where('enf_tipo_persona', 'ESTUDIANTE');
        
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('enf_fecha', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('enf_fecha', '<=', $request->fecha_fin);
        }
        
        $registros = $query->orderBy('enf_fecha', 'desc')->orderBy('enf_hora', 'desc')->get();
        $fechaInicio = $request->fecha_inicio ?? $registros->min('enf_fecha');
        $fechaFin = $request->fecha_fin ?? $registros->max('enf_fecha');
        $curso = $request->filled('cur_codigo') ? \App\Models\Curso::find($request->cur_codigo) : null;
        
        // Agrupar por estudiante y mes
        $estudiantes = $registros->groupBy('est_codigo')->map(function($regs) {
            $estudiante = $regs->first()->estudiante;
            if (!$estudiante) return null;
            
            $porMes = [];
            $totalHigiene = 0;
            $totalAtencion = 0;
            $totalMedicamentos = 0;
            
            foreach($regs as $reg) {
                $mes = $reg->enf_fecha->format('Y-m');
                if (!isset($porMes[$mes])) {
                    $porMes[$mes] = ['HIGIENE PERSONAL' => 0, 'ATENCIÓN MÉDICA' => 0, 'DOTACIÓN DE MEDICAMENTOS' => 0];
                }
                if ($reg->enf_dx_detalle == 'HIGIENE PERSONAL') {
                    $porMes[$mes]['HIGIENE PERSONAL']++;
                    $totalHigiene++;
                } else if ($reg->enf_dx_detalle == 'ATENCIÓN MÉDICA') {
                    $porMes[$mes]['ATENCIÓN MÉDICA']++;
                    $totalAtencion++;
                    if ($reg->enf_medicamentos) {
                        $porMes[$mes]['DOTACIÓN DE MEDICAMENTOS']++;
                        $totalMedicamentos++;
                    }
                }
            }
            return [
                'estudiante' => $estudiante,
                'meses' => $porMes,
                'total_higiene' => $totalHigiene,
                'total_atencion' => $totalAtencion,
                'total_medicamentos' => $totalMedicamentos
            ];
        })->filter();
        
        $meses = $registros->pluck('enf_fecha')->map(fn($f) => $f->format('Y-m'))->unique()->sort()->values();
        
        $pdf = Pdf::loadView('enfermeria.reporte-pdf', compact('estudiantes', 'meses', 'fechaInicio', 'fechaFin', 'curso'))->setPaper('letter', 'landscape');
        return $pdf->stream('reporte-enfermeria-estudiantes-' . date('Y-m-d') . '.pdf');
    }

    public function reporteDocentesPdf(Request $request)
    {
        $query = RegistroEnfermeria::with('docente')->activo()->where('enf_tipo_persona', 'DOCENTE');
        
        if ($request->filled('doc_codigo')) {
            $query->where('doc_codigo', $request->doc_codigo);
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('enf_fecha', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('enf_fecha', '<=', $request->fecha_fin);
        }
        
        $registros = $query->orderBy('enf_fecha', 'desc')->orderBy('enf_hora', 'desc')->get();
        $fechaInicio = $request->fecha_inicio ?? $registros->min('enf_fecha');
        $fechaFin = $request->fecha_fin ?? $registros->max('enf_fecha');
        
        $pdf = Pdf::loadView('enfermeria.reporte-docentes-pdf', compact('registros', 'fechaInicio', 'fechaFin'))->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-enfermeria-docentes-' . date('Y-m-d') . '.pdf');
    }
}
