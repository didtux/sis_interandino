<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Pago;
use App\Models\PreinscripcionPago;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Recibos generados en el servidor (media carta o térmico de 80 mm).
 *
 * Los de mensualidad e inscripción se armaban con jsPDF en el navegador; los
 * nuevos (reserva de cupo y devolución) sólo existen acá. Todos comparten el
 * mismo formato y el monto en letras sale de monto_literal().
 *
 * Tipos: mensualidad (pagos_codigo), inscripcion (inscpago_recibo),
 * reserva (prepago_recibo), devolucion (dev_codigo).
 */
class ReciboController extends Controller
{
    public function mostrar(Request $request, string $tipo, string $codigo)
    {
        $r = match ($tipo) {
            'mensualidad' => $this->mensualidad($codigo),
            'inscripcion' => $this->inscripcion($codigo),
            'reserva'     => $this->reserva($codigo),
            'devolucion'  => $this->devolucion($codigo),
            default       => abort(404),
        };
        $r->literal = monto_literal($r->total);
        $termica = $request->input('formato') === 'termica';
        $config = \App\Models\SistemaConfiguracion::actual();

        // La reserva de cupo usa en media carta el mismo formato que el recibo
        // de mensualidad (original y copia)
        $vista = $termica ? 'recibos.termica' : ($tipo === 'reserva' ? 'recibos.clasico' : 'recibos.carta');
        $pdf = Pdf::loadView($vista, compact('r', 'config'));
        $termica ? $pdf->setPaper([0, 0, 226.77, 520 + 18 * count($r->lineas)], 'portrait')
                 : $pdf->setPaper([0, 0, 396, 612], 'landscape'); // media carta apaisada: 612 × 396 pt (DomPDF gira las medidas)
        return $pdf->stream('recibo-' . $codigo . '.pdf');
    }

    private function base(string $titulo, string $codigo): object
    {
        return (object) [
            'titulo' => $titulo, 'codigo' => $codigo, 'fecha' => null, 'estudiante' => null, 'curso' => null,
            'padre' => null, 'lineas' => [], 'total' => 0, 'metodo' => null, 'efectivo' => 0, 'qr' => 0,
            'usuario' => null, 'nota' => null, 'anulado' => false, 'anulado_motivo' => null, 'egreso' => false,
        ];
    }

    private function mensualidad(string $codigo): object
    {
        $pagos = Pago::with('estudiante.curso', 'padreFamilia')->where('pagos_codigo', $codigo)->get();
        abort_if($pagos->isEmpty(), 404);
        $p = $pagos->first();
        $r = $this->base('RECIBO DE MENSUALIDAD', $codigo);
        $r->fecha = $p->pagos_fecha;
        $r->padre = optional($p->padreFamilia)->pfam_nombres;
        $r->estudiante = $pagos->map(fn($x) => trim(optional($x->estudiante)->est_apellidos . ' ' . optional($x->estudiante)->est_nombres))->unique()->implode(', ');
        $r->curso = $pagos->map(fn($x) => optional(optional($x->estudiante)->curso)->cur_nombre)->filter()->unique()->implode(', ');
        foreach ($pagos as $x) {
            $r->lineas[] = [$x->concepto . ($pagos->pluck('est_codigo')->unique()->count() > 1 ? ' — ' . optional($x->estudiante)->est_nombres : ''), (float) $x->pagos_precio];
        }
        $r->total = $pagos->sum('pagos_precio');
        $r->metodo = $p->pagos_metodo;
        $r->efectivo = $pagos->sum('pagos_monto_efectivo');
        $r->qr = $pagos->sum('pagos_monto_qr');
        $r->usuario = $p->pagos_usuario;
        $r->anulado = $pagos->every(fn($x) => (int) $x->pagos_estado === 0);
        $r->anulado_motivo = $p->anulado_motivo;
        return $r;
    }

    private function inscripcion(string $codigo): object
    {
        $ip = InscripcionPago::where('inscpago_recibo', $codigo)->firstOrFail();
        $insc = Inscripcion::with('estudiante.curso', 'padreFamilia')->where('insc_codigo', $ip->insc_codigo)->first();
        $r = $this->base('RECIBO DE INSCRIPCIÓN', $codigo);
        $r->fecha = $ip->inscpago_fecha;
        $r->estudiante = trim(optional(optional($insc)->estudiante)->est_apellidos . ' ' . optional(optional($insc)->estudiante)->est_nombres);
        $r->curso = optional(\App\Models\Curso::where('cur_codigo', optional($insc)->cur_codigo)->first())->cur_nombre;
        $r->padre = optional(optional($insc)->padreFamilia)->pfam_nombres;
        $r->lineas[] = [$ip->inscpago_concepto . ' — gestión ' . optional($insc)->insc_gestion, (float) $ip->inscpago_monto];
        $r->total = (float) $ip->inscpago_monto;
        $r->usuario = $ip->inscpago_usuario;
        $r->anulado = (int) ($ip->inscpago_estado ?? 1) === 0;
        return $r;
    }

    private function reserva(string $codigo): object
    {
        $pp = PreinscripcionPago::with('preinscripcion.cursoDestino', 'preinscripcion.estudiante', 'preinscripcion.padre')
            ->where('prepago_recibo', $codigo)->firstOrFail();
        $pre = $pp->preinscripcion;
        $r = $this->base('RESERVA DE CUPO — GESTIÓN ' . $pre->ges_anio, $codigo);
        $r->fecha = $pp->prepago_fecha;
        $r->estudiante = $pre->nombre_completo;
        $r->curso = optional($pre->cursoDestino)->cur_nombre;
        $r->padre = optional($pre->padre)->pfam_nombres;
        $r->lineas[] = ['Reserva de cupo gestión ' . $pre->ges_anio . ' - Pre-inscripción ' . $pre->preinsc_codigo, (float) $pp->prepago_monto];
        $r->total = (float) $pp->prepago_monto;
        $r->metodo = $pp->prepago_metodo;
        $r->efectivo = (float) $pp->monto_efectivo;
        $r->qr = (float) $pp->monto_qr;
        $r->usuario = optional(\App\Models\User::find($pp->prepago_usuario))->us_user;
        $r->anulado = (int) $pp->prepago_estado === 0;
        $r->anulado_motivo = $pp->anulado_motivo;
        $ret = (float) parametro('devolucion_retencion_pct', 0);
        $r->nota = 'Este monto se descuenta de la primera mensualidad (febrero) de la gestión ' . $pre->ges_anio . '. '
            . ($pre->preinsc_condicional ? 'La reserva queda sujeta a que el estudiante apruebe la gestión ' . ($pre->ges_anio - 1) . '. ' : '')
            . 'En caso de devolución se retiene el ' . rtrim(rtrim(number_format($ret, 2, ',', ''), '0'), ',') . '% del monto.';
        return $r;
    }

    private function devolucion(string $codigo): object
    {
        $d = Devolucion::with('estudiante.curso', 'preinscripcion')->where('dev_codigo', $codigo)->firstOrFail();
        $r = $this->base('RECIBO DE DEVOLUCIÓN', $codigo);
        $r->egreso = true;
        $r->fecha = $d->pagado_fecha;
        $r->estudiante = $d->estudiante ? trim($d->estudiante->est_apellidos . ' ' . $d->estudiante->est_nombres) : optional($d->preinscripcion)->nombre_completo;
        $r->curso = optional(optional($d->estudiante)->curso)->cur_nombre;
        $r->lineas[] = ['Devolución de ' . strtolower($d->origen_tipo) . ' — recibo ' . $d->recibo_origen . ' (' . (Devolucion::MOTIVOS[$d->dev_motivo] ?? $d->dev_motivo) . ')', (float) $d->monto_original];
        if ($d->monto_retenido > 0) $r->lineas[] = ['Retención', -(float) $d->monto_retenido];
        $r->total = (float) $d->monto_devuelto;
        $r->metodo = $d->dev_metodo;
        $r->usuario = optional(\App\Models\User::find($d->pagado_por))->us_user;
        $r->nota = 'El recibo original ' . $d->recibo_origen . ' no se anula: esta devolución es un egreso de caja.';
        return $r;
    }
}
