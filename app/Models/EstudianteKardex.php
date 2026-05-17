<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteKardex extends Model
{
    protected $table = 'estudiante_kardex';
    protected $primaryKey = 'ek_id';

    protected $fillable = [
        'est_codigo','cur_codigo','ek_fecha','ek_tipo','ek_categoria',
        'ek_titulo','ek_descripcion','ek_acuerdo','ek_archivo',
        'ek_visible_padre','ek_visto_padre','ek_visto_padre_at',
        'doc_codigo','mat_codigo','ek_registrado_por','ek_estado',
    ];

    protected $casts = [
        'ek_fecha'          => 'date',
        'ek_visto_padre_at' => 'datetime',
        'ek_visible_padre'  => 'boolean',
        'ek_visto_padre'    => 'boolean',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'doc_codigo', 'doc_codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }
}
