<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Kardex Docente — General</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; font-size:10px; padding:8mm 10mm; color:#000; }

/* Encabezado institucional */
.header-table { width:100%; border-collapse:collapse; margin-bottom:6px; }
.header-table td { vertical-align:middle; padding:0; }
.h-logo-cell { width:80px; text-align:center; }
.h-logo-cell img { width:64px; height:auto; }
.h-info-cell { text-align:center; }
.h-info-cell .ue-nombre { font-weight:700; font-size:13px; letter-spacing:0.5px; }
.h-info-cell .ue-dir    { font-size:7.5px; color:#333; line-height:1.3; }
.h-info-cell .titulo-banda { display:inline-block; margin-top:4px; padding:4px 14px; border:1.5px solid #000; font-weight:700; font-size:11px; letter-spacing:1px; }
.h-fecha-cell { width:80px; text-align:center; border:1.5px solid #000; padding:4px; }
.h-fecha-cell .fecha-label { font-size:7px; font-weight:bold; }
.h-fecha-cell .fecha-val   { font-size:9px; font-weight:bold; }

/* Resumen */
.resumen { width:100%; border-collapse:collapse; margin:8px 0 10px; }
.resumen td { border:1.5px solid #000; padding:6px 8px; text-align:center; width:33.33%; }
.resumen .num { font-size:18px; font-weight:bold; }
.resumen .lbl { font-size:8px; text-transform:uppercase; letter-spacing:0.5px; }
.resumen .c-pend { background:#fef5e7; }
.resumen .c-obs  { background:#fadbd8; }
.resumen .c-disc { background:#ebdef0; }

/* Secciones */
.section { margin-top:12px; page-break-inside:avoid; }
.section-title { background:#000; color:#fff; padding:5px 8px; font-weight:bold; font-size:10.5px; letter-spacing:1px; }
table.data { width:100%; border-collapse:collapse; margin-top:4px; }
table.data th, table.data td { border:1px solid #000; padding:3px 5px; font-size:8.5px; }
table.data th { background:#e8e8e8; font-weight:bold; }
.gravedad-LEVE  { background:#fef9e7; color:#9a7d0a; font-weight:bold; padding:1px 4px; border-radius:2px; }
.gravedad-MEDIA { background:#fef5e7; color:#a04000; font-weight:bold; padding:1px 4px; border-radius:2px; }
.gravedad-GRAVE { background:#fadbd8; color:#922b21; font-weight:bold; padding:1px 4px; border-radius:2px; }

.footer { position:fixed; bottom:5mm; left:10mm; right:10mm; font-size:6.5px; color:#888; text-align:center; border-top:0.5px solid #ccc; padding-top:2px; }
</style></head>
<body>

@include('partials.pdf-header-institucional', ['tituloBanda' => 'KARDEX DOCENTE — REPORTE GENERAL'])

<table class="resumen">
    <tr>
        <td class="c-pend"><div class="num">{{ $pendientes->count() }}</div><div class="lbl">Pendientes de entrega</div></td>
        <td class="c-obs"><div class="num">{{ $observados->count() }}</div><div class="lbl">Documentos observados</div></td>
        <td class="c-disc"><div class="num">{{ $disc->count() }}</div><div class="lbl">Incidencias disciplinarias</div></td>
    </tr>
</table>

<div class="section">
    <div class="section-title">PENDIENTES DE ENTREGA ({{ $pendientes->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th>Docente</th>
                <th style="width:80px;">Tipo</th>
                <th>Título</th>
                <th style="width:80px;">Solicitado</th>
                <th style="width:80px;">Entrega pactada</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendientes as $k)
                <tr>
                    <td><strong>{{ optional($k->docente)->doc_apellidos }} {{ optional($k->docente)->doc_nombres }}</strong></td>
                    <td>{{ $k->kdx_tipo_documento }}</td>
                    <td>{{ $k->kdx_titulo }}</td>
                    <td>{{ $k->kdx_fecha_solicitud->format('d/m/Y') }}</td>
                    <td>{{ optional($k->kdx_fecha_entrega)->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#888;">Sin pendientes registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">OBSERVADOS ({{ $observados->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th>Docente</th>
                <th>Documento</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($observados as $k)
                <tr>
                    <td><strong>{{ optional($k->docente)->doc_apellidos }} {{ optional($k->docente)->doc_nombres }}</strong></td>
                    <td>{{ $k->kdx_titulo }} ({{ $k->kdx_tipo_documento }})</td>
                    <td>{{ $k->kdx_observacion }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#888;">Sin observaciones registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">ÚLTIMAS INCIDENCIAS DISCIPLINARIAS</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:75px;">Fecha</th>
                <th>Docente</th>
                <th style="width:90px;">Tipo</th>
                <th style="width:75px;">Gravedad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disc as $d)
                <tr>
                    <td>{{ $d->disc_fecha->format('d/m/Y') }}</td>
                    <td><strong>{{ optional($d->docente)->doc_apellidos }} {{ optional($d->docente)->doc_nombres }}</strong></td>
                    <td>{{ $d->disc_tipo }}</td>
                    <td><span class="gravedad-{{ $d->disc_gravedad }}">{{ $d->disc_gravedad }}</span></td>
                    <td>{{ $d->disc_descripcion }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#888;">Sin incidencias registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    Kardex Docente · Reporte General · Generado el {{ now()->format('d/m/Y H:i') }} · Documento institucional confidencial
</div>
</body></html>
