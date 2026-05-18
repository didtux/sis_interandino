<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletinDescarga extends Model
{
    protected $table = 'boletines_descargas';
    protected $primaryKey = 'descarga_id';
    public $timestamps = false;

    protected $fillable = [
        'descarga_token', 'est_codigo', 'descarga_gestion', 'descarga_trimestre',
        'descargado_por', 'descargado_por_nombre', 'descarga_fecha',
        'descarga_ip', 'descarga_user_agent',
        'descarga_numero_copia', 'descarga_cobrable',
        'descarga_servicio_id', 'descarga_observacion',
        'descarga_anulada', 'descarga_anulada_motivo',
        'descarga_anulada_por', 'descarga_anulada_at',
        'pserv_id_cobro',
    ];

    protected $casts = [
        'descarga_fecha'        => 'datetime',
        'descarga_cobrable'     => 'boolean',
        'descarga_anulada'      => 'boolean',
        'descarga_anulada_at'   => 'datetime',
    ];

    public function pagoServicio()
    {
        return $this->belongsTo(PagoServicio::class, 'pserv_id_cobro', 'pserv_id');
    }

    public function usuarioDescargo()
    {
        return $this->belongsTo(User::class, 'descargado_por', 'us_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }
}
