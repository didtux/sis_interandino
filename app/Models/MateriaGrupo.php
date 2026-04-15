<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaGrupo extends Model
{
    protected $table = 'colegio_materia_grupos';
    protected $primaryKey = 'grupo_id';
    public $timestamps = false;

    protected $fillable = ['grupo_nombre', 'grupo_estado'];

    protected $casts = ['grupo_fecha' => 'datetime'];

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'colegio_materia_grupo_detalle', 'grupo_id', 'mat_codigo', 'grupo_id', 'mat_codigo')
            ->withPivot('detalle_orden')
            ->orderBy('detalle_orden');
    }

    public function scopeActivo($query)
    {
        return $query->where('grupo_estado', 1);
    }
}
