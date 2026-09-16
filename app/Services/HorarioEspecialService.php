<?php

namespace App\Services;

use App\Models\HorarioEspecial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve QUÉ HORARIO RIGE CADA DÍA para una categoría y turno.
 *
 * La configuración normal (asistencia_configuracion) no tiene noción de fecha:
 * define un único horario por categoría/turno. Cuando el colegio aplica un
 * horario de invierno por unos meses, o suspende clases por vacaciones, los
 * reportes seguían evaluando contra el horario normal y marcaban atrasos que
 * nunca existieron.
 *
 * Este service parte un rango de fechas en TRAMOS, cada uno con el horario que
 * realmente rigió, y expone además las fechas sin clases.
 *
 * Colaciones: no se une por SQL con colegio_cursos/colegio_materias (latin1 y
 * utf8mb4 mezclados). Todo el cruce por categoría se hace en PHP.
 */
class HorarioEspecialService
{
    /** Cache por gestión de los rangos activos, para no repegarle a la BD. */
    private array $cacheRangos = [];

    /** Cache de la configuración base por "categoria|turno". */
    private array $cacheBase = [];

    /** Cache de la categoría (cur_nivel) por código de estudiante. */
    private array $cacheCategoria = [];

    /** Normaliza una categoría para comparar sin sufrir tildes ni mayúsculas. */
    public static function normalizar(?string $v): string
    {
        $v = mb_strtoupper(trim((string) $v), 'UTF-8');
        return strtr($v, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
    }

    /** Clave con la que se indexa el detalle de un rango: categoría + turno. */
    private static function clave(?string $categoria, ?string $turno): string
    {
        return self::normalizar($categoria) . '|' . trim((string) $turno);
    }

    /** 'HH:MM:SS' a partir de lo que venga (TIME, DATETIME o string corto). */
    private static function hora($v, string $fallback = '00:00:00'): string
    {
        if ($v === null || $v === '') return $fallback;
        if ($v instanceof \DateTimeInterface) return $v->format('H:i:s');
        $s = (string) $v;
        if (strlen($s) > 8 && strpos($s, ' ') !== false) $s = substr($s, 11);
        $s = substr($s, 0, 8);
        return strlen($s) === 5 ? $s . ':00' : $s;
    }

    /** Rangos especiales activos de una gestión, con su detalle por categoría. */
    private function rangos(int $gestion): array
    {
        if (isset($this->cacheRangos[$gestion])) return $this->cacheRangos[$gestion];

        $cabeceras = HorarioEspecial::activo()->gestion($gestion)
            ->orderBy('esp_id')
            ->get();

        if ($cabeceras->isEmpty()) return $this->cacheRangos[$gestion] = [];

        $detalles = DB::table('asistencia_horarios_especiales_detalle')
            ->whereIn('esp_id', $cabeceras->pluck('esp_id')->all())
            ->get()
            ->groupBy('esp_id');

        $out = [];
        foreach ($cabeceras as $c) {
            // Indexado por "CATEGORIA|Turno": el turno vive en el detalle, así que
            // un mismo rango puede afectar la mañana y la tarde a la vez.
            $porCategoria = [];
            foreach ($detalles[$c->esp_id] ?? [] as $d) {
                $porCategoria[self::clave($d->det_categoria, $d->det_turno)] = [
                    'entrada'    => self::hora($d->det_hora_entrada),
                    'tolerancia' => self::hora($d->det_tolerancia_atraso),
                    'salida'     => self::hora($d->det_hora_salida),
                ];
            }
            $out[] = [
                'id'         => (int) $c->esp_id,
                'nombre'     => $c->esp_nombre,
                'tipo'       => (int) $c->esp_tipo,
                'inicio'     => $c->esp_fecha_inicio->format('Y-m-d'),
                'fin'        => $c->esp_fecha_fin->format('Y-m-d'),
                'categorias' => $porCategoria,
            ];
        }
        return $this->cacheRangos[$gestion] = $out;
    }

    /**
     * Configuración NORMAL (sin fechas) para una categoría y turno.
     * Es el horario que rige fuera de todo rango especial.
     *
     * @return array{entrada:string,tolerancia:?string,salida:string}|null
     */
    public function configBase(string $categoria, string $turno): ?array
    {
        $key = self::normalizar($categoria) . '|' . $turno;
        if (array_key_exists($key, $this->cacheBase)) return $this->cacheBase[$key];

        $fila = null;
        foreach (DB::table('asistencia_configuracion')->where('config_estado', 1)->get() as $c) {
            if ($c->config_turno !== $turno) continue;
            if (self::normalizar($c->config_categoria) !== self::normalizar($categoria)) continue;
            $fila = $c;
            break;
        }
        if (!$fila) return $this->cacheBase[$key] = null;

        return $this->cacheBase[$key] = [
            'entrada'    => self::hora($fila->hora_entrada),
            'tolerancia' => $fila->tolerancia_atraso ? self::hora($fila->tolerancia_atraso) : null,
            'salida'     => self::hora($fila->hora_salida),
        ];
    }

    /**
     * ¿Qué rango especial pisa esta fecha para esta categoría/turno?
     *
     * Si dos rangos cubren el mismo día, la regla es fija y no se configura:
     * manda el receso por sobre el horario especial —"no hay clases" pesa más
     * que "se entra más tarde", que es lo que pasa cuando las vacaciones caen
     * dentro del horario de invierno— y entre dos del mismo tipo vale el
     * cargado después.
     */
    private function rangoEn(string $fecha, string $categoria, string $turno, int $gestion): ?array
    {
        $clave = self::clave($categoria, $turno);
        $elegido = null;

        foreach ($this->rangos($gestion) as $r) {
            if ($fecha < $r['inicio'] || $fecha > $r['fin']) continue;

            // Un horario especial sólo aplica si incluyó esta categoría en este turno.
            // Un receso pisa siempre: no hay clases para nadie.
            if ($r['tipo'] === HorarioEspecial::TIPO_HORARIO && !isset($r['categorias'][$clave])) continue;

            if ($elegido === null) { $elegido = $r; continue; }

            $esReceso  = $r['tipo'] === HorarioEspecial::TIPO_RECESO;
            $eraReceso = $elegido['tipo'] === HorarioEspecial::TIPO_RECESO;

            if ($esReceso !== $eraReceso) {
                if ($esReceso) $elegido = $r;        // el receso manda
            } elseif ($r['id'] > $elegido['id']) {
                $elegido = $r;                        // mismo tipo: el último cargado
            }
        }
        return $elegido;
    }

    /**
     * ¿Hay algún rango especial que toque [inicio, fin] para esta categoría/turno?
     * Permite saltarse el recorrido día a día cuando no hay nada cargado.
     */
    public function hayRangosEn(string $inicio, string $fin, string $categoria, string $turno): bool
    {
        $clave = self::clave($categoria, $turno);
        $desde = (int) substr($inicio, 0, 4);
        $hasta = (int) substr($fin, 0, 4);

        for ($g = $desde; $g <= $hasta; $g++) {
            foreach ($this->rangos($g) as $r) {
                if ($r['fin'] < $inicio || $r['inicio'] > $fin) continue;
                if ($r['tipo'] === HorarioEspecial::TIPO_RECESO) return true;
                if (isset($r['categorias'][$clave])) return true;
            }
        }
        return false;
    }

    /**
     * Horario vigente en UNA fecha. Devuelve null si ese día no hay clases.
     *
     * @return array{tipo:string,entrada:string,tolerancia:?string,salida:string,nombre:?string}|null
     */
    public function horarioEn(string $fecha, string $categoria, string $turno, ?int $gestion = null): ?array
    {
        $gestion = $gestion ?? (int) substr($fecha, 0, 4);
        $rango   = $this->rangoEn($fecha, $categoria, $turno, $gestion);

        if ($rango && $rango['tipo'] === HorarioEspecial::TIPO_RECESO) {
            return null;                              // receso: no hay clases
        }

        if ($rango) {
            $h = $rango['categorias'][self::clave($categoria, $turno)];
            return [
                'tipo'       => 'especial',
                'entrada'    => $h['entrada'],
                'tolerancia' => $h['tolerancia'],
                'salida'     => $h['salida'],
                'nombre'     => $rango['nombre'],
            ];
        }

        $base = $this->configBase($categoria, $turno);
        if (!$base) return null;

        return [
            'tipo'       => 'normal',
            'entrada'    => $base['entrada'],
            'tolerancia' => $base['tolerancia'],
            'salida'     => $base['salida'],
            'nombre'     => null,
        ];
    }

    /**
     * Parte [inicio, fin] en tramos contiguos con el mismo horario vigente.
     * Los tramos de receso se devuelven con tipo 'receso' y sin horas.
     *
     * Evita una consulta por día: agrupa días consecutivos con idéntico horario.
     *
     * @return array<int, array{desde:string,hasta:string,tipo:string,entrada:?string,tolerancia:?string,salida:?string,nombre:?string}>
     */
    public function tramos(string $inicio, string $fin, string $categoria, string $turno): array
    {
        $cur = Carbon::parse($inicio)->startOfDay();
        $end = Carbon::parse($fin)->startOfDay();
        if ($end->lt($cur)) return [];

        // Protección ante rangos absurdos (p. ej. periodos mal cargados con año 0026).
        if ($cur->diffInDays($end) > 3660) {
            $cur = $end->copy()->subDays(3660);
        }

        // Atajo: si ningún rango especial toca este intervalo, no hace falta recorrer
        // día por día. Es el caso normal y este método se llama una vez por estudiante.
        if (!$this->hayRangosEn($cur->format('Y-m-d'), $end->format('Y-m-d'), $categoria, $turno)) {
            $base = $this->configBase($categoria, $turno);
            return [[
                'desde'      => $cur->format('Y-m-d'),
                'hasta'      => $end->format('Y-m-d'),
                'tipo'       => $base ? 'normal' : 'sin_config',
                'entrada'    => $base['entrada']    ?? null,
                'tolerancia' => $base['tolerancia'] ?? null,
                'salida'     => $base['salida']     ?? null,
                'nombre'     => null,
            ]];
        }

        $tramos = [];
        $actual = null;

        while ($cur->lte($end)) {
            $f = $cur->format('Y-m-d');
            $gestion = (int) $cur->format('Y');

            $rango = $this->rangoEn($f, $categoria, $turno, $gestion);
            if ($rango && $rango['tipo'] === HorarioEspecial::TIPO_RECESO) {
                $firma = 'receso|' . $rango['id'];
                $datos = ['tipo' => 'receso', 'entrada' => null, 'tolerancia' => null,
                          'salida' => null, 'nombre' => $rango['nombre']];
            } else {
                $h = $this->horarioEn($f, $categoria, $turno, $gestion);
                if (!$h) {
                    $firma = 'sin-config';
                    $datos = ['tipo' => 'sin_config', 'entrada' => null, 'tolerancia' => null,
                              'salida' => null, 'nombre' => null];
                } else {
                    $firma = $h['tipo'] . '|' . $h['entrada'] . '|' . $h['tolerancia'] . '|' . $h['salida'];
                    $datos = $h;
                }
            }

            if ($actual && $actual['firma'] === $firma) {
                $actual['hasta'] = $f;
            } else {
                if ($actual) $tramos[] = $actual;
                $actual = ['firma' => $firma, 'desde' => $f, 'hasta' => $f] + $datos;
            }
            $cur->addDay();
        }
        if ($actual) $tramos[] = $actual;

        return array_map(function ($t) {
            unset($t['firma']);
            return $t;
        }, $tramos);
    }

    /**
     * Fechas sin clases (recesos) dentro del rango, como set para lookup rápido.
     * El receso aplica a todas las categorías y turnos.
     *
     * @return \Illuminate\Support\Collection claves 'Y-m-d' => true
     */
    public function fechasSinClases(string $inicio, string $fin)
    {
        $cur = Carbon::parse($inicio)->startOfDay();
        $end = Carbon::parse($fin)->startOfDay();
        if ($end->lt($cur)) return collect();
        if ($cur->diffInDays($end) > 3660) $cur = $end->copy()->subDays(3660);

        // Gestiones tocadas por el rango (normalmente una sola).
        $gestiones = [];
        for ($y = (int) $cur->format('Y'); $y <= (int) $end->format('Y'); $y++) $gestiones[] = $y;

        $recesos = [];
        foreach ($gestiones as $g) {
            foreach ($this->rangos($g) as $r) {
                if ($r['tipo'] === HorarioEspecial::TIPO_RECESO) $recesos[] = $r;
            }
        }
        if (empty($recesos)) return collect();

        $fechas = [];
        while ($cur->lte($end)) {
            $f = $cur->format('Y-m-d');
            foreach ($recesos as $r) {
                if ($f >= $r['inicio'] && $f <= $r['fin']) { $fechas[$f] = true; break; }
            }
            $cur->addDay();
        }
        return collect($fechas);
    }

    /** Categoría (cur_nivel) del curso de un estudiante. Sin joins de colación. */
    public function categoriaDeEstudiante(string $estCodigo): ?string
    {
        if (array_key_exists($estCodigo, $this->cacheCategoria)) return $this->cacheCategoria[$estCodigo];

        $est = DB::table('colegio_estudiantes')->where('est_codigo', $estCodigo)->first();
        if (!$est) return $this->cacheCategoria[$estCodigo] = null;

        return $this->cacheCategoria[$estCodigo] =
            DB::table('colegio_cursos')->where('cur_codigo', $est->cur_codigo)->value('cur_nivel');
    }

    /**
     * Horario especial que rige para un estudiante en una fecha, o null si ese día
     * no hay nada especial cargado (entonces vale la configuración normal).
     *
     * Devuelve además 'receso' => true cuando ese día no hay clases, para que quien
     * registra atrasos en vivo simplemente no registre nada.
     *
     * @return array{receso:bool,entrada:?string,tolerancia:?string,salida:?string,nombre:?string}|null
     */
    public function especialDeEstudianteEn(string $estCodigo, string $fecha, string $turno = 'Mañana'): ?array
    {
        $categoria = $this->categoriaDeEstudiante($estCodigo);
        if (!$categoria) return null;

        $gestion = (int) substr($fecha, 0, 4);
        $rango   = $this->rangoEn($fecha, $categoria, $turno, $gestion);
        if (!$rango) return null;

        if ($rango['tipo'] === HorarioEspecial::TIPO_RECESO) {
            return ['receso' => true, 'entrada' => null, 'tolerancia' => null,
                    'salida' => null, 'nombre' => $rango['nombre']];
        }

        $h = $rango['categorias'][self::clave($categoria, $turno)];
        return ['receso' => false, 'entrada' => $h['entrada'], 'tolerancia' => $h['tolerancia'],
                'salida' => $h['salida'], 'nombre' => $rango['nombre']];
    }

    /** Rangos activos de una gestión, para mostrarlos en pantalla. */
    public function listar(int $gestion): array
    {
        return $this->rangos($gestion);
    }
}
