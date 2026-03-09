<?php

if (!function_exists('getConfiguracionPorEstudiante')) {
    /**
     * Obtiene la configuración de asistencia para un estudiante
     * Prioriza la configuración específica del curso sobre la general
     */
    function getConfiguracionPorEstudiante($estudCodigo)
    {
        $estudiante = \App\Models\Estudiante::where('est_codigo', $estudCodigo)->first();
        if (!$estudiante) return null;

        return \App\Models\ConfiguracionAsistencia::activo()
            ->where(function($query) use ($estudiante) {
                $query->where('cur_codigo', $estudiante->cur_codigo)
                      ->orWhereNull('cur_codigo');
            })
            ->orderByRaw('CASE WHEN cur_codigo IS NULL THEN 1 ELSE 0 END')
            ->first();
    }
}

if (!function_exists('esAtraso')) {
    /**
     * Verifica si una hora de llegada es considerada atraso
     */
    function esAtraso($estudCodigo, $horaLlegada)
    {
        $config = getConfiguracionPorEstudiante($estudCodigo);
        if (!$config) return false;

        $horaEntrada = \Carbon\Carbon::parse($config->hora_entrada);
        $tolerancia = \Carbon\Carbon::parse($config->tolerancia_atraso);
        $hora = \Carbon\Carbon::parse($horaLlegada);
        
        $minutosTolerancia = $tolerancia->hour * 60 + $tolerancia->minute;
        $horaLimite = $horaEntrada->copy()->addMinutes($minutosTolerancia);

        return $hora->gt($horaLimite);
    }
}
