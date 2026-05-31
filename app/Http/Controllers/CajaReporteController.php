<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes de caja de mensualidades (sección "REPORTES" del documento):
 *  - Diario: total cobrado, efectivo, QR, mixto y N° de estudiantes cobrados,
 *            con detalle por estudiante (método, recibo, WhatsApp).
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
                'efectivo'  => $grp->sum('pagos_monto_efectivo'),
                'qr'        => $grp->sum('pagos_monto_qr'),
                'total'     => $grp->sum('pagos_precio'),
                'metodo'    => $first->pagos_metodo,
                'whatsapp'  => (bool) $grp->max('pagos_via_whatsapp'),
                'transf'    => $first->pagos_transferencia_nro,
            ];
        })->values();

        $vista = $formato === 'termico' ? 'pagos.caja-diario-termico' : 'pagos.caja-diario-pdf';
        $pdf = Pdf::loadView($vista, compact('fecha', 'resumen', 'detalle'));
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
                'efectivo'    => $grp->sum('pagos_monto_efectivo'),
                'qr'          => $grp->sum('pagos_monto_qr'),
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

    /** Totales por método sobre una colección de pagos. */
    private function resumen($pagos): array
    {
        $efectivo = (float) $pagos->sum('pagos_monto_efectivo');
        $qr       = (float) $pagos->sum('pagos_monto_qr');
        $total    = (float) $pagos->sum('pagos_precio');
        return [
            'total'        => $total,
            'efectivo'     => $efectivo,
            'qr'           => $qr,
            'mixto'        => $pagos->where('pagos_metodo', 'MIXTO')->sum('pagos_precio'),
            'n_pagos'      => $pagos->count(),
        ];
    }
}
