<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Actividad - {{ $actividad->act_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 10mm; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.2; font-weight: bold; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 10mm; right: 10mm; background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 12px; font-weight: bold; font-size: 8px; text-align: center; }
        .title-section { text-align: center; margin: 8px 0; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .title-section h2 { font-size: 13px; font-weight: bold; margin: 2px 0; }
        .title-section p { font-size: 8px; margin: 2px 0; }
        .filtros { background: #f8f9fa; border: 1px solid #ddd; padding: 4px 8px; margin-bottom: 8px; font-size: 8px; border-radius: 4px; }
        .stats { margin-bottom: 8px; }
        .stats span { display: inline-block; background: #2c3e50; color: white; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; margin-right: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #333; padding: 3px 5px; }
        th { background-color: #2c3e50; color: white; font-size: 8px; font-weight: bold; text-align: center; }
        td { font-size: 8px; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .badge-cat { background: #17a2b8; color: white; padding: 1px 5px; border-radius: 6px; font-size: 7px; }
        .badge-curso { background: #3498db; color: white; padding: 1px 5px; border-radius: 6px; font-size: 7px; }
        .footer { position: fixed; bottom: 10mm; left: 10mm; right: 10mm; font-size: 7px; color: #666; border-top: 1px solid #ccc; padding-top: 3px; }
        .footer-left { float: left; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <div class="fecha-box">
        Fecha<br>{{ now()->format('d/m/Y') }}
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
        <h2>{{ $tab == 'registros' ? 'REPORTE DE ASISTENCIA' : 'REPORTE DE FALTAS' }} - ACTIVIDAD</h2>
        <p><strong>{{ mb_strtoupper($actividad->act_nombre, 'UTF-8') }}</strong> | Fecha: {{ $actividad->act_fecha->format('d/m/Y') }}</p>
        @if($actividad->act_descripcion)
            <p>{{ $actividad->act_descripcion }}</p>
        @endif
    </div>

    @if(count($filtros) > 0)
        <div class="filtros">
            <strong>Filtros aplicados:</strong> {{ implode(' | ', $filtros) }}
        </div>
    @endif

    @if($tab == 'registros')
        <div class="stats">
            <span>Total Registros: {{ $registros->count() }}</span>
            @php $porCat = $registros->groupBy(fn($r) => $r->categoria->actcat_nombre ?? 'Sin categoría'); @endphp
            @foreach($porCat as $catNombre => $regs)
                <span style="background:#17a2b8;">{{ $catNombre }}: {{ $regs->count() }}</span>
            @endforeach
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:30px;">N°</th>
                    <th>ESTUDIANTE</th>
                    <th style="width:80px;">CURSO</th>
                    <th style="width:80px;">CATEGORÍA</th>
                    <th style="width:45px;">HORA</th>
                    <th>OBSERVACIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $i => $r)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-left">{{ $r->estudiante->est_apellidos ?? '' }} {{ $r->estudiante->est_nombres ?? '' }}</td>
                    <td class="text-center"><span class="badge-curso">{{ $r->estudiante->curso->cur_nombre ?? 'N/A' }}</span></td>
                    <td class="text-center"><span class="badge-cat">{{ $r->categoria->actcat_nombre ?? '' }}</span></td>
                    <td class="text-center">{{ $r->actreg_hora }}</td>
                    <td class="text-left">{{ $r->actreg_observacion }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No hay registros</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div class="stats">
            <span style="background:#dc3545;">Total Faltas: {{ $faltas->count() }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:30px;">N°</th>
                    <th>ESTUDIANTE</th>
                    <th style="width:100px;">CURSO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faltas as $i => $est)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-left">{{ $est->est_apellidos }} {{ $est->est_nombres }}</td>
                    <td class="text-center"><span class="badge-curso">{{ $est->curso->cur_nombre ?? 'N/A' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center">Todos los estudiantes tienen registro</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        <span class="footer-left">Impreso: {{ now()->format('d/m/Y H:i:s') }}</span>
        <span class="footer-right">Actividades - Asistencia | Gestión {{ date('Y') }}</span>
    </div>
</body>
</html>
