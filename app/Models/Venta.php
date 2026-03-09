<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas_ventas';
    protected $primaryKey = 'ven_id';
    public $timestamps = false;

    protected $fillable = [
        'ven_codigo', 'prod_codigo', 'ven_cliente', 'ven_celular', 'ven_direccion',
        'venta_cantidad', 'venta_precio', 'venta_preciototal',
        'venta_estado', 'venta_tipo', 'venta_usuario'
    ];

    protected $casts = [
        'venta_fecha' => 'datetime',
        'venta_precio' => 'float',
        'venta_preciototal' => 'float'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'prod_codigo', 'prod_codigo');
    }
}
