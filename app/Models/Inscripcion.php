<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';
    protected $primaryKey = 'insc_id';
    public $timestamps = false;

    protected $fillable = [
        'insc_codigo', 'est_codigo', 'pfam_codigo', 'cur_codigo',
        'insc_gestion', 'insc_monto_total', 'insc_monto_pagado',
        'insc_saldo', 'insc_concepto', 'insc_estado', 'insc_usuario',
        'insc_monto_descuento', 'insc_monto_final', 'insc_sin_factura'
    ];

    protected $casts = [
        'insc_fecha' => 'datetime',
        'insc_monto_total' => 'float',
        'insc_monto_pagado' => 'float',
        'insc_saldo' => 'float',
        'insc_monto_descuento' => 'float',
        'insc_monto_final' => 'float',
        'insc_sin_factura' => 'boolean'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function padreFamilia()
    {
        return $this->belongsTo(PadreFamilia::class, 'pfam_codigo', 'pfam_codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }

    public function pagos()
    {
        return $this->hasMany(InscripcionPago::class, 'insc_codigo', 'insc_codigo');
    }

    public function descuentos()
    {
        return $this->belongsToMany(Descuento::class, 'inscripciones_descuentos', 'insc_id', 'desc_id')
            ->withPivot('inscdesc_monto_descuento', 'inscdesc_fecha');
    }

    public function getMontoMensualidadAttribute()
    {
        return ($this->insc_monto_final ?? $this->insc_monto_total) / 10;
    }
}
