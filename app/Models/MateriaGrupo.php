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
            ->withPivot('detalle_orden', 'detalle_promediable')
            ->orderBy('detalle_orden');
    }

    /**
     * Sólo las materias que aportan al promedio del grupo (detalle_promediable = 1).
     * Si la columna aún no existe (DB sin upgrade), todas las materias se consideran promediables.
     */
    public function getMateriasPromediablesAttribute()
    {
        return $this->materias->filter(function ($m) {
            $p = $m->pivot->detalle_promediable ?? 1;
            return (int) $p === 1;
        })->values();
    }

    public function scopeActivo($query)
    {
        return $query->where('grupo_estado', 1);
    }
}
