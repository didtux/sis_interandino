<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreinscripcionPago extends Model
{
    protected $table = 'preinscripciones_pagos';
    protected $primaryKey = 'prepago_id';
    public $timestamps = false;

    protected $fillable = [
        'preinsc_id', 'prepago_recibo', 'prepago_monto', 'prepago_metodo', 'monto_efectivo', 'monto_qr',
        'transferencia_nro', 'sin_factura', 'prepago_estado', 'anulado_por', 'anulado_fecha',
        'anulado_motivo', 'prepago_usuario', 'prepago_fecha',
    ];
    protected $casts = ['prepago_fecha' => 'datetime', 'anulado_fecha' => 'datetime',
                        'prepago_monto' => 'float', 'monto_efectivo' => 'float', 'monto_qr' => 'float'];

    public function preinscripcion()
    {
        return $this->belongsTo(Preinscripcion::class, 'preinsc_id', 'preinsc_id');
    }
}
