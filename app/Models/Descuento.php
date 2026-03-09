<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    protected $table = 'descuentos';
    protected $primaryKey = 'desc_id';
    public $timestamps = false;

    protected $fillable = [
        'desc_codigo', 'desc_nombre', 'desc_porcentaje', 'desc_estado'
    ];

    protected $casts = [
        'desc_porcentaje' => 'float',
        'desc_estado' => 'integer',
        'desc_fecha_registro' => 'datetime'
    ];

    public function inscripciones()
    {
        return $this->belongsToMany(Inscripcion::class, 'inscripciones_descuentos', 'desc_id', 'insc_id')
            ->withPivot('inscdesc_monto_descuento', 'inscdesc_fecha');
    }
}
