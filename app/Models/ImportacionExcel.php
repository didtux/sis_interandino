<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionExcel extends Model
{
    protected $table = 'notas_importaciones';
    protected $primaryKey = 'import_id';
    public $timestamps = false;

    protected $fillable = [
        'curmatdoc_id', 'periodo_id', 'import_tipo', 'import_data',
        'import_errores', 'import_resumen', 'import_estado',
        'import_usuario_id', 'import_archivo', 'import_fecha', 'import_fecha_confirmacion'
    ];

    protected $casts = [
        'import_data' => 'array',
        'import_errores' => 'array',
        'import_resumen' => 'array',
    ];

    public function cursoMateriaDocente()
    {
        return $this->belongsTo(CursoMateriaDocente::class, 'curmatdoc_id', 'curmatdoc_id');
    }

    public function periodo()
    {
        return $this->belongsTo(NotaPeriodo::class, 'periodo_id', 'periodo_id');
    }
}
