<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaDimension extends Model
{
    protected $table = 'notas_config_dimensiones';
    protected $primaryKey = 'dimension_id';
    public $timestamps = false;

    protected $fillable = [
        'dimension_nombre', 'dimension_valor_max', 'dimension_columnas', 'dimension_orden',
        'dimension_gestion', 'dimension_estado'
    ];

    public function scopeActivo($query)
    {
        return $query->where('dimension_estado', 1);
    }

    public function scopeGestion($query, $gestion = null)
    {
        return $query->where('dimension_gestion', $gestion ?? date('Y'));
    }
}
