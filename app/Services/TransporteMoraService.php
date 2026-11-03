<?php

namespace App\Services;

use App\Models\EstudianteRuta;
use App\Models\PagoTransporte;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mora del transporte escolar. El servicio se cobra de febrero a noviembre y
 * cada cuota cubre un rango de fechas; un mes está pagado si algún pago no
 * anulado lo cubre. Un mes anterior al de corte, sin pagar y sin suspensión
 * del servicio, está en mora (la misma regla que ya usaba la pantalla de cobro).
 */
class TransporteMoraService
{
    public const PRIMER_MES = 2;
    public const ULTIMO_MES = 11;
    public const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /** Meses (2–11) cubiertos por los pagos dados. */
    public function mesesPagados(iterable $pagos): array
    {
        $meses = [];
        foreach ($pagos as $pago) {
            $actual = Carbon::parse($pago->tpago_fecha_inicio)->startOfMonth();
            $fin = Carbon::parse($pago->tpago_fecha_fin)->startOfMonth();
            while ($actual < $fin) {
                $m = (int) $actual->format('n');
                if ($m >= self::PRIMER_MES && $m <= self::ULTIMO_MES) $meses[$m] = true;
                $actual->addMonth();
            }
        }
        $meses = array_keys($meses);
        sort($meses);
        return $meses;
    }

    /** Meses en mora de un estudiante con ruta (los anteriores a $mesCorte). */
    public function mesesEnMora(array $pagados, int $mesCorte, bool $suspendido, ?int $suspendidoDesde): array
    {
        $mora = [];
        for ($m = self::PRIMER_MES; $m <= self::ULTIMO_MES && $m < $mesCorte; $m++) {
            if (in_array($m, $pagados, true)) continue;
            if ($suspendido && $suspendidoDesde && $m >= $suspendidoDesde) continue;
            $mora[] = $m;
        }
        return $mora;
    }

    /**
     * Estudiantes con ruta activa y al menos un mes en mora.
     * Filtros opcionales: curso, ruta, vehículo (bus).
     */
    public function calcular(int $gestion, int $mesCorte, array $filtros = []): Collection
    {
        $q = EstudianteRuta::activo()
            ->with(['estudiante.curso', 'ruta.asignaciones' => fn($a) => $a->where('asig_estado', 1)->with(['vehiculo', 'chofer'])])
            ->whereHas('estudiante', function ($e) use ($filtros) {
                $e->where('est_visible', 1);
                if (!empty($filtros['cur_codigo'])) $e->where('cur_codigo', $filtros['cur_codigo']);
            });
        if (!empty($filtros['ruta_codigo'])) $q->where('ruta_codigo', $filtros['ruta_codigo']);
        if (!empty($filtros['veh_codigo'])) {
            $q->whereIn('ruta_codigo', \App\Models\AsignacionTransporte::activo()->where('veh_codigo', $filtros['veh_codigo'])->pluck('ruta_codigo'));
        }
        // Un estudiante con dos rutas activas (dato duplicado) cuenta una vez,
        // como en la pantalla de cobro (Estudiante::rutaTransporte toma una).
        $rutas = $q->orderBy('ter_id')->get()->unique('est_codigo')->values();

        $pagos = PagoTransporte::whereIn('est_codigo', $rutas->pluck('est_codigo'))
            ->whereYear('tpago_fecha_pago', $gestion)
            ->where('tpago_estado', '!=', 'cancelado')
            ->get()->groupBy('est_codigo');

        return $rutas->map(function ($r) use ($pagos, $mesCorte) {
            $propios = $pagos->get($r->est_codigo, collect());
            $mora = $this->mesesEnMora($this->mesesPagados($propios), $mesCorte, (bool) $r->ter_suspendido, $r->ter_suspendido_desde);
            if (!$mora) return null;
            $asig = optional($r->ruta)->asignaciones ? $r->ruta->asignaciones->first() : null;
            $ultima = $propios->sortByDesc('tpago_fecha_inicio')->first();
            return (object) [
                'est_codigo'   => $r->est_codigo,
                'estudiante'   => trim(optional($r->estudiante)->est_apellidos . ' ' . optional($r->estudiante)->est_nombres),
                'curso'        => optional(optional($r->estudiante)->curso)->cur_nombre ?? '—',
                'cur_orden'    => optional(optional($r->estudiante)->curso)->cur_orden ?? 999,
                'ruta'         => optional($r->ruta)->ruta_nombre ?? '—',
                'bus'          => $asig && $asig->vehiculo ? ($asig->vehiculo->veh_numero_bus ?: $asig->vehiculo->veh_placa) : '—',
                'chofer'       => $asig && $asig->chofer ? trim($asig->chofer->chof_nombres . ' ' . $asig->chofer->chof_apellidos) : '—',
                'meses'        => $mora,
                'meses_texto'  => implode(', ', array_map(fn($m) => mb_substr(self::MESES[$m], 0, 3), $mora)),
                // Sin tarifa fija por ruta: la deuda se estima con la última cuota cobrada.
                'cuota_ref'    => $ultima ? (float) $ultima->tpago_monto : null,
                'deuda_estimada' => $ultima ? round(count($mora) * (float) $ultima->tpago_monto, 2) : null,
            ];
        })->filter()->sortBy([['cur_orden', 'asc'], ['curso', 'asc'], ['estudiante', 'asc']])->values();
    }
}
