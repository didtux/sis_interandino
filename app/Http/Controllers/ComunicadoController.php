<?php

namespace App\Http\Controllers;

use App\Models\Comunicado;
use App\Models\ComunicadoDestinatario;
use App\Models\Docente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComunicadoController extends Controller
{
    /** Dirección / administración: Admin(1), Director General(9), Directora(10), Secretaría(11). */
    private function esDireccion(): bool
    {
        $u = auth()->user();
        return $u && in_array($u->rol_id, [1, 9, 10, 11]);
    }

    private function esDocente(): bool
    {
        $u = auth()->user();
        return $u && $u->us_entidad_tipo === 'docente';
    }

    // ── Lado DIRECCIÓN ───────────────────────────────────────────────
    public function index()
    {
        if (!$this->esDireccion()) abort(403, 'Acceso restringido.');

        $comunicados = Comunicado::with('destinatarios.docente')
            ->orderByDesc('com_fecha')->paginate(15);
        $docentes = Docente::visible()->orderBy('doc_apellidos')->orderBy('doc_nombres')->get();

        return view('comunicados.index', compact('comunicados', 'docentes'));
    }

    public function store(Request $request)
    {
        if (!$this->esDireccion()) abort(403);

        $request->validate([
            'com_titulo'       => 'required|max:150',
            'com_descripcion'  => 'nullable|max:2000',
            'com_fecha_limite' => 'nullable|date',
            'com_archivo'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:8192',
            'destino'          => 'required|in:todos,seleccion',
            'docentes'         => 'required_if:destino,seleccion|array',
        ]);

        $u = auth()->user();
        $data = [
            'com_titulo'            => $request->com_titulo,
            'com_descripcion'       => $request->com_descripcion,
            'com_fecha_limite'      => $request->com_fecha_limite,
            'com_requiere_archivo'  => $request->boolean('com_requiere_archivo', true) ? 1 : 0,
            'com_para_todos'        => $request->destino === 'todos' ? 1 : 0,
            'com_creado_por'        => $u->us_id,
            'com_creado_por_nombre' => trim(($u->us_nombres ?? '') . ' ' . ($u->us_apellidos ?? '')),
            'com_fecha'             => now(),
            'com_estado'            => 1,
        ];

        if ($request->hasFile('com_archivo')) {
            $data['com_archivo'] = $request->file('com_archivo')->store('comunicados', 'public');
        }

        $com = Comunicado::create($data);

        // Destinatarios
        $codigos = $request->destino === 'todos'
            ? Docente::visible()->pluck('doc_codigo')->all()
            : (array) $request->docentes;

        foreach (array_unique($codigos) as $cod) {
            ComunicadoDestinatario::create([
                'com_id'    => $com->com_id,
                'doc_codigo'=> $cod,
                'cd_estado' => 'PENDIENTE',
            ]);
        }

        return redirect()->route('comunicados.index')
            ->with('success', 'Comunicado enviado a ' . count($codigos) . ' docente(s).');
    }

    public function anular(Request $request, $id)
    {
        if (!$this->esDireccion()) abort(403);
        $request->validate(['com_motivo_anulacion' => 'required|max:255']);

        $com = Comunicado::findOrFail($id);
        $com->update(['com_estado' => 0, 'com_motivo_anulacion' => $request->com_motivo_anulacion]);

        return back()->with('success', 'Comunicado anulado.');
    }

    /** Dirección observa la entrega de un docente. */
    public function observar(Request $request, $cdId)
    {
        if (!$this->esDireccion()) abort(403);
        $request->validate(['cd_observacion' => 'nullable|max:255']);

        $dest = ComunicadoDestinatario::findOrFail($cdId);
        $dest->update(['cd_observacion' => $request->cd_observacion]);

        return back()->with('success', 'Observación guardada.');
    }

    public function reportePdf($id)
    {
        if (!$this->esDireccion()) abort(403);

        $comunicado = Comunicado::with('destinatarios.docente')->findOrFail($id);
        $limite = $comunicado->com_fecha_limite;
        $filas = $comunicado->destinatarios->map(function ($d) use ($limite) {
            $d->estado_entrega = $d->estadoEntrega($limite);
            return $d;
        });

        $pdf = Pdf::loadView('comunicados.reporte-pdf', compact('comunicado', 'filas'))->setPaper('letter');
        return $pdf->stream('comunicado-' . $id . '.pdf');
    }

    // ── Lado DOCENTE ─────────────────────────────────────────────────
    public function misComunicados()
    {
        if (!$this->esDocente()) abort(403, 'Solo docentes.');
        $docCodigo = auth()->user()->us_entidad_id;

        $items = ComunicadoDestinatario::with('comunicado')
            ->where('doc_codigo', $docCodigo)
            ->get()
            ->filter(fn($d) => $d->comunicado && $d->comunicado->com_estado == 1)
            ->sortByDesc(fn($d) => $d->comunicado->com_fecha)
            ->values();

        return view('comunicados.docente', compact('items'));
    }

    public function subirArchivo(Request $request, $cdId)
    {
        if (!$this->esDocente()) abort(403);
        $request->validate([
            'cd_archivo' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:8192',
        ]);

        $dest = ComunicadoDestinatario::with('comunicado')->findOrFail($cdId);
        if ($dest->doc_codigo !== auth()->user()->us_entidad_id) abort(403);
        if (!$dest->comunicado || $dest->comunicado->com_estado != 1) {
            return back()->with('error', 'El comunicado ya no está activo.');
        }

        // Borrar archivo anterior
        if ($dest->cd_archivo && Storage::disk('public')->exists($dest->cd_archivo)) {
            Storage::disk('public')->delete($dest->cd_archivo);
        }

        $path = $request->file('cd_archivo')->store('comunicados/entregas', 'public');
        $dest->update([
            'cd_archivo'       => $path,
            'cd_fecha_entrega' => now(),
            'cd_estado'        => 'ENTREGADO',
        ]);

        return back()->with('success', 'Documento entregado correctamente.');
    }
}
