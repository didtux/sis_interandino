<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChoferController extends Controller
{
    public function index()
    {
        $choferes = Chofer::orderBy('chof_fecha_registro', 'desc')->get();
        $usuariosChoferes = User::where('us_entidad_tipo', 'chofer')
            ->where('us_visible', 1)
            ->pluck('us_entidad_id')
            ->toArray();
        return view('transporte.choferes.index', compact('choferes', 'usuariosChoferes'));
    }

    public function create()
    {
        return view('transporte.choferes.create');
    }

    /**
     * Choferes y vehículos en una sola pantalla: qué chofer maneja qué bus en
     * qué ruta, cuántos estudiantes lleva y si tiene usuario para el portal.
     */
    public function flota()
    {
        return view('transporte.flota.index', $this->datosFlota());
    }

    public function flotaPdf()
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('transporte.flota.pdf', $this->datosFlota())
            ->setPaper('letter', 'landscape')
            ->stream('choferes-y-buses.pdf');
    }

    private function datosFlota(): array
    {
        $asignaciones = \App\Models\AsignacionTransporte::activo()->with(['chofer', 'vehiculo', 'ruta'])->get()
            ->sortBy(fn($a) => (string) optional($a->vehiculo)->veh_numero_bus, SORT_NATURAL)->values();
        $estudiantesPorRuta = \App\Models\EstudianteRuta::activo()->selectRaw('ruta_codigo, COUNT(*) n')->groupBy('ruta_codigo')->pluck('n', 'ruta_codigo');
        $conUsuario = User::where('us_entidad_tipo', 'chofer')->where('us_visible', 1)->pluck('us_user', 'us_entidad_id');
        $choferesSinBus = Chofer::activo()->whereNotIn('chof_codigo', $asignaciones->pluck('chof_codigo'))->orderBy('chof_apellidos')->get();
        $busesSinChofer = \App\Models\Vehiculo::activo()->whereNotIn('veh_codigo', $asignaciones->pluck('veh_codigo'))->get();
        return compact('asignaciones', 'estudiantesPorRuta', 'conUsuario', 'choferesSinBus', 'busesSinChofer');
    }

    public function store(Request $request)
    {
        $request->validate([
            'chof_nombres' => 'required',
            'chof_apellidos' => 'required',
            'chof_ci' => 'required|unique:transporte_choferes,chof_ci',
            'chof_licencia' => 'required',
            'chof_foto' => 'nullable|image|max:2048'
        ]);

        $data = [
            'chof_codigo' => 'CHOF' . time(),
            'chof_nombres' => $request->chof_nombres,
            'chof_apellidos' => $request->chof_apellidos,
            'chof_ci' => $request->chof_ci,
            'chof_licencia' => $request->chof_licencia,
            'chof_telefono' => $request->chof_telefono,
            'chof_direccion' => $request->chof_direccion,
            'chof_fecha_nacimiento' => $request->chof_fecha_nacimiento,
            'chof_usuario_registro' => auth()->user()->us_codigo
        ];

        if ($request->hasFile('chof_foto')) {
            $data['chof_foto'] = $request->file('chof_foto')->store('choferes', 'public');
        }

        Chofer::create($data);

        return redirect()->route('choferes.index')->with('success', 'Chofer registrado');
    }

    public function edit($id)
    {
        $chofer = Chofer::findOrFail($id);
        return view('transporte.choferes.edit', compact('chofer'));
    }

    public function update(Request $request, $id)
    {
        $chofer = Chofer::findOrFail($id);
        
        $request->validate([
            'chof_nombres' => 'required',
            'chof_apellidos' => 'required',
            'chof_ci' => 'required|unique:transporte_choferes,chof_ci,' . $id . ',chof_id',
            'chof_licencia' => 'required',
            'chof_foto' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('chof_foto');
        
        if ($request->hasFile('chof_foto')) {
            $data['chof_foto'] = $request->file('chof_foto')->store('choferes', 'public');
        }

        $chofer->update($data);
        return redirect()->route('choferes.index')->with('success', 'Chofer actualizado');
    }

    public function destroy($id)
    {
        $chofer = Chofer::findOrFail($id);
        $chofer->update(['chof_estado' => 0]);
        return redirect()->route('choferes.index')->with('success', 'Chofer eliminado');
    }

    public function crearUsuario(Request $request, $id)
    {
        $chofer = Chofer::findOrFail($id);

        $existe = User::where('us_entidad_tipo', 'chofer')
            ->where('us_entidad_id', $chofer->chof_codigo)
            ->where('us_visible', 1)
            ->first();

        if ($existe) {
            return back()->with('error', 'Este chofer ya tiene un usuario asignado: ' . $existe->us_user);
        }

        $request->validate(['password' => 'required|min:6']);

        $rolChofer = Rol::where('rol_nombre', 'Chofer')->first();

        User::create([
            'us_codigo' => $chofer->chof_codigo,
            'rol_id' => $rolChofer ? $rolChofer->rol_id : 4,
            'us_ci' => $chofer->chof_ci,
            'us_nombres' => $chofer->chof_nombres,
            'us_apellidos' => $chofer->chof_apellidos,
            'us_user' => strtolower(str_replace(' ', '', $chofer->chof_apellidos)) . $chofer->chof_id,
            'us_pass' => Hash::make($request->password),
            'us_visible' => 1,
            'us_entidad_tipo' => 'chofer',
            'us_entidad_id' => $chofer->chof_codigo,
        ]);

        return back()->with('success', 'Usuario creado exitosamente para ' . $chofer->chof_nombres . ' ' . $chofer->chof_apellidos);
    }
}
