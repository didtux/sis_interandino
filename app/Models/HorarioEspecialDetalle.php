<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Horas que rigen para UNA categoría y turno dentro de un horario especial.
 *
 * Existe una fila por categoría afectada, que es lo que permite que en un mismo
 * rango INICIAL entre 8:45, PRIMARIA/SECUNDARIA 8:15 y la secundaria de la
 * tarde tenga su propio cambio. El turno vive acá y no en la cabecera
 * justamente para que un rango pueda cruzar turnos.
 *
 * det_tolerancia_atraso es una HORA ABSOLUTA, no una duración: una marcación
 * posterior a esa hora es atraso (mismo criterio que
 * asistencia_configuracion.tolerancia_atraso).
 */
class HorarioEspecialDetalle extends Model
{
    protected $table = 'asistencia_horarios_especiales_detalle';
    protected $primaryKey = 'det_id';
    public $timestamps = false;

    protected $fillable = [
        'esp_id',
        'det_categoria',
        'det_turno',
        'det_hora_entrada',
        'det_tolerancia_atraso',
        'det_hora_salida',
    ];

    public function horario()
    {
        return $this->belongsTo(HorarioEspecial::class, 'esp_id', 'esp_id');
    }
}
