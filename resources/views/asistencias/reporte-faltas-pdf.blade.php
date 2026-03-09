<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen de Faltas Sin Licencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 8mm; }
        body { font-family: Arial, sans-serif; font-size: 7px; }
        .header { display: table; width: 100%; margin-bottom: 5px; border-bottom: 2px solid #000; padding-bottom: 3px; }
        .logo { display: table-cell; width: 50px; vertical-align: middle; }
        .logo img { width: 45px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 9px; margin: 0; line-height: 1.2; font-weight: bold; }
        .header-info p { font-size: 6.5px; margin: 0; }
        .fecha-box { position: absolute; top: 3mm; right: 8mm; border: 1.5px solid #000; padding: 4px 8px; text-align: center; font-size: 7px; }
        .fecha-box .label { font-weight: bold; font-size: 6px; }
        .fecha-box .fecha { font-size: 8px; font-weight: bold; margin: 2px 0; }
        .fecha-box .prelim { font-size: 5.5px; font-style: italic; color: #666; }
        .title-section { text-align: center; margin: 5px 0; background-color: #f0f0f0; padding: 4px; border: 1px solid #000; }
        .title-section h2 { font-size: 10px; font-weight: bold; margin: 0; }
        .title-section p { font-size: 7px; margin: 2px 0; }
        .columns-container { display: table; width: 100%; margin-top: 8px; }
        .column { display: table-cell; width: 33.33%; padding: 0 5px; vertical-align: top; border-right: 1.5px solid #ccc; }
        .column:last-child { border-right: none; }
        .curso-section { break-inside: avoid; margin-bottom: 10px; border: 1px solid #ddd; }
        .curso-header { background-color: #2c3e50; color: white; padding: 3px 6px; font-weight: bold; font-size: 7.5px; text-align: center; border-bottom: 2px solid #000; }
        .estudiantes-list { font-size: 6.5px; line-height: 1.4; padding: 3px 5px; }
        .estudiante-item { padding: 1.5px 0; border-bottom: 0.5px dotted #ddd; }
        .estudiante-item:last-child { border-bottom: none; }
        .estudiante-numero { display: inline-block; width: 18px; font-weight: bold; color: #333; }
        .footer { position: fixed; bottom: 5mm; left: 8mm; font-size: 6px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        <div class="label">Fecha</div>
        <div class="fecha">{{ now()->format('d/m/Y') }}</div>
        <div class="prelim">Preliminar</div>
    </div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa<br>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>RESUMEN DE FALTAS SIN LICENCIA TURNO {{ strtoupper($turno) }}</h2>
        <p>Fecha: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd') }}
        @if($config)
            | Horario: {{ substr($config->hora_entrada, 0, 5) }} - {{ substr($config->hora_salida, 0, 5) }}
        @endif
        </p>
    </div>

    @php
        $totalCursos = count($datosPorCurso);
        $columnas = $totalCursos <= 6 ? 3 : 3;
        $cursosPorColumna = ceil($totalCursos / $columnas);
    @endphp

    <div class="columns-container">
        @for($col = 0; $col < $columnas; $col++)
            <div class="column">
                @for($i = 0; $i < $cursosPorColumna; $i++)
                    @php
                        $indice = ($col * $cursosPorColumna) + $i;
                    @endphp
                    @if($indice < $totalCursos)
                        @php $datos = $datosPorCurso[$indice]; @endphp
                        <div class="curso-section">
                            <div class="curso-header">{{ $datos['curso']->cur_nombre }}</div>
                            <div class="estudiantes-list">
                                @php $contador = 1; @endphp
                                @foreach($datos['estudiantes'] as $estudiante)
                                    <div class="estudiante-item">
                                        <span class="estudiante-numero">{{ $contador++ }}</span>
                                        <span>{{ strtoupper($estudiante->est_apellidos) }} {{ strtoupper($estudiante->est_nombres) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endfor
            </div>
        @endfor
    </div>

    @if(count($datosPorCurso) == 0)
        <div style="text-align: center; margin-top: 30px; font-size: 9px;">
            <p><strong>No hay estudiantes con faltas sin licencia en esta fecha</strong></p>
        </div>
    @endif

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
