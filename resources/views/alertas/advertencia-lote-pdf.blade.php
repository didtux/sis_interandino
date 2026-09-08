<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Advertencia Parcial — {{ $curso->cur_nombre ?? '' }}</title>
<style>
    * { box-sizing:border-box; }
    body { font-family:Arial,Helvetica,sans-serif; font-size:9px; margin:10px 14px; color:#000; }
    .slip { border:1.5px solid #000; padding:8px 10px; margin-bottom:10px; page-break-inside:avoid; }
    .slip-head { display:table; width:100%; }
    .slip-head .tit { display:table-cell; vertical-align:middle; }
    .slip-head .box { display:table-cell; width:170px; vertical-align:top; text-align:right; }
    .t-adv { font-size:26px; font-weight:800; letter-spacing:5px; line-height:1; }
    .t-sub { font-size:12px; font-weight:800; letter-spacing:1px; margin-top:2px; }
    .cajas { display:inline-table; border-collapse:collapse; }
    .cajas td { border:1.5px solid #000; text-align:center; font-size:8px; padding:2px 6px; vertical-align:top; }
    .cajas .v { font-size:13px; font-weight:bold; padding-top:3px; }
    .parrafo { margin:6px 0; line-height:1.35; text-align:justify; }
    .parrafo .neg { font-weight:bold; }
    .parrafo .it { font-style:italic; font-size:8px; }
    table.mat { width:100%; border-collapse:collapse; margin:4px 0 6px; }
    table.mat td { border:1px solid #777; padding:3px 4px; font-size:8.5px; font-weight:bold; text-transform:uppercase; width:20%; height:16px; }
    td.rep-doc { background:#f6a623; color:#000; }      /* naranja = docente */
    td.rep-dir { background:#f48fb1; color:#000; }       /* rosa = dirección */
    .aviso { border:1px solid #000; padding:5px 7px; font-size:8.5px; line-height:1.3; margin-bottom:4px; }
    .firma { text-align:right; margin-top:34px; font-size:9px; }
    .firma .l { display:inline-block; border-top:1px solid #000; min-width:200px; text-align:center; padding-top:1px; }
    .leyenda { font-size:7.5px; margin-top:3px; }
    .leyenda span { padding:1px 5px; font-weight:bold; }
</style>
</head>
<body>
@php
    // 5 columnas para la grilla de materias (como el formato físico)
    $cols = 5;
    $matChunks = $materias->chunk($cols);
@endphp

@foreach($estudiantes as $est)
    @php
        $marcas = $marcasPorEst[$est->est_codigo] ?? [];
        $nlista = $lista[$est->est_codigo] ?? '';
    @endphp
    <div class="slip">
        <div class="slip-head">
            <div class="tit">
                <div class="t-adv">A D V E R T E N C I A</div>
                <div class="t-sub">PARCIAL — {{ mb_strtoupper($periodo->periodo_nombre ?? '', 'UTF-8') }}</div>
            </div>
            <div class="box">
                <table class="cajas">
                    <tr><td>CURSO</td><td>N° LISTA</td></tr>
                    <tr><td class="v">{{ mb_strtoupper($curso->cur_nombre ?? '', 'UTF-8') }}</td><td class="v">{{ $nlista }}</td></tr>
                </table>
            </div>
        </div>

        <div class="parrafo">
            Señor Padre de familia: Concluidas las evaluaciones parciales su hija(o)
            <strong>{{ mb_strtoupper(trim($est->est_apellidos.' '.$est->est_nombres), 'UTF-8') }}</strong>
            a la fecha <span class="neg">TIENE NOTA DE REPROBACIÓN</span> en las siguientes materias:
            <span class="it">(TOMAR EN CUENTA SOLAMENTE LO RESALTADO)</span>
        </div>

        <table class="mat">
            @foreach($matChunks as $chunk)
                <tr>
                    @foreach($chunk as $m)
                        @php $tipo = $marcas[$m->mat_codigo] ?? null; @endphp
                        <td class="{{ $tipo === 'director' ? 'rep-dir' : ($tipo === 'docente' ? 'rep-doc' : '') }}">
                            {{ mb_strtoupper($m->mat_nombre, 'UTF-8') }}
                        </td>
                    @endforeach
                    @for($i = $chunk->count(); $i < $cols; $i++)<td></td>@endfor
                </tr>
            @endforeach
        </table>

        <div class="aviso">
            Se le indica que debe apersonarse en el lapso de <strong>24 horas</strong> para entrevistarse con el
            Dpto. de Control y Seguimiento, caso contrario la Unidad Educativa no se responsabiliza por los
            resultados que puedan existir a <strong>FIN DE AÑO</strong>.
        </div>

        <div class="firma"><span class="l">FIRMA SR. PP.FF.</span></div>
    </div>
@endforeach
</body>
</html>
