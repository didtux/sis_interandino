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
        'inscpago_concepto', 'inscpago_usuario', 'inscpago_recibo'
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
