<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'colegio_materias';
    protected $primaryKey = 'mat_id';
    public $timestamps = false;

    protected $fillable = ['mat_codigo', 'mat_nombre', 'mat_visible'];

    protected $casts = ['mat_fecha' => 'datetime', 'mat_visible' => 'integer'];

    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'colegiorela_docente_materia', 'mat_codigo', 'doc_codigo', 'mat_codigo', 'doc_codigo');
    }

    public function scopeVisible($query)
    {
        return $query->where('mat_visible', 1);
    }
}
