<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Devolución de dinero. Es un documento propio con su recibo (serie DEV): el
 * recibo original no se anula ni se toca. Anular es corregir un error de caja;
 * devolver es una salida de dinero, y como tal aparece en la caja del día en
 * que se paga.
 */
class Devolucion extends Model
{
    protected $table = 'devoluciones';
    protected $primaryKey = 'dev_id';
    public $timestamps = false;

    protected $fillable = [
        'dev_codigo', 'origen_tipo', 'recibo_origen', 'est_codigo', 'preinsc_id', 'pfam_codigo', 'ges_anio',
        'monto_original', 'monto_retenido', 'monto_devuelto', 'dev_motivo', 'dev_detalle', 'dev_metodo',
        'dev_estado', 'solicitado_por', 'solicitado_fecha', 'aprobado_por', 'aprobado_fecha',
        'pagado_por', 'pagado_fecha', 'rechazo_motivo',
    ];
    protected $casts = [
        'solicitado_fecha' => 'datetime', 'aprobado_fecha' => 'datetime', 'pagado_fecha' => 'datetime',
        'monto_original' => 'float', 'monto_retenido' => 'float', 'monto_devuelto' => 'float',
    ];

    public const MOTIVOS = [
        'REPROBADO' => 'El estudiante reprobó el año',
        'DESISTE'   => 'La familia desiste',
        'SIN_CUPO'  => 'No hay cupo',
        'DUPLICADO' => 'Cobro duplicado',
        'OTRO'      => 'Otro',
    ];
    public const ESTADOS = [
        'SOLICITADA' => ['Solicitada', 'warning'],
        'APROBADA'   => ['Aprobada', 'info'],
        'PAGADA'     => ['Pagada', 'success'],
        'RECHAZADA'  => ['Rechazada', 'secondary'],
    ];

    public function estudiante()     { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }
    public function preinscripcion() { return $this->belongsTo(Preinscripcion::class, 'preinsc_id', 'preinsc_id'); }
}
