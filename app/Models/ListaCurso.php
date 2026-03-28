<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaCurso extends Model
{
    protected $table = 'colegio_lista_curso';
    protected $primaryKey = 'lista_id';
    public $timestamps = false;

    protected $fillable = ['cur_codigo', 'est_codigo', 'lista_numero', 'lista_gestion'];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }
}
