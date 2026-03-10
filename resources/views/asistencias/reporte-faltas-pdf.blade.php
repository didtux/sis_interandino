<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resumen de Faltas Sin Licencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; padding: 5mm; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .header-left { float: left; width: 70%; border-bottom: 2px solid #000; padding-bottom: 2px; }
        .logo { float: left; width: 50px; }
        .logo img { width: 40px; height: auto; }
        .header-info { margin-left: 55px; }
        .header-info h3 { font-size: 8px; margin: 0; line-height: 1.1; font-weight: bold; }
        .header-info p { font-size: 6px; margin: 0; }
        .fecha-box { float: right; width: 28%; text-align: right; }
        .fecha-box-inner { display: inline-block; border: 1.5px solid #000; padding: 3px 6px; text-align: center; font-size: 6.5px; }
        .fecha-box .label { font-weight: bold; font-size: 5.5px; }
        .fecha-box .fecha { font-size: 7px; font-weight: bold; margin: 1px 0; }
        .fecha-box .prelim { font-size: 5px; font-style: italic; color: #666; }
        .title-section { clear: both; text-align: center; margin: 3px 0; background-color: #f0f0f0; padding: 3px; border: 1px solid #000; }
        .title-section h2 { font-size: 9px; font-weight: bold; margin: 0; }
        .title-section p { font-size: 6.5px; margin: 1px 0; }
        .content-wrapper { width: 100%; }
        .curso-row { width: 100%; margin-bottom: 4px; }
        .curso-row::after { content: ""; display: table; clear: both; }
        .curso-col { float: left; width: 25%; padding: 0 1px; box-sizing: border-box; }
        .curso-section { border: 1px solid #ddd; page-break-inside: avoid; }
        .curso-header { background-color: #2c3e50; color: white; padding: 2px 3px; font-weight: bold; font-size: 6.5px; text-align: center; }
        .curso-horario { background-color: #34495e; color: white; padding: 1px 3px; font-size: 5px; text-align: center; font-style: italic; }
        .estudiantes-list { font-size: 5.5px; line-height: 1.2; padding: 2px 3px; }
        .estudiante-item { padding: 1px 0; border-bottom: 0.5px dotted #ddd; }
        .estudiante-item:last-child { border-bottom: none; }
        .estudiante-numero { display: inline-block; width: 14px; font-weight: bold; color: #333; }
        .footer { clear: both; margin-top: 10px; font-size: 5.5px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="clearfix">
        <div class="header-left">
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
        <div class="fecha-box">
            <div class="fecha-box-inner">
                <div class="label">Fecha</div>
                <div class="fecha">{{ now()->format('d/m/Y') }}</div>
                <div class="prelim">Preliminar</div>
            </div>
        </div>
    </div>

    <div class="title-section">
        <h2>RESUMEN DE FALTAS SIN LICENCIA TURNO {{ mb_strtoupper($turno, 'UTF-8') }}</h2>
        <p>Fecha: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd') }}
        @if($config)
            | Horario: {{ substr($config->hora_entrada, 0, 5) }} - {{ substr($config->hora_salida, 0, 5) }}
        @endif
        </p>
    </div>

    @if(count($datosPorCurso) > 0)
        <div class="content-wrapper">
            @foreach($datosPorCurso as $index => $datos)
                @if($index % 4 == 0)
                    <div class="curso-row">
                @endif
                
                <div class="curso-col">
                    <div class="curso-section">
                        <div class="curso-header">{{ $datos['curso']->cur_nombre }}</div>
                        @if(isset($datos['horario']))
                            <div class="curso-horario">{{ $datos['horario'] }}</div>
                        @endif
                        <div class="estudiantes-list">
                            @php $contador = 1; @endphp
                            @foreach($datos['estudiantes'] as $estudiante)
                                <div class="estudiante-item">
                                    <span class="estudiante-numero">{{ $contador++ }}</span>
                                    <span>{{ mb_strtoupper($estudiante->est_apellidos, 'UTF-8') }} {{ mb_strtoupper($estudiante->est_nombres, 'UTF-8') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                @if(($index + 1) % 4 == 0 || $loop->last)
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div style="text-align: center; margin-top: 20px; font-size: 8px;">
            <p><strong>No hay estudiantes con faltas sin licencia en esta fecha</strong></p>
        </div>
    @endif

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
