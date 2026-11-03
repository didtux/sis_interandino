<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lo que cambia de un año a otro para un estudiante: curso, inscripción y
 * resultado (PROMOVIDO / REPROBADO / …). La ficha del estudiante guarda sólo
 * el curso actual; los reportes de una gestión pasada leen el curso de acá.
 */
class EstudianteGestion extends Model
{
    protected $table = 'estudiante_gestion';
    protected $primaryKey = 'eg_id';
    public $timestamps = false;

    protected $fillable = [
        'est_codigo', 'ges_anio', 'cur_codigo', 'insc_codigo', 'eg_estado', 'eg_resultado',
        'eg_promedio_final', 'eg_materias_reprobadas', 'eg_resultado_manual', 'eg_resultado_motivo',
        'eg_resultado_por', 'eg_resultado_fecha',
    ];
    protected $casts = ['eg_resultado_fecha' => 'datetime', 'eg_promedio_final' => 'float'];

    public const RESULTADOS = ['PENDIENTE', 'PROMOVIDO', 'REPROBADO', 'RETIRADO', 'EGRESADO'];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    /** Curso del estudiante en una gestión (historial; si no hay, el actual). */
    public static function cursoDe(string $estCodigo, int $gestion): ?string
    {
        return self::where('est_codigo', $estCodigo)->where('ges_anio', $gestion)->value('cur_codigo')
            ?: Estudiante::where('est_codigo', $estCodigo)->value('cur_codigo');
    }

    /**
     * Estudiantes que cursaron un curso en una gestión. Para la gestión vigente
     * es la nómina actual (colegio_estudiantes.cur_codigo, como siempre); para
     * una pasada sale del historial, porque al pasar de año la ficha ya apunta
     * al curso nuevo y el reporte de 2026 mostraría a los alumnos de 2027.
     */
    public static function estudiantesDeCurso(string $curCodigo, int $gestion)
    {
        if ($gestion === gestion_actual() || !self::where('ges_anio', $gestion)->exists()) {
            return Estudiante::where('cur_codigo', $curCodigo)->get();
        }
        $codigos = self::where('ges_anio', $gestion)->where('cur_codigo', $curCodigo)->pluck('est_codigo');
        return Estudiante::whereIn('est_codigo', $codigos)->get();
    }

    /** Alta o actualización del curso/inscripción de un estudiante en una gestión. */
    public static function registrar(string $estCodigo, int $gestion, string $curCodigo, ?string $inscCodigo): self
    {
        $eg = self::firstOrNew(['est_codigo' => $estCodigo, 'ges_anio' => $gestion]);
        $eg->cur_codigo = $curCodigo;
        if ($inscCodigo) $eg->insc_codigo = $inscCodigo;
        if (!$eg->exists) {
            $eg->eg_estado = 'INSCRITO';
            $eg->eg_resultado = 'PENDIENTE';
        }
        $eg->save();
        return $eg;
    }
}
