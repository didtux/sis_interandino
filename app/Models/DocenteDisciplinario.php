<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteDisciplinario extends Model
{
    protected $table = 'docente_disciplinario';
    protected $primaryKey = 'disc_id';
    public $timestamps = false;

    protected $fillable = [
        'doc_codigo','disc_fecha','disc_tipo','disc_gravedad',
        'disc_descripcion','disc_evidencia','disc_registrado_por','disc_registrado_fecha',
    ];

    protected $casts = [
        'disc_fecha'            => 'date',
        'disc_registrado_fecha' => 'datetime',
    ];

    public function docente() { return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo'); }
}
