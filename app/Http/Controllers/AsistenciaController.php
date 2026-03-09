<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\ConfiguracionAsistencia;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\FechaFestiva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\AsistenciasTrimestralExport;
use App\Exports\AsistenciasAnualExport;
use Maatwebsite\Excel\Facades\Excel;

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

        // Si el filtro es "permiso", mostrar desde la tabla de permisos
        if ($request->filled('estado') && $request->estado == 'permiso') {
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
            $permisosQuery = Permiso::where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fechaFin)
                ->where('permiso_fecha_fin', '>=', $fechaInicio);

            if ($request->filled('est_codigo')) {
                $permisosQuery->where('estud_codigo', $request->est_codigo);
            }

            $permisos = $permisosQuery->get();

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

        $totalAsistencias = Asistencia::whereBetween('asis_fecha', [$fechaInicio, $fechaFin])->count();
        
        // Contar atrasos y puntuales dinámicamente
        $todasAsistencias = Asistencia::with('estudiante.curso')
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin])
            ->get();
        $totalAtrasos = 0;
        $totalPuntuales = 0;
        foreach ($todasAsistencias as $a) {
            if ($this->calcularSiEsAtraso($a, $permisos)) {
                $totalAtrasos++;
            } else {
                $totalPuntuales++;
            }
        }
        
        $totalPermisos = Permiso::where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio)
            ->count();

        return view('asistencias.index', compact(
            'asistencias',
            'estudiantes',
            'totalAsistencias',
            'totalPuntuales',
            'totalAtrasos',
            'totalPermisos',
            'permisos',
            'mostrarPermisos',
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
        
        // Verificar si tiene permiso
        $tienePermiso = $permisos->where('estud_codigo', $asistencia->estud_codigo)
            ->where('permiso_fecha_inicio', '<=', $asistencia->asis_fecha->format('Y-m-d'))
            ->where('permiso_fecha_fin', '>=', $asistencia->asis_fecha->format('Y-m-d'))
            ->first();
        
        if ($tienePermiso) return false;
        
        // Obtener configuraciones específicas del curso o generales
        $configs = ConfiguracionAsistencia::activo()
            ->where(function($q) use ($asistencia) {
                // Buscar configuraciones que tengan el curso específico
                $q->whereHas('cursos', function($subQ) use ($asistencia) {
                    $subQ->where('colegio_cursos.cur_codigo', $asistencia->estudiante->cur_codigo);
                })
                // O configuraciones sin cursos específicos (aplican a todos)
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
            
            // Verificar si está en el rango del turno
            if ($minutosLlegada >= ($minutosEntrada - 120) && $minutosLlegada <= ($minutosSalida + 120)) {
                $diferencia = abs($minutosLlegada - $minutosEntrada);
                if ($diferencia < $menorDiferencia) {
                    $menorDiferencia = $diferencia;
                    $config = $conf;
                }
            }
        }
        
        if (!$config) $config = $configs->first();
        
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
            'estudiantes' => 'required|array'
        ]);

        $registrados = 0;
        foreach ($request->estudiantes as $estCodigo) {
            $hora = $request->input('hora_' . $estCodigo, date('H:i:s'));
            
            // Verificar si ya existe asistencia para este estudiante en esta fecha
            $asistenciaExistente = Asistencia::where('estud_codigo', $estCodigo)
                ->whereDate('asis_fecha', $request->asis_fecha)
                ->exists();
            
            if (!$asistenciaExistente) {
                Asistencia::create([
                    'estud_codigo' => $estCodigo,
                    'asis_fecha' => $request->asis_fecha,
                    'asis_hora' => $hora,
                    'asis_fecha2' => now()
                ]);
                
                // Verificar atraso
                $this->verificarYRegistrarAtraso($estCodigo, $hora, $request->asis_fecha);
                $registrados++;
            }
        }

        return redirect()->route('asistencias.index')->with('success', "$registrados asistencias registradas exitosamente");
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

    public function estudiantesPorCurso($curCodigo)
    {
        $estudiantes = Estudiante::where('cur_codigo', $curCodigo)
            ->visible()
            ->select('est_codigo', 'est_nombres', 'est_apellidos')
            ->orderBy('est_nombres')
            ->get();
        return response()->json($estudiantes);
    }

    public function reporteTrimestral(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'trimestre' => 'required|in:1,2,3'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) {
            return back()->with('error', 'Curso no encontrado');
        }

        $trimestre = $request->trimestre;
        $year = date('Y');
        $trimestres = [
            1 => ['inicio' => "$year-02-01", 'fin' => "$year-05-31", 'meses' => ['Febrero', 'Marzo', 'Abril', 'Mayo']],
            2 => ['inicio' => "$year-06-01", 'fin' => "$year-09-30", 'meses' => ['Junio', 'Julio', 'Agosto', 'Septiembre']],
            3 => ['inicio' => "$year-10-01", 'fin' => "$year-12-31", 'meses' => ['Octubre', 'Noviembre', 'Diciembre']]
        ];

        $rango = $trimestres[$trimestre];
        $estudiantes = $curso->estudiantes()->visible()->orderBy('est_nombres')->get();
        
        if ($estudiantes->isEmpty()) {
            return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-trimestral-pdf', 
            compact('curso', 'estudiantes', 'trimestre', 'rango'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('asistencia-trimestre-' . $trimestre . '-' . date('Y-m-d') . '.pdf');
    }

    public function reporteAnual(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) {
            return back()->with('error', 'Curso no encontrado');
        }

        $year = date('Y');
        $estudiantes = $curso->estudiantes()->visible()->orderBy('est_nombres')->get();
        
        if ($estudiantes->isEmpty()) {
            return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-anual-pdf', 
            compact('curso', 'estudiantes', 'year'))
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
        if (!$curso) {
            return back()->with('error', 'Curso no encontrado');
        }

        $estudiantes = $curso->estudiantes()->visible()->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) {
            return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);
        }

        $year = date('Y');
        $trimestres = [
            1 => ['meses' => [2, 3, 4, 5], 'nombres' => ['Febrero', 'Marzo', 'Abril', 'Mayo']],
            2 => ['meses' => [6, 7, 8, 9], 'nombres' => ['Junio', 'Julio', 'Agosto', 'Septiembre']],
            3 => ['meses' => [10, 11, 12], 'nombres' => ['Octubre', 'Noviembre', 'Diciembre']]
        ];
        
        $rango = $trimestres[$request->trimestre];
        $data = collect();
        
        foreach ($estudiantes as $est) {
            $fila = [$est->est_nombres . ' ' . $est->est_apellidos];
            $totalTrimestre = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];
            
            foreach ($rango['meses'] as $mes) {
                $diasMes = cal_days_in_month(CAL_GREGORIAN, $mes, $year);
                
                $asistencias = Asistencia::where('estud_codigo', $est->est_codigo)
                    ->whereYear('asis_fecha', $year)
                    ->whereMonth('asis_fecha', $mes)
                    ->count();
                
                $permisos = Permiso::where('estud_codigo', $est->est_codigo)
                    ->where('permiso_estado', 1)
                    ->whereYear('permiso_fecha_inicio', $year)
                    ->whereMonth('permiso_fecha_inicio', $mes)
                    ->count();
                
                $atrasos = Atraso::where('estud_codigo', $est->est_codigo)
                    ->whereYear('atraso_fecha', $year)
                    ->whereMonth('atraso_fecha', $mes)
                    ->count();
                
                // Contar días festivos (feriados tipo 1) en el mes
                $festivos = FechaFestiva::activo()
                    ->where('festivo_tipo', 1)
                    ->whereYear('festivo_fecha', $year)
                    ->whereMonth('festivo_fecha', $mes)
                    ->count();
                
                $faltas = $diasMes - $asistencias - $permisos - $festivos;
                
                $fila[] = $asistencias;
                $fila[] = $permisos;
                $fila[] = $faltas;
                $fila[] = $atrasos;
                $fila[] = $diasMes;
                
                $totalTrimestre['dt'] += $asistencias;
                $totalTrimestre['tl'] += $permisos;
                $totalTrimestre['tf'] += $faltas;
                $totalTrimestre['ta'] += $atrasos;
                $totalTrimestre['total'] += $diasMes;
            }
            
            $fila[] = $totalTrimestre['dt'];
            $fila[] = $totalTrimestre['tl'];
            $fila[] = $totalTrimestre['tf'];
            $fila[] = $totalTrimestre['ta'];
            $fila[] = $totalTrimestre['total'];
            
            $data->push($fila);
        }

        return Excel::download(new AsistenciasTrimestralExport($data, $curso, $request->trimestre, $rango['nombres']), 
            'asistencia-trimestre-' . $request->trimestre . '-' . date('Y-m-d') . '.xlsx');
    }

    public function reporteAnualExcel(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required'
        ]);

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->first();
        if (!$curso) {
            return back()->with('error', 'Curso no encontrado');
        }

        $estudiantes = $curso->estudiantes()->visible()->orderBy('est_nombres')->get();
        if ($estudiantes->isEmpty()) {
            return back()->with('error', 'No hay estudiantes registrados en el curso ' . $curso->cur_nombre);
        }

        $year = date('Y');
        $trimestres = [
            1 => ['meses' => [2, 3, 4, 5]],
            2 => ['meses' => [6, 7, 8, 9]],
            3 => ['meses' => [10, 11, 12]]
        ];
        
        $data = collect();
        
        foreach ($estudiantes as $est) {
            $fila = [$est->est_nombres . ' ' . $est->est_apellidos];
            $totalAnual = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];
            
            foreach ($trimestres as $trim) {
                $totalTrim = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];
                
                foreach ($trim['meses'] as $mes) {
                    $diasMes = cal_days_in_month(CAL_GREGORIAN, $mes, $year);
                    
                    $asistencias = Asistencia::where('estud_codigo', $est->est_codigo)
                        ->whereYear('asis_fecha', $year)
                        ->whereMonth('asis_fecha', $mes)
                        ->count();
                    
                    $permisos = Permiso::where('estud_codigo', $est->est_codigo)
                        ->where('permiso_estado', 1)
                        ->whereYear('permiso_fecha_inicio', $year)
                        ->whereMonth('permiso_fecha_inicio', $mes)
                        ->count();
                    
                    $atrasos = Atraso::where('estud_codigo', $est->est_codigo)
                        ->whereYear('atraso_fecha', $year)
                        ->whereMonth('atraso_fecha', $mes)
                        ->count();
                    
                    // Contar días festivos (feriados tipo 1) en el mes
                    $festivos = FechaFestiva::activo()
                        ->where('festivo_tipo', 1)
                        ->whereYear('festivo_fecha', $year)
                        ->whereMonth('festivo_fecha', $mes)
                        ->count();
                    
                    $faltas = $diasMes - $asistencias - $permisos - $festivos;
                    
                    $totalTrim['dt'] += $asistencias;
                    $totalTrim['tl'] += $permisos;
                    $totalTrim['tf'] += $faltas;
                    $totalTrim['ta'] += $atrasos;
                    $totalTrim['total'] += $diasMes;
                }
                
                $fila[] = $totalTrim['dt'];
                $fila[] = $totalTrim['tl'];
                $fila[] = $totalTrim['tf'];
                $fila[] = $totalTrim['ta'];
                $fila[] = $totalTrim['total'];
                
                $totalAnual['dt'] += $totalTrim['dt'];
                $totalAnual['tl'] += $totalTrim['tl'];
                $totalAnual['tf'] += $totalTrim['tf'];
                $totalAnual['ta'] += $totalTrim['ta'];
                $totalAnual['total'] += $totalTrim['total'];
            }
            
            $fila[] = $totalAnual['dt'];
            $fila[] = $totalAnual['tl'];
            $fila[] = $totalAnual['tf'];
            $fila[] = $totalAnual['ta'];
            $fila[] = $totalAnual['total'];
            
            $data->push($fila);
        }

        return Excel::download(new AsistenciasAnualExport($data, $curso, $year), 
            'asistencia-anual-' . date('Y-m-d') . '.xlsx');
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
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-atrasos-pdf', 
            compact('curso', 'atrasos', 'fecha'))
            ->setPaper('letter', 'landscape');
        
        return $pdf->stream('reporte-atrasos-' . date('Y-m-d') . '.pdf');
    }
    
    public function reporteFaltas(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'fecha' => 'required|date',
            'turno' => 'required',
            'categoria' => 'required'
        ]);

        $fecha = $request->fecha;
        $turno = $request->turno;
        $categoria = $request->categoria;
        $todosCursos = $request->cur_codigo === 'todos';
        
        // Obtener configuración específica por turno Y categoría
        $config = ConfiguracionAsistencia::activo()
            ->where('config_turno', $turno)
            ->where('config_categoria', $categoria)
            ->first();
        
        if (!$config) {
            return back()->with('error', 'No hay configuración para ' . $categoria . ' - ' . $turno);
        }
        
        // Obtener cursos de esta configuración específica
        $cursosPivote = \DB::table('asistencia_configuracion_cursos')
            ->where('config_id', $config->config_id)
            ->pluck('cur_codigo')
            ->toArray();
        
        $aplicaATodos = empty($cursosPivote);
        
        if ($todosCursos) {
            if ($aplicaATodos) {
                // Si aplica a todos, obtener todos los cursos
                $cursos = Curso::visible()->orderBy('cur_nombre')->get();
            } else {
                // Solo cursos de esta configuración específica
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
            
            // Verificar si el curso pertenece a esta configuración
            if (!$aplicaATodos && !in_array($curso->cur_codigo, $cursosPivote)) {
                return back()->with('error', 'El curso ' . $curso->cur_nombre . ' no pertenece a ' . $categoria . ' - ' . $turno);
            }
            
            $cursos = collect([$curso]);
        }
        
        $datosPorCurso = [];
        
        foreach ($cursos as $curso) {
            // Obtener todos los estudiantes del curso
            $estudiantes = $curso->estudiantes()->visible()->orderBy('est_apellidos')->orderBy('est_nombres')->get();
            
            // Obtener estudiantes que SÍ marcaron asistencia
            $asistencias = Asistencia::whereDate('asis_fecha', $fecha)
                ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                ->pluck('estud_codigo')
                ->toArray();
            
            // Obtener estudiantes con permiso activo en la fecha
            $estudiantesConPermiso = Permiso::where('permiso_estado', 1)
                ->whereDate('permiso_fecha_inicio', '<=', $fecha)
                ->whereDate('permiso_fecha_fin', '>=', $fecha)
                ->whereIn('estud_codigo', $estudiantes->pluck('est_codigo'))
                ->pluck('estud_codigo')
                ->toArray();
            
            // Filtrar estudiantes SIN asistencia y SIN permiso
            $estudiantesSinAsistencia = $estudiantes->filter(function($est) use ($asistencias, $estudiantesConPermiso) {
                return !in_array($est->est_codigo, $asistencias) && !in_array($est->est_codigo, $estudiantesConPermiso);
            });
            
            if ($estudiantesSinAsistencia->count() > 0) {
                $datosPorCurso[] = [
                    'curso' => $curso,
                    'estudiantes' => $estudiantesSinAsistencia
                ];
            }
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asistencias.reporte-faltas-pdf', 
            compact('datosPorCurso', 'fecha', 'turno', 'categoria', 'config', 'todosCursos'))
            ->setPaper('letter', 'portrait');
        
        return $pdf->stream('reporte-faltas-' . $categoria . '-' . $turno . '-' . $fecha . '.pdf');
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