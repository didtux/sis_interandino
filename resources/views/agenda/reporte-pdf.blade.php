<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agenda institucional</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; padding: 16px; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 65px; vertical-align: middle; }
        .logo img { width: 56px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 9px; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 16px; right: 16px; text-align: right; font-size: 8px; }
        .title-section { text-align: center; margin: 10px 0; }
        .title-section h2 { font-size: 13px; }
        .title-section p { font-size: 9px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px 5px; font-size: 9px; vertical-align: top; word-wrap: break-word; }
        th { background-color: #1c4789; color: #fff; font-weight: bold; text-align: center; }
        td.centro { text-align: center; }
        .dia-row td { background-color: #e8e8e8; font-weight: bold; text-align: left; font-size: 10px; }
        .tipo-evento { background-color: #667eea; color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 8px; }
        .tipo-aviso  { background-color: #ffc107; color: #000; padding: 1px 4px; border-radius: 3px; font-size: 8px; }
        .vacio { text-align: center; padding: 18px; font-style: italic; color: #666; }
        .footer { position: fixed; bottom: 8px; left: 16px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        <div><strong>Fecha</strong></div>
        <div>{{ now()->format('d/m/Y') }}</div>
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
        <h2>AGENDA INSTITUCIONAL</h2>
        <p>{{ $subtitulo }}</p>
    </div>

    @if($porDia->isEmpty())
        <div class="vacio">No hay registros de agenda para el filtro seleccionado.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:10%;">HORA</th>
                    <th style="width:10%;">TIPO</th>
                    <th style="width:26%;">TÍTULO</th>
                    <th style="width:34%;">DETALLE</th>
                    <th style="width:20%;">DIRIGIDO A</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porDia as $dia => $items)
                    <tr class="dia-row">
                        <td colspan="5">
                            {{ $dia === 'sin-fecha'
                                ? 'SIN FECHA'
                                : mb_strtoupper(\Carbon\Carbon::parse($dia)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'), 'UTF-8') }}
                        </td>
                    </tr>
                    @foreach($items as $a)
                        <tr>
                            <td class="centro">{{ $a->age_fechahora ? $a->age_fechahora->format('H:i') : '—' }}</td>
                            <td class="centro">
                                <span class="{{ $a->age_tipo == 1 ? 'tipo-evento' : 'tipo-aviso' }}">
                                    {{ $a->age_tipo == 1 ? 'EVENTO' : 'AVISO' }}
                                </span>
                            </td>
                            <td>{{ $a->age_titulo }}</td>
                            <td>{{ $a->age_detalles }}</td>
                            <td>
                                @if($a->estudiante)
                                    {{ $a->estudiante->est_apellidos }} {{ $a->estudiante->est_nombres }}
                                    <br><small>{{ $a->estudiante->curso->cur_nombre ?? '' }}</small>
                                @elseif($a->curso_codigo)
                                    {{ $cursos[$a->curso_codigo] ?? $a->curso_codigo }}
                                @else
                                    Todo el colegio
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} — Total de registros: {{ $total }}
    </div>
</body>
</html>
