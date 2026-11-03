<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Services\ActivacionGestionService;
use Illuminate\Http\Request;

/**
 * Gestiones escolares.
 *
 * La gestión VIGENTE (ges_estado = 1) ya no se elige en el formulario: es la
 * que usa todo el sistema (caja, listas, notas) y cambiarla es pasar de año.
 * Se hace con el asistente de activación, que además mueve a los estudiantes
 * de curso. En el formulario sí se abre la inscripción de la gestión siguiente.
 */
class GestionController extends Controller
{
    private function reglas($id = null): array
    {
        return [
            'ges_anio'                => 'required|digits:4|unique:colegio_gestiones,ges_anio' . ($id ? ",{$id},ges_id" : ''),
            'ges_nombre'              => 'required|max:80',
            'ges_abreviado'           => 'nullable|max:20',
            'ges_inscripcion_abierta' => 'nullable|boolean',
            'ges_preinsc_desde'       => 'nullable|date',
            'ges_preinsc_hasta'       => 'nullable|date|after_or_equal:ges_preinsc_desde',
            'ges_fecha_inicio'        => 'nullable|date',
            'ges_fecha_fin'           => 'nullable|date|after_or_equal:ges_fecha_inicio',
            'ges_monto_reserva'       => 'nullable|numeric|min:0',
        ];
    }

    public function index()
    {
        $gestiones = Gestion::orderBy('ges_anio', 'desc')->get();
        return view('gestiones.index', compact('gestiones'));
    }

    public function create()
    {
        return view('gestiones.create');
    }

    /**
     * Quita del formulario lo que el rol no puede decidir: abrir la
     * pre-inscripción y sus fechas (gestion.preinscripcion) y el monto de la
     * reserva (gestion.montos). Lo demás de la gestión lo controla el módulo.
     */
    private function segunCapacidades(array $data, Request $request, ?Gestion $actual): array
    {
        $user = auth()->user();
        if ($user->tieneCapacidad('gestion.preinscripcion')) {
            $data['ges_inscripcion_abierta'] = $request->boolean('ges_inscripcion_abierta') ? 1 : 0;
        } else {
            unset($data['ges_preinsc_desde'], $data['ges_preinsc_hasta']);
            $data['ges_inscripcion_abierta'] = $actual ? $actual->ges_inscripcion_abierta : 0;
        }
        // Vacío = se usa el monto por defecto (Parámetros) o el de la tarifa del curso
        if ($user->tieneCapacidad('gestion.montos')) {
            $data['ges_monto_reserva'] = ($data['ges_monto_reserva'] ?? '') === '' ? null : $data['ges_monto_reserva'];
        } else {
            unset($data['ges_monto_reserva']);
        }
        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->segunCapacidades($request->validate($this->reglas()), $request, null);
        $data['ges_estado'] = 0;
        Gestion::create($data);
        return redirect()->route('gestiones.index')->with('success', 'Gestión creada');
    }

    public function edit($id)
    {
        $gestion = Gestion::findOrFail($id);
        return view('gestiones.edit', compact('gestion'));
    }

    public function update(Request $request, $id)
    {
        $gestion = Gestion::findOrFail($id);
        $data = $request->validate($this->reglas($id));
        // El año de la vigente no se cambia: todo el sistema trabaja sobre él.
        if ((int) $gestion->ges_estado === 1) unset($data['ges_anio']);
        $gestion->update($this->segunCapacidades($data, $request, $gestion));
        \App\Support\GestionActual::olvidar();
        return redirect()->route('gestiones.index')->with('success', 'Gestión actualizada');
    }

    public function destroy($id)
    {
        $gestion = Gestion::findOrFail($id);
        // Desactivar la vigente dejaría al sistema sin gestión: hay que activar otra.
        if ((int) $gestion->ges_estado === 1) {
            return redirect()->route('gestiones.index')->with('error', 'Es la gestión vigente: active otra gestión en lugar de desactivar esta.');
        }
        $gestion->update(['ges_inscripcion_abierta' => 0]);
        return redirect()->route('gestiones.index')->with('success', 'Inscripción de la gestión cerrada');
    }

    /** Asistente de paso de año: lista de control antes de activar. */
    public function asistente($id)
    {
        $gestion = Gestion::findOrFail($id);
        $check = (new ActivacionGestionService())->checklist((int) $gestion->ges_anio);
        return view('gestiones.asistente', compact('gestion', 'check'));
    }

    /** Ejecuta el paso de año (capacidad "gestion.activar"). */
    public function activar(Request $request, $id)
    {
        if (!auth()->user()->tieneCapacidad('gestion.activar')) {
            abort(403, 'Su rol no tiene la capacidad «Activar la gestión siguiente».');
        }
        $gestion = Gestion::findOrFail($id);
        $request->validate(['confirmacion' => 'required|in:' . $gestion->ges_anio], [
            'confirmacion.in' => 'Escriba el año ' . $gestion->ges_anio . ' para confirmar.',
            'confirmacion.required' => 'Escriba el año ' . $gestion->ges_anio . ' para confirmar.',
        ]);

        try {
            $r = (new ActivacionGestionService())->ejecutar(
                (int) $gestion->ges_anio, $request->boolean('ocultar_no_reinscritos'), auth()->user()->us_id
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'No se activó la gestión (no se cambió nada): ' . $e->getMessage());
        }

        return redirect()->route('gestiones.index')->with('success',
            "Gestión {$gestion->ges_anio} activada. Alumnos nuevos creados: {$r['nuevos_creados']}; " .
            "estudiantes movidos a su curso: {$r['movidos']}; no reinscritos ocultados: {$r['ocultados']}; " .
            "periodos copiados: {$r['periodos']}; dimensiones copiadas: {$r['dimensiones']}; listas generadas: {$r['listas']}.");
    }
}
