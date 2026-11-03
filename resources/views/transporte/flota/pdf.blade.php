<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Choferes y buses</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #222; }
        .cab { width: 100%; border-bottom: 2px solid #1a3a6c; margin-bottom: 8px; }
        .cab td { vertical-align: middle; }
        .cab h1 { font-size: 14px; margin: 0; color: #1a3a6c; }
        .cab .sub { font-size: 9px; color: #666; }
        table.t { width: 100%; border-collapse: collapse; }
        table.t th { background: #1a3a6c; color: #fff; padding: 4px 5px; text-align: left; font-size: 9px; }
        table.t td { border-bottom: 1px solid #ddd; padding: 4px 5px; }
        table.t tr:nth-child(even) td { background: #f5f7fa; }
        .c { text-align: center; }
        .bus { font-weight: bold; font-size: 11px; }
        h3 { font-size: 10.5px; margin: 12px 0 4px; color: #1a3a6c; }
        .pie { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 8px; color: #888; text-align: right; }
    </style>
</head>
<body>
@php $logo = optional($sistemaConfig ?? null)->config_logo ? public_path('storage/' . $sistemaConfig->config_logo) : public_path('img/logo.png'); @endphp
<table class="cab">
    <tr>
        <td style="width:50px">@if(is_file($logo))<img src="{{ $logo }}" style="height:42px">@endif</td>
        <td>
            <h1>CHOFERES Y BUSES DEL TRANSPORTE ESCOLAR</h1>
            <div class="sub">{{ optional($sistemaConfig ?? null)->config_nombre_ue ?: 'Unidad Educativa' }} · Gestión {{ gestion_actual() }} · {{ now()->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

<table class="t">
    <thead>
        <tr><th>Bus</th><th>Placa</th><th>Vehículo</th><th class="c">Capac.</th><th>Chofer</th><th>CI</th><th>Licencia</th><th>Teléfono</th><th>Ruta</th><th class="c">Estud.</th></tr>
    </thead>
    <tbody>
    @forelse($asignaciones as $a)
        @php $v = $a->vehiculo; $c = $a->chofer; @endphp
        <tr>
            <td class="bus">{{ optional($v)->veh_numero_bus ?: '—' }}</td>
            <td>{{ optional($v)->veh_placa }}</td>
            <td>{{ trim(optional($v)->veh_marca . ' ' . optional($v)->veh_modelo) }}</td>
            <td class="c">{{ optional($v)->veh_capacidad }}</td>
            <td>{{ $c ? $c->chof_apellidos . ' ' . $c->chof_nombres : '—' }}</td>
            <td>{{ optional($c)->chof_ci }}</td>
            <td>{{ optional($c)->chof_licencia }}</td>
            <td>{{ optional($c)->chof_telefono }}</td>
            <td>{{ optional($a->ruta)->ruta_nombre }}</td>
            <td class="c">{{ $estudiantesPorRuta[$a->ruta_codigo] ?? 0 }}</td>
        </tr>
    @empty
        <tr><td colspan="10" class="c">No hay asignaciones activas.</td></tr>
    @endforelse
    </tbody>
</table>

@if($choferesSinBus->isNotEmpty())
<h3>Choferes sin bus asignado</h3>
<table class="t">
    <thead><tr><th>Chofer</th><th>CI</th><th>Licencia</th><th>Teléfono</th></tr></thead>
    <tbody>
    @foreach($choferesSinBus as $c)
        <tr><td>{{ $c->chof_apellidos }} {{ $c->chof_nombres }}</td><td>{{ $c->chof_ci }}</td><td>{{ $c->chof_licencia }}</td><td>{{ $c->chof_telefono }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

@if($busesSinChofer->isNotEmpty())
<h3>Buses sin chofer asignado</h3>
<table class="t">
    <thead><tr><th>Bus</th><th>Placa</th><th>Vehículo</th><th class="c">Capac.</th></tr></thead>
    <tbody>
    @foreach($busesSinChofer as $v)
        <tr><td class="bus">{{ $v->veh_numero_bus ?: '—' }}</td><td>{{ $v->veh_placa }}</td><td>{{ trim($v->veh_marca . ' ' . $v->veh_modelo) }}</td><td class="c">{{ $v->veh_capacidad }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="pie">Total: {{ $asignaciones->count() }} buses en servicio</div>
</body>
</html>
