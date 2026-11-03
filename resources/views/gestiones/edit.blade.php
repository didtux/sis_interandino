@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-calendar-alt mr-2"></i>Editar Gestión {{ $gestion->ges_anio }}</h4></div>
                <div class="card-body">
                    <form action="{{ route('gestiones.update', $gestion->ges_id) }}" method="POST">
                        @csrf @method('PUT')
                        @include('gestiones._form')
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Actualizar</button>
                        <a href="{{ route('gestiones.index') }}" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
