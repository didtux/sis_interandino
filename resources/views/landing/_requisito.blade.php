<div class="req-item">
    @if($r->lb_subtitulo)<span class="tipo">{{ $r->lb_subtitulo }}</span>@endif
    <strong>{{ $r->lb_titulo }}</strong>
    @if($r->lb_texto)<div class="det">{!! $r->lb_texto !!}</div>@endif
    @if($r->url_archivo)<a href="{{ $r->url_archivo }}" target="_blank" rel="noopener" style="font-size:.85rem"><i class="fas fa-file-download"></i> Descargar</a>@endif
    @if($r->lb_link)<a href="{{ $r->lb_link }}" target="_blank" rel="noopener" style="font-size:.85rem"><i class="fas fa-external-link-alt"></i> {{ $r->lb_link_texto ?: 'Ver más' }}</a>@endif
</div>
