<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'ventas_proveedores';
    protected $primaryKey = 'prov_id';
    public $timestamps = false;

    protected $fillable = [
        'prov_codigo', 'prov_nombre', 'prov_razon_social', 'prov_nit',
        'prov_telefono', 'prov_email', 'prov_direccion', 'prov_contacto',
        'prov_estado', 'prov_usuario_registro'
    ];

    protected $casts = [
        'prov_fecha_registro' => 'datetime'
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'prov_codigo', 'prov_codigo');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlmacen::class, 'prov_codigo', 'prov_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('prov_estado', 1);
    }
}
