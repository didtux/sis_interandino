<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionTransporte extends Model
{
    protected $table = 'transporte_asignaciones';
    protected $primaryKey = 'asig_id';
    public $timestamps = false;

    protected $fillable = [
        'asig_codigo', 'chof_codigo', 'veh_codigo', 'ruta_codigo',
        'asig_fecha_inicio', 'asig_fecha_fin', 'asig_estado',
        'asig_usuario_registro'
    ];

    public function chofer()
    {
        return $this->belongsTo(Chofer::class, 'chof_codigo', 'chof_codigo');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'veh_codigo', 'veh_codigo');
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_codigo', 'ruta_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('asig_estado', 1);
    }
}
