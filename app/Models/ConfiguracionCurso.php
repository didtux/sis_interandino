<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionCurso extends Model
{
    protected $table = 'asistencia_configuracion_cursos';
    protected $primaryKey = 'config_curso_id';

    protected $fillable = [
        'config_id',
        'cur_codigo'
    ];

    public function configuracion()
    {
        return $this->belongsTo(ConfiguracionAsistencia::class, 'config_id', 'config_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }
}
