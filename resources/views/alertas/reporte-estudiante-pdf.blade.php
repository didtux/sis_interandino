<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Advertencia — {{ $estudiante->est_apellidos }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;margin:18px;color:#000;}
.banda-titulo{text-align:center;background:#000;color:#fff;padding:8px 0;font-size:22px;font-weight:700;letter-spacing:3px;margin-bottom:4px;}
.subtitulo{text-align:center;background:#e91e63;color:#fff;padding:5px 0;font-size:13px;font-weight:700;letter-spacing:1px;margin-bottom:14px;}
.info-est{display:table;width:100%;border:1px solid #000;margin-bottom:10px;}
.info-est .col{display:table-cell;padding:6px 8px;}
.info-est .col-meta{width:35%;border-left:1px solid #000;text-align:center;}
.info-est strong{font-size:11px;}

.parrafo{font-size:10.5px;line-height:1.5;margin-bottom:10px;text-align:justify;}
.materias-grid{display:table;width:100%;border:1.5px solid #000;margin-bottom:10px;}
.materias-grid .col{display:table-cell;padding:8px 10px;border-right:1px solid #000;font-weight:bold;text-transform:uppercase;font-size:12px;}
.materias-grid .col:last-child{border-right:none;}
.aviso{padding:8px 10px;background:#fef5f5;border:1px solid #e91e63;font-size:10px;margin-bottom:14px;}
.firma{margin-top:50px;text-align:right;font-size:10px;}
.firma .line{display:inline-block;border-top:1px solid #000;padding-top:2px;min-width:200px;text-align:center;}
.foot{margin-top:14px;font-size:8px;color:#666;text-align:right;}
</style></head>
<body>
<div class="banda-titulo">A D V E R T E N C I A</div>
<div class="subtitulo">PARCIAL — {{ mb_strtoupper($periodo->periodo_nombre ?? '', 'UTF-8') }}</div>

<div class="info-est">
    <div class="col">
        <strong>Estudiante:</strong> {{ mb_strtoupper($estudiante->est_apellidos.' '.$estudiante->est_nombres, 'UTF-8') }}<br>
        <strong>Curso:</strong> {{ optional($estudiante->curso)->cur_nombre ?? '-' }}
    </div>
    <div class="col col-meta">
        <strong>CURSO</strong><br>{{ optional($estudiante->curso)->cur_nombre ?? '-' }}
    </div>
</div>

<div class="parrafo">
    Señor Padre de familia: Concluidas las evaluaciones parciales su hijo(a) a la fecha
    <strong>TIENE NOTA DE REPROBACIÓN</strong> en las siguientes materias <em>(tomar en cuenta
    solamente lo resaltado)</em>:
</div>

@if($alertas->isEmpty())
    <div class="parrafo" style="color:#27ae60;font-weight:bold;font-size:14px;text-align:center;">FELICITACIONES — Sin observaciones.</div>
@else
    <div class="materias-grid">
        @foreach($alertas->chunk(3) as $grupo)
            <div class="row">
                @foreach($grupo as $a)
                    <div class="col">{{ mb_strtoupper(optional($a->materia)->mat_nombre ?? $a->mat_codigo, 'UTF-8') }}</div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif

<div class="aviso">
    Se le indica que debe apersonarse en el lapso de <strong>24 horas</strong> para entrevistarse con el Dpto. de Control y
    Seguimiento, caso contrario la Unidad Educativa no se responsabiliza por los resultados que puedan existir a <strong>FIN DE AÑO</strong>.
</div>

<div class="firma">
    <div class="line">FIRMA SR. PP.FF.</div>
</div>

<div class="foot">Generado: {{ now()->format('d/m/Y H:i') }}</div>
</body></html>
