<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaTransporte extends Model
{
    protected $table = 'transporte_asistencia';
    protected $primaryKey = 'tasis_id';
    public $timestamps = false;

    protected $fillable = [
        'tasis_codigo', 'ruta_codigo', 'est_codigo', 'tasis_fecha',
        'tasis_tipo', 'tasis_hora', 'tasis_observacion', 'tasis_registrado_por'
    ];

    protected $casts = [
        'tasis_fecha' => 'date',
        'tasis_fecha_registro' => 'datetime'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_codigo', 'ruta_codigo');
    }
}
