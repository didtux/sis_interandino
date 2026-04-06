<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaPeriodo extends Model
{
    protected $table = 'notas_config_periodos';
    protected $primaryKey = 'periodo_id';
    public $timestamps = false;

    protected $fillable = [
        'periodo_nombre', 'periodo_numero', 'periodo_fecha_inicio',
        'periodo_fecha_fin', 'periodo_gestion', 'periodo_estado'
    ];

    protected $casts = [
        'periodo_fecha_inicio' => 'date',
        'periodo_fecha_fin' => 'date',
    ];

    public function scopeActivo($query)
    {
        return $query->where('periodo_estado', 1);
    }

    public function scopeGestion($query, $gestion = null)
    {
        return $query->where('periodo_gestion', $gestion ?? date('Y'));
    }
}
