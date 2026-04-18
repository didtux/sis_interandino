@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-heartbeat mr-2"></i>Enfermería</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado && $registros->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:0.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Diagnóstico</th>
                                    <th>Detalle</th>
                                    <th>Tratamiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registros as $reg)
                                <tr>
                                    <td>{{ $reg->enf_fecha ? \Carbon\Carbon::parse($reg->enf_fecha)->format('d/m/Y') : '-' }}</td>
                                    <td><span class="badge badge-info">{{ $reg->enf_dx ?? '-' }}</span></td>
                                    <td>{{ $reg->enf_dx_detalle ?? '-' }}</td>
                                    <td>{{ $reg->enf_tratamiento ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($estSeleccionado)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-heartbeat fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay registros de enfermería.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
