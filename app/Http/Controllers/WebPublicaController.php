<?php

namespace App\Http\Controllers;

use App\Models\LandingBloque;
use App\Models\SistemaConfiguracion;
use App\Support\Landing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Web pública editable: textos de cada página (config/landing.php), bloques
 * repetibles (avisos, requisitos, documentos, autoridades, hitos) y redes
 * sociales del colegio. Antes todo estaba fijo en las vistas.
 */
class WebPublicaController extends Controller
{
    // ── Textos ──────────────────────────────────────────────────────────────

    public function textos(Request $request)
    {
        $paginas = config('landing.paginas');
        $pagina = array_key_exists($request->pagina, $paginas) ? $request->pagina : array_key_first($paginas);
        return view('web-publica.textos', compact('paginas', 'pagina'));
    }

    public function guardarTextos(Request $request)
    {
        $paginas = config('landing.paginas');
        abort_unless(array_key_exists($request->pagina, $paginas), 404);
        $request->validate(['img.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096'],
                           ['img.*.image' => 'La imagen debe ser JPG, PNG o WEBP de hasta 4 MB.', 'img.*.max' => 'La imagen debe pesar hasta 4 MB.']);

        $cambios = 0;
        DB::transaction(function () use ($request, $paginas, &$cambios) {
            $valores = (array) $request->input('valor', []);
            $imagenes = (array) $request->file('img', []);
            $quitar = (array) $request->input('quitar', []);
            foreach ($paginas[$request->pagina]['campos'] as $clave => [$etiqueta, $tipo, $original]) {
                $campo = self::nombreCampo($clave);
                if ($tipo === 'imagen') {
                    if (isset($imagenes[$campo])) {
                        Landing::guardar($clave, $imagenes[$campo]->store('web-publica', 'public'));
                        $cambios++;
                    } elseif (!empty($quitar[$campo])) {
                        DB::table('landing_secciones')->where('ls_clave', $clave)->delete();
                        $cambios++;
                    }
                    continue;
                }
                $valor = $valores[$campo] ?? null;
                if ($valor === null) continue;
                $valor = $tipo === 'html' ? Landing::limpiarHtml($valor) : trim(str_replace("\r\n", "\n", $valor));

                // Vacío o igual al original: se borra la fila y la web muestra el texto original.
                $igual = $valor === null || $valor === '' || $this->normalizar($valor) === $this->normalizar($original);
                $antes = DB::table('landing_secciones')->where('ls_clave', $clave)->value('ls_valor');
                if ($igual) {
                    if ($antes !== null) { DB::table('landing_secciones')->where('ls_clave', $clave)->delete(); $cambios++; }
                } elseif ($antes !== $valor) {
                    Landing::guardar($clave, mb_substr($valor, 0, 60000));
                    $cambios++;
                }
            }
        });
        Landing::olvidar();
        return redirect()->route('web-publica.textos', ['pagina' => $request->pagina])
            ->with('success', $cambios ? "Página actualizada ({$cambios} cambio" . ($cambios > 1 ? 's' : '') . ').' : 'No hubo cambios.');
    }

    /** Vuelve un texto al original de la web. */
    public function restaurar(Request $request)
    {
        abort_unless(Landing::campo((string) $request->clave), 404);
        DB::table('landing_secciones')->where('ls_clave', $request->clave)->delete();
        Landing::olvidar();
        return back()->with('success', 'Se restauró el texto original.');
    }

    /**
     * Nombre del campo del formulario para una clave: los puntos de
     * "inicio.slide1.titulo" Laravel los leería como niveles de un arreglo.
     */
    public static function nombreCampo(string $clave): string
    {
        return str_replace('.', '__', $clave);
    }

    private function normalizar(?string $s): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $s));
    }

    // ── Bloques ─────────────────────────────────────────────────────────────

    public function bloques(Request $request)
    {
        $secciones = config('landing.bloques');
        $seccion = array_key_exists($request->seccion, $secciones) ? $request->seccion : array_key_first($secciones);
        $bloques = LandingBloque::where('lb_seccion', $seccion)->orderByDesc('lb_activo')->orderBy('lb_orden')->orderByDesc('lb_id')->get();
        return view('web-publica.bloques', compact('secciones', 'seccion', 'bloques'));
    }

    public function create(Request $request)
    {
        $seccion = array_key_exists($request->seccion, config('landing.bloques')) ? $request->seccion : 'AVISO';
        $bloque = new LandingBloque(['lb_seccion' => $seccion, 'lb_activo' => 1, 'lb_orden' => 0,
            'ges_anio' => in_array($seccion, ['REQUISITO', 'DOCUMENTO']) ? Landing::gestionInscripcion() : null,
            'lb_fecha' => $seccion === 'AVISO' ? now() : null]);
        return view('web-publica.bloque-form', compact('bloque'));
    }

    public function edit($id)
    {
        return view('web-publica.bloque-form', ['bloque' => LandingBloque::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        return $this->guardarBloque($request, new LandingBloque);
    }

    public function update(Request $request, $id)
    {
        return $this->guardarBloque($request, LandingBloque::findOrFail($id));
    }

    private function guardarBloque(Request $request, LandingBloque $bloque)
    {
        $request->validate([
            'lb_seccion'    => 'required|in:' . implode(',', array_keys(config('landing.bloques'))),
            'lb_titulo'     => 'required|max:150',
            'lb_subtitulo'  => 'nullable|max:150',
            'lb_link'       => 'nullable|url|max:255',
            'lb_link_texto' => 'nullable|max:60',
            'lb_fecha'      => 'nullable|date',
            'niv_nombre'    => 'nullable|in:' . implode(',', array_keys(LandingBloque::NIVELES)),
            'ges_anio'      => 'nullable|digits:4',
            'vigente_desde' => 'nullable|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'lb_orden'      => 'nullable|integer',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'archivo'       => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ], [
            'imagen.max' => 'La imagen debe pesar hasta 4 MB.', 'archivo.max' => 'El archivo debe pesar hasta 10 MB.',
            'vigente_hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a "desde".',
        ]);

        $d = $request->only('lb_seccion', 'lb_titulo', 'lb_subtitulo', 'lb_link', 'lb_link_texto', 'lb_fecha',
                             'niv_nombre', 'ges_anio', 'vigente_desde', 'vigente_hasta');
        $d['lb_texto'] = Landing::limpiarHtml($request->input('lb_texto'));
        $d['lb_orden'] = (int) $request->input('lb_orden', 0);
        $d['lb_activo'] = $request->boolean('lb_activo') ? 1 : 0;
        if ($request->hasFile('imagen')) $d['lb_imagen'] = $request->file('imagen')->store('web-publica', 'public');
        elseif ($request->boolean('quitar_imagen')) $d['lb_imagen'] = null;
        if ($request->hasFile('archivo')) $d['lb_archivo'] = $request->file('archivo')->store('web-publica', 'public');
        elseif ($request->boolean('quitar_archivo')) $d['lb_archivo'] = null;

        if (!$bloque->exists) {
            $d['lb_usuario'] = auth()->user()->us_id;
            $d['lb_fecha_registro'] = now();
        }
        $bloque->fill($d)->save();
        return redirect()->route('web-publica.bloques', ['seccion' => $bloque->lb_seccion])->with('success', 'Bloque guardado.');
    }

    public function destroy($id)
    {
        $bloque = LandingBloque::findOrFail($id);
        $bloque->delete();
        return redirect()->route('web-publica.bloques', ['seccion' => $bloque->lb_seccion])->with('success', 'Bloque eliminado.');
    }

    // ── Redes y contacto ────────────────────────────────────────────────────

    public function redes()
    {
        return view('web-publica.redes', ['config' => SistemaConfiguracion::actual() ?? new SistemaConfiguracion]);
    }

    public function guardarRedes(Request $request)
    {
        $data = $request->validate([
            'config_direccion' => 'nullable|max:255',
            'config_telefono'  => 'nullable|max:50',
            'config_email'     => 'nullable|email|max:100',
            'config_horario'   => 'nullable|max:150',
            'config_facebook'  => 'nullable|url|max:255',
            'config_instagram' => 'nullable|url|max:255',
            'config_tiktok'    => 'nullable|url|max:255',
            'config_youtube'   => 'nullable|url|max:255',
            'config_whatsapp'  => ['nullable', 'max:30', 'regex:/^\+?[\d\s-]{7,}$/'],
            'config_mapa_url'  => 'nullable|url|max:500',
        ], ['config_whatsapp.regex' => 'El WhatsApp debe ser un número con código de país, p.ej. 591 71234567.', 'url' => 'El enlace debe empezar con https://']);

        $config = SistemaConfiguracion::actual() ?? new SistemaConfiguracion(['config_denominacion' => 'UNIDAD EDUCATIVA']);
        $config->fill($data)->save();
        return redirect()->route('web-publica.redes')->with('success', 'Datos de contacto y redes actualizados.');
    }
}
