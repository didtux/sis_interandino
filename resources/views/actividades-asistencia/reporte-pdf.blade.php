<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Actividad - {{ $actividad->act_nombre }}</title>
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
        .title-section { clear: both; text-align: center; margin: 3px 0; background: #f0f0f0; padding: 3px; border: 1px solid #000; }
        .title-section h2 { font-size: 9px; font-weight: bold; margin: 0; }
        .title-section p { font-size: 6.5px; margin: 1px 0; }
        .filtros { text-align: center; font-size: 6px; color: #666; margin: 2px 0; }
        .stats { text-align: center; margin: 4px 0; }
        .stats span { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 6.5px; font-weight: bold; color: #fff; margin: 0 2px; }

        /* Layout columnas por curso */
        .curso-row { width: 100%; margin-bottom: 3px; }
        .curso-row::after { content: ""; display: table; clear: both; }
        .curso-col { float: left; width: 33.33%; padding: 0 1px; }
        .curso-section { border: 1px solid #ccc; page-break-inside: avoid; margin-bottom: 3px; }
        .curso-header { background: #2c3e50; color: #fff; padding: 2px 4px; font-weight: bold; font-size: 6.5px; text-align: center; }
        .curso-count { background: #34495e; color: #aaa; padding: 1px 4px; font-size: 5px; text-align: center; }
        .est-list { font-size: 5.5px; line-height: 1.3; padding: 2px 3px; }
        .est-item { padding: 1px 0; border-bottom: 0.5px dotted #ddd; }
        .est-item:last-child { border-bottom: none; }
        .est-num { display: inline-block; width: 12px; font-weight: bold; color: #333; }
        .est-hora { color: #888; font-size: 5px; }
        .est-cat { color: #17a2b8; font-size: 5px; }

        .footer { clear: both; margin-top: 6px; font-size: 5.5px; color: #666; text-align: center; }
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
            </div>
        </div>
    </div>

    <div class="title-section">
        <h2>{{ $tab == 'registros' ? 'REPORTE DE ASISTENCIA' : 'REPORTE DE FALTAS' }}</h2>
        <p><strong>{{ mb_strtoupper($actividad->act_nombre, 'UTF-8') }}</strong> | Fecha actividad: {{ $actividad->act_fecha->format('d/m/Y') }}</p>
        @if($actividad->act_descripcion)
            <p>{{ $actividad->act_descripcion }}</p>
        @endif
    </div>

    @if(count($filtros) > 0)
        <div class="filtros">Filtros: {{ implode(' | ', $filtros) }}</div>
    @endif

    <div class="stats">
        @if($tab == 'registros')
            <span style="background:#27ae60;">Total: {{ $registros->count() }}</span>
            <span style="background:#2c3e50;">Cursos: {{ $porCurso->count() }}</span>
            @php $porCat = $registros->groupBy(fn($r) => $r->categoria->actcat_nombre ?? '-'); @endphp
            @foreach($porCat as $catNombre => $regs)
                <span style="background:#17a2b8;">{{ $catNombre }}: {{ $regs->count() }}</span>
            @endforeach
        @else
            <span style="background:#c0392b;">Total Faltas: {{ $faltas->count() }}</span>
            <span style="background:#2c3e50;">Cursos: {{ $porCurso->count() }}</span>
        @endif
    </div>

    @if($porCurso->count() > 0)
        <div class="content-wrapper">
            @foreach($porCurso as $cursoNombre => $items)
                @if($loop->index % 3 == 0)
                    <div class="curso-row">
                @endif

                <div class="curso-col">
                    <div class="curso-section">
                        <div class="curso-header">{{ $cursoNombre }} ({{ $items->count() }})</div>
                        <div class="est-list">
                            @if($tab == 'registros')
                                @foreach($items->sortBy(fn($r) => $r->estudiante->est_apellidos ?? '') as $r)
                                    <div class="est-item">
                                        <span class="est-num">{{ $loop->iteration }}</span>
                                        {{ mb_strtoupper($r->estudiante->est_apellidos ?? '', 'UTF-8') }} {{ $r->estudiante->est_nombres ?? '' }}
                                        <span class="est-hora">{{ $r->actreg_hora }}</span>
                                        @if($porCat->count() > 1)
                                            <span class="est-cat">[{{ $r->categoria->actcat_nombre ?? '' }}]</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                @foreach($items->sortBy('est_apellidos') as $est)
                                    <div class="est-item">
                                        <span class="est-num">{{ $loop->iteration }}</span>
                                        {{ mb_strtoupper($est->est_apellidos, 'UTF-8') }} {{ $est->est_nombres }}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                @if(($loop->index + 1) % 3 == 0 || $loop->last)
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div style="text-align:center;margin-top:20px;font-size:8px;">
            <p><strong>{{ $tab == 'registros' ? 'No hay registros' : 'Todos los estudiantes tienen registro' }}</strong></p>
        </div>
    @endif

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | Actividades - Asistencia | Gestión {{ date('Y') }}
    </div>
</body>
</html>
