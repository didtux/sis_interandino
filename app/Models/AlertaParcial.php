<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaParcial extends Model
{
    protected $table = 'nota_alerta_parcial';
    protected $primaryKey = 'alerta_id';
    public $timestamps = false;

    protected $fillable = [
        'est_codigo','mat_codigo','cur_codigo','periodo_id','alerta_gestion',
        'marcado_docente','marcado_docente_por','marcado_docente_nombre','marcado_docente_fecha',
        'marcado_director','marcado_director_por','marcado_director_nombre','marcado_director_fecha',
        'alerta_observacion',
    ];

    protected $casts = [
        'marcado_docente_fecha'  => 'datetime',
        'marcado_director_fecha' => 'datetime',
        'marcado_docente'        => 'integer',
        'marcado_director'       => 'integer',
    ];

    public function estudiante() { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }
    public function materia()    { return $this->belongsTo(Materia::class,    'mat_codigo', 'mat_codigo'); }
    public function curso()      { return $this->belongsTo(Curso::class,      'cur_codigo', 'cur_codigo'); }
    public function periodo()    { return $this->belongsTo(NotaPeriodo::class,'periodo_id', 'periodo_id'); }
}
