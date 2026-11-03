<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos_mensualidades';
    protected $primaryKey = 'pagos_id';
    public $timestamps = false;

    protected $fillable = [
        'pagos_codigo', 'men_codigo', 'est_codigo', 'pfam_codigo', 'prod_codigo',
        'pagos_precio', 'pagos_nombres', 'pagos_usuario',
        'pagos_descuento', 'concepto', 'tipo', 'pagos_fecha', 'pagos_estado', 'pagos_sin_factura',
        // Método de pago / comprobante / transferencia
        'pagos_metodo', 'pagos_monto_efectivo', 'pagos_monto_qr', 'pagos_comprobante',
        'pagos_transferencia_nro', 'pagos_transferencia_hora', 'pagos_recibo_nro', 'pagos_via_whatsapp',
        // Gestión y mes a los que corresponde la cuota (antes se deducían de la
        // fecha y del texto del concepto) y registro de la anulación.
        'pagos_gestion', 'pagos_mes', 'insc_codigo', 'anulado_por', 'anulado_fecha', 'anulado_motivo',
    ];

    protected $casts = [
        'pagos_fecha' => 'datetime',
        'pagos_precio' => 'float',
        'pagos_descuento' => 'float',
        'tipo' => 'integer',
        'pagos_sin_factura' => 'boolean',
        'pagos_gestion' => 'integer',
        'pagos_mes' => 'integer',
        'anulado_fecha' => 'datetime',
    ];

    /**
     * Pagos de una gestión. Usa pagos_gestion; las filas sin ese dato (cargadas
     * antes de upgrade_2026_11_pagos_gestion_mes.sql) caen al año de la fecha,
     * que era el criterio anterior.
     */
    public function scopeDeGestion($query, $gestion)
    {
        return $query->where(function ($q) use ($gestion) {
            $q->where('pagos_gestion', $gestion)
              ->orWhere(function ($q2) use ($gestion) {
                  $q2->whereNull('pagos_gestion')->whereYear('pagos_fecha', $gestion);
              });
        });
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function padreFamilia()
    {
        return $this->belongsTo(PadreFamilia::class, 'pfam_codigo', 'pfam_codigo');
    }

    public function getMesCorrespondienteAttribute()
    {
        if ($this->pagos_mes) return (int) $this->pagos_mes;

        $meses = [
            'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6,
            'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11
        ];
        
        foreach ($meses as $nombre => $numero) {
            if (stripos($this->concepto, $nombre) !== false) {
                return $numero;
            }
        }
        
        // Si el pago se hizo en enero, asignar a febrero
        if ($this->pagos_fecha && $this->pagos_fecha->month == 1) {
            return 2;
        }
        
        // Si no encuentra mes en concepto, usar mes de pagos_fecha ajustado al rango 2-11
        $mes = $this->pagos_fecha ? $this->pagos_fecha->month : null;
        if ($mes && $mes >= 2 && $mes <= 11) {
            return $mes;
        }
        
        return null;
    }
    
    public function getEsPagoMultipleMesesAttribute()
    {
        $conceptoGenerico = in_array(strtolower(trim($this->concepto)), ['mensualidades', 'mensualidad', 'pago mensualidades']);
        $montoAlto = $this->pagos_precio >= 1000;
        
        return $conceptoGenerico && $montoAlto;
    }
    
    public function getMesesCubiertosAttribute()
    {
        if (!$this->es_pago_multiple_meses) {
            $mes = $this->mes_correspondiente;
            return $mes ? [$mes] : [];
        }
        
        // Calcular meses cubiertos basado en monto (475 Bs por mes)
        $cantidadMeses = round($this->pagos_precio / 475);
        $mesInicio = $this->pagos_fecha->month >= 2 && $this->pagos_fecha->month <= 11 ? $this->pagos_fecha->month : 2;
        
        $meses = [];
        for ($i = 0; $i < $cantidadMeses && ($mesInicio + $i) <= 11; $i++) {
            $meses[] = $mesInicio + $i;
        }
        
        return $meses;
    }
}
