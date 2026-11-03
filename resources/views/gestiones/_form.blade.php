@php $g = $gestion ?? null; @endphp
<div class="row">
    <div class="col-md-4 form-group">
        <label class="font-weight-bold">Año *</label>
        <input type="text" name="ges_anio" class="form-control @error('ges_anio') is-invalid @enderror"
               value="{{ old('ges_anio', optional($g)->ges_anio) }}" required maxlength="4"
               {{ $g && $g->ges_estado == 1 ? 'readonly' : '' }}>
        @error('ges_anio')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5 form-group">
        <label class="font-weight-bold">Nombre *</label>
        <input type="text" name="ges_nombre" class="form-control" value="{{ old('ges_nombre', optional($g)->ges_nombre) }}" required maxlength="80" placeholder="GESTIÓN 2027">
    </div>
    <div class="col-md-3 form-group">
        <label class="font-weight-bold">Abreviado</label>
        <input type="text" name="ges_abreviado" class="form-control" value="{{ old('ges_abreviado', optional($g)->ges_abreviado) }}" maxlength="20">
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label class="font-weight-bold">Inicio de clases</label>
        <input type="date" name="ges_fecha_inicio" class="form-control" value="{{ old('ges_fecha_inicio', optional($g)->ges_fecha_inicio) }}">
    </div>
    <div class="col-md-6 form-group">
        <label class="font-weight-bold">Fin de clases</label>
        <input type="date" name="ges_fecha_fin" class="form-control" value="{{ old('ges_fecha_fin', optional($g)->ges_fecha_fin) }}">
    </div>
</div>

@php
    $puedePreinsc = auth()->user()->tieneCapacidad('gestion.preinscripcion');
    $puedeMontos = auth()->user()->tieneCapacidad('gestion.montos');
@endphp
<hr>
<h6 class="font-weight-bold"><i class="fas fa-user-clock mr-1"></i>Pre-inscripción</h6>
@if(!$puedePreinsc || !$puedeMontos)
    <div class="alert alert-light border small py-2">
        <i class="fas fa-lock mr-1"></i>Su rol no puede cambiar
        {{ !$puedePreinsc && !$puedeMontos ? 'la apertura, las fechas ni el monto' : (!$puedePreinsc ? 'la apertura ni las fechas' : 'el monto') }}
        de la pre-inscripción (Roles → Capacidades especiales).
    </div>
@endif
<div class="custom-control custom-switch mb-2">
    <input type="checkbox" class="custom-control-input" id="insc_abierta" name="ges_inscripcion_abierta" value="1" {{ $puedePreinsc ? '' : 'disabled' }}
           {{ old('ges_inscripcion_abierta', optional($g)->ges_inscripcion_abierta) ? 'checked' : '' }}>
    <label class="custom-control-label" for="insc_abierta">
        Inscripción abierta para esta gestión
        <small class="text-muted d-block">Se puede pre-inscribir y reservar cupo aunque todavía no sea la gestión vigente.</small>
    </label>
</div>
<div class="row">
    <div class="col-md-4 form-group">
        <label>Pre-inscripción desde</label>
        <input type="date" name="ges_preinsc_desde" class="form-control" value="{{ old('ges_preinsc_desde', optional($g)->ges_preinsc_desde) }}" {{ $puedePreinsc ? '' : 'disabled' }}>
    </div>
    <div class="col-md-4 form-group">
        <label>Hasta</label>
        <input type="date" name="ges_preinsc_hasta" class="form-control @error('ges_preinsc_hasta') is-invalid @enderror" value="{{ old('ges_preinsc_hasta', optional($g)->ges_preinsc_hasta) }}" {{ $puedePreinsc ? '' : 'disabled' }}>
        @error('ges_preinsc_hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 form-group">
        <label>Monto de la reserva (Bs)</label>
        <input type="number" step="0.01" min="0" name="ges_monto_reserva" class="form-control" {{ $puedeMontos ? '' : 'disabled' }}
               value="{{ old('ges_monto_reserva', optional($g)->ges_monto_reserva) }}" placeholder="{{ number_format((float) parametro('preinsc_monto_reserva', 300), 2) }} (por defecto)">
        <small class="text-muted">Vacío = el monto por defecto de Parámetros. Si la tarifa del curso fija una reserva, manda esa.</small>
    </div>
</div>
@if(!$g || $g->ges_estado != 1)
    <div class="alert alert-light border small mb-3">
        <i class="fas fa-info-circle mr-1"></i>Para que esta gestión pase a ser la <strong>vigente</strong> (paso de año) use
        el botón <strong>Activar</strong> del listado: abre un asistente que revisa todo antes de cambiar.
    </div>
@endif
