@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4><i class="fas fa-exclamation-triangle mr-2"></i>Estudiantes Reprobados</h4></div>
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <label>Curso</label>
                    <select name="curso" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $cursoCod == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Trimestre</label>
                    <select name="periodo_id" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p->periodo_id }}" {{ $periodoId == $p->periodo_id ? 'selected' : '' }}>
                                {{ $p->periodo_nombre ?? ($p->periodo_numero . 'er Trimestre') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>

            @if($cursoCod && $periodoId)
                <div class="table-responsive-modern">
                    <table class="modern-table">
                        <thead><tr><th>#</th><th>Estudiante</th><th>Materias Reprobadas</th></tr></thead>
                        <tbody>
                            @forelse($rows as $r)
                                <tr style="{{ $r->est_visible == 0 ? 'background:#ffe6e6;' : '' }}">
                                    <td>{{ $r->lista_numero }}</td>
                                    <td>
                                        {{ $r->nombre }}
                                        @if($r->est_visible == 0)<span class="modern-badge badge-danger-modern ml-1">RETIRADO</span>@endif
                                    </td>
                                    <td><span class="modern-badge badge-danger-modern">{{ $r->materias_reprobadas }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Sin reprobados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
