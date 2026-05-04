<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\NotaDimension;
use App\Models\NotaPeriodo;
use App\Models\CursoMateriaDocente;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarExcelService
{
    private $spreadsheet;
    private $errores = [];
    private $resumen = [];

    // Mapeo de hojas por trimestre
    private const HOJAS_NOTAS = [1 => '1 TRIM', 2 => '2 TRIM', 3 => '3 TRIM'];
    private const HOJAS_ASISTENCIA = [1 => 'ASIS 1 TRIM', 2 => 'ASIS 2 TRIM', 3 => 'ASIS 3 TRIM'];

    // Estructura fija del Excel para notas
    // SER: cols C-F (hasta 4), promedio en G
    // SABER: cols H-Q (hasta 10), promedio en R
    // HACER: cols S-AB (hasta 10), promedio en AC
    // AUTOEVALUACION: col AD (1)
    private const NOTAS_MAPA = [
        'SER' => ['inicio' => 'C', 'fin' => 'F', 'max_cols' => 4, 'promedio' => 'G'],
        'SABER' => ['inicio' => 'H', 'fin' => 'Q', 'max_cols' => 10, 'promedio' => 'R'],
        'HACER' => ['inicio' => 'S', 'fin' => 'AB', 'max_cols' => 10, 'promedio' => 'AC'],
        'AUTOEVALUACION' => ['inicio' => 'AD', 'fin' => 'AD', 'max_cols' => 1, 'promedio' => null],
    ];

    // Asistencia: datos desde fila 8, fechas en fila 7, cols C-AH
    private const ASIS_FILA_FECHAS = 7;
    private const ASIS_FILA_INICIO = 8;
    private const ASIS_COL_INICIO = 'C';
    private const ASIS_COL_FIN = 'AH';

    // Notas: datos desde fila 11, cols según NOTAS_MAPA
    private const NOTAS_FILA_INICIO = 11;

    // Mapeo de estados asistencia Excel -> Sistema
    private const MAPEO_ASISTENCIA = [
        'A' => 'P', // A en Excel = Asistencia = Presente en sistema
        'F' => 'F', // Falta
        'R' => 'A', // Retraso en Excel = Atraso en sistema
        'L' => 'L', // Licencia
    ];

    public function cargar(string $rutaArchivo): self
    {
        $this->spreadsheet = IOFactory::load($rutaArchivo);
        return $this;
    }

    public function validarEstructura(int $trimestre, string $tipo): array
    {
        $this->errores = [];

        if (in_array($tipo, ['notas', 'ambos'])) {
            $nombreHoja = self::HOJAS_NOTAS[$trimestre] ?? null;
            if (!$nombreHoja || !$this->spreadsheet->sheetNameExists($nombreHoja)) {
                $this->errores[] = "No se encontró la hoja '{$nombreHoja}' para notas del trimestre {$trimestre}.";
            }
        }

        if (in_array($tipo, ['asistencia', 'ambos'])) {
            $nombreHoja = self::HOJAS_ASISTENCIA[$trimestre] ?? null;
            if (!$nombreHoja || !$this->spreadsheet->sheetNameExists($nombreHoja)) {
                $this->errores[] = "No se encontró la hoja '{$nombreHoja}' para asistencia del trimestre {$trimestre}.";
            }
        }

        if (!$this->spreadsheet->sheetNameExists('FILIACIÓN')) {
            $this->errores[] = "No se encontró la hoja 'FILIACIÓN' con la lista de estudiantes.";
        }

        return $this->errores;
    }

    public function validarConfiguracion(int $gestion): array
    {
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();
        if ($dimensiones->isEmpty()) {
            $this->errores[] = "No hay dimensiones configuradas para la gestión {$gestion}.";
            return $this->errores;
        }

        $nombresRequeridos = ['SER', 'SABER', 'HACER', 'AUTOEVALUACION'];
        foreach ($nombresRequeridos as $nombre) {
            $dim = $dimensiones->first(fn($d) => mb_strtoupper(trim($d->dimension_nombre)) === $nombre);
            if (!$dim) {
                $this->errores[] = "Falta la dimensión '{$nombre}' en la configuración.";
            }
        }

        return $this->errores;
    }

    public function parsearNotas(int $trimestre, string $curCodigo, int $gestion): array
    {
        $hoja = $this->spreadsheet->getSheetByName(self::HOJAS_NOTAS[$trimestre]);
        $estudiantesExcel = $this->leerEstudiantesFiliacion();
        $estudiantesDB = $this->matchearEstudiantes($estudiantesExcel, $curCodigo);
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $dimMap = [];
        foreach ($dimensiones as $dim) {
            $key = mb_strtoupper(trim($dim->dimension_nombre));
            $dimMap[$key] = $dim;
        }

        $notas = [];
        $matcheados = 0;
        $noEncontrados = [];

        foreach ($estudiantesExcel as $idx => $nombreExcel) {
            $fila = self::NOTAS_FILA_INICIO + $idx;
            $numExcel = $hoja->getCell('A' . $fila)->getCalculatedValue();
            if (empty($numExcel) && empty($hoja->getCell('B' . $fila)->getCalculatedValue())) break;

            $estCodigo = $estudiantesDB[$idx] ?? null;
            if (!$estCodigo) {
                $noEncontrados[] = $nombreExcel;
                continue;
            }

            $matcheados++;
            $notaEst = ['est_codigo' => $estCodigo, 'nombre' => $nombreExcel, 'dimensiones' => [], 'promedio_trimestral' => 0];

            foreach (self::NOTAS_MAPA as $dimNombre => $config) {
                $dim = $dimMap[$dimNombre] ?? null;
                if (!$dim) continue;

                $valores = [];
                $colActual = $config['inicio'];
                for ($c = 1; $c <= $config['max_cols']; $c++) {
                    if ($this->colToNum($colActual) > $this->colToNum($config['fin'])) break;
                    $val = $hoja->getCell($colActual . $fila)->getCalculatedValue();
                    if ($val !== null && $val !== '' && is_numeric($val) && $val > 0) {
                        $valores[$c] = round(floatval($val), 1);
                    }
                    $colActual = $this->nextCol($colActual);
                }

                // Leer promedio calculado del Excel
                $promExcel = 0;
                if ($config['promedio']) {
                    $promExcel = $hoja->getCell($config['promedio'] . $fila)->getCalculatedValue();
                    $promExcel = is_numeric($promExcel) ? round(floatval($promExcel), 2) : 0;
                } elseif (!empty($valores)) {
                    $promExcel = round(array_sum($valores) / count($valores), 2);
                }

                $notaEst['dimensiones'][$dim->dimension_id] = [
                    'nombre' => $dimNombre,
                    'valores' => $valores,
                    'promedio_excel' => $promExcel,
                    'max' => $dim->dimension_valor_max,
                    'cols_config' => $dim->dimension_columnas,
                ];
            }

            // Promedio trimestral del Excel
            $promTrim = $hoja->getCell('AG' . $fila)->getCalculatedValue();
            $notaEst['promedio_trimestral'] = is_numeric($promTrim) ? round(floatval($promTrim), 2) : 0;

            $notas[] = $notaEst;
        }

        $this->resumen['notas'] = [
            'total_excel' => count($estudiantesExcel),
            'matcheados' => $matcheados,
            'no_encontrados' => $noEncontrados,
        ];

        return $notas;
    }

    public function parsearAsistencia(int $trimestre, string $curCodigo): array
    {
        $hoja = $this->spreadsheet->getSheetByName(self::HOJAS_ASISTENCIA[$trimestre]);
        $estudiantesExcel = $this->leerEstudiantesFiliacion();
        $estudiantesDB = $this->matchearEstudiantes($estudiantesExcel, $curCodigo);

        // Leer fechas de la fila 7
        $fechas = [];
        $col = self::ASIS_COL_INICIO;
        while ($this->colToNum($col) <= $this->colToNum(self::ASIS_COL_FIN)) {
            $val = $hoja->getCell($col . self::ASIS_FILA_FECHAS)->getCalculatedValue();
            if ($val !== null && $val !== '') {
                $fecha = $this->parsearFechaExcel($val);
                if ($fecha) {
                    $fechas[$col] = $fecha;
                }
            }
            $col = $this->nextCol($col);
        }

        $asistencia = [];
        $matcheados = 0;
        $noEncontrados = [];

        foreach ($estudiantesExcel as $idx => $nombreExcel) {
            $fila = self::ASIS_FILA_INICIO + $idx;
            $numExcel = $hoja->getCell('A' . $fila)->getCalculatedValue();
            if (empty($numExcel) && empty($hoja->getCell('B' . $fila)->getCalculatedValue())) break;

            $estCodigo = $estudiantesDB[$idx] ?? null;
            if (!$estCodigo) {
                $noEncontrados[] = $nombreExcel;
                continue;
            }

            $matcheados++;
            $registros = [];

            foreach ($fechas as $colFecha => $fecha) {
                $val = strtoupper(trim($hoja->getCell($colFecha . $fila)->getValue() ?? ''));
                if (isset(self::MAPEO_ASISTENCIA[$val])) {
                    $registros[] = [
                        'fecha' => $fecha,
                        'estado' => self::MAPEO_ASISTENCIA[$val],
                        'estado_original' => $val,
                    ];
                }
            }

            $asistencia[] = [
                'est_codigo' => $estCodigo,
                'nombre' => $nombreExcel,
                'registros' => $registros,
                'total_dias' => count($registros),
            ];
        }

        $this->resumen['asistencia'] = [
            'total_excel' => count($estudiantesExcel),
            'matcheados' => $matcheados,
            'no_encontrados' => $noEncontrados,
            'total_fechas' => count($fechas),
            'fechas' => array_values($fechas),
        ];

        return $asistencia;
    }

    public function getErrores(): array
    {
        return $this->errores;
    }

    public function getResumen(): array
    {
        return $this->resumen;
    }

    // ── Métodos privados ──

    private function leerEstudiantesFiliacion(): array
    {
        $hoja = $this->spreadsheet->getSheetByName('FILIACIÓN');
        $estudiantes = [];
        for ($fila = 9; $fila <= 60; $fila++) {
            $nombre = trim($hoja->getCell('B' . $fila)->getCalculatedValue() ?? '');
            if (empty($nombre)) break;
            $estudiantes[] = $nombre;
        }
        return $estudiantes;
    }

    private function matchearEstudiantes(array $nombresExcel, string $curCodigo): array
    {
        $estudiantesDB = Estudiante::visible()
            ->where('cur_codigo', $curCodigo)
            ->get()
            ->keyBy(fn($e) => $this->normalizarNombre($e->est_apellidos . ' ' . $e->est_nombres));

        $resultado = [];
        foreach ($nombresExcel as $idx => $nombreExcel) {
            $normalizado = $this->normalizarNombre($nombreExcel);
            $est = $estudiantesDB[$normalizado] ?? null;

            if (!$est) {
                // Búsqueda parcial por similitud
                $est = $estudiantesDB->first(function ($e) use ($normalizado) {
                    $dbNorm = $this->normalizarNombre($e->est_apellidos . ' ' . $e->est_nombres);
                    return similar_text($normalizado, $dbNorm) / max(strlen($normalizado), strlen($dbNorm)) > 0.85;
                });
            }

            $resultado[$idx] = $est ? $est->est_codigo : null;
        }

        return $resultado;
    }

    private function normalizarNombre(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre));
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        // Quitar tildes
        $nombre = str_replace(
            ['Á','É','Í','Ó','Ú','Ñ','Ü'],
            ['A','E','I','O','U','N','U'],
            $nombre
        );
        return $nombre;
    }

    private function parsearFechaExcel($valor): ?string
    {
        if (is_numeric($valor)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject(intval($valor));
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        // Intentar parsear texto como "17/4/2026 R."
        $limpio = preg_replace('/[^0-9\/\-]/', '', $valor);
        try {
            $dt = \Carbon\Carbon::createFromFormat('d/m/Y', $limpio);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                $dt = \Carbon\Carbon::parse($limpio);
                return $dt->format('Y-m-d');
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    private function colToNum(string $col): int
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col);
    }

    private function nextCol(string $col): string
    {
        $num = $this->colToNum($col);
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($num + 1);
    }
}
