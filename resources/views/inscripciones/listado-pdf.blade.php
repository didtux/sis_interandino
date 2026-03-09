<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 5px 0; }
        .info { margin-bottom: 15px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f0f0f0; border: 1px solid #000; padding: 5px; font-size: 10px; }
        td { border: 1px solid #000; padding: 4px; font-size: 10px; }
        .totales { margin-top: 15px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>U.E. PRIVADA INTERANDINO BOLIVIANO</h2>
        <h3>LISTADO DE INSCRIPCIONES</h3>
    </div>

    <div class="info">
        <strong>Usuario:</strong> {{ auth()->user()->us_nombres }}<br>
        <strong>Fecha:</strong> {{ date('d/m/Y') }}<br>
        <strong>Hora:</strong> {{ date('H:i:s') }}<br>
        @if($request->fecha_inicio && $request->fecha_fin)
            <strong>Período:</strong> {{ date('d/m/Y', strtotime($request->fecha_inicio)) }} - {{ date('d/m/Y', strtotime($request->fecha_fin)) }}<br>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Estudiante</th>
                <th>Curso</th>
                <th>Gestión</th>
                <th>Fecha</th>
                <th>Monto Total</th>
                <th>Pagado</th>
                <th>Saldo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscripciones as $i)
                <tr>
                    <td>{{ $i->insc_codigo }}</td>
                    <td>{{ $i->estudiante->est_nombres ?? 'N/A' }} {{ $i->estudiante->est_apellidos ?? '' }}</td>
                    <td>{{ $i->curso->cur_nombre ?? 'N/A' }}</td>
                    <td>{{ $i->insc_gestion }}</td>
                    <td>{{ $i->insc_fecha ? $i->insc_fecha->format('d/m/Y') : 'N/A' }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_monto_total, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_monto_pagado, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_saldo, 2) }}</td>
                    <td>
                        @if($i->insc_estado == 2)
                            Pagada
                        @elseif($i->insc_saldo > 0)
                            Pendiente
                        @else
                            Cancelada
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align: center;">No hay registros</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totales">
        <table style="width: 50%; margin-left: auto; border: none;">
            <tr>
                <td style="border: none; text-align: right;">TOTAL INSCRIPCIONES:</td>
                <td style="border: none; text-align: right;">Bs. {{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right;">TOTAL PAGADO:</td>
                <td style="border: none; text-align: right;">Bs. {{ number_format($pagado, 2) }}</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right;">TOTAL SALDO:</td>
                <td style="border: none; text-align: right;">Bs. {{ number_format($saldo, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
