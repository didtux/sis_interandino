@extends('layouts.app')

{{-- Asistente de paso de año: revisa los puntos de control y ejecuta
     ActivacionGestionService. Si algo falla no cambia nada (transacción). --}}
@section('content')
@php
    $c = $check;
    $items = [
        ['ok' => $c['resultados_pend'] == 0, 'aviso' => true,
         'texto' => "Resultados de fin de año {$c['anterior']}",
         'detalle' => $c['resultados_pend'] ? "{$c['resultados_pend']} estudiantes siguen PENDIENTES (sin resultado calculado o con notas incompletas)." : 'Todos los estudiantes tienen resultado.',
         'link' => route('promocion.index', ['gestion' => $c['anterior']]), 'link_txt' => 'Ir a Promoción'],
        ['ok' => $c['pre_sin_convertir'] == 0, 'aviso' => true,
         'texto' => 'Pre-inscripciones de alumnos antiguos sin inscribir',
         'detalle' => $c['pre_sin_convertir'] ? "{$c['pre_sin_convertir']} pre-inscripciones todavía no son inscripción: esos alumnos NO pasarán de curso." : 'Todas convertidas.',
         'link' => route('preinscripciones.index', ['gestion' => $c['nueva']]), 'link_txt' => 'Ir a Pre-inscripciones'],
        ['ok' => $c['tarifas'] > 0, 'aviso' => false,
         'texto' => "Tarifas {$c['nueva']}", 'detalle' => $c['tarifas'] ? "{$c['tarifas']} tarifas cargadas." : 'No hay tarifas: no se podrán convertir pre-inscripciones.',
         'link' => route('tarifas.index', ['gestion' => $c['nueva']]), 'link_txt' => 'Ir a Tarifas'],
        ['ok' => true, 'aviso' => false, 'texto' => 'Periodos y dimensiones de notas',
         'detalle' => ($c['periodos'] ? "{$c['periodos']} periodos" : 'Sin periodos: se copiarán los de ' . $c['anterior'] . ' con un año más') . ' · ' .
                      ($c['dimensiones'] ? "{$c['dimensiones']} dimensiones" : 'sin dimensiones: se copiarán las de ' . $c['anterior'])],
    ];
@endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header">
            <h4><i class="fas fa-forward mr-2"></i>Paso de año: {{ $c['anterior'] }} → {{ $gestion->ges_anio }}</h4>
        </div>
        <div class="card-body">
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

            <div class="row mb-3">
                <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="h3 mb-0">{{ $c['inscritos'] }}</div><small>inscritos en {{ $c['nueva'] }} (pasan a su curso nuevo)</small></div></div>
                <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="h3 mb-0">{{ $c['nuevos'] }}</div><small>alumnos nuevos pre-inscritos (se crea su ficha)</small></div></div>
                <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="h3 mb-0">{{ $c['sin_reinscribir'] }}</div><small>de {{ $c['anterior'] }} sin inscripción {{ $c['nueva'] }}</small></div></div>
            </div>

            <ul class="list-group mb-4">
                @foreach($items as $it)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas {{ $it['ok'] ? 'fa-check-circle text-success' : ($it['aviso'] ? 'fa-exclamation-triangle text-warning' : 'fa-times-circle text-danger') }} mr-2"></i>
                            <strong>{{ $it['texto'] }}</strong>
                            <div class="small text-muted ml-4">{{ $it['detalle'] }}</div>
                        </div>
                        @isset($it['link'])<a href="{{ $it['link'] }}" class="btn btn-sm btn-outline-primary">{{ $it['link_txt'] }}</a>@endisset
                    </li>
                @endforeach
            </ul>

            @can('gestion.activar')
            <form method="POST" action="{{ route('gestiones.activar', $gestion->ges_id) }}" class="border rounded p-3 bg-light">
                @csrf
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="ocultar" name="ocultar_no_reinscritos" value="1">
                    <label class="custom-control-label" for="ocultar">
                        Ocultar a los {{ $c['sin_reinscribir'] }} estudiantes de {{ $c['anterior'] }} que no se reinscribieron
                        <small class="text-muted d-block">Si no se marca, siguen apareciendo en su curso de {{ $c['anterior'] }}. Se pueden inscribir tarde igual.</small>
                    </label>
                </div>
                <div class="form-inline">
                    <label class="mr-2">Para confirmar, escriba <strong class="mx-1">{{ $gestion->ges_anio }}</strong>:</label>
                    <input type="text" name="confirmacion" class="form-control form-control-sm mr-2" style="width:90px;" autocomplete="off" required>
                    <button class="btn btn-danger" onclick="return confirm('Todo el sistema pasará a la gestión {{ $gestion->ges_anio }}. ¿Continuar?')">
                        <i class="fas fa-forward mr-1"></i>Activar gestión {{ $gestion->ges_anio }}
                    </button>
                </div>
            </form>
            @else
                <div class="alert alert-warning">Su rol no tiene la capacidad «Activar la gestión siguiente». Puede revisar la lista, pero la activación la hace quien la tenga.</div>
            @endcan
            <a href="{{ route('gestiones.index') }}" class="btn btn-secondary mt-3">Volver</a>
        </div>
    </div>
</div>
@endsection
