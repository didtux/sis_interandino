<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    protected $table = 'colegio_gestiones';
    protected $primaryKey = 'ges_id';
    public $timestamps = false;

    protected $fillable = [
        'ges_anio',
        'ges_nombre',
        'ges_abreviado',
        'ges_estado',
    ];

    protected $casts = [
        'ges_fecha'  => 'datetime',
        'ges_estado' => 'integer',
    ];

    public function scopeActiva($q)
    {
        return $q->where('ges_estado', 1);
    }
}
