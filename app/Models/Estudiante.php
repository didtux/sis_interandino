<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'colegio_estudiantes';
    protected $primaryKey = 'est_id';
    public $timestamps = false;

    const CREATED_AT = 'est_fecha';
    const UPDATED_AT = null;

    protected $fillable = [
        'est_codigo',
        'cur_codigo',
        'est_nombres',
        'est_apellidos',
        'est_ci',
        'est_visible',
        'est_lugarnac',
        'est_fechanac',
        'est_ueprocedencia',
        'preinscripcion',
        'est_foto',
        'est_fecha'
    ];

    protected $casts = [
        'est_fecha' => 'datetime',
        'preinscripcion' => 'float',
        'est_visible' => 'integer'
    ];

    // Relación con Curso
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }

    // Relación con Asistencias
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'estud_codigo', 'est_codigo');
    }

    public function padres()
    {
        return $this->belongsToMany(PadreFamilia::class, 'rela_estudiantespadres', 'est_id', 'pfam_id', 'est_codigo', 'pfam_codigo')
            ->wherePivot('estpad_estado', 1);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'est_codigo', 'est_codigo');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'est_codigo', 'est_codigo');
    }

    public function rutaTransporte()
    {
        return $this->hasOne(EstudianteRuta::class, 'est_codigo', 'est_codigo')
            ->where('ter_estado', 1);
    }

    public function pagosTransporte()
    {
        return $this->hasMany(PagoTransporte::class, 'est_codigo', 'est_codigo');
    }

    public function inscripcion()
    {
        return $this->hasOne(Inscripcion::class, 'est_codigo', 'est_codigo')
            ->where('insc_estado', 1)
            ->orderBy('insc_gestion', 'desc');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'est_codigo', 'est_codigo');
    }

    // Scope para estudiantes visibles
    public function scopeVisible($query)
    {
        return $query->where('est_visible', 1);
    }
}
