@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-sliders-h mr-2"></i>Parámetros del colegio</h4></div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    <p class="text-muted small">Reglas que define el colegio. Los marcados "A DEFINIR" tienen un valor provisorio.</p>
                    <form method="POST" action="{{ route('parametros.guardar') }}">
                        @csrf
                        <table class="table table-sm">
                            <tbody>
                                @foreach($parametros as $p)
                                    <tr>
                                        <td style="width:60%;">
                                            <strong>{{ $p->param_descripcion }}</strong>
                                            <div class="small text-muted">{{ $p->param_clave }}</div>
                                        </td>
                                        <td>
                                            @if(isset($opciones[$p->param_clave]))
                                                <select name="param[{{ $p->param_clave }}]" class="form-control form-control-sm js-select2">
                                                    @foreach($opciones[$p->param_clave] as $v => $t)
                                                        <option value="{{ $v }}" {{ (string) $p->param_valor === (string) $v ? 'selected' : '' }}>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" name="param[{{ $p->param_clave }}]" class="form-control form-control-sm" value="{{ $p->param_valor }}"
                                                       @if(in_array($p->param_clave, $bloqueados ?? [])) disabled title="Requiere la capacidad «Fijar el monto de la reserva»" @endif>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @puede('parametros', 'editar')
                            <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                        @endpuede
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
