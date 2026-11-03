<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Única fuente de verdad de los permisos de la pantalla Roles.
 *
 * La usan el middleware CheckPermiso, User::tienePermiso / tieneCapacidad,
 * la directiva @puede, los Gate (@can) y el menú. Antes cada uno consultaba la
 * base por su cuenta (una consulta por ítem del menú) y algunos ni siquiera
 * miraban esta tabla, sino listas de roles escritas en el código.
 *
 * Todo se carga una vez por rol y por petición.
 */
class Permisos
{
    public const ACCIONES = ['ver', 'crear', 'editar', 'eliminar', 'anular', 'exportar'];

    /** @var array<int, array<string, array<string,int>>> rol => slug => [accion => 0/1] */
    private static array $porRol = [];
    /** @var array<int, array<string,bool>> rol => capacidad => true */
    private static array $capacidades = [];
    private static ?array $catalogo = null;

    /** ¿El rol tiene la acción sobre el módulo? $accion: 'ver' o 'perm_ver'. */
    public static function puede(?int $rolId, string $slug, string $accion = 'ver'): bool
    {
        if (!$rolId) return false;
        if ($rolId === 1) return true;
        $accion = str_starts_with($accion, 'perm_') ? substr($accion, 5) : $accion;
        return !empty(self::delRol($rolId)[$slug][$accion]);
    }

    /** Ver en el módulo o en alguno de sus submódulos (grupos del menú). */
    public static function puedeGrupo(?int $rolId, string $slug): bool
    {
        if ($rolId === 1) return true;
        if (self::puede($rolId, $slug)) return true;
        foreach (self::catalogo() as $hijo => $m) {
            if ($m->padre === $slug && self::puede($rolId, $hijo)) return true;
        }
        return false;
    }

    public static function tieneCapacidad(?int $rolId, string $slug): bool
    {
        if (!$rolId) return false;
        if ($rolId === 1) return true;
        if (!array_key_exists($rolId, self::$capacidades)) {
            self::$capacidades[$rolId] = Schema::hasTable('rol_capacidades')
                ? DB::table('rol_capacidades')->where('rol_id', $rolId)->pluck('cap_slug')->flip()->map(fn() => true)->all()
                : [];
        }
        return isset(self::$capacidades[$rolId][$slug]);
    }

    /** [slug => [accion => 0/1]] del rol. */
    public static function delRol(int $rolId): array
    {
        if (isset(self::$porRol[$rolId])) return self::$porRol[$rolId];
        $porId = collect(self::catalogo())->keyBy('mod_id');
        $out = [];
        foreach (DB::table('rol_permisos')->where('rol_id', $rolId)->get() as $p) {
            $slug = optional($porId->get($p->mod_id))->mod_slug;
            if (!$slug) continue;
            foreach (self::ACCIONES as $a) {
                $out[$slug][$a] = (int) ($p->{'perm_' . $a} ?? 0);
            }
        }
        return self::$porRol[$rolId] = $out;
    }

    /** Catálogo de módulos por slug, con el slug del padre. */
    public static function catalogo(): array
    {
        if (self::$catalogo !== null) return self::$catalogo;
        $mods = DB::table('rol_modulos')->get(['mod_id', 'mod_slug', 'mod_nombre', 'mod_padre_id']);
        $porId = $mods->keyBy('mod_id');
        $out = [];
        foreach ($mods as $m) {
            $m->padre = optional($porId->get($m->mod_padre_id))->mod_slug;
            $out[$m->mod_slug] = $m;
        }
        return self::$catalogo = $out;
    }

    /** Vacía la caché (después de guardar un rol, y en pruebas). */
    public static function olvidar(): void
    {
        self::$porRol = [];
        self::$capacidades = [];
        self::$catalogo = null;
    }
}
