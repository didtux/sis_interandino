<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Rutas de Transporte</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }
        .header-table { width: 100%; margin-bottom: 15px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 60px; }
        .titulo { text-align: center; }
        .titulo h3 { margin: 0; font-size: 12px; }
        .titulo p { margin: 2px 0; font-size: 9px; }
        .fecha-badge { float: right; background-color: #4472C4; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .ruta-section { margin-bottom: 20px; page-break-inside: avoid; }
        .ruta-titulo { background-color: #000; color: white; padding: 8px; text-align: center; font-size: 11px; font-weight: bold; margin: 15px 0 10px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 4px; border: 1px solid #ddd; font-size: 9px; }
        .info-table th { background-color: #f0f0f0; font-weight: bold; width: 25%; text-align: left; padding: 4px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #000; }
        th { background-color: #f0f0f0; padding: 6px; text-align: center; font-size: 9px; font-weight: bold; }
        td { padding: 5px; text-align: left; font-size: 9px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .numero { width: 30px; text-align: center; }
        .total-row { background-color: #e3f2fd; font-weight: bold; }
        .footer { margin-top: 15px; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                <img src="{{ public_path('img/logo.png') }}" alt="Logo" class="logo">
            </td>
            <td class="titulo">
                <h3>Unidad Educativa</h3>
                <h3>INTERANDINO BOLIVIANO</h3>
                <p>Dir. Calle Victor Gutierrez Nro 3339</p>
                <p>Teléfonos: 2840320</p>
            </td>
            <td style="width: 150px; text-align: right;">
                <div class="fecha-badge">
                    Fecha<br>{{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 10px;">
        REPORTE DE RUTAS DE TRANSPORTE
    </div>

    @foreach($rutas as $ruta)
        @php
            $asignacion = $ruta->asignaciones->where('asig_estado', 1)->first();
            $chofer = $asignacion ? $asignacion->chofer : null;
            $vehiculo = $asignacion ? $asignacion->vehiculo : null;
            $estudiantes = $ruta->estudiantes->where('ter_estado', 1);
            $totalMonto = $estudiantes->sum(function($er) { return $er->pago ? $er->pago->tpago_monto : 0; });
        @endphp

        <div class="ruta-section">
            <div class="ruta-titulo">{{ $ruta->ruta_nombre }} ({{ $ruta->ruta_codigo }})</div>
            
            <table class="info-table">
                <tr>
                    <th>Conductor:</th>
                    <td>{{ $chofer ? $chofer->chof_nombres . ' ' . $chofer->chof_apellidos : '-' }}</td>
                    <th>Teléfono:</th>
                    <td>{{ $chofer->chof_telefono ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Vehículo:</th>
                    <td colspan="3">{{ $vehiculo ? $vehiculo->veh_marca . ' ' . $vehiculo->veh_modelo . ' - ' . $vehiculo->veh_placa : '-' }}</td>
                </tr>
                @if($ruta->ruta_descripcion)
                <tr>
                    <th>Descripción:</th>
                    <td colspan="3">{{ $ruta->ruta_descripcion }}</td>
                </tr>
                @endif
            </table>

            <table>
                <thead>
                    <tr>
                        <th class="numero">#</th>
                        <th style="width: 30%;">ESTUDIANTE</th>
                        <th style="width: 15%;">CURSO</th>
                        <th style="width: 35%;">DIRECCIÓN</th>
                        <th style="width: 15%;">MONTO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estudiantes as $index => $er)
                        <tr>
                            <td class="numero">{{ $index + 1 }}</td>
                            <td>{{ strtoupper($er->estudiante->est_apellidos . ' ' . $er->estudiante->est_nombres) }}</td>
                            <td class="text-center">{{ $er->estudiante->curso->cur_nombre ?? '-' }}</td>
                            <td>{{ $er->ter_direccion_recogida ?? '-' }}</td>
                            <td class="text-right">Bs. {{ $er->pago ? number_format($er->pago->tpago_monto, 2) : '0.00' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay estudiantes asignados</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" class="text-right">TOTAL:</td>
                        <td class="text-right">Bs. {{ number_format($totalMonto, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}
    </div>
</body>
</html>
