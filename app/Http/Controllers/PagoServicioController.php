<?php

namespace App\Http\Controllers;

use App\Models\PagoServicio;
use App\Models\Servicio;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PagoServicioController extends Controller
{
    public function index(Request $request)
    {
        $query = PagoServicio::with('servicio', 'estudiante', 'padreFamilia');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('pserv_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('pserv_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('serv_codigo')) {
            $query->where('serv_codigo', $request->serv_codigo);
        }
        if (request()->filled('est_codigo')) {
            $query->where('est_codigo', request('est_codigo'));
        }
        if (request('estado') === '0') {
            $query->where('pserv_estado', 0);
        } elseif (request('estado') === 'activos') {
            $query->where('pserv_estado', 1);
        }

        $pagos = $query->orderBy('pserv_fecha', 'desc')->paginate(20);
        $servicios = Servicio::activo()->get();
        $estudiantes = Estudiante::visible()->get();

        return view('pagos-servicios.index', compact('pagos', 'servicios', 'estudiantes'));
    }

    public function create()
    {
        $servicios = Servicio::activo()->get();
        $estudiantes = Estudiante::visible()->get();
        $padres = PadreFamilia::activo()->get();
        return view('pagos-servicios.create', compact('servicios', 'estudiantes', 'padres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'serv_codigo' => 'required',
            'est_codigo' => 'required',
            'pserv_monto' => 'required|numeric|min:0',
            'pserv_descuento' => 'nullable|numeric|min:0'
        ]);

        $descuento = $request->pserv_descuento ?? 0;
        $total = $request->pserv_monto - $descuento;

        PagoServicio::create([
            'pserv_codigo' => 'PSERV' . time(),
            'serv_codigo' => $request->serv_codigo,
            'est_codigo' => $request->est_codigo,
            'pfam_codigo' => $request->pfam_codigo,
            'pserv_monto' => $request->pserv_monto,
            'pserv_descuento' => $descuento,
            'pserv_total' => $total,
            'pserv_observacion' => $request->pserv_observacion,
            'pserv_usuario' => auth()->user()->us_codigo,
            'pserv_estado' => 1
        ]);

        return redirect()->route('pagos-servicios.index')->with('success', 'Pago registrado exitosamente');
    }

    public function reportePdf(Request $request)
    {
        $query = PagoServicio::with('servicio', 'estudiante', 'padreFamilia')
            ->where('pserv_estado', 1);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('pserv_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('pserv_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('serv_codigo')) {
            $query->where('serv_codigo', $request->serv_codigo);
        }
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }

        $pagos = $query->orderBy('pserv_fecha', 'desc')->get();
        $total = $pagos->sum('pserv_total');

        $pdf = Pdf::loadView('pagos-servicios.reporte-pdf', compact('pagos', 'total', 'request'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-pagos-servicios-' . date('Y-m-d') . '.pdf');
    }

    public function anular($id)
    {
        $pago = PagoServicio::findOrFail($id);
        $pago->pserv_estado = 0;
        $pago->save();
        
        return response()->json(['success' => true, 'message' => 'Pago anulado']);
    }

    public function recibo($id)
    {
        $pago = PagoServicio::with('servicio', 'estudiante.curso', 'padreFamilia')->findOrFail($id);
        $pdf = Pdf::loadView('pagos-servicios.recibo', compact('pago'))
            ->setPaper([0, 0, 396, 612], 'portrait');
        return $pdf->stream('recibo-servicio-' . $pago->pserv_codigo . '.pdf');
    }
}
