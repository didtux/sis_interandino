<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Publicacion;
use Illuminate\Http\Request;

/**
 * Publicaciones del colegio para el portal de padres: revista, lista de útiles
 * por curso y gestión, reglamento, horarios, misión y visión, agenda.
 */
class PublicacionController extends Controller
{
    public function index(Request $request)
    {
        $q = Publicacion::with('curso')->orderByDesc('ges_anio')->orderBy('pub_tipo')->orderBy('pub_orden');
        if ($request->filled('tipo')) $q->where('pub_tipo', $request->tipo);
        if ($request->filled('gestion')) $q->where(fn($w) => $w->where('ges_anio', $request->gestion)->orWhereNull('ges_anio'));
        $publicaciones = $q->get();
        return view('publicaciones.index', compact('publicaciones'));
    }

    public function create()
    {
        return view('publicaciones.form', ['pub' => new Publicacion(['pub_tipo' => request('tipo', 'LISTA_UTILES'), 'ges_anio' => gestion_actual(), 'visible_padres' => 1, 'pub_formato' => 'PDF']),
                                           'cursos' => Curso::visible()->ordenado()->get()]);
    }

    public function edit($id)
    {
        return view('publicaciones.form', ['pub' => Publicacion::findOrFail($id), 'cursos' => Curso::visible()->ordenado()->get()]);
    }

    private function datos(Request $request, ?Publicacion $actual = null): array
    {
        $request->validate([
            'pub_tipo'        => 'required|in:' . implode(',', array_keys(Publicacion::TIPOS)),
            'pub_titulo'      => 'required|max:150',
            'pub_formato'     => 'required|in:PDF,IMAGEN,HTML,LINK',
            'ges_anio'        => 'nullable|digits:4',
            'niv_nombre'      => 'nullable|in:INICIAL,PRIMARIA,SECUNDARIA',
            'cur_codigo'      => 'nullable|exists:colegio_cursos,cur_codigo',
            'archivo'         => ($request->pub_formato === 'PDF' || $request->pub_formato === 'IMAGEN') && !optional($actual)->pub_archivo
                                    ? 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp' : 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png,webp',
            'pub_url'         => 'required_if:pub_formato,LINK|nullable|url|max:255',
            'publicado_desde' => 'nullable|date',
            'publicado_hasta' => 'nullable|date|after_or_equal:publicado_desde',
            'pub_orden'       => 'nullable|integer',
        ], ['archivo.required' => 'Suba el archivo (PDF de hasta 20 MB o imagen).']);

        $d = $request->only('pub_tipo', 'pub_titulo', 'pub_descripcion', 'ges_anio', 'niv_nombre', 'cur_codigo', 'pub_formato',
                             'pub_contenido_html', 'pub_url', 'publicado_desde', 'publicado_hasta');
        $d['pub_orden'] = (int) $request->input('pub_orden', 0);
        $d['visible_padres'] = $request->boolean('visible_padres') ? 1 : 0;
        if ($request->hasFile('archivo')) {
            $d['pub_archivo'] = $request->file('archivo')->store('publicaciones', 'public');
        }
        return $d;
    }

    public function store(Request $request)
    {
        Publicacion::create($this->datos($request) + ['pub_estado' => 1, 'pub_usuario' => auth()->user()->us_id, 'pub_fecha' => now()]);
        return redirect()->route('publicaciones.index')->with('success', 'Publicación guardada.');
    }

    public function update(Request $request, $id)
    {
        $pub = Publicacion::findOrFail($id);
        $pub->update($this->datos($request, $pub));
        return redirect()->route('publicaciones.index')->with('success', 'Publicación actualizada.');
    }

    public function destroy($id)
    {
        Publicacion::findOrFail($id)->update(['pub_estado' => 0]);
        return back()->with('success', 'Publicación retirada del portal.');
    }

    /** Copia las publicaciones de una gestión a la siguiente (útiles, horarios…) para editarlas. */
    public function duplicar(Request $request)
    {
        $request->validate(['desde' => 'required|digits:4']);
        $desde = (int) $request->desde;
        $n = 0;
        foreach (Publicacion::where('ges_anio', $desde)->where('pub_estado', 1)->get() as $p) {
            $copia = $p->replicate();
            $copia->ges_anio = $desde + 1;
            $copia->visible_padres = 0; // se revisa antes de mostrar
            $copia->pub_fecha = now();
            $copia->pub_usuario = auth()->user()->us_id;
            $copia->save();
            $n++;
        }
        return back()->with('success', "{$n} publicaciones copiadas a " . ($desde + 1) . ' (ocultas hasta que las revise).');
    }
}
