<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Tarifa;
use App\Services\CursoProgresion;
use App\Support\GestionActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tarifas por gestión y curso siguiente de cada curso.
 *
 * Las dos cosas que necesita la pre-inscripción: cuánto cuesta el año en el
 * curso destino (antes se escribía a mano en cada inscripción) y a qué curso
 * pasa cada estudiante si aprueba (antes no existía).
 */
class TarifaController extends Controller
{
    public function index(Request $request)
    {
        $gestion = (int) ($request->input('gestion') ?: (GestionActual::inscripcionAbierta() ?? gestion_actual() + 1));
        $cursos = Curso::visible()->ordenado()->get();
        $tarifas = Tarifa::where('ges_anio', $gestion)->whereNotNull('cur_codigo')->pluck('tar_monto_anual', 'cur_codigo');
        $reservas = Tarifa::where('ges_anio', $gestion)->whereNotNull('cur_codigo')->pluck('tar_monto_reserva', 'cur_codigo');
        [$reservaGeneral, $origenGeneral] = Tarifa::reservaPara($gestion, null);
        $puedeMontos = auth()->user()->tieneCapacidad('gestion.montos');
        $sugerencia= (new CursoProgresion())->sugerir();

        // Referencia: el monto anual más cobrado en cada curso en la gestión vigente.
        $referencia = Inscripcion::where('insc_gestion', gestion_actual())->whereIn('insc_estado', [1, 2])
            ->get(['cur_codigo', 'insc_monto_total'])->groupBy('cur_codigo')
            ->map(fn($g) => $g->countBy(fn($i) => (string) round($i->insc_monto_total, 2))->sortDesc()->keys()->first());

        return view('tarifas.index', compact('gestion', 'cursos', 'tarifas', 'sugerencia', 'referencia', 'reservas', 'reservaGeneral', 'origenGeneral', 'puedeMontos'));
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'gestion'      => 'required|digits:4',
            'tarifa.*'     => 'nullable|numeric|min:0',
            'reserva.*'    => 'nullable|numeric|min:0',
            'siguiente.*'  => 'nullable|string|max:20',
        ]);
        $gestion = (int) $request->gestion;
        $validos = Curso::pluck('cur_codigo')->flip();

        $puedeMontos = auth()->user()->tieneCapacidad('gestion.montos');

        DB::transaction(function () use ($request, $gestion, $validos, $puedeMontos) {
            foreach ((array) $request->input('siguiente', []) as $cur => $sig) {
                // Un código sólo numérico ('5500504') llega como clave entera; sin
                // pasarlo a texto MySQL compara cur_codigo como número y falla.
                $cur = (string) $cur;
                if (!isset($validos[$cur])) continue;
                $info = $request->input("info.{$cur}", []);
                Curso::where('cur_codigo', $cur)->update([
                    'cur_siguiente' => ($sig && $sig !== 'EGRESA' && isset($validos[$sig])) ? (string) $sig : null,
                    'cur_grado'     => isset($info['grado']) && $info['grado'] !== '' ? (int) $info['grado'] : null,
                    'cur_paralelo'  => isset($info['paralelo']) && $info['paralelo'] !== '' ? strtoupper(substr($info['paralelo'], 0, 1)) : null,
                ]);
            }
            $reservas = (array) $request->input('reserva', []);
            foreach ((array) $request->input('tarifa', []) as $cur => $monto) {
                $cur = (string) $cur;
                if (!isset($validos[$cur])) continue;
                $fila = Tarifa::where('ges_anio', $gestion)->where('cur_codigo', $cur)->first();
                $anual = ($monto === null || $monto === '') ? null : $monto;
                // Sin la capacidad gestion.montos la reserva del curso no se toca
                $reserva = $puedeMontos
                    ? (($reservas[$cur] ?? '') === '' ? null : $reservas[$cur])
                    : optional($fila)->tar_monto_reserva;
                if ($anual === null && $reserva === null) {
                    if ($fila) $fila->delete();
                    continue;
                }
                Tarifa::updateOrCreate(['ges_anio' => $gestion, 'cur_codigo' => $cur],
                                       ['tar_monto_anual' => $anual, 'tar_monto_reserva' => $reserva]);
            }
        });

        return redirect()->route('tarifas.index', ['gestion' => $gestion])->with('success', 'Tarifas y cursos siguientes guardados.');
    }
}
