<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Permiso;
use App\Models\ConfiguracionAsistencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Reportes Excel del módulo de Licencias (permisos justificados).
 *
 *  1) Mensual: matriz días (L-V) × curso — "FALTAS CON LICENCIA".
 *  2) Anual por estudiante de un curso: meses × estudiante.
 *  3) Anual general por curso: meses × curso.
 *
 * Una "licencia" cuenta cuando un estudiante tiene un permiso activo
 * (asistencia_permisos, permiso_estado=1) que cubre ese día hábil (L-V).
 * Los reportes pueden separarse por turno (config_turno).
 */
class ReporteLicenciaController extends Controller
{
    private array $mesesNombre = [
        2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
        7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE',
        11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];
    private array $diaLetra = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V'];

    // ───────────────────────────────────────────────────────────────
    // Helpers de datos
    // ───────────────────────────────────────────────────────────────

    /** Cursos visibles, opcionalmente acotados al turno (config_id). */
    private function cursosDelTurno($turnoConfigId = null)
    {
        $q = Curso::visible()->orderBy('cur_orden')->orderBy('cur_nombre');

        if ($turnoConfigId) {
            $cfg = ConfiguracionAsistencia::find($turnoConfigId);
            // Cursos por pivote; si no hay pivote, por categoría = nivel.
            $pivote = DB::table('asistencia_configuracion_cursos')
                ->where('config_id', $turnoConfigId)->pluck('cur_codigo')->all();
            if (!empty($pivote)) {
                $q->whereIn('cur_codigo', $pivote);
            } elseif ($cfg && $cfg->config_categoria) {
                $q->where('cur_nivel', $cfg->config_categoria);
            }
        }
        return $q->get();
    }

    /** Mapa est_codigo => cur_codigo (curso actual del estudiante). */
    private function mapaEstudianteCurso(): \Illuminate\Support\Collection
    {
        return Estudiante::where('est_visible', 1)->pluck('cur_codigo', 'est_codigo');
    }

    /**
     * Permisos activos que se traslapan con [inicio, fin].
     * @return \Illuminate\Support\Collection
     */
    private function permisosEnRango(string $inicio, string $fin)
    {
        return Permiso::where('permiso_estado', 1)
            ->whereDate('permiso_fecha_inicio', '<=', $fin)
            ->whereDate('permiso_fecha_fin', '>=', $inicio)
            ->get(['estud_codigo', 'permiso_fecha_inicio', 'permiso_fecha_fin']);
    }

    /** Festivos (tipo 1 y 2) en el rango => [Y-m-d => nombre]. */
    private function festivosEnRango(string $inicio, string $fin): array
    {
        return DB::table('asistencia_fechas_festivas')
            ->where('festivo_estado', 1)
            ->whereBetween('festivo_fecha', [$inicio, $fin])
            ->pluck('festivo_nombre', 'festivo_fecha')
            ->mapWithKeys(fn($n, $f) => [(string) $f => $n])
            ->all();
    }

    // ───────────────────────────────────────────────────────────────
    // 1) Reporte MENSUAL — matriz días × curso
    // ───────────────────────────────────────────────────────────────
    public function mensualExcel(Request $request)
    {
        $gestion = (int) $request->input('gestion', date('Y'));
        $mes     = (int) $request->input('mes', date('n'));
        $turno   = $request->input('turno');

        if (!isset($this->mesesNombre[$mes])) $mes = max(2, min(12, $mes));

        $inicioMes = Carbon::create($gestion, $mes, 1)->startOfMonth();
        $finMes    = (clone $inicioMes)->endOfMonth();

        // Días hábiles (L-V) del mes
        $dias = [];
        $cur = $inicioMes->copy();
        while ($cur <= $finMes) {
            if ($cur->isWeekday()) $dias[] = $cur->copy();
            $cur->addDay();
        }

        $cursos   = $this->cursosDelTurno($turno);
        $estCurso = $this->mapaEstudianteCurso();
        $festivos = $this->festivosEnRango($inicioMes->toDateString(), $finMes->toDateString());
        $permisos = $this->permisosEnRango($inicioMes->toDateString(), $finMes->toDateString());

        // counts[cur_codigo][Y-m-d] = set de estudiantes con licencia ese día
        $counts = [];
        foreach ($permisos as $p) {
            $curCod = $estCurso[$p->estud_codigo] ?? null;
            if (!$curCod) continue;
            $ini = Carbon::parse($p->permiso_fecha_inicio)->max($inicioMes);
            $fin = Carbon::parse($p->permiso_fecha_fin)->min($finMes);
            $d = $ini->copy();
            while ($d <= $fin) {
                if ($d->isWeekday()) {
                    $counts[$curCod][$d->toDateString()][$p->estud_codigo] = true;
                }
                $d->addDay();
            }
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle($this->mesesNombre[$mes]);

        // Título
        $sheet->setCellValue('A1', 'FALTAS CON LICENCIA');
        $sheet->setCellValue('A2', 'MES');
        $sheet->setCellValue('B2', $this->mesesNombre[$mes] . ' ' . $gestion
            . ($turno ? '  —  TURNO: ' . (ConfiguracionAsistencia::find($turno)->config_turno ?? '') : ''));

        // Encabezados de día: fila 3 = letra día, fila 4 = número fecha
        $sheet->setCellValue('A3', 'DIA');
        $sheet->setCellValue('A4', 'FECHA');
        $sheet->setCellValue('A5', 'CURSO');

        $colIdx = 2; // empezar en columna B
        $colDias = [];
        foreach ($dias as $dia) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $colDias[$dia->toDateString()] = $col;
            $sheet->setCellValue($col . '3', $this->diaLetra[$dia->dayOfWeekIso] ?? '');
            $sheet->setCellValue($col . '4', $dia->day);
            $colIdx++;
        }
        $colTotal = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colTotal . '3', 'TOTAL');
        $sheet->setCellValue($colTotal . '4', 'TOTAL');

        // Filas de cursos
        $fila = 6;
        $totalPorDia = [];
        foreach ($cursos as $c) {
            $sheet->setCellValue('A' . $fila, $c->cur_nombre);
            $totalCurso = 0;
            foreach ($dias as $dia) {
                $f = $dia->toDateString();
                $col = $colDias[$f];
                if (isset($festivos[$f])) {
                    $sheet->setCellValue($col . $fila, $festivos[$f]); // descanso/feriado
                } else {
                    $n = isset($counts[$c->cur_codigo][$f]) ? count($counts[$c->cur_codigo][$f]) : 0;
                    $sheet->setCellValue($col . $fila, $n);
                    $totalCurso += $n;
                    $totalPorDia[$f] = ($totalPorDia[$f] ?? 0) + $n;
                }
            }
            $sheet->setCellValue($colTotal . $fila, $totalCurso);
            $fila++;
        }

        // Fila TOTAL por día
        $sheet->setCellValue('A' . $fila, 'TOTAL');
        $granTotal = 0;
        foreach ($dias as $dia) {
            $f = $dia->toDateString();
            $col = $colDias[$f];
            if (!isset($festivos[$f])) {
                $t = $totalPorDia[$f] ?? 0;
                $sheet->setCellValue($col . $fila, $t);
                $granTotal += $t;
            }
        }
        $sheet->setCellValue($colTotal . $fila, $granTotal);

        $this->estiloMatriz($sheet, $colTotal, $fila, 5);

        return $this->descargar($ss, 'licencias-mensual-' . strtolower($this->mesesNombre[$mes]) . '-' . $gestion);
    }

    // ───────────────────────────────────────────────────────────────
    // 2) Reporte ANUAL por ESTUDIANTE de un curso
    // ───────────────────────────────────────────────────────────────
    public function anualEstudianteExcel(Request $request)
    {
        $gestion  = (int) $request->input('gestion', date('Y'));
        $curCodigo = $request->input('cur_codigo');

        if (!$curCodigo) {
            return back()->with('error', 'Seleccione un curso para el reporte anual por estudiante.');
        }
        $curso = Curso::where('cur_codigo', $curCodigo)->first();

        $estudiantes = Estudiante::where('est_visible', 1)
            ->where('cur_codigo', $curCodigo)
            ->orderBy('est_apellidos')->orderBy('est_nombres')
            ->get(['est_codigo', 'est_apellidos', 'est_nombres']);

        $inicioAnio = Carbon::create($gestion, 2, 1)->startOfMonth();
        $finAnio    = Carbon::create($gestion, 12, 31)->endOfMonth();
        $permisos   = $this->permisosEnRango($inicioAnio->toDateString(), $finAnio->toDateString());

        // licDias[est_codigo][mes] = set de días hábiles con licencia
        $licDias = [];
        foreach ($permisos as $p) {
            $ini = Carbon::parse($p->permiso_fecha_inicio)->max($inicioAnio);
            $fin = Carbon::parse($p->permiso_fecha_fin)->min($finAnio);
            $d = $ini->copy();
            while ($d <= $fin) {
                if ($d->isWeekday()) {
                    $licDias[$p->estud_codigo][$d->month][$d->toDateString()] = true;
                }
                $d->addDay();
            }
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Anual x Estudiante');

        $sheet->setCellValue('A1', 'REPORTE ANUAL DE LICENCIAS POR ESTUDIANTES');
        $sheet->setCellValue('A2', 'Curso: ' . ($curso->cur_nombre ?? $curCodigo) . '  —  Gestión ' . $gestion);

        // Encabezado
        $sheet->setCellValue('A4', 'N°');
        $sheet->setCellValue('B4', 'NOMBRE DEL ALUMNO');
        $colIdx = 3;
        $colMes = [];
        foreach ($this->mesesNombre as $num => $nombre) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $colMes[$num] = $col;
            $sheet->setCellValue($col . '4', $nombre);
            $colIdx++;
        }
        $colTotal = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colTotal . '4', 'TOTAL');

        $fila = 5;
        $n = 1;
        foreach ($estudiantes as $e) {
            $sheet->setCellValue('A' . $fila, $n);
            $sheet->setCellValue('B' . $fila, $e->est_apellidos . ' ' . $e->est_nombres);
            $totalEst = 0;
            foreach ($this->mesesNombre as $num => $nombre) {
                $cnt = isset($licDias[$e->est_codigo][$num]) ? count($licDias[$e->est_codigo][$num]) : 0;
                $sheet->setCellValue($colMes[$num] . $fila, $cnt);
                $totalEst += $cnt;
            }
            $sheet->setCellValue($colTotal . $fila, $totalEst);
            $fila++; $n++;
        }

        $this->estiloMatriz($sheet, $colTotal, $fila - 1, 4);
        $sheet->getColumnDimension('B')->setWidth(34);

        return $this->descargar($ss, 'licencias-anual-estudiante-' . $curCodigo . '-' . $gestion);
    }

    // ───────────────────────────────────────────────────────────────
    // 3) Reporte ANUAL general por CURSO
    // ───────────────────────────────────────────────────────────────
    public function anualCursoExcel(Request $request)
    {
        $gestion = (int) $request->input('gestion', date('Y'));
        $turno   = $request->input('turno');

        $cursos   = $this->cursosDelTurno($turno);
        $estCurso = $this->mapaEstudianteCurso();

        $inicioAnio = Carbon::create($gestion, 2, 1)->startOfMonth();
        $finAnio    = Carbon::create($gestion, 12, 31)->endOfMonth();
        $permisos   = $this->permisosEnRango($inicioAnio->toDateString(), $finAnio->toDateString());

        // tot[cur_codigo][mes] += días hábiles con licencia (sumando estudiantes)
        $tot = [];
        foreach ($permisos as $p) {
            $curCod = $estCurso[$p->estud_codigo] ?? null;
            if (!$curCod) continue;
            $ini = Carbon::parse($p->permiso_fecha_inicio)->max($inicioAnio);
            $fin = Carbon::parse($p->permiso_fecha_fin)->min($finAnio);
            $d = $ini->copy();
            while ($d <= $fin) {
                if ($d->isWeekday()) {
                    $tot[$curCod][$d->month] = ($tot[$curCod][$d->month] ?? 0) + 1;
                }
                $d->addDay();
            }
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Anual x Curso');

        $sheet->setCellValue('A1', 'REPORTE ANUAL DE LICENCIAS POR CURSO');
        $sheet->setCellValue('A2', 'Gestión ' . $gestion
            . ($turno ? '  —  TURNO: ' . (ConfiguracionAsistencia::find($turno)->config_turno ?? '') : ''));

        $sheet->setCellValue('A4', 'CURSO');
        $colIdx = 2;
        $colMes = [];
        foreach ($this->mesesNombre as $num => $nombre) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $colMes[$num] = $col;
            $sheet->setCellValue($col . '4', $nombre);
            $colIdx++;
        }
        $colTotal = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colTotal . '4', 'TOTAL');

        $fila = 5;
        foreach ($cursos as $c) {
            $sheet->setCellValue('A' . $fila, $c->cur_nombre);
            $totalCurso = 0;
            foreach ($this->mesesNombre as $num => $nombre) {
                $cnt = $tot[$c->cur_codigo][$num] ?? 0;
                $sheet->setCellValue($colMes[$num] . $fila, $cnt);
                $totalCurso += $cnt;
            }
            $sheet->setCellValue($colTotal . $fila, $totalCurso);
            $fila++;
        }

        $this->estiloMatriz($sheet, $colTotal, $fila - 1, 4);
        $sheet->getColumnDimension('A')->setWidth(28);

        return $this->descargar($ss, 'licencias-anual-curso-' . $gestion);
    }

    // ───────────────────────────────────────────────────────────────
    // Estilo + descarga
    // ───────────────────────────────────────────────────────────────
    private function estiloMatriz($sheet, string $colTotal, int $filaFin, int $filaHeader): void
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getColumnDimension('A')->setAutoSize(true);

        $rango = 'A' . $filaHeader . ':' . $colTotal . $filaHeader;
        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1C4789');
        $sheet->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $todo = 'A' . $filaHeader . ':' . $colTotal . $filaFin;
        $sheet->getStyle($todo)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B' . ($filaHeader + 1) . ':' . $colTotal . $filaFin)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fila TOTAL en negrita
        $sheet->getStyle('A' . $filaFin . ':' . $colTotal . $filaFin)->getFont()->setBold(true);
        $sheet->getStyle($colTotal . $filaHeader . ':' . $colTotal . $filaFin)->getFont()->setBold(true);
    }

    private function descargar(Spreadsheet $ss, string $nombre)
    {
        $writer = new Xlsx($ss);
        $tmp = tempnam(sys_get_temp_dir(), 'lic') . '.xlsx';
        $writer->save($tmp);
        return response()->download($tmp, $nombre . '.xlsx')->deleteFileAfterSend(true);
    }
}
