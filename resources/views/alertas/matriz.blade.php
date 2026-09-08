@extends('layouts.app')

@section('content')
<style>
    .adv-table { border-collapse:collapse; width:100%; font-size:12px; }
    .adv-table th, .adv-table td { border:1px solid #cfd6e4; padding:2px; text-align:center; }
    .adv-table thead th { background:#2c3e50; color:#fff; vertical-align:bottom; }
    .adv-table th.mat { height:120px; white-space:nowrap; }
    .adv-table th.mat > div { transform:rotate(-90deg); width:18px; font-size:10.5px; font-weight:600; }
    .adv-table td.nombre { text-align:left; white-space:nowrap; font-weight:600; padding-left:6px; }
    .adv-table td.num { font-weight:bold; background:#f8f9fa; }
    .cell { cursor:pointer; min-width:30px; height:28px; font-size:10px; user-select:none; }
    .cell.lock { cursor:not-allowed; opacity:.55; }
    .cell.dir { background:#f48fb1; }       /* rosa = dirección */
    .cell.doc { background:#f6a623; }        /* naranja = docente */
    .cell.sug { background:#fff3cd; }        /* amarillo = sugerido (nota<51) */
    .cell .nota { display:block; font-size:9px; color:#555; }
    .cell.dir .nota, .cell.doc .nota { color:#000; }
    .leyenda span { padding:2px 8px; border-radius:3px; font-weight:bold; margin-right:8px; font-size:12px; }
</style>

<div class="section-body">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-triangle-exclamation mr-2"></i>Advertencia Parcial — Reprobaciones</h4>
            <small class="text-muted">Marca las materias reprobadas. {{ ($datos['esDireccion'] ?? false) ? 'Tus marcas son ROSA (dirección).' : 'Tus marcas son NARANJA (docente).' }}</small>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            {{-- Filtros --}}
            <form method="GET" class="form-row align-items-end mb-3">
                <div class="form-group col-md-4">
                    <label class="mb-1">Curso</label>
                    <select name="cur_codigo" class="form-control select2">
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $curCodigo == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="mb-1">Periodo</label>
                    <select name="periodo_id" class="form-control">
                        @foreach($periodos as $p)
                            <option value="{{ $p->periodo_id }}" {{ $periodoId == $p->periodo_id ? 'selected' : '' }}>{{ $p->periodo_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="mb-1">Gestión</label>
                    <input type="number" name="gestion" value="{{ $gestion }}" class="form-control">
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Ver matriz</button>
                </div>
            </form>

            @if($datos)
                {{-- Acciones --}}
                <div class="mb-3 d-flex flex-wrap" style="gap:8px;">
                    <form method="POST" action="{{ route('alertas.aplicar-sugerencia') }}" onsubmit="return confirm('¿Marcar automáticamente todas las materias reprobadas (nota < {{ $datos['umbral'] }})?');">
                        @csrf
                        <input type="hidden" name="cur_codigo" value="{{ $curCodigo }}">
                        <input type="hidden" name="periodo_id" value="{{ $periodoId }}">
                        <input type="hidden" name="gestion" value="{{ $gestion }}">
                        <button class="btn btn-warning"><i class="fas fa-wand-magic-sparkles mr-1"></i>Aplicar sugerencia (notas &lt; {{ $datos['umbral'] }})</button>
                    </form>
                    <a target="_blank" class="btn btn-danger" href="{{ route('alertas.advertencia-lote', ['cur_codigo'=>$curCodigo,'periodo_id'=>$periodoId,'gestion'=>$gestion]) }}"><i class="fas fa-file-pdf mr-1"></i>Advertencias (lote)</a>
                    <a target="_blank" class="btn btn-info" href="{{ route('alertas.curso', ['cur_codigo'=>$curCodigo,'periodo_id'=>$periodoId,'gestion'=>$gestion]) }}"><i class="fas fa-list mr-1"></i>Nómina por curso</a>
                    <a target="_blank" class="btn btn-secondary" href="{{ route('alertas.hoja-docente', ['gestion'=>$gestion]) }}"><i class="fas fa-clipboard mr-1"></i>Hoja docente</a>
                </div>

                <div class="leyenda mb-2">
                    <span style="background:#f6a623;">NARANJA = Docente</span>
                    <span style="background:#f48fb1;">ROSA = Dirección</span>
                    <span style="background:#fff3cd;">SUGERIDO (nota &lt; {{ $datos['umbral'] }})</span>
                    <small class="text-muted">Clic en una celda para marcar/desmarcar.</small>
                </div>

                <div class="table-responsive">
                    <table class="adv-table">
                        <thead>
                            <tr>
                                <th style="width:34px;">N°</th>
                                <th style="text-align:left;">Estudiante</th>
                                @foreach($datos['materias'] as $m)
                                    <th class="mat"><div>{{ mb_strtoupper($m->mat_nombre, 'UTF-8') }}</div></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datos['estudiantes'] as $i => $est)
                                <tr>
                                    <td class="num">{{ $datos['lista'][$est->est_codigo] ?? ($i+1) }}</td>
                                    <td class="nombre">{{ mb_strtoupper($est->est_apellidos.' '.$est->est_nombres, 'UTF-8') }}</td>
                                    @foreach($datos['materias'] as $m)
                                        @php
                                            $marca = $datos['marcas'][$est->est_codigo][$m->mat_codigo] ?? ['docente'=>false,'director'=>false];
                                            $nota  = $datos['notas'][$est->est_codigo][$m->mat_codigo] ?? null;
                                            $puede = $datos['esDireccion'] || in_array($m->mat_codigo, $datos['misMaterias']);
                                            $cls = $marca['director'] ? 'dir' : ($marca['docente'] ? 'doc' : (($nota !== null && $nota > 0 && $nota < $datos['umbral']) ? 'sug' : ''));
                                        @endphp
                                        <td class="cell {{ $cls }} {{ $puede ? '' : 'lock' }}"
                                            data-est="{{ $est->est_codigo }}"
                                            data-mat="{{ $m->mat_codigo }}"
                                            data-doc="{{ $marca['docente'] ? 1 : 0 }}"
                                            data-dir="{{ $marca['director'] ? 1 : 0 }}"
                                            data-puede="{{ $puede ? 1 : 0 }}">
                                            <span class="nota">{{ $nota !== null ? $nota : '' }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            @if($datos['estudiantes']->isEmpty())
                                <tr><td colspan="{{ 2 + $datos['materias']->count() }}" class="text-center text-muted py-3">Sin estudiantes en el curso.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">Seleccione curso y periodo para ver la matriz.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('.select2').select2({ theme:'bootstrap4', width:'100%' });

    const CFG = {
        rol: {{ ($datos['esDireccion'] ?? false) ? "'director'" : "'docente'" }},
        cur: '{{ $curCodigo }}',
        periodo: '{{ $periodoId }}',
        gestion: '{{ $gestion }}',
        token: '{{ csrf_token() }}'
    };

    $('.cell').on('click', function(){
        const $c = $(this);
        if ($c.data('puede') != 1) return;

        const campo = CFG.rol === 'director' ? 'dir' : 'doc';
        const actual = $c.data(campo) == 1 ? 1 : 0;
        const nuevo = actual ? 0 : 1;

        $.post('{{ route("alertas.toggle") }}', {
            _token: CFG.token,
            est_codigo: $c.data('est'),
            mat_codigo: $c.data('mat'),
            cur_codigo: CFG.cur,
            periodo_id: CFG.periodo,
            gestion: CFG.gestion,
            rol: CFG.rol,
            estado: nuevo
        }).done(function(r){
            $c.data('doc', r.docente ? 1 : 0).data('dir', r.director ? 1 : 0);
            pintar($c);
        }).fail(function(x){
            alert(x.responseJSON?.message || 'No autorizado / error al marcar');
        });
    });

    function pintar($c){
        $c.removeClass('dir doc sug');
        const nota = parseInt($c.find('.nota').text()) || 0;
        if ($c.data('dir') == 1)      $c.addClass('dir');
        else if ($c.data('doc') == 1) $c.addClass('doc');
        else if (nota > 0 && nota < {{ $datos['umbral'] ?? 51 }}) $c.addClass('sug');
    }
});
</script>
@endsection
