<?php

namespace App\Http\Controllers;

use App\Models\ContactoMensaje;
use Illuminate\Http\Request;

/** Bandeja de los mensajes que llegan por el formulario de contacto público. */
class ContactoMensajeController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'pendientes');

        $query = ContactoMensaje::with('atendidoPor')->orderByDesc('created_at');
        if ($estado === 'pendientes') {
            $query->whereIn('cm_estado', ['NUEVO', 'EN_PROCESO']);
        } elseif ($estado !== 'todos') {
            $query->where('cm_estado', $estado);
        }
        if ($request->filled('motivo')) {
            $query->where('cm_motivo', $request->motivo);
        }
        if ($request->filled('buscar')) {
            $b = '%' . trim($request->buscar) . '%';
            $query->where(fn($q) => $q->where('cm_nombre', 'like', $b)
                ->orWhere('cm_telefono', 'like', $b)
                ->orWhere('cm_correo', 'like', $b)
                ->orWhere('cm_mensaje', 'like', $b));
        }

        $mensajes = $query->paginate(25)->withQueryString();
        $conteo = ContactoMensaje::selectRaw('cm_estado, COUNT(*) n')->groupBy('cm_estado')->pluck('n', 'cm_estado');

        return view('contacto-mensajes.index', compact('mensajes', 'conteo', 'estado'));
    }

    public function show($id)
    {
        $mensaje = ContactoMensaje::with('atendidoPor')->findOrFail($id);
        return view('contacto-mensajes.show', compact('mensaje'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cm_estado'       => 'required|in:' . implode(',', array_keys(ContactoMensaje::ESTADOS)),
            'cm_nota_interna' => 'nullable|string|max:2000',
        ]);

        $mensaje = ContactoMensaje::findOrFail($id);
        $mensaje->cm_estado = $request->cm_estado;
        $mensaje->cm_nota_interna = $request->cm_nota_interna;
        if ($request->cm_estado !== 'NUEVO') {
            $mensaje->cm_atendido_por = auth()->user()->us_id;
            $mensaje->cm_atendido_at = now();
        }
        $mensaje->save();

        return redirect()->route('contacto-mensajes.show', $mensaje->cm_id)->with('success', 'Mensaje actualizado.');
    }
}
