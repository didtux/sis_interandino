{{-- Selector de ruta --}}
@if($rutas->count() > 1)
<div class="card mb-3" style="border-left:4px solid #2d3436; background:linear-gradient(135deg,#f8f9ff,#fff);">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <i class="fas fa-route" style="font-size:1.4rem;color:#2d3436;"></i>
            <div>
                <small class="text-muted d-block" style="font-size:0.7rem;line-height:1;">RUTA</small>
                <strong style="font-size:0.95rem;">{{ $rutas->firstWhere('ruta_codigo', $rutaSeleccionada)->ruta_nombre ?? 'Seleccione' }}</strong>
            </div>
        </div>
        <form method="GET" class="d-flex align-items-center" style="gap:6px;">
            @if(isset($tipo))<input type="hidden" name="tipo" value="{{ $tipo }}">@endif
            @if(isset($fecha))<input type="hidden" name="fecha" value="{{ $fecha }}">@endif
            <select name="ruta_codigo" class="form-control form-control-sm" style="width:auto;min-width:200px;" onchange="this.form.submit()">
                @foreach($rutas as $r)
                    <option value="{{ $r->ruta_codigo }}" {{ $rutaSeleccionada == $r->ruta_codigo ? 'selected' : '' }}>
                        {{ $r->ruta_nombre }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
</div>
@endif
