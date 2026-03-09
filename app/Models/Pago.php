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
        'pagos_descuento', 'concepto', 'tipo', 'pagos_fecha', 'pagos_estado', 'pagos_sin_factura'
    ];

    protected $casts = [
        'pagos_fecha' => 'datetime',
        'pagos_precio' => 'float',
        'pagos_descuento' => 'float',
        'tipo' => 'integer',
        'pagos_sin_factura' => 'boolean'
    ];

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
