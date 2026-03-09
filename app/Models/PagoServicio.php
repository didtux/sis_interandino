<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoServicio extends Model
{
    protected $table = 'pagos_servicios';
    protected $primaryKey = 'pserv_id';
    public $timestamps = false;

    protected $fillable = [
        'pserv_codigo', 'serv_codigo', 'est_codigo', 'pfam_codigo',
        'pserv_monto', 'pserv_descuento', 'pserv_total',
        'pserv_observacion', 'pserv_usuario', 'pserv_estado'
    ];

    protected $casts = [
        'pserv_monto' => 'float',
        'pserv_descuento' => 'float',
        'pserv_total' => 'float',
        'pserv_fecha' => 'datetime'
    ];

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'serv_codigo', 'serv_codigo');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function padreFamilia()
    {
        return $this->belongsTo(PadreFamilia::class, 'pfam_codigo', 'pfam_codigo');
    }
}
