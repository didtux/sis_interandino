<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 14px; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .logo img { width: 52px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 10px; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 14px; right: 14px; text-align: right; font-size: 8px; }
        .title-section { text-align: center; margin: 8px 0 10px; }
        .title-section h2 { font-size: 12px; }
        .title-section p { font-size: 9px; margin-top: 2px; }
        .curso { background: #1c4789; color: #fff; font-weight: bold; padding: 4px 6px; font-size: 10px; margin-top: 10px; }
        .curso small { font-weight: normal; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #999; padding: 3px 5px; font-size: 9px; }
        th { background: #e8eef7; text-align: center; }
        td.c { text-align: center; }
        .retirado { color: #c0392b; }
        .vacio { text-align: center; color: #666; margin-top: 30px; font-size: 10px; }
        .footer { position: fixed; bottom: 8px; left: 14px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box"><div><strong>Fecha</strong></div><div>{{ now()->format('d/m/Y') }}</div></div>
    <div class="header">
        <div class="logo">@if(file_exists(public_path('img/logo.png')))<img src="{{ public_path('img/logo.png') }}" alt="Logo">@endif</div>
        <div class="header-info">
            <h3>UNIDAD EDUCATIVA PRIVADA</h3>
            <h3>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
        </div>
    </div>
    <div class="title-section">
        <h2>{{ $titulo }}</h2>
        <p>{{ $subtitulo }}</p>
    </div>

    @forelse($secciones as $s)
        <div class="curso">{{ $s['curso'] }} <small>— {{ $s['total'] }} estudiante{{ $s['total'] > 1 ? 's' : '' }} llega{{ $s['total'] > 1 ? 'n' : '' }} al mínimo{{ $s['total'] > count($s['filas']) ? ', se muestran ' . count($s['filas']) : '' }}</small></div>
        <table>
            <thead>
                <tr><th style="width:6%">N°</th><th>Estudiante</th><th style="width:14%">Licencias</th><th style="width:16%">Días hábiles</th><th style="width:16%">Última licencia</th></tr>
            </thead>
            <tbody>
                @foreach($s['filas'] as $i => $f)
                    <tr class="{{ $f['retirado'] ? 'retirado' : '' }}">
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $f['nombre'] }}{{ $f['retirado'] ? ' (RETIRADO)' : '' }}</td>
                        <td class="c"><strong>{{ $f['licencias'] }}</strong></td>
                        <td class="c">{{ $f['dias'] }}</td>
                        <td class="c">{{ $f['ultima'] ? \Carbon\Carbon::parse($f['ultima'])->format('d/m/Y') : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div class="vacio">Ningún estudiante llega al mínimo de licencias en este período.</div>
    @endforelse

    <div class="footer">Impreso: {{ now()->format('d/m/Y H:i:s') }} — Licencias = permisos registrados; días hábiles = lunes a viernes cubiertos por esos permisos.</div>
</body>
</html>
