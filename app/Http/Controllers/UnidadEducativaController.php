<?php

namespace App\Http\Controllers;

use App\Models\SistemaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnidadEducativaController extends Controller
{
    public function edit()
    {
        $config = SistemaConfiguracion::actual();
        if (!$config) {
            $config = SistemaConfiguracion::create(['config_denominacion' => 'UNIDAD EDUCATIVA']);
        }
        return view('unidad_educativa.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'config_denominacion' => 'nullable|max:200',
            'config_nombre_ue'    => 'required|max:200',
            'config_direccion'    => 'nullable|max:255',
            'config_telefono'     => 'nullable|max:50',
            'config_ciudad'       => 'nullable|max:100',
            'config_email'        => 'nullable|email|max:100',
            'config_logo'         => 'nullable|image|max:2048',
        ]);

        $config = SistemaConfiguracion::actual() ?? new SistemaConfiguracion;

        if ($request->hasFile('config_logo')) {
            if ($config->config_logo && Storage::disk('public')->exists($config->config_logo)) {
                Storage::disk('public')->delete($config->config_logo);
            }
            $data['config_logo'] = $request->file('config_logo')->store('config', 'public');
        }

        $config->fill($data);
        $config->save();

        return redirect()->route('unidad-educativa.edit')->with('success', 'Datos de la institución actualizados');
    }
}
