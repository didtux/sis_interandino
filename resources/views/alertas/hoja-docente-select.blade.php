@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-clipboard mr-2"></i>Hoja de Reporte para Docente</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Seleccione el/la docente para generar su hoja de reporte (advertencia parcial) con los casilleros numerados por curso.</p>

                    <form method="GET" action="{{ route('alertas.hoja-docente') }}" target="_blank">
                        <input type="hidden" name="gestion" value="{{ $gestion }}">
                        <div class="form-group">
                            <label>Docente</label>
                            <select name="doc_codigo" class="form-control select2" required>
                                <option value="">Seleccione un docente...</option>
                                @foreach($docentes as $d)
                                    <option value="{{ $d->doc_codigo }}">{{ $d->doc_apellidos }} {{ $d->doc_nombres }}</option>
                                @endforeach
                            </select>
                            @if($docentes->isEmpty())
                                <small class="text-danger">No hay docentes con materias asignadas.</small>
                            @endif
                        </div>
                        <button class="btn btn-danger" {{ $docentes->isEmpty() ? 'disabled' : '' }}>
                            <i class="fas fa-file-pdf mr-1"></i>Generar hoja
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>$(function(){ $('.select2').select2({ theme:'bootstrap4', width:'100%' }); });</script>
@endsection
