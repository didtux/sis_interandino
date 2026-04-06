<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaClase extends Model
{
    protected $table = 'notas_asistencia_clases';
    protected $primaryKey = 'asiscl_id';
    public $timestamps = false;

    protected $fillable = [
        'curmatdoc_id', 'periodo_id', 'est_codigo', 'asiscl_fecha',
        'asiscl_estado', 'asiscl_observacion', 'asiscl_registrado_por'
    ];

    protected $casts = ['asiscl_fecha' => 'date'];

    public function cursoMateriaDocente()
    {
        return $this->belongsTo(CursoMateriaDocente::class, 'curmatdoc_id', 'curmatdoc_id');
    }

    public function periodo()
    {
        return $this->belongsTo(NotaPeriodo::class, 'periodo_id', 'periodo_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public static function estadoLabel($estado)
    {
        return match($estado) {
            'P' => 'Presente', 'A' => 'Atraso', 'F' => 'Falta', 'L' => 'Licencia', default => $estado
        };
    }

    public static function estadoColor($estado)
    {
        return match($estado) {
            'P' => 'success', 'A' => 'warning', 'F' => 'danger', 'L' => 'info', default => 'secondary'
        };
    }
}
