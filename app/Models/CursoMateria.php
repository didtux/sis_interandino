<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoMateria extends Model
{
    protected $table = 'colegio_curso_materia';
    protected $primaryKey = 'curmat_id';
    public $timestamps = false;

    protected $fillable = ['cur_codigo', 'mat_codigo', 'curmat_estado'];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'mat_codigo', 'mat_codigo');
    }
}
