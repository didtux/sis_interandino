<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;

class DescuentoController extends Controller
{
    public function index()
    {
        $descuentos = Descuento::orderBy('desc_fecha_registro', 'desc')->get();
        return view('descuentos.index', compact('descuentos'));
    }

    /**
     * Listado de ESTUDIANTES con descuento, filtrable por gestión.
     *
     * La pestaña Descuentos es el catálogo de tipos (Hermanos, Docente…), no
     * la lista de quiénes lo tienen: para saber a qué alumnos se les aplicó un
     * descuento había que ir a Inscripciones y filtrar de a uno.
     */
    public function estudiantes(Request $request)
    {
        $gestiones = \App\Models\Inscripcion::select('insc_gestion')->distinct()
            ->orderBy('insc_gestion', 'desc')->pluck('insc_gestion')->filter()->values();
        $gestion = $request->input('gestion', date('Y'));

        $query = \App\Models\Inscripcion::with(['estudiante.curso', 'padreFamilia', 'descuentos'])
            ->whereHas('descuentos')
            ->where('insc_estado', '!=', 0);          // las anuladas no cuentan

        if ($gestion !== 'todas') {
            $query->where('insc_gestion', $gestion);
        }
        if ($request->filled('desc_id')) {
            $query->whereHas('descuentos', fn($q) => $q->where('descuentos.desc_id', $request->desc_id));
        }
        if ($request->filled('cur_codigo')) {
            $query->where('cur_codigo', $request->cur_codigo);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('estudiante', function ($q) use ($buscar) {
                $q->where('est_nombres', 'like', "%$buscar%")
                  ->orWhere('est_apellidos', 'like', "%$buscar%")
                  ->orWhere('est_ci', 'like', "%$buscar%");
            });
        }

        $inscripciones = $query->get();

        // Orden en PHP: las colaciones mezcladas de este esquema hacen que
        // ordenar entre tablas en SQL no sea confiable.
        $inscripciones = $inscripciones->sortBy([
            fn($a, $b) => ($a->curso->cur_orden ?? 999) <=> ($b->curso->cur_orden ?? 999),
            fn($a, $b) => strcmp($a->estudiante->est_apellidos ?? '', $b->estudiante->est_apellidos ?? ''),
        ])->values();

        $totalDescuento = $inscripciones->sum('insc_monto_descuento');
        $catalogo = Descuento::orderBy('desc_nombre')->get();
        $cursos   = \App\Models\Curso::visible()->orderBy('cur_orden')->orderBy('cur_nombre')->get();

        if ($request->input('formato') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('descuentos.estudiantes-pdf',
                    compact('inscripciones', 'totalDescuento', 'gestion'))
                ->setPaper('letter', 'portrait');
            return $pdf->stream('estudiantes-con-descuento-' . $gestion . '.pdf');
        }

        return view('descuentos.estudiantes', compact(
            'inscripciones', 'totalDescuento', 'gestiones', 'gestion', 'catalogo', 'cursos'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'desc_nombre' => 'required|string|max:100',
            'desc_porcentaje' => 'required|numeric|min:0|max:100'
        ]);

        Descuento::create([
            'desc_codigo' => 'DESC' . time(),
            'desc_nombre' => $request->desc_nombre,
            'desc_porcentaje' => $request->desc_porcentaje,
            'desc_estado' => 1
        ]);

        return redirect()->route('descuentos.index')->with('success', 'Descuento creado');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'desc_nombre' => 'required|string|max:100',
            'desc_porcentaje' => 'required|numeric|min:0|max:100'
        ]);

        $descuento = Descuento::findOrFail($id);
        $descuento->update([
            'desc_nombre' => $request->desc_nombre,
            'desc_porcentaje' => $request->desc_porcentaje
        ]);

        return redirect()->route('descuentos.index')->with('success', 'Descuento actualizado');
    }

    public function destroy($id)
    {
        $descuento = Descuento::findOrFail($id);
        $descuento->update(['desc_estado' => 0]);
        return redirect()->route('descuentos.index')->with('success', 'Descuento eliminado');
    }
}
