<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    protected $table = 'colegio_gestiones';
    protected $primaryKey = 'ges_id';
    public $timestamps = false;

    protected $fillable = [
        'ges_anio',
        'ges_nombre',
        'ges_abreviado',
        'ges_estado',
        'ges_inscripcion_abierta', 'ges_preinsc_desde', 'ges_preinsc_hasta',
        'ges_fecha_inicio', 'ges_fecha_fin', 'ges_cerrada', 'ges_monto_reserva',
    ];

    protected $casts = [
        'ges_fecha'  => 'datetime',
        'ges_estado' => 'integer',
    ];

    public function scopeActiva($q)
    {
        return $q->where('ges_estado', 1);
    }
}
