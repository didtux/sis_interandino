<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->tieneCapacidad('auditoria.ver')) abort(403, 'Su rol no tiene la capacidad «Ver la auditoría del sistema».');

        $query = Auditoria::orderBy('audit_fecha', 'desc');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('audit_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('audit_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('usuario')) {
            $query->where('audit_usuario_nombre', 'like', '%' . $request->usuario . '%');
        }
        if ($request->filled('modulo')) {
            $query->where('audit_modulo', $request->modulo);
        }
        if ($request->filled('accion')) {
            $query->where('audit_accion', $request->accion);
        }

        $registros = $query->paginate(50);

        $modulos = Auditoria::select('audit_modulo')->distinct()->orderBy('audit_modulo')->pluck('audit_modulo');

        return view('auditoria.index', compact('registros', 'modulos'));
    }
}
