{{-- Selector de estudiante --}}
<div class="card mb-3" style="border-left:4px solid #667eea; background:linear-gradient(135deg,#f8f9ff,#fff);">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <i class="fas fa-user-graduate" style="font-size:1.4rem;color:#667eea;"></i>
            <div>
                <small class="text-muted d-block" style="font-size:0.7rem;line-height:1;">ESTUDIANTE</small>
                <strong style="font-size:0.95rem;">{{ $estSeleccionado ? mb_strtoupper($estSeleccionado->est_apellidos . ' ' . $estSeleccionado->est_nombres) : 'Seleccione' }}</strong>
                @if($estSeleccionado && $estSeleccionado->curso)
                    <span class="badge badge-primary ml-1" style="font-size:10px;">{{ $estSeleccionado->curso->cur_nombre }}</span>
                @endif
            </div>
        </div>
        @if($estudiantes->count() > 1)
        <form method="GET" class="d-flex align-items-center" style="gap:6px;">
            <select name="est_codigo" class="form-control form-control-sm" style="width:auto;min-width:200px;" onchange="this.form.submit()">
                @foreach($estudiantes as $e)
                    <option value="{{ $e->est_codigo }}" {{ ($estSeleccionado && $estSeleccionado->est_codigo == $e->est_codigo) ? 'selected' : '' }}>
                        {{ $e->est_apellidos }} {{ $e->est_nombres }} — {{ $e->curso->cur_nombre ?? '' }}
                    </option>
                @endforeach
            </select>
        </form>
        @endif
    </div>
</div>
