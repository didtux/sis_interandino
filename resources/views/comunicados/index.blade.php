@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-bullhorn mr-2 text-primary"></i>Comunicados / Documentación a Docentes</h4>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNuevoCom">
                <i class="fas fa-plus mr-1"></i>Nuevo comunicado
            </button>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

            @forelse($comunicados as $com)
                @php
                    $limite = $com->com_fecha_limite;
                    $total = $com->destinatarios->count();
                    $entregados = $com->destinatarios->filter(fn($d) => $d->cd_archivo)->count();
                    $enFecha = 0; $fuera = 0; $noEntrego = 0; $pendiente = 0;
                    foreach ($com->destinatarios as $d) {
                        $e = $d->estadoEntrega($limite);
                        if ($e === 'EN FECHA') $enFecha++;
                        elseif ($e === 'FUERA DE FECHA') $fuera++;
                        elseif ($e === 'NO ENTREGÓ') $noEntrego++;
                        else $pendiente++;
                    }
                @endphp
                <div class="card mb-3 {{ $com->com_estado ? '' : 'border-danger' }}">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:6px;">
                        <div>
                            <strong>{{ $com->com_titulo }}</strong>
                            @if(!$com->com_estado)<span class="badge badge-danger ml-1">ANULADO</span>@endif
                            <small class="text-muted d-block">
                                {{ $com->com_para_todos ? 'Todos los docentes' : 'Docentes seleccionados' }}
                                · Límite: {{ $limite ? $limite->format('d/m/Y') : 'sin fecha' }}
                                · Creado: {{ $com->com_fecha->format('d/m/Y') }} por {{ $com->com_creado_por_nombre }}
                            </small>
                        </div>
                        <div>
                            @if($com->com_archivo)
                                <a href="{{ asset('storage/'.$com->com_archivo) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-paperclip"></i> Adjunto</a>
                            @endif
                            <a href="{{ route('comunicados.reporte', $com->com_id) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf"></i> Reporte</a>
                            @if($com->com_estado)
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#modalAnular{{ $com->com_id }}"><i class="fas fa-ban"></i> Anular</button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body py-2">
                        @if($com->com_descripcion)<p class="mb-2 small">{{ $com->com_descripcion }}</p>@endif
                        <div class="mb-2">
                            <span class="badge badge-success">En fecha: {{ $enFecha }}</span>
                            <span class="badge badge-warning">Fuera de fecha: {{ $fuera }}</span>
                            <span class="badge badge-danger">No entregó: {{ $noEntrego }}</span>
                            <span class="badge badge-secondary">Pendiente: {{ $pendiente }}</span>
                            <span class="badge badge-info">Entregados: {{ $entregados }}/{{ $total }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0" style="font-size:12.5px;">
                                <thead><tr><th>Docente</th><th>Estado</th><th>Entrega</th><th>Archivo</th><th>Observación</th></tr></thead>
                                <tbody>
                                    @foreach($com->destinatarios as $d)
                                        @php
                                            $e = $d->estadoEntrega($limite);
                                            $cls = ['EN FECHA'=>'success','FUERA DE FECHA'=>'warning','NO ENTREGÓ'=>'danger','PENDIENTE'=>'secondary'][$e] ?? 'secondary';
                                        @endphp
                                        <tr>
                                            <td>{{ optional($d->docente)->doc_apellidos }} {{ optional($d->docente)->doc_nombres }}</td>
                                            <td><span class="badge badge-{{ $cls }}">{{ $e }}</span></td>
                                            <td>{{ $d->cd_fecha_entrega ? $d->cd_fecha_entrega->format('d/m/Y H:i') : '-' }}</td>
                                            <td>@if($d->cd_archivo)<a href="{{ asset('storage/'.$d->cd_archivo) }}" target="_blank"><i class="fas fa-file-download"></i></a>@else - @endif</td>
                                            <td>
                                                <form action="{{ route('comunicados.observar', $d->cd_id) }}" method="POST" class="form-inline">
                                                    @csrf
                                                    <input type="text" name="cd_observacion" value="{{ $d->cd_observacion }}" class="form-control form-control-sm mr-1" style="width:160px;" placeholder="Observación...">
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Modal anular --}}
                <div class="modal fade" id="modalAnular{{ $com->com_id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('comunicados.anular', $com->com_id) }}" method="POST">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white"><h5>Anular comunicado</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
                                <div class="modal-body">
                                    <p>{{ $com->com_titulo }}</p>
                                    <div class="form-group">
                                        <label>Motivo de anulación *</label>
                                        <textarea name="com_motivo_anulacion" class="form-control" rows="2" required maxlength="255"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button class="btn btn-danger">Anular</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-4">No hay comunicados registrados.</p>
            @endforelse

            {{ $comunicados->links() }}
        </div>
    </div>
</div>

{{-- Modal nuevo comunicado --}}
<div class="modal fade" id="modalNuevoCom" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('comunicados.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5><i class="fas fa-bullhorn mr-1"></i>Nuevo comunicado</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="com_titulo" class="form-control" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="com_descripcion" class="form-control" rows="3" maxlength="2000"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Fecha límite</label>
                            <input type="date" name="com_fecha_limite" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Adjunto (opcional)</label>
                            <input type="file" name="com_archivo" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Requiere que el docente suba archivo</label>
                            <div><label><input type="checkbox" name="com_requiere_archivo" value="1" checked> Sí</label></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Destinatarios *</label>
                        <select name="destino" id="destinoSel" class="form-control">
                            <option value="todos">Todos los docentes</option>
                            <option value="seleccion">Docentes seleccionados</option>
                        </select>
                    </div>
                    <div class="form-group" id="grpDocentes" style="display:none;">
                        <label>Seleccionar docentes</label>
                        <select name="docentes[]" class="form-control select2-docs" multiple style="width:100%;">
                            @foreach($docentes as $d)
                                <option value="{{ $d->doc_codigo }}">{{ $d->doc_apellidos }} {{ $d->doc_nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Enviar comunicado</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('.select2-docs').select2({ theme:'bootstrap4', width:'100%', dropdownParent: $('#modalNuevoCom'), placeholder:'Buscar docentes...' });
    $('#destinoSel').on('change', function(){
        $('#grpDocentes').toggle($(this).val() === 'seleccion');
    });
});
</script>
@endsection
