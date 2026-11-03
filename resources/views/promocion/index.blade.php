@extends('layouts.app')

@section('content')
@php
    $colores = ['PENDIENTE' => 'secondary', 'PROMOVIDO' => 'success', 'REPROBADO' => 'danger', 'EGRESADO' => 'primary', 'RETIRADO' => 'dark'];
    $puedeDecidir = auth()->user()->tieneCapacidad('promocion.decidir');
@endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-level-up-alt mr-2"></i>Promoción — gestión {{ $gestion }}</h4>
            @puede('promocion', 'editar')
            <form method="POST" action="{{ route('promocion.calcular') }}" onsubmit="return confirm('Se recalcula el resultado de {{ $curso ? 'este curso' : 'todos los cursos' }} con las notas actuales (no toca los corregidos a mano). ¿Continuar?')">
                @csrf <input type="hidden" name="gestion" value="{{ $gestion }}"><input type="hidden" name="cur_codigo" value="{{ $curso }}">
                <button class="btn btn-primary-modern"><i class="fas fa-calculator mr-1"></i>Calcular resultados {{ $curso ? 'del curso' : 'de todos los cursos' }}</button>
            </form>
            @endpuede
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <p class="text-muted small">
                Mismo cálculo que el boletín: promedio anual de cada materia. Reprueba el año quien tiene
                <strong>{{ parametro('reprobacion_materias', 3) }}</strong> o más materias bajo <strong>{{ parametro('nota_minima_aprobacion', 51) }}</strong>
                (Parametrización → Parámetros). Con trimestres sin notas el resultado queda PENDIENTE.
            </p>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Curso</th>@foreach($colores as $r => $c)<th class="text-center">{{ $r }}</th>@endforeach<th></th></tr></thead>
                    <tbody>
                        @foreach($cursos as $c)
                            @php $res = $resumen[$c->cur_codigo] ?? collect(); @endphp
                            <tr class="{{ $curso === $c->cur_codigo ? 'table-info' : '' }}">
                                <td>{{ $c->cur_nombre }}</td>
                                @foreach($colores as $r => $col)<td class="text-center">{{ $res[$r] ?? '' }}</td>@endforeach
                                <td><a href="{{ route('promocion.index', ['gestion' => $gestion, 'cur_codigo' => $c->cur_codigo]) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($curso)
                <h5>{{ optional($cursos->firstWhere('cur_codigo', $curso))->cur_nombre }}</h5>
                <table class="table table-sm table-striped">
                    <thead><tr><th>Estudiante</th><th class="text-center">Promedio</th><th class="text-center">Materias reprobadas</th><th>Resultado</th>@if($puedeDecidir)<th>Corregir</th>@endif</tr></thead>
                    <tbody>
                        @foreach($filas as $f)
                            <tr>
                                <td>{{ optional($f->estudiante)->est_apellidos }} {{ optional($f->estudiante)->est_nombres }}</td>
                                <td class="text-center">{{ $f->eg_promedio_final !== null ? number_format($f->eg_promedio_final, 2) : '—' }}</td>
                                <td class="text-center">{{ $f->eg_materias_reprobadas ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $colores[$f->eg_resultado] ?? 'light' }}">{{ $f->eg_resultado }}</span>
                                    @if($f->eg_resultado_manual)<span class="badge badge-warning" title="{{ $f->eg_resultado_motivo }}">MANUAL</span>@endif
                                </td>
                                @if($puedeDecidir)
                                <td>
                                    <form method="POST" action="{{ route('promocion.decidir', $f->eg_id) }}" class="form-inline" onsubmit="if (this.automatico.value === '1') return true; var m = prompt('Motivo de la corrección:'); if (!m) return false; this.motivo.value = m; return true;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="motivo"><input type="hidden" name="automatico" value="0">
                                        <select name="eg_resultado" class="form-control form-control-sm mr-1 js-select2">
                                            @foreach(['PROMOVIDO', 'REPROBADO', 'EGRESADO', 'RETIRADO'] as $r)
                                                <option value="{{ $r }}" {{ $f->eg_resultado === $r ? 'selected' : '' }}>{{ $r }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary">Fijar</button>
                                        @if($f->eg_resultado_manual)
                                            <button class="btn btn-sm btn-link" onclick="this.form.automatico.value='1'">volver a automático</button>
                                        @endif
                                    </form>
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
