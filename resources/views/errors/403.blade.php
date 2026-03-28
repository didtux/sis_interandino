@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card modern-card text-center" style="margin-top: 50px;">
                <div class="card-body py-5">
                    <i class="fas fa-lock fa-4x text-danger mb-3"></i>
                    <h3 class="text-danger">Acceso Denegado</h3>
                    <p class="text-muted mt-3">No tiene permisos para realizar esta acción.</p>
                    <p class="text-muted">Contacte al administrador si necesita acceso.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary-modern mt-3">
                        <i class="fas fa-home mr-1"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
