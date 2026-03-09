<?php

namespace App\Http\Controllers;

use App\Models\PadreFamilia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PadreFamiliaController extends Controller
{
    public function index(Request $request)
    {
        $query = PadreFamilia::activo();
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('pfam_nombres', 'like', "%{$buscar}%")
                  ->orWhere('pfam_ci', 'like', "%{$buscar}%")
                  ->orWhere('pfam_codigo', 'like', "%{$buscar}%");
            });
        }
        
        if ($request->filled('estudiante_id')) {
            $query->whereHas('estudiantes', function($q) use ($request) {
                $q->where('colegio_estudiantes.est_codigo', $request->estudiante_id);
            });
        }
        
        $padres = $query->with(['estudiantes' => function($q) {
            $q->with('curso');
        }])->paginate(20);
        $estudiantes = Estudiante::visible()->orderBy('est_nombres')->get();
        
        return view('padres.index', compact('padres', 'estudiantes'));
    }

    public function create()
    {
        $ultimoPadre = PadreFamilia::orderBy('pfam_id', 'desc')->first();
        $siguienteNumero = $ultimoPadre ? $ultimoPadre->pfam_id + 1 : 1;
        $siguienteCodigo = 'Pad' . str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);
        
        return view('padres.create', compact('siguienteCodigo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pfam_ci' => 'required',
            'pfam_nombres' => 'required|max:30',
            'pfam_foto' => 'nullable|image|max:2048'
        ]);

        // Generar código automáticamente
        $ultimoPadre = PadreFamilia::orderBy('pfam_id', 'desc')->first();
        $siguienteNumero = $ultimoPadre ? $ultimoPadre->pfam_id + 1 : 1;
        $pfamCodigo = 'Pad' . str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);
        
        $data = $request->except(['pfam_foto', 'pfam_codigo']);
        $data['pfam_codigo'] = $pfamCodigo;
        
        if ($request->hasFile('pfam_foto')) {
            $data['pfam_foto'] = $request->file('pfam_foto')->store('padres', 'public');
        }

        PadreFamilia::create($data);
        return redirect()->route('padres.index')->with('success', 'Padre de familia creado exitosamente');
    }

    public function edit($id)
    {
        $padre = PadreFamilia::findOrFail($id);
        return view('padres.edit', compact('padre'));
    }

    public function show($id)
    {
        $padre = PadreFamilia::findOrFail($id);
        return response()->json($padre);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pfam_ci' => 'required',
            'pfam_nombres' => 'required|max:30',
            'pfam_foto' => 'nullable|image|max:2048'
        ]);

        $padre = PadreFamilia::findOrFail($id);
        $data = $request->except('pfam_foto');
        
        if ($request->hasFile('pfam_foto')) {
            $data['pfam_foto'] = $request->file('pfam_foto')->store('padres', 'public');
        }

        $padre->update($data);
        return redirect()->route('padres.index')->with('success', 'Padre actualizado exitosamente');
    }

    public function destroy($id)
    {
        PadreFamilia::findOrFail($id)->update(['pfam_estado' => 0]);
        return redirect()->route('padres.index')->with('success', 'Padre eliminado exitosamente');
    }

    public function vincularEstudiante(Request $request, $id)
    {
        $padre = PadreFamilia::findOrFail($id);
        
        $existe = DB::table('rela_estudiantespadres')
            ->where('pfam_id', $padre->pfam_codigo)
            ->where('est_id', $request->est_id)
            ->first();
            
        if ($existe) {
            if ($existe->estpad_estado == 0) {
                DB::table('rela_estudiantespadres')
                    ->where('pfam_id', $padre->pfam_codigo)
                    ->where('est_id', $request->est_id)
                    ->update(['estpad_estado' => 1]);
            }
        } else {
            DB::table('rela_estudiantespadres')->insert([
                'pfam_id' => $padre->pfam_codigo,
                'est_id' => $request->est_id,
                'estpad_fecha' => now(),
                'estpad_estado' => 1
            ]);
        }
        
        return back()->with('success', 'Estudiante vinculado exitosamente');
    }
    
    public function getEstudiantes($id)
    {
        $padre = PadreFamilia::findOrFail($id);
        $estudiantes = DB::table('rela_estudiantespadres')
            ->join('colegio_estudiantes', 'rela_estudiantespadres.est_id', '=', 'colegio_estudiantes.est_codigo')
            ->leftJoin('colegio_cursos', 'colegio_estudiantes.cur_codigo', '=', 'colegio_cursos.cur_codigo')
            ->where('rela_estudiantespadres.pfam_id', $padre->pfam_codigo)
            ->where('rela_estudiantespadres.estpad_estado', 1)
            ->select(
                'colegio_estudiantes.est_codigo as est_id',
                'colegio_estudiantes.est_nombres',
                'colegio_estudiantes.est_apellidos',
                'colegio_cursos.cur_nombre'
            )
            ->get()
            ->map(function($est) {
                return [
                    'est_id' => $est->est_id,
                    'est_nombres' => $est->est_nombres,
                    'est_apellidos' => $est->est_apellidos,
                    'curso' => ['cur_nombre' => $est->cur_nombre]
                ];
            });
        
        return response()->json($estudiantes);
    }
    
    public function addEstudiante(Request $request, $id)
    {
        $padre = PadreFamilia::findOrFail($id);
        
        $existe = DB::table('rela_estudiantespadres')
            ->where('pfam_id', $padre->pfam_codigo)
            ->where('est_id', $request->estudiante_id)
            ->exists();
            
        if ($existe) {
            // Si existe pero está inactivo, reactivarlo
            $registro = DB::table('rela_estudiantespadres')
                ->where('pfam_id', $padre->pfam_codigo)
                ->where('est_id', $request->estudiante_id)
                ->first();
                
            if ($registro && $registro->estpad_estado == 0) {
                DB::table('rela_estudiantespadres')
                    ->where('pfam_id', $padre->pfam_codigo)
                    ->where('est_id', $request->estudiante_id)
                    ->update(['estpad_estado' => 1]);
                return response()->json(['success' => true]);
            }
            
            return response()->json(['error' => 'Ya está vinculado'], 400);
        }
        
        DB::table('rela_estudiantespadres')->insert([
            'pfam_id' => $padre->pfam_codigo,
            'est_id' => $request->estudiante_id,
            'estpad_fecha' => now(),
            'estpad_estado' => 1
        ]);
        
        return response()->json(['success' => true]);
    }
    
    public function desvincularEstudiante($padreId, $estudianteId)
    {
        $padre = PadreFamilia::findOrFail($padreId);
        
        $affected = DB::table('rela_estudiantespadres')
            ->where('pfam_id', $padre->pfam_codigo)
            ->where('est_id', $estudianteId)
            ->update(['estpad_estado' => 0]);
        
        \Log::info('Desvincular', [
            'padre_id' => $padreId,
            'padre_codigo' => $padre->pfam_codigo,
            'estudiante_id' => $estudianteId,
            'affected' => $affected
        ]);
            
        return back()->with('success', 'Estudiante desvinculado exitosamente');
    }
}
