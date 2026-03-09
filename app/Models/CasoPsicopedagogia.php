<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoPsicopedagogia extends Model
{
    protected $table = 'psicopedagogia_casos';
    protected $primaryKey = 'psico_id';
    public $timestamps = false;

    protected $fillable = [
        'psico_codigo',
        'est_codigo',
        'psico_fecha',
        'psico_caso',
        'psico_solucion',
        'psico_acuerdo',
        'psico_tipo_acuerdo',
        'psico_observaciones',
        'psico_estado',
        'psico_registrado_por'
    ];

    protected $casts = [
        'psico_fecha' => 'date',
        'psico_fecha_registro' => 'datetime',
        'psico_estado' => 'integer'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'psico_registrado_por', 'us_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('psico_estado', 1);
    }
}
