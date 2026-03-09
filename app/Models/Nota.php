<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'colegio_notas';
    protected $primaryKey = 'notas_id';
    public $timestamps = false;

    protected $fillable = [
        'notas_codigo', 'est_codigo', 'doc_codigo', 'cur_codigo',
        'notas_ser1', 'notas_ser2', 'notas_saber1', 'notas_saber2',
        'notas_hacer1', 'notas_hacer2', 'notas_decidir1', 'notas_decidir2',
        'notas_auto1', 'notas_auto2'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }
}
