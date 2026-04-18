@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-edit mr-2"></i>Editar Registro — {{ $agenda->age_codigo }}</h4>
                    <a href="{{ route('agenda.show', $agenda->age_id) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('agenda.update', $agenda->age_id) }}" method="POST">
                        @csrf @method('PUT')
                        @include('agenda._form')
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Actualizar</button>
                        <a href="{{ route('agenda.show', $agenda->age_id) }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('agenda._scripts')
@endsection
