<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * App de escaneo de asistencia (lector QR).
 * Acceso: usuarios con us_entidad_tipo = 'escaneo' (rol "Encargado Escaneo")
 * y administradores (rol_id = 1).
 * Cada registro guarda el usuario que escaneó (asis_usuario) para auditoría.
 */
class EscaneoAsistenciaController extends Controller
{
    /** Solo encargados de escaneo y admin. */
    private function autorizar()
    {
        $user = auth()->user();
        if (!$user) abort(403);
        $esEscaneo = $user->us_entidad_tipo === 'escaneo';
        $esAdmin   = (int) $user->rol_id === 1;
        if (!$esEscaneo && !$esAdmin) abort(403, 'No autorizado para la app de escaneo.');
        return $user;
    }

    public function index()
    {
        $this->autorizar();
        $hoy = Carbon::today()->toDateString();

        // Registros que ESTE usuario escaneó hoy (feedback + auditoría visible).
        $misRegistros = DB::table('colegio_asistencia as a')
            ->leftJoin('colegio_estudiantes as e', 'e.est_codigo', '=', 'a.estud_codigo')
            ->leftJoin('colegio_cursos as c', 'c.cur_codigo', '=', 'e.cur_codigo')
            ->where('a.asis_usuario', auth()->user()->us_id)
            ->whereDate('a.asis_fecha', $hoy)
            ->orderByDesc('a.asis_hora')
            ->select('a.estud_codigo', 'a.asis_hora', 'a.asis_origen',
                     'e.est_nombres', 'e.est_apellidos', 'c.cur_nombre')
            ->get();

        return view('escaneo.index', compact('misRegistros'));
    }

    /**
     * Registra una asistencia escaneada/buscada. Responde JSON.
     */
    public function registrar(Request $request)
    {
        $user = $this->autorizar();

        $codigo = trim((string) $request->input('codigo'));
        $origen = strtoupper($request->input('origen')) === 'MANUAL' ? 'MANUAL' : 'QR';
        $fecha  = $request->input('fecha') ?: Carbon::today()->toDateString();
        $hora   = $request->input('hora') ?: Carbon::now()->format('H:i:s');

        if ($codigo === '') {
            return response()->json(['success' => false, 'message' => 'Código requerido']);
        }

        // Estudiante
        $estudiante = DB::table('colegio_estudiantes')
            ->where('est_codigo', $codigo)
            ->where('est_visible', 1)
            ->select('est_codigo', 'est_nombres', 'est_apellidos', 'cur_codigo')
            ->first();

        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado o inactivo']);
        }

        // Turno según la hora (Mañana 07:00-12:59, Tarde 13:00-22:00)
        $horaNum = (int) substr($hora, 0, 2);
        if ($horaNum >= 7 && $horaNum < 13) {
            $turno = 'Mañana';
            $rango = ['07:00:00', '12:59:59'];
        } elseif ($horaNum >= 13 && $horaNum <= 22) {
            $turno = 'Tarde';
            $rango = ['13:00:00', '22:00:00'];
        } else {
            return response()->json(['success' => false, 'message' => 'Fuera de horario (07-12 o 13-22)']);
        }

        // Evitar doble registro en el mismo turno del día
        $yaRegistrado = DB::table('colegio_asistencia')
            ->where('estud_codigo', $codigo)
            ->whereDate('asis_fecha', $fecha)
            ->whereRaw('TIME(asis_hora) BETWEEN ? AND ?', $rango)
            ->exists();

        if ($yaRegistrado) {
            return response()->json([
                'success' => false,
                'message' => 'Ya registrado en turno ' . $turno,
                'estudiante' => $this->datosEstudiante($estudiante, $turno, $fecha, $hora),
            ]);
        }

        // Registrar (con auditoría: quién y por qué medio)
        DB::table('colegio_asistencia')->insert([
            'estud_codigo' => $codigo,
            'asis_fecha'   => $fecha,
            'asis_hora'    => $hora,
            'asis_fecha2'  => Carbon::now(),
            'asis_usuario' => $user->us_id,
            'asis_origen'  => $origen,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada — ' . $turno,
            'turno'   => $turno,
            'estudiante' => $this->datosEstudiante($estudiante, $turno, $fecha, $hora),
        ]);
    }

    private function datosEstudiante($estudiante, string $turno, string $fecha, string $hora): array
    {
        $curso = DB::table('colegio_cursos')->where('cur_codigo', $estudiante->cur_codigo)->value('cur_nombre');
        return [
            'codigo'    => $estudiante->est_codigo,
            'nombres'   => $estudiante->est_nombres,
            'apellidos' => $estudiante->est_apellidos,
            'curso'     => $curso ?? 'N/A',
            'turno'     => $turno,
            'fecha'     => $fecha,
            'hora'      => $hora,
        ];
    }
}
