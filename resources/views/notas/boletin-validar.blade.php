<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Boletín · Interandino Boliviano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f3f4f6; font-family:'Segoe UI',sans-serif; padding-top:30px; }
        .card-validador { max-width:560px; margin:0 auto; border:0; box-shadow:0 10px 30px rgba(0,0,0,0.08); border-radius:12px; }
        .head-ok    { background:linear-gradient(135deg,#16a085,#2ecc71); color:#fff; border-radius:12px 12px 0 0; padding:24px; text-align:center; }
        .head-fail  { background:linear-gradient(135deg,#c0392b,#e74c3c); color:#fff; border-radius:12px 12px 0 0; padding:24px; text-align:center; }
        .head-icon  { font-size:48px; margin-bottom:8px; }
        .row-data   { padding:8px 0; border-bottom:1px solid #ecf0f1; }
        .row-data:last-child { border-bottom:none; }
        .lbl { color:#7f8c8d; font-size:13px; }
        .val { color:#2c3e50; font-weight:600; }
        .copy-tag { background:#f39c12; color:#fff; padding:2px 8px; border-radius:4px; font-size:11px; }
    </style>
</head>
<body>
    <div class="card card-validador">
        @if(!empty($valido))
            <div class="head-ok">
                <i class="fas fa-shield-check head-icon"></i>
                <h4 class="mb-0">Documento Auténtico</h4>
                <small>Validado por el sistema de la U.E. Interandino Boliviano</small>
            </div>
            <div class="card-body">
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Estudiante</span>
                    <span class="val">{{ $descarga->estudiante->est_apellidos ?? '' }} {{ $descarga->estudiante->est_nombres ?? '' }}</span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Curso</span>
                    <span class="val">{{ optional($descarga->estudiante->curso)->cur_nombre ?? '-' }}</span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Gestión</span>
                    <span class="val">{{ $descarga->descarga_gestion }}</span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Período</span>
                    <span class="val">
                        @if($descarga->descarga_trimestre)
                            {{ $descarga->descarga_trimestre }}° Trimestre
                        @else
                            Boletín Anual
                        @endif
                    </span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Fecha de generación</span>
                    <span class="val">{{ $descarga->descarga_fecha->format('d/m/Y H:i') }}</span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Copia N°</span>
                    <span class="val">{{ $descarga->descarga_numero_copia }} <span class="copy-tag">de {{ $totalCopias }} emitida(s)</span></span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Emitido por</span>
                    <span class="val">{{ $descarga->descargado_por_nombre ?: '—' }}</span>
                </div>
                <div class="row-data d-flex justify-content-between">
                    <span class="lbl">Código de verificación</span>
                    <span class="val text-monospace small">{{ substr($descarga->descarga_token, 0, 16) }}…</span>
                </div>

                <div class="mt-3 text-center text-muted small">
                    <i class="fas fa-info-circle"></i>
                    Este código confirma la autenticidad del documento PDF. El contenido completo de las
                    calificaciones está en el archivo original entregado a los padres.
                </div>
            </div>
        @else
            <div class="head-fail">
                <i class="fas fa-times-circle head-icon"></i>
                <h4 class="mb-0">Documento no encontrado</h4>
                <small>El código de verificación no corresponde a un boletín emitido por la institución.</small>
            </div>
            <div class="card-body text-center text-muted">
                Si crees que esto es un error, contacta a secretaría con el código completo del QR.
            </div>
        @endif

        <div class="card-footer text-center bg-white">
            <small class="text-muted">U.E. Privada Interandino Boliviano · Resolución Administrativa RA 311/2006</small>
        </div>
    </div>
</body>
</html>
