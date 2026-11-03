<?php

namespace App\Services;

use App\Models\Descuento;
use App\Models\Estudiante;
use App\Models\EstudianteGestion;
use App\Models\EstudianteObservado;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\PadreFamilia;
use App\Models\Preinscripcion;
use App\Models\PreinscripcionPago;
use App\Models\Tarifa;
use Illuminate\Support\Facades\DB;

/**
 * Pre-inscripción para la gestión siguiente: reserva de cupo con recibo propio
 * (serie RSV / TSV) y, más adelante, conversión en inscripción.
 */
class PreinscripcionService
{
    /**
     * Crea la pre-inscripción y cobra la reserva en una sola transacción.
     * $d: tipo, est_codigo | (pre_nombres, pre_apellidos, pre_ci, pre_fecha_nac,
     * pre_ue_procedencia, padre_nombre, padre_ci), cur_destino, desc_id,
     * monto, metodo, monto_efectivo, monto_qr, transferencia_nro, sin_factura, obs.
     */
    public function crear(int $gestion, array $d, int $usuarioId): Preinscripcion
    {
        return DB::transaction(function () use ($gestion, $d, $usuarioId) {
            $pfam = null;
            $curOrigen = null;
            if ($d['tipo'] === 'ANTIGUO') {
                $est = Estudiante::with('padres')->where('est_codigo', $d['est_codigo'])->firstOrFail();
                $curOrigen = EstudianteGestion::cursoDe($est->est_codigo, gestion_actual());
                $pfam = optional($est->padres->first())->pfam_codigo;
            } else {
                if (!empty($d['padre_nombre'])) {
                    $pfam = PadreFamilia::obtenerOCrear($d['padre_nombre'], $d['padre_ci'] ?? null)->pfam_codigo;
                }
            }

            $tarifa = Tarifa::para($gestion, $d['cur_destino']);
            $montoAnual = $tarifa ? $tarifa->tar_monto_anual : null;
            $desc = !empty($d['desc_id']) ? Descuento::find($d['desc_id']) : null;

            $pre = Preinscripcion::create([
                'preinsc_codigo'     => ReciboNumerador::siguiente('PRE'),
                'ges_anio'           => $gestion,
                'preinsc_tipo'       => $d['tipo'],
                'est_codigo'         => $d['tipo'] === 'ANTIGUO' ? $d['est_codigo'] : null,
                'pre_nombres'        => $d['pre_nombres'] ?? null,
                'pre_apellidos'      => $d['pre_apellidos'] ?? null,
                'pre_ci'             => $d['pre_ci'] ?? null,
                'pre_fecha_nac'      => $d['pre_fecha_nac'] ?? null,
                'pre_ue_procedencia' => $d['pre_ue_procedencia'] ?? null,
                'pfam_codigo'        => $pfam,
                'cur_origen'         => $curOrigen,
                'cur_destino'        => $d['cur_destino'],
                // Un alumno nuevo no depende de un resultado de este año.
                'preinsc_condicional'=> $d['tipo'] === 'ANTIGUO' ? 1 : 0,
                'preinsc_estado'     => $d['tipo'] === 'ANTIGUO' ? 'RESERVADA' : 'CONFIRMADA',
                'monto_anual_ref'    => $montoAnual,
                'desc_id'            => $desc->desc_id ?? null,
                'monto_desc_ref'     => ($desc && $montoAnual) ? $desc->montoSobre($montoAnual) : 0,
                'preinsc_obs'        => $d['obs'] ?? null,
                'preinsc_usuario'    => $usuarioId,
                'preinsc_fecha'      => now(),
            ]);

            $monto = round((float) $d['monto'], 2);
            if ($monto > 0) {
                [$ef, $qr] = $this->repartir($d['metodo'] ?? 'EFECTIVO', $monto, $d);
                PreinscripcionPago::create([
                    'preinsc_id'        => $pre->preinsc_id,
                    'prepago_recibo'    => ReciboNumerador::siguiente(!empty($d['sin_factura']) ? 'RES-TAL' : 'RES-REC'),
                    'prepago_monto'     => $monto,
                    'prepago_metodo'    => $d['metodo'] ?? 'EFECTIVO',
                    'monto_efectivo'    => $ef,
                    'monto_qr'          => $qr,
                    'transferencia_nro' => $d['transferencia_nro'] ?? null,
                    'sin_factura'       => !empty($d['sin_factura']) ? 1 : 0,
                    'prepago_estado'    => 1,
                    'prepago_usuario'   => $usuarioId,
                    'prepago_fecha'     => now(),
                ]);
            }
            return $pre->load('pagos');
        });
    }

    /** [efectivo, qr] según el método. En MIXTO deben sumar el monto. */
    private function repartir(string $metodo, float $monto, array $d): array
    {
        if ($metodo === 'QR') return [0, $monto];
        if ($metodo === 'MIXTO') {
            $ef = round((float) ($d['monto_efectivo'] ?? 0), 2);
            $qr = round((float) ($d['monto_qr'] ?? 0), 2);
            if (abs($ef + $qr - $monto) > 0.5) {
                throw new \InvalidArgumentException("En pago MIXTO, efectivo ({$ef}) + QR ({$qr}) debe ser igual al monto ({$monto}).");
            }
            return [$ef, $qr];
        }
        return [$monto, 0];
    }

    /** Controles previos de un alumno antiguo: observado y mora de este año. */
    public function controles(string $estCodigo, int $gestionDestino): array
    {
        $est = Estudiante::where('est_codigo', $estCodigo)->first();
        $obs = EstudianteObservado::vigentePara($estCodigo, $gestionDestino)
            ?: EstudianteObservado::vigentePorCi(optional($est)->est_ci, $gestionDestino);

        $deuda = 0; $meses = [];
        $estMora = (new MoraService())->deEstudiante($estCodigo);
        if ($estMora) { $deuda = $estMora->mora_deuda; $meses = $estMora->mora_meses_pendientes; }

        $yaTiene = Preinscripcion::where('est_codigo', $estCodigo)->where('ges_anio', $gestionDestino)->vigentes()->first();
        return ['observado' => $obs, 'deuda' => $deuda, 'meses_deuda' => $meses, 'ya_preinscrito' => $yaTiene];
    }

    /**
     * Convierte una pre-inscripción de alumno antiguo en inscripción de la
     * gestión siguiente. La reserva entra como monto ya pagado (origen RESERVA,
     * no vuelve a sumar en caja) y, como la cuota de febrero ya se calcula como
     * mensualidad − monto pagado, se descuenta sola de febrero.
     */
    public function convertir(Preinscripcion $pre, int $usuarioId, ?float $montoAnual = null): Inscripcion
    {
        if (!in_array($pre->preinsc_estado, ['RESERVADA', 'CONFIRMADA', 'AJUSTAR'])) {
            throw new \RuntimeException("La pre-inscripción {$pre->preinsc_codigo} está {$pre->preinsc_estado}: no se puede inscribir.");
        }
        if (!$pre->est_codigo) {
            throw new \RuntimeException('Es un alumno nuevo: se inscribe al activar la gestión ' . $pre->ges_anio . ' (se crea su ficha en ese momento).');
        }
        $existe = Inscripcion::where('est_codigo', $pre->est_codigo)->where('insc_gestion', $pre->ges_anio)->where('insc_estado', '!=', 0)->exists();
        if ($existe) {
            throw new \RuntimeException('El estudiante ya tiene una inscripción para ' . $pre->ges_anio . '.');
        }

        return DB::transaction(function () use ($pre, $usuarioId, $montoAnual) {
            $tarifa = Tarifa::para($pre->ges_anio, $pre->cur_destino);
            $total = $montoAnual ?? ($tarifa->tar_monto_anual ?? $pre->monto_anual_ref);
            if (!$total) {
                throw new \RuntimeException('No hay tarifa cargada para ' . $pre->cur_destino . ' en ' . $pre->ges_anio . ' (Parametrización → Tarifas).');
            }
            $desc = $pre->desc_id ? Descuento::find($pre->desc_id) : null;
            $montoDesc = $desc ? $desc->montoSobre($total) : 0;
            $final = $total - $montoDesc;
            $reserva = $pre->load('pagos')->monto_reserva;

            $insc = Inscripcion::create([
                'insc_codigo'          => ReciboNumerador::siguiente('INSC'),
                'est_codigo'           => $pre->est_codigo,
                'pfam_codigo'          => $pre->pfam_codigo,
                'cur_codigo'           => $pre->cur_destino,
                'insc_gestion'         => $pre->ges_anio,
                'insc_monto_total'     => $total,
                'insc_monto_descuento' => $montoDesc,
                'insc_monto_final'     => $final,
                'insc_monto_pagado'    => $reserva,
                'insc_saldo'           => $final - $reserva,
                'insc_concepto'        => 'Inscripción gestión ' . $pre->ges_anio . ' (desde pre-inscripción ' . $pre->preinsc_codigo . ')',
                'insc_estado'          => 1,
                'insc_usuario'         => auth()->user()->us_codigo ?? 'SISTEMA',
                'insc_sin_factura'     => (int) optional($pre->pagos->first())->sin_factura,
                'insc_caso_especial'   => 0,
                'preinsc_id'           => $pre->preinsc_id,
            ]);
            if ($desc) {
                $insc->descuentos()->attach($desc->desc_id, ['inscdesc_monto_descuento' => $montoDesc]);
            }
            foreach ($pre->pagos->where('prepago_estado', 1) as $pp) {
                InscripcionPago::create([
                    'inscpago_codigo'   => 'PAGO' . str_pad(InscripcionPago::max('inscpago_id') + 1, 6, '0', STR_PAD_LEFT),
                    'insc_codigo'       => $insc->insc_codigo,
                    'inscpago_monto'    => $pp->prepago_monto,
                    'inscpago_concepto' => 'Reserva de cupo ' . $pp->prepago_recibo,
                    'inscpago_usuario'  => auth()->user()->us_codigo ?? 'SISTEMA',
                    'inscpago_recibo'   => $pp->prepago_recibo,
                    'inscpago_estado'   => 1,
                    'inscpago_origen'   => 'RESERVA',
                ]);
            }
            EstudianteGestion::registrar($pre->est_codigo, (int) $pre->ges_anio, $pre->cur_destino, $insc->insc_codigo);

            $pre->update(['preinsc_estado' => 'CONVERTIDA', 'insc_codigo' => $insc->insc_codigo]);
            return $insc;
        });
    }

    /** Anula una pre-inscripción cargada por error (mismo criterio que anular un recibo). */
    public function anular(Preinscripcion $pre, string $motivo, int $usuarioId): void
    {
        if ($pre->preinsc_estado === 'CONVERTIDA') {
            throw new \RuntimeException('Ya es una inscripción: anule la inscripción o registre una devolución.');
        }
        DB::transaction(function () use ($pre, $motivo, $usuarioId) {
            $pre->pagos()->where('prepago_estado', 1)->update([
                'prepago_estado' => 0, 'anulado_por' => $usuarioId,
                'anulado_fecha' => now(), 'anulado_motivo' => mb_substr($motivo, 0, 255),
            ]);
            $pre->update(['preinsc_estado' => 'ANULADA']);
        });
    }
}
