<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de Compromiso de Cumplimiento de Deberes de Mi Hijo/a</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; padding: 15mm 20mm; }
        .header { display: table; width: 100%; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; padding: 0 10px; }
        .header-info h3 { font-size: 12px; margin: 2px 0; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .title { text-align: center; margin: 15px 0; font-size: 13px; font-weight: bold; text-decoration: underline; }
        .info-line { margin: 5px 0; }
        .dotted { border-bottom: 1px dotted #000; display: inline-block; min-width: 200px; }
        .content { text-align: justify; margin: 10px 0; }
        .list-item { margin: 5px 0 5px 30px; text-align: justify; }
        .signature-section { margin-top: 30px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: left; padding: 10px 20px; }
        .signature-item { margin: 10px 0; }
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

    <div class="title">ACTA DE COMPROMISO DE CUMPLIMIENTO<br>DE DEBERES DE MI HIJO/A</div>

    <div class="content">
        <div class="info-line">
            Yo<span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span>C.I.<span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Padre/madre/tutor del estudiante<span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span>
        </div>
        <div class="info-line">
            Del curso<span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>Nivel <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        Tomo en cuenta toda información que me brindó la Unidad Educativa Privada Interandino Boliviano sobre las exigencias como el cumplimiento de deberes, la disciplina, la puntualidad de mi hijo como responsable y tutor directo me comprometo a hacer cumplir en esta gestión 2026 los siguientes puntos:
    </div>

    <div class="content">
        <div class="list-item">
            a) Hacer un seguimiento minucioso en cuanto a las responsabilidades de mi hijo/a no más de tres observaciones de incumplimiento.
        </div>
        <div class="list-item">
            b) Recomendar sobre su conducta de indisciplina a mi hijo/a no reincida en las llamadas de atención
        </div>
        <div class="list-item">
            c) Llegaremos 5 minutos antes de la hora indicada al ingreso a la Unidad Educativa. No tendrá más de 10 atrasos ni más de 3 faltas al trimestre
        </div>
        <div class="list-item">
            d) Me comprometo a asistir de manera regular a todas las solicitudes de entrevista que convoque Dirección Académica, Depto. De Control Y seguimiento y Profesores
        </div>
    </div>

    <div class="content">
        En caso de que no haya esa adaptación social y académico velando siempre que el desarrollo académico esté orientado a un nivel de excelencia hasta el primer trimestre. Realizaré la transferencia a otra Unidad Educativa de manera voluntaria por el bien del/la estudiante y la institución, sin que exista presión alguna, vicio dolo y no presentare ninguna queja o denuncia ante la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1(DDEEA-1).
    </div>

    <div class="content">
        Para constancia firmo el presente compromiso.
    </div>

    <div class="content" style="margin-top: 20px; text-align: center;">
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
