<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asistencia;
use App\Models\Atraso;
use App\Models\ConfiguracionAsistencia;
use Carbon\Carbon;

class SincronizarAtrasos extends Command
{
    protected $signature = 'asistencia:sincronizar-atrasos {--fecha= : Fecha específica en formato Y-m-d}';
    protected $description = 'Sincroniza los atrasos basándose en las asistencias existentes y la configuración';

    public function handle()
    {
        $fecha = $this->option('fecha');
        
        $query = Asistencia::with('estudiante.curso');
        
        if ($fecha) {
            $query->whereDate('asis_fecha', $fecha);
            $this->info("Sincronizando atrasos para la fecha: $fecha");
        } else {
            $this->info("Sincronizando todos los atrasos...");
        }
        
        $asistencias = $query->get();
        $procesados = 0;
        $atrasosCreados = 0;
        
        foreach ($asistencias as $asistencia) {
            if (!$asistencia->estudiante) continue;
            
            $procesados++;
            
            // Verificar si ya existe atraso
            $atrasoExistente = Atraso::where('estud_codigo', $asistencia->estud_codigo)
                ->whereDate('atraso_fecha', $asistencia->asis_fecha)
                ->exists();
            
            if ($atrasoExistente) continue;
            
            // Obtener configuraciones
            $configs = ConfiguracionAsistencia::activo()
                ->where(function($q) use ($asistencia) {
                    $q->where('cur_codigo', $asistencia->estudiante->cur_codigo)
                      ->orWhereNull('cur_codigo');
                })
                ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
                ->get();
            
            if ($configs->isEmpty()) continue;
            
            $horaLlegada = Carbon::parse($asistencia->asis_hora);
            $config = null;
            
            // Buscar configuración que coincida
            foreach ($configs as $conf) {
                $horaEntrada = Carbon::parse($conf->hora_entrada);
                $horaSalida = Carbon::parse($conf->hora_salida);
                
                if ($horaLlegada->between($horaEntrada->copy()->subHours(2), $horaSalida->copy()->addHours(2))) {
                    $config = $conf;
                    break;
                }
            }
            
            if (!$config) {
                $config = $configs->first();
            }
            
            $horaEntrada = Carbon::parse($config->hora_entrada);
            $tolerancia = Carbon::parse($config->tolerancia_atraso);
            $minutosTolerancia = $tolerancia->hour * 60 + $tolerancia->minute;
            $horaLimite = $horaEntrada->copy()->addMinutes($minutosTolerancia);
            
            if ($horaLlegada->gt($horaLimite)) {
                $minutosAtraso = $horaLlegada->diffInMinutes($horaEntrada);
                
                Atraso::create([
                    'atraso_codigo' => 'ATR' . time() . rand(100, 999),
                    'estud_codigo' => $asistencia->estud_codigo,
                    'atraso_fecha' => $asistencia->asis_fecha,
                    'atraso_hora' => $asistencia->asis_hora,
                    'minutos_atraso' => $minutosAtraso
                ]);
                
                $atrasosCreados++;
            }
        }
        
        $this->info("Procesados: $procesados asistencias");
        $this->info("Atrasos creados: $atrasosCreados");
        $this->info("¡Sincronización completada!");
        
        return 0;
    }
}
