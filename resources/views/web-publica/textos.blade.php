@extends('layouts.app')
@section('title', 'Web pública')

@section('content')
@php
    use App\Support\Landing;
    use App\Http\Controllers\WebPublicaController as W;
    $rutas = ['general' => 'landing', 'inicio' => 'landing', 'nosotros' => 'landing.nosotros', 'niveles' => 'landing.niveles',
              'historia' => 'landing.historia', 'inscripciones' => 'landing.inscripciones'];
@endphp
<section class="section">
    <div class="section-header"><h1><i class="fas fa-globe mr-2"></i>Web pública</h1></div>
    <div class="section-body">
        @include('web-publica._tabs')
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="list-group">
                    @foreach($paginas as $k => $p)
                        <a href="{{ route('web-publica.textos', ['pagina' => $k]) }}" class="list-group-item list-group-item-action {{ $k === $pagina ? 'active' : '' }}">{{ $p['titulo'] }}</a>
                    @endforeach
                </div>
                <div class="alert alert-light mt-3 small mb-0">
                    <i class="fas fa-info-circle"></i> Si deja un campo vacío, la web muestra el texto original.
                    En los títulos, escriba <code>*palabra*</code> para resaltarla en color.
                </div>
            </div>
            <div class="col-lg-9">
                <form method="POST" action="{{ route('web-publica.textos.guardar') }}" enctype="multipart/form-data" class="card">
                    @csrf
                    <input type="hidden" name="pagina" value="{{ $pagina }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $paginas[$pagina]['titulo'] }}</h4>
                        <a href="{{ route($rutas[$pagina] ?? 'landing') }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>Ver página</a>
                    </div>
                    <div class="card-body">
                        @foreach($paginas[$pagina]['campos'] as $clave => [$etiqueta, $tipo, $original])
                            @php $campo = W::nombreCampo($clave); $editado = Landing::editado($clave); $valor = old("valor.$campo", Landing::valor($clave)); @endphp
                            <div class="form-group">
                                <label class="d-flex justify-content-between mb-1">
                                    <span>{{ $etiqueta }} @if($editado)<span class="badge badge-info ml-1">editado</span>@endif</span>
                                    @if($editado && $tipo !== 'imagen')
                                        <button type="submit" form="restaurar-{{ $campo }}" class="btn btn-link btn-sm p-0" onclick="return confirm('¿Volver al texto original?')">restaurar original</button>
                                    @endif
                                </label>
                                @switch($tipo)
                                    @case('html')
                                        <textarea name="valor[{{ $campo }}]" class="form-control editor-html">{{ $valor }}</textarea>
                                        @break
                                    @case('parrafo')
                                    @case('lista')
                                        <textarea name="valor[{{ $campo }}]" class="form-control" rows="{{ $tipo === 'lista' ? 6 : 3 }}" style="height:auto">{{ $valor }}</textarea>
                                        @break
                                    @case('titulo')
                                        <textarea name="valor[{{ $campo }}]" class="form-control" rows="2" style="height:auto">{{ $valor }}</textarea>
                                        @break
                                    @case('imagen')
                                        <div class="d-flex align-items-center">
                                            @if($url = Landing::imagen($clave))
                                                <img src="{{ $url }}" alt="" style="height:60px;border-radius:6px" class="mr-3">
                                                <div class="custom-control custom-checkbox mr-3">
                                                    <input type="checkbox" class="custom-control-input" id="q-{{ $campo }}" name="quitar[{{ $campo }}]" value="1">
                                                    <label class="custom-control-label" for="q-{{ $campo }}">Quitar</label>
                                                </div>
                                            @endif
                                            <input type="file" name="img[{{ $campo }}]" accept="image/*" class="form-control-file">
                                        </div>
                                        <small class="text-muted">JPG, PNG o WEBP de hasta 4 MB, horizontal (1920×1080 ideal). Sin imagen se usa el fondo azul.</small>
                                        @break
                                    @default
                                        <input type="text" name="valor[{{ $campo }}]" class="form-control" value="{{ $valor }}" maxlength="255">
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer text-right">
                        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar {{ mb_strtolower($paginas[$pagina]['titulo']) }}</button>
                    </div>
                </form>
                {{-- Formularios de "restaurar" fuera del principal (no se anidan forms) --}}
                @foreach($paginas[$pagina]['campos'] as $clave => $def)
                    @if(Landing::editado($clave))
                        <form id="restaurar-{{ W::nombreCampo($clave) }}" method="POST" action="{{ route('web-publica.textos.restaurar') }}" class="d-none">
                            @csrf <input type="hidden" name="clave" value="{{ $clave }}">
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
@include('web-publica._editor')
@endsection
