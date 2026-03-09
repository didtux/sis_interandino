<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud de {{ $permiso->permiso_tipo }}</title>
    <style>
        @page { margin: 10mm; size: 10cm 16cm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 50px; vertical-align: top; }
        .logo img { width: 45px; height: auto; }
        .institucion { display: table-cell; vertical-align: top; padding-left: 5px; }
        .institucion h3 { margin: 0; font-size: 7pt; line-height: 1.2; font-weight: bold; }
        .numero-box { display: table-cell; width: 60px; text-align: right; vertical-align: top; }
        .numero-box div { border: 1.5px solid #000; padding: 3px; text-align: center; }
        .numero-box .label { font-size: 7pt; margin: 0; font-weight: bold; }
        .numero-box .numero { font-size: 10pt; font-weight: bold; margin: 2px 0; }
        .numero-box .fecha { font-size: 6pt; margin: 0; border-top: 1px solid #000; padding-top: 2px; }
        .titulo { text-align: center; font-size: 10pt; font-weight: bold; margin: 8px 0; }
        .info-line { margin: 4px 0; font-size: 7pt; }
        .info-line strong { font-weight: bold; }
        .grid-container { margin: 8px 0; }
        .grid-row { display: table; width: 100%; margin-bottom: 3px; }
        .grid-cell { display: table-cell; width: 33.33%; padding: 1px; }
        .grid-box { border: 1.5px solid #000; border-radius: 3px; height: 50px; position: relative; }
        .grid-number { position: absolute; top: 3px; left: 5px; font-size: 9pt; font-weight: bold; background: white; padding: 0 3px; }
        .footer { margin-top: 10px; }
        .footer-line { margin: 4px 0; font-size: 7pt; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="institucion">
            <h3>UNIDAD EDUCATIVA PRIVADA</h3>
            <h3>INTERANDINO BOLIVIANO</h3>
        </div>
        <div class="numero-box">
            <div>
                <p class="label">N° {{ $permiso->permiso_numero ?? 0 }}</p>
                <p class="fecha">{{ $permiso->permiso_fecha_inicio->format('d') }}</p>
                <p class="fecha">{{ $permiso->permiso_fecha_inicio->format('m') }}</p>
                <p class="fecha">{{ $permiso->permiso_fecha_inicio->format('Y') }}</p>
            </div>
        </div>
    </div>

    <div class="titulo">SOLICITUD DE {{ strtoupper($permiso->permiso_tipo) }}</div>

    <div class="info-line">
        <strong>Estudiante:</strong> <u>{{ strtoupper($permiso->estudiante->est_nombres ?? '') }} {{ strtoupper($permiso->estudiante->est_apellidos ?? '') }}</u>
    </div>

    <div class="info-line">
        <strong>Curso:</strong> <u>{{ strtoupper($permiso->estudiante->curso->cur_nombre ?? 'N/A') }}</u>
    </div>

    <div class="info-line">
        <strong>Motivo:</strong> <u>{{ $permiso->permiso_motivo }}</u>
    </div>

    <div class="info-line">
        <strong>Por:</strong> Familiar solicitante: ........... ({{ $permiso->solicitante_nombre_completo ?? ($permiso->estudiante->padres->first()->pfam_nombres ?? '-') }})
    </div>

    <div class="grid-container">
        <div class="grid-row">
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">1</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">4</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">7</span></div>
            </div>
        </div>
        <div class="grid-row">
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">2</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">5</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">8</span></div>
            </div>
        </div>
        <div class="grid-row">
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">3</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">6</span></div>
            </div>
            <div class="grid-cell">
                <div class="grid-box"><span class="grid-number">9</span></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-line">
            <strong>Firma:</strong> ........................................................
        </div>
        <div class="footer-line">
            <strong>HORA:</strong> {{ now()->format('H:i:s') }}
        </div>
        <div class="footer-line" style="font-size: 6pt; font-style: italic;">
            Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
