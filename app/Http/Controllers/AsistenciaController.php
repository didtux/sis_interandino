<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\ConfiguracionAsistencia;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\FechaFestiva;
use App\Models\ListaCurso;
use App\Models\NotaPeriodo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\AsistenciasTrimestralExport;
use App\Exports\AsistenciasAnualExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        // Determinar rango de fechas
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin;
        } elseif ($request->filled('fecha')) {
            $fechaInicio = $fechaFin = $request->fecha;
        } else {
            $fechaInicio = $fechaFin = Carbon::today()->format('Y-m-d');
        }
        
        // Obtener turnos desde configuración
        $turnos = ConfiguracionAsistencia::activo()
            ->select('config_categoria', 'config_turno', 'hora_entrada', 'hora_salida', 'config_id')
            ->distinct()
            ->whereNotNull('config_turno')
            ->get()
            ->map(function($config) {
                return [
                    'turno' => $config->config_id,
                    'categoria' => $config->config_categoria,
                    'nombre' => $config->config_turno,
                    'hora_entrada' => substr($config->hora_entrada, 0, 5),
                    'hora_salida' => substr($config->hora_salida, 0, 5)
                ];
            });

        // Si el filtro es "falta", mostrar solo las faltas en la tabla
        if ($request->filled('estado') && $request->estado == 'falta') {
            $faltasRows = $this->calcularFaltas($fechaInicio, $fechaFin, $request);

            $page = $request->get('page', 1);
            $perPage = 50;
            $asistencias = new \Illuminate\Pagination\LengthAwarePaginator(
                $faltasRows->forPage($page, $perPage),
                $faltasRows->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $permisos = collect();
            $atrasos = collect();
            $mostrarPermisos = false;
            $mostrarFaltas = true;
            // continúa al bloque de cards
        }
        // Si el filtro es "permiso", mostrar desde la tabla de permisos
        elseif ($request->filled('estado') && $request->estado == 'permiso') {
            $query = Permiso::with('estudiante.curso')
                ->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fechaFin)
                ->where('permiso_fecha_fin', '>=', $fechaInicio);

            if ($request->filled('est_codigo')) {
                $query->where('estud_codigo', $request->est_codigo);
            }
            
            if ($request->filled('cur_codigo')) {
                $query->whereHas('estudiante', function($q) use ($request) {
                    $q->where('cur_codigo', $request->cur_codigo);
                });
            }

            $permisos = $query->orderBy('permiso_fecha_inicio', 'desc')->paginate(50);
            $asistencias = collect();
            $atrasos = collect();
            $mostrarPermisos = true;
        } else {
            // Lógica normal para asistencias, atrasos y puntuales
            $permisosQuery = Permiso::with('estudiante.curso')->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fechaFin)
                ->where('permiso_fecha_fin', '>=', $fechaInicio);

            if ($request->filled('est_codigo')) {
                $permisosQuery->where('estud_codigo', $request->est_codigo);
            }
            if ($request->filled('cur_codigo')) {
                $permisosQuery->whereHas('estudiante', function($q) use ($request) {
                    $q->where('cur_codigo', $request->cur_codigo);
                });
            }

            $permisos = $permisosQuery->orderBy('permiso_fecha_inicio', 'desc')->get();

            $query = Asistencia::with('estudiante.curso')
                ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin]);

            if ($request->filled('est_codigo')) {
                $query->where('estud_codigo', $request->est_codigo);
            }
            
            if ($request->filled('cur_codigo')) {
                $query->whereHas('estudiante', function($q) use ($request) {
                    $q->where('cur_codigo', $request->cur_codigo);
                });
            }
            
            // Filtrar por turno
            if ($request->filled('turno')) {
                $config = ConfiguracionAsistencia::activo()
                    ->where('config_id', $request->turno)
                    ->first();
                
                if ($config) {
                    // Filtrar por hora del turno
                    $horaEntrada = substr($config->hora_entrada, 0, 5);
                    $horaSalida = substr($config->hora_salida, 0, 5);
                    $query->whereBetween('asis_hora', [$horaEntrada . ':00', $horaSalida . ':59']);
                    
                    // Filtrar por cursos del turno si no se especificó curso
                    if (!$request->filled('cur_codigo')) {
                        $cursosPivote = \DB::table('asistencia_configuracion_cursos')
                            ->where('config_id', $config->config_id)
                            ->pluck('cur_codigo')
                            ->toArray();
                        
                        if (!empty($cursosPivote)) {
                            $query->whereHas('estudiante', function($q) use ($cursosPivote) {
                                $q->whereIn('cur_codigo', $cursosPivote);
                            });
                        }
                    }
                }
            }

            // Obtener todas las asistencias sin paginar para calcular estado
            $todasAsistenciasQuery = clone $query;
            $todasAsistencias = $todasAsistenciasQuery->orderBy('asis_fecha', 'desc')
                ->orderBy('asis_hora', 'desc')
                ->get();
            
            // Calcular estado de cada asistencia
            foreach ($todasAsistencias as $asistencia) {
                $asistencia->esAtraso = $this->calcularSiEsAtraso($asistencia, $permisos);
                $permisoEst = $permisos->first(function($p) use ($asistencia) {
                    return $p->estud_codigo === $asistencia->estud_codigo
                        && $p->permiso_fecha_inicio <= $asistencia->asis_fecha->format('Y-m-d')
                        && $p->permiso_fecha_fin >= $asistencia->asis_fecha->format('Y-m-d');
                });
                $asistencia->permisoInfo = $permisoEst;
            }
            
            // Filtrar por estado si se solicita
            if ($request->filled('estado')) {
                if ($request->estado == 'atraso') {
                    $todasAsistencias = $todasAsistencias->filter(function($a) {
                        return $a->esAtraso === true;
                    });
                } elseif ($request->estado == 'puntual') {
                    $todasAsistencias = $todasAsistencias->filter(function($a) {
                        return $a->esAtraso === false;
                    });
                }
            } else {
                // Sin filtro de estado: mezclar faltas con asistencias en la misma lista.
                $faltasRows = $this->calcularFaltas($fechaInicio, $fechaFin, $request);
                $todasAsistencias = $todasAsistencias->concat($faltasRows)
                    ->sortBy([['asis_fecha','desc'],['asis_hora','desc']])
                    ->values();
            }

            // Paginar manualmente
            $page = $request->get('page', 1);
            $perPage = 50;
            $asistencias = new \Illuminate\Pagination\LengthAwarePaginator(
                $todasAsistencias->forPage($page, $perPage),
                $todasAsistencias->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $atrasos = collect();
            $mostrarPermisos = false;
        }

        $estudiantes = Estudiante::visible()->orderBy('est_nombres')->get();
        $cursos = Curso::visible()->get();

        // === Cards: respetan filtros (curso, estudiante, turno, fechas) ===
        $cardsQuery = Asistencia::with('estudiante.curso')
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin]);
        if ($request->filled('est_codigo')) {
            $cardsQuery->where('estud_codigo', $request->est_codigo);
        }
        if ($request->filled('cur_codigo')) {
            $cardsQuery->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        // Default: turno MAÑANA (07:00-13:00) si no se eligió un turno explícito.
        // Mantiene consistencia con los reportes oficiales (concejo, boletín, centralizador).
        if (!$request->filled('turno')) {
            $cardsQuery->whereRaw('TIME(asis_hora) BETWEEN ? AND ?', ['07:00:00', '13:00:00']);
        }
        if ($request->filled('turno')) {
            $configCards = ConfiguracionAsistencia::activo()->where('config_id', $request->turno)->first();
            if ($configCards) {
                $he = substr($configCards->hora_entrada, 0, 5);
                $hs = substr($configCards->hora_salida, 0, 5);
                $cardsQuery->whereBetween('asis_hora', [$he.':00', $hs.':59']);
                if (!$request->filled('cur_codigo')) {
                    $cursosPivote = \DB::table('asistencia_configuracion_cursos')
                        ->where('config_id', $configCards->config_id)->pluck('cur_codigo')->toArray();
                    if (!empty($cursosPivote)) {
                        $cardsQuery->whereHas('estudiante', function($q) use ($cursosPivote) {
                            $q->whereIn('cur_codigo', $cursosPivote);
                        });
                    }
                }
            }
        }
        $todasParaCards = $cardsQuery->get();
        $totalAsistencias = $todasParaCards->count();
        $totalAtrasos = 0; $totalPuntuales = 0;

        // Permisos del rango para que el cálculo de atrasos los excluya correctamente
        $permisosParaCards = Permiso::where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio)
            ->get();
        foreach ($todasParaCards as $a) {
            if ($this->calcularSiEsAtraso($a, $permisosParaCards)) $totalAtrasos++;
            else $totalPuntuales++;
        }

        // Permisos: respeta curso/estudiante
        $permQ = Permiso::where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio);
        if ($request->filled('est_codigo')) $permQ->where('estud_codigo', $request->est_codigo);
        if ($request->filled('cur_codigo')) {
            $permQ->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        $totalPermisos = $permQ->count();

        // Faltas: usar helper que ya respeta filtros
        $totalFaltas = $this->calcularFaltas($fechaInicio, $fechaFin, $request)->count();

        $mostrarFaltas = isset($mostrarFaltas) ? $mostrarFaltas : false;

        return view('asistencias.index', compact(
            'asistencias',
            'estudiantes',
            'totalAsistencias',
            'totalPuntuales',
            'totalAtrasos',
            'totalPermisos',
            'totalFaltas',
            'permisos',
            'mostrarPermisos',
            'mostrarFaltas',
            'cursos',
            'turnos'
        ));
    }
    
    private function calcularSiEsAtraso($asistencia, $permisos)
    {
        if (!$asistencia->estudiante) return false;
        
        // Verificar si es un día festivo (feriado)
        $esFeriado = FechaFestiva::activo()
            ->where('festivo_tipo', 1)
            ->whereDate('festivo_fecha', $asistencia->asis_fecha)
            ->exists();
        
        if ($esFeriado) return false;
        
        // Buscar permisos del estudiante para esta fecha
        $permisosEstudiante = $permisos->where('estud_codigo', $asistencia->estud_codigo)
            ->where('permiso_fecha_inicio', '<=', $asistencia->asis_fecha->format('Y-m-d'))
            ->where('permiso_fecha_fin', '>=', $asistencia->asis_fecha->format('Y-m-d'));
        
        // Obtener configuraciones específicas del curso o generales
        $configs = ConfiguracionAsistencia::activo()
            ->where(function($q) use ($asistencia) {
                $q->whereHas('cursos', function($subQ) use ($asistencia) {
                    $subQ->where('colegio_cursos.cur_codigo', $asistencia->estudiante->cur_codigo);
                })
                ->orWhereDoesntHave('cursos');
            })
            ->get();
        
        if ($configs->isEmpty()) return false;
        
        // Convertir hora de llegada a minutos
        $horaPartes = explode(':', substr($asistencia->asis_hora, 0, 5));
        $minutosLlegada = ((int)$horaPartes[0] * 60) + (int)$horaPartes[1];
        
        // Buscar el turno más cercano según la hora de llegada
        $config = null;
        $menorDiferencia = PHP_INT_MAX;
        
        foreach ($configs as $conf) {
            $horaEntrada = is_object($conf->hora_entrada) 
                ? $conf->hora_entrada->format('H:i') 
                : (strlen($conf->hora_entrada) > 8 ? substr($conf->hora_entrada, 11, 5) : substr($conf->hora_entrada, 0, 5));
            $horaSalida = is_object($conf->hora_salida) 
                ? $conf->hora_salida->format('H:i') 
                : (strlen($conf->hora_salida) > 8 ? substr($conf->hora_salida, 11, 5) : substr($conf->hora_salida, 0, 5));
            
            $entradaPartes = explode(':', $horaEntrada);
            $salidaPartes = explode(':', $horaSalida);
            
            $minutosEntrada = ((int)$entradaPartes[0] * 60) + (int)$entradaPartes[1];
            $minutosSalida = ((int)$salidaPartes[0] * 60) + (int)$salidaPartes[1];
            
            if ($minutosLlegada >= ($minutosEntrada - 120) && $minutosLlegada <= ($minutosSalida + 120)) {
                $diferencia = abs($minutosLlegada - $minutosEntrada);
                if ($diferencia < $menorDiferencia) {
                    $menorDiferencia = $diferencia;
                    $config = $conf;
                }
            }
        }
        
        if (!$config) $config = $configs->first();
        
        // Verificar si algún permiso cubre este turno específico
        // config_id NULL = aplica a todos los turnos, específico = solo ese turno
        foreach ($permisosEstudiante as $permiso) {
            if (!$permiso->config_id || $permiso->config_id == $config->config_id) {
                return false;
            }
        }
        
        // La tolerancia es la hora límite completa
        $tolerancia = is_object($config->tolerancia_atraso) 
            ? $config->tolerancia_atraso->format('H:i') 
            : (strlen($config->tolerancia_atraso) > 8 ? substr($config->tolerancia_atraso, 11, 5) : substr($config->tolerancia_atraso, 0, 5));
        $toleranciaPartes = explode(':', $tolerancia);
        $minutosLimite = ((int)$toleranciaPartes[0] * 60) + (int)$toleranciaPartes[1];
        
        return $minutosLlegada > $minutosLimite;
    }


    public function create()
    {
        $cursos = Curso::visible()->get();
        return view('asistencias.create', compact('cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required|exists:colegio_cursos,cur_codigo',
            'asis_fecha' => 'required|date',
        ]);

        // Solo se procesan los marcados explícitamente en cada tab.
        $presentes = array_values((array) $request->input('estudiantes', []));
        $faltantes = array_values((array) $request->input('faltantes', []));

        // Deduplicar: si por alguna razón vino en ambas listas, prevalece "presente".
        $faltantes = array_diff($faltantes, $presentes);

        if (empty($presentes) && empty($faltantes)) {
            return back()->withInput()->with('error', 'No se marcó ningún estudiante (ni presente ni con falta).');
        }

        $regP = 0; $regF = 0;
        foreach ($presentes as $estCodigo) {
            $hora = $request->input('hora_' . $estCodigo, date('H:i:s'));

            $existente = Asistencia::where('estud_codigo', $estCodigo)
                ->whereDate('asis_fecha', $request->asis_fecha)
                ->first();

            if ($existente) {
                if (substr($existente->asis_hora, 0, 5) !== substr($hora, 0, 5)) {
                    $existente->update(['asis_hora' => $hora]);
                }
                continue;
            }
            Asistencia::create([
                'estud_codigo' => $estCodigo,
                'asis_fecha'   => $request->asis_fecha,
                'asis_hora'    => $hora,
                'asis_fecha2'  => now()
            ]);
            $this->verificarYRegistrarAtraso($estCodigo, $hora, $request->asis_fecha);
            $regP++;
        }

        // Faltas: en lugar de crear un registro 'F', simplemente eliminamos la asistencia
        // de ese día para el estudiante (también limpiamos el atraso si lo hubiera).
        // Así se mantiene la lógica histórica donde "sin registro = falta", compatible con
        // el flujo de asistencia vía QR del otro sistema.
        foreach ($faltantes as $estCodigo) {
            $borradas = Asistencia::where('estud_codigo', $estCodigo)
                ->whereDate('asis_fecha', $request->asis_fecha)
                ->delete();
            // Quitar atraso del día (si se había generado por la presencia anterior)
            Atraso::where('estud_codigo', $estCodigo)
                ->whereDate('atraso_fecha', $request->asis_fecha)
                ->delete();
            $regF++;
        }

        $msg = "Registrados: {$regP} presentes, {$regF} faltas (asistencia quitada del día).";
        return redirect()->route('asistencias.index')->with('success', $msg);
    }
    
    private function verificarYRegistrarAtraso($estudCodigo, $hora, $fecha = null)
    {
        // Obtener el estudiante con su curso
        $estudiante = Estudiante::where('est_codigo', $estudCodigo)->with('curso')->first();
        if (!$estudiante) return;

        $fechaAtraso = $fecha ?? now()->toDateString();
        $horaLlegada = Carbon::parse($hora);
        
        // Buscar todas las configuraciones del curso o generales
        $configuraciones = ConfiguracionAsistencia::activo()
            ->where(function($query) use ($estudiante) {
                $query->where('cur_codigo', $estudiante->cur_codigo)
                      ->orWhereNull('cur_codigo');
            })
            ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
            ->get();

        if ($configuraciones->isEmpty()) return;

        // Determinar qué configuración usar según la hora de llegada
        $config = null;
        foreach ($configuraciones as $conf) {
            $horaEntrada = Carbon::parse($conf->hora_entrada);
            $horaSalida = Carbon::parse($conf->hora_salida);
            
            // Si la hora de llegada está dentro del rango del turno (con margen de 2 horas antes y después)
            if ($horaLlegada->between($horaEntrada->copy()->subHours(2), $horaSalida->copy()->addHours(2))) {
                $config = $conf;
                break;
            }
        }
        
        // Si no se encontró configuración por rango, usar la primera
        if (!$config) {
            $config = $configuraciones->first();
        }

        $horaEntrada = Carbon::parse($config->hora_entrada);
        $tolerancia = Carbon::parse($config->tolerancia_atraso);
        
        $minutosTolerancia = $tolerancia->hour * 60 + $tolerancia->minute;
        $horaLimite = $horaEntrada->copy()->addMinutes($minutosTolerancia);

        if ($horaLlegada->gt($horaLimite)) {
            $minutosAtraso = $horaLlegada->diffInMinutes($horaEntrada);
            
            // Verificar que no exista ya un atraso para este estudiante en esta fecha
            $atrasoExistente = Atraso::where('estud_codigo', $estudCodigo)
                ->whereDate('atraso_fecha', $fechaAtraso)
                ->exists();
            
            if (!$atrasoExistente) {
                Atraso::create([
                    'atraso_codigo' => 'ATR' . substr(time(), -8) . rand(10, 99),
                    'estud_codigo' => $estudCodigo,
                    'atraso_fecha' => $fechaAtraso,
                    'atraso_hora' => $hora,
                    'minutos_atraso' => $minutosAtraso
                ]);
            }
        }
    }

    public function porCurso($cursoId)
    {
        $curso = Curso::findOrFail($cursoId);
        $estudiantes = $curso->estudiantes()->visible()->get();
        return view('asistencias.por-curso', compact('curso', 'estudiantes'));
    }

    public function registrarMasivo(Request $request)
    {
        $request->validate([
            'estudiantes' => 'required|array',
            'fecha' => 'required|date'
        ]);

        foreach ($request->estudiantes as $estCodigo) {
            Asistencia::create([
                'estud_codigo' => $estCodigo,
                'asis_fecha' => $request->fecha,
                'asis_hora' => Carbon::now()->format('H:i:s')
            ]);
        }

        return redirect()->route('asistencias.index')->with('success', 'Asistencias registradas exitosamente');
    }

    public function estudiantesPorCurso($curCodigo, Request $request = null)
    {
        $gestion = date('Y');
        $fecha = request()->query('fecha');
        $presentes = [];
        $horas = [];
        $permisos = []; // est_codigo => ['tipo'=>..., 'motivo'=>..., 'codigo'=>...]
        if ($fecha) {
            $estudiantesCurso = Estudiante::where('cur_codigo', $curCodigo)->pluck('est_codigo');

            $rows = Asistencia::whereDate('asis_fecha', $fecha)
                ->whereIn('estud_codigo', $estudiantesCurso)
                ->get(['estud_codigo', 'asis_hora']);
            foreach ($rows as $r) {
                $presentes[] = $r->estud_codigo;
                $horas[$r->estud_codigo] = substr($r->asis_hora, 0, 5);
            }

            $permisosRows = Permiso::where('permiso_estado', 1)
                ->whereDate('permiso_fecha_inicio', '<=', $fecha)
                ->whereDate('permiso_fecha_fin', '>=', $fecha)
                ->whereIn('estud_codigo', $estudiantesCurso)
                ->get(['estud_codigo', 'permiso_tipo', 'permiso_motivo', 'permiso_codigo']);
            foreach ($permisosRows as $p) {
                $permisos[$p->estud_codigo] = [
                    'tipo'   => $p->permiso_tipo,
                    'motivo' => $p->permiso_motivo,
                    'codigo' => $p->permiso_codigo,
                ];
            }
        }

        $lista = ListaCurso::where('cur_codigo', $curCodigo)
            ->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        $estudiantes = Estudiante::where('cur_codigo', $curCodigo)
            ->visible()
            ->select('est_codigo', 'est_nombres', 'est_apellidos')
            ->orderBy('est_apellidos')
            ->orderBy('est_nombres')
            ->get()
            ->map(function($est) use ($lista) {
                $est->lista_numero = $lista[$est->est_codigo] ?? null;
                return $est;
            });

        // Ordenar: primero los que tienen número de lista, luego alfabético
        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(function($est) {
                return $est->lista_numero ?? 9999;
            })->values();
        }

        return response()->json([
            'estudiantes' => $estudiantes,
            'presentes'  => $presentes,
            'horas'      => $horas,
            'permisos'   => $permisos,
        ]);
    }

    private function calcularFaltas($fechaInicio, $fechaFin, Request $request)
    {
        // Estudiantes en alcance (por curso si se filtró)
        $estQuery = Estudiante::visible();
        if ($request->filled('cur_codigo')) {
            $estQuery->where('cur_codigo', $request->cur_codigo);
        }
        if ($request->filled('est_codigo')) {
            $estQuery->where('est_codigo', $request->est_codigo);
        }
        $estudiantes = $estQuery->with('curso')->get();

        // Rango de fechas hábiles
        $fechas = [];
        $cur = \Carbon\Carbon::parse($fechaInicio);
        $fin = \Carbon\Carbon::parse($fechaFin);
        while ($cur <= $fin) {
            if ($cur->isWeekday()) $fechas[] = $cur->format('Y-m-d');
            $cur->addDay();
        }
        if (empty($fechas) || $estudiantes->isEmpty()) return collect();

        $estCodigos = $estudiantes->pluck('est_codigo')->all();

        // Presencias por (estudiante, fecha) — solo turno mañana (07:00-13:00).
        $presSet = DB::table('colegio_asistencia')
            ->whereIn('estud_codigo', $estCodigos)
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin])
            ->whereRaw('TIME(asis_hora) BETWEEN ? AND ?', ['07:00:00', '13:00:00'])
            ->selectRaw("CONCAT(estud_codigo,'|',DATE_FORMAT(asis_fecha,'%Y-%m-%d')) AS k")
            ->pluck('k')->flip();

        $permRows = DB::table('asistencia_permisos')
            ->whereIn('estud_codigo', $estCodigos)
            ->where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio)
            ->get(['estud_codigo','permiso_fecha_inicio','permiso_fecha_fin']);
        $permSet = [];
        foreach ($permRows as $p) {
            $iniP = max($p->permiso_fecha_inicio, $fechaInicio);
            $finP = min($p->permiso_fecha_fin, $fechaFin);
            $c = \Carbon\Carbon::parse($iniP);
            $e = \Carbon\Carbon::parse($finP);
            while ($c <= $e) {
                if ($c->isWeekday()) $permSet[$p->estud_codigo.'|'.$c->format('Y-m-d')] = true;
                $c->addDay();
            }
        }

        $faltas = collect();
        foreach ($estudiantes as $est) {
            foreach ($fechas as $f) {
                $k = $est->est_codigo.'|'.$f;
                if (isset($presSet[$k]) || isset($permSet[$k])) continue;
                $faltas->push((object)[
                    'estud_codigo' => $est->est_codigo,
                    'estudiante'   => $est,
                    'asis_fecha'   => \Carbon\Carbon::parse($f),
                    'asis_hora'    => '-',
                    'esFalta'      => true,
                ]);
            }
        }

        return $faltas->sortBy([['asis_fecha','desc'],['estud_codigo','asc']])->values();
    }

    private function diasHabilesRango($fechaInicio, $fechaFin, $year)
    {
        $feriados = FechaFestiva::activo()->where('festivo_tipo', 1)
            ->whereYear('festivo_fecha', $year)
            ->pluck('festivo_fecha')->map(fn($f) => $f->format('Y-m-d'))->toArray();
        $dias = 0;
        $current = Carbon::parse($fechaInicio)->copy();
        $fin = Carbon::parse($fechaFin);
        while ($current <= $fin) {
            if ($current->isWeekday() && !in_array($current->format('Y-m-d'), $feriados)) {
                $dias++;
            }
            $current->addDay();
        }
        return $dias;
    }

    private function getAsistenciaTrimestreEst($estCodigo, $periodo, $year, $turno = 'Mañana')
    {
        $service = new \App\Services\AsistenciaResumenService($turno);
        $r = $service->resumen(
            $estCodigo,
            $periodo->periodo_fecha_inicio->format('Y-m-d'),
            $periodo->periodo_fecha_fin->format('Y-m-d')
        );

        return [
            'dt'    => $r['dias_trabajados_efectivos'],
            'tl'    => $r['licencias_dias'],
            'tf'    => $r['faltas'],
            'ta'    => $r['atrasos'],
            'pres'  => $r['presencias'],
            'total' => $r['dias_trabajados_curso'],
        ];
    }



    public function reporteTrimestral(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'trimestre' => 'required|in:1,2,3'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) return back()->with('error', 'Curso no encontrado');

        $trimestre = $request->trimestre;
        $year = date('Y');
        // Turno del reporte: por config_id o por nombre; default Mañana.
        $turnoNombre = 'Mañana';
        if ($request->filled('turno')) {
            $turnoNombre = ConfiguracionAsistencia::where('config_id', $request->turno)->value('config_turno') ?: $request->turno;
        } elseif ($request->filled('turno_nombre')) {
            $turnoNombre = $request->turno_nombre;
        }
        $periodo = NotaPeriodo::activo()->gestion($year)->where('periodo_numero', $trimestre)->first();

        $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);

        $lista = ListaCurso::where('cur_codigo', $request->cur_codigo)->where('lista_gestion', $year)->pluck('lista_numero', 'est_codigo');
        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();
        }

        // ¿El curso pertenece al turno seleccionado? (avisa en el PDF si no)
        $turnoNoAplica = !(new \App\Services\AsistenciaResumenService($turnoNombre))->turnoAplica($estudiantes->first()->est_codigo);

        // Construir meses dentro del rango del periodo
        $mesesConfig = [];
        if ($periodo) {
            $cur = $periodo->periodo_fecha_inicio->copy()->startOfMonth();
            $fin = $periodo->periodo_fecha_fin->copy()->startOfMonth();
            $nombresMes = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
            while ($cur <= $fin) {
                $iniMes = $cur->copy();
                $finMes = $cur->copy()->endOfMonth();
                if ($iniMes < $periodo->periodo_fecha_inicio) $iniMes = $periodo->periodo_fecha_inicio->copy();
                if ($finMes > $periodo->periodo_fecha_fin)    $finMes = $periodo->periodo_fecha_fin->copy();
                $mesesConfig[$cur->format('Y-m')] = [
                    'nombre'      => $nombresMes[(int)$cur->format('n')],
                    'fechaInicio' => $iniMes->format('Y-m-d'),
                    'fechaFin'    => $finMes->format('Y-m-d'),
                ];
                $cur->addMonth();
            }
        }

        // Pre-compute data per student por mes y total trimestre
        $datosEstudiantes = [];
        $datosMensuales   = [];
        if ($periodo) {
            foreach ($estudiantes as $est) {
                $datosEstudiantes[$est->est_codigo] = $this->getAsistenciaTrimestreEst($est->est_codigo, $periodo, $year, $turnoNombre);
                $datosMensuales[$est->est_codigo] = [];
                foreach ($mesesConfig as $key => $m) {
                    $pseudo = (object)[
                        'periodo_fecha_inicio' => Carbon::parse($m['fechaInicio']),
                        'periodo_fecha_fin'    => Carbon::parse($m['fechaFin']),
                    ];
                    $datosMensuales[$est->est_codigo][$key] = $this->getAsistenciaTrimestreEst($est->est_codigo, $pseudo, $year, $turnoNombre);
                }
            }
        }

        // Estadísticas del trimestre
        $totDT=0;$totTL=0;$totTF=0;$totTA=0;$totDias=0;$mejor=null;$mejorPct=-1;$perfectos=0;
        foreach ($estudiantes as $est) {
            if (($est->est_visible ?? 1) == 0) continue;
            $d = $datosEstudiantes[$est->est_codigo] ?? null;
            if (!$d) continue;
            $totDT += $d['dt']; $totTL += $d['tl']; $totTF += $d['tf']; $totTA += $d['ta']; $totDias += $d['total'];
            $pct = $d['total'] > 0 ? ($d['dt'] / $d['total']) : 0;
            if ($pct > $mejorPct) { $mejorPct = $pct; $mejor = $est; }
            if ($d['total'] > 0 && $d['dt'] == $d['total']) $perfectos++;
            }
        $totalEvaluados = $estudiantes->where('est_visible', '!=', 0)->count() ?: $estudiantes->count();
        $diasHabiles    = $periodo ? $this->diasHabilesRango($periodo->periodo_fecha_inicio->format('Y-m-d'), $periodo->periodo_fecha_fin->format('Y-m-d'), $year) : 0;
        $sumaUnidades   = $totDT + $totTL + $totTF;
        $stats = [
            'estudiantes'   => $totalEvaluados,
            'dias_habiles'  => $diasHabiles,
            'presentes'     => $totDT,
            'atrasos'       => $totTA,
            'licencias'     => $totTL,
            'faltas'        => $totTF,
            'pct_presentes' => $sumaUnidades>0 ? round($totDT/$sumaUnidades*100, 2) : 0,
            'pct_atrasos'   => $sumaUnidades>0 ? round($totTA/($sumaUnidades+$totTA)*100, 2) : 0,
            'pct_licencias' => $sumaUnidades>0 ? round($totTL/$sumaUnidades*100, 2) : 0,
            'pct_faltas'    => $sumaUnidades>0 ? round($totTF/$sumaUnidades*100, 2) : 0,
            'prom_presentes'=> $totalEvaluados>0 ? round($totDT/$totalEvaluados, 2) : 0,
            'prom_atrasos'  => $totalEvaluados>0 ? round($totTA/$totalEvaluados, 2) : 0,
            'prom_faltas'   => $totalEvaluados>0 ? round($totTF/$totalEvaluados, 2) : 0,
            'tasa_efectiva' => ($totDT+$totTL)>0 && $sumaUnidades>0 ? round(($totDT+$totTL)/$sumaUnidades*100, 2) : 0,
            'tasa_ausencia' => $sumaUnidades>0 ? round(($totTF+$totTA)/$sumaUnidades*100, 2) : 0,
            'puntualidad'   => ($totDT+$totTL)>0 ? round(($totDT+$totTL)/($totDT+$totTL+$totTA)*100, 2) : 0,
            'mejor_nombre'  => $mejor ? trim($mejor->est_apellidos.' '.$mejor->est_nombres) : '-',
            'mejor_dias'    => $mejor ? ($datosEstudiantes[$mejor->est_codigo]['dt'] ?? 0) : 0,
            'mejor_total'   => $mejor ? ($datosEstudiantes[$mejor->est_codigo]['total'] ?? 0) : 0,
            'perfectos'     => $perfectos,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-trimestral-pdf',
            compact('curso', 'estudiantes', 'trimestre', 'lista', 'year', 'periodo', 'datosEstudiantes', 'mesesConfig', 'datosMensuales', 'stats', 'turnoNombre', 'turnoNoAplica'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('asistencia-trimestre-' . $trimestre . '-' . date('Y-m-d') . '.pdf');
    }

    public function reporteAnual(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) return back()->with('error', 'Curso no encontrado');

        $year = date('Y');
        $turnoNombre = 'Mañana';
        if ($request->filled('turno')) {
            $turnoNombre = ConfiguracionAsistencia::where('config_id', $request->turno)->value('config_turno') ?: $request->turno;
        } elseif ($request->filled('turno_nombre')) {
            $turnoNombre = $request->turno_nombre;
        }
        $periodos = NotaPeriodo::activo()->gestion($year)->orderBy('periodo_numero')->get();

        $trimestresConfig = [];
        foreach ($periodos as $p) {
            $trimestresConfig[$p->periodo_numero] = ['nombre' => $p->periodo_nombre];
        }
        if (empty($trimestresConfig)) {
            $trimestresConfig = [
                1 => ['nombre' => '1er Trimestre'],
                2 => ['nombre' => '2do Trimestre'],
                3 => ['nombre' => '3er Trimestre'],
            ];
        }

        $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);

        $lista = ListaCurso::where('cur_codigo', $request->cur_codigo)->where('lista_gestion', $year)->pluck('lista_numero', 'est_codigo');
        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();
        }

        // ¿El curso pertenece al turno seleccionado? (avisa en el PDF si no)
        $turnoNoAplica = !(new \App\Services\AsistenciaResumenService($turnoNombre))->turnoAplica($estudiantes->first()->est_codigo);

        // Pre-compute data per student per trimestre
        $datosEstudiantes = [];
        foreach ($estudiantes as $est) {
            $datosEstudiantes[$est->est_codigo] = [];
            foreach ($periodos as $p) {
                $datosEstudiantes[$est->est_codigo][$p->periodo_numero] = $this->getAsistenciaTrimestreEst($est->est_codigo, $p, $year, $turnoNombre);
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-anual-pdf',
            compact('curso', 'estudiantes', 'year', 'lista', 'trimestresConfig', 'datosEstudiantes', 'turnoNombre', 'turnoNoAplica'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('asistencia-anual-' . date('Y-m-d') . '.pdf');
    }

    public function reporteTrimestralExcel(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'trimestre' => 'required|in:1,2,3'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) return back()->with('error', 'Curso no encontrado');

        $year = date('Y');
        $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);

        $listaExcel = ListaCurso::where('cur_codigo', $request->cur_codigo)->where('lista_gestion', $year)->pluck('lista_numero', 'est_codigo');
        if ($listaExcel->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $listaExcel[$e->est_codigo] ?? 9999)->values();
        }

        $turnoNombre = $request->filled('turno')
            ? (ConfiguracionAsistencia::where('config_id', $request->turno)->value('config_turno') ?: 'Mañana')
            : ($request->turno_nombre ?? 'Mañana');

        $periodo = NotaPeriodo::activo()->gestion($year)->where('periodo_numero', $request->trimestre)->first();
        $periodoNombre = $periodo ? $periodo->periodo_nombre : 'Trimestre ' . $request->trimestre;

        $data = collect();
        foreach ($estudiantes as $index => $est) {
            $fila = [$listaExcel[$est->est_codigo] ?? ($index + 1), $est->est_apellidos . ' ' . $est->est_nombres];
            if ($periodo) {
                $d = $this->getAsistenciaTrimestreEst($est->est_codigo, $periodo, $year, $turnoNombre);
                $fila = array_merge($fila, [$d['dt'], $d['tl'], $d['tf'], $d['ta'], $d['total']]);
            } else {
                $fila = array_merge($fila, [0, 0, 0, 0, 0]);
            }
            $data->push($fila);
        }

        return Excel::download(new AsistenciasTrimestralExport($data, $curso, $request->trimestre, [$periodoNombre], $turnoNombre),
            'asistencia-trimestre-' . $request->trimestre . '-' . $turnoNombre . '-' . date('Y-m-d') . '.xlsx');
    }

    public function reporteAnualExcel(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) return back()->with('error', 'Curso no encontrado');

        $year = date('Y');
        $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);

        $listaExcelAnual = ListaCurso::where('cur_codigo', $request->cur_codigo)->where('lista_gestion', $year)->pluck('lista_numero', 'est_codigo');
        if ($listaExcelAnual->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $listaExcelAnual[$e->est_codigo] ?? 9999)->values();
        }

        $turnoNombre = $request->filled('turno')
            ? (ConfiguracionAsistencia::where('config_id', $request->turno)->value('config_turno') ?: 'Mañana')
            : ($request->turno_nombre ?? 'Mañana');

        $periodos = NotaPeriodo::activo()->gestion($year)->orderBy('periodo_numero')->get();

        $data = collect();
        foreach ($estudiantes as $index => $est) {
            $fila = [$listaExcelAnual[$est->est_codigo] ?? ($index + 1), $est->est_apellidos . ' ' . $est->est_nombres];
            $totalAnual = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];

            foreach ($periodos as $p) {
                $d = $this->getAsistenciaTrimestreEst($est->est_codigo, $p, $year, $turnoNombre);
                $fila = array_merge($fila, [$d['dt'], $d['tl'], $d['tf'], $d['ta'], $d['total']]);
                $totalAnual['dt'] += $d['dt']; $totalAnual['tl'] += $d['tl'];
                $totalAnual['tf'] += $d['tf']; $totalAnual['ta'] += $d['ta']; $totalAnual['total'] += $d['total'];
            }
            $fila = array_merge($fila, [$totalAnual['dt'], $totalAnual['tl'], $totalAnual['tf'], $totalAnual['ta'], $totalAnual['total']]);
            $data->push($fila);
        }

        return Excel::download(new AsistenciasAnualExport($data, $curso, $year, $turnoNombre),
            'asistencia-anual-' . $turnoNombre . '-' . date('Y-m-d') . '.xlsx');
    }

    public function reporteAtrasos(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'fecha' => 'nullable|date'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) {
            return back()->with('error', 'Curso no encontrado');
        }

        $query = Atraso::with('estudiante.padres')
            ->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        
        if ($request->filled('fecha')) {
            $query->whereDate('atraso_fecha', $request->fecha);
        }
        
        $atrasos = $query->orderBy('atraso_fecha', 'desc')->get();
        $fecha = $request->fecha;

        $lista = ListaCurso::where('cur_codigo', $request->cur_codigo)
            ->where('lista_gestion', date('Y'))
            ->pluck('lista_numero', 'est_codigo');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-atrasos-pdf', 
            compact('curso', 'atrasos', 'fecha', 'lista'))
            ->setPaper('letter', 'landscape');
        
        return $pdf->stream('reporte-atrasos-' . date('Y-m-d') . '.pdf');
    }
    
    public function reporteFaltas(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date'
        ]);

        $fecha = $request->fecha;
        $todosHorarios = $request->has('todos_horarios') && $request->todos_horarios == '1';
        
        if ($todosHorarios) {
            $configuraciones = ConfiguracionAsistencia::activo()
                ->orderBy('hora_entrada')
                ->get();
            
            if ($configuraciones->isEmpty()) {
                return back()->with('error', 'No hay configuraciones de horarios activas');
            }
            
            $datosPorCurso = [];
            $cursosYaProcesados = [];
            $listaPorCurso = [];
            $gestionActual = date('Y');
            
            foreach ($configuraciones as $config) {
                $cursosPivote = \DB::table('asistencia_configuracion_cursos')
                    ->where('config_id', $config->config_id)
                    ->pluck('cur_codigo')
                    ->toArray();
                
                $aplicaATodos = empty($cursosPivote);
                
                if ($aplicaATodos) {
                    $cursos = Curso::visible()->orderBy('cur_nombre')->get();
                } else {
                    $cursos = Curso::visible()
                        ->whereIn('cur_codigo', $cursosPivote)
                        ->orderBy('cur_nombre')
                        ->get();
                }
                
                foreach ($cursos as $curso) {
                    if (in_array($curso->cur_codigo, $cursosYaProcesados)) {
                        continue;
                    }
                    $cursosYaProcesados[] = $curso->cur_codigo;
                    
                    $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();

                    // Cargar lista si existe
                    if (!isset($listaPorCurso[$curso->cur_codigo])) {
                        $listaPorCurso[$curso->cur_codigo] = ListaCurso::where('cur_codigo', $curso->cur_codigo)
                            ->where('lista_gestion', $gestionActual)->pluck('lista_numero', 'est_codigo');
                    }
                    $listaC = $listaPorCurso[$curso->cur_codigo];
                    if ($listaC->isNotEmpty()) {
                        $estudiantes = $estudiantes->sortBy(fn($e) => $listaC[$e->est_codigo] ?? 9999)->values();
                    }
                    
                    $asistencias = Asistencia::whereDate('asis_fecha', $fecha)
                        ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                        ->pluck('estud_codigo')
                        ->toArray();
                    
                    // Permisos: solo excluir si config_id es NULL (todos) o coincide con este config
                    $permisosActivos = Permiso::where('permiso_estado', 1)
                        ->whereDate('permiso_fecha_inicio', '<=', $fecha)
                        ->whereDate('permiso_fecha_fin', '>=', $fecha)
                        ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                        ->get();
                    
                    $permisosFiltrados = $permisosActivos->filter(function($p) use ($config) {
                        return !$p->config_id || $p->config_id == $config->config_id;
                    });
                    $estudiantesConPermiso = $permisosFiltrados->pluck('estud_codigo')->unique()->toArray();

                    // Construir lista de licencias (estudiante + datos del permiso)
                    $permisoPorEst = $permisosFiltrados->keyBy('estud_codigo');
                    $licenciasCurso = $estudiantes->filter(fn($e) => isset($permisoPorEst[$e->est_codigo]))
                        ->map(function($e) use ($permisoPorEst) {
                            $p = $permisoPorEst[$e->est_codigo];
                            return (object)[
                                'estudiante' => $e,
                                'tipo' => $p->permiso_tipo,
                                'motivo' => $p->permiso_motivo,
                                'codigo' => $p->permiso_codigo,
                            ];
                        })->values();

                    $estudiantesSinAsistencia = $estudiantes->filter(function($est) use ($asistencias, $estudiantesConPermiso) {
                        return !in_array($est->est_codigo, $asistencias) && !in_array($est->est_codigo, $estudiantesConPermiso);
                    });

                    if ($estudiantesSinAsistencia->count() > 0 || $licenciasCurso->count() > 0) {
                        $datosPorCurso[] = [
                            'curso' => $curso,
                            'estudiantes' => $estudiantesSinAsistencia,
                            'licencias' => $licenciasCurso,
                            'horario' => $config->config_turno . ' (' . substr($config->hora_entrada, 0, 5) . '-' . substr($config->hora_salida, 0, 5) . ')',
                            'lista' => $listaC,
                        ];
                    }
                }
            }

            $turno = 'TODOS LOS HORARIOS';
            $config = null;
            
        } else {
            $request->validate([
                'turno' => 'required',
                'categoria' => 'required',
                'cur_codigo' => 'required'
            ]);
            
            $turno = $request->turno;
            $categoria = $request->categoria;
            $todosCursos = $request->cur_codigo === 'todos';
            
            $config = ConfiguracionAsistencia::activo()
                ->where('config_turno', $turno)
                ->where('config_categoria', $categoria)
                ->first();
            
            if (!$config) {
                return back()->with('error', 'No hay configuración para ' . $categoria . ' - ' . $turno);
            }
            
            $cursosPivote = \DB::table('asistencia_configuracion_cursos')
                ->where('config_id', $config->config_id)
                ->pluck('cur_codigo')
                ->toArray();
            
            $aplicaATodos = empty($cursosPivote);
            
            if ($todosCursos) {
                if ($aplicaATodos) {
                    $cursos = Curso::visible()->orderBy('cur_nombre')->get();
                } else {
                    $cursos = Curso::visible()
                        ->whereIn('cur_codigo', $cursosPivote)
                        ->orderBy('cur_nombre')
                        ->get();
                }
            } else {
                $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
                if (!$curso) {
                    return back()->with('error', 'Curso no encontrado');
                }
                
                if (!$aplicaATodos && !in_array($curso->cur_codigo, $cursosPivote)) {
                    return back()->with('error', 'El curso ' . $curso->cur_nombre . ' no pertenece a ' . $categoria . ' - ' . $turno);
                }
                
                $cursos = collect([$curso]);
            }
            
            $datosPorCurso = [];
            $gestionFaltas = date('Y');
            
            foreach ($cursos as $curso) {
                $estudiantes = $curso->estudiantes()->orderBy('est_apellidos')->orderBy('est_nombres')->get();

                $listaC = ListaCurso::where('cur_codigo', $curso->cur_codigo)
                    ->where('lista_gestion', $gestionFaltas)->pluck('lista_numero', 'est_codigo');
                if ($listaC->isNotEmpty()) {
                    $estudiantes = $estudiantes->sortBy(fn($e) => $listaC[$e->est_codigo] ?? 9999)->values();
                }
                
                $asistencias = Asistencia::whereDate('asis_fecha', $fecha)
                    ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                    ->pluck('estud_codigo')
                    ->toArray();
                
                // Permisos: solo excluir si config_id es NULL (todos) o coincide con este config
                $permisosActivos = Permiso::where('permiso_estado', 1)
                    ->whereDate('permiso_fecha_inicio', '<=', $fecha)
                    ->whereDate('permiso_fecha_fin', '>=', $fecha)
                    ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                    ->get();
                
                $permisosFiltrados = $permisosActivos->filter(function($p) use ($config) {
                    return !$p->config_id || $p->config_id == $config->config_id;
                });
                $estudiantesConPermiso = $permisosFiltrados->pluck('estud_codigo')->unique()->toArray();

                $permisoPorEst = $permisosFiltrados->keyBy('estud_codigo');
                $licenciasCurso = $estudiantes->filter(fn($e) => isset($permisoPorEst[$e->est_codigo]))
                    ->map(function($e) use ($permisoPorEst) {
                        $p = $permisoPorEst[$e->est_codigo];
                        return (object)[
                            'estudiante' => $e,
                            'tipo' => $p->permiso_tipo,
                            'motivo' => $p->permiso_motivo,
                            'codigo' => $p->permiso_codigo,
                        ];
                    })->values();

                $estudiantesSinAsistencia = $estudiantes->filter(function($est) use ($asistencias, $estudiantesConPermiso) {
                    return !in_array($est->est_codigo, $asistencias) && !in_array($est->est_codigo, $estudiantesConPermiso);
                });

                if ($estudiantesSinAsistencia->count() > 0 || $licenciasCurso->count() > 0) {
                    $datosPorCurso[] = [
                        'curso' => $curso,
                        'estudiantes' => $estudiantesSinAsistencia,
                        'licencias' => $licenciasCurso,
                        'lista' => $listaC,
                    ];
                }
            }
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-faltas-pdf', 
            compact('datosPorCurso', 'fecha', 'turno', 'config'))
            ->setPaper('letter', 'portrait');
        
        return $pdf->stream('reporte-faltas-' . $fecha . '.pdf');
    }
    
    public function cursosPorTurno(Request $request)
    {
        $categoria = $request->categoria;
        $turno = $request->turno;
        
        \Log::info('=== cursosPorTurno DEBUG ===');
        \Log::info('Categoria recibida: ' . $categoria);
        \Log::info('Turno recibido: ' . $turno);
        
        // Obtener configuración por categoría y turno
        $configuracion = ConfiguracionAsistencia::activo()
            ->where('config_categoria', $categoria)
            ->where('config_turno', $turno)
            ->first();
        
        \Log::info('Configuración encontrada: ' . ($configuracion ? 'SI (ID: ' . $configuracion->config_id . ')' : 'NO'));
        
        if (!$configuracion) {
            // Buscar todas las configuraciones para debug
            $todasConfigs = ConfiguracionAsistencia::activo()
                ->select('config_id', 'config_categoria', 'config_turno')
                ->get();
            \Log::info('Configuraciones disponibles:', $todasConfigs->toArray());
            
            return response()->json([
                'cursos' => [],
                'aplica_a_todos' => false,
                'error' => 'No se encontró configuración para ' . $categoria . ' - ' . $turno,
                'debug' => [
                    'categoria_buscada' => $categoria,
                    'turno_buscado' => $turno,
                    'configuraciones_disponibles' => $todasConfigs
                ]
            ]);
        }
        
        // Obtener cursos de la tabla pivote para esta configuración específica
        $cursosPivote = \DB::table('asistencia_configuracion_cursos')
            ->where('config_id', $configuracion->config_id)
            ->pluck('cur_codigo')
            ->toArray();
        
        $aplicaATodos = empty($cursosPivote);
        
        \Log::info('Cursos encontrados: ' . count($cursosPivote));
        \Log::info('Aplica a todos: ' . ($aplicaATodos ? 'SI' : 'NO'));
        
        return response()->json([
            'cursos' => $cursosPivote,
            'aplica_a_todos' => $aplicaATodos,
            'debug' => [
                'config_id' => $configuracion->config_id,
                'categoria' => $configuracion->config_categoria,
                'turno' => $configuracion->config_turno,
                'cursos_encontrados' => count($cursosPivote)
            ]
        ]);
    }
    
    public function limpiarDuplicados()
    {
        // Buscar duplicados: mismo estudiante, misma fecha, mismo turno (rango de 1 hora)
        $duplicados = \DB::select("
            SELECT estud_codigo, DATE(asis_fecha) as fecha, 
                   HOUR(asis_hora) as hora,
                   COUNT(*) as total,
                   MIN(asis_id) as mantener_id
            FROM colegio_asistencia
            GROUP BY estud_codigo, DATE(asis_fecha), HOUR(asis_hora)
            HAVING COUNT(*) > 1
        ");
        
        $eliminados = 0;
        
        foreach ($duplicados as $dup) {
            // Eliminar todos excepto el primero (mantener_id)
            $deleted = Asistencia::where('estud_codigo', $dup->estud_codigo)
                ->whereDate('asis_fecha', $dup->fecha)
                ->whereRaw('HOUR(asis_hora) = ?', [$dup->hora])
                ->where('asis_id', '!=', $dup->mantener_id)
                ->delete();
            
            $eliminados += $deleted;
        }
        
        return redirect()->route('asistencias.index')
            ->with('success', "Limpieza completada: $eliminados registros duplicados eliminados");
    }}