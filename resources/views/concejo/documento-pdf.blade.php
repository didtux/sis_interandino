<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Documento Concejo</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:22px;}
.header{text-align:center;margin-bottom:10px;}
.header h2{margin:2px 0;}
.datos{display:flex;justify-content:space-between;margin:8px 0;border:1px solid #888;padding:6px;}
.foto{width:80px;height:90px;object-fit:cover;border:1px solid #888;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #444;padding:5px;}
th{background:#1c4789;color:#fff;}
.num{text-align:center;width:55px;}
.rep{color:#c0392b;font-weight:bold;}
.aprob{color:#2c8c2c;font-weight:bold;}
.totales{margin-top:10px;border:1px solid #444;}
.totales td{padding:5px 8px;}
</style></head><body>
<div class="header">
    @if($config && $config->config_logo)<img src="{{ public_path('storage/'.$config->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $config->config_denominacion ?? 'UNIDAD EDUCATIVA' }} {{ $config->config_nombre_ue ?? '' }}</h2>
    <h3>DOCUMENTO PARA CONCEJO EDUCATIVO — Gestión {{ $gestion }}</h3>
    <div>{{ date('d/m/Y H:i') }} — Control-Cole</div>
</div>
<table style="width:100%;border:1px solid #888;margin:8px 0;border-collapse:collapse;">
    <tr>
        {{-- Lado izquierdo: foto + datos del estudiante --}}
        <td style="vertical-align:top;padding:6px;border:none;width:15%;text-align:center;">
            @if($estudiante->est_foto)
                <img src="{{ public_path('storage/'.$estudiante->est_foto) }}" class="foto">
            @endif
        </td>
        <td style="vertical-align:top;padding:6px;border:none;width:42%;">
            <b>Estudiante:</b> {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}<br>
            <b>Grado:</b> {{ optional($estudiante->curso)->cur_nombre ?? '-' }}<br>
            <b>U.E. de procedencia:</b> {{ $estudiante->est_ueprocedencia ?? '-' }}<br>
            <b>CI:</b> {{ $estudiante->est_ci ?? '-' }}
        </td>
        {{-- Lado derecho: datos de los padres --}}
        <td style="vertical-align:top;padding:6px;border:none;border-left:1px solid #ccc;width:43%;">
            <b>Padres / Tutores:</b><br>
            @forelse($estudiante->padres as $pf)
                @php
                    $padreFoto = !empty($pf->pfam_foto) && file_exists(public_path('storage/'.$pf->pfam_foto))
                        ? public_path('storage/'.$pf->pfam_foto) : null;
                @endphp
                <div style="margin-bottom:4px;">
                    @if($padreFoto)
                        <img src="{{ $padreFoto }}" style="width:30px;height:30px;object-fit:cover;border:1px solid #888;border-radius:50%;vertical-align:middle;margin-right:4px;">
                    @endif
                    {{ $pf->pfam_nombres ?? '-' }} {{ $pf->pfam_apellidos ?? '' }}
                    @if(!empty($pf->pfam_parentesco)) <span style="color:#555;">({{ $pf->pfam_parentesco }})</span>@endif
                    @if(!empty($pf->pfam_numeroscelular)) — {{ $pf->pfam_numeroscelular }}@endif
                </div>
            @empty
                <span style="color:#888;">Sin registros</span>
            @endforelse
        </td>
    </tr>
</table>

<table class="totales">
    <tr>
        <td><b>Atrasos:</b> {{ $atrasos }}</td>
        <td><b>Faltas:</b> {{ $faltas }}</td>
        <td><b>Licencias:</b> {{ $licencias }} d. / {{ $permisosSolicitudes ?? 0 }} sol.</td>
        <td><b>Enfermería:</b> {{ $enfermeria }}</td>
        <td><b>Compromisos Verbales:</b> {{ $compromisosVerb }}</td>
        <td><b>Compromisos Escritos:</b> {{ $compromisosEscrit }}</td>
    </tr>
</table>

@if(!empty($resumenPorTrim))
<table style="width:100%;border-collapse:collapse;margin-top:6px;font-size:9px;">
    <thead>
        <tr style="background:#000;color:#fff;">
            <th style="border:1px solid #000;padding:3px;">TRIMESTRE</th>
            <th style="border:1px solid #000;padding:3px;">RANGO</th>
            <th style="border:1px solid #000;padding:3px;">DT</th>
            <th style="border:1px solid #000;padding:3px;">PRES.</th>
            <th style="border:1px solid #000;padding:3px;">FALTAS</th>
            <th style="border:1px solid #000;padding:3px;">ATRASOS</th>
            <th style="border:1px solid #000;padding:3px;">LIC. (d/sol)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($resumenPorTrim as $pn => $r)
            <tr>
                <td style="border:1px solid #000;padding:3px;text-align:center;font-weight:bold;">T{{ $pn }}</td>
                <td style="border:1px solid #000;padding:3px;font-size:8px;">
                    {{ \Carbon\Carbon::parse($r['rango']['inicio'])->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($r['rango']['fin'])->format('d/m/Y') }}
                </td>
                @if(($r['visible'] ?? true))
                    <td style="border:1px solid #000;padding:3px;text-align:center;">{{ $r['dias_trabajados_curso'] }}</td>
                    <td style="border:1px solid #000;padding:3px;text-align:center;">{{ $r['presencias'] }}</td>
                    <td style="border:1px solid #000;padding:3px;text-align:center;color:#c0392b;font-weight:bold;">{{ $r['faltas'] }}</td>
                    <td style="border:1px solid #000;padding:3px;text-align:center;color:#d35400;font-weight:bold;">{{ $r['atrasos'] }}</td>
                    <td style="border:1px solid #000;padding:3px;text-align:center;">{{ $r['licencias_dias'] }} / {{ $r['licencias_solicitudes'] }}</td>
                @else
                    <td colspan="5" style="border:1px solid #000;padding:3px;text-align:center;color:#888;font-style:italic;">
                        Trimestre en curso — sin notas aprobadas
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
@endif

<table>
    <thead>
        <tr>
            <th>Materia</th>
            @foreach($periodos as $p)<th class="num">T{{ $p->periodo_numero }}</th>@endforeach
            <th class="num">Promedio</th>
            <th class="num">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriz as $matCod => $m)
            @php
                $vals = []; foreach ($periodos as $p) { if (isset($m['per'][$p->periodo_id])) $vals[] = $m['per'][$p->periodo_id]; }
                $prom = count($vals) ? round(array_sum($vals)/count($vals)) : null;
                $aprob = $prom !== null && $prom >= 51;
            @endphp
            <tr>
                <td>{{ $m['nombre'] }}</td>
                @foreach($periodos as $p)
                    @php $v = $m['per'][$p->periodo_id] ?? null; @endphp
                    <td class="num {{ $v !== null && $v < 51 ? 'rep' : '' }}">{{ $v ?? '-' }}</td>
                @endforeach
                <td class="num {{ $prom !== null && $prom < 51 ? 'rep' : '' }}"><b>{{ $prom ?? '-' }}</b></td>
                <td class="num {{ $aprob ? 'aprob' : 'rep' }}">{{ $prom === null ? '-' : ($aprob ? 'APROBADO' : 'REPROBADO') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body></html>
