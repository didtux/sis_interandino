@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="row"><div class="col-md-8 offset-md-2">
        <div class="card modern-card">
            <div class="card-header"><h4><i class="fas fa-plus-circle mr-2"></i>Nueva Actividad</h4></div>
            <div class="card-body">
                <form action="{{ route('actividades-asistencia.store') }}" method="POST">@csrf
                    <div class="form-group"><label>Nombre <span class="text-danger">*</span></label><input type="text" name="act_nombre" class="form-control" required></div>
                    <div class="form-group"><label>Fecha <span class="text-danger">*</span></label><input type="date" name="act_fecha" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Descripción</label><textarea name="act_descripcion" class="form-control" rows="3"></textarea></div>
                    <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                    <a href="{{ route('actividades-asistencia.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div></div>
</div>
@endsection
