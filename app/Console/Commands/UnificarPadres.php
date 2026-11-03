<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Unifica padres de familia duplicados (mismo CI, varios pfam_codigo).
 *
 * Cada inscripción creaba un padre nuevo sin buscar si ya existía, así que hay
 * familias con un registro de padre por hijo. Consecuencias: en Caja, al elegir
 * al papá aparece un solo hijo; en el portal, el padre ve sólo al hijo atado al
 * registro que tiene usuario.
 *
 * Uso:
 *   php artisan padres:unificar             → sólo genera el CSV para revisar (no toca nada)
 *   php artisan padres:unificar --aplicar   → aplica los grupos marcados como UNIFICAR
 *
 * Reglas:
 *   - Se agrupa por CI normalizado (sólo dígitos, al menos 5, distinto de 0000000).
 *   - Sólo se unifica si los nombres coinciden (normalizados). Si difieren, el
 *     grupo queda como REVISAR: puede ser un CI mal cargado.
 *   - Registro principal: el que tiene usuario del portal; si no, el que tiene
 *     más hijos vinculados; si no, el más antiguo.
 *   - Si dos registros del grupo tienen usuario, queda como REVISAR.
 *   - Los vínculos se mueven al principal y los duplicados quedan con
 *     pfam_estado = 0 (no se borran). Cada movimiento queda en
 *     padres_unificados para poder rastrearlo.
 */
class UnificarPadres extends Command
{
    protected $signature = 'padres:unificar {--aplicar : Aplica los cambios (sin esto sólo genera el CSV)}';
    protected $description = 'Unifica padres de familia duplicados por CI';

    public function handle()
    {
        $padres = DB::table('cole_padresfamilia')
            ->select('pfam_id', 'pfam_codigo', 'pfam_ci', 'pfam_nombres', 'pfam_estado')
            ->get();

        $usuarios = DB::table('rol_usuarios')->where('us_entidad_tipo', 'padre')
            ->pluck('us_id', 'us_entidad_id');
        $hijos = DB::table('rela_estudiantespadres')->select('pfam_id', DB::raw('COUNT(*) n'))
            ->groupBy('pfam_id')->pluck('n', 'pfam_id');

        $grupos = $padres->groupBy(fn($p) => $this->ci($p->pfam_ci))
            ->filter(fn($g, $ci) => $ci !== '' && $g->count() > 1);

        $filas = [];
        $plan  = [];
        foreach ($grupos as $ci => $g) {
            $nombres = $g->map(fn($p) => $this->nombre($p->pfam_nombres))->unique()->filter();
            $conUsuario = $g->filter(fn($p) => isset($usuarios[$p->pfam_codigo]));

            $principal = $g->sortBy([
                fn($a, $b) => (int) isset($usuarios[$b->pfam_codigo]) <=> (int) isset($usuarios[$a->pfam_codigo]),
                fn($a, $b) => ($hijos[$b->pfam_codigo] ?? 0) <=> ($hijos[$a->pfam_codigo] ?? 0),
                fn($a, $b) => $a->pfam_id <=> $b->pfam_id,
            ])->first();

            if ($nombres->count() > 1) {
                $accion = 'REVISAR: nombres distintos';
            } elseif ($conUsuario->count() > 1) {
                $accion = 'REVISAR: más de un usuario';
            } else {
                $accion = 'UNIFICAR';
                $plan[] = [$principal->pfam_codigo, $g->where('pfam_codigo', '!=', $principal->pfam_codigo)->pluck('pfam_codigo')->all()];
            }

            foreach ($g as $p) {
                $filas[] = [
                    $ci, $p->pfam_codigo, $p->pfam_nombres, $hijos[$p->pfam_codigo] ?? 0,
                    isset($usuarios[$p->pfam_codigo]) ? 'SI' : '',
                    $p->pfam_codigo === $principal->pfam_codigo ? 'PRINCIPAL' : 'DUPLICADO',
                    $accion,
                ];
            }
        }

        $archivo = storage_path('app/padres_unificar_' . date('Ymd_His') . '.csv');
        $fh = fopen($archivo, 'w');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM: Excel abre bien los acentos
        fputcsv($fh, ['CI', 'CODIGO', 'NOMBRE', 'HIJOS', 'USUARIO', 'ROL', 'ACCION'], ';');
        foreach ($filas as $f) fputcsv($fh, $f, ';');
        fclose($fh);

        $revisar = collect($filas)->where(6, '!=', 'UNIFICAR')->pluck(0)->unique()->count();
        $this->info("CI con más de un registro: {$grupos->count()}");
        $this->info('  se unifican: ' . count($plan) . " grupos | a revisar a mano: {$revisar}");
        $this->info("Detalle: {$archivo}");

        if (!$this->option('aplicar')) {
            $this->warn('No se modificó nada. Revise el CSV y corra con --aplicar.');
            return 0;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('padres_unificados')) {
            $this->error('Falta la tabla padres_unificados: aplique database/sql/upgrade_2026_11_padres_unificados.sql.');
            return 1;
        }
        $movidos = 0;
        DB::transaction(function () use ($plan, &$movidos) {
            foreach ($plan as [$principal, $duplicados]) {
                foreach ($duplicados as $dup) {
                    $movidos += $this->mover($dup, $principal);
                }
            }
        });
        $this->info("Aplicado: {$movidos} vínculos movidos.");
        return 0;
    }

    /** Mueve todo lo que apunta a $dup hacia $principal y desactiva $dup. */
    private function mover(string $dup, string $principal): int
    {
        $n = 0;
        // Vínculos padre-hijo: evitar duplicar el mismo hijo en el principal.
        $yaTiene = DB::table('rela_estudiantespadres')->where('pfam_id', $principal)->pluck('est_id')->all();
        $n += DB::table('rela_estudiantespadres')->where('pfam_id', $dup)->whereIn('est_id', $yaTiene)->delete();
        $n += DB::table('rela_estudiantespadres')->where('pfam_id', $dup)->update(['pfam_id' => $principal]);

        foreach (['inscripciones' => 'pfam_codigo', 'pagos_mensualidades' => 'pfam_codigo',
                  'pagos_servicios' => 'pfam_codigo', 'asistencia_permisos' => 'permiso_solicitante_pfam'] as $tabla => $col) {
            $n += DB::table($tabla)->where($col, $dup)->update([$col => $principal]);
        }
        // Si el duplicado tenía el usuario del portal, el usuario pasa al principal.
        DB::table('rol_usuarios')->where('us_entidad_tipo', 'padre')->where('us_entidad_id', $dup)
            ->update(['us_entidad_id' => $principal]);

        DB::table('cole_padresfamilia')->where('pfam_codigo', $dup)->update(['pfam_estado' => 0]);
        DB::table('padres_unificados')->insert([
            'pu_origen' => $dup, 'pu_destino' => $principal, 'pu_fecha' => now(),
        ]);
        return $n;
    }

    private function ci(?string $ci): string
    {
        $d = preg_replace('/\D/', '', (string) $ci);
        return (strlen($d) >= 5 && !preg_match('/^0+$/', $d)) ? $d : '';
    }

    private function nombre(?string $n): string
    {
        $n = trim((string) $n);
        // Algunos registros tienen los acentos codificados dos veces ("SIÃ‘ANI"
        // en lugar de "SIÑANI"); se deshace antes de comparar.
        if (str_contains($n, 'Ã') || str_contains($n, 'Â')) {
            $fix = @iconv('UTF-8', 'Windows-1252//IGNORE', $n);
            if ($fix !== false && mb_check_encoding($fix, 'UTF-8')) $n = $fix;
        }
        $n = mb_strtoupper($n, 'UTF-8');
        $n = strtr($n, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
        return preg_replace('/\s+/', ' ', $n);
    }
}
