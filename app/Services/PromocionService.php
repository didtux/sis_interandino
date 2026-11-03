<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\EstudianteGestion;
use App\Models\NotaPeriodo;
use App\Models\Preinscripcion;

/**
 * Resultado de fin de año: PROMOVIDO, REPROBADO, EGRESADO o RETIRADO.
 *
 * Antes "aprobado/reprobado" no se guardaba en ningún lado: cada reporte lo
 * calculaba al imprimir. Acá se calcula con la misma fórmula del boletín
 * (BoletinNotasService: promedio anual de cada materia, redondeado) y queda en
 * estudiante_gestion. La regla de cuántas materias reprobadas hacen repetir el
 * año es un parámetro del colegio (sistema_parametros.reprobacion_materias).
 *
 * Los resultados corregidos a mano por dirección no se recalculan.
 */
class PromocionService
{
    private BoletinNotasService $boletin;

    public function __construct()
    {
        $this->boletin = new BoletinNotasService();
    }

    /**
     * Calcula el resultado de los estudiantes de una gestión (o de un curso).
     * Devuelve [actualizados, pendientes_por_notas, año_completo].
     */
    public function calcular(int $gestion, ?string $curCodigo = null): array
    {
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $notaMin  = (int) parametro('nota_minima_aprobacion', 51);
        $limite   = (int) parametro('reprobacion_materias', 3);

        $q = EstudianteGestion::where('ges_anio', $gestion)->where('eg_resultado_manual', 0);
        if ($curCodigo) $q->where('cur_codigo', $curCodigo);

        $visibles = Estudiante::pluck('est_visible', 'est_codigo');
        $actualizados = 0;
        $incompletos = 0;
        foreach ($q->get() as $eg) {
            $r = $this->resultadoDe($eg, $periodos, $notaMin, $limite, ($visibles[$eg->est_codigo] ?? 1) == 0);
            if ($r['incompleto']) $incompletos++;
            $eg->fill([
                'eg_resultado'           => $r['resultado'],
                'eg_promedio_final'      => $r['promedio'],
                'eg_materias_reprobadas' => $r['reprobadas'],
                'eg_resultado_fecha'     => now(),
            ])->save();
            $actualizados++;
        }

        return [$actualizados, $incompletos, $periodos->count()];
    }

    /** Resultado de un estudiante. Con trimestres sin notas queda PENDIENTE. */
    public function resultadoDe(EstudianteGestion $eg, $periodos, int $notaMin, int $limite, bool $retirado): array
    {
        if ($retirado || $eg->eg_estado === 'RETIRADO') {
            return ['resultado' => 'RETIRADO', 'promedio' => null, 'reprobadas' => null, 'incompleto' => false];
        }

        $filas = $this->boletin->notasEstudiante($eg->est_codigo, $eg->cur_codigo, $periodos);
        $conNota = collect($filas)->filter(fn($f) => $f['promedio'] > 0);
        // Año incompleto: alguna materia sin nota en algún trimestre configurado.
        $incompleto = $periodos->isEmpty() || collect($filas)->contains(
            fn($f) => count(array_filter($f['trimestres'], fn($v) => $v > 0)) < $periodos->count()
        );

        $reprobadas = $conNota->filter(fn($f) => $f['promedio'] < $notaMin)->count();
        $promedio = $conNota->count() ? round($conNota->avg('promedio'), 2) : null;

        if ($incompleto) {
            $resultado = 'PENDIENTE';
        } elseif ($reprobadas >= $limite) {
            $resultado = 'REPROBADO';
        } elseif (CursoProgresion::esUltimoGrado($eg->cur_codigo)) {
            $resultado = 'EGRESADO';
        } else {
            $resultado = 'PROMOVIDO';
        }

        return ['resultado' => $resultado, 'promedio' => $promedio, 'reprobadas' => $reprobadas, 'incompleto' => $incompleto];
    }

    /**
     * Lleva el resultado a las pre-inscripciones de la gestión siguiente:
     * PROMOVIDO → CONFIRMADA; REPROBADO → AJUSTAR con destino en el mismo grado
     * (y se avisa a la familia: sigue o pide la devolución). Las ya convertidas
     * en inscripción no se tocan.
     */
    public function aplicarAPreinscripciones(int $gestion): array
    {
        $cambios = ['CONFIRMADA' => 0, 'AJUSTAR' => 0];
        $resultados = EstudianteGestion::where('ges_anio', $gestion)->pluck('eg_resultado', 'est_codigo');
        $cursos = EstudianteGestion::where('ges_anio', $gestion)->pluck('cur_codigo', 'est_codigo');

        $pres = Preinscripcion::where('ges_anio', $gestion + 1)->where('preinsc_tipo', 'ANTIGUO')
            ->whereIn('preinsc_estado', ['RESERVADA', 'CONFIRMADA', 'AJUSTAR'])->get();
        foreach ($pres as $p) {
            $res = $resultados[$p->est_codigo] ?? null;
            if ($res === 'PROMOVIDO' && $p->preinsc_estado !== 'CONFIRMADA') {
                $p->preinsc_estado = 'CONFIRMADA';
                $p->cur_destino = CursoProgresion::siguiente($cursos[$p->est_codigo] ?? $p->cur_origen) ?: $p->cur_destino;
                $p->save();
                $cambios['CONFIRMADA']++;
            } elseif ($res === 'REPROBADO' && $p->preinsc_estado !== 'AJUSTAR') {
                $p->preinsc_estado = 'AJUSTAR';
                $p->cur_destino = $cursos[$p->est_codigo] ?? $p->cur_origen; // repite el grado
                $p->preinsc_obs = trim(($p->preinsc_obs ?? '') . "\nReprobó la gestión {$gestion}: el destino pasa al mismo grado. Confirmar con la familia (sigue o pide devolución).");
                $p->save();
                $cambios['AJUSTAR']++;
            }
        }
        return $cambios;
    }
}
