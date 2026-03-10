<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionAsistencia;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\FechaFestiva;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Curso;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Excel;

class ConfiguracionAsistenciaController extends Controller
{
    // ========== CONFIGURACIÓN DE HORARIOS ==========
    public function index()
    {
        $configuraciones = ConfiguracionAsistencia::with(['curso', 'cursos'])->activo()->get();
        $cursos = Curso::visible()->get();
        return view('asistencia-config.index', compact('configuraciones', 'cursos'));
    }

    public function storeConfiguracion(Request $request)
    {
        $request->validate([
            'config_categoria' => 'required|string|max:50',
            'config_turno' => 'required|string|max:20',
            'cur_codigos' => 'nullable|array',
            'hora_entrada' => 'required',
            'hora_salida' => 'required',
            'tolerancia_atraso' => 'required',
            'hora_atraso_desde' => 'required',
            'hora_atraso_hasta' => 'required'
        ]);

        $config = ConfiguracionAsistencia::create([
            'config_codigo' => 'CONF' . time(),
            'config_categoria' => $request->config_categoria,
            'config_turno' => $request->config_turno,
            'cur_codigo' => null,
            'hora_entrada' => $request->hora_entrada . ':00',
            'hora_salida' => $request->hora_salida . ':00',
            'tolerancia_atraso' => $request->tolerancia_atraso . ':00',
            'hora_atraso_desde' => $request->hora_atraso_desde . ':00',
            'hora_atraso_hasta' => $request->hora_atraso_hasta . ':00'
        ]);

        if ($request->filled('cur_codigos')) {
            $config->cursos()->attach($request->cur_codigos);
        }

        return redirect()->back()->with('success', 'Configuración creada exitosamente');
    }

    public function updateConfiguracion(Request $request, $id)
    {
        $config = ConfiguracionAsistencia::findOrFail($id);
        
        $data = [
            'config_categoria' => $request->config_categoria,
            'config_turno' => $request->config_turno,
            'hora_entrada' => $request->hora_entrada . ':00',
            'hora_salida' => $request->hora_salida . ':00',
            'tolerancia_atraso' => $request->tolerancia_atraso . ':00',
            'hora_atraso_desde' => $request->hora_atraso_desde . ':00',
            'hora_atraso_hasta' => $request->hora_atraso_hasta . ':00'
        ];
        
        $config->update($data);
        
        if ($request->filled('cur_codigos')) {
            $config->cursos()->sync($request->cur_codigos);
        } else {
            $config->cursos()->detach();
        }
        
        return redirect()->back()->with('success', 'Configuración actualizada');
    }

    public function destroyConfiguracion($id)
    {
        ConfiguracionAsistencia::findOrFail($id)->update(['config_estado' => 0]);
        return redirect()->back()->with('success', 'Configuración eliminada');
    }

    // ========== GESTIÓN DE ATRASOS ==========
    public function atrasos(Request $request)
    {
        $fechaInicio = $request->filled('fecha_inicio') ? $request->fecha_inicio : Carbon::today()->subDays(30)->format('Y-m-d');
        $fechaFin = $request->filled('fecha_fin') ? $request->fecha_fin : Carbon::today()->format('Y-m-d');
        
        $permisos = Permiso::where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio)
            ->get();
        
        $query = Asistencia::with('estudiante.curso')
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin]);
        
        if ($request->filled('estudiante')) {
            $query->where('estud_codigo', $request->estudiante);
        }
        
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('turno')) {
            $config = ConfiguracionAsistencia::activo()->where('config_id', $request->turno)->first();
            if ($config) {
                $horaEntrada = substr($config->hora_entrada, 0, 5);
                $horaSalida = substr($config->hora_salida, 0, 5);
                $query->whereBetween('asis_hora', [$horaEntrada . ':00', $horaSalida . ':59']);
                
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

        $asistencias = $query->orderBy('asis_fecha', 'desc')->orderBy('asis_hora', 'desc')->get();
        
        $atrasos = collect();
        foreach ($asistencias as $asistencia) {
            $esAtraso = $this->calcularSiEsAtraso($asistencia, $permisos);
            if ($esAtraso) {
                $asistencia->esAtraso = true;
                $atrasos->push($asistencia);
            }
        }
        
        $estudiantes = Estudiante::visible()->get();
        $cursos = Curso::visible()->get();
        $turnos = ConfiguracionAsistencia::activo()->select('config_id', 'config_categoria', 'config_turno')->distinct()->get();
        
        return view('asistencia-config.atrasos', compact('atrasos', 'estudiantes', 'cursos', 'turnos'));
    }
    
    private function calcularSiEsAtraso($asistencia, $permisos)
    {
        if (!$asistencia->estudiante) return false;
        
        $tienePermiso = $permisos->where('estud_codigo', $asistencia->estud_codigo)
            ->where('permiso_fecha_inicio', '<=', $asistencia->asis_fecha->format('Y-m-d'))
            ->where('permiso_fecha_fin', '>=', $asistencia->asis_fecha->format('Y-m-d'))
            ->first();
        
        if ($tienePermiso) return false;
        
        $configs = ConfiguracionAsistencia::activo()
            ->where(function($q) use ($asistencia) {
                $q->where('cur_codigo', $asistencia->estudiante->cur_codigo)
                  ->orWhereNull('cur_codigo');
            })
            ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
            ->get();
        
        if ($configs->isEmpty()) return false;
        
        $horaPartes = explode(':', substr($asistencia->asis_hora, 0, 5));
        $minutosLlegada = ((int)$horaPartes[0] * 60) + (int)$horaPartes[1];
        
        $config = null;
        $menorDiferencia = PHP_INT_MAX;
        
        foreach ($configs as $conf) {
            $horaEntrada = strlen($conf->hora_entrada) > 8 ? substr($conf->hora_entrada, 11, 5) : substr($conf->hora_entrada, 0, 5);
            $horaSalida = strlen($conf->hora_salida) > 8 ? substr($conf->hora_salida, 11, 5) : substr($conf->hora_salida, 0, 5);
            
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
        
        $tolerancia = strlen($config->tolerancia_atraso) > 8 ? substr($config->tolerancia_atraso, 11, 5) : substr($config->tolerancia_atraso, 0, 5);
        $toleranciaPartes = explode(':', $tolerancia);
        $minutosLimite = ((int)$toleranciaPartes[0] * 60) + (int)$toleranciaPartes[1];
        
        return $minutosLlegada > $minutosLimite;
    }

    public function atrasosReportePdf(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        
        $fechaInicio = $request->filled('fecha_inicio') ? $request->fecha_inicio : Carbon::today()->subDays(30)->format('Y-m-d');
        $fechaFin = $request->filled('fecha_fin') ? $request->fecha_fin : Carbon::today()->format('Y-m-d');
        
        $permisos = Permiso::where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->where('permiso_fecha_fin', '>=', $fechaInicio)
            ->get();
        
        $query = Asistencia::with('estudiante.curso')
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin]);
        
        if ($request->filled('estudiante')) {
            $query->where('estud_codigo', $request->estudiante);
        }
        
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        if ($request->filled('turno')) {
            $config = ConfiguracionAsistencia::activo()->where('config_id', $request->turno)->first();
            if ($config) {
                $horaEntrada = substr($config->hora_entrada, 0, 5);
                $horaSalida = substr($config->hora_salida, 0, 5);
                $query->whereBetween('asis_hora', [$horaEntrada . ':00', $horaSalida . ':59']);
                
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

        $asistencias = $query->orderBy('asis_fecha', 'desc')->get();
        
        $atrasos = collect();
        foreach ($asistencias as $asistencia) {
            $esAtraso = $this->calcularSiEsAtraso($asistencia, $permisos);
            if ($esAtraso) {
                $atrasos->push($asistencia);
            }
        }
        
        $curso = $request->filled('cur_codigo') ? Curso::where('cur_codigo', $request->cur_codigo)->first() : null;
        
        $pdf = Pdf::loadView('asistencia-config.atrasos-pdf', compact('atrasos', 'curso', 'fechaInicio', 'fechaFin'))->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-atrasos-' . date('Y-m-d') . '.pdf');
    }

    public function verificarAtraso($estudCodigo, $hora)
    {
        $estudiante = Estudiante::where('est_codigo', $estudCodigo)->with('curso')->first();
        if (!$estudiante) return null;

        $horaLlegada = Carbon::parse($hora);
        
        // Buscar todas las configuraciones del curso o generales
        $configuraciones = ConfiguracionAsistencia::activo()
            ->where(function($query) use ($estudiante) {
                $query->where('cur_codigo', $estudiante->cur_codigo)
                      ->orWhereNull('cur_codigo');
            })
            ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
            ->get();

        if ($configuraciones->isEmpty()) return null;

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
            
            Atraso::create([
                'atraso_codigo' => 'ATR' . time() . rand(100, 999),
                'estud_codigo' => $estudCodigo,
                'atraso_fecha' => now()->toDateString(),
                'atraso_hora' => $hora,
                'minutos_atraso' => $minutosAtraso
            ]);
        }
    }

    // ========== GESTIÓN DE PERMISOS ==========
    public function permisos(Request $request)
    {
        $query = Permiso::with('estudiante.curso', 'solicitantePadre')->orderBy('permiso_fecha_registro', 'desc');
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('permiso_fecha_inicio', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('permiso_fecha_fin', '<=', $request->fecha_fin);
        }
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        $permisos = $query->paginate(50);
        $estudiantes = Estudiante::visible()->get();
        $cursos = Curso::visible()->get();
        $padres = \App\Models\PadreFamilia::activo()->orderBy('pfam_nombres')->get();
        return view('asistencia-config.permisos', compact('permisos', 'estudiantes', 'cursos', 'padres'));
    }

    public function storePermiso(Request $request)
    {
        $request->validate([
            'estud_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'permiso_tipo' => 'required|in:PERMISO,LICENCIA',
            'permiso_fecha_inicio' => 'required|date',
            'permiso_fecha_fin' => 'required|date|after_or_equal:permiso_fecha_inicio',
            'permiso_origen' => 'required|in:PERSONAL,WHATSAPP,LLAMADA',
            'permiso_motivo' => 'required|string|max:200',
            'permiso_archivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $ultimoCodigo = Permiso::where('permiso_codigo', 'REGEXP', '^PER[0-9]{4}$')
            ->orderByRaw('CAST(SUBSTRING(permiso_codigo, 4) AS UNSIGNED) DESC')
            ->first();
        
        $nuevoNumero = 1;
        if ($ultimoCodigo) {
            $numero = (int)substr($ultimoCodigo->permiso_codigo, 3);
            $nuevoNumero = $numero + 1;
        }

        $fechaInicio = Carbon::parse($request->permiso_fecha_inicio);
        $fechaFin = Carbon::parse($request->permiso_fecha_fin);
        $diasDiferencia = $fechaInicio->diffInDays($fechaFin) + 1;

        for ($i = 0; $i < $diasDiferencia; $i++) {
            $fechaActual = $fechaInicio->copy()->addDays($i);
            $codigoPermiso = 'PER' . str_pad($nuevoNumero + $i, 4, '0', STR_PAD_LEFT);

            $data = [
                'permiso_codigo' => $codigoPermiso,
                'permiso_tipo' => $request->permiso_tipo,
                'permiso_numero' => $nuevoNumero + $i,
                'estud_codigo' => $request->estud_codigo,
                'permiso_fecha_inicio' => $fechaActual->format('Y-m-d'),
                'permiso_fecha_fin' => $fechaActual->format('Y-m-d'),
                'permiso_origen' => $request->permiso_origen,
                'permiso_motivo' => $request->permiso_motivo,
                'permiso_observacion' => $request->permiso_observacion,
                'permiso_solicitante_pfam' => $request->permiso_solicitante_pfam,
                'permiso_solicitante_nombre' => $request->permiso_solicitante_nombre,
                'permiso_estado' => 1,
                'permiso_aprobado_por' => auth()->user()->us_codigo
            ];

            if ($request->hasFile('permiso_archivo') && $i == 0) {
                $file = $request->file('permiso_archivo');
                $filename = 'permiso_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/permisos'), $filename);
                $data['permiso_archivo'] = $filename;
            }

            Permiso::create($data);
        }

        return redirect()->back()->with('success', "Se registraron {$diasDiferencia} permiso(s) exitosamente");
    }

    public function updatePermiso(Request $request, $id)
    {
        $request->validate([
            'estud_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'permiso_fecha_inicio' => 'required|date',
            'permiso_fecha_fin' => 'required|date|after_or_equal:permiso_fecha_inicio',
            'permiso_origen' => 'required|in:PERSONAL,WHATSAPP,LLAMADA',
            'permiso_motivo' => 'required|string|max:200',
            'permiso_archivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $permiso = Permiso::findOrFail($id);
        $data = $request->except('permiso_archivo');

        if ($request->hasFile('permiso_archivo')) {
            if ($permiso->permiso_archivo && file_exists(public_path('uploads/permisos/' . $permiso->permiso_archivo))) {
                unlink(public_path('uploads/permisos/' . $permiso->permiso_archivo));
            }
            $file = $request->file('permiso_archivo');
            $filename = 'permiso_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/permisos'), $filename);
            $data['permiso_archivo'] = $filename;
        }

        $permiso->update($data);
        return redirect()->back()->with('success', 'Permiso actualizado');
    }

    public function imprimirPermiso($id)
    {
        $permiso = Permiso::with('estudiante.curso', 'estudiante.padres')->findOrFail($id);
        $html = view('asistencia-config.permiso-pdf', compact('permiso'))->render();
        $pdf = Pdf::loadHTML($html, 'UTF-8')
            ->setPaper([0, 0, 283.46, 453.54], 'portrait');
        return $pdf->stream('permiso-' . $permiso->permiso_codigo . '.pdf');
    }

    public function reportePermisosPdf(Request $request)
    {
        $query = Permiso::with('estudiante.curso', 'estudiante.padres', 'solicitantePadre')
            ->where('permiso_estado', 1)
            ->orderBy('permiso_fecha_inicio', 'desc');
        
        $fechaInicio = $request->fecha_inicio ?? null;
        $fechaFin = $request->fecha_fin ?? null;
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('permiso_fecha_inicio', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('permiso_fecha_fin', '<=', $request->fecha_fin);
        }
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        
        $permisos = $query->get();
        
        $permisosPorCurso = $permisos->groupBy(function($permiso) {
            return $permiso->estudiante->curso->cur_nombre ?? 'Sin Curso';
        })->sortKeys();
        
        $pdf = Pdf::loadView('asistencia-config.permisos-reporte-pdf', compact('permisosPorCurso', 'fechaInicio', 'fechaFin'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-permisos-' . date('Y-m-d') . '.pdf');
    }

    public function destroyPermiso($id)
    {
        Permiso::findOrFail($id)->update(['permiso_estado' => 0]);
        return redirect()->back()->with('success', 'Permiso eliminado');
    }

    // ========== GESTIÓN DE FECHAS FESTIVAS ==========
    public function fechasFestivas()
    {
        $festivos = FechaFestiva::activo()->orderBy('festivo_fecha', 'desc')->paginate(50);
        return view('asistencia-config.festivos', compact('festivos'));
    }

    public function storeFestivo(Request $request)
    {
        $request->validate([
            'festivo_fecha' => 'required|date',
            'festivo_nombre' => 'required|string|max:100',
            'festivo_tipo' => 'required|in:1,2'
        ]);

        FechaFestiva::create([
            'festivo_codigo' => 'FEST' . time(),
            'festivo_fecha' => $request->festivo_fecha,
            'festivo_nombre' => $request->festivo_nombre,
            'festivo_descripcion' => $request->festivo_descripcion,
            'festivo_hora_entrada' => $request->festivo_hora_entrada,
            'festivo_hora_salida' => $request->festivo_hora_salida,
            'festivo_tipo' => $request->festivo_tipo
        ]);

        return redirect()->back()->with('success', 'Fecha festiva registrada');
    }

    public function updateFestivo(Request $request, $id)
    {
        $festivo = FechaFestiva::findOrFail($id);
        $festivo->update($request->all());
        return redirect()->back()->with('success', 'Fecha festiva actualizada');
    }

    public function destroyFestivo($id)
    {
        FechaFestiva::findOrFail($id)->update(['festivo_estado' => 0]);
        return redirect()->back()->with('success', 'Fecha festiva eliminada');
    }

    // ========== REPORTES ==========
    public function reportes()
    {
        $cursos = Curso::visible()->get();
        $estudiantes = Estudiante::visible()->get();
        return view('asistencia-config.reportes', compact('cursos', 'estudiantes'));
    }

    public function generarReporte(Request $request)
    {
        $tipo = $request->tipo_reporte;
        $formato = $request->formato;

        $data = $this->obtenerDatosReporte($request);

        if ($formato == 'pdf') {
            return $this->generarPDF($data, $tipo);
        } else {
            return $this->generarExcel($data, $tipo);
        }
    }

    private function obtenerDatosReporte($request)
    {
        $query = Asistencia::with('estudiante.curso');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('asis_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('curso_id')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->curso_id);
            });
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estud_codigo', $request->estudiante_id);
        }

        return [
            'asistencias' => $query->get(),
            'atrasos' => Atraso::with('estudiante')->whereBetween('atraso_fecha', [$request->fecha_inicio ?? now()->subMonth(), $request->fecha_fin ?? now()])->get(),
            'permisos' => Permiso::with('estudiante')->whereBetween('permiso_fecha_inicio', [$request->fecha_inicio ?? now()->subMonth(), $request->fecha_fin ?? now()])->get(),
            'filtros' => $request->all()
        ];
    }

    private function generarPDF($data, $tipo)
    {
        $pdf = Pdf::loadView('asistencia-config.reportes.pdf', $data);
        return $pdf->download('reporte_asistencia_' . date('Y-m-d') . '.pdf');
    }

    private function generarExcel($data, $tipo)
    {
        // Implementar exportación a Excel
        return response()->json(['message' => 'Exportación a Excel en desarrollo']);
    }
}
