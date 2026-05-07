<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemaConfiguracion extends Model
{
    protected $table = 'sistema_configuracion';
    protected $primaryKey = 'config_id';
    public $timestamps = false;

    protected $fillable = [
        'config_logo',
        'config_denominacion',
        'config_nombre_ue',
        'config_direccion',
        'config_telefono',
        'config_ciudad',
        'config_email',
    ];

    protected $casts = ['config_fecha' => 'datetime'];

    public static function actual()
    {
        return self::orderBy('config_id')->first();
    }
}
