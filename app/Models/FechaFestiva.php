<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FechaFestiva extends Model
{
    protected $table = 'asistencia_fechas_festivas';
    protected $primaryKey = 'festivo_id';
    public $timestamps = false;

    protected $fillable = [
        'festivo_codigo',
        'festivo_fecha',
        'festivo_nombre',
        'festivo_descripcion',
        'festivo_hora_entrada',
        'festivo_hora_salida',
        'festivo_tipo',
        'festivo_estado'
    ];

    protected $casts = [
        'festivo_fecha' => 'date',
        'festivo_hora_entrada' => 'datetime:H:i',
        'festivo_hora_salida' => 'datetime:H:i',
        'festivo_fecha_registro' => 'datetime'
    ];

    public function scopeActivo($query)
    {
        return $query->where('festivo_estado', 1);
    }

    public function scopeFeriado($query)
    {
        return $query->where('festivo_tipo', 1);
    }

    public function scopeHorarioEspecial($query)
    {
        return $query->where('festivo_tipo', 2);
    }
}
