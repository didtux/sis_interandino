<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comunicado extends Model
{
    protected $table = 'comunicados_docentes';
    protected $primaryKey = 'com_id';
    public $timestamps = false;

    protected $fillable = [
        'com_titulo', 'com_descripcion', 'com_fecha_limite', 'com_requiere_archivo',
        'com_archivo', 'com_para_todos', 'com_creado_por', 'com_creado_por_nombre',
        'com_fecha', 'com_estado', 'com_motivo_anulacion',
    ];

    protected $casts = [
        'com_fecha_limite' => 'date',
        'com_fecha'        => 'datetime',
        'com_estado'       => 'integer',
        'com_para_todos'   => 'integer',
    ];

    public function destinatarios()
    {
        return $this->hasMany(ComunicadoDestinatario::class, 'com_id', 'com_id');
    }
}
