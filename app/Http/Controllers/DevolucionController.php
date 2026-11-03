<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Pago;
use App\Models\Preinscripcion;
use App\Services\ReciboNumerador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Devoluciones de dinero: SOLICITADA → APROBADA (capacidad devoluciones.aprobar)
 * → PAGADA (recibo DEV, egreso en la caja de ese día). El recibo original no
 * se anula ni se modifica: anular es corregir un error de caja; devolver es
 * entregar dinero.
 */
class DevolucionController extends Controller
{
    public function index(Request $request)
    {
        $q = Devolucion::with('estudiante', 'preinscripcion')->orderByDesc('dev_id');
        if ($request->filled('estado')) $q->where('dev_estado', $request->estado);
        $devoluciones = $q->paginate(30)->withQueryString();
        return view('devoluciones.index', compact('devoluciones'));
    }

    /** Formulario: desde una pre-inscripción (?preinsc=) o buscando un recibo (?recibo=). */
    public function create(Request $request)
    {
        $origen = $request->filled('preinsc')
            ? $this->origenReserva((int) $request->preinsc)
            : ($request->filled('recibo') ? $this->origenRecibo(trim($request->recibo)) : null);
        $retencion = (float) parametro('devolucion_retencion_pct', 0);
        return view('devoluciones.create', compact('origen', 'retencion'));
    }

    private function origenReserva(int $preinscId): ?array
    {
        $pre = Preinscripcion::with('pagos')->find($preinscId);
        if (!$pre) return null;
        $pp = $pre->pagos->where('prepago_estado', 1)->first();
        if (!$pp) return null;
        return ['tipo' => 'RESERVA', 'recibo' => $pp->prepago_recibo, 'monto' => $pre->monto_reserva,
                'est_codigo' => $pre->est_codigo, 'preinsc_id' => $pre->preinsc_id, 'pfam_codigo' => $pre->pfam_codigo,
                'ges_anio' => $pre->ges_anio, 'nombre' => $pre->nombre_completo,
                'detalle' => 'Reserva de cupo ' . $pre->ges_anio . ' (' . $pre->preinsc_codigo . ')'];
    }

    private function origenRecibo(string $recibo): ?array
    {
        if ($pp = \App\Models\PreinscripcionPago::where('prepago_recibo', $recibo)->where('prepago_estado', 1)->first()) {
            return $this->origenReserva((int) $pp->preinsc_id);
        }
        if ($ip = InscripcionPago::where('inscpago_recibo', $recibo)->where('inscpago_estado', 1)->where('inscpago_origen', 'CAJA')->first()) {
            $insc = Inscripcion::with('estudiante')->where('insc_codigo', $ip->insc_codigo)->first();
            return ['tipo' => 'INSCRIPCION', 'recibo' => $recibo, 'monto' => (float) $ip->inscpago_monto,
                    'est_codigo' => optional($insc)->est_codigo, 'preinsc_id' => null, 'pfam_codigo' => optional($insc)->pfam_codigo,
                    'ges_anio' => optional($insc)->insc_gestion, 'nombre' => trim(optional(optional($insc)->estudiante)->est_apellidos . ' ' . optional(optional($insc)->estudiante)->est_nombres),
                    'detalle' => $ip->inscpago_concepto];
        }
        $pagos = Pago::with('estudiante')->where('pagos_codigo', $recibo)->where('pagos_estado', 1)->get();
        if ($pagos->isNotEmpty()) {
            $p = $pagos->first();
            return ['tipo' => 'MENSUALIDAD', 'recibo' => $recibo, 'monto' => (float) $pagos->sum('pagos_precio'),
                    'est_codigo' => $p->est_codigo, 'preinsc_id' => null, 'pfam_codigo' => $p->pfam_codigo,
                    'ges_anio' => $p->pagos_gestion, 'nombre' => trim(optional($p->estudiante)->est_apellidos . ' ' . optional($p->estudiante)->est_nombres),
                    'detalle' => $pagos->pluck('concepto')->implode(', ')];
        }
        return null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'recibo'         => 'required',
            'monto_retenido' => 'required|numeric|min:0',
            'dev_motivo'     => 'required|in:' . implode(',', array_keys(Devolucion::MOTIVOS)),
            'dev_detalle'    => 'nullable|max:1000',
            'dev_metodo'     => 'required|in:EFECTIVO,QR',
        ]);
        $o = $this->origenRecibo($request->recibo);
        if (!$o) return back()->with('error', 'No se encontró un recibo vigente con ese número.')->withInput();

        $yaDevuelto = Devolucion::where('recibo_origen', $o['recibo'])->whereIn('dev_estado', ['SOLICITADA', 'APROBADA', 'PAGADA'])->sum('monto_original');
        if ($yaDevuelto + 0.01 >= $o['monto']) {
            return back()->with('error', 'El recibo ' . $o['recibo'] . ' ya tiene una devolución por el total.')->withInput();
        }
        $retenido = min((float) $request->monto_retenido, $o['monto']);

        $d = Devolucion::create([
            'origen_tipo' => $o['tipo'], 'recibo_origen' => $o['recibo'], 'est_codigo' => $o['est_codigo'],
            'preinsc_id' => $o['preinsc_id'], 'pfam_codigo' => $o['pfam_codigo'], 'ges_anio' => $o['ges_anio'],
            'monto_original' => $o['monto'], 'monto_retenido' => $retenido, 'monto_devuelto' => $o['monto'] - $retenido,
            'dev_motivo' => $request->dev_motivo, 'dev_detalle' => $request->dev_detalle, 'dev_metodo' => $request->dev_metodo,
            'dev_estado' => 'SOLICITADA', 'solicitado_por' => auth()->user()->us_id, 'solicitado_fecha' => now(),
        ]);
        return redirect()->route('devoluciones.index')->with('success', 'Devolución solicitada por Bs ' . number_format($d->monto_devuelto, 2) . '. Falta la aprobación.');
    }

    public function aprobar(Request $request, $id)
    {
        $this->soloAprobador();
        $d = Devolucion::where('dev_estado', 'SOLICITADA')->findOrFail($id);
        $d->update(['dev_estado' => 'APROBADA', 'aprobado_por' => auth()->user()->us_id, 'aprobado_fecha' => now()]);
        return back()->with('success', 'Devolución aprobada: caja ya puede pagarla.');
    }

    public function rechazar(Request $request, $id)
    {
        $this->soloAprobador();
        $request->validate(['motivo' => 'required|max:255']);
        $d = Devolucion::whereIn('dev_estado', ['SOLICITADA', 'APROBADA'])->findOrFail($id);
        $d->update(['dev_estado' => 'RECHAZADA', 'rechazo_motivo' => $request->motivo, 'aprobado_por' => auth()->user()->us_id, 'aprobado_fecha' => now()]);
        return back()->with('success', 'Devolución rechazada.');
    }

    /** Caja entrega el dinero: recibo DEV y egreso en la caja del día. */
    public function pagar(Request $request, $id)
    {
        $d = Devolucion::where('dev_estado', 'APROBADA')->findOrFail($id);
        DB::transaction(function () use ($d) {
            $d->update([
                'dev_codigo' => ReciboNumerador::siguiente('DEV'), 'dev_estado' => 'PAGADA',
                'pagado_por' => auth()->user()->us_id, 'pagado_fecha' => now(),
            ]);
            if ($d->origen_tipo === 'RESERVA' && $d->preinsc_id) {
                Preinscripcion::where('preinsc_id', $d->preinsc_id)->whereIn('preinsc_estado', ['RESERVADA', 'CONFIRMADA', 'AJUSTAR'])
                    ->update(['preinsc_estado' => 'DEVUELTA']);
            }
        });
        return redirect()->route('devoluciones.index')->with('success', 'Devolución pagada — recibo ' . $d->dev_codigo)
            ->with('abrir_recibo', route('recibos.mostrar', ['devolucion', $d->dev_codigo]));
    }

    private function soloAprobador(): void
    {
        if (!auth()->user()->tieneCapacidad('devoluciones.aprobar')) {
            abort(403, 'Su rol no tiene la capacidad «Aprobar o rechazar devoluciones».');
        }
    }
}
