<?php

namespace App\Http\Controllers;

use App\Models\ContactoMensaje;
use App\Models\Gestion;
use App\Support\Landing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class LandingController extends Controller
{
    /** Segundos mínimos entre que se carga el formulario y se envía. */
    private const SEGUNDOS_MINIMOS = 3;

    public function index()
    {
        return view('landing.index');
    }

    public function nosotros()
    {
        return view('landing.nosotros');
    }

    public function niveles()
    {
        return view('landing.niveles');
    }

    public function historia()
    {
        return view('landing.historia');
    }

    /**
     * Requisitos y pasos de pre-inscripción e inscripción, por nivel, de la
     * gestión que se está inscribiendo (la abierta, o la vigente).
     */
    public function inscripciones()
    {
        $anio = Landing::gestionInscripcion();
        $requisitos = Landing::bloques('REQUISITO', $anio);
        return view('landing.inscripciones', [
            'anio'        => $anio,
            'gestion'     => rescue(fn() => Gestion::where('ges_anio', $anio)->first(), null, false),
            'generales'   => $requisitos->whereNull('niv_nombre')->values(),
            'porNivel'    => $requisitos->whereNotNull('niv_nombre')->groupBy('niv_nombre'),
            'documentos'  => Landing::bloques('DOCUMENTO', $anio),
        ]);
    }

    public function contacto(Request $request)
    {
        // "Solicitar inscripción" llega con ?motivo=inscripcion y el motivo
        // queda elegido.
        $motivo = strtoupper((string) $request->query('motivo', ''));
        if (!array_key_exists($motivo, ContactoMensaje::MOTIVOS)) $motivo = '';

        return view('landing.contacto', [
            'motivoInicial' => old('motivo', $motivo),
            'marcaTiempo'   => Crypt::encryptString((string) time()),
            'motivos'       => ContactoMensaje::MOTIVOS,
        ]);
    }

    /**
     * Guarda el mensaje del formulario de contacto. Antes el formulario no
     * enviaba nada y el JavaScript mostraba "¡Mensaje recibido!" igual, así que
     * todas las consultas de los padres se perdían.
     */
    public function enviarContacto(Request $request)
    {
        // Antispam: campo trampa (los robots lo llenan) y tiempo mínimo de
        // llenado. Se responde como si se hubiera recibido, para no darle pistas.
        if ($request->filled('sitio_web') || $this->enviadoMuyRapido($request->input('t'))) {
            return redirect()->route('landing.contacto')->with('contacto_ok', true);
        }

        $datos = $request->validate([
            'nombre'        => 'required|string|max:120',
            'telefono'      => 'nullable|required_without:correo|string|max:30',
            'correo'        => 'nullable|required_without:telefono|email|max:120',
            'motivo'        => 'required|in:' . implode(',', array_keys(ContactoMensaje::MOTIVOS)),
            'curso_interes' => 'nullable|string|max:80',
            'mensaje'       => 'required|string|max:3000',
        ], [
            'telefono.required_without' => 'Dejanos un teléfono o un correo para poder responderte.',
            'correo.required_without'   => 'Dejanos un teléfono o un correo para poder responderte.',
        ]);

        ContactoMensaje::create([
            'cm_nombre'        => trim($datos['nombre']),
            'cm_telefono'      => $datos['telefono'] ?? null,
            'cm_correo'        => $datos['correo'] ?? null,
            'cm_motivo'        => $datos['motivo'],
            'cm_curso_interes' => $datos['curso_interes'] ?? null,
            'cm_mensaje'       => trim($datos['mensaje']),
            'cm_estado'        => 'NUEVO',
            'cm_ip'            => $request->ip(),
            'cm_user_agent'    => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()->route('landing.contacto')->with('contacto_ok', true);
    }

    private function enviadoMuyRapido(?string $marca): bool
    {
        try {
            $cargado = (int) Crypt::decryptString((string) $marca);
        } catch (\Throwable $e) {
            return true; // sin marca válida: no vino del formulario
        }
        return (time() - $cargado) < self::SEGUNDOS_MINIMOS;
    }
}
