<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteKardex extends Model
{
    protected $table = 'docente_kardex';
    protected $primaryKey = 'kdx_id';
    public $timestamps = false;

    protected $fillable = [
        'doc_codigo','kdx_tipo_documento','kdx_titulo','kdx_descripcion',
        'kdx_fecha_solicitud','kdx_fecha_entrega','kdx_fecha_recibido',
        'kdx_estado','kdx_archivo','kdx_creado_por','kdx_creado_fecha','kdx_observacion',
    ];

    protected $casts = [
        'kdx_fecha_solicitud' => 'date',
        'kdx_fecha_entrega'   => 'date',
        'kdx_fecha_recibido'  => 'date',
        'kdx_creado_fecha'    => 'datetime',
    ];

    public function docente() { return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo'); }
}
