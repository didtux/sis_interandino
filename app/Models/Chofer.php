<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chofer extends Model
{
    protected $table = 'transporte_choferes';
    protected $primaryKey = 'chof_id';
    public $timestamps = false;

    protected $fillable = [
        'chof_codigo', 'chof_nombres', 'chof_apellidos', 'chof_ci',
        'chof_licencia', 'chof_telefono', 'chof_direccion', 'chof_foto',
        'chof_fecha_nacimiento', 'chof_estado', 'chof_usuario_registro'
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTransporte::class, 'chof_codigo', 'chof_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('chof_estado', 1);
    }
}
