<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\User;
use App\Models\Rol;
use App\Models\Curso;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DocenteController extends Controller
{
    public function index(Request $request)
    {
        $query = Docente::visible();

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function($q) use ($b) {
                $q->where('doc_nombres', 'LIKE', "%$b%")
                  ->orWhere('doc_apellidos', 'LIKE', "%$b%")
                  ->orWhere('doc_ci', 'LIKE', "%$b%")
                  ->orWhere('doc_codigo', 'LIKE', "%$b%");
            });
        }

        if ($request->filled('cur_codigo')) {
            $query->whereHas('cursoMateriaDocentes', fn($q) => $q->where('cur_codigo', $request->cur_codigo));
        }

        if ($request->filled('mat_codigo')) {
            $query->whereHas('cursoMateriaDocentes', fn($q) => $q->where('mat_codigo', $request->mat_codigo));
        }

        $docentes = $query->with(['cursoMateriaDocentes.curso', 'cursoMateriaDocentes.materia'])
            ->orderBy('doc_apellidos')->paginate(20)->appends($request->query());
        $cursos = Curso::visible()->orderBy('cur_nombre')->get();
        $materias = Materia::visible()->orderBy('mat_nombre')->get();
        $usuariosDocentes = User::where('us_entidad_tipo', 'docente')
            ->where('us_visible', 1)
            ->pluck('us_entidad_id')
            ->toArray();

        return view('docentes.index', compact('docentes', 'cursos', 'materias', 'usuariosDocentes'));
    }

    public function create()
    {
        $ultimoDocente = Docente::orderBy('doc_id', 'desc')->first();
        $siguienteNumero = $ultimoDocente ? intval(substr($ultimoDocente->doc_codigo, 3)) + 1 : 1;
        $codigoGenerado = 'DOC' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);

        return view('docentes.create', compact('codigoGenerado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'doc_nombres' => 'required|max:30',
            'doc_apellidos' => 'nullable|max:30',
            'doc_ci' => 'nullable|max:20',
            'doc_foto' => 'nullable|image|max:2048'
        ]);

        $ultimoDocente = Docente::orderBy('doc_id', 'desc')->first();
        $siguienteNumero = $ultimoDocente ? intval(substr($ultimoDocente->doc_codigo, 3)) + 1 : 1;
        $codigoGenerado = 'DOC' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);

        $data = $request->only(['doc_nombres', 'doc_apellidos', 'doc_ci']);
        $data['doc_codigo'] = $codigoGenerado;

        if ($request->hasFile('doc_foto')) {
            $data['doc_foto'] = $request->file('doc_foto')->store('docentes', 'public');
        }

        Docente::create($data);
        return redirect()->route('docentes.index')->with('success', 'Docente creado exitosamente');
    }

    public function show($id)
    {
        $docente = Docente::with(['cursoMateriaDocentes.curso', 'cursoMateriaDocentes.materia'])->findOrFail($id);
        return view('docentes.show', compact('docente'));
    }

    public function edit($id)
    {
        $docente = Docente::findOrFail($id);
        return view('docentes.edit', compact('docente'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'doc_nombres' => 'required|max:30',
            'doc_apellidos' => 'nullable|max:30',
            'doc_ci' => 'nullable|max:20',
            'doc_foto' => 'nullable|image|max:2048'
        ]);

        $docente = Docente::findOrFail($id);
        $data = $request->only(['doc_nombres', 'doc_apellidos', 'doc_ci']);

        if ($request->hasFile('doc_foto')) {
            if ($docente->doc_foto) {
                Storage::disk('public')->delete($docente->doc_foto);
            }
            $data['doc_foto'] = $request->file('doc_foto')->store('docentes', 'public');
        }

        if ($request->has('eliminar_foto') && $request->eliminar_foto) {
            if ($docente->doc_foto) {
                Storage::disk('public')->delete($docente->doc_foto);
            }
            $data['doc_foto'] = null;
        }

        $docente->update($data);
        return redirect()->route('docentes.index')->with('success', 'Docente actualizado exitosamente');
    }

    public function destroy($id)
    {
        $docente = Docente::findOrFail($id);
        $docente->update(['doc_visible' => 0]);
        return redirect()->route('docentes.index')->with('success', 'Docente eliminado exitosamente');
    }

    public function crearUsuario(Request $request, $id)
    {
        $docente = Docente::findOrFail($id);

        $existe = User::where('us_entidad_tipo', 'docente')
            ->where('us_entidad_id', $docente->doc_codigo)
            ->where('us_visible', 1)
            ->first();

        if ($existe) {
            return back()->with('error', 'Este docente ya tiene un usuario asignado: ' . $existe->us_user);
        }

        $request->validate(['password' => 'required|min:6']);

        $rolDocente = Rol::where('rol_nombre', 'Docente')->first();

        User::create([
            'us_codigo' => $docente->doc_codigo,
            'rol_id' => $rolDocente ? $rolDocente->rol_id : 2,
            'us_ci' => $docente->doc_ci ?? '',
            'us_nombres' => $docente->doc_nombres,
            'us_apellidos' => $docente->doc_apellidos ?? '',
            'us_user' => strtolower(str_replace(' ', '', $docente->doc_apellidos ?? $docente->doc_nombres)) . $docente->doc_id,
            'us_pass' => Hash::make($request->password),
            'us_visible' => 1,
            'us_entidad_tipo' => 'docente',
            'us_entidad_id' => $docente->doc_codigo,
        ]);

        return back()->with('success', 'Usuario creado exitosamente para ' . $docente->doc_nombres);
    }
}
