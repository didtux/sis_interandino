<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteRuta extends Model
{
    protected $table = 'transporte_estudiantes_rutas';
    protected $primaryKey = 'ter_id';
    public $timestamps = false;

    protected $fillable = [
        'ter_codigo', 'est_codigo', 'ruta_codigo', 'tpago_codigo',
        'ter_direccion_recogida', 'ter_coordenadas', 'ter_estado',
        'ter_usuario_registro'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_codigo', 'ruta_codigo');
    }

    public function asignacionActiva()
    {
        return $this->hasOneThrough(
            AsignacionTransporte::class,
            Ruta::class,
            'ruta_codigo',
            'ruta_codigo',
            'ruta_codigo',
            'ruta_codigo'
        )->where('asig_estado', 1);
    }

    public function pago()
    {
        return $this->belongsTo(PagoTransporte::class, 'tpago_codigo', 'tpago_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('ter_estado', 1);
    }
}
