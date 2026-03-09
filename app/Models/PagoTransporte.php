<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PagoTransporte extends Model
{
    protected $table = 'transporte_pagos';
    protected $primaryKey = 'tpago_id';
    public $timestamps = false;

    protected $fillable = [
        'tpago_codigo', 'est_codigo', 'tpago_tipo', 'tpago_monto',
        'tpago_fecha_pago', 'tpago_fecha_inicio', 'tpago_fecha_fin',
        'tpago_estado', 'tpago_usuario_registro'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function asignacionRuta()
    {
        return $this->hasOne(EstudianteRuta::class, 'tpago_codigo', 'tpago_codigo');
    }

    public function scopeVigente($query)
    {
        return $query->where('tpago_estado', 'vigente');
    }

    public function scopeVencido($query)
    {
        return $query->where('tpago_estado', 'vencido');
    }

    public function verificarVencimiento()
    {
        if ($this->tpago_estado == 'vigente' && Carbon::parse($this->tpago_fecha_fin)->isPast()) {
            $this->tpago_estado = 'vencido';
            $this->save();
        }
    }
}
