<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Código</label>
            <input type="text" class="form-control" value="{{ $codigo ?? $agenda->age_codigo }}" readonly>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Tipo <span class="text-danger">*</span></label>
            <select name="age_tipo" class="form-control" required>
                <option value="1" {{ (old('age_tipo', $agenda->age_tipo ?? '') == 1) ? 'selected' : '' }}>Agenda</option>
                <option value="2" {{ (old('age_tipo', $agenda->age_tipo ?? '') == 2) ? 'selected' : '' }}>Notificación</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Fecha y Hora <span class="text-danger">*</span></label>
            <input type="datetime-local" name="age_fechahora" class="form-control"
                   value="{{ old('age_fechahora', isset($agenda) && $agenda->age_fechahora ? $agenda->age_fechahora->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Estudiante</label>
            <select name="est_codigo" id="est_codigo" class="form-control select2">
                <option value="">Seleccione estudiante (opcional)</option>
                @foreach($estudiantes as $est)
                    <option value="{{ $est->est_codigo }}" {{ old('est_codigo', $agenda->est_codigo ?? '') == $est->est_codigo ? 'selected' : '' }}>
                        {{ $est->est_apellidos }} {{ $est->est_nombres }} — {{ $est->curso->cur_nombre ?? '' }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-info btn-sm mt-2" id="btnToggleQR">
                <i class="fas fa-qrcode mr-1"></i>Escanear QR
            </button>
            <div id="qr-reader-container" style="display:none;margin-top:10px;">
                <div id="qr-reader" style="width:100%;max-width:350px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Padres de Familia</label>
            <div id="padres-container">
                <p class="text-muted" style="font-size:0.85rem;"><i class="fas fa-info-circle mr-1"></i>Seleccione un estudiante para ver sus padres</p>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label>Título <span class="text-danger">*</span></label>
    <input type="text" name="age_titulo" class="form-control" value="{{ old('age_titulo', $agenda->age_titulo ?? '') }}" maxlength="50" required>
</div>

<div class="form-group">
    <label>Detalles <span class="text-danger">*</span></label>
    <textarea name="age_detalles" class="form-control" rows="4" required>{{ old('age_detalles', $agenda->age_detalles ?? '') }}</textarea>
</div>
