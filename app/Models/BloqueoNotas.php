<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Bloqueo o exoneración manual de las notas de un estudiante en el portal de padres. */
class BloqueoNotas extends Model
{
    protected $table = 'estudiante_bloqueo_notas';
    protected $primaryKey = 'bloq_id';
    public $timestamps = false;

    protected $fillable = ['est_codigo', 'ges_anio', 'bloq_tipo', 'bloq_motivo', 'vigente_hasta', 'bloq_activo', 'bloq_usuario', 'bloq_fecha'];
    protected $casts = ['vigente_hasta' => 'date', 'bloq_fecha' => 'datetime'];

    public function estudiante() { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }

    public function scopeVigentes($q)
    {
        return $q->where('bloq_activo', 1)
            ->where(fn($w) => $w->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', now()->toDateString()));
    }
}
