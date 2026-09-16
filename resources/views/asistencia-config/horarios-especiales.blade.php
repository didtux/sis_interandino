@extends('layouts.app')

@section('content')
<div class="section-body">

    {{-- Qué resuelve esta pantalla, para quien la abre por primera vez --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="mb-2"><i class="fas fa-snowflake text-primary mr-1"></i> Horarios especiales por rango de fechas</h6>
                    <p class="mb-2 text-muted" style="font-size:.88rem">
                        La configuración de horarios define <strong>un solo horario por categoría</strong>, sin fechas.
                        Acá se cargan los periodos en que rigió otra cosa:
                    </p>
                    <div class="d-flex flex-wrap" style="gap:.5rem 1.5rem; font-size:.86rem">
                        <div><span class="badge badge-info">Horario especial</span>
                            <span class="text-muted">se entra a otra hora durante el rango.</span></div>
                        <div><span class="badge badge-secondary">Receso sin clases</span>
                            <span class="text-muted">vacaciones: no cuentan como días hábiles.</span></div>
                    </div>
                </div>
                <div class="col-md-4 border-left">
                    <p class="mb-0 text-muted" style="font-size:.83rem">
                        <i class="fas fa-info-circle text-info"></i>
                        Los atrasos se calculan al imprimir, no están guardados: al cargar un rango,
                        <strong>los boletines y reportes ya emitidos se corrigen solos</strong>,
                        sin recalcular nada ni tocar las marcaciones.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Gestión {{ $gestion }}</h4>
            <div class="d-flex align-items-center">
                <form method="GET" class="form-inline mr-2">
                    <label class="mr-1 mb-0 text-muted">Gestión</label>
                    <select name="gestion" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach($gestiones as $g)
                            <option value="{{ $g }}" @if($g == $gestion) selected @endif>{{ $g }}</option>
                        @endforeach
                    </select>
                </form>
                <button class="btn btn-primary" onclick="nuevoHorario()">
                    <i class="fas fa-plus"></i> Nuevo rango
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-nowrap">Rango de fechas</th>
                            <th>Tipo</th>
                            <th>A quiénes afecta y con qué horas</th>
                            <th class="text-center">Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($horarios as $h)
                        <tr class="{{ $h->esp_estado ? '' : 'text-muted bg-light' }}">
                            <td style="min-width:170px">
                                <strong>{{ $h->esp_nombre }}</strong>
                                @if($h->esp_observacion)
                                    <br><small class="text-muted">{{ $h->esp_observacion }}</small>
                                @endif
                                @foreach(($solapes[$h->esp_id] ?? []) as $s)
                                    <div class="{{ $s['alerta'] ? 'text-warning' : 'text-muted' }}" style="font-size:.78rem">
                                        <i class="fas {{ $s['alerta'] ? 'fa-exclamation-triangle' : 'fa-level-up-alt fa-rotate-90' }}"></i>
                                        {{ $s['texto'] }}
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-nowrap">
                                {{ $h->esp_fecha_inicio->format('d/m/Y') }}
                                <i class="fas fa-arrow-right text-muted mx-1" style="font-size:.7rem"></i>
                                {{ $h->esp_fecha_fin->format('d/m/Y') }}
                                <br><small class="text-muted">{{ $h->esp_fecha_inicio->diffInDays($h->esp_fecha_fin) + 1 }} días corridos</small>
                            </td>
                            <td class="text-nowrap">
                                @if($h->es_receso)
                                    <span class="badge badge-secondary">Receso sin clases</span>
                                @else
                                    <span class="badge badge-info">Horario especial</span>
                                @endif
                            </td>
                            <td>
                                @if($h->es_receso)
                                    <span class="text-muted" style="font-size:.85rem">
                                        <i class="fas fa-umbrella-beach"></i> Todo el colegio, todos los turnos
                                    </span>
                                @else
                                    <table class="table table-sm mb-0 border-0" style="font-size:.8rem">
                                        @foreach($h->detalles->sortBy('det_turno') as $d)
                                            <tr>
                                                <td class="border-0 py-0 pl-0">
                                                    <span class="badge badge-light border">{{ $d->det_categoria }}</span>
                                                    <small class="text-muted">{{ $d->det_turno }}</small>
                                                </td>
                                                <td class="border-0 py-0 text-nowrap">
                                                    entra <strong>{{ substr($d->det_hora_entrada, 0, 5) }}</strong>
                                                    · atraso desde <strong class="text-danger">{{ substr($d->det_tolerancia_atraso, 0, 5) }}</strong>
                                                    · sale {{ substr($d->det_hora_salida, 0, 5) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($h->esp_estado)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-dark">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                <button class="btn btn-sm btn-warning" title="Editar"
                                        data-horario="{{ json_encode([
                                            'id' => $h->esp_id,
                                            'nombre' => $h->esp_nombre,
                                            'gestion' => $h->esp_gestion,
                                            'inicio' => $h->esp_fecha_inicio->format('Y-m-d'),
                                            'fin' => $h->esp_fecha_fin->format('Y-m-d'),
                                            'tipo' => (int) $h->esp_tipo,
                                            'observacion' => $h->esp_observacion,
                                            'detalles' => $h->detalles->map(fn($d) => [
                                                'categoria' => $d->det_categoria,
                                                'turno' => $d->det_turno,
                                                'entrada' => substr($d->det_hora_entrada, 0, 5),
                                                'tolerancia' => substr($d->det_tolerancia_atraso, 0, 5),
                                                'salida' => substr($d->det_hora_salida, 0, 5),
                                            ])->values(),
                                        ]) }}"
                                        onclick="editarHorario(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('asistencia-config.horarios-especiales.toggle', $h->esp_id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $h->esp_estado ? 'btn-secondary' : 'btn-success' }}"
                                            title="{{ $h->esp_estado ? 'Desactivar (los reportes vuelven al horario normal)' : 'Activar' }}">
                                        <i class="fas {{ $h->esp_estado ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('asistencia-config.horarios-especiales.destroy', $h->esp_id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ $h->esp_nombre }}»? Los reportes volverán al horario normal en ese rango.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block text-black-50"></i>
                                No hay horarios especiales cargados para la gestión {{ $gestion }}.<br>
                                <small>Todos los días se evalúan con la configuración normal de horarios.</small>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════ Modal de alta / edición ══════════════════ --}}
<div class="modal fade" id="modalHorario" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST" id="formHorario" action="{{ route('asistencia-config.horarios-especiales.store') }}">
        @csrf
        <input type="hidden" name="_method" id="metodoForm" value="POST">

        <div class="modal-header">
            <h5 class="modal-title" id="tituloModal">Nuevo horario especial</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">

            {{-- ── 1. Qué es y cuándo ── --}}
            <h6 class="text-primary mb-2"><span class="badge badge-primary">1</span> Qué pasó y cuándo</h6>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="esp_nombre" id="f_nombre" class="form-control"
                           placeholder="Ej: Horario de invierno 2026" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Tipo <span class="text-danger">*</span></label>
                    <select name="esp_tipo" id="f_tipo" class="form-control" onchange="cambiarTipo()" required>
                        <option value="1">Horario especial — se entró y salió a otra hora</option>
                        <option value="2">Receso sin clases — vacaciones, feriado largo</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Desde <span class="text-danger">*</span></label>
                    <input type="date" name="esp_fecha_inicio" id="f_inicio" class="form-control"
                           onchange="resumenRango()" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Hasta <span class="text-danger">*</span></label>
                    <input type="date" name="esp_fecha_fin" id="f_fin" class="form-control"
                           onchange="resumenRango()" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Gestión <span class="text-danger">*</span></label>
                    <input type="number" name="esp_gestion" id="f_gestion" class="form-control"
                           value="{{ $gestion }}" min="2000" max="2100" required>
                </div>
                <div class="form-group col-md-4">
                    <label class="d-block">&nbsp;</label>
                    <div id="resumenRango" class="text-muted pt-1" style="font-size:.86rem">
                        <i class="fas fa-calendar-day"></i> Cargá el rango de fechas
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Observación</label>
                <input type="text" name="esp_observacion" id="f_observacion" class="form-control"
                       placeholder="Opcional: resolución, motivo, etc.">
            </div>

            {{-- ── 2. Categorías: TODAS, de todos los turnos ── --}}
            <div id="bloqueCategorias">
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="text-primary mb-0">
                        <span class="badge badge-primary">2</span> A quiénes les cambió el horario
                    </h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodas(true)">
                            Marcar todas
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodas(false)">
                            Ninguna
                        </button>
                    </div>
                </div>
                <p class="text-muted mb-2" style="font-size:.85rem">
                    Marcá cada categoría afectada y cargá sus horas de ese periodo.
                    Podés mezclar turnos en un mismo rango. Las que no marques
                    <strong>siguen con su horario normal</strong>.
                    <br>
                    <i class="fas fa-exclamation-circle text-warning"></i>
                    <strong>Tolerancia</strong> es la hora límite, no una cantidad de minutos:
                    una marcación <em>posterior</em> a esa hora es atraso.
                </p>

                <div class="table-responsive" style="max-height:340px; overflow-y:auto">
                    <table class="table table-sm table-bordered mb-0" id="tablaCategorias">
                        <thead class="thead-light" style="position:sticky; top:0; z-index:2">
                            <tr>
                                <th style="width:36px"></th>
                                <th>Categoría</th>
                                <th style="width:125px">Entrada</th>
                                <th style="width:145px">Atraso desde</th>
                                <th style="width:125px">Salida</th>
                                <th>Su horario normal</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoCategorias"></tbody>
                    </table>
                </div>
            </div>

            <div id="avisoReceso" class="alert alert-secondary mt-3" style="display:none">
                <i class="fas fa-umbrella-beach mr-1"></i>
                Durante un receso <strong>no hay clases para nadie</strong>: esos días no se cuentan como
                hábiles, no generan faltas ni atrasos, en ninguna categoría ni turno. No hace falta cargar horas.
                <hr class="my-2">
                <span style="font-size:.86rem">
                    Si el receso cae dentro de un horario especial, <strong>manda el receso</strong>:
                    esos días quedan sin clases y el resto del periodo sigue con su horario.
                    Por ejemplo, las vacaciones de julio dentro del horario de invierno. No hay nada que configurar.
                </span>
            </div>

            {{-- ── 3. Vista previa ── --}}
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-primary mb-0">
                    <span class="badge badge-primary">3</span> Verificación
                </h6>
                <div class="form-inline">
                    <select id="prev_categoria" class="form-control form-control-sm mr-1"></select>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="verPreview()">
                        <i class="fas fa-eye"></i> Ver qué horario regirá cada día
                    </button>
                </div>
            </div>
            <div id="preview" class="mt-2"></div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const CATEGORIAS  = @json($categorias);   // todas, de todos los turnos
const URL_STORE   = "{{ route('asistencia-config.horarios-especiales.store') }}";
const URL_PREVIEW = "{{ route('asistencia-config.horarios-especiales.preview') }}";
const URL_UPDATE  = "{{ url('asistencia-config/horarios-especiales') }}";

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/**
 * Una fila por categoría configurada, de TODOS los turnos, con un separador por
 * turno. `seleccion` trae lo guardado al editar, para volver a marcar y rellenar.
 */
function pintarCategorias(seleccion) {
    const prev = {};
    (seleccion || []).forEach(d => prev[d.categoria + '|' + d.turno] = d);

    const cuerpo = document.getElementById('cuerpoCategorias');
    if (!CATEGORIAS.length) {
        cuerpo.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">' +
            'No hay categorías en la configuración de horarios.</td></tr>';
        return;
    }

    let turnoActual = null;
    cuerpo.innerHTML = CATEGORIAS.map((c, i) => {
        const clave   = c.categoria + '|' + c.turno;
        const yaEsta  = prev[clave];
        const marcado = yaEsta ? 'checked' : '';
        const dis     = yaEsta ? '' : 'disabled';

        // Separador cuando cambia el turno, para que se lea de un vistazo.
        let sep = '';
        if (c.turno !== turnoActual) {
            turnoActual = c.turno;
            sep = `<tr class="bg-light">
                     <td colspan="6" class="py-1" style="font-size:.8rem">
                       <strong class="text-uppercase text-muted">Turno ${esc(c.turno)}</strong>
                     </td>
                   </tr>`;
        }

        return sep + `
        <tr class="fila-cat ${yaEsta ? 'table-info' : ''}">
            <td class="text-center align-middle">
                <input type="checkbox" class="chk-cat" name="categorias[${i}]" value="${esc(c.categoria)}"
                       data-turno="${esc(c.turno)}" ${marcado} onchange="activarFila(this)">
                <input type="hidden" name="turnos[${i}]" value="${esc(c.turno)}">
            </td>
            <td class="align-middle">
                <label class="mb-0" style="cursor:pointer" onclick="this.closest('tr').querySelector('.chk-cat').click()">
                    <strong>${esc(c.categoria)}</strong>
                </label>
            </td>
            <td><input type="time" class="form-control form-control-sm h-entrada"
                       name="horas[${i}][entrada]" value="${esc(yaEsta ? yaEsta.entrada : c.entrada)}"
                       ${dis} onchange="sugerirTolerancia(this)"></td>
            <td><input type="time" class="form-control form-control-sm h-tolerancia"
                       name="horas[${i}][tolerancia]" value="${esc(yaEsta ? yaEsta.tolerancia : c.tolerancia)}" ${dis}></td>
            <td><input type="time" class="form-control form-control-sm h-salida"
                       name="horas[${i}][salida]" value="${esc(yaEsta ? yaEsta.salida : c.salida)}" ${dis}></td>
            <td class="align-middle text-muted" style="font-size:.8rem">
                entra ${esc(c.entrada)} · atraso desde ${esc(c.tolerancia)} · sale ${esc(c.salida)}
            </td>
        </tr>`;
    }).join('');

    pintarSelectorPreview();
}

/** Los campos de hora sólo se editan si la categoría está marcada. */
function activarFila(chk) {
    const fila = chk.closest('tr');
    fila.querySelectorAll('input[type=time]').forEach(i => i.disabled = !chk.checked);
    fila.classList.toggle('table-info', chk.checked);
    pintarSelectorPreview();
}

function marcarTodas(valor) {
    document.querySelectorAll('.chk-cat').forEach(c => { c.checked = valor; activarFila(c); });
}

/**
 * Al cambiar la entrada, corre la tolerancia el mismo margen de 4 minutos que usa
 * la configuración actual. Es sólo una sugerencia: se puede pisar a mano.
 */
function sugerirTolerancia(inp) {
    const fila = inp.closest('tr');
    const tol = fila.querySelector('.h-tolerancia');
    if (!inp.value || !tol || tol.dataset.tocada === '1') return;
    const [h, m] = inp.value.split(':').map(Number);
    const t = new Date(2000, 0, 1, h, m + 4);
    tol.value = String(t.getHours()).padStart(2, '0') + ':' + String(t.getMinutes()).padStart(2, '0');
}

function cambiarTipo() {
    const esReceso = document.getElementById('f_tipo').value === '2';
    document.getElementById('bloqueCategorias').style.display = esReceso ? 'none' : '';
    document.getElementById('avisoReceso').style.display = esReceso ? '' : 'none';
    pintarSelectorPreview();
}



/** Cuántos días abarca el rango, para detectar un tipeo antes de guardar. */
function resumenRango() {
    const d = document.getElementById('f_inicio').value;
    const h = document.getElementById('f_fin').value;
    const cont = document.getElementById('resumenRango');
    if (!d || !h) { cont.innerHTML = '<i class="fas fa-calendar-day"></i> Cargá el rango de fechas'; return; }

    const di = new Date(d + 'T00:00:00'), hf = new Date(h + 'T00:00:00');
    if (hf < di) {
        cont.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> La fecha final es anterior a la inicial</span>';
        return;
    }
    let corridos = 0, habiles = 0;
    for (let c = new Date(di); c <= hf; c.setDate(c.getDate() + 1)) {
        corridos++;
        const dw = c.getDay();
        if (dw !== 0 && dw !== 6) habiles++;
    }
    cont.innerHTML = `<i class="fas fa-calendar-day"></i> <strong>${corridos}</strong> días corridos
                      · <strong>${habiles}</strong> de lunes a viernes`;
}

/** El selector con el que se elige sobre qué categoría se previsualiza. */
function pintarSelectorPreview() {
    const sel = document.getElementById('prev_categoria');
    const esReceso = document.getElementById('f_tipo').value === '2';

    const marcadas = Array.from(document.querySelectorAll('.chk-cat:checked'))
        .map(c => ({categoria: c.value, turno: c.dataset.turno}));
    const lista = (esReceso || !marcadas.length) ? CATEGORIAS : marcadas;

    const previo = sel.value;
    sel.innerHTML = lista.map(c =>
        `<option value="${esc(c.categoria)}|${esc(c.turno)}">${esc(c.categoria)} — ${esc(c.turno)}</option>`
    ).join('');
    if (previo) sel.value = previo;
    if (!sel.value && sel.options.length) sel.selectedIndex = 0;
}

/** Muestra los tramos resultantes, ya considerando lo demás que hay cargado. */
function verPreview() {
    const desde = document.getElementById('f_inicio').value;
    const hasta = document.getElementById('f_fin').value;
    const cont  = document.getElementById('preview');

    if (!desde || !hasta) {
        cont.innerHTML = '<div class="alert alert-warning mb-0">Cargá primero el rango de fechas.</div>';
        return;
    }
    const elegido = document.getElementById('prev_categoria').value;
    if (!elegido) {
        cont.innerHTML = '<div class="alert alert-warning mb-0">No hay categorías configuradas.</div>';
        return;
    }
    const [categoria, turno] = elegido.split('|');

    // Se amplía una semana a cada lado para que se vea el corte con el horario normal.
    const ampliar = (f, dias) => {
        const d = new Date(f + 'T00:00:00'); d.setDate(d.getDate() + dias);
        return d.toISOString().slice(0, 10);
    };

    cont.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> Calculando…</div>';

    const q = new URLSearchParams({
        desde: ampliar(desde, -7), hasta: ampliar(hasta, 7),
        categoria: categoria, turno: turno
    });

    fetch(URL_PREVIEW + '?' + q.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(j => {
            if (!j.ok) throw new Error('respuesta inválida');
            const filas = j.tramos.map(t => {
                const badge = t.tipo === 'receso'   ? '<span class="badge badge-secondary">Sin clases</span>'
                            : t.tipo === 'especial' ? '<span class="badge badge-info">Especial</span>'
                            : t.tipo === 'normal'   ? '<span class="badge badge-light border">Normal</span>'
                            :                         '<span class="badge badge-warning">Sin configuración</span>';
                const horas = t.tipo === 'receso' || !t.tolerancia
                    ? '<span class="text-muted">—</span>'
                    : `entra ${esc(t.entrada.slice(0,5))} · atraso desde <strong>${esc(t.tolerancia.slice(0,5))}</strong> · sale ${esc(t.salida.slice(0,5))}`;
                // En un receso esos días L-V dejan de contar: se muestran como descuento.
                const habiles = t.tipo === 'receso'
                    ? `<span class="text-danger">−${t.habiles}</span>`
                    : t.habiles;
                return `<tr>
                    <td class="text-nowrap">${esc(t.desde)} → ${esc(t.hasta)}</td>
                    <td class="text-center">${habiles}</td>
                    <td>${badge}</td>
                    <td>${horas}</td>
                    <td class="text-muted">${esc(t.nombre || '')}</td>
                </tr>`;
            }).join('');

            cont.innerHTML = `
                <p class="text-muted mb-1" style="font-size:.85rem">
                    <strong>${esc(categoria)}</strong>, turno <strong>${esc(turno)}</strong>.
                    Se muestran 7 días antes y después del rango para ver el corte.
                    <em>Refleja lo ya guardado, no los cambios sin guardar de este formulario.</em>
                </p>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light"><tr>
                        <th>Tramo</th><th class="text-center">Días L-V</th><th>Tipo</th><th>Horario</th><th>Origen</th>
                    </tr></thead>
                    <tbody>${filas}</tbody>
                </table>`;
        })
        .catch(() => {
            cont.innerHTML = '<div class="alert alert-danger mb-0">No se pudo calcular la vista previa.</div>';
        });
}

function nuevoHorario() {
    const f = document.getElementById('formHorario');
    f.action = URL_STORE;
    document.getElementById('metodoForm').value = 'POST';
    document.getElementById('tituloModal').textContent = 'Nuevo horario especial';
    f.reset();
    document.getElementById('f_gestion').value = {{ $gestion }};
    document.getElementById('preview').innerHTML = '';
    cambiarTipo();
    pintarCategorias(null);
    resumenRango();
    $('#modalHorario').modal('show');
}

function editarHorario(btn) {
    const d = JSON.parse(btn.dataset.horario);
    const f = document.getElementById('formHorario');
    f.action = URL_UPDATE + '/' + d.id;
    document.getElementById('metodoForm').value = 'PUT';
    document.getElementById('tituloModal').textContent = 'Editar: ' + d.nombre;

    document.getElementById('f_nombre').value      = d.nombre;
    document.getElementById('f_gestion').value     = d.gestion;
    document.getElementById('f_inicio').value      = d.inicio;
    document.getElementById('f_fin').value         = d.fin;
    document.getElementById('f_tipo').value        = d.tipo;
    document.getElementById('f_observacion').value = d.observacion || '';
    document.getElementById('preview').innerHTML   = '';

    cambiarTipo();
    pintarCategorias(d.detalles);
    resumenRango();
    $('#modalHorario').modal('show');
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('cuerpoCategorias').addEventListener('input', function (e) {
        if (e.target.classList.contains('h-tolerancia')) e.target.dataset.tocada = '1';
    });
    pintarCategorias(null);
    cambiarTipo();
});
</script>
@endsection
