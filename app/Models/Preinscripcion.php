<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reserva de cupo para la gestión siguiente.
 *
 * Estados: RESERVADA (cobrada, condicional al resultado de este año) →
 * CONFIRMADA (aprobó) o AJUSTAR (reprobó: el destino vuelve al mismo grado y
 * hay que hablar con la familia) → CONVERTIDA (ya es inscripción). También
 * ANULADA (error de caja) o DEVUELTA (se devolvió la reserva).
 */
class Preinscripcion extends Model
{
    protected $table = 'preinscripciones';
    protected $primaryKey = 'preinsc_id';
    public $timestamps = false;

    protected $fillable = [
        'preinsc_codigo', 'ges_anio', 'preinsc_tipo', 'est_codigo', 'pre_nombres', 'pre_apellidos',
        'pre_ci', 'pre_fecha_nac', 'pre_ue_procedencia', 'pfam_codigo', 'cur_origen', 'cur_destino',
        'preinsc_condicional', 'preinsc_estado', 'monto_anual_ref', 'desc_id', 'monto_desc_ref',
        'insc_codigo', 'preinsc_obs', 'preinsc_usuario', 'preinsc_fecha',
    ];
    protected $casts = ['preinsc_fecha' => 'datetime', 'pre_fecha_nac' => 'date',
                        'monto_anual_ref' => 'float', 'monto_desc_ref' => 'float'];

    public const ESTADOS = [
        'RESERVADA'  => ['Reservada', 'info'],
        'CONFIRMADA' => ['Confirmada', 'success'],
        'AJUSTAR'    => ['Reprobó: ajustar curso', 'warning'],
        'CONVERTIDA' => ['Inscrita', 'primary'],
        'ANULADA'    => ['Anulada', 'secondary'],
        'DEVUELTA'   => ['Devuelta', 'dark'],
    ];

    public function estudiante()  { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }
    public function padre()       { return $this->belongsTo(PadreFamilia::class, 'pfam_codigo', 'pfam_codigo'); }
    public function cursoDestino(){ return $this->belongsTo(Curso::class, 'cur_destino', 'cur_codigo'); }
    public function cursoOrigen() { return $this->belongsTo(Curso::class, 'cur_origen', 'cur_codigo'); }
    public function descuento()   { return $this->belongsTo(Descuento::class, 'desc_id', 'desc_id'); }
    public function pagos()       { return $this->hasMany(PreinscripcionPago::class, 'preinsc_id', 'preinsc_id'); }

    public function getNombreCompletoAttribute(): string
    {
        if ($this->preinsc_tipo === 'ANTIGUO' && $this->estudiante) {
            return trim($this->estudiante->est_apellidos . ' ' . $this->estudiante->est_nombres);
        }
        return trim($this->pre_apellidos . ' ' . $this->pre_nombres);
    }

    /** Total de reserva vigente (pagos no anulados). */
    public function getMontoReservaAttribute(): float
    {
        return (float) $this->pagos->where('prepago_estado', 1)->sum('prepago_monto');
    }

    public function scopeVigentes($q)
    {
        return $q->whereIn('preinsc_estado', ['RESERVADA', 'CONFIRMADA', 'AJUSTAR']);
    }
}
