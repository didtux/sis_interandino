<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes de caja de mensualidades (sección "REPORTES" del documento):
 *  - Diario: total cobrado, efectivo, QR y N° de estudiantes cobrados, con
 *            detalle por estudiante (recibo, efectivo, QR, WhatsApp). Un pago
 *            mixto no tiene columna propia: su parte en efectivo suma a
 *            Efectivo y su parte QR a QR, que es como se cuadra la caja.
 *  - Por período (semanal/mensual/por fechas): totales por día.
 * Formato carta (PDF) y térmico (PDF angosto 80mm).
 */
class CajaReporteController extends Controller
{
    /** Reporte de caja de un día. */
    public function diario(Request $request)
    {
        $fecha   = $request->input('fecha', date('Y-m-d'));
        $formato = $request->input('formato', 'carta'); // carta | termico

        $pagos = Pago::with('estudiante.curso', 'padreFamilia')
            ->where('pagos_estado', 1)
            ->whereDate('pagos_fecha', $fecha)
            ->orderBy('pagos_codigo')->orderBy('est_codigo')
            ->get();

        $resumen = $this->resumen($pagos);
        // # estudiantes distintos cobrados
        $resumen['estudiantes'] = $pagos->pluck('est_codigo')->unique()->count();

        // Agrupar por recibo para el detalle (una fila por recibo+estudiante)
        $detalle = $pagos->groupBy(function ($p) {
            return $p->pagos_codigo . '|' . $p->est_codigo;
        })->map(function ($grp) {
            $first = $grp->first();
            return (object) [
                'recibo'    => $first->pagos_codigo,
                'recibo_nro'=> $first->pagos_recibo_nro,
                'fecha'     => $first->pagos_fecha,
                'estudiante'=> $first->estudiante,
                'curso'     => optional(optional($first->estudiante)->curso)->cur_nombre,
                'conceptos' => $grp->pluck('concepto')->implode(', '),
                'efectivo'  => $grp->sum(fn($p) => $this->montos($p)[0]),
                'qr'        => $grp->sum(fn($p) => $this->montos($p)[1]),
                'total'     => $grp->sum('pagos_precio'),
                'metodo'    => $first->pagos_metodo,
                'whatsapp'  => (bool) $grp->max('pagos_via_whatsapp'),
                'transf'    => $first->pagos_transferencia_nro,
            ];
        })->values();

        // Reservas de cupo cobradas ese día (ingreso) y devoluciones pagadas ese
        // día (egreso). La devolución no anula el recibo original: es una salida
        // de dinero y se descuenta del efectivo o QR de la caja del día.
        [$reservas, $devoluciones] = $this->movimientosExtra($fecha, $fecha);
        $resumen['reservas']          = (float) $reservas->sum('prepago_monto');
        $resumen['devoluciones']      = (float) $devoluciones->sum('monto_devuelto');
        $resumen['efectivo_neto']     = $resumen['efectivo'] + (float) $reservas->sum('monto_efectivo')
                                        - (float) $devoluciones->where('dev_metodo', 'EFECTIVO')->sum('monto_devuelto');
        $resumen['qr_neto']           = $resumen['qr'] + (float) $reservas->sum('monto_qr')
                                        - (float) $devoluciones->where('dev_metodo', 'QR')->sum('monto_devuelto');

        $vista = $formato === 'termico' ? 'pagos.caja-diario-termico' : 'pagos.caja-diario-pdf';
        $pdf = Pdf::loadView($vista, compact('fecha', 'resumen', 'detalle', 'reservas', 'devoluciones'));
        if ($formato === 'termico') {
            $pdf->setPaper([0, 0, 226.77, 800], 'portrait'); // 80mm de ancho
        } else {
            $pdf->setPaper('letter');
        }
        return $pdf->stream('caja-diario-' . $fecha . '.pdf');
    }

    /** Reporte por período: totales por día. */
    public function periodo(Request $request)
    {
        $desde = $request->input('desde', date('Y-m-01'));
        $hasta = $request->input('hasta', date('Y-m-d'));

        $pagos = Pago::where('pagos_estado', 1)
            ->whereDate('pagos_fecha', '>=', $desde)
            ->whereDate('pagos_fecha', '<=', $hasta)
            ->orderBy('pagos_fecha')->get();

        $porDia = $pagos->groupBy(fn($p) => $p->pagos_fecha->format('Y-m-d'))->map(function ($grp, $dia) {
            return (object) [
                'fecha'       => $dia,
                'efectivo'    => $grp->sum(fn($p) => $this->montos($p)[0]),
                'qr'          => $grp->sum(fn($p) => $this->montos($p)[1]),
                'total'       => $grp->sum('pagos_precio'),
                'estudiantes' => $grp->pluck('est_codigo')->unique()->count(),
                'recibo_min'  => $grp->min('pagos_codigo'),
                'recibo_max'  => $grp->max('pagos_codigo'),
            ];
        })->values();

        $resumen = $this->resumen($pagos);
        $resumen['estudiantes'] = $pagos->pluck('est_codigo')->unique()->count();

        $pdf = Pdf::loadView('pagos.caja-periodo-pdf', compact('desde', 'hasta', 'porDia', 'resumen'))->setPaper('letter');
        return $pdf->stream('caja-periodo-' . $desde . '_' . $hasta . '.pdf');
    }

    /** Reservas de cupo (ingreso) y devoluciones pagadas (egreso) de un rango. */
    private function movimientosExtra(string $desde, string $hasta): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('preinscripciones_pagos')) {
            return [collect(), collect()];
        }
        $reservas = \App\Models\PreinscripcionPago::with('preinscripcion')->where('prepago_estado', 1)
            ->whereDate('prepago_fecha', '>=', $desde)->whereDate('prepago_fecha', '<=', $hasta)->get();
        $devoluciones = \App\Models\Devolucion::with('estudiante', 'preinscripcion')->where('dev_estado', 'PAGADA')
            ->whereDate('pagado_fecha', '>=', $desde)->whereDate('pagado_fecha', '<=', $hasta)->get();
        return [$reservas, $devoluciones];
    }

    /** Totales por método sobre una colección de pagos. */
    private function resumen($pagos): array
    {
        return [
            'total'    => (float) $pagos->sum('pagos_precio'),
            'efectivo' => (float) $pagos->sum(fn($p) => $this->montos($p)[0]),
            'qr'       => (float) $pagos->sum(fn($p) => $this->montos($p)[1]),
            'n_pagos'  => $pagos->count(),
        ];
    }

    /**
     * [efectivo, qr] de un pago. Los pagos cargados antes de que existieran
     * las columnas por método tienen 0 y 0: se reparten según pagos_metodo
     * para que Efectivo + QR siempre dé el total cobrado.
     * (database/sql/upgrade_2026_11_pagos_montos_metodo.sql corrige los datos.)
     */
    private function montos($p): array
    {
        $ef = (float) $p->pagos_monto_efectivo;
        $qr = (float) $p->pagos_monto_qr;
        if ($ef == 0 && $qr == 0) {
            return $p->pagos_metodo === 'QR' ? [0.0, (float) $p->pagos_precio] : [(float) $p->pagos_precio, 0.0];
        }
        return [$ef, $qr];
    }
}
