<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'ventas_productos';
    protected $primaryKey = 'prod_id';
    public $timestamps = false;

    protected $fillable = [
        'prod_codigo', 'prod_item', 'categ_codigo', 'prov_codigo', 'suc_codigo',
        'prod_nombre', 'prod_detalles', 'prod_cantidad',
        'prod_precioreal', 'prod_preciounitario', 'prod_preciodescuento', 'prod_visible'
    ];

    protected $casts = [
        'prod_fecha' => 'datetime',
        'prod_preciounitario' => 'float',
        'prod_preciodescuento' => 'float'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categ_codigo', 'categ_codigo');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'prov_codigo', 'prov_codigo');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlmacen::class, 'prod_codigo', 'prod_codigo');
    }

    public function scopeVisible($query)
    {
        return $query->where('prod_visible', 1);
    }
}
