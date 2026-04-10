@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="row"><div class="col-md-8 offset-md-2">
        <div class="card modern-card">
            <div class="card-header"><h4><i class="fas fa-edit mr-2"></i>Editar Actividad</h4></div>
            <div class="card-body">
                <form action="{{ route('actividades-asistencia.update', $actividad->act_id) }}" method="POST">@csrf @method('PUT')
                    <div class="form-group"><label>Nombre <span class="text-danger">*</span></label><input type="text" name="act_nombre" class="form-control" value="{{ $actividad->act_nombre }}" required></div>
                    <div class="form-group"><label>Fecha <span class="text-danger">*</span></label><input type="date" name="act_fecha" class="form-control" value="{{ $actividad->act_fecha->format('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Descripción</label><textarea name="act_descripcion" class="form-control" rows="3">{{ $actividad->act_descripcion }}</textarea></div>
                    <div class="form-group"><label>Estado</label><select name="act_estado" class="form-control"><option value="1" {{ $actividad->act_estado ? 'selected' : '' }}>Activo</option><option value="0" {{ !$actividad->act_estado ? 'selected' : '' }}>Inactivo</option></select></div>
                    <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Actualizar</button>
                    <a href="{{ route('actividades-asistencia.show', $actividad->act_id) }}" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div></div>
</div>
@endsection
