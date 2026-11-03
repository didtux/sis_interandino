@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-tags mr-2"></i>Tarifas {{ $gestion }} y curso siguiente</h4>
            <form method="GET" class="form-inline">
                <label class="mr-2 small">Gestión</label>
                <input type="number" name="gestion" value="{{ $gestion }}" class="form-control form-control-sm mr-1" style="width:90px;">
                <button class="btn btn-sm btn-secondary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <p class="text-muted small">
                <strong>Curso siguiente</strong>: a qué curso pasa el estudiante si aprueba. Si reprueba, repite el mismo curso.
                "Egresa" = último grado. <strong>Monto anual</strong>: tarifa de la gestión {{ $gestion }}; la columna de referencia
                es el monto más cobrado en {{ gestion_actual() }}. <strong>Reserva</strong>: lo que se cobra al pre-inscribir en ese curso;
                vacío = Bs {{ number_format($reservaGeneral, 2) }} ({{ $origenGeneral }}).
                @unless($puedeMontos)<br><i class="fas fa-lock"></i> Su rol no puede cambiar la reserva (capacidad «Fijar el monto de la reserva»).@endunless
            </p>
            <div class="mb-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSugerir"><i class="fas fa-magic mr-1"></i>Sugerir cursos siguientes</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopiarRef"><i class="fas fa-copy mr-1"></i>Usar montos de referencia</button>
            </div>
            <form method="POST" action="{{ route('tarifas.guardar') }}">
                @csrf
                <input type="hidden" name="gestion" value="{{ $gestion }}">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th style="width:70px;">Grado</th>
                                <th style="width:70px;">Paralelo</th>
                                <th>Curso siguiente</th>
                                <th class="text-right">Referencia {{ gestion_actual() }}</th>
                                <th style="width:150px;">Monto anual {{ $gestion }}</th>
                                <th style="width:150px;" title="Vacío = {{ number_format($reservaGeneral, 2) }} ({{ $origenGeneral }})">Reserva {{ $gestion }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursos as $c)
                                @php $s = $sugerencia[$c->cur_codigo] ?? ['grado' => null, 'paralelo' => null, 'siguiente' => null]; @endphp
                                <tr data-cur="{{ $c->cur_codigo }}"
                                    data-sug-grado="{{ $s['grado'] }}" data-sug-paralelo="{{ $s['paralelo'] }}" data-sug-siguiente="{{ $s['siguiente'] ?? 'EGRESA' }}"
                                    data-ref="{{ $referencia[$c->cur_codigo] ?? '' }}">
                                    <td>{{ $c->cur_nombre }} <small class="text-muted">{{ $c->cur_nivel }}</small></td>
                                    <td><input type="number" name="info[{{ $c->cur_codigo }}][grado]" class="form-control form-control-sm in-grado" value="{{ $c->cur_grado }}" min="1" max="20"></td>
                                    <td><input type="text" name="info[{{ $c->cur_codigo }}][paralelo]" class="form-control form-control-sm in-paralelo" value="{{ $c->cur_paralelo }}" maxlength="1"></td>
                                    <td>
                                        <select name="siguiente[{{ $c->cur_codigo }}]" class="form-control form-control-sm in-siguiente js-select2">
                                            <option value="">— sin definir —</option>
                                            <option value="EGRESA" {{ !$c->cur_siguiente && $c->cur_grado ? 'selected' : '' }}>Egresa</option>
                                            @foreach($cursos as $d)
                                                @if($d->cur_codigo !== $c->cur_codigo)
                                                    <option value="{{ $d->cur_codigo }}" {{ $c->cur_siguiente === $d->cur_codigo ? 'selected' : '' }}>{{ $d->cur_nombre }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-right text-muted">{{ isset($referencia[$c->cur_codigo]) ? number_format($referencia[$c->cur_codigo], 2) : '—' }}</td>
                                    <td><input type="number" step="0.01" min="0" name="tarifa[{{ $c->cur_codigo }}]" class="form-control form-control-sm in-tarifa" value="{{ $tarifas[$c->cur_codigo] ?? '' }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="reserva[{{ $c->cur_codigo }}]" class="form-control form-control-sm in-reserva" {{ $puedeMontos ? '' : 'disabled' }}
                                               value="{{ $reservas[$c->cur_codigo] ?? '' }}" placeholder="{{ number_format($reservaGeneral, 2) }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @puede('tarifas', 'editar')
                    <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                @endpuede
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$('#btnSugerir').on('click', function () {
    // Sólo completa lo que está vacío: no pisa lo que el colegio ya corrigió.
    $('tr[data-cur]').each(function () {
        var $r = $(this);
        if (!$r.find('.in-grado').val()) $r.find('.in-grado').val($r.data('sug-grado'));
        if (!$r.find('.in-paralelo').val()) $r.find('.in-paralelo').val($r.data('sug-paralelo'));
        if (!$r.find('.in-siguiente').val()) $r.find('.in-siguiente').val($r.data('sug-siguiente')).trigger('change.select2');
    });
});
$('#btnCopiarRef').on('click', function () {
    $('tr[data-cur]').each(function () {
        var $r = $(this);
        if (!$r.find('.in-tarifa').val() && $r.data('ref')) $r.find('.in-tarifa').val($r.data('ref'));
    });
});
</script>
@endsection
