<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';
    protected $primaryKey = 'serv_id';
    public $timestamps = false;

    protected $fillable = [
        'serv_codigo', 'serv_nombre', 'serv_descripcion',
        'serv_costo', 'serv_estado', 'serv_usuario_registro'
    ];

    protected $casts = [
        'serv_costo' => 'float',
        'serv_estado' => 'integer',
        'serv_fecha_registro' => 'datetime'
    ];

    public function pagos()
    {
        return $this->hasMany(PagoServicio::class, 'serv_codigo', 'serv_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('serv_estado', 1);
    }
}
