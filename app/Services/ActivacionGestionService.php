<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\EstudianteGestion;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\ListaCurso;
use App\Models\NotaDimension;
use App\Models\NotaPeriodo;
use App\Models\Preinscripcion;
use App\Models\Tarifa;
use App\Support\GestionActual;
use Illuminate\Support\Facades\DB;

/**
 * Paso a la gestión siguiente (asistente de activación).
 *
 * Hasta ahora "Activar" una gestión sólo cambiaba una bandera que nadie leía;
 * el sistema pasaba de año solo el 1 de enero y nadie movía a los estudiantes
 * de curso. Ahora el paso de año lo decide el colegio y hace, en orden:
 *   1. crea las fichas de los alumnos NUEVOS pre-inscritos y los inscribe;
 *   2. a cada inscrito en la gestión nueva le pone el curso de su inscripción;
 *   3. (opcional) marca como no visibles a los de la gestión anterior que no
 *      se reinscribieron — siguen pudiendo inscribirse tarde;
 *   4. copia periodos y dimensiones de notas si la gestión nueva no tiene;
 *   5. genera las listas de curso de la gestión nueva;
 *   6. cambia la gestión vigente.
 * Todo en una transacción: si algo falla, no cambia nada.
 */
class ActivacionGestionService
{
    /** Estado de cada punto de control antes de activar. */
    public function checklist(int $nueva): array
    {
        $anterior = gestion_actual();
        $inscritos = Inscripcion::where('insc_gestion', $nueva)->whereIn('insc_estado', [1, 2])->count();
        $eg = EstudianteGestion::where('ges_anio', $anterior);
        $pendientes = (clone $eg)->where('eg_resultado', 'PENDIENTE')->where('eg_estado', 'INSCRITO')->count();
        $preSinConvertir = Preinscripcion::where('ges_anio', $nueva)->where('preinsc_tipo', 'ANTIGUO')->vigentes()->count();
        $nuevos = Preinscripcion::where('ges_anio', $nueva)->where('preinsc_tipo', 'NUEVO')->vigentes()->count();
        $sinReinscribir = (clone $eg)->where('eg_estado', 'INSCRITO')
            ->whereNotIn('est_codigo', Inscripcion::where('insc_gestion', $nueva)->whereIn('insc_estado', [1, 2])->pluck('est_codigo'))
            ->whereNotIn('eg_resultado', ['EGRESADO', 'RETIRADO'])->count();

        return [
            'anterior'         => $anterior,
            'nueva'            => $nueva,
            'inscritos'        => $inscritos,
            'resultados_pend'  => $pendientes,
            'pre_sin_convertir'=> $preSinConvertir,
            'nuevos'           => $nuevos,
            'sin_reinscribir'  => $sinReinscribir,
            'periodos'         => NotaPeriodo::where('periodo_gestion', $nueva)->count(),
            'dimensiones'      => NotaDimension::where('dimension_gestion', $nueva)->count(),
            'tarifas'          => Tarifa::where('ges_anio', $nueva)->count(),
        ];
    }

    /** Ejecuta el paso de año. Devuelve un resumen de lo hecho. */
    public function ejecutar(int $nueva, bool $ocultarNoReinscritos, int $usuarioId): array
    {
        $anterior = gestion_actual();
        if ($nueva <= $anterior) {
            throw new \RuntimeException("La gestión {$nueva} no es posterior a la vigente ({$anterior}).");
        }
        $gestionNueva = Gestion::where('ges_anio', $nueva)->firstOrFail();

        $r = DB::transaction(function () use ($nueva, $anterior, $ocultarNoReinscritos, $usuarioId, $gestionNueva) {
            $r = ['nuevos_creados' => 0, 'movidos' => 0, 'ocultados' => 0, 'periodos' => 0, 'dimensiones' => 0, 'listas' => 0];

            // 1. Alumnos nuevos pre-inscritos: ficha + inscripción.
            $pre = new PreinscripcionService();
            foreach (Preinscripcion::where('ges_anio', $nueva)->where('preinsc_tipo', 'NUEVO')->vigentes()->get() as $p) {
                $est = Estudiante::create([
                    'est_codigo'        => Estudiante::siguienteCodigo(),
                    'cur_codigo'        => $p->cur_destino,
                    'est_nombres'       => $p->pre_nombres,
                    'est_apellidos'     => $p->pre_apellidos,
                    'est_ci'            => $p->pre_ci,
                    'est_fechanac'      => $p->pre_fecha_nac,
                    'est_ueprocedencia' => $p->pre_ue_procedencia,
                    'est_fecha'         => now(),
                    'est_visible'       => 1,
                    'preinscripcion'    => $p->load('pagos')->monto_reserva,
                ]);
                if ($p->pfam_codigo) {
                    optional(\App\Models\PadreFamilia::where('pfam_codigo', $p->pfam_codigo)->first())->vincularHijo($est->est_codigo);
                }
                $p->update(['est_codigo' => $est->est_codigo]);
                $pre->convertir($p->fresh(), $usuarioId);
                $r['nuevos_creados']++;
            }

            // 2. Inscritos en la gestión nueva: curso de su inscripción.
            $inscritos = Inscripcion::where('insc_gestion', $nueva)->whereIn('insc_estado', [1, 2])->get();
            foreach ($inscritos as $i) {
                Estudiante::where('est_codigo', $i->est_codigo)->update(['cur_codigo' => $i->cur_codigo, 'est_visible' => 1]);
                EstudianteGestion::registrar($i->est_codigo, $nueva, $i->cur_codigo, $i->insc_codigo);
                $r['movidos']++;
            }

            // 3. No reinscritos (opcional).
            if ($ocultarNoReinscritos) {
                $conInsc = $inscritos->pluck('est_codigo')->all();
                $r['ocultados'] = Estudiante::where('est_visible', 1)
                    ->whereIn('est_codigo', EstudianteGestion::where('ges_anio', $anterior)->pluck('est_codigo'))
                    ->whereNotIn('est_codigo', $conInsc)
                    ->update(['est_visible' => 0]);
            }

            // 4. Periodos y dimensiones de notas, copiados de la gestión anterior.
            if (!NotaPeriodo::where('periodo_gestion', $nueva)->exists()) {
                foreach (NotaPeriodo::where('periodo_gestion', $anterior)->get() as $p) {
                    $n = $p->replicate();
                    $n->periodo_gestion = $nueva;
                    $n->periodo_fecha_inicio = $p->periodo_fecha_inicio->copy()->addYear();
                    $n->periodo_fecha_fin = $p->periodo_fecha_fin->copy()->addYear();
                    $n->save();
                    $r['periodos']++;
                }
            }
            if (!NotaDimension::where('dimension_gestion', $nueva)->exists()) {
                foreach (NotaDimension::where('dimension_gestion', $anterior)->get() as $d) {
                    $n = $d->replicate();
                    $n->dimension_gestion = $nueva;
                    $n->save();
                    $r['dimensiones']++;
                }
            }

            // 5. Listas de curso de la gestión nueva (alfabéticas; se pueden reordenar).
            foreach ($inscritos->groupBy('cur_codigo') as $cur => $grupo) {
                if (ListaCurso::where('cur_codigo', $cur)->where('lista_gestion', $nueva)->exists()) continue;
                $ests = Estudiante::whereIn('est_codigo', $grupo->pluck('est_codigo'))
                    ->orderBy('est_apellidos')->orderBy('est_nombres')->pluck('est_codigo');
                $num = 1;
                foreach ($ests as $e) {
                    ListaCurso::create(['cur_codigo' => $cur, 'est_codigo' => $e, 'lista_numero' => $num++, 'lista_gestion' => $nueva]);
                }
                $r['listas']++;
            }

            // 6. Cambio de gestión vigente.
            DB::table('colegio_gestiones')->update(['ges_estado' => 0]);
            $gestionNueva->update(['ges_estado' => 1, 'ges_inscripcion_abierta' => 0]);

            \App\Models\Auditoria::registrar('editar', 'GESTION_ACTIVAR',
                "Paso de la gestión {$anterior} a {$nueva}: " . json_encode($r), $gestionNueva->ges_id, null, $r);
            return $r;
        });

        GestionActual::olvidar();
        return $r;
    }
}
