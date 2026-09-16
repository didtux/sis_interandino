<?php

namespace App\Http\Controllers;

use App\Models\HorarioEspecial;
use App\Models\HorarioEspecialDetalle;
use App\Services\HorarioEspecialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ABM de horarios especiales por rango de fechas.
 *
 * Cubre dos necesidades que la configuración normal no puede expresar, porque
 * ésta tiene una sola fila por categoría y turno, sin noción de fecha:
 *
 *   • Horario de invierno — durante unos meses se entra más tarde, y no igual
 *     en todos los niveles (INICIAL 8:45, PRIMARIA/SECUNDARIA 8:15).
 *   • Receso sin clases — vacaciones: esos días no son hábiles, no generan
 *     faltas y no pueden producir atrasos.
 *
 * Los atrasos se derivan al leer (ver AsistenciaResumenService), así que cargar
 * un rango acá corrige también los boletines ya emitidos, sin recalcular nada
 * ni tocar una sola marcación.
 */
class HorarioEspecialController extends Controller
{
    public function index(Request $request)
    {
        $gestion = (int) ($request->input('gestion') ?: $this->gestionPorDefecto());

        $horarios = HorarioEspecial::with('detalles')
            ->gestion($gestion)
            ->orderBy('esp_estado', 'desc')
            ->orderBy('esp_fecha_inicio')
            ->get();

        // Todas las categorías configuradas, de todos los turnos: un mismo rango
        // puede afectar la mañana y la tarde, así que se muestran juntas.
        $configs = DB::table('asistencia_configuracion')
            ->where('config_estado', 1)
            ->orderByRaw("FIELD(config_turno, 'Mañana', 'Tarde', 'Noche') , config_turno")
            ->orderBy('config_categoria')
            ->get();

        $categorias = [];
        foreach ($configs as $c) {
            $categorias[] = [
                'categoria'  => $c->config_categoria,
                'turno'      => $c->config_turno,
                'entrada'    => substr((string) $c->hora_entrada, 0, 5),
                'tolerancia' => substr((string) $c->tolerancia_atraso, 0, 5),
                'salida'     => substr((string) $c->hora_salida, 0, 5),
            ];
        }

        $gestiones = DB::table('notas_config_periodos')
            ->distinct()->orderBy('periodo_gestion', 'desc')
            ->pluck('periodo_gestion')->map(fn($g) => (int) $g)->all();
        if (!in_array($gestion, $gestiones, true)) array_unshift($gestiones, $gestion);

        // Solapamientos, para avisarlos en el listado.
        $solapes = $this->solapamientos($horarios);

        return view('asistencia-config.horarios-especiales', compact(
            'horarios', 'gestion', 'gestiones', 'categorias', 'solapes'
        ));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        DB::beginTransaction();
        try {
            $horario = HorarioEspecial::create([
                'esp_nombre'      => $datos['esp_nombre'],
                'esp_gestion'     => $datos['esp_gestion'],
                'esp_fecha_inicio' => $datos['esp_fecha_inicio'],
                'esp_fecha_fin'   => $datos['esp_fecha_fin'],
                'esp_tipo'        => $datos['esp_tipo'],
                'esp_observacion' => $request->input('esp_observacion'),
                'esp_estado'      => 1,
                'esp_creado_por'  => auth()->user()->us_id ?? null,
            ]);

            $this->guardarDetalles($horario, $request);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'No se pudo guardar: ' . $e->getMessage());
        }

        return redirect()
            ->route('asistencia-config.horarios-especiales', ['gestion' => $datos['esp_gestion']])
            ->with('success', 'Horario especial registrado.');
    }

    public function update(Request $request, $id)
    {
        $horario = HorarioEspecial::findOrFail($id);
        $datos = $this->validar($request, (int) $id);

        DB::beginTransaction();
        try {
            $horario->update([
                'esp_nombre'      => $datos['esp_nombre'],
                'esp_gestion'     => $datos['esp_gestion'],
                'esp_fecha_inicio' => $datos['esp_fecha_inicio'],
                'esp_fecha_fin'   => $datos['esp_fecha_fin'],
                'esp_tipo'        => $datos['esp_tipo'],
                'esp_observacion' => $request->input('esp_observacion'),
            ]);

            // El detalle se reescribe completo: es más simple y no deja categorías huérfanas.
            HorarioEspecialDetalle::where('esp_id', $horario->esp_id)->delete();
            $this->guardarDetalles($horario, $request);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'No se pudo actualizar: ' . $e->getMessage());
        }

        return redirect()
            ->route('asistencia-config.horarios-especiales', ['gestion' => $datos['esp_gestion']])
            ->with('success', 'Horario especial actualizado.');
    }

    /**
     * Activa o desactiva el rango. Desactivar equivale a que nunca existió:
     * los reportes vuelven de inmediato al horario normal.
     */
    public function toggle($id)
    {
        $horario = HorarioEspecial::findOrFail($id);
        $horario->update(['esp_estado' => $horario->esp_estado ? 0 : 1]);

        return back()->with('success', $horario->esp_estado
            ? 'Horario especial activado.'
            : 'Horario especial desactivado: los reportes vuelven al horario normal.');
    }

    public function destroy($id)
    {
        $horario = HorarioEspecial::findOrFail($id);
        $gestion = $horario->esp_gestion;
        $horario->delete();   // el detalle cae por ON DELETE CASCADE

        return redirect()
            ->route('asistencia-config.horarios-especiales', ['gestion' => $gestion])
            ->with('success', 'Horario especial eliminado.');
    }

    /**
     * Vista previa de los tramos que resultan de lo cargado, para que el usuario
     * confirme qué horario va a regir cada día antes de fiarse de los reportes.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'desde'     => 'required|date',
            'hasta'     => 'required|date|after_or_equal:desde',
            'categoria' => 'required|string',
            'turno'     => 'required|string',
        ]);

        $svc = new HorarioEspecialService();
        $tramos = $svc->tramos(
            Carbon::parse($request->desde)->format('Y-m-d'),
            Carbon::parse($request->hasta)->format('Y-m-d'),
            $request->categoria,
            $request->turno
        );

        foreach ($tramos as &$t) {
            $t['dias'] = Carbon::parse($t['desde'])->diffInDays(Carbon::parse($t['hasta'])) + 1;
            $t['habiles'] = $this->diasHabiles($t['desde'], $t['hasta']);
        }

        return response()->json(['ok' => true, 'tramos' => $tramos]);
    }

    // ────────────────────────────────────────────────────────────────────

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        $datos = $request->validate([
            'esp_nombre'       => 'required|string|max:120',
            'esp_gestion'      => 'required|integer|min:2000|max:2100',
            'esp_fecha_inicio' => 'required|date',
            'esp_fecha_fin'    => 'required|date|after_or_equal:esp_fecha_inicio',
            'esp_tipo'         => 'required|in:1,2',
        ], [], [
            'esp_fecha_fin' => 'fecha de fin',
        ]);

        $datos['esp_tipo'] = (int) $datos['esp_tipo'];

        // Un horario especial sin categorías no cambia nada; un receso no las necesita.
        if ($datos['esp_tipo'] === HorarioEspecial::TIPO_HORARIO
            && empty(array_filter((array) $request->input('categorias', [])))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'categorias' => 'Seleccione al menos una categoría para el horario especial.',
            ]);
        }

        return $datos;
    }

    private function guardarDetalles(HorarioEspecial $horario, Request $request): void
    {
        if ((int) $horario->esp_tipo !== HorarioEspecial::TIPO_HORARIO) {
            return;   // un receso no lleva horas
        }

        $horas  = (array) $request->input('horas', []);
        $turnos = (array) $request->input('turnos', []);

        foreach (array_filter((array) $request->input('categorias', [])) as $indice => $categoria) {
            $h = $horas[$indice] ?? [];

            HorarioEspecialDetalle::create([
                'esp_id'                => $horario->esp_id,
                'det_categoria'         => $categoria,
                'det_turno'             => $turnos[$indice] ?? 'Mañana',
                'det_hora_entrada'      => $this->hora($h['entrada'] ?? null, '08:00'),
                'det_tolerancia_atraso' => $this->hora($h['tolerancia'] ?? null, $h['entrada'] ?? '08:00'),
                'det_hora_salida'       => $this->hora($h['salida'] ?? null, '12:30'),
            ]);
        }
    }

    /** Normaliza 'HH:MM' del formulario a 'HH:MM:SS'. */
    private function hora($valor, $fallback): string
    {
        $v = trim((string) ($valor ?: $fallback));
        if ($v === '') $v = '08:00';
        return strlen($v) === 5 ? $v . ':00' : substr($v, 0, 8);
    }

    /**
     * Qué pasa en los días que un rango comparte con otro, dicho en concreto:
     * "Del 06/07 al 17/07 no hay clases por «Vacaciones de invierno»".
     *
     * No hay nada que configurar: la regla la aplica HorarioEspecialService.
     * Acá sólo se muestra el resultado, para que no haya que deducirlo.
     *
     * @return array<int, array<int, array{texto:string,alerta:bool}>>
     */
    private function solapamientos($horarios): array
    {
        $avisos = [];
        $activos = $horarios->where('esp_estado', 1)->values();

        // Claves categoría|turno de cada rango; null = alcanza a todos (receso).
        $alcance = fn($h) => (int) $h->esp_tipo === HorarioEspecial::TIPO_RECESO
            ? null
            : $h->detalles->map(fn($d) => $d->det_categoria . '|' . $d->det_turno)->all();

        foreach ($activos as $a) {
            foreach ($activos as $b) {
                if ($a->esp_id === $b->esp_id) continue;

                $desde = $a->esp_fecha_inicio->max($b->esp_fecha_inicio);
                $hasta = $a->esp_fecha_fin->min($b->esp_fecha_fin);
                if ($desde->gt($hasta)) continue;   // no comparten ningún día

                // Dos horarios especiales sólo compiten si comparten alguna categoría.
                $ca = $alcance($a); $cb = $alcance($b);
                if ($ca !== null && $cb !== null && empty(array_intersect($ca, $cb))) continue;

                // ¿Quién manda esos días? El receso; entre iguales, el último cargado.
                // Mismo criterio que HorarioEspecialService::rangoEn().
                $mandaB = $a->es_receso === $b->es_receso
                    ? $b->esp_id > $a->esp_id
                    : $b->es_receso;
                if (!$mandaB) continue;   // el aviso se muestra en la fila que pierde

                $tramo = 'Del ' . $desde->format('d/m') . ' al ' . $hasta->format('d/m');

                $avisos[$a->esp_id][] = $b->es_receso
                    ? ['texto' => "{$tramo} no hay clases por «{$b->esp_nombre}»", 'alerta' => false]
                    : ['texto' => "{$tramo} manda «{$b->esp_nombre}», que se cargó después", 'alerta' => true];
            }
        }
        return $avisos;
    }


    private function diasHabiles(string $desde, string $hasta): int
    {
        $cur = Carbon::parse($desde); $end = Carbon::parse($hasta); $n = 0;
        while ($cur->lte($end)) { if ($cur->isWeekday()) $n++; $cur->addDay(); }
        return $n;
    }

    private function gestionPorDefecto(): int
    {
        return (int) (DB::table('notas_config_periodos')->max('periodo_gestion') ?: date('Y'));
    }
}
