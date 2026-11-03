<?php

namespace App\Http\Controllers;

use App\Models\AtencionMensaje;
use App\Models\AtencionTicket;
use App\Models\CursoMateriaDocente;
use App\Models\PadreFamilia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Canal de atención a padres. El padre abre una consulta a un área desde su
 * portal y el personal de esa área responde en un hilo. Quién ve cada área
 * sale de Roles (submódulos atencion.*); los docentes sólo ven las consultas
 * de los cursos donde dictan clases. Las pantallas se refrescan solas cada
 * config('atencion.refresco_segundos').
 */
class AtencionController extends Controller
{
    // ════════════════════════════════════════════════════════════════════
    // Personal del colegio
    // ════════════════════════════════════════════════════════════════════

    /** Áreas que el usuario atiende: [área => ['responder' => bool, 'gestionar' => bool]]. */
    public static function areasDe(User $user): array
    {
        $areas = [];
        foreach (config('atencion.areas') as $clave => $a) {
            if ($user->tienePermiso($a['modulo'], 'perm_ver')) {
                $areas[$clave] = [
                    'responder' => $user->tienePermiso($a['modulo'], 'perm_crear'),
                    'gestionar' => $user->tienePermiso($a['modulo'], 'perm_editar'),
                ];
            }
        }
        return $areas;
    }

    /** Cursos del docente, o null si el usuario no es docente (ve todo el área). */
    private static function cursosDocente(User $user): ?array
    {
        if ($user->us_entidad_tipo !== 'docente' || !$user->us_entidad_id) return null;
        return CursoMateriaDocente::where('doc_codigo', $user->us_entidad_id)->where('curmatdoc_estado', 1)
            ->pluck('cur_codigo')->unique()->values()->all();
    }

    /** Consultas que el usuario puede ver. */
    public static function visiblesPara(User $user)
    {
        $areas = array_keys(self::areasDe($user));
        $cursos = in_array('DOCENTE', $areas, true) ? self::cursosDocente($user) : null;
        return AtencionTicket::where(function ($w) use ($areas, $cursos) {
            $w->whereRaw('1 = 0');
            foreach ($areas as $area) {
                if ($area === 'DOCENTE' && $cursos !== null) {
                    $w->orWhere(fn($v) => $v->where('at_area', 'DOCENTE')->whereIn('cur_codigo', $cursos ?: ['-']));
                } else {
                    $w->orWhere('at_area', $area);
                }
            }
        });
    }

    /** Consultas con mensajes del padre sin leer (para el globito del menú). */
    public static function pendientesPara(User $user): int
    {
        if (!self::areasDe($user)) return 0;
        $ids = self::visiblesPara($user)->where('at_estado', '!=', 'CERRADO')->pluck('at_id');
        return $ids->isEmpty() ? 0 : AtencionMensaje::whereIn('at_id', $ids)->where('am_autor', 'PADRE')->where('am_leido', 0)
            ->distinct()->count('at_id');
    }

    private function ticketDelPersonal($id): AtencionTicket
    {
        $user = auth()->user();
        abort_unless(self::areasDe($user), 403, 'Su rol no atiende consultas de padres.');
        $ticket = self::visiblesPara($user)->where('at_id', $id)->first();
        abort_unless($ticket, 404);
        return $ticket;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $areas = self::areasDe($user);
        abort_unless($areas, 403, 'Su rol no atiende consultas de padres (Roles → Atención a padres).');

        $q = self::visiblesPara($user)->with(['estudiante.curso', 'padre']);
        if ($request->filled('area') && isset($areas[$request->area])) $q->where('at_area', $request->area);
        $estado = $request->input('estado', 'pendientes');
        if ($estado === 'pendientes') $q->where('at_estado', 'ABIERTO');
        elseif (isset(AtencionTicket::ESTADOS[$estado])) $q->where('at_estado', $estado);
        if ($request->filled('buscar')) {
            $b = '%' . $request->buscar . '%';
            $q->where(fn($w) => $w->where('at_asunto', 'like', $b)
                ->orWhereHas('estudiante', fn($e) => $e->where('est_apellidos', 'like', $b)->orWhere('est_nombres', 'like', $b)));
        }
        $tickets = $q->orderByDesc('at_ultimo_mensaje')->paginate(25)->withQueryString();

        $noLeidos = AtencionMensaje::whereIn('at_id', $tickets->pluck('at_id'))->where('am_autor', 'PADRE')->where('am_leido', 0)
            ->selectRaw('at_id, COUNT(*) n')->groupBy('at_id')->pluck('n', 'at_id');
        $pendientesPorArea = self::visiblesPara($user)->where('at_estado', 'ABIERTO')
            ->selectRaw('at_area, COUNT(*) n')->groupBy('at_area')->pluck('n', 'at_area');

        return view('atencion.index', compact('tickets', 'areas', 'estado', 'noLeidos', 'pendientesPorArea'));
    }

    public function show($id)
    {
        $ticket = $this->ticketDelPersonal($id);
        $this->marcarLeidos($ticket, 'PADRE');
        $permisos = self::areasDe(auth()->user())[$ticket->at_area];
        return view('atencion.show', [
            'ticket'   => $ticket->load(['mensajes.usuario', 'estudiante.curso', 'padre']),
            'permisos' => $permisos,
            'areas'    => config('atencion.areas'),
        ]);
    }

    public function responder(Request $request, $id)
    {
        $ticket = $this->ticketDelPersonal($id);
        abort_unless(self::areasDe(auth()->user())[$ticket->at_area]['responder'], 403, 'Su rol puede leer pero no responder consultas de esta área.');
        $this->agregarMensaje($request, $ticket, 'PERSONAL');
        $ticket->update(['at_estado' => 'RESPONDIDO', 'at_ultimo_mensaje' => now()]);
        return $this->respuesta($request, route('atencion.show', $ticket->at_id), 'Respuesta enviada.');
    }

    public function estado(Request $request, $id)
    {
        $ticket = $this->ticketDelPersonal($id);
        abort_unless(self::areasDe(auth()->user())[$ticket->at_area]['gestionar'], 403, 'Su rol no puede cerrar consultas de esta área.');
        if ($request->accion === 'cerrar') {
            $ticket->update(['at_estado' => 'CERRADO', 'at_cerrado_por' => auth()->user()->us_id, 'at_cerrado_fecha' => now()]);
            $this->mensajeSistema($ticket, 'Consulta cerrada por ' . $this->nombre(auth()->user()) . '.');
        } else {
            $ticket->update(['at_estado' => 'ABIERTO', 'at_cerrado_por' => null, 'at_cerrado_fecha' => null]);
            $this->mensajeSistema($ticket, 'Consulta reabierta por ' . $this->nombre(auth()->user()) . '.');
        }
        return back()->with('success', $request->accion === 'cerrar' ? 'Consulta cerrada.' : 'Consulta reabierta.');
    }

    /** Pasa la consulta a otra área (p.ej. de secretaría a caja). */
    public function derivar(Request $request, $id)
    {
        $ticket = $this->ticketDelPersonal($id);
        abort_unless(self::areasDe(auth()->user())[$ticket->at_area]['gestionar'], 403, 'Su rol no puede derivar consultas de esta área.');
        $request->validate(['area' => 'required|in:' . implode(',', array_keys(config('atencion.areas')))]);
        if ($request->area === 'DOCENTE' && !$ticket->cur_codigo) {
            return back()->with('error', 'La consulta no indica un estudiante: no se puede derivar a los docentes del curso.');
        }
        $antes = $ticket->area_nombre;
        $ticket->update(['at_area' => $request->area, 'at_estado' => 'ABIERTO', 'at_ultimo_mensaje' => now()]);
        $this->mensajeSistema($ticket, "Derivada de {$antes} a {$ticket->fresh()->area_nombre} por " . $this->nombre(auth()->user()) . '.');
        $sigueViendo = self::visiblesPara(auth()->user())->where('at_id', $ticket->at_id)->exists();
        return $sigueViendo ? back()->with('success', 'Consulta derivada.')
                            : redirect()->route('atencion.index')->with('success', 'Consulta derivada a ' . $ticket->fresh()->area_nombre . '.');
    }

    /** Mensajes nuevos de un hilo abierto (refresco automático). */
    public function mensajes(Request $request, $id)
    {
        $ticket = $this->ticketDelPersonal($id);
        return $this->mensajesNuevos($request, $ticket, 'PADRE', 'personal');
    }

    public function contador()
    {
        return response()->json(['pendientes' => self::pendientesPara(auth()->user())]);
    }

    // ════════════════════════════════════════════════════════════════════
    // Portal de padres
    // ════════════════════════════════════════════════════════════════════

    private function padre(): PadreFamilia
    {
        $user = auth()->user();
        abort_unless($user->us_entidad_tipo === 'padre' && $user->us_entidad_id, 403);
        return PadreFamilia::where('pfam_codigo', $user->us_entidad_id)->firstOrFail();
    }

    private function ticketDelPadre($id): AtencionTicket
    {
        $this->padre();
        return AtencionTicket::where('at_id', $id)->where('us_id', auth()->user()->us_id)->firstOrFail();
    }

    /** Respuestas del colegio sin leer, para el globito del menú del padre. */
    public static function noLeidasPadre(User $user): int
    {
        return AtencionMensaje::whereIn('at_id', AtencionTicket::where('us_id', $user->us_id)->pluck('at_id'))
            ->whereIn('am_autor', ['PERSONAL', 'SISTEMA'])->where('am_leido', 0)->distinct()->count('at_id');
    }

    public function misConsultas()
    {
        $padre = $this->padre();
        $tickets = AtencionTicket::with('estudiante')->where('us_id', auth()->user()->us_id)
            ->orderByDesc('at_ultimo_mensaje')->get();
        $noLeidos = AtencionMensaje::whereIn('at_id', $tickets->pluck('at_id'))->whereIn('am_autor', ['PERSONAL', 'SISTEMA'])
            ->where('am_leido', 0)->selectRaw('at_id, COUNT(*) n')->groupBy('at_id')->pluck('n', 'at_id');
        return view('padre-portal.consultas.index', [
            'tickets' => $tickets, 'noLeidos' => $noLeidos, 'hijos' => $padre->hijos(), 'areas' => config('atencion.areas'),
        ]);
    }

    public function crearConsulta(Request $request)
    {
        $padre = $this->padre();
        $hijos = $padre->hijos()->keyBy('est_codigo');
        $request->validate([
            'area'       => 'required|in:' . implode(',', array_keys(config('atencion.areas'))),
            'est_codigo' => ['nullable', 'required_if:area,DOCENTE', fn($a, $v, $f) => $v && !$hijos->has($v) ? $f('Elija uno de sus hijos.') : null],
            'asunto'     => 'required|max:150',
            'texto'      => 'required|max:3000',
            'adjunto'    => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
        ], [
            'est_codigo.required_if' => 'Para escribir a los docentes indique a qué hijo/a se refiere.',
            'texto.required' => 'Escriba su consulta.', 'adjunto.max' => 'El archivo debe pesar hasta 5 MB.',
        ]);

        $ticket = DB::transaction(function () use ($request, $padre, $hijos) {
            $hijo = $request->est_codigo ? $hijos[$request->est_codigo] : null;
            $ticket = AtencionTicket::create([
                'at_area' => $request->area, 'at_asunto' => trim($request->asunto), 'us_id' => auth()->user()->us_id,
                'pfam_codigo' => $padre->pfam_codigo, 'est_codigo' => optional($hijo)->est_codigo, 'cur_codigo' => optional($hijo)->cur_codigo,
                'at_estado' => 'ABIERTO', 'at_ultimo_mensaje' => now(), 'at_fecha' => now(),
            ]);
            $this->agregarMensaje($request, $ticket, 'PADRE');
            return $ticket;
        });
        return redirect()->route('padre-portal.consultas.ver', $ticket->at_id)
            ->with('success', 'Consulta enviada a ' . $ticket->area_nombre . '. Le avisaremos aquí cuando respondan.');
    }

    public function verConsulta($id)
    {
        $ticket = $this->ticketDelPadre($id);
        $this->marcarLeidos($ticket, ['PERSONAL', 'SISTEMA']);
        return view('padre-portal.consultas.show', ['ticket' => $ticket->load(['mensajes.usuario', 'estudiante'])]);
    }

    public function responderPadre(Request $request, $id)
    {
        $ticket = $this->ticketDelPadre($id);
        if ($ticket->at_estado === 'CERRADO') {
            return back()->with('error', 'La consulta está cerrada. Abra una nueva si necesita algo más.');
        }
        $this->agregarMensaje($request, $ticket, 'PADRE');
        $ticket->update(['at_estado' => 'ABIERTO', 'at_ultimo_mensaje' => now()]);
        return $this->respuesta($request, route('padre-portal.consultas.ver', $ticket->at_id), 'Mensaje enviado.');
    }

    public function cerrarPadre($id)
    {
        $ticket = $this->ticketDelPadre($id);
        $ticket->update(['at_estado' => 'CERRADO', 'at_cerrado_por' => auth()->user()->us_id, 'at_cerrado_fecha' => now()]);
        $this->mensajeSistema($ticket, 'El padre/madre marcó la consulta como resuelta.');
        return back()->with('success', 'Consulta cerrada. ¡Gracias!');
    }

    public function mensajesPadre(Request $request, $id)
    {
        $ticket = $this->ticketDelPadre($id);
        return $this->mensajesNuevos($request, $ticket, ['PERSONAL', 'SISTEMA'], 'padre');
    }

    // ════════════════════════════════════════════════════════════════════

    private function agregarMensaje(Request $request, AtencionTicket $ticket, string $autor): AtencionMensaje
    {
        $request->validate(['texto' => 'required|max:3000', 'adjunto' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf'],
                           ['texto.required' => 'Escriba un mensaje.', 'adjunto.max' => 'El archivo debe pesar hasta 5 MB.']);
        return AtencionMensaje::create([
            'at_id' => $ticket->at_id, 'am_autor' => $autor, 'us_id' => auth()->user()->us_id,
            'am_texto' => trim($request->texto),
            'am_adjunto' => $request->hasFile('adjunto') ? $request->file('adjunto')->store('atencion', 'public') : null,
            'am_leido' => 0, 'am_fecha' => now(),
        ]);
    }

    private function mensajeSistema(AtencionTicket $ticket, string $texto): void
    {
        AtencionMensaje::create(['at_id' => $ticket->at_id, 'am_autor' => 'SISTEMA', 'us_id' => auth()->user()->us_id,
                                 'am_texto' => $texto, 'am_leido' => 0, 'am_fecha' => now()]);
    }

    private function marcarLeidos(AtencionTicket $ticket, $autores): void
    {
        AtencionMensaje::where('at_id', $ticket->at_id)->whereIn('am_autor', (array) $autores)->where('am_leido', 0)->update(['am_leido' => 1]);
    }

    private function mensajesNuevos(Request $request, AtencionTicket $ticket, $autoresAjenos, string $lado)
    {
        $nuevos = AtencionMensaje::with('usuario')->where('at_id', $ticket->at_id)
            ->where('am_id', '>', (int) $request->query('desde', 0))->orderBy('am_id')->get();
        if ($nuevos->isNotEmpty()) $this->marcarLeidos($ticket, $autoresAjenos);
        return response()->json([
            'html'   => $nuevos->isEmpty() ? '' : view('atencion._mensajes', ['mensajes' => $nuevos, 'lado' => $lado])->render(),
            'ultimo' => $nuevos->max('am_id'),
            'estado' => $ticket->fresh()->at_estado,
        ]);
    }

    /** Envío por AJAX (desde el hilo) o por formulario normal. */
    private function respuesta(Request $request, string $url, string $mensaje)
    {
        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect($url)->with('success', $mensaje);
    }

    private function nombre(User $u): string
    {
        return trim($u->us_nombres . ' ' . $u->us_apellidos);
    }
}
