@extends('layouts.app')
@section('title', 'Web pública')

@section('content')
@php
    use App\Models\LandingBloque;
    $edit = $bloque->exists;
    $sec = old('lb_seccion', $bloque->lb_seccion);
    // Qué significa "subtítulo" en cada sección
    $subtitulos = ['AUTORIDAD' => ['Cargo', 'Director General'], 'HITO' => ['Año o fecha', '2026'],
                   'REQUISITO' => ['Para quién (opcional)', 'Alumnos nuevos / Antiguos'], 'AVISO' => ['Subtítulo (opcional)', ''],
                   'DOCUMENTO' => ['Subtítulo (opcional)', '']];
@endphp
<section class="section">
    <div class="section-header"><h1><i class="fas fa-globe mr-2"></i>Web pública</h1></div>
    <div class="section-body">
        @include('web-publica._tabs')
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <form method="POST" enctype="multipart/form-data" class="card"
                      action="{{ $edit ? route('web-publica.bloques.update', $bloque->lb_id) : route('web-publica.bloques.store') }}">
                    @csrf @if($edit) @method('PUT') @endif
                    <div class="card-header"><h4 class="mb-0">{{ $edit ? 'Editar' : 'Agregar' }}: {{ config('landing.bloques')[$sec][0] ?? '' }}</h4></div>
                    <div class="card-body">
                        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                        <input type="hidden" name="lb_seccion" value="{{ $sec }}">
                        <div class="row">
                            <div class="col-md-7 form-group">
                                <label>{{ $sec === 'AUTORIDAD' ? 'Nombre' : 'Título' }}</label>
                                <input type="text" name="lb_titulo" class="form-control" value="{{ old('lb_titulo', $bloque->lb_titulo) }}" required maxlength="150">
                            </div>
                            <div class="col-md-5 form-group">
                                <label>{{ $subtitulos[$sec][0] }}</label>
                                <input type="text" name="lb_subtitulo" class="form-control" value="{{ old('lb_subtitulo', $bloque->lb_subtitulo) }}" maxlength="150" placeholder="{{ $subtitulos[$sec][1] }}">
                            </div>
                        </div>
                        @if($sec !== 'AUTORIDAD')
                        <div class="form-group">
                            <label>{{ $sec === 'REQUISITO' ? 'Detalle' : 'Texto' }}</label>
                            <textarea name="lb_texto" class="form-control editor-html">{{ old('lb_texto', $bloque->lb_texto) }}</textarea>
                        </div>
                        @endif
                        <div class="row">
                            @if(in_array($sec, ['AVISO', 'AUTORIDAD']))
                            <div class="col-md-6 form-group">
                                <label>{{ $sec === 'AUTORIDAD' ? 'Foto (opcional)' : 'Imagen (opcional)' }}</label>
                                <input type="file" name="imagen" accept="image/*" class="form-control-file">
                                @if($bloque->lb_imagen)
                                    <div class="mt-1"><img src="{{ $bloque->url_imagen }}" alt="" style="height:50px;border-radius:4px">
                                    <label class="small ml-2"><input type="checkbox" name="quitar_imagen" value="1"> quitar</label></div>
                                @endif
                            </div>
                            @endif
                            @if($sec !== 'AUTORIDAD' && $sec !== 'HITO')
                            <div class="col-md-6 form-group">
                                <label>Archivo para descargar (PDF, Word, Excel o imagen)</label>
                                <input type="file" name="archivo" class="form-control-file">
                                @if($bloque->lb_archivo)
                                    <div class="mt-1 small"><a href="{{ $bloque->url_archivo }}" target="_blank">Archivo actual</a>
                                    <label class="ml-2"><input type="checkbox" name="quitar_archivo" value="1"> quitar</label></div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @if($sec !== 'AUTORIDAD' && $sec !== 'HITO')
                        <div class="row">
                            <div class="col-md-8 form-group"><label>Enlace (opcional)</label><input type="url" name="lb_link" class="form-control" value="{{ old('lb_link', $bloque->lb_link) }}" placeholder="https://"></div>
                            <div class="col-md-4 form-group"><label>Texto del enlace</label><input type="text" name="lb_link_texto" class="form-control" value="{{ old('lb_link_texto', $bloque->lb_link_texto) }}" maxlength="60" placeholder="Ver más"></div>
                        </div>
                        @endif
                        <div class="row">
                            @if($sec === 'AVISO')
                            <div class="col-md-4 form-group"><label>Fecha del aviso</label><input type="date" name="lb_fecha" class="form-control" value="{{ old('lb_fecha', optional($bloque->lb_fecha)->format('Y-m-d')) }}"></div>
                            @endif
                            @if(in_array($sec, ['REQUISITO', 'DOCUMENTO', 'AVISO']))
                            <div class="col-md-4 form-group">
                                <label>Nivel</label>
                                <select name="niv_nombre" class="form-control js-select2">
                                    <option value="">Todos los niveles</option>
                                    @foreach(LandingBloque::NIVELES as $k => $n)<option value="{{ $k }}" {{ old('niv_nombre', $bloque->niv_nombre) === $k ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                                </select>
                            </div>
                            @endif
                            @if(in_array($sec, ['REQUISITO', 'DOCUMENTO']))
                            <div class="col-md-4 form-group">
                                <label>Gestión</label>
                                <input type="number" name="ges_anio" class="form-control" value="{{ old('ges_anio', $bloque->ges_anio) }}" placeholder="todas">
                                <small class="text-muted">La página de inscripciones muestra la gestión {{ \App\Support\Landing::gestionInscripcion() }}.</small>
                            </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Mostrar desde</label><input type="date" name="vigente_desde" class="form-control" value="{{ old('vigente_desde', optional($bloque->vigente_desde)->format('Y-m-d')) }}"></div>
                            <div class="col-md-4 form-group"><label>Hasta</label><input type="date" name="vigente_hasta" class="form-control" value="{{ old('vigente_hasta', optional($bloque->vigente_hasta)->format('Y-m-d')) }}"></div>
                            <div class="col-md-4 form-group"><label>Orden</label><input type="number" name="lb_orden" class="form-control" value="{{ old('lb_orden', $bloque->lb_orden ?? 0) }}"><small class="text-muted">Menor primero.</small></div>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="activo" name="lb_activo" value="1" {{ old('lb_activo', $bloque->lb_activo) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="activo">Mostrar en la web</label>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('web-publica.bloques', ['seccion' => $sec]) }}" class="btn btn-secondary">Volver</a>
                        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
@include('web-publica._editor')
@endsection
