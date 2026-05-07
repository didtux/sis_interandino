@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h4><i class="fas fa-chart-line mr-2"></i>Rendimiento / Notas</h4>
            <div class="d-flex flex-wrap" style="gap:6px;">
                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modalCuadroHonor"><i class="fas fa-medal mr-1"></i>Cuadro de Honor</button>
                <a href="{{ route('notas.top3-cursos', ['gestion'=>date('Y')]) }}" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-trophy mr-1"></i>Top 3 por Curso</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <div class="col-md-3 mb-2">
                    <label>Trimestre</label>
                    <select name="periodo_id" class="form-control" required>
                        @foreach($periodos as $p)
                            <option value="{{ $p->periodo_id }}" {{ $periodoId == $p->periodo_id ? 'selected' : '' }}>
                                {{ $p->periodo_numero }}° Trimestre
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label>Curso</label>
                    <select name="cur_codigo" class="form-control select2-curso" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $cursoCod == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Buscar estudiante</label>
                    <input type="text" name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Nombre o apellido">
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>

            @if($cursoCod && $periodoId)
                @if($materias->isEmpty())
                    <div class="alert alert-warning">No hay materias asignadas a este curso. Configure desde Cursos → Asignar Materias.</div>
                @elseif($estudiantes->isEmpty())
                    <div class="alert alert-info">No hay estudiantes en este curso.</div>
                @else
                    <div class="table-responsive-modern" style="max-height:70vh;overflow:auto;">
                        <table class="modern-table" style="font-size:12px;">
                            <thead style="position:sticky;top:0;z-index:2;background:#1c4789;color:#fff;">
                                <tr>
                                    <th style="min-width:30px;">#</th>
                                    <th style="min-width:220px;">Estudiante</th>
                                    @foreach($materias as $m)
                                        <th title="{{ $m->mat_nombre }}" style="white-space:nowrap;">{{ $m->mat_abreviatura ?: $m->mat_nombre }}</th>
                                    @endforeach
                                    <th>Prom.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estudiantes as $e)
                                    @php
                                        $retirado = $e->est_visible == 0;
                                        $valores = [];
                                        foreach ($materias as $m) {
                                            $v = $matriz[$e->est_codigo][$m->mat_codigo]['promedio'] ?? null;
                                            if ($v !== null) $valores[] = $v;
                                        }
                                        $prom = count($valores) ? round(array_sum($valores)/count($valores)) : null;
                                    @endphp
                                    <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                                        <td>{{ $e->lista_numero ?? '-' }}</td>
                                        <td style="{{ $retirado ? 'color:#c0392b;font-weight:600;' : '' }}">
                                            {{ $e->est_apellidos }} {{ $e->est_nombres }}
                                            @if($retirado)<span class="modern-badge badge-danger-modern ml-1">RET</span>@endif
                                        </td>
                                        @foreach($materias as $m)
                                            @php
                                                $cell = $matriz[$e->est_codigo][$m->mat_codigo] ?? null;
                                                $val  = $cell['promedio'] ?? null;
                                                $rep  = $val !== null && round($val) < 51;
                                            @endphp
                                            <td class="text-center" style="{{ $rep ? 'color:#c0392b;font-weight:700;' : '' }}">
                                                @if($cell)
                                                    <a href="{{ route('notas.calificar', [$cell['curmatdoc_id'], $periodoId]) }}" style="text-decoration:none;color:inherit;">
                                                        {{ $val !== null ? round($val) : '-' }}
                                                    </a>
                                                @else
                                                    @php
                                                        $asig = $asignaciones->firstWhere('mat_codigo', $m->mat_codigo);
                                                    @endphp
                                                    @if($asig)
                                                        <a href="{{ route('notas.calificar', [$asig->curmatdoc_id, $periodoId]) }}" class="text-muted">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center" style="font-weight:700;{{ $prom !== null && $prom < 51 ? 'color:#c0392b;' : '' }}">
                                            {{ $prom !== null ? $prom : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <div class="alert alert-info">Seleccione trimestre y curso para ver el rendimiento.</div>
            @endif
        </div>
    </div>
</div>


{{-- ── Modal Cuadro de Honor ──────────────────────────── --}}
<div class="modal fade" id="modalCuadroHonor" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#f39c12;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-medal mr-2"></i>Cuadro de Honor</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Alcance</label>
                    <select id="chTipo" class="form-control">
                        <option value="curso">Por curso</option>
                        <option value="nivel">Por nivel</option>
                        <option value="colegio">Toda la institución</option>
                    </select>
                </div>
                <div class="form-group" id="chCursoWrap">
                    <label>Curso</label>
                    <select id="chCurso" class="form-control select2-curso-modal" style="width:100%;">
                        <option value="">— Seleccionar —</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" data-nivel="{{ $c->cur_nivel }}">{{ $c->cur_nombre }} @if($c->cur_nivel) — {{ $c->cur_nivel }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="chNivelWrap" style="display:none;">
                    <label>Nivel</label>
                    <select id="chNivel" class="form-control" style="width:100%;">
                        <option value="">— Seleccionar —</option>
                        <option value="INICIAL">INICIAL</option>
                        <option value="PRIMARIA">PRIMARIA</option>
                        <option value="SECUNDARIA">SECUNDARIA</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trimestre</label>
                    <select id="chTrimestre" class="form-control">
                        <option value="">Todos los trimestres (anual)</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p->periodo_id }}">{{ $p->periodo_nombre ?? $p->periodo_numero.'° Trimestre' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Gestión</label>
                    <input type="number" id="chGestion" class="form-control" value="{{ date('Y') }}" min="2020" max="2099">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btnGenerarCH"><i class="fas fa-file-pdf mr-1"></i>Generar PDF</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function(){
    $('.select2-curso').select2({ theme:'bootstrap4', width:'100%' });
    $('.select2-curso-modal').select2({ theme:'bootstrap4', width:'100%', dropdownParent: $('#modalCuadroHonor') });

    function actualizarCamposCH(){
        var tipo = $('#chTipo').val();
        $('#chCursoWrap').toggle(tipo === 'curso');
        $('#chNivelWrap').toggle(tipo === 'nivel');
    }
    $('#chTipo').on('change', actualizarCamposCH);
    actualizarCamposCH();

    $('#btnGenerarCH').on('click', function(){
        var tipo = $('#chTipo').val();
        var gestion = $('#chGestion').val() || '{{ date("Y") }}';
        var trimestre = $('#chTrimestre').val();
        var url = '{{ route("notas.cuadro-honor") }}?tipo=' + encodeURIComponent(tipo) + '&gestion=' + encodeURIComponent(gestion);
        if (trimestre) url += '&periodo_id=' + encodeURIComponent(trimestre);
        if (tipo === 'curso') {
            var curso = $('#chCurso').val();
            if (!curso) { alert('Seleccione un curso'); return; }
            url += '&curso=' + encodeURIComponent(curso);
        } else if (tipo === 'nivel') {
            var nivel = $('#chNivel').val();
            if (!nivel) { alert('Seleccione un nivel'); return; }
            url += '&nivel=' + encodeURIComponent(nivel);
        }
        window.open(url, '_blank');
        $('#modalCuadroHonor').modal('hide');
    });
});
</script>
@endsection
