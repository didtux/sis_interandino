@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-print mr-2 text-info"></i>Historial de Reimpresiones de Boletines</h5>
            <small class="text-muted">Gestión {{ $gestion }}</small>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

            {{-- Tarjetas resumen --}}
            <div class="row mb-3">
                <div class="col-md-3"><div class="p-2 border rounded text-center"><div class="text-muted small">Descargas válidas</div><h4 class="mb-0">{{ $resumen->total_validas ?? 0 }}</h4></div></div>
                <div class="col-md-3"><div class="p-2 border rounded text-center" style="background:#fef5e7;"><div class="text-muted small">Cobrables pendientes</div><h4 class="mb-0" style="color:#d35400;">{{ $resumen->cobrables_pendientes ?? 0 }}</h4></div></div>
                <div class="col-md-3"><div class="p-2 border rounded text-center" style="background:#e8f5e9;"><div class="text-muted small">Cobradas</div><h4 class="mb-0 text-success">{{ $resumen->cobradas ?? 0 }}</h4></div></div>
                <div class="col-md-3"><div class="p-2 border rounded text-center" style="background:#f5f5f5;"><div class="text-muted small">Anuladas</div><h4 class="mb-0 text-muted">{{ $resumen->anuladas ?? 0 }}</h4></div></div>
            </div>

            {{-- Filtros --}}
            <form method="GET" class="row mb-3" style="font-size:13px;">
                <div class="col-md-2">
                    <label class="small font-weight-bold">Gestión</label>
                    <input type="number" name="gestion" value="{{ $gestion }}" class="form-control form-control-sm" min="2020" max="2099">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">Curso</label>
                    <select name="cur_codigo" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $curCodigo == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Trimestre</label>
                    <select name="trimestre" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        <option value="anual" {{ $trim == 'anual' ? 'selected' : '' }}>Anual</option>
                        <option value="1" {{ $trim == '1' ? 'selected' : '' }}>1°</option>
                        <option value="2" {{ $trim == '2' ? 'selected' : '' }}>2°</option>
                        <option value="3" {{ $trim == '3' ? 'selected' : '' }}>3°</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Estado</label>
                    <select name="estado" class="form-control form-control-sm">
                        <option value="">— Todos —</option>
                        <option value="primera" {{ $estado == 'primera' ? 'selected' : '' }}>Primera (gratis)</option>
                        <option value="cobrable" {{ $estado == 'cobrable' ? 'selected' : '' }}>Cobrable pendiente</option>
                        <option value="cobrado" {{ $estado == 'cobrado' ? 'selected' : '' }}>Cobrada</option>
                        <option value="anulada" {{ $estado == 'anulada' ? 'selected' : '' }}>Anulada</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-info btn-sm mr-1"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                    <a href="{{ route('reimpresiones.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="small font-weight-bold">Desde</label>
                    <input type="date" name="desde" value="{{ $desde }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mt-2">
                    <label class="small font-weight-bold">Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta }}" class="form-control form-control-sm">
                </div>
            </form>

            <div class="table-responsive">
                <table class="modern-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th class="text-center">Trim.</th>
                            <th class="text-center">Copia N°</th>
                            <th>Descargado por</th>
                            <th>IP</th>
                            <th class="text-center">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($descargas as $d)
                            @php
                                $est = $d->estudiante;
                                $rowClass = $d->descarga_anulada ? 'style="opacity:0.55;background:#f5f5f5;"'
                                            : ($d->descarga_cobrable && !$d->pserv_id_cobro ? 'style="background:#fff8e1;"' : '');
                            @endphp
                            <tr {!! $rowClass !!}>
                                <td><small>{{ $d->descarga_fecha->format('d/m/Y H:i') }}</small></td>
                                <td>
                                    @if($est)
                                        <strong>{{ $est->est_apellidos }} {{ $est->est_nombres }}</strong>
                                        <small class="d-block text-muted">{{ $est->est_codigo }}</small>
                                    @else
                                        <span class="text-muted">{{ $d->est_codigo }}</span>
                                    @endif
                                </td>
                                <td><small>{{ optional(optional($est)->curso)->cur_nombre ?? '—' }}</small></td>
                                <td class="text-center">{{ $d->descarga_trimestre ? $d->descarga_trimestre.'°' : 'Anual' }}</td>
                                <td class="text-center"><strong style="font-size:14px;">{{ $d->descarga_numero_copia }}</strong></td>
                                <td><small>{{ $d->descargado_por_nombre ?? '—' }}</small></td>
                                <td><small class="text-muted">{{ $d->descarga_ip }}</small></td>
                                <td class="text-center">
                                    @if($d->descarga_anulada)
                                        <span class="badge badge-secondary">ANULADA</span>
                                        <small class="d-block text-muted" title="{{ $d->descarga_anulada_motivo }}">{{ \Illuminate\Support\Str::limit($d->descarga_anulada_motivo, 25) }}</small>
                                    @elseif($d->pserv_id_cobro)
                                        <span class="badge badge-success">COBRADA</span>
                                        <small class="d-block text-muted">Pago #{{ $d->pserv_id_cobro }}</small>
                                    @elseif($d->descarga_cobrable)
                                        <span class="badge badge-warning">COBRABLE</span>
                                    @else
                                        <span class="badge badge-light border">PRIMERA</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$d->descarga_anulada && !$d->pserv_id_cobro)
                                        @if($d->descarga_cobrable)
                                            <button class="btn btn-action btn-sm" style="background:#27ae60;color:#fff;" data-toggle="modal" data-target="#modalCobrar-{{ $d->descarga_id }}" title="Generar cobro">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-action btn-action-delete btn-sm" data-toggle="modal" data-target="#modalAnular-{{ $d->descarga_id }}" title="Anular">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                    @if($d->descarga_token)
                                        <a href="{{ url('/boletin/validar/'.$d->descarga_token) }}" target="_blank" class="btn btn-action btn-sm" style="background:#3498db;color:#fff;" title="Validar QR">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            {{-- Modal anular --}}
                            @if(!$d->descarga_anulada && !$d->pserv_id_cobro)
                            <div class="modal fade" id="modalAnular-{{ $d->descarga_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('reimpresiones.anular', $d->descarga_id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header" style="background:#c0392b;color:#fff;">
                                                <h5 class="modal-title">Anular descarga</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Vas a anular la descarga de <strong>{{ optional($est)->est_apellidos }} {{ optional($est)->est_nombres }}</strong>
                                                ({{ $d->descarga_trimestre ? 'T'.$d->descarga_trimestre : 'anual' }}, copia N° {{ $d->descarga_numero_copia }}).</p>
                                                <p class="small text-muted">La descarga queda registrada para auditoría pero no contará en el correlativo de copias.</p>
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Motivo <span class="text-danger">*</span></label>
                                                    <textarea name="motivo" rows="2" class="form-control" maxlength="255" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-danger">Anular</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Modal cobrar (solo si es cobrable) --}}
                            @if($d->descarga_cobrable)
                            <div class="modal fade" id="modalCobrar-{{ $d->descarga_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('reimpresiones.cobrar', $d->descarga_id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header" style="background:#27ae60;color:#fff;">
                                                <h5 class="modal-title">Generar cobro de reimpresión</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>{{ optional($est)->est_apellidos }} {{ optional($est)->est_nombres }}</strong> —
                                                Copia N° {{ $d->descarga_numero_copia }} del boletín {{ $d->descarga_trimestre ? 'T'.$d->descarga_trimestre : 'anual' }} de la gestión {{ $d->descarga_gestion }}.</p>
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Monto (Bs)</label>
                                                    <input type="number" name="monto" step="0.01" min="0"
                                                           value="{{ optional(\App\Models\Servicio::where('serv_codigo','REIMPR_BOLETIN')->first())->serv_costo ?? 10 }}"
                                                           class="form-control" required>
                                                    <small class="text-muted">Se generará un cargo PENDIENTE en Pagos > Servicios. La caja lo cobra después.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success">Generar cobro</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endif
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">No hay descargas registradas con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">{{ $descargas->links() }}</div>
        </div>
    </div>
</div>
@endsection
