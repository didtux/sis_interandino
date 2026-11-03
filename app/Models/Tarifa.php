<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Monto anual por gestión y curso (o nivel). Antes se escribía a mano en cada inscripción. */
class Tarifa extends Model
{
    protected $table = 'colegio_tarifas';
    protected $primaryKey = 'tar_id';
    public $timestamps = false;

    protected $fillable = ['ges_anio', 'cur_codigo', 'cur_nivel', 'tar_monto_anual', 'tar_monto_reserva', 'tar_cuotas', 'tar_mes_inicio'];
    protected $casts = ['tar_monto_anual' => 'float', 'tar_monto_reserva' => 'float', 'tar_cuotas' => 'integer', 'tar_mes_inicio' => 'integer'];

    /** Tarifa de un curso: la del curso, si no la de su nivel, si no la general. */
    public static function para(int $gestion, string $curCodigo): ?self
    {
        $nivel = Curso::where('cur_codigo', $curCodigo)->value('cur_nivel');
        $todas = self::where('ges_anio', $gestion)->get();
        // Una fila puede tener sólo la reserva: para el monto anual se salta
        $todas = $todas->whereNotNull('tar_monto_anual');
        return $todas->firstWhere('cur_codigo', $curCodigo)
            ?? $todas->first(fn($t) => !$t->cur_codigo && $t->cur_nivel && $t->cur_nivel === $nivel)
            ?? $todas->first(fn($t) => !$t->cur_codigo && !$t->cur_nivel);
    }

    /**
     * Monto de la reserva de pre-inscripción y de dónde sale, del más
     * específico al más general: tarifa del curso destino (o de su nivel),
     * monto de la gestión, parámetro del colegio.
     *
     * @return array{0: float, 1: string}
     */
    public static function reservaPara(int $gestion, ?string $curCodigo): array
    {
        if ($curCodigo) {
            $nivel = Curso::where('cur_codigo', $curCodigo)->value('cur_nivel');
            $todas = self::where('ges_anio', $gestion)->whereNotNull('tar_monto_reserva')->get();
            $t = $todas->firstWhere('cur_codigo', $curCodigo)
                ?? $todas->first(fn($t) => !$t->cur_codigo && $t->cur_nivel && $t->cur_nivel === $nivel);
            if ($t) return [(float) $t->tar_monto_reserva, 'tarifa del curso'];
        }
        $g = Gestion::where('ges_anio', $gestion)->value('ges_monto_reserva');
        if ($g !== null) return [(float) $g, 'gestión ' . $gestion];
        return [(float) parametro('preinsc_monto_reserva', 300), 'parámetro del colegio'];
    }
}
