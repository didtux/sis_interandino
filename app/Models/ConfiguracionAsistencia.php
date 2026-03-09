<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionAsistencia extends Model
{
    protected $table = 'asistencia_configuracion';
    protected $primaryKey = 'config_id';
    public $timestamps = false;

    protected $fillable = [
        'config_codigo',
        'config_categoria',
        'config_turno',
        'cur_codigo',
        'hora_entrada',
        'hora_salida',
        'tolerancia_atraso',
        'hora_atraso_desde',
        'hora_atraso_hasta',
        'config_estado'
    ];

    protected $casts = [
        'config_fecha' => 'datetime'
    ];

    public function scopeActivo($query)
    {
        return $query->where('config_estado', 1);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'asistencia_configuracion_cursos', 'config_id', 'cur_codigo', 'config_id', 'cur_codigo')
            ->selectRaw('colegio_cursos.*, asistencia_configuracion_cursos.config_id as pivot_config_id, asistencia_configuracion_cursos.cur_codigo as pivot_cur_codigo')
            ->whereRaw('colegio_cursos.cur_codigo COLLATE utf8mb4_general_ci = asistencia_configuracion_cursos.cur_codigo COLLATE utf8mb4_general_ci');
    }
}
