<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración POR CURSO de una materia: a qué campo/área pertenece,
 * en qué orden aparece y si suma al promedio.
 *
 * Esto reemplaza al uso global de `colegio_materias.mat_campo / mat_promediable / mat_orden`
 * para los reportes de notas. Para vistas que aún no se migraron, se sigue cayendo a los
 * valores globales como fallback.
 */
class MateriaCurso extends Model
{
    protected $table = 'colegio_materia_curso';
    protected $primaryKey = 'matc_id';
    public $timestamps = false;

    protected $fillable = [
        'cur_codigo',
        'mat_codigo',
        'matc_campo',
        'matc_orden',
        'matc_promediable',
        'matc_estado',
    ];

    protected $casts = [
        'matc_orden'       => 'integer',
        'matc_promediable' => 'integer',
        'matc_estado'      => 'integer',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'mat_codigo', 'mat_codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo');
    }
}
