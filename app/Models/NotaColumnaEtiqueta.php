<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nombre que el docente le da a cada casillero de nota ("Examen parcial",
 * "Tema 1"…). Antes los encabezados eran "Nota 1..N" generados por un `for`,
 * así que ni el docente ni el padre sabían a qué correspondía cada columna.
 *
 * La etiqueta es por clase (curmatdoc_id) y por periodo: cada docente usa sus
 * columnas como quiere y las cambia de un trimestre al otro.
 */
class NotaColumnaEtiqueta extends Model
{
    protected $table = 'notas_columna_etiquetas';
    protected $primaryKey = 'colet_id';
    public $timestamps = false;

    protected $fillable = [
        'curmatdoc_id', 'periodo_id', 'dimension_id', 'columna_num',
        'colet_nombre', 'colet_usuario', 'colet_fecha',
    ];

    protected $casts = ['colet_fecha' => 'datetime'];

    /**
     * Etiquetas de una clase+periodo indexadas por "dimension_id-columna_num",
     * que es como las consultan las vistas.
     */
    public static function mapaDe($curmatdocId, $periodoId): array
    {
        return self::where('curmatdoc_id', $curmatdocId)
            ->where('periodo_id', $periodoId)
            ->get()
            ->mapWithKeys(fn($e) => [$e->dimension_id . '-' . $e->columna_num => $e->colet_nombre])
            ->all();
    }
}
