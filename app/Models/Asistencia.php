<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'colegio_asistencia';
    protected $primaryKey = 'asis_id';
    public $timestamps = false;

    protected $fillable = [
        'estud_codigo',
        'asis_fecha',
        'asis_hora',
        'asis_fecha2'
    ];

    protected $casts = [
        'asis_fecha' => 'date',
        'asis_fecha2' => 'datetime'
    ];

    // Relación con Estudiante
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estud_codigo', 'est_codigo');
    }

    // Obtener estado de asistencia (puntual/atrasado)
    public function getEstadoAttribute()
    {
        if (!$this->estudiante) return 'puntual';
        
        // Verificar si tiene permiso
        $tienePermiso = Permiso::where('permiso_estado', 1)
            ->where('estud_codigo', $this->estud_codigo)
            ->where('permiso_fecha_inicio', '<=', $this->asis_fecha->format('Y-m-d'))
            ->where('permiso_fecha_fin', '>=', $this->asis_fecha->format('Y-m-d'))
            ->exists();
        
        if ($tienePermiso) return 'permiso';
        
        // Obtener todas las configuraciones
        $configs = ConfiguracionAsistencia::activo()
            ->where(function($q) {
                $q->where('cur_codigo', $this->estudiante->cur_codigo)
                  ->orWhereNull('cur_codigo');
            })
            ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
            ->get();
        
        if ($configs->isEmpty()) return 'puntual';
        
        // Convertir hora de llegada a minutos
        $horaPartes = explode(':', substr($this->asis_hora, 0, 5));
        $minutosLlegada = ((int)$horaPartes[0] * 60) + (int)$horaPartes[1];
        
        // Buscar el turno más cercano
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
        
        // La tolerancia es la hora límite completa
        $tolerancia = is_object($config->tolerancia_atraso) 
            ? $config->tolerancia_atraso->format('H:i') 
            : (strlen($config->tolerancia_atraso) > 8 ? substr($config->tolerancia_atraso, 11, 5) : substr($config->tolerancia_atraso, 0, 5));
        $toleranciaPartes = explode(':', $tolerancia);
        $minutosLimite = ((int)$toleranciaPartes[0] * 60) + (int)$toleranciaPartes[1];
        
        return $minutosLlegada > $minutosLimite ? 'atrasado' : 'puntual';
    }
}
