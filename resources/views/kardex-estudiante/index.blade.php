@extends('layouts.app')

@section('content')
@php
    $u = auth()->user();
    $esDir = in_array($u->rol_id, [1,4]);
    $esDoc = $u->us_entidad_tipo === 'docente';
@endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-folder-open mr-2 text-info"></i>Kardex de Estudiantes</h5>
            <div>
                @if($estCodigo || $curCodigo)
                <a href="{{ route('kardex-estudiante.reporte-pdf', request()->only(['est_codigo','cur_codigo'])) }}"
                   target="_blank" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i>PDF
                </a>
                @endif
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNueva">
                    <i class="fas fa-plus mr-1"></i>Nueva anotación
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success py-2"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</div>
            @endif

            {{-- Filtros --}}
            <form method="GET" class="row mb-3" style="font-size:13px;">
                <div class="col-md-3">
                    <label class="small font-weight-bold">Curso</label>
                    <select name="cur_codigo" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">— Todos —</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $curCodigo == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">Estudiante</label>
                    <select name="est_codigo" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        @foreach($estudiantes as $e)
                            <option value="{{ $e->est_codigo }}" {{ $estCodigo == $e->est_codigo ? 'selected' : '' }}>{{ $e->est_apellido_paterno }} {{ $e->est_nombres }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Tipo</label>
                    <select name="tipo" class="form-control form-control-sm">
                        <option value="">—</option>
                        @foreach(['ACADEMICO','CONDUCTUAL','FELICITACION','OBSERVACION','COMPROMISO'] as $t)
                            <option value="{{ $t }}" {{ $tipo == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Desde</label>
                    <input type="date" name="fecha_ini" value="{{ $fechaIni }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                    <a href="{{ route('kardex-estudiante.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="modern-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th>Docente</th>
                            <th class="text-center">Padre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $r)
                            @php
                                $color = ['POSITIVO'=>'#27ae60','NEGATIVO'=>'#e74c3c','NEUTRO'=>'#7f8c8d'][$r->ek_categoria ?? ''] ?? '#34495e';
                                $tipoBadge = ['FELICITACION'=>'badge-success','ACADEMICO'=>'badge-primary','CONDUCTUAL'=>'badge-warning','OBSERVACION'=>'badge-secondary','COMPROMISO'=>'badge-info'][$r->ek_tipo] ?? 'badge-light';
                            @endphp
                            <tr style="border-left:4px solid {{ $color }};">
                                <td>{{ $r->ek_fecha->format('d/m/Y') }}</td>
                                <td><strong>{{ optional($r->estudiante)->est_apellido_paterno }} {{ optional($r->estudiante)->est_nombres }}</strong></td>
                                <td><small>{{ optional($r->curso)->cur_nombre }}</small></td>
                                <td><span class="badge {{ $tipoBadge }}">{{ $r->ek_tipo }}</span></td>
                                <td><strong>{{ $r->ek_titulo }}</strong></td>
                                <td style="max-width:300px;"><small>{{ \Illuminate\Support\Str::limit($r->ek_descripcion, 120) }}</small></td>
                                <td><small>{{ optional($r->docente)->doc_apellidos ?? '—' }}</small></td>
                                <td class="text-center">
                                    @if(!$r->ek_visible_padre)
                                        <span title="Oculto al padre" class="text-muted"><i class="fas fa-eye-slash"></i></span>
                                    @elseif($r->ek_visto_padre)
                                        <i class="fas fa-check-circle text-success" title="Visto {{ optional($r->ek_visto_padre_at)->format('d/m/Y H:i') }}"></i>
                                    @else
                                        <i class="far fa-clock text-muted" title="No visto"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($esDir || $r->ek_registrado_por == $u->us_id)
                                        <button class="btn btn-action btn-action-edit btn-sm" data-toggle="modal"
                                                data-target="#modalEdit-{{ $r->ek_id }}" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if($esDir)
                                        <form action="{{ route('kardex-estudiante.destroy', $r->ek_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete btn-sm" onclick="return confirm('¿Eliminar anotación?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            {{-- Modal edición --}}
                            @if($esDir || $r->ek_registrado_por == $u->us_id)
                            <div class="modal fade" id="modalEdit-{{ $r->ek_id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form action="{{ route('kardex-estudiante.update', $r->ek_id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar anotación</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label class="small">Título</label>
                                                    <input type="text" name="ek_titulo" value="{{ $r->ek_titulo }}" class="form-control" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label class="small">Tipo</label>
                                                        <select name="ek_tipo" class="form-control">
                                                            @foreach(['ACADEMICO','CONDUCTUAL','FELICITACION','OBSERVACION','COMPROMISO'] as $t)
                                                                <option value="{{ $t }}" {{ $r->ek_tipo == $t ? 'selected' : '' }}>{{ $t }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label class="small">Categoría</label>
                                                        <select name="ek_categoria" class="form-control">
                                                            <option value="">—</option>
                                                            @foreach(['POSITIVO','NEUTRO','NEGATIVO'] as $c)
                                                                <option value="{{ $c }}" {{ $r->ek_categoria == $c ? 'selected' : '' }}>{{ $c }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="small">Descripción</label>
                                                    <textarea name="ek_descripcion" rows="3" class="form-control">{{ $r->ek_descripcion }}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label class="small">Acuerdo / Compromiso</label>
                                                    <textarea name="ek_acuerdo" rows="2" class="form-control">{{ $r->ek_acuerdo }}</textarea>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="ek_visible_padre" value="1" {{ $r->ek_visible_padre ? 'checked' : '' }} class="form-check-input" id="vp-{{ $r->ek_id }}">
                                                    <label for="vp-{{ $r->ek_id }}" class="form-check-label small">Visible al padre</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                <button type="submit" class="btn btn-success">Guardar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">Sin anotaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">{{ $registros->links() }}</div>
        </div>
    </div>
</div>

{{-- Modal Nueva --}}
<div class="modal fade" id="modalNueva" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('kardex-estudiante.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="background:#17a2b8;color:#fff;">
                    <h5 class="modal-title"><i class="fas fa-plus mr-1"></i>Nueva anotación</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Estudiante <span class="text-danger">*</span></label>
                            <select name="est_codigo" class="form-control select2-nuevo" required>
                                <option value="">— Seleccione —</option>
                                @php
                                    $estsNuevo = $estudiantes->isNotEmpty() ? $estudiantes : \App\Models\Inscripcion::where('insc_gestion', date('Y'))->where('insc_estado',1)->with('estudiante')->get()->pluck('estudiante')->filter()->unique('est_codigo')->values();
                                @endphp
                                @foreach($estsNuevo as $e)
                                    <option value="{{ $e->est_codigo }}">{{ $e->est_apellido_paterno }} {{ $e->est_nombres }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="ek_fecha" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold">Tipo <span class="text-danger">*</span></label>
                            <select name="ek_tipo" class="form-control" required>
                                @foreach(['ACADEMICO','CONDUCTUAL','FELICITACION','OBSERVACION','COMPROMISO'] as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="ek_titulo" class="form-control" maxlength="150" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold">Categoría</label>
                            <select name="ek_categoria" class="form-control">
                                <option value="">—</option>
                                <option value="POSITIVO">POSITIVO</option>
                                <option value="NEUTRO">NEUTRO</option>
                                <option value="NEGATIVO">NEGATIVO</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold">Visible al padre</label>
                            <div><label class="switch-label" style="margin-top:6px;">
                                <input type="checkbox" name="ek_visible_padre" value="1" checked>
                                Sí, mostrar al padre
                            </label></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Descripción</label>
                        <textarea name="ek_descripcion" rows="3" class="form-control" maxlength="2000"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Acuerdo / Compromiso (opcional)</label>
                        <textarea name="ek_acuerdo" rows="2" class="form-control" maxlength="2000"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Archivo adjunto (opcional)</label>
                        <input type="file" name="ek_archivo" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    if ($('.select2-nuevo').length) {
        $('.select2-nuevo').select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $('#modalNueva') });
    }
});
</script>
@endsection
