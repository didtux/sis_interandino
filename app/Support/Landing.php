<?php

namespace App\Support;

use App\Models\LandingBloque;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Textos y bloques editables de la web pública.
 *
 * Los textos se definen en config/landing.php con su valor original; lo que el
 * colegio edita se guarda en landing_secciones y tiene prioridad. Sin fila (o
 * sin la tabla, si el SQL todavía no se aplicó) la página se ve como antes.
 */
class Landing
{
    private const CACHE = 'landing_secciones';
    private static ?array $valores = null;

    /** Etiquetas que el editor puede dejar en los campos html. */
    private const ETIQUETAS_HTML = '<p><br><strong><b><em><i><u><ul><ol><li><a><h3><h4><h5><span><blockquote><img><table><thead><tbody><tr><th><td>';

    /** Definición de una clave: [etiqueta, tipo, valor original] o null. */
    public static function campo(string $clave): ?array
    {
        foreach (config('landing.paginas', []) as $pagina) {
            if (isset($pagina['campos'][$clave])) return $pagina['campos'][$clave];
        }
        return null;
    }

    private static function valores(): array
    {
        if (self::$valores !== null) return self::$valores;
        try {
            return self::$valores = Cache::rememberForever(self::CACHE,
                fn() => DB::table('landing_secciones')->pluck('ls_valor', 'ls_clave')->all());
        } catch (\Throwable $e) {
            return self::$valores = [];
        }
    }

    /** Valor guardado, o el original del catálogo. */
    public static function valor(string $clave): ?string
    {
        $v = self::valores()[$clave] ?? null;
        if ($v !== null && trim($v) !== '') return $v;
        return self::campo($clave)[2] ?? null;
    }

    /** ¿El colegio cambió este texto? (para mostrarlo en el editor) */
    public static function editado(string $clave): bool
    {
        $v = self::valores()[$clave] ?? null;
        return $v !== null && trim($v) !== '';
    }

    /** El texto listo para imprimir con {{ }} según su tipo. */
    public static function html(string $clave): HtmlString
    {
        $tipo = self::campo($clave)[1] ?? 'texto';
        $v = (string) self::valor($clave);
        switch ($tipo) {
            case 'html':
                return new HtmlString($v);
            case 'titulo':
                $e = e($v);
                $e = preg_replace('/\*(.+?)\*/u', '<span>$1</span>', $e);
                return new HtmlString(nl2br($e, false));
            case 'parrafo':
                return new HtmlString(nl2br(e($v), false));
            default:
                return new HtmlString(e($v));
        }
    }

    /** Campo tipo lista: un elemento por línea, sin vacíos. */
    public static function lista(string $clave): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) self::valor($clave)))));
    }

    /** URL de un campo imagen, o null si no se subió ninguna. */
    public static function imagen(string $clave): ?string
    {
        $v = self::valor($clave);
        return $v ? asset('storage/' . $v) : null;
    }

    /**
     * Bloques activos y vigentes hoy de una sección. Los requisitos y
     * documentos se filtran por la gestión que se está inscribiendo.
     */
    public static function bloques(string $seccion, ?int $gestion = null)
    {
        try {
            $hoy = now()->toDateString();
            $q = LandingBloque::where('lb_seccion', $seccion)->where('lb_activo', 1)
                ->where(fn($w) => $w->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $hoy))
                ->where(fn($w) => $w->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy));
            if ($gestion) $q->where(fn($w) => $w->whereNull('ges_anio')->orWhere('ges_anio', $gestion));
            return $q->orderBy('lb_orden')->orderByDesc('lb_fecha')->orderBy('lb_id')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /** Gestión de la que hablan las páginas de inscripción: la abierta, o la vigente. */
    public static function gestionInscripcion(): int
    {
        return GestionActual::inscripcionAbierta() ?? GestionActual::vigente();
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        DB::table('landing_secciones')->updateOrInsert(['ls_clave' => $clave],
            ['ls_valor' => $valor, 'ls_usuario' => optional(auth()->user())->us_id, 'ls_fecha' => now()]);
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CACHE);
        self::$valores = null;
    }

    /**
     * Limpia el HTML del editor: sólo etiquetas de formato, sin scripts,
     * estilos, atributos on* ni enlaces javascript:.
     */
    public static function limpiarHtml(?string $html): ?string
    {
        if ($html === null) return null;
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags($html, self::ETIQUETAS_HTML);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data):[^"\'\s>]*\2/i', '$1="#"', $html);
        $html = trim($html);
        return in_array($html, ['', '<p><br></p>', '<p></p>'], true) ? null : $html;
    }
}
