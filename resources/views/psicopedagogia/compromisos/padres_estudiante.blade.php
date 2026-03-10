<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compromiso de los Padres de Familia y Estudiante</title>
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
        .signature-box { display: table-cell; width: 50%; text-align: center; padding: 10px 20px; }
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

    <div class="title">COMPROMISO DE LOS PADRES DE FAMILIA Y ESTUDIANTE</div>

    <div class="content">
        <div class="info-line">
            Yo <span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span> C.I. <span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Padre/Madre/Tutor del estudiante <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span>
        </div>
        <div class="info-line">
            Del Nivel. <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span> Curso <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        Como responsable de mi hijo (a) he sido informado (a) de la actitud de indisciplina e incumplimiento al Reglamento Interno de la Unidad Educativa a la Educación a las normas del (m) resistencia al uso del uniforme reglamentario. Así mismo firmo el ACTA DE COMPROMISO DEL ESTUDIANTE Y LOS PADRES DE FAMILIA donde claramente explica el documento sobre el cabello recogido con moño de color blanco en caso de tenerlo corto usar vincha blanca con el cabello recogido hacia atrás de manera adecuada y respetando las normas de la institución.
    </div>

    <div class="content">
        Yo <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span> del curso <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
    </div>

    <div class="content">
        Me comprometo a venir a la Unidad Educativa con el cabello recogido de manera adecuada y respetuosa, según las normas de la institución.
    </div>

    <div class="content">
        Declaración: estamos conscientes de que en la gestión anterior no se cumplió con esta norma y nos comprometemos a hacer el esfuerzo adicional para asegurarnos de que se cumpla en la presente gestión sin que exista presión alguna, vicio dolo y no presentare ninguna queja o denuncia ante la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1 (DDEEA-1).
    </div>

    <div class="content">
        Para constancia firmo el presente compromiso por la buena salud académica y disciplinaria de mi hijo(a).
    </div>

    <div class="content" style="margin-top: 30px; text-align: center;">
        El Alto <span class="dotted" style="min-width: 100px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d') }}</span>de<span class="dotted" style="min-width: 150px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->locale('es')->translatedFormat('F') }}</span>2026
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">FIRMA DEL PADRE O TUTOR</div>
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px;">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 150px;">{{ $caso->estudiante->padres->first()->pfam_celular ?? '' }}</span>
            </div>
            <div class="signature-item">
                Dirección Actual: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
        </div>
        <div class="signature-box">
            <div style="font-weight: bold; margin-bottom: 10px;">FIRMA DE LA MADRE O TUTORA</div>
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                Dirección Actual: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
        </div>
    </div>
</body>
</html>
