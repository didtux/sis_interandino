<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'transporte_vehiculos';
    protected $primaryKey = 'veh_id';
    public $timestamps = false;

    protected $fillable = [
        'veh_codigo', 'veh_numero_bus', 'veh_placa', 'veh_marca', 'veh_modelo', 
        'veh_anio', 'veh_capacidad', 'veh_color', 'veh_estado',
        'veh_usuario_registro'
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTransporte::class, 'veh_codigo', 'veh_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('veh_estado', 1);
    }
}
