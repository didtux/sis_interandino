<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoMateriaDocente extends Model
{
    protected $table = 'colegio_curso_materia_docente';
    protected $primaryKey = 'curmatdoc_id';
    public $timestamps = false;

    protected $fillable = ['cur_codigo', 'mat_codigo', 'doc_codigo', 'curmatdoc_estado'];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'mat_codigo', 'mat_codigo');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'curmatdoc_id', 'curmatdoc_id');
    }
}
