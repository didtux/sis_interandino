<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Devolución de Celular</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.6; padding: 15mm 20mm; }
        .header { display: table; width: 100%; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; padding: 0 10px; }
        .header-info h3 { font-size: 12px; margin: 2px 0; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .title { text-align: center; margin: 20px 0; font-size: 14px; font-weight: bold; text-decoration: underline; }
        .info-line { margin: 8px 0; }
        .dotted { border-bottom: 1px dotted #000; display: inline-block; min-width: 200px; }
        .content { text-align: justify; margin: 15px 0; }
        .signature-section { margin-top: 60px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: left; padding: 10px 20px; }
        .signature-item { margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>UNIDAD EDUCATIVA PRIVADA "INTERANDINO BOLIVIANO"</h3>
            <h3>RESOLUCIÓN ADMINISTRATIVA Nº 311/2006</h3>
            <h3>DEPTO. DE CONTROL Y SEGUIMIENTO</h3>
            <p>Calle V. Gutiérrez Nº 3339 Zona "16 de Julio" Telf. 2840320 Fax: 2846479 - El Alto, La Paz - Bolivia</p>
            <p>Contribuir - Mejorar - Desarrollar - Adquiere sabiduría, adquiere inteligencia... Prov. 4:5</p>
        </div>
    </div>

    <div class="title">DEVOLUCION DE CELULAR</div>

    <div class="content">
        Con el propósito de evitar mayores sanciones al/la estudiante: <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span>
    </div>

    <div class="content">
        <div class="info-line">
            Del Nivel <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span> Curso<span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        Al haber incurrido en el uso de su celular en horario de clases a horas <span class="dotted" style="min-width: 100px;"></span> En fecha <span class="dotted" style="min-width: 150px;"></span> En la materia de<span class="dotted" style="min-width: 150px;"></span> En el establecimiento a pesar de las anteriores advertencias (verbales).
    </div>

    <div class="content">
        Yo<span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span> (Padre)(Madre)(tutor) Me comprometo a hacer cumplir estrictamente las normas estipuladas por el establecimiento en este aspecto, para evitar que mi hijo no se vea perjudicado con el decomiso de su celular. Si este percance vuelve a reincidir aceptare el decomiso del celular hasta la culminación de la presente gestión académica en cual estipula en el contrato de inscripción.
    </div>

    <div class="content">
        En fecha<span class="dotted" style="min-width: 150px;"></span> del 2026 se procedió a la devolución del celular marca<span class="dotted" style="min-width: 150px;"></span>Modelo<span class="dotted" style="min-width: 150px;"></span> En el mismo estado que se le decomiso al/la estudiante en el establecimiento.
    </div>

    <div class="content">
        Para constancia del presente compromiso damos nuestra conformidad y firmamos al pie del documento para su estricto cumplimiento.
    </div>

    <div class="content">
        En medida que no se cumpla este acuerdo y tratándose de un percance interno con la Unidad Educativa. No será necesario denunciar ante ninguna autoridad superior como la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1 (DDEEA-1).
    </div>

    <div class="content">
        Para constancia firmo el presente compromiso.
    </div>

    <div class="content" style="margin-top: 30px; text-align: center;">
        El Alto<span class="dotted" style="min-width: 100px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d') }}</span>de<span class="dotted" style="min-width: 150px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->locale('es')->translatedFormat('F') }}</span>2026
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-item">
                Firma: <span class="dotted" style="min-width: 250px;"></span>
            </div>
            <div class="signature-item">
                Nombres: <span class="dotted" style="min-width: 200px;">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }}</span>
            </div>
            <div class="signature-item">
                Apellidos: <span class="dotted" style="min-width: 200px;">{{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 200px;">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 200px;">{{ $caso->estudiante->padres->first()->pfam_celular ?? '' }}</span>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-item">
                Firma: <span class="dotted" style="min-width: 250px;"></span>
            </div>
            <div class="signature-item">
                Nombres: <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                Apellidos: <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 200px;"></span>
            </div>
         
        </div>
    </div>
</body>
</html>
