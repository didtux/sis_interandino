<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'ventas_categorias';
    protected $primaryKey = 'categ_id';
    public $timestamps = false;

    protected $fillable = [
        'categ_codigo', 'categ_nombre', 'categ_foto', 'categ_visible'
    ];

    protected $casts = [
        'categ_fecha' => 'datetime'
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categ_codigo', 'categ_codigo');
    }

    public function scopeVisible($query)
    {
        return $query->where('categ_visible', 1);
    }
}
