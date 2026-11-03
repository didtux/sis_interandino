@extends('layouts.app')

@section('content')
@php use App\Models\AtencionTicket; @endphp
<div class="section-body">
    <div class="row">
        <div class="col-lg-5">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-paper-plane mr-2"></i>Nueva consulta</h4></div>
                <div class="card-body">
                    @if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                    <form method="POST" action="{{ route('padre-portal.consultas.crear') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>¿A quién le escribe?</label>
                            @foreach($areas as $k => $a)
                                <div class="custom-control custom-radio mb-1">
                                    <input type="radio" id="area-{{ $k }}" name="area" value="{{ $k }}" class="custom-control-input" {{ old('area', 'SECRETARIA') === $k ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="area-{{ $k }}"><i class="{{ $a['icono'] }} mr-1 text-primary"></i><strong>{{ $a['nombre'] }}</strong>
                                        <small class="text-muted d-block">{{ $a['ayuda'] }}</small></label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-group">
                            <label>Sobre su hijo/a <small class="text-muted" id="hijoOpc">(opcional)</small></label>
                            <select name="est_codigo" class="form-control js-select2">
                                <option value="">— General —</option>
                                @foreach($hijos as $h)
                                    <option value="{{ $h->est_codigo }}" {{ old('est_codigo') === $h->est_codigo ? 'selected' : '' }}>{{ $h->est_nombres }} {{ $h->est_apellidos }} · {{ optional($h->curso)->cur_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label>Asunto</label><input type="text" name="asunto" class="form-control" maxlength="150" value="{{ old('asunto') }}" required></div>
                        <div class="form-group"><label>Mensaje</label><textarea name="texto" class="form-control" rows="4" maxlength="3000" required style="height:auto">{{ old('texto') }}</textarea></div>
                        <div class="form-group"><label>Adjunto <small class="text-muted">(foto o PDF, opcional)</small></label><input type="file" name="adjunto" accept="image/*,application/pdf" class="form-control-file"></div>
                        <button class="btn btn-primary btn-block"><i class="fas fa-paper-plane mr-1"></i>Enviar consulta</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-comments mr-2"></i>Mis consultas</h4></div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
                    <div class="list-group">
                        @forelse($tickets as $t)
                            @php $nl = $noLeidos[$t->at_id] ?? 0; @endphp
                            <a href="{{ route('padre-portal.consultas.ver', $t->at_id) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $t->at_asunto }} @if($nl)<span class="badge badge-danger ml-1">{{ $nl }} nueva{{ $nl > 1 ? 's' : '' }}</span>@endif</strong>
                                    <span class="badge badge-{{ AtencionTicket::ESTADOS[$t->at_estado][1] }}">{{ $t->at_estado === 'ABIERTO' ? 'Esperando respuesta' : AtencionTicket::ESTADOS[$t->at_estado][0] }}</span>
                                </div>
                                <small class="text-muted">{{ $t->area_nombre }}{{ $t->estudiante ? ' · ' . $t->estudiante->est_nombres : '' }} · {{ $t->at_ultimo_mensaje->diffForHumans() }}</small>
                            </a>
                        @empty
                            <div class="text-center text-muted py-4">Todavía no hizo ninguna consulta.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Para los docentes hay que decir de qué hijo se trata (la consulta la ven los profesores de su curso)
function hijoObligatorio() {
    var doc = $('input[name=area]:checked').val() === 'DOCENTE';
    $('select[name=est_codigo]').prop('required', doc).find('option[value=""]').text(doc ? '— Elija —' : '— General —');
    $('#hijoOpc').text(doc ? '(obligatorio para docentes)' : '(opcional)');
}
$('input[name=area]').on('change', hijoObligatorio); hijoObligatorio();
</script>
@endsection
