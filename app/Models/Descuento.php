<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    protected $table = 'descuentos';
    protected $primaryKey = 'desc_id';
    public $timestamps = false;

    protected $fillable = [
        'desc_codigo', 'desc_nombre', 'desc_porcentaje', 'desc_estado',
        'desc_tipo', 'desc_monto', 'desc_gestion', 'desc_vigente_hasta',
    ];

    /**
     * Monto que descuenta sobre un total anual. Los descuentos por familia se
     * cargaron con 0,05 % pero eran 250 Bs fijos: recalcularlos por porcentaje
     * daba 2,38 Bs. Ahora pueden ser PORCENTAJE o MONTO.
     */
    public function montoSobre(float $total): float
    {
        if (($this->desc_tipo ?? 'PORCENTAJE') === 'MONTO') {
            return round(min((float) $this->desc_monto, $total), 2);
        }
        return round($total * (float) $this->desc_porcentaje / 100, 2);
    }

    /** Vigente para una gestión (sin gestión fija y sin vencer). */
    public function scopeVigentePara($q, int $gestion)
    {
        return $q->where('desc_estado', 1)
            ->where(fn($w) => $w->whereNull('desc_gestion')->orWhere('desc_gestion', $gestion))
            ->where(fn($w) => $w->whereNull('desc_vigente_hasta')->orWhere('desc_vigente_hasta', '>=', now()->toDateString()));
    }

    protected $casts = [
        'desc_porcentaje' => 'float',
        'desc_estado' => 'integer',
        'desc_fecha_registro' => 'datetime'
    ];

    public function inscripciones()
    {
        return $this->belongsToMany(Inscripcion::class, 'inscripciones_descuentos', 'desc_id', 'insc_id')
            ->withPivot('inscdesc_monto_descuento', 'inscdesc_fecha');
    }
}
