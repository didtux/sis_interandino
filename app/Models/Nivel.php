<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = 'colegio_niveles';
    protected $primaryKey = 'niv_id';
    public $timestamps = false;

    protected $fillable = [
        'niv_nombre',
        'niv_abreviado',
        'niv_orden',
        'niv_estado',
    ];

    protected $casts = [
        'niv_fecha'  => 'datetime',
        'niv_estado' => 'integer',
        'niv_orden'  => 'integer',
    ];

    public function scopeActivo($q)
    {
        return $q->where('niv_estado', 1);
    }

    public function scopeOrdenado($q)
    {
        return $q->orderBy('niv_orden')->orderBy('niv_nombre');
    }
}
