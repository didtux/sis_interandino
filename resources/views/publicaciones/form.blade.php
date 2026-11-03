@extends('layouts.app')

@section('content')
@php use App\Models\Publicacion; $edit = $pub->exists; @endphp
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-book-open mr-2"></i>{{ $edit ? 'Editar' : 'Nueva' }} publicación</h4></div>
                <div class="card-body">
                    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                    <form method="POST" enctype="multipart/form-data" action="{{ $edit ? route('publicaciones.update', $pub->pub_id) : route('publicaciones.store') }}">
                        @csrf @if($edit) @method('PUT') @endif
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Tipo</label>
                                <select name="pub_tipo" class="form-control js-select2">
                                    @foreach(Publicacion::TIPOS as $k => [$t])<option value="{{ $k }}" {{ old('pub_tipo', $pub->pub_tipo) === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group"><label>Título</label><input type="text" name="pub_titulo" class="form-control" value="{{ old('pub_titulo', $pub->pub_titulo) }}" required maxlength="150"></div>
                        </div>
                        <div class="form-group"><label>Descripción</label><textarea name="pub_descripcion" class="form-control" rows="2">{{ old('pub_descripcion', $pub->pub_descripcion) }}</textarea></div>
                        <div class="row">
                            <div class="col-md-3 form-group"><label>Gestión</label><input type="number" name="ges_anio" class="form-control" value="{{ old('ges_anio', $pub->ges_anio) }}" placeholder="todas"></div>
                            <div class="col-md-4 form-group">
                                <label>Nivel</label>
                                <select name="niv_nombre" class="form-control js-select2">
                                    <option value="">Todos</option>
                                    @foreach(['INICIAL', 'PRIMARIA', 'SECUNDARIA'] as $n)<option {{ old('niv_nombre', $pub->niv_nombre) === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5 form-group">
                                <label>Curso</label>
                                <select name="cur_codigo" class="form-control js-select2">
                                    <option value="">Todos (del nivel)</option>
                                    @foreach($cursos as $c)<option value="{{ $c->cur_codigo }}" {{ old('cur_codigo', $pub->cur_codigo) === $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>@endforeach
                                </select>
                                <small class="text-muted">La lista de útiles y el horario suelen ser por curso.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Formato</label>
                                <select name="pub_formato" id="formato" class="form-control js-select2">
                                    @foreach(['PDF' => 'PDF', 'IMAGEN' => 'Imagen', 'HTML' => 'Texto', 'LINK' => 'Enlace'] as $k => $t)<option value="{{ $k }}" {{ old('pub_formato', $pub->pub_formato) === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group f-archivo">
                                <label>Archivo (PDF hasta 20 MB o imagen)</label>
                                <input type="file" name="archivo" class="form-control-file" accept="application/pdf,image/*">
                                @if($pub->pub_archivo)<small><a href="{{ $pub->url_archivo }}" target="_blank">Archivo actual</a> — suba otro para reemplazarlo</small>@endif
                            </div>
                            <div class="col-md-8 form-group f-link"><label>Enlace</label><input type="url" name="pub_url" class="form-control" value="{{ old('pub_url', $pub->pub_url) }}"></div>
                        </div>
                        <div class="form-group f-html"><label>Texto</label><textarea name="pub_contenido_html" class="form-control" rows="8">{{ old('pub_contenido_html', $pub->pub_contenido_html) }}</textarea></div>
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Visible desde</label><input type="date" name="publicado_desde" class="form-control" value="{{ old('publicado_desde', optional($pub->publicado_desde)->format('Y-m-d')) }}"></div>
                            <div class="col-md-4 form-group"><label>Hasta</label><input type="date" name="publicado_hasta" class="form-control" value="{{ old('publicado_hasta', optional($pub->publicado_hasta)->format('Y-m-d')) }}"></div>
                            <div class="col-md-4 form-group"><label>Orden</label><input type="number" name="pub_orden" class="form-control" value="{{ old('pub_orden', $pub->pub_orden ?? 0) }}"></div>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="vis" name="visible_padres" value="1" {{ old('visible_padres', $pub->visible_padres) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="vis">Visible para los padres</label>
                        </div>
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                        <a href="{{ route('publicaciones.index') }}" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function formato() { var f = $('#formato').val(); $('.f-archivo').toggle(f === 'PDF' || f === 'IMAGEN'); $('.f-link').toggle(f === 'LINK'); $('.f-html').toggle(f === 'HTML'); }
$('#formato').on('change', formato); formato();
</script>
@endsection
