<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'colegio_docentes';
    protected $primaryKey = 'doc_id';
    public $timestamps = false;

    protected $fillable = [
        'doc_codigo',
        'doc_nombres',
        'doc_apellidos',
        'doc_ci',
        'doc_materia',
        'doc_foto',
        'doc_visible'
    ];

    protected $casts = [
        'doc_fecha' => 'datetime',
        'doc_visible' => 'integer'
    ];

    // Relación con Cursos
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'colegiorela_docente_curso', 'doc_codigo', 'cur_codigo', 'doc_codigo', 'cur_codigo');
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'colegiorela_docente_materia', 'doc_codigo', 'mat_codigo', 'doc_codigo', 'mat_codigo');
    }

    public function cursoMateriaDocentes()
    {
        return $this->hasMany(CursoMateriaDocente::class, 'doc_codigo', 'doc_codigo')->where('curmatdoc_estado', 1);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'doc_codigo', 'doc_codigo');
    }

    // Scope para docentes visibles
    public function scopeVisible($query)
    {
        return $query->where('doc_visible', 1);
    }
}
