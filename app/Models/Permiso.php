<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'asistencia_permisos';
    protected $primaryKey = 'permiso_id';
    public $timestamps = false;

    protected $fillable = [
        'permiso_codigo',
        'permiso_tipo',
        'permiso_numero',
        'estud_codigo',
        'permiso_fecha_inicio',
        'permiso_fecha_fin',
        'permiso_origen',
        'permiso_numero_licencia',
        'permiso_motivo',
        'permiso_observacion',
        'permiso_solicitante_tipo',
        'permiso_solicitante_pfam',
        'permiso_solicitante_nombre',
        'permiso_responsable',
        'permiso_archivo',
        'permiso_documento',
        'permiso_estado',
        'permiso_aprobado_por'
    ];

    protected $casts = [
        'permiso_fecha_inicio' => 'date',
        'permiso_fecha_fin' => 'date',
        'permiso_fecha_registro' => 'datetime'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estud_codigo', 'est_codigo');
    }

    public function solicitantePadre()
    {
        return $this->belongsTo(PadreFamilia::class, 'permiso_solicitante_pfam', 'pfam_codigo');
    }

    public function getSolicitanteNombreCompletoAttribute()
    {
        if ($this->permiso_solicitante_tipo === 'PADRE' && $this->solicitantePadre) {
            return $this->solicitantePadre->pfam_nombres . ' ' . $this->solicitantePadre->pfam_apellidos;
        }
        return $this->permiso_solicitante_nombre;
    }

    public function scopeAprobado($query)
    {
        return $query->where('permiso_estado', 1);
    }

    public function scopePendiente($query)
    {
        return $query->where('permiso_estado', 2);
    }
}
