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
        .licencias-header { background-color:#17a2b8; color:#fff; padding:1px 3px; font-size:5px; text-align:center; font-weight:bold; letter-spacing:0.3px; margin-top:1px; }
        .licencias-list { font-size: 5.5px; line-height: 1.2; padding: 2px 3px; background:#f4fbfd; }
        .licencia-item { padding: 1px 0; border-bottom: 0.5px dotted #cfe9ee; }
        .licencia-item:last-child { border-bottom: none; }
        .licencia-tag { display:inline-block; background:#17a2b8; color:#fff; padding:0 2px; border-radius:1px; font-size:4.5px; font-weight:bold; margin-left:2px; }
        .licencia-motivo { font-size:4.5px; color:#555; font-style:italic; display:block; margin-left:14px; }
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
                            @forelse($datos['estudiantes'] as $estudiante)
                                @php $retirado = ($estudiante->est_visible ?? 1) == 0; @endphp
                                <div class="estudiante-item" style="{{ $retirado ? 'background:#ffe6e6;color:#c0392b;font-weight:700;' : '' }}">
                                    <span class="estudiante-numero">{{ isset($datos['lista']) && isset($datos['lista'][$estudiante->est_codigo]) ? $datos['lista'][$estudiante->est_codigo] : $loop->iteration }}</span>
                                    <span>
                                        {{ mb_strtoupper($estudiante->est_apellidos, 'UTF-8') }} {{ mb_strtoupper($estudiante->est_nombres, 'UTF-8') }}
                                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 3px;border-radius:2px;font-size:7px;margin-left:3px;">RET</span>@endif
                                    </span>
                                </div>
                            @empty
                                <div class="estudiante-item" style="color:#888;font-style:italic;">Sin faltas</div>
                            @endforelse
                        </div>
                        @if(!empty($datos['licencias']) && count($datos['licencias']) > 0)
                            <div class="licencias-header">CON LICENCIA / PERMISO ({{ count($datos['licencias']) }})</div>
                            <div class="licencias-list">
                                @foreach($datos['licencias'] as $lic)
                                    <div class="licencia-item">
                                        <span class="estudiante-numero">{{ isset($datos['lista']) && isset($datos['lista'][$lic->estudiante->est_codigo]) ? $datos['lista'][$lic->estudiante->est_codigo] : $loop->iteration }}</span>
                                        <span>{{ mb_strtoupper($lic->estudiante->est_apellidos, 'UTF-8') }} {{ mb_strtoupper($lic->estudiante->est_nombres, 'UTF-8') }}</span>
                                        <span class="licencia-tag">{{ $lic->tipo }}</span>
                                        @if(!empty($lic->motivo))<span class="licencia-motivo">{{ $lic->motivo }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
