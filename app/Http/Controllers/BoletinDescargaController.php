<?php

namespace App\Http\Controllers;

use App\Models\BoletinDescarga;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\PagoServicio;
use App\Models\Servicio;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletinDescargaController extends Controller
{
    /** Solo admin / dirección pueden auditar reimpresiones. */
    private function autorizar()
    {
        $u = auth()->user();
        if (!$u || !in_array($u->rol_id, [1, 4])) {
            abort(403, 'Solo dirección puede auditar reimpresiones.');
        }
    }

    public function index(Request $request)
    {
        $this->autorizar();

        $curCodigo = $request->input('cur_codigo');
        $estCodigo = $request->input('est_codigo');
        $gestion   = $request->input('gestion', date('Y'));
        $trim      = $request->input('trimestre');
        $estado    = $request->input('estado'); // '', 'cobrable', 'cobrado', 'anulada', 'primera'
        $desde     = $request->input('desde');
        $hasta     = $request->input('hasta');

        $q = BoletinDescarga::with(['estudiante.curso', 'pagoServicio'])
            ->where('descarga_gestion', (int) $gestion)
            ->orderByDesc('descarga_fecha');

        if ($estCodigo) $q->where('est_codigo', $estCodigo);
        if ($curCodigo) {
            $ests = Inscripcion::where('cur_codigo', $curCodigo)
                ->where('insc_gestion', $gestion)->where('insc_estado', 1)
                ->pluck('est_codigo');
            $q->whereIn('est_codigo', $ests);
        }
        if ($trim === 'anual') $q->whereNull('descarga_trimestre');
        elseif (in_array($trim, ['1','2','3'])) $q->where('descarga_trimestre', $trim);

        if ($estado === 'cobrable') $q->where('descarga_cobrable', 1)->whereNull('pserv_id_cobro')->where('descarga_anulada', 0);
        elseif ($estado === 'cobrado')  $q->whereNotNull('pserv_id_cobro');
        elseif ($estado === 'anulada')  $q->where('descarga_anulada', 1);
        elseif ($estado === 'primera')  $q->where('descarga_numero_copia', 1)->where('descarga_anulada', 0);

        if ($desde) $q->whereDate('descarga_fecha', '>=', $desde);
        if ($hasta) $q->whereDate('descarga_fecha', '<=', $hasta);

        $descargas = $q->paginate(25)->withQueryString();

        $resumen = BoletinDescarga::where('descarga_gestion', (int) $gestion)
            ->selectRaw("
                SUM(CASE WHEN descarga_anulada = 0 THEN 1 ELSE 0 END) AS total_validas,
                SUM(CASE WHEN descarga_anulada = 0 AND descarga_cobrable = 1 AND pserv_id_cobro IS NULL THEN 1 ELSE 0 END) AS cobrables_pendientes,
                SUM(CASE WHEN pserv_id_cobro IS NOT NULL THEN 1 ELSE 0 END) AS cobradas,
                SUM(CASE WHEN descarga_anulada = 1 THEN 1 ELSE 0 END) AS anuladas
            ")->first();

        $cursos = Curso::orderBy('cur_nombre')->get();

        return view('notas.reimpresiones.index', compact(
            'descargas','resumen','cursos','curCodigo','estCodigo','gestion','trim','estado','desde','hasta'
        ));
    }

    /** Anula una descarga (registro queda para auditoría, no cuenta como copia). */
    public function anular(Request $request, $id)
    {
        $this->autorizar();
        $request->validate(['motivo' => 'required|max:255']);

        $d = BoletinDescarga::findOrFail($id);
        if ($d->descarga_anulada) {
            return back()->with('error', 'Esta descarga ya estaba anulada.');
        }
        if ($d->pserv_id_cobro) {
            return back()->with('error', 'No se puede anular: la descarga ya tiene un cobro asociado.');
        }

        $datosAnt = $d->toArray();
        $d->descarga_anulada        = 1;
        $d->descarga_anulada_motivo = $request->motivo;
        $d->descarga_anulada_por    = auth()->user()->us_id;
        $d->descarga_anulada_at     = now();
        $d->save();

        Auditoria::registrar('editar', 'boletines_descargas',
            "Anulación descarga #{$id} - {$request->motivo}", $id, $datosAnt, $d->toArray());

        return back()->with('success', 'Descarga anulada. No contará como copia.');
    }

    /** Genera el cobro en pagos_servicios para una reimpresión cobrable. */
    public function cobrar(Request $request, $id)
    {
        $this->autorizar();

        $d = BoletinDescarga::with('estudiante')->findOrFail($id);
        if ($d->descarga_anulada)   return back()->with('error', 'Descarga anulada — no se puede cobrar.');
        if (!$d->descarga_cobrable) return back()->with('error', 'Esta descarga no es cobrable (es la primera copia).');
        if ($d->pserv_id_cobro)     return back()->with('error', 'Esta descarga ya tiene un cobro registrado.');

        $servicio = Servicio::where('serv_codigo', 'REIMPR_BOLETIN')->where('serv_estado', 1)->first();
        if (!$servicio) {
            return back()->with('error', 'No existe el servicio "REIMPR_BOLETIN" en el catálogo de servicios.');
        }

        $monto = $request->input('monto', $servicio->serv_costo);
        $monto = max(0, (float) $monto);

        DB::beginTransaction();
        try {
            $pago = PagoServicio::create([
                'pserv_codigo'      => 'REIMPR-' . str_pad($d->descarga_id, 6, '0', STR_PAD_LEFT),
                'serv_codigo'       => $servicio->serv_codigo,
                'est_codigo'        => $d->est_codigo,
                'pfam_codigo'       => optional($d->estudiante)->pfam_codigo,
                'pserv_monto'       => $monto,
                'pserv_descuento'   => 0,
                'pserv_total'       => $monto,
                'pserv_observacion' => 'Reimpresión boletín ' . ($d->descarga_trimestre ? "T{$d->descarga_trimestre}" : 'anual')
                                    . " gestión {$d->descarga_gestion} - Copia N° {$d->descarga_numero_copia}",
                'pserv_usuario'     => auth()->user()->us_codigo ?? auth()->user()->us_id,
                'pserv_estado'      => 1, // 1 = activo / vigente. (0 = anulado)
            ]);

            $d->pserv_id_cobro = $pago->pserv_id;
            $d->save();

            Auditoria::registrar('crear', 'boletines_descargas',
                "Cobro de reimpresión #{$id} → pago_servicio #{$pago->pserv_id} ({$monto} Bs)", $id);

            DB::commit();
            return back()->with('success', "Cobro generado: {$monto} Bs (pendiente de pago en módulo Pagos > Servicios).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al generar el cobro: ' . $e->getMessage());
        }
    }
}
