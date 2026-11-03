<?php

namespace App\Services;

use App\Models\Curso;
use Illuminate\Support\Collection;

/**
 * A qué curso pasa un estudiante que aprueba (colegio_cursos.cur_siguiente).
 *
 * El sistema no tenía esta noción: cada curso es un código suelto y el paralelo
 * sólo aparece dentro del nombre ('1RO PRIMARIA "A"', '1ro Secundaria B').
 * sugerir() deduce grado y paralelo del nombre y propone el curso siguiente:
 * mismo paralelo del grado siguiente o, si ese paralelo no existe, el A.
 * Es sólo una sugerencia: se revisa y corrige en Parametrización → Tarifas.
 */
class CursoProgresion
{
    /** Orden de grados de todo el colegio: Inicial 1–2, Primaria 3–8, Secundaria 9–14. */
    public static function gradoDeNombre(string $nombre, ?string $nivel): ?int
    {
        $n = mb_strtoupper($nombre, 'UTF-8');
        if (!preg_match('/(\d)\s*(RO|RA|DO|DA|ER|TO|TA|MO|NO|VO|°|º)?/u', $n, $m)) {
            return null;
        }
        $num = (int) $m[1];
        $nivel = mb_strtoupper((string) $nivel, 'UTF-8');
        if (str_contains($n, 'SECCI') || $nivel === 'INICIAL') return $num;          // 1ra/2da sección
        if (str_contains($n, 'SECUNDAR') || str_starts_with($nivel, 'SECUNDAR')) return 8 + $num;
        if (str_contains($n, 'PRIMAR') || str_starts_with($nivel, 'PRIMAR')) return 2 + $num;
        return null;
    }

    public static function paraleloDeNombre(string $nombre): string
    {
        $n = mb_strtoupper(trim($nombre), 'UTF-8');
        if (preg_match('/"([A-Z])"\s*$/u', $n, $m)) return $m[1];
        if (preg_match('/\s([A-Z])$/u', $n, $m)) return $m[1];
        return 'A';
    }

    /**
     * Propuesta [cur_codigo => ['grado', 'paralelo', 'siguiente']] para los
     * cursos visibles. 'siguiente' null = egresa (último grado).
     */
    public function sugerir(): array
    {
        $cursos = Curso::visible()->get();
        $info = [];
        foreach ($cursos as $c) {
            $info[$c->cur_codigo] = [
                'grado'    => self::gradoDeNombre($c->cur_nombre, $c->cur_nivel),
                'paralelo' => self::paraleloDeNombre($c->cur_nombre),
            ];
        }
        $maxGrado = collect($info)->max('grado');
        $porGrado = collect($info)->filter(fn($i) => $i['grado'])->groupBy('grado', true);

        foreach ($info as $cod => &$i) {
            $i['siguiente'] = null;
            if (!$i['grado'] || $i['grado'] >= $maxGrado) continue;
            $sig = $porGrado->get($i['grado'] + 1);
            if (!$sig) continue;
            $mismo = $sig->filter(fn($x) => $x['paralelo'] === $i['paralelo'])->keys()->first();
            $elegido = $mismo ?? ($sig->filter(fn($x) => $x['paralelo'] === 'A')->keys()->first() ?? $sig->keys()->first());
            // Códigos sólo numéricos ('5500504') llegan como claves enteras de PHP.
            $i['siguiente'] = $elegido === null ? null : (string) $elegido;
        }
        return $info;
    }

    /** Curso siguiente configurado; null si egresa o no está configurado. */
    public static function siguiente(string $curCodigo): ?string
    {
        return Curso::where('cur_codigo', $curCodigo)->value('cur_siguiente') ?: null;
    }

    /** ¿Es el último grado (egresa)? */
    public static function esUltimoGrado(string $curCodigo): bool
    {
        $c = Curso::where('cur_codigo', $curCodigo)->first(['cur_grado', 'cur_siguiente']);
        if (!$c) return false;
        if ($c->cur_siguiente) return false;
        $max = Curso::visible()->max('cur_grado');
        return $c->cur_grado && $max && (int) $c->cur_grado === (int) $max;
    }
}
