@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-brain mr-2"></i>Psicopedagogía</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado && $casos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:0.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Motivo</th>
                                    <th>Acuerdo</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($casos as $caso)
                                <tr>
                                    <td>{{ $caso->psico_fecha ? \Carbon\Carbon::parse($caso->psico_fecha)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $caso->psico_motivo ?? '-' }}</td>
                                    <td>
                                        @if($caso->psico_tipo_acuerdo && $caso->psico_tipo_acuerdo !== 'NINGUNO')
                                            <span class="badge badge-success">{{ $caso->psico_tipo_acuerdo }}</span>
                                        @else
                                            <span class="badge badge-secondary">Sin acuerdo</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($caso->psico_observacion ?? '-', 80) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($estSeleccionado)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-brain fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay registros de psicopedagogía.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
