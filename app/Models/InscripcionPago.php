<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionPago extends Model
{
    protected $table = 'inscripciones_pagos';
    protected $primaryKey = 'inscpago_id';
    public $timestamps = false;

    protected $fillable = [
        'inscpago_codigo', 'insc_codigo', 'inscpago_monto',
        'inscpago_concepto', 'inscpago_usuario', 'inscpago_recibo',
        // RESERVA: el dinero entró como reserva de cupo (pre-inscripción) y ya
        // se contó en la caja de ese día; no vuelve a sumar en la de hoy.
        'inscpago_estado', 'inscpago_origen',
    ];

    protected $casts = [
        'inscpago_fecha' => 'datetime',
        'inscpago_monto' => 'float'
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'insc_codigo', 'insc_codigo');
    }
}
