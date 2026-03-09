<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atraso extends Model
{
    protected $table = 'asistencia_atrasos';
    protected $primaryKey = 'atraso_id';
    public $timestamps = false;

    protected $fillable = [
        'atraso_codigo',
        'estud_codigo',
        'atraso_fecha',
        'atraso_hora',
        'minutos_atraso',
        'atraso_observacion'
    ];

    protected $casts = [
        'atraso_fecha' => 'date',
        'atraso_fecha_registro' => 'datetime'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estud_codigo', 'est_codigo');
    }
}
