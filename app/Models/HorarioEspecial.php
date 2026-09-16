<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Horario que rige sobre un RANGO DE FECHAS y pisa a la configuración normal
 * de asistencia_configuracion.
 *
 * Dos tipos:
 *   TIPO_HORARIO (1) — cambia entrada/tolerancia/salida por categoría
 *                      (ej. horario de invierno: INICIAL 8:45, resto 8:15).
 *   TIPO_RECESO  (2) — no hay clases: ni atrasos, ni faltas, ni días hábiles
 *                      (ej. vacaciones de julio).
 *
 * @see \App\Services\HorarioEspecialService  resuelve qué horario rige cada día
 */
class HorarioEspecial extends Model
{
    protected $table = 'asistencia_horarios_especiales';
    protected $primaryKey = 'esp_id';
    public $timestamps = false;

    public const TIPO_HORARIO = 1;
    public const TIPO_RECESO  = 2;

    protected $fillable = [
        'esp_nombre',
        'esp_gestion',
        'esp_fecha_inicio',
        'esp_fecha_fin',
        'esp_tipo',
        'esp_observacion',
        'esp_estado',
        'esp_creado_por',
    ];

    protected $casts = [
        'esp_fecha_inicio'   => 'date',
        'esp_fecha_fin'      => 'date',
        'esp_tipo'           => 'integer',
        'esp_gestion'        => 'integer',
        'esp_estado'         => 'integer',
        'esp_fecha_registro' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(HorarioEspecialDetalle::class, 'esp_id', 'esp_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('esp_estado', 1);
    }

    public function scopeGestion($query, $gestion)
    {
        return $query->where('esp_gestion', (int) $gestion);
    }

    /** Horarios que cambian las horas (no los recesos). */
    public function scopeDeHorario($query)
    {
        return $query->where('esp_tipo', self::TIPO_HORARIO);
    }

    /** Recesos / vacaciones: días sin clases. */
    public function scopeReceso($query)
    {
        return $query->where('esp_tipo', self::TIPO_RECESO);
    }

    /** Rangos que contienen la fecha dada. */
    public function scopeVigenteEn($query, $fecha)
    {
        return $query->whereDate('esp_fecha_inicio', '<=', $fecha)
                     ->whereDate('esp_fecha_fin', '>=', $fecha);
    }

    /** Rangos que se solapan con [inicio, fin]. */
    public function scopeSolapa($query, $inicio, $fin)
    {
        return $query->whereDate('esp_fecha_inicio', '<=', $fin)
                     ->whereDate('esp_fecha_fin', '>=', $inicio);
    }

    public function getEsRecesoAttribute(): bool
    {
        return (int) $this->esp_tipo === self::TIPO_RECESO;
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->es_receso ? 'Receso sin clases' : 'Horario especial';
    }
}
