<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Kardex {{ $docente->doc_apellidos }}</title>
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

/* Sub-info docente */
.docente-info { width:100%; border:1.5px solid #000; border-collapse:collapse; margin:6px 0 10px; }
.docente-info td { padding:4px 8px; font-size:9.5px; }
.docente-info .label { font-weight:bold; background:#f0f0f0; width:18%; font-size:8px; }

/* Secciones */
.section { margin-top:12px; page-break-inside:avoid; }
.section-title { background:#000; color:#fff; padding:5px 8px; font-weight:bold; font-size:10.5px; letter-spacing:1px; }
table.data { width:100%; border-collapse:collapse; margin-top:4px; }
table.data th, table.data td { border:1px solid #000; padding:3px 5px; font-size:8.5px; }
table.data th { background:#e8e8e8; font-weight:bold; }
.tag { display:inline-block; padding:1px 5px; background:#2c3e50; color:#fff; border-radius:2px; font-size:7.5px; font-weight:bold; }
.estado-PENDIENTE  { color:#d35400; font-weight:bold; }
.estado-ENTREGADO  { color:#27ae60; font-weight:bold; }
.estado-OBSERVADO  { color:#c0392b; font-weight:bold; }
.estado-RECHAZADO  { color:#7f8c8d; font-weight:bold; }

.footer { position:fixed; bottom:5mm; left:10mm; right:10mm; font-size:6.5px; color:#888; text-align:center; border-top:0.5px solid #ccc; padding-top:2px; }
</style></head>
<body>

@include('partials.pdf-header-institucional', ['tituloBanda' => 'KARDEX DEL DOCENTE'])

<table class="docente-info">
    <tr>
        <td class="label">APELLIDOS Y NOMBRES</td>
        <td style="font-weight:bold;font-size:11px;">{{ mb_strtoupper($docente->doc_apellidos.' '.$docente->doc_nombres, 'UTF-8') }}</td>
        <td class="label">CÓDIGO</td>
        <td>{{ $docente->doc_codigo }}</td>
    </tr>
    @if(!empty($docente->doc_ci) || !empty($docente->doc_telefono))
    <tr>
        <td class="label">C.I.</td>
        <td>{{ $docente->doc_ci ?? '—' }}</td>
        <td class="label">TELÉFONO</td>
        <td>{{ $docente->doc_telefono ?? '—' }}</td>
    </tr>
    @endif
</table>

<div class="section">
    <div class="section-title">DOCUMENTOS</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:75px;">Tipo</th>
                <th>Título</th>
                <th style="width:65px;">Solicitado</th>
                <th style="width:65px;">Entrega</th>
                <th style="width:65px;">Recibido</th>
                <th style="width:75px;">Estado</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kardex as $k)
                <tr>
                    <td><span class="tag">{{ $k->kdx_tipo_documento }}</span></td>
                    <td>{{ $k->kdx_titulo }}</td>
                    <td>{{ $k->kdx_fecha_solicitud->format('d/m/Y') }}</td>
                    <td>{{ optional($k->kdx_fecha_entrega)->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ optional($k->kdx_fecha_recibido)->format('d/m/Y') ?? '-' }}</td>
                    <td><span class="estado-{{ $k->kdx_estado }}">{{ $k->kdx_estado }}</span></td>
                    <td>{{ $k->kdx_observacion }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:#888;">Sin documentos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">ASISTENCIA</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:75px;">Fecha</th>
                <th style="width:60px;">Hora</th>
                <th style="width:80px;">Tipo</th>
                <th style="width:80px;">Origen</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asistencias as $a)
                <tr>
                    <td>{{ $a->dasist_fecha->format('d/m/Y') }}</td>
                    <td>{{ substr($a->dasist_hora, 0, 5) }}</td>
                    <td>{{ $a->dasist_tipo }}</td>
                    <td>{{ $a->dasist_origen }}</td>
                    <td>{{ $a->dasist_observacion }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#888;">Sin asistencias registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">INCIDENCIAS DISCIPLINARIAS</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:75px;">Fecha</th>
                <th style="width:90px;">Tipo</th>
                <th style="width:80px;">Gravedad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disciplinarios as $d)
                <tr>
                    <td>{{ $d->disc_fecha->format('d/m/Y') }}</td>
                    <td>{{ $d->disc_tipo }}</td>
                    <td><strong>{{ $d->disc_gravedad }}</strong></td>
                    <td>{{ $d->disc_descripcion }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#888;">Sin incidencias registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    Kardex Docente · Generado el {{ now()->format('d/m/Y H:i') }} · Documento institucional confidencial
</div>
</body></html>
