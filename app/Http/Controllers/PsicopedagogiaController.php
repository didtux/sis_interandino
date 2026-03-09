<?php

namespace App\Http\Controllers;

use App\Models\CasoPsicopedagogia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PsicopedagogiaController extends Controller
{
    public function index(Request $request)
    {
        $query = CasoPsicopedagogia::with('estudiante.curso', 'estudiante.padres')->activo();
        
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('psico_fecha', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('psico_fecha', '<=', $request->fecha_fin);
        }
        
        $casos = $query->orderBy('psico_fecha', 'desc')->paginate(20);
        $estudiantes = Estudiante::visible()->orderBy('est_nombres')->get();
        $cursos = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
        
        return view('psicopedagogia.index', compact('casos', 'estudiantes', 'cursos'));
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_nombres')->get();
        return view('psicopedagogia.create', compact('estudiantes'));
    }

    public function buscarEstudiante($codigo)
    {
        $estudiante = Estudiante::with('curso', 'padres')->where('est_codigo', $codigo)->first();
        
        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado']);
        }
        
        return response()->json([
            'success' => true,
            'estudiante' => [
                'codigo' => $estudiante->est_codigo,
                'nombres' => $estudiante->est_nombres . ' ' . $estudiante->est_apellidos,
                'ci' => $estudiante->est_ci,
                'curso' => $estudiante->curso->cur_nombre ?? 'N/A',
                'padres' => $estudiante->padres->map(function($padre) {
                    return $padre->pfam_nombres . ' ' . $padre->pfam_apellidos . ' - ' . $padre->pfam_celular;
                })
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'psico_fecha' => 'required|date',
            'psico_caso' => 'required|string',
            'psico_tipo_acuerdo' => 'required|in:VERBAL,ESCRITO,NINGUNO'
        ]);

        CasoPsicopedagogia::create([
            'psico_codigo' => 'PSICO' . time(),
            'est_codigo' => $request->est_codigo,
            'psico_fecha' => $request->psico_fecha,
            'psico_caso' => $request->psico_caso,
            'psico_solucion' => $request->psico_solucion,
            'psico_acuerdo' => $request->psico_acuerdo,
            'psico_tipo_acuerdo' => $request->psico_tipo_acuerdo,
            'psico_observaciones' => $request->psico_observaciones,
            'psico_registrado_por' => auth()->user()->us_codigo
        ]);

        return redirect()->route('psicopedagogia.index')->with('success', 'Caso registrado exitosamente');
    }

    public function edit($id)
    {
        $caso = CasoPsicopedagogia::with('estudiante.curso', 'estudiante.padres')->findOrFail($id);
        $estudiantes = Estudiante::visible()->with('curso')->orderBy('est_nombres')->get();
        return view('psicopedagogia.edit', compact('caso', 'estudiantes'));
    }

    public function update(Request $request, $id)
    {
        $caso = CasoPsicopedagogia::findOrFail($id);
        
        $caso->update([
            'psico_fecha' => $request->psico_fecha,
            'psico_caso' => $request->psico_caso,
            'psico_solucion' => $request->psico_solucion,
            'psico_acuerdo' => $request->psico_acuerdo,
            'psico_tipo_acuerdo' => $request->psico_tipo_acuerdo,
            'psico_observaciones' => $request->psico_observaciones
        ]);

        return redirect()->route('psicopedagogia.index')->with('success', 'Caso actualizado');
    }

    public function destroy($id)
    {
        CasoPsicopedagogia::findOrFail($id)->update(['psico_estado' => 0]);
        return redirect()->back()->with('success', 'Caso eliminado');
    }

    public function reportePdf(Request $request)
    {
        $query = CasoPsicopedagogia::with('estudiante.curso', 'estudiante.padres')->activo();
        
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('psico_fecha', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('psico_fecha', '<=', $request->fecha_fin);
        }
        
        $casos = $query->orderBy('psico_fecha', 'desc')->get();
        $fechaInicio = $request->fecha_inicio ?? $casos->min('psico_fecha');
        $fechaFin = $request->fecha_fin ?? $casos->max('psico_fecha');
        $curso = $request->filled('cur_codigo') ? \App\Models\Curso::find($request->cur_codigo) : null;
        
        $pdf = Pdf::loadView('psicopedagogia.reporte-pdf', compact('casos', 'fechaInicio', 'fechaFin', 'curso'))->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-psicopedagogia-' . date('Y-m-d') . '.pdf');
    }

    public function compromisoPdf($id)
    {
        $caso = CasoPsicopedagogia::with('estudiante.curso', 'estudiante.padres')->findOrFail($id);
        $pdf = Pdf::loadView('psicopedagogia.compromiso-pdf', compact('caso'))->setPaper('letter', 'portrait');
        return $pdf->stream('compromiso-' . $caso->estudiante->est_codigo . '-' . date('Y-m-d') . '.pdf');
    }
}
