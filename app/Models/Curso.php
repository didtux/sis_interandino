<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $table = 'colegio_cursos';
    protected $primaryKey = 'cur_id';
    public $timestamps = false;

    protected $fillable = [
        'cur_codigo',
        'cur_nombre',
        'cur_visible'
    ];

    protected $casts = [
        'cur_fecha' => 'datetime',
        'cur_visible' => 'integer'
    ];

    // Relación con Estudiantes
    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'cur_codigo', 'cur_codigo');
    }
    
    // Relación con configuraciones de asistencia
    public function configuraciones()
    {
        return $this->hasMany(ConfiguracionAsistencia::class, 'cur_codigo', 'cur_codigo');
    }

    // Relación con Docentes
    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'colegiorela_docente_curso', 'cur_codigo', 'doc_codigo', 'cur_codigo', 'doc_codigo');
    }

    // Materias asignadas al curso
    public function cursoMaterias()
    {
        return $this->hasMany(CursoMateria::class, 'cur_codigo', 'cur_codigo')->where('curmat_estado', 1);
    }

    // Docentes asignados a materias del curso
    public function cursoMateriaDocentes()
    {
        return $this->hasMany(CursoMateriaDocente::class, 'cur_codigo', 'cur_codigo')->where('curmatdoc_estado', 1);
    }

    // Lista de curso
    public function listaCurso()
    {
        return $this->hasMany(ListaCurso::class, 'cur_codigo', 'cur_codigo');
    }

    // Scope para cursos visibles
    public function scopeVisible($query)
    {
        return $query->where('cur_visible', 1);
    }
}
