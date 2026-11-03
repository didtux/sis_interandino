<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mora de transporte</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #222; }
        .cab { width: 100%; border-bottom: 2px solid #1a3a6c; margin-bottom: 8px; }
        .cab h1 { font-size: 13px; margin: 0; color: #1a3a6c; }
        .cab .sub { font-size: 9px; color: #666; }
        table.t { width: 100%; border-collapse: collapse; }
        table.t th { background: #1a3a6c; color: #fff; padding: 4px; text-align: left; font-size: 9px; }
        table.t td { border-bottom: 1px solid #ddd; padding: 3px 4px; }
        .curso td { background: #e8eef7; font-weight: bold; color: #1a3a6c; }
        .r { text-align: right; } .c { text-align: center; }
        .tot td { font-weight: bold; border-top: 2px solid #1a3a6c; }
    </style>
</head>
<body>
@php use App\Services\TransporteMoraService as TM; @endphp
<table class="cab">
    <tr><td>
        <h1>MORA DE TRANSPORTE ESCOLAR{{ $titulo ? ' — ' . mb_strtoupper($titulo) : '' }}</h1>
        <div class="sub">Gestión {{ gestion_actual() }} · meses de {{ TM::MESES[TM::PRIMER_MES] }} a {{ TM::MESES[$mesCorte - 1] }} · {{ now()->format('d/m/Y H:i') }} · deuda estimada con la última cuota cobrada</div>
    </td></tr>
</table>
<table class="t">
    <thead><tr><th>N°</th><th>Estudiante</th><th>Ruta</th><th>Bus</th><th>Meses en mora</th><th class="c">Cant.</th><th class="r">Deuda est. (Bs)</th></tr></thead>
    <tbody>
    @php $n = 0; @endphp
    @forelse($filas->groupBy('curso') as $curso => $grupo)
        <tr class="curso"><td colspan="7">{{ $curso }} ({{ $grupo->count() }})</td></tr>
        @foreach($grupo as $f)
            <tr>
                <td>{{ ++$n }}</td><td>{{ $f->estudiante }}</td><td>{{ $f->ruta }}</td><td>{{ $f->bus }}</td>
                <td>{{ $f->meses_texto }}</td><td class="c">{{ count($f->meses) }}</td>
                <td class="r">{{ $f->deuda_estimada !== null ? number_format($f->deuda_estimada, 2) : '—' }}</td>
            </tr>
        @endforeach
    @empty
        <tr><td colspan="7" class="c">No hay estudiantes en mora.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr class="tot"><td colspan="5">TOTAL: {{ $filas->count() }} estudiantes</td><td class="c">{{ $filas->sum(fn($f) => count($f->meses)) }}</td><td class="r">{{ number_format($filas->sum('deuda_estimada'), 2) }}</td></tr>
    </tfoot>
</table>
</body>
</html>
