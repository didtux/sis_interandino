<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletinDescarga extends Model
{
    protected $table = 'boletines_descargas';
    protected $primaryKey = 'descarga_id';
    public $timestamps = false;

    protected $fillable = [
        'descarga_token',
        'est_codigo',
        'descarga_gestion',
        'descarga_trimestre',
        'descargado_por',
        'descargado_por_nombre',
        'descarga_fecha',
        'descarga_ip',
        'descarga_user_agent',
        'descarga_numero_copia',
        'descarga_cobrable',
        'descarga_servicio_id',
        'descarga_observacion',
    ];

    protected $casts = [
        'descarga_fecha'    => 'datetime',
        'descarga_cobrable' => 'boolean',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }
}
