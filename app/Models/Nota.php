<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'colegio_notas';
    protected $primaryKey = 'nota_id';
    public $timestamps = false;

    protected $fillable = [
        'periodo_id', 'curmatdoc_id', 'est_codigo',
        'nota_ser_respeto', 'nota_ser_responsabilidad', 'nota_ser_puntualidad', 'nota_ser_promedio',
        'nota_saber_parcial', 'nota_saber_examen', 'nota_saber_promedio',
        'nota_hacer_promedio', 'nota_autoevaluacion', 'nota_promedio_trimestral',
        'nota_estado', 'nota_observacion',
        'nota_guardado_por', 'nota_fecha_guardado',
        'nota_enviado_por', 'nota_fecha_envio',
        'nota_fecha_aprobacion', 'nota_aprobado_por'
    ];

    public function periodo()
    {
        return $this->belongsTo(NotaPeriodo::class, 'periodo_id', 'periodo_id');
    }

    public function cursoMateriaDocente()
    {
        return $this->belongsTo(CursoMateriaDocente::class, 'curmatdoc_id', 'curmatdoc_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function detalles()
    {
        return $this->hasMany(NotaDetalle::class, 'nota_id', 'nota_id');
    }

    // Estado labels
    public function getEstadoLabelAttribute()
    {
        return match($this->nota_estado) {
            0 => 'Borrador',
            1 => 'Enviado',
            2 => 'Aprobado',
            3 => 'Rechazado',
            default => 'Desconocido'
        };
    }
}
