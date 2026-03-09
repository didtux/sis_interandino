@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-clipboard-list mr-2"></i>Notas</h4>
                    <a href="{{ route('notas.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Registrar Nota
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Docente</th>
                                    <th>SER</th>
                                    <th>SABER</th>
                                    <th>HACER</th>
                                    <th>DECIDIR</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notas as $nota)
                                    <tr>
                                        <td data-label="Estudiante">{{ $nota->estudiante->est_nombres ?? 'N/A' }}</td>
                                        <td data-label="Curso">
                                            <span class="modern-badge badge-primary-modern">{{ $nota->curso->cur_nombre ?? 'N/A' }}</span>
                                        </td>
                                        <td data-label="Docente">{{ $nota->docente->doc_nombres ?? 'N/A' }}</td>
                                        <td data-label="SER">
                                            <span class="modern-badge badge-success-modern">{{ $nota->notas_ser1 }}/{{ $nota->notas_ser2 }}</span>
                                        </td>
                                        <td data-label="SABER">
                                            <span class="modern-badge badge-success-modern">{{ $nota->notas_saber1 }}/{{ $nota->notas_saber2 }}</span>
                                        </td>
                                        <td data-label="HACER">
                                            <span class="modern-badge badge-success-modern">{{ $nota->notas_hacer1 }}/{{ $nota->notas_hacer2 }}</span>
                                        </td>
                                        <td data-label="DECIDIR">
                                            <span class="modern-badge badge-success-modern">{{ $nota->notas_decidir1 }}/{{ $nota->notas_decidir2 }}</span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('notas.edit', $nota->notas_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list"></i>
                                                <h5>No hay notas registradas</h5>
                                                <p>Comienza registrando la primera nota</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $notas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
