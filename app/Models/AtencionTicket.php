<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Consulta de un padre a un área del colegio (canal de atención). */
class AtencionTicket extends Model
{
    protected $table = 'atencion_tickets';
    protected $primaryKey = 'at_id';
    public $timestamps = false;

    protected $fillable = [
        'at_area', 'at_asunto', 'us_id', 'pfam_codigo', 'est_codigo', 'cur_codigo', 'at_estado',
        'at_ultimo_mensaje', 'at_cerrado_por', 'at_cerrado_fecha', 'at_fecha',
    ];
    protected $casts = ['at_ultimo_mensaje' => 'datetime', 'at_cerrado_fecha' => 'datetime', 'at_fecha' => 'datetime'];

    public const ESTADOS = ['ABIERTO' => ['Pendiente', 'warning'], 'RESPONDIDO' => ['Respondida', 'info'], 'CERRADO' => ['Cerrada', 'secondary']];

    public function mensajes() { return $this->hasMany(AtencionMensaje::class, 'at_id', 'at_id')->orderBy('am_id'); }
    public function estudiante() { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }
    public function padre() { return $this->belongsTo(PadreFamilia::class, 'pfam_codigo', 'pfam_codigo'); }
    public function usuario() { return $this->belongsTo(User::class, 'us_id', 'us_id'); }

    public function getAreaNombreAttribute(): string
    {
        return config("atencion.areas.{$this->at_area}.nombre", $this->at_area);
    }
}
