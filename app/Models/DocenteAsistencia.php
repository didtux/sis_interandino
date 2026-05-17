<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteAsistencia extends Model
{
    protected $table = 'docente_asistencia';
    protected $primaryKey = 'dasist_id';
    public $timestamps = false;

    protected $fillable = [
        'doc_codigo','dasist_fecha','dasist_hora','dasist_tipo',
        'dasist_origen','dasist_observacion','dasist_registrado_por',
    ];

    protected $casts = ['dasist_fecha' => 'date'];

    public function docente() { return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo'); }
}
