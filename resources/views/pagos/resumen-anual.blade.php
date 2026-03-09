@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header" style="background-color: #6777ef;">
            <h4 style="color: white; margin: 0;"><i class="fas fa-chart-bar mr-2"></i>Resumen Anual de Mensualidades</h4>
        </div>
        <div class="card-body">
            <form id="formResumen" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label>Año</label>
                        <select name="year" class="form-control">
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (request('year', date('Y')) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label>Curso</label>
                        <div class="d-flex">
                            <button type="button" id="btnTodosCursos" class="btn btn-outline-primary mr-2" onclick="seleccionarTodosCursos()">
                                <i class="fas fa-list"></i> Todos
                            </button>
                            <select name="cur_codigo" id="cur_codigo" class="form-control" style="flex: 1;">
                                <option value=""></option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>{{ $curso->cur_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-danger mr-2" onclick="generarReporte('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-success" onclick="generarReporte('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.select2-selection__clear {
    margin-right: 10px !important;
}
#btnTodosCursos.active {
    background-color: #6777ef;
    color: white;
    border-color: #6777ef;
}
</style>
<script>
function seleccionarTodosCursos() {
    const btn = $('#btnTodosCursos');
    const select = $('#cur_codigo');
    
    if (btn.hasClass('active')) {
        // Activar select
        btn.removeClass('active');
        select.prop('disabled', false).select2({
            placeholder: 'Seleccione un curso',
            allowClear: true,
            width: '100%'
        });
    } else {
        // Desactivar select y seleccionar todos
        btn.addClass('active');
        select.val('').trigger('change').select2('destroy').prop('disabled', true);
    }
}

function generarReporte(tipo) {
    const form = document.getElementById('formResumen');
    if (tipo === 'pdf') {
        form.action = '{{ route("pagos.resumen-anual-pdf") }}';
        form.target = '_blank';
    } else {
        form.action = '{{ route("pagos.resumen-anual-excel") }}';
        form.target = '_self';
    }
    form.submit();
}

$(document).ready(function() {
    $('#cur_codigo').select2({
        placeholder: 'Seleccione un curso',
        allowClear: true,
        width: '100%'
    });
    
    // Verificar estado inicial
    if (!$('#cur_codigo').val()) {
        $('#btnTodosCursos').addClass('active');
        $('#cur_codigo').prop('disabled', true).select2('destroy');
    }
    
    $('#cur_codigo').on('change', function() {
        if ($(this).val()) {
            $('#btnTodosCursos').removeClass('active');
        }
    });
});
</script>
@endsection
