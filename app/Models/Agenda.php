<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $table = 'agenda';
    protected $primaryKey = 'age_id';
    public $timestamps = false;

    protected $fillable = [
        'age_codigo', 'age_tipo', 'curso_codigo', 'prof_codigo',
        'est_codigo', 'age_titulo', 'age_detalles', 'age_estado'
    ];

    protected $casts = [
        'age_fechahora' => 'datetime',
        'age_tipo' => 'integer',
        'age_estado' => 'integer'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('age_estado', 1);
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('age_tipo', $tipo);
    }
}
