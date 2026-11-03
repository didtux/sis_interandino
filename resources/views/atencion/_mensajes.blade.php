{{-- Burbujas de un hilo de atención. $lado: 'padre' o 'personal' (quién mira). --}}
@foreach($mensajes as $m)
    @if($m->am_autor === 'SISTEMA')
        <div class="at-sistema" data-id="{{ $m->am_id }}"><span>{{ $m->am_texto }} · {{ $m->am_fecha->format('d/m H:i') }}</span></div>
    @else
        @php $mio = ($lado === 'padre') === ($m->am_autor === 'PADRE'); @endphp
        <div class="at-msg {{ $mio ? 'at-mio' : 'at-otro' }}" data-id="{{ $m->am_id }}">
            <div class="at-burbuja">
                <div class="at-autor">
                    @if($m->am_autor === 'PADRE') {{ $lado === 'padre' ? 'Usted' : 'Padre/madre' }}
                    @else {{ $m->usuario ? trim($m->usuario->us_nombres . ' ' . $m->usuario->us_apellidos) : 'Colegio' }}@endif
                </div>
                <div class="at-texto">{{ $m->am_texto }}</div>
                @if($m->am_adjunto)
                    @if(preg_match('/\.(jpe?g|png|webp)$/i', $m->am_adjunto))
                        <a href="{{ $m->url_adjunto }}" target="_blank"><img src="{{ $m->url_adjunto }}" alt="adjunto" class="at-img"></a>
                    @else
                        <a href="{{ $m->url_adjunto }}" target="_blank" class="d-block mt-1"><i class="fas fa-paperclip mr-1"></i>Ver adjunto</a>
                    @endif
                @endif
                <div class="at-hora">{{ $m->am_fecha->format('d/m/Y H:i') }}@if($mio && $m->am_autor !== 'SISTEMA') · {{ $m->am_leido ? 'leído' : 'enviado' }}@endif</div>
            </div>
        </div>
    @endif
@endforeach
