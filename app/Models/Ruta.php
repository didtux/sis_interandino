<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'transporte_rutas';
    protected $primaryKey = 'ruta_id';
    public $timestamps = false;

    protected $fillable = [
        'ruta_codigo', 'ruta_nombre', 'ruta_descripcion',
        'ruta_coordenadas', 'ruta_estado', 'ruta_usuario_registro'
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTransporte::class, 'ruta_codigo', 'ruta_codigo');
    }

    public function estudiantes()
    {
        return $this->hasMany(EstudianteRuta::class, 'ruta_codigo', 'ruta_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('ruta_estado', 1);
    }
}
