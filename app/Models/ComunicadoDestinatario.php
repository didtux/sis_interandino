<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComunicadoDestinatario extends Model
{
    protected $table = 'comunicados_destinatarios';
    protected $primaryKey = 'cd_id';
    public $timestamps = false;

    protected $fillable = [
        'com_id', 'doc_codigo', 'cd_archivo', 'cd_fecha_entrega', 'cd_estado', 'cd_observacion',
    ];

    protected $casts = [
        'cd_fecha_entrega' => 'datetime',
    ];

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class, 'com_id', 'com_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo');
    }

    /**
     * Clasifica la entrega respecto a la fecha límite del comunicado.
     * Devuelve: PENDIENTE | NO ENTREGÓ | EN FECHA | FUERA DE FECHA.
     */
    public function estadoEntrega(?\Carbon\Carbon $fechaLimite): string
    {
        if (!$this->cd_archivo && !$this->cd_fecha_entrega) {
            if ($fechaLimite && \Carbon\Carbon::today()->greaterThan($fechaLimite)) {
                return 'NO ENTREGÓ';
            }
            return 'PENDIENTE';
        }
        if ($fechaLimite && $this->cd_fecha_entrega && $this->cd_fecha_entrega->gt($fechaLimite->copy()->endOfDay())) {
            return 'FUERA DE FECHA';
        }
        return 'EN FECHA';
    }
}
