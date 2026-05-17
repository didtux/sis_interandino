<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\NotaPeriodo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConcejoController extends Controller
{
    public function index(Request $request)
    {
        $cursos      = Curso::visible()->ordenado()->get();
        $cursoCod    = $request->input('curso');
        $buscar      = trim((string) $request->input('buscar', ''));
        $gestion     = (int) $request->input('gestion', date('Y'));
        $tab         = $request->input('tab', 'documentos');

        $estudiantes = collect();
        $mejores     = collect();
        $enRiesgo    = collect();

        if ($cursoCod) {
            $q = Estudiante::where('cur_codigo', $cursoCod);
            if ($buscar !== '') {
                // Búsqueda por tokens: cada palabra debe encontrarse en alguno de los campos del nombre
                $tokens = preg_split('/\s+/', $buscar, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $tok) {
                    $q->where(function($w) use ($tok) {
                        $w->where('est_nombres', 'like', "%{$tok}%")
                          ->orWhere('est_apellidos', 'like', "%{$tok}%")
                          ->orWhere('est_codigo', 'like', "%{$tok}%")
                          ->orWhere('est_ci', 'like', "%{$tok}%");
                    });
                }
            }
            $estudiantes = $q->orderBy('est_apellidos')->orderBy('est_nombres')->get();

            // Resumen por estudiante: promedio anual y métricas de riesgo
            $resumen = DB::select("
                SELECT e.est_codigo,
                       CONCAT(e.est_apellidos,' ',e.est_nombres) AS nombre,
                       e.est_visible,
                       ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio,
                       SUM(CASE WHEN n.nota_promedio_trimestral < 51 THEN 1 ELSE 0 END) AS materias_reprobadas,
                       COUNT(DISTINCT n.curmatdoc_id) AS materias_total
                FROM colegio_estudiantes e
                LEFT JOIN colegio_notas n ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci AND n.nota_estado = 2
                LEFT JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id AND p.periodo_gestion = ?
                WHERE e.cur_codigo = ?
                GROUP BY e.est_codigo, e.est_apellidos, e.est_nombres, e.est_visible
            ", [$gestion, $cursoCod]);

            // Métricas de asistencia y compromisos por estudiante
            $codigos = array_column($resumen, 'est_codigo');
            $atrasosByEst = DB::table('asistencia_atrasos')
                ->whereIn('estud_codigo', $codigos)
                ->whereYear('atraso_fecha', $gestion)
                ->select('estud_codigo', DB::raw('COUNT(*) as c'))
                ->groupBy('estud_codigo')->pluck('c', 'estud_codigo');
            $faltasByEst = DB::table('notas_asistencia_clases')
                ->whereIn('est_codigo', $codigos)
                ->where('asiscl_estado', 'F')
                ->whereYear('asiscl_fecha', $gestion)
                ->select('est_codigo', DB::raw('COUNT(*) as c'))
                ->groupBy('est_codigo')->pluck('c', 'est_codigo');
            $compEscritoByEst = DB::table('psicopedagogia_casos')
                ->whereIn('est_codigo', $codigos)
                ->where('psico_tipo_acuerdo', 'ESCRITO')
                ->whereYear('psico_fecha', $gestion)
                ->select('est_codigo', DB::raw('COUNT(*) as c'))
                ->groupBy('est_codigo')->pluck('c', 'est_codigo');
            $compVerbalByEst = DB::table('psicopedagogia_casos')
                ->whereIn('est_codigo', $codigos)
                ->where('psico_tipo_acuerdo', 'VERBAL')
                ->whereYear('psico_fecha', $gestion)
                ->select('est_codigo', DB::raw('COUNT(*) as c'))
                ->groupBy('est_codigo')->pluck('c', 'est_codigo');

            $listaResumen = collect($resumen)->map(function($r) use ($atrasosByEst, $faltasByEst, $compEscritoByEst, $compVerbalByEst) {
                $r->atrasos          = (int) ($atrasosByEst[$r->est_codigo] ?? 0);
                $r->faltas           = (int) ($faltasByEst[$r->est_codigo] ?? 0);
                $r->comp_escritos    = (int) ($compEscritoByEst[$r->est_codigo] ?? 0);
                $r->comp_verbales    = (int) ($compVerbalByEst[$r->est_codigo] ?? 0);
                $r->promedio         = $r->promedio !== null ? (float) $r->promedio : null;
                $r->materias_reprobadas = (int) $r->materias_reprobadas;
                $r->materias_total   = (int) $r->materias_total;

                // Score de riesgo: notas bajas + faltas + atrasos + compromisos escritos pesan más
                $score = 0;
                if ($r->promedio !== null && $r->promedio < 51) $score += 50;
                $score += $r->materias_reprobadas * 10;
                $score += min($r->faltas, 20);
                $score += min($r->atrasos, 20) * 0.5;
                $score += $r->comp_escritos * 5;
                $score += $r->comp_verbales * 2;
                $r->riesgo_score = round($score, 1);
                return $r;
            });

            // Solo activos para los rankings
            $activos = $listaResumen->where('est_visible', 1);

            $mejores = $activos->filter(fn($r) => $r->promedio !== null)
                ->sortByDesc('promedio')
                ->take(10)
                ->values();

            $enRiesgo = $activos->filter(function($r){
                return ($r->promedio !== null && $r->promedio < 51)
                    || $r->materias_reprobadas > 0
                    || $r->faltas >= 5
                    || $r->atrasos >= 5
                    || $r->comp_escritos > 0;
            })->sortByDesc('riesgo_score')->values();
        }

        return view('concejo.index', compact(
            'cursos','cursoCod','buscar','gestion','tab','estudiantes','mejores','enRiesgo'
        ));
    }

    public function documento($estCodigo, Request $request)
    {
        $gestion    = (int) $request->input('gestion', date('Y'));
        $config     = DB::table('sistema_configuracion')->first();
        $estudiante = Estudiante::with('curso','padres')->where('est_codigo', $estCodigo)->firstOrFail();
        $periodos   = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $notas = DB::select("
            SELECT m.mat_codigo, m.mat_nombre, m.mat_orden,
                   n.periodo_id, ROUND(n.nota_promedio_trimestral) AS prom
            FROM colegio_notas n
            JOIN colegio_curso_materia_docente cmd ON cmd.curmatdoc_id = n.curmatdoc_id
            JOIN colegio_materias m ON CONVERT(m.mat_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = cmd.mat_codigo COLLATE utf8mb4_unicode_ci
            WHERE n.est_codigo = ?
              AND n.nota_estado = 2
              AND n.periodo_id IN (".implode(',', $periodos->pluck('periodo_id')->all() ?: [0]).")
            ORDER BY m.mat_orden ASC
        ", [$estCodigo]);

        $matriz = [];
        foreach ($notas as $r) {
            $matriz[$r->mat_codigo]['nombre'] = $r->mat_nombre;
            $matriz[$r->mat_codigo]['per'][$r->periodo_id] = $r->prom;
        }

        // Asistencia anual — solo desde colegio_asistencia (no del registro de docentes).
        // Falta = día trabajado del curso donde el estudiante no fue presencia y no tenía permiso vigente.
        // Periodos disjuntos para evitar duplicados si la configuración los traslapa.
        $periodosOrden = $periodos->sortBy('periodo_fecha_inicio')->values();
        $rangos = [];
        $lastEnd = null;
        foreach ($periodosOrden as $p) {
            $ini = \Carbon\Carbon::parse($p->periodo_fecha_inicio);
            if ($lastEnd && $ini->lessThanOrEqualTo($lastEnd)) {
                $ini = $lastEnd->copy()->addDay();
            }
            $finP = \Carbon\Carbon::parse($p->periodo_fecha_fin);
            if ($finP->lessThan($ini)) continue;
            $rangos[] = ['inicio'=>$ini->format('Y-m-d'), 'fin'=>$finP->format('Y-m-d')];
            $lastEnd = $finP;
        }

        $atrasos = 0; $faltas = 0; $licencias = 0; $permisosSolicitudes = 0;
        $vistosFaltas = []; $vistosDiasPerm = []; $vistosPermisoCod = []; $vistosAtrasos = [];
        foreach ($rangos as $rg) {
            $inicio = $rg['inicio'];
            $fin    = $rg['fin'];

            $diasTrab = DB::table('colegio_asistencia')
                ->whereBetween('asis_fecha', [$inicio, $fin])
                ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
                ->select('asis_fecha')->distinct()->pluck('asis_fecha');

            $presencias = DB::table('colegio_asistencia')
                ->where('estud_codigo', $estCodigo)
                ->whereBetween('asis_fecha', [$inicio, $fin])
                ->select('asis_fecha')->distinct()->pluck('asis_fecha');

            $atrasosRows = DB::table('asistencia_atrasos')
                ->where('estud_codigo', $estCodigo)
                ->whereBetween('atraso_fecha', [$inicio, $fin])
                ->select('atraso_fecha','atraso_hora')->get();
            foreach ($atrasosRows as $a) {
                $key = (string)$a->atraso_fecha.'|'.(string)$a->atraso_hora;
                if (!isset($vistosAtrasos[$key])) { $vistosAtrasos[$key] = true; $atrasos++; }
            }

            // Permisos solicitudes: cuenta solo los que arrancan en este periodo
            $permisosNuevos = DB::table('asistencia_permisos')
                ->where('estud_codigo', $estCodigo)
                ->where('permiso_estado', 1)
                ->whereBetween('permiso_fecha_inicio', [$inicio, $fin])
                ->select('permiso_codigo')->get();
            foreach ($permisosNuevos as $p) {
                if (!isset($vistosPermisoCod[$p->permiso_codigo])) {
                    $vistosPermisoCod[$p->permiso_codigo] = true;
                    $permisosSolicitudes++;
                }
            }

            // Cobertura de días con permiso (permisos que se traslapan con este periodo)
            $permisos = DB::table('asistencia_permisos')
                ->where('estud_codigo', $estCodigo)
                ->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fin)
                ->where('permiso_fecha_fin', '>=', $inicio)
                ->select('permiso_fecha_inicio','permiso_fecha_fin')->get();

            $diasTrabSet = collect($diasTrab)->map(fn($f)=>(string)$f)->flip();
            foreach ($permisos as $p) {
                $cur = \Carbon\Carbon::parse(max($p->permiso_fecha_inicio, $inicio));
                $end = \Carbon\Carbon::parse(min($p->permiso_fecha_fin, $fin));
                while ($cur <= $end) {
                    $f = $cur->format('Y-m-d');
                    if ($diasTrabSet->has($f) && !isset($vistosDiasPerm[$f])) {
                        $vistosDiasPerm[$f] = true;
                        $licencias++;
                    }
                    $cur->addDay();
                }
            }

            $presSet = collect($presencias)->map(fn($f)=>(string)$f)->flip();
            foreach ($diasTrab as $f) {
                $f = (string) $f;
                if (isset($vistosFaltas[$f])) continue;
                if ($presSet->has($f) || isset($vistosDiasPerm[$f])) continue;
                $dow = (int) date('w', strtotime($f));
                if ($dow === 0 || $dow === 6) continue;
                $vistosFaltas[$f] = true;
                $faltas++;
            }
        }
        $enfermeria = DB::table('enfermeria_registros')->where('est_codigo', $estCodigo)->whereYear('enf_fecha', $gestion)->count();
        $compromisosVerb   = DB::table('psicopedagogia_casos')->where('est_codigo', $estCodigo)->where('psico_tipo_acuerdo', 'VERBAL')->whereYear('psico_fecha', $gestion)->count();
        $compromisosEscrit = DB::table('psicopedagogia_casos')->where('est_codigo', $estCodigo)->where('psico_tipo_acuerdo', 'ESCRITO')->whereYear('psico_fecha', $gestion)->count();

        $pdf = Pdf::loadView('concejo.documento-pdf', compact(
            'estudiante','periodos','matriz','config','gestion',
            'atrasos','faltas','licencias','enfermeria','compromisosVerb','compromisosEscrit'
        ))->setPaper('letter');

        return $pdf->stream('concejo-'.$estCodigo.'.pdf');
    }

    public function detalle($estCodigo, Request $request)
    {
        $gestion    = (int) $request->input('gestion', date('Y'));
        $estudiante = Estudiante::with('curso')->where('est_codigo', $estCodigo)->firstOrFail();
        $periodos   = NotaPeriodo::activo()->gestion($gestion)
            ->orderBy('periodo_numero')->get();

        $service = new \App\Services\AsistenciaResumenService();
        $resumenPorTrim = $service->resumenPorTrimestre($estCodigo, $periodos);

        $detallePeriodos = [];
        $totales = ['dias_trabajados'=>0,'presencias'=>0,'faltas'=>0,'permisos_dias'=>0,'permisos_solicitudes'=>0,'atrasos'=>0];

        foreach ($resumenPorTrim as $pn => $r) {
            $detallePeriodos[] = [
                'periodo'           => $r['periodo'],
                'dias_trabajados'   => $r['fechas_presencia']->concat($r['fechas_faltas'])->concat($r['fechas_dias_con_permiso'])->unique()->values(),
                'presencias'        => $r['fechas_presencia'],
                'atrasos'           => $r['fechas_atrasos'],
                'permisos'          => $r['permisos'],
                'dias_con_permiso'  => $r['fechas_dias_con_permiso'],
                'faltas'            => $r['fechas_faltas'],
            ];

            $totales['dias_trabajados']     += $r['dias_trabajados_curso'];
            $totales['presencias']          += $r['presencias'];
            $totales['faltas']              += $r['faltas'];
            $totales['permisos_dias']       += $r['licencias_dias'];
            $totales['permisos_solicitudes']+= $r['licencias_solicitudes'];
            $totales['atrasos']             += $r['atrasos'];
        }

        return view('concejo.detalle', compact('estudiante','periodos','detallePeriodos','totales','gestion'));
    }
}
