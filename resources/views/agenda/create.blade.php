@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-plus mr-2"></i>Nuevo Registro de Agenda</h4>
                    <a href="{{ route('agenda.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('agenda.store') }}" method="POST">
                        @csrf
                        @include('agenda._form')
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
                        <a href="{{ route('agenda.index') }}" class="btn btn-secondary">Cancelar</a>
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
