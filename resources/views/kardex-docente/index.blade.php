@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-folder-open mr-2"></i>Kardex de Docentes</h4>
            <div>
                <a href="{{ route('kardex-docente.reporte-general') }}" target="_blank" class="btn btn-sm btn-danger">
                    <i class="fas fa-file-pdf mr-1"></i>Reporte general
                </a>
                @if($docCod)
                    <a href="{{ route('kardex-docente.reporte', $docCod) }}" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-user-tag mr-1"></i>Reporte del docente
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <form method="GET" class="row mb-3">
                <div class="col-md-6">
                    <label class="small">Docente</label>
                    <select name="doc_codigo" class="form-control select2-doc" style="width:100%;" onchange="this.form.submit()">
                        <option value="">— Seleccione un docente —</option>
                        @foreach($docentes as $d)
                            <option value="{{ $d->doc_codigo }}" {{ $docCod === $d->doc_codigo ? 'selected':'' }}>{{ $d->doc_apellidos }} {{ $d->doc_nombres }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="tab" value="{{ $tab }}">
            </form>

            @if(!$docCod)
                <div class="text-center text-muted py-4">
                    <i class="fas fa-user-tag" style="font-size:2rem;opacity:0.3;"></i>
                    <p class="mt-2">Selecciona un docente para ver/registrar su información.</p>
                </div>
            @else
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><a class="nav-link {{ $tab=='kardex' ? 'active':'' }}" href="?doc_codigo={{ $docCod }}&tab=kardex"><i class="fas fa-book mr-1"></i>Documentos / Kardex <span class="badge badge-secondary ml-1">{{ $kardex->total() ?? 0 }}</span></a></li>
                    <li class="nav-item"><a class="nav-link {{ $tab=='asistencia' ? 'active':'' }}" href="?doc_codigo={{ $docCod }}&tab=asistencia"><i class="fas fa-clipboard-check mr-1"></i>Asistencia <span class="badge badge-secondary ml-1">{{ $asistencias->total() ?? 0 }}</span></a></li>
                    <li class="nav-item"><a class="nav-link {{ $tab=='disciplinario' ? 'active':'' }}" href="?doc_codigo={{ $docCod }}&tab=disciplinario"><i class="fas fa-exclamation-triangle mr-1"></i>Disciplinario <span class="badge badge-danger ml-1">{{ $disciplinarios->total() ?? 0 }}</span></a></li>
                </ul>

                {{-- KARDEX --}}
                @if($tab=='kardex')
                    <button class="btn btn-sm btn-primary mb-2" data-toggle="modal" data-target="#mKardex"><i class="fas fa-plus"></i> Nuevo documento</button>
                    <table class="table table-sm table-striped" style="font-size:13px;">
                        <thead>
                            <tr><th>Tipo</th><th>Título</th><th>Solicitado</th><th>Entrega</th><th>Estado</th><th>Archivo</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($kardex as $k)
                                <tr>
                                    <td><span class="badge badge-info">{{ $k->kdx_tipo_documento }}</span></td>
                                    <td><strong>{{ $k->kdx_titulo }}</strong><small class="d-block text-muted">{{ $k->kdx_descripcion }}</small></td>
                                    <td>{{ $k->kdx_fecha_solicitud->format('d/m/Y') }}</td>
                                    <td>{{ $k->kdx_fecha_entrega ? $k->kdx_fecha_entrega->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @php $cls = ['PENDIENTE'=>'warning','ENTREGADO'=>'success','OBSERVADO'=>'info','RECHAZADO'=>'danger'][$k->kdx_estado] ?? 'secondary'; @endphp
                                        <span class="badge badge-{{ $cls }}">{{ $k->kdx_estado }}</span>
                                        @if($k->kdx_fecha_recibido)<small class="d-block text-muted">{{ $k->kdx_fecha_recibido->format('d/m/Y') }}</small>@endif
                                    </td>
                                    <td>
                                        @if($k->kdx_archivo)
                                            <a href="{{ asset('uploads/kardex-docente/'.$k->kdx_archivo) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i></a>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#mEstado{{ $k->kdx_id }}"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <div class="modal fade" id="mEstado{{ $k->kdx_id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('kardex-docente.kardex.estado', $k->kdx_id) }}" method="POST">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header"><h5>Actualizar estado · {{ $k->kdx_titulo }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Estado</label>
                                                        <select name="kdx_estado" class="form-control" required>
                                                            @foreach(['PENDIENTE','ENTREGADO','OBSERVADO','RECHAZADO'] as $e)
                                                                <option value="{{ $e }}" {{ $k->kdx_estado===$e ? 'selected':'' }}>{{ $e }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Observación</label>
                                                        <textarea name="kdx_observacion" class="form-control" rows="3">{{ $k->kdx_observacion }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $kardex->appends(request()->query())->links() }}
                @endif

                {{-- ASISTENCIA --}}
                @if($tab=='asistencia')
                    <button class="btn btn-sm btn-primary mb-2" data-toggle="modal" data-target="#mAsist"><i class="fas fa-plus"></i> Registrar manual</button>
                    <small class="ml-2 text-muted">El docente también puede marcar con QR desde su panel.</small>
                    <table class="table table-sm table-striped mt-2" style="font-size:13px;">
                        <thead><tr><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Origen</th><th>Observación</th></tr></thead>
                        <tbody>
                            @forelse($asistencias as $a)
                                <tr>
                                    <td>{{ $a->dasist_fecha->format('d/m/Y') }}</td>
                                    <td>{{ substr($a->dasist_hora, 0, 5) }}</td>
                                    <td><span class="badge badge-secondary">{{ $a->dasist_tipo }}</span></td>
                                    <td><span class="badge badge-{{ $a->dasist_origen=='QR'?'success':'light border' }}">{{ $a->dasist_origen }}</span></td>
                                    <td>{{ $a->dasist_observacion }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Sin registros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $asistencias->appends(request()->query())->links() }}
                @endif

                {{-- DISCIPLINARIO --}}
                @if($tab=='disciplinario')
                    <button class="btn btn-sm btn-danger mb-2" data-toggle="modal" data-target="#mDisc"><i class="fas fa-plus"></i> Registrar incidencia</button>
                    <table class="table table-sm table-striped" style="font-size:13px;">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th>Gravedad</th><th>Descripción</th><th>Evidencia</th><th>Registrado</th></tr></thead>
                        <tbody>
                            @forelse($disciplinarios as $d)
                                <tr style="background:{{ $d->disc_gravedad=='GRAVE'?'#ffe6e6':($d->disc_gravedad=='MEDIA'?'#fff3cd':'') }};">
                                    <td>{{ $d->disc_fecha->format('d/m/Y') }}</td>
                                    <td><span class="badge badge-dark">{{ $d->disc_tipo }}</span></td>
                                    <td>
                                        @php $g=['LEVE'=>'secondary','MEDIA'=>'warning','GRAVE'=>'danger'][$d->disc_gravedad]; @endphp
                                        <span class="badge badge-{{ $g }}">{{ $d->disc_gravedad }}</span>
                                    </td>
                                    <td>{{ $d->disc_descripcion }}</td>
                                    <td>
                                        @if($d->disc_evidencia)<a href="{{ asset('uploads/disciplinario/'.$d->disc_evidencia) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i></a>@endif
                                    </td>
                                    <td><small>{{ optional($d->disc_registrado_fecha)->format('d/m/Y H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $disciplinarios->appends(request()->query())->links() }}
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Modal Kardex --}}
@if($docCod)
<div class="modal fade" id="mKardex" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('kardex-docente.kardex.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="doc_codigo" value="{{ $docCod }}">
            <div class="modal-content">
                <div class="modal-header"><h5>Nuevo documento</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group"><label>Tipo *</label>
                        <select name="kdx_tipo_documento" class="form-control" required>
                            <option value="EXAMENES">Exámenes</option>
                            <option value="PDC">PDC</option>
                            <option value="CUADERNO_PEDAGOGICO">Cuaderno Pedagógico</option>
                            <option value="PLAN_ANUAL">Plan Anual</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Título *</label><input type="text" name="kdx_titulo" class="form-control" required maxlength="150"></div>
                    <div class="form-group"><label>Descripción</label><textarea name="kdx_descripcion" class="form-control" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col"><label>Fecha solicitud *</label><input type="date" name="kdx_fecha_solicitud" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col"><label>Fecha entrega pactada</label><input type="date" name="kdx_fecha_entrega" class="form-control"></div>
                    </div>
                    <div class="form-group mt-2"><label>Archivo</label><input type="file" name="kdx_archivo" class="form-control"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Registrar</button></div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Asistencia --}}
<div class="modal fade" id="mAsist" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('kardex-docente.asistencia.store') }}" method="POST">
            @csrf
            <input type="hidden" name="doc_codigo" value="{{ $docCod }}">
            <div class="modal-content">
                <div class="modal-header"><h5>Registrar asistencia manual</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col"><label>Fecha</label><input type="date" name="dasist_fecha" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col"><label>Hora</label><input type="time" name="dasist_hora" class="form-control" value="{{ date('H:i') }}" required></div>
                    </div>
                    <div class="form-group mt-2"><label>Tipo</label>
                        <select name="dasist_tipo" class="form-control">
                            <option value="ENTRADA">Entrada</option>
                            <option value="SALIDA">Salida</option>
                            <option value="UNICO">Único</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Observación</label><input type="text" name="dasist_observacion" class="form-control"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Registrar</button></div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Disciplinario --}}
<div class="modal fade" id="mDisc" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('kardex-docente.disciplinario.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="doc_codigo" value="{{ $docCod }}">
            <div class="modal-content">
                <div class="modal-header" style="background:#c0392b;color:#fff;"><h5>Registrar incidencia disciplinaria</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col"><label>Fecha</label><input type="date" name="disc_fecha" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col"><label>Tipo</label>
                            <select name="disc_tipo" class="form-control">
                                <option value="FALTA">Falta</option>
                                <option value="UNIFORME">Uniforme</option>
                                <option value="ATRASO">Atraso</option>
                                <option value="ACADEMICO">Académico</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="col"><label>Gravedad</label>
                            <select name="disc_gravedad" class="form-control">
                                <option value="LEVE">Leve</option><option value="MEDIA">Media</option><option value="GRAVE">Grave</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-2"><label>Descripción *</label><textarea name="disc_descripcion" class="form-control" rows="3" required maxlength="500"></textarea></div>
                    <div class="form-group"><label>Evidencia</label><input type="file" name="disc_evidencia" class="form-control"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-danger">Registrar</button></div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
$(document).ready(function(){
    $('.select2-doc').select2({ theme:'bootstrap4', width:'100%' });
});
</script>
@endsection
