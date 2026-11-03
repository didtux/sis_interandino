@extends('layouts.app')
@section('title', 'Web pública')

@section('content')
<section class="section">
    <div class="section-header"><h1><i class="fas fa-globe mr-2"></i>Web pública</h1></div>
    <div class="section-body">
        @include('web-publica._tabs')
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <form method="POST" action="{{ route('web-publica.redes.guardar') }}" class="card">
                    @csrf @method('PUT')
                    <div class="card-header"><h4 class="mb-0">Contacto y redes sociales</h4></div>
                    <div class="card-body">
                        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                        <p class="text-muted small">Se muestran en el pie de todas las páginas y en Contacto. Las redes vacías no aparecen.</p>
                        <div class="row">
                            <div class="col-md-12 form-group"><label>Dirección</label><input type="text" name="config_direccion" class="form-control" value="{{ old('config_direccion', $config->config_direccion) }}" maxlength="255"></div>
                            <div class="col-md-4 form-group"><label>Teléfono</label><input type="text" name="config_telefono" class="form-control" value="{{ old('config_telefono', $config->config_telefono) }}" maxlength="50"></div>
                            <div class="col-md-4 form-group"><label>Correo</label><input type="email" name="config_email" class="form-control" value="{{ old('config_email', $config->config_email) }}" maxlength="100"></div>
                            <div class="col-md-4 form-group"><label>Horario de atención</label><input type="text" name="config_horario" class="form-control" value="{{ old('config_horario', $config->config_horario) }}" maxlength="150" placeholder="Lun a vie 8:00 – 16:00"></div>
                        </div>
                        <hr>
                        <div class="row">
                            @foreach(['config_facebook' => ['Facebook', 'fab fa-facebook-f', 'https://facebook.com/...'], 'config_instagram' => ['Instagram', 'fab fa-instagram', 'https://instagram.com/...'],
                                      'config_tiktok' => ['TikTok', 'fab fa-tiktok', 'https://tiktok.com/@...'], 'config_youtube' => ['YouTube', 'fab fa-youtube', 'https://youtube.com/@...']] as $campo => [$nombre, $icono, $ph])
                            <div class="col-md-6 form-group">
                                <label><i class="{{ $icono }} mr-1"></i>{{ $nombre }}</label>
                                <input type="url" name="{{ $campo }}" class="form-control" value="{{ old($campo, $config->$campo) }}" placeholder="{{ $ph }}">
                            </div>
                            @endforeach
                            <div class="col-md-6 form-group">
                                <label><i class="fab fa-whatsapp mr-1"></i>WhatsApp</label>
                                <input type="text" name="config_whatsapp" class="form-control" value="{{ old('config_whatsapp', $config->config_whatsapp) }}" placeholder="591 71234567">
                                <small class="text-muted">Con código de país (591). Abre un chat con ese número.</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label><i class="fas fa-map-marked-alt mr-1"></i>Enlace de Google Maps</label>
                                <input type="url" name="config_mapa_url" class="form-control" value="{{ old('config_mapa_url', $config->config_mapa_url) }}" placeholder="https://maps.app.goo.gl/...">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right"><button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button></div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
