<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\DocenteAsistencia;
use App\Models\DocenteKardex;
use App\Models\DocenteDisciplinario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class KardexDocenteController extends Controller
{
    private function soloDireccion()
    {
        $user = auth()->user();
        if (!in_array($user->rol_id, [1, 4])) {
            abort(403, 'Solo dirección puede gestionar este módulo.');
        }
    }

    // ─── Vista principal: tabs (asistencia, kardex, disciplinario) ───
    public function index(Request $request)
    {
        $this->soloDireccion();

        $tab      = $request->input('tab', 'kardex');
        $docCod   = $request->input('doc_codigo');
        $docentes = Docente::orderBy('doc_apellidos')->orderBy('doc_nombres')->get();

        $kardex          = collect();
        $asistencias     = collect();
        $disciplinarios  = collect();

        if ($docCod) {
            $kardex = DocenteKardex::where('doc_codigo', $docCod)
                ->orderByDesc('kdx_fecha_solicitud')->paginate(20, ['*'], 'k');
            $asistencias = DocenteAsistencia::where('doc_codigo', $docCod)
                ->orderByDesc('dasist_fecha')->orderByDesc('dasist_hora')->paginate(20, ['*'], 'a');
            $disciplinarios = DocenteDisciplinario::where('doc_codigo', $docCod)
                ->orderByDesc('disc_fecha')->paginate(20, ['*'], 'd');
        }

        return view('kardex-docente.index', compact(
            'tab','docCod','docentes','kardex','asistencias','disciplinarios'
        ));
    }

    // ─── Asistencia ───
    public function storeAsistencia(Request $request)
    {
        $this->soloDireccion();
        $request->validate([
            'doc_codigo'    => 'required|exists:colegio_docentes,doc_codigo',
            'dasist_fecha'  => 'required|date',
            'dasist_hora'   => 'required',
            'dasist_tipo'   => 'required|in:ENTRADA,SALIDA,UNICO',
        ]);
        DocenteAsistencia::create([
            'doc_codigo'           => $request->doc_codigo,
            'dasist_fecha'         => $request->dasist_fecha,
            'dasist_hora'          => $request->dasist_hora,
            'dasist_tipo'          => $request->dasist_tipo,
            'dasist_origen'        => 'MANUAL',
            'dasist_observacion'   => $request->dasist_observacion,
            'dasist_registrado_por'=> auth()->user()->us_id,
        ]);
        return back()->with('success', 'Asistencia registrada.');
    }

    /** Endpoint para QR docente (sin auth restrictiva, identifica al docente por código). */
    public function qrAsistencia(Request $request)
    {
        $request->validate(['doc_codigo' => 'required|exists:colegio_docentes,doc_codigo']);
        $hoy = now()->toDateString();
        $hora = now()->format('H:i:s');
        // Si ya hay una asistencia hoy del docente, registra SALIDA; si no, ENTRADA.
        $ya = DocenteAsistencia::where('doc_codigo', $request->doc_codigo)
            ->whereDate('dasist_fecha', $hoy)->orderByDesc('dasist_hora')->first();
        $tipo = $ya && $ya->dasist_tipo === 'ENTRADA' ? 'SALIDA' : 'ENTRADA';
        DocenteAsistencia::create([
            'doc_codigo'    => $request->doc_codigo,
            'dasist_fecha'  => $hoy,
            'dasist_hora'   => $hora,
            'dasist_tipo'   => $tipo,
            'dasist_origen' => 'QR',
        ]);
        return response()->json(['ok' => true, 'tipo' => $tipo, 'hora' => substr($hora, 0, 5)]);
    }

    // ─── Kardex ───
    public function storeKardex(Request $request)
    {
        $this->soloDireccion();
        $request->validate([
            'doc_codigo'         => 'required|exists:colegio_docentes,doc_codigo',
            'kdx_tipo_documento' => 'required',
            'kdx_titulo'         => 'required|max:150',
            'kdx_fecha_solicitud'=> 'required|date',
            'kdx_archivo'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
        $data = $request->except('kdx_archivo');
        $data['kdx_creado_por'] = auth()->user()->us_id;
        $data['kdx_estado']     = $data['kdx_estado'] ?? 'PENDIENTE';

        if ($request->hasFile('kdx_archivo')) {
            $file = $request->file('kdx_archivo');
            $name = 'kdx_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/kardex-docente'), $name);
            $data['kdx_archivo'] = $name;
        }

        DocenteKardex::create($data);
        return back()->with('success', 'Documento registrado en el kardex.');
    }

    public function updateKardexEstado(Request $request, $id)
    {
        $this->soloDireccion();
        $request->validate(['kdx_estado' => 'required|in:PENDIENTE,ENTREGADO,OBSERVADO,RECHAZADO']);
        $row = DocenteKardex::findOrFail($id);
        $row->kdx_estado        = $request->kdx_estado;
        $row->kdx_observacion   = $request->kdx_observacion;
        $row->kdx_fecha_recibido = $request->kdx_estado === 'ENTREGADO' ? now()->toDateString() : null;
        $row->save();
        return back()->with('success', 'Estado actualizado.');
    }

    // ─── Disciplinario ───
    public function storeDisciplinario(Request $request)
    {
        $this->soloDireccion();
        $request->validate([
            'doc_codigo'      => 'required|exists:colegio_docentes,doc_codigo',
            'disc_fecha'      => 'required|date',
            'disc_tipo'       => 'required|max:30',
            'disc_descripcion'=> 'required|max:500',
            'disc_evidencia'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $data = $request->except('disc_evidencia');
        $data['disc_registrado_por'] = auth()->user()->us_id;

        if ($request->hasFile('disc_evidencia')) {
            $file = $request->file('disc_evidencia');
            $name = 'disc_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/disciplinario'), $name);
            $data['disc_evidencia'] = $name;
        }

        DocenteDisciplinario::create($data);
        return back()->with('success', 'Incidencia registrada.');
    }

    // ─── Reportes ───
    public function reporteDocentePdf($docCod)
    {
        $this->soloDireccion();
        $docente = Docente::where('doc_codigo', $docCod)->firstOrFail();
        $asistencias    = DocenteAsistencia::where('doc_codigo', $docCod)->orderByDesc('dasist_fecha')->get();
        $kardex         = DocenteKardex::where('doc_codigo', $docCod)->orderByDesc('kdx_fecha_solicitud')->get();
        $disciplinarios = DocenteDisciplinario::where('doc_codigo', $docCod)->orderByDesc('disc_fecha')->get();

        $pdf = Pdf::loadView('kardex-docente.reporte-docente-pdf',
            compact('docente','asistencias','kardex','disciplinarios'))->setPaper('letter');
        return $pdf->stream('kardex-'.$docCod.'.pdf');
    }

    public function reporteGeneralPdf()
    {
        $this->soloDireccion();
        $pendientes  = DocenteKardex::with('docente')->where('kdx_estado','PENDIENTE')->orderBy('kdx_fecha_entrega')->get();
        $observados  = DocenteKardex::with('docente')->where('kdx_estado','OBSERVADO')->orderBy('kdx_fecha_entrega')->get();
        $disc        = DocenteDisciplinario::with('docente')->orderByDesc('disc_fecha')->limit(100)->get();
        $pdf = Pdf::loadView('kardex-docente.reporte-general-pdf',
            compact('pendientes','observados','disc'))->setPaper('letter');
        return $pdf->stream('kardex-general.pdf');
    }
}
