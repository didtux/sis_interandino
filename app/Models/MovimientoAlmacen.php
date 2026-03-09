<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoAlmacen extends Model
{
    protected $table = 'ventas_movimientos';
    protected $primaryKey = 'mov_id';
    public $timestamps = false;

    protected $fillable = [
        'mov_codigo', 'prod_codigo', 'prov_codigo', 'mov_tipo',
        'mov_cantidad', 'mov_precio_unitario', 'mov_precio_total',
        'mov_motivo', 'mov_observacion', 'mov_usuario'
    ];

    protected $casts = [
        'mov_fecha' => 'datetime',
        'mov_precio_unitario' => 'float',
        'mov_precio_total' => 'float'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'prod_codigo', 'prod_codigo');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'prov_codigo', 'prov_codigo');
    }

    public function scopeEntradas($query)
    {
        return $query->where('mov_tipo', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('mov_tipo', 'salida');
    }
}
