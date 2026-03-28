<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroEnfermeria extends Model
{
    protected $table = 'enfermeria_registros';
    protected $primaryKey = 'enf_id';
    public $timestamps = false;

    protected $fillable = [
        'enf_codigo',
        'enf_tipo_persona',
        'est_codigo',
        'doc_codigo',
        'enf_fecha',
        'enf_hora',
        'enf_dx_detalle',
        'enf_tipo_atencion',
        'enf_medicamentos',
        'enf_observaciones',
        'enf_estado',
        'enf_registrado_por'
    ];

    protected $casts = [
        'enf_fecha' => 'date',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function docente()
    {
        return $this->belongsTo(\App\Models\Docente::class, 'doc_codigo', 'doc_codigo');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'enf_registrado_por', 'us_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('enf_estado', 1);
    }
}
