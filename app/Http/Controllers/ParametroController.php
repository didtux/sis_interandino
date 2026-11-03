<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Parámetros del colegio (sistema_parametros): decisiones que el sistema no
 * debe fijar en el código, como cuántas materias reprobadas hacen repetir el
 * año o cuánto de la reserva se retiene al devolverla.
 */
class ParametroController extends Controller
{
    /** Valores permitidos de los parámetros con opciones cerradas. */
    private const OPCIONES = [
        'preinsc_mora'  => ['AVISO' => 'Avisar y permitir', 'BLOQUEO' => 'Bloquear la pre-inscripción'],
        'bloqueo_mora_modo' => ['AUTO' => 'Automático', 'MANUAL' => 'Sólo manual', 'AMBOS' => 'Automático y manual'],
        'bloqueo_mora_activo' => ['0' => 'No', '1' => 'Sí'],
    ];

    /** Parámetros que además piden la capacidad gestion.montos. */
    private const DE_MONTOS = ['preinsc_monto_reserva'];

    public function index()
    {
        $parametros = DB::table('sistema_parametros')->orderBy('param_clave')->get();
        $opciones = self::OPCIONES;
        $bloqueados = auth()->user()->tieneCapacidad('gestion.montos') ? [] : self::DE_MONTOS;
        return view('parametros.index', compact('parametros', 'opciones', 'bloqueados'));
    }

    public function guardar(Request $request)
    {
        $existentes = DB::table('sistema_parametros')->pluck('param_clave')->flip();
        foreach ((array) $request->input('param', []) as $clave => $valor) {
            if (!isset($existentes[$clave])) continue;
            if (in_array($clave, self::DE_MONTOS, true) && !auth()->user()->tieneCapacidad('gestion.montos')) continue;
            if (isset(self::OPCIONES[$clave]) && !array_key_exists((string) $valor, self::OPCIONES[$clave])) continue;
            DB::table('sistema_parametros')->where('param_clave', $clave)
                ->update(['param_valor' => mb_substr(trim((string) $valor), 0, 255)]);
        }
        parametro('', null, true);
        return redirect()->route('parametros.index')->with('success', 'Parámetros guardados.');
    }
}
