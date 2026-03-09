<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Permisos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 15px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; font-weight: bold; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 15px; right: 15px; text-align: right; }
        .fecha-box .label { font-size: 8px; font-weight: bold; }
        .fecha-box .fecha { font-size: 10px; font-weight: bold; }
        .fecha-box .saib { font-size: 7px; font-style: italic; }
        .title-section { text-align: center; margin: 10px 0; }
        .title-section h2 { font-size: 12px; font-weight: bold; margin: 2px 0; }
        .title-section p { font-size: 9px; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 4px 3px; font-size: 8px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { vertical-align: top; }
        .col-num { width: 3%; text-align: center; }
        .col-curso { width: 15%; }
        .col-paralelo { width: 5%; text-align: center; }
        .col-estudiante { width: 25%; }
        .col-detalle { width: 20%; }
        .col-solicitante { width: 32%; }
        .footer { position: fixed; bottom: 10px; left: 15px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        <div class="label">Fecha</div>
        <div class="fecha">{{ now()->format('d/m/Y') }}</div>
        <div class="saib">Saib</div>
    </div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>UNIDAD EDUCATIVA PRIVADA</h3>
            <h3>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>Faltas con Licencias</h2>
        @if($fechaInicio || $fechaFin)
            <p>Período: 
                @if($fechaInicio)
                    {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                @else
                    Inicio
                @endif
                - 
                @if($fechaFin)
                    {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                @else
                    Fin
                @endif
            </p>
        @else
            <p>Todos los períodos</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-curso">Curso</th>
                <th class="col-paralelo">Paralelo</th>
                <th class="col-estudiante">Estudiante</th>
                <th class="col-detalle">Detalle Falta</th>
                <th class="col-solicitante">Solicitante</th>
            </tr>
        </thead>
        <tbody>
            @php $contador = 0; @endphp
            @forelse($permisosPorCurso as $cursoNombre => $permisosDelCurso)
                <tr style="background-color: #e0e0e0;">
                    <td colspan="6" style="font-weight: bold; text-align: left; padding: 6px;">{{ strtoupper($cursoNombre) }}</td>
                </tr>
                @foreach($permisosDelCurso as $permiso)
                    @php $contador++; @endphp
                    <tr>
                        <td class="col-num">{{ $contador }}</td>
                        <td class="col-curso">{{ strtoupper($permiso->estudiante->curso->cur_nombre ?? 'N/A') }}</td>
                        <td class="col-paralelo">{{ $permiso->estudiante->curso->cur_paralelo ?? 'A' }}</td>
                        <td class="col-estudiante">{{ strtoupper($permiso->estudiante->est_nombres ?? '') }} {{ strtoupper($permiso->estudiante->est_apellidos ?? '') }}</td>
                        <td class="col-detalle">{{ strtoupper($permiso->permiso_motivo) }}</td>
                        <td class="col-solicitante">{{ strtoupper($permiso->solicitante_nombre_completo ?? ($permiso->estudiante->padres->first()->pfam_nombres ?? '-')) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No hay permisos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
