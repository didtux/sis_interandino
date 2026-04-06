<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaDetalle extends Model
{
    protected $table = 'colegio_notas_detalle';
    protected $primaryKey = 'detalle_id';
    public $timestamps = false;

    protected $fillable = ['nota_id', 'dimension_id', 'columna_num', 'detalle_valor'];

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id', 'nota_id');
    }

    public function dimension()
    {
        return $this->belongsTo(NotaDimension::class, 'dimension_id', 'dimension_id');
    }
}
