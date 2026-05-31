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
        'tpago_codigo', 'est_codigo', 'tpago_tipo', 'tpago_cuota_nro', 'tpago_mes', 'tpago_monto',
        'tpago_fecha_pago', 'tpago_fecha_inicio', 'tpago_fecha_fin',
        'tpago_estado', 'tpago_usuario_registro', 'tpago_monto_modificado'
    ];

    /** Etiqueta ordinal de cuota: 1 => "1ra cuota", 3 => "3ra cuota". */
    public static function etiquetaCuota($n): string
    {
        $n = (int) $n;
        $ord = [1=>'1ra',2=>'2da',3=>'3ra',4=>'4ta',5=>'5ta',6=>'6ta',7=>'7ma',8=>'8va',9=>'9na',10=>'10ma'];
        return 'Transporte ' . ($ord[$n] ?? ($n.'ª')) . ' cuota';
    }

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
