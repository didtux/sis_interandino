@extends('layouts.app')

{{-- Publicaciones del colegio (revista, útiles, reglamento, horarios, misión y visión). --}}
@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4><i class="{{ $icono }} mr-2"></i>{{ $titulo }} — {{ $gestion }}</h4></div>
        <div class="card-body">
            @forelse($publicaciones as $p)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <h6 class="mb-1 font-weight-bold">{{ $p->pub_titulo }}</h6>
                            @if($p->cur_codigo)<span class="badge badge-primary">{{ optional($p->curso)->cur_nombre }}</span>@elseif($p->niv_nombre)<span class="badge badge-info">{{ $p->niv_nombre }}</span>@endif
                            @if($p->pub_descripcion)<p class="text-muted small mb-0 mt-1">{{ $p->pub_descripcion }}</p>@endif
                        </div>
                        <div class="mt-2 mt-md-0">
                            @if($p->pub_formato === 'PDF' && $p->url_archivo)
                                @if($tipo === 'REVISTA')
                                    <button type="button" class="btn btn-sm btn-primary" onclick="abrirRevista(@json($p->url_archivo), @json($p->pub_titulo))"><i class="fas fa-book-open mr-1"></i>Leer</button>
                                @endif
                                <a href="{{ $p->url_archivo }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download mr-1"></i>Descargar</a>
                            @elseif($p->pub_formato === 'LINK' && $p->pub_url)
                                <a href="{{ $p->pub_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt mr-1"></i>Abrir</a>
                            @endif
                        </div>
                    </div>
                    @if($p->pub_formato === 'IMAGEN' && $p->url_archivo)
                        <img src="{{ $p->url_archivo }}" class="img-fluid mt-2 rounded" alt="{{ $p->pub_titulo }}">
                    @elseif($p->pub_formato === 'HTML' && $p->pub_contenido_html)
                        <div class="mt-2" style="white-space:pre-line;">{{ $p->pub_contenido_html }}</div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5"><i class="{{ $icono }} fa-2x mb-2 d-block" style="opacity:.3"></i>Todavía no hay publicaciones.</div>
            @endforelse
        </div>
    </div>
</div>

@if($tipo === 'REVISTA')
<div class="modal fade" id="modalRevista" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:96vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="revTitulo"></h6>
                <div class="ml-auto d-flex align-items-center">
                    <button class="btn btn-sm btn-light mr-1" onclick="pagina(-1)"><i class="fas fa-chevron-left"></i></button>
                    <span id="revPag" class="small mx-2"></span>
                    <button class="btn btn-sm btn-light mr-3" onclick="pagina(1)"><i class="fas fa-chevron-right"></i></button>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
            </div>
            <div class="modal-body text-center" style="background:#444;min-height:70vh;">
                <div id="revCargando" class="text-white py-5">Cargando…</div>
                <canvas id="revIzq" style="max-width:49%;box-shadow:0 2px 10px #000;"></canvas>
                <canvas id="revDer" style="max-width:49%;box-shadow:0 2px 10px #000;"></canvas>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if($tipo === 'REVISTA')
{{-- Revista: PDF.js, a doble página como un libro (en el celular, una página). --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
var revDoc = null, revActual = 1, doble = window.innerWidth > 900;
function abrirRevista(url, titulo) {
    $('#revTitulo').text(titulo); $('#revCargando').show(); $('#modalRevista').modal('show');
    pdfjsLib.getDocument(url).promise.then(function (d) { revDoc = d; revActual = 1; $('#revCargando').hide(); dibujar(); });
}
function render(n, canvas) {
    if (!revDoc || n < 1 || n > revDoc.numPages) { canvas.style.display = 'none'; return; }
    canvas.style.display = '';
    revDoc.getPage(n).then(function (p) {
        var alto = window.innerHeight * 0.75, v = p.getViewport({ scale: 1 });
        var vp = p.getViewport({ scale: alto / v.height });
        canvas.width = vp.width; canvas.height = vp.height;
        p.render({ canvasContext: canvas.getContext('2d'), viewport: vp });
    });
}
function dibujar() {
    // Portada sola; después, pares 2-3, 4-5… como una revista abierta.
    var izq = revActual, der = (doble && revActual > 1) ? revActual + 1 : null;
    render(izq, document.getElementById('revIzq'));
    if (der) render(der, document.getElementById('revDer')); else document.getElementById('revDer').style.display = 'none';
    $('#revPag').text(izq + (der && der <= revDoc.numPages ? '–' + der : '') + ' / ' + revDoc.numPages);
}
function pagina(dir) {
    if (!revDoc) return;
    if (!doble) {
        revActual += dir;
    } else if (dir > 0) {
        revActual = revActual === 1 ? 2 : revActual + 2;   // portada → 2-3 → 4-5 …
    } else {
        revActual = revActual <= 2 ? 1 : revActual - 2;
    }
    revActual = Math.min(Math.max(1, revActual), revDoc.numPages);
    dibujar();
}
</script>
@endif
@endsection
