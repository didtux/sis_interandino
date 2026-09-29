<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use App\Models\EstudianteRuta;
use App\Models\AsignacionTransporte;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $query = Ruta::with('estudiantes');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('ruta_nombre', 'like', "%{$buscar}%")
                  ->orWhere('ruta_codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('conductor')) {
            $query->whereHas('asignaciones', function($q) use ($request) {
                $q->where('chof_codigo', $request->conductor)->where('asig_estado', 1);
            });
        }

        if ($request->filled('estudiante')) {
            $query->whereHas('estudiantes', function($q) use ($request) {
                $q->where('est_codigo', $request->estudiante)->where('ter_estado', 1);
            });
        }

        $rutas = $query->orderBy('ruta_fecha_registro', 'desc')->get();
        return view('transporte.rutas.index', compact('rutas'));
    }

    public function create()
    {
        return view('transporte.rutas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ruta_nombre' => 'required'
        ]);

        Ruta::create([
            'ruta_codigo' => 'RUTA' . time(),
            'ruta_nombre' => $request->ruta_nombre,
            'ruta_descripcion' => $request->ruta_descripcion,
            'ruta_coordenadas' => $request->ruta_coordenadas,
            'ruta_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('rutas.index')->with('success', 'Ruta registrada');
    }

    public function edit($id)
    {
        $ruta = Ruta::findOrFail($id);
        return view('transporte.rutas.edit', compact('ruta'));
    }

    public function update(Request $request, $id)
    {
        $ruta = Ruta::findOrFail($id);
        
        $request->validate([
            'ruta_nombre' => 'required'
        ]);

        $ruta->update($request->all());
        return redirect()->route('rutas.index')->with('success', 'Ruta actualizada');
    }

    public function destroy($id)
    {
        $ruta = Ruta::findOrFail($id);
        $ruta->update(['ruta_estado' => 0]);
        return redirect()->route('rutas.index')->with('success', 'Ruta eliminada');
    }

    public function show($id)
    {
        abort(404);
    }

    public function detalle($id, Request $request)
    {
        $ruta = Ruta::with([
            'estudiantes.estudiante.curso',
            'estudiantes.pago',
            'asignaciones' => function($q) {
                $q->where('asig_estado', 1)->with(['chofer', 'vehiculo']);
            }
        ])->findOrFail($id);

        // Mes a consultar: la lista mostraba siempre el pago vinculado a la
        // asignación, sin importar de qué mes fuera, así que no había forma de
        // saber quién pagó un mes concreto.
        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $mes   = $request->input('mes', $meses[(int) date('n') - 1]);

        // Pagos vigentes de ese mes, por estudiante (los cancelados no cuentan).
        $codigos = $ruta->estudiantes->where('ter_estado', 1)->pluck('est_codigo')->filter()->all();
        $pagosMes = collect();
        if (!empty($codigos) && $mes !== 'todos') {
            $pagosMes = \App\Models\PagoTransporte::whereIn('est_codigo', $codigos)
                ->where('tpago_mes', $mes)
                ->where('tpago_estado', '!=', 'cancelado')
                ->get()
                ->groupBy('est_codigo')
                ->map(fn($g) => $g->sum('tpago_monto'));
        }

        return view('transporte.rutas.detalle', compact('ruta', 'meses', 'mes', 'pagosMes'));
    }

    public function reportePdf(Request $request)
    {
        $query = Ruta::with([
            'estudiantes.estudiante.curso',
            'estudiantes.pago',
            'asignaciones' => function($q) {
                $q->where('asig_estado', 1)->with(['chofer', 'vehiculo']);
            }
        ]);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('ruta_nombre', 'like', "%{$buscar}%")
                  ->orWhere('ruta_codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('conductor')) {
            $query->whereHas('asignaciones', function($q) use ($request) {
                $q->where('chof_codigo', $request->conductor)->where('asig_estado', 1);
            });
        }

        if ($request->filled('estudiante')) {
            $query->whereHas('estudiantes', function($q) use ($request) {
                $q->where('est_codigo', $request->estudiante)->where('ter_estado', 1);
            });
        }

        $rutas = $query->get();
        
        $pdf = Pdf::loadView('transporte.rutas.reporte-pdf', compact('rutas'))
            ->setPaper('letter', 'portrait');
        
        return $pdf->stream('reporte-rutas-' . date('Y-m-d') . '.pdf');
    }
}
