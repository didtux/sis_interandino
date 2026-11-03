@extends('layouts.app')

@section('content')
@php use App\Models\Publicacion; @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-book-open mr-2"></i>Publicaciones del colegio (portal de padres)</h4>
            @puede('publicaciones', 'crear')
                <a href="{{ route('publicaciones.create') }}" class="btn btn-primary-modern"><i class="fas fa-plus mr-1"></i>Nueva publicación</a>
            @endpuede
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <form method="GET" class="form-inline mb-3">
                <select name="tipo" class="form-control form-control-sm mr-2 js-select2">
                    <option value="">Todos los tipos</option>
                    @foreach(Publicacion::TIPOS as $k => [$t])<option value="{{ $k }}" {{ request('tipo') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                </select>
                <input type="number" name="gestion" value="{{ request('gestion') }}" class="form-control form-control-sm mr-2" placeholder="Gestión" style="width:100px;">
                <button class="btn btn-sm btn-secondary mr-3"><i class="fas fa-filter"></i></button>
            </form>
            @puede('publicaciones', 'crear')
            <form method="POST" action="{{ route('publicaciones.duplicar') }}" class="form-inline mb-3" onsubmit="return confirm('Se copian todas las publicaciones de esa gestión a la siguiente, ocultas hasta revisarlas. ¿Continuar?')">
                @csrf
                <label class="small mr-2">Copiar las publicaciones de la gestión</label>
                <input type="number" name="desde" value="{{ gestion_actual() }}" class="form-control form-control-sm mr-2" style="width:90px;">
                <button class="btn btn-sm btn-outline-primary">a la siguiente</button>
            </form>
            @endpuede
            <table class="table table-sm table-striped">
                <thead><tr><th>Tipo</th><th>Título</th><th>Gestión</th><th>Para</th><th>Formato</th><th>Portal</th><th></th></tr></thead>
                <tbody>
                    @forelse($publicaciones as $p)
                        <tr class="{{ $p->pub_estado ? '' : 'text-muted' }}">
                            <td><i class="{{ Publicacion::TIPOS[$p->pub_tipo][1] }} mr-1"></i>{{ Publicacion::TIPOS[$p->pub_tipo][0] }}</td>
                            <td>{{ $p->pub_titulo }}</td>
                            <td>{{ $p->ges_anio ?: 'Todas' }}</td>
                            <td>{{ $p->cur_codigo ? optional($p->curso)->cur_nombre : ($p->niv_nombre ?: 'Todo el colegio') }}</td>
                            <td>{{ $p->pub_formato }} @if($p->url_archivo)<a href="{{ $p->url_archivo }}" target="_blank"><i class="fas fa-external-link-alt"></i></a>@endif</td>
                            <td>{!! !$p->pub_estado ? '<span class="badge badge-secondary">retirada</span>' : ($p->visible_padres ? '<span class="badge badge-success">visible</span>' : '<span class="badge badge-warning">oculta</span>') !!}</td>
                            <td class="text-nowrap">
                                @puede('publicaciones', 'editar')<a href="{{ route('publicaciones.edit', $p->pub_id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>@endpuede
                                @if($p->pub_estado)
                                    @puede('publicaciones', 'eliminar')
                                    <form method="POST" action="{{ route('publicaciones.destroy', $p->pub_id) }}" class="d-inline" onsubmit="return confirm('¿Retirar del portal?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                    @endpuede
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Todavía no hay publicaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
