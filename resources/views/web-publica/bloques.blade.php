@extends('layouts.app')
@section('title', 'Web pública')

@section('content')
@php use App\Models\LandingBloque; $hoy = now()->startOfDay(); @endphp
<section class="section">
    <div class="section-header"><h1><i class="fas fa-globe mr-2"></i>Web pública</h1></div>
    <div class="section-body">
        @include('web-publica._tabs')
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="list-group">
                    @foreach($secciones as $k => [$titulo, $pagina, $icono])
                        <a href="{{ route('web-publica.bloques', ['seccion' => $k]) }}" class="list-group-item list-group-item-action {{ $k === $seccion ? 'active' : '' }}">
                            <i class="{{ $icono }} mr-1"></i>{{ $titulo }} <small class="d-block {{ $k === $seccion ? '' : 'text-muted' }}">página {{ $pagina }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $secciones[$seccion][0] }}</h4>
                        @puede('web-publica', 'crear')
                        <a href="{{ route('web-publica.bloques.create', ['seccion' => $seccion]) }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Agregar</a>
                        @endpuede
                    </div>
                    <div class="card-body">
                        @if(in_array($seccion, ['AUTORIDAD', 'HITO']) && $bloques->where('lb_activo', 1)->isEmpty())
                            <div class="alert alert-light small"><i class="fas fa-info-circle"></i> Mientras no haya ninguno activo, la web muestra los originales.</div>
                        @endif
                        <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Orden</th><th>Título</th><th>{{ ['AUTORIDAD' => 'Cargo', 'HITO' => 'Año', 'REQUISITO' => 'Tipo'][$seccion] ?? 'Fecha' }}</th><th>Para</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                            @forelse($bloques as $b)
                                @php
                                    $vencido = $b->vigente_hasta && $b->vigente_hasta->lt($hoy);
                                    $futuro = $b->vigente_desde && $b->vigente_desde->gt($hoy);
                                @endphp
                                <tr class="{{ $b->lb_activo && !$vencido ? '' : 'text-muted' }}">
                                    <td>{{ $b->lb_orden }}</td>
                                    <td>
                                        @if($b->url_imagen)<img src="{{ $b->url_imagen }}" alt="" style="height:28px;border-radius:4px" class="mr-1">@endif
                                        {{ $b->lb_titulo }}
                                        @if($b->lb_archivo)<i class="fas fa-paperclip ml-1" title="Tiene archivo"></i>@endif
                                    </td>
                                    <td>{{ $b->lb_subtitulo ?: optional($b->lb_fecha)->format('d/m/Y') }}</td>
                                    <td>{{ $b->niv_nombre ? LandingBloque::NIVELES[$b->niv_nombre] : 'Todos' }}{{ $b->ges_anio ? ' · ' . $b->ges_anio : '' }}</td>
                                    <td class="small">
                                        @if($b->vigente_desde || $b->vigente_hasta)
                                            {{ optional($b->vigente_desde)->format('d/m/Y') ?: '…' }} – {{ optional($b->vigente_hasta)->format('d/m/Y') ?: '…' }}
                                        @else siempre @endif
                                    </td>
                                    <td>
                                        @if(!$b->lb_activo)<span class="badge badge-secondary">oculto</span>
                                        @elseif($vencido)<span class="badge badge-light">vencido</span>
                                        @elseif($futuro)<span class="badge badge-warning">programado</span>
                                        @else<span class="badge badge-success">en la web</span>@endif
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @puede('web-publica', 'editar')
                                        <a href="{{ route('web-publica.bloques.edit', $b->lb_id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        @endpuede
                                        @puede('web-publica', 'eliminar')
                                        <form method="POST" action="{{ route('web-publica.bloques.destroy', $b->lb_id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar «{{ addslashes($b->lb_titulo) }}»?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                        @endpuede
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Todavía no hay ninguno.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
