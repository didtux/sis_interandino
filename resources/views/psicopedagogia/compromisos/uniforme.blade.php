<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compromiso del Padre de Familia Uniforme del Estudiante</title>
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
        .image-section { text-align: center; margin: 15px 0; }
        .image-section img { width: 150px; margin: 0 20px; }
        .image-caption { text-align: center; font-weight: bold; margin-top: 5px; }
        .signature-section { margin-top: 30px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; padding: 10px; }
        .signature-item { margin: 8px 0; }
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

    <div class="title">COMPROMISO DEL PADRE DE FAMILIA UNIFORME DEL ESTUDIANTE</div>

    <div class="content">
        <div class="info-line">
            Yo, Sr./Sra.<span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span>
        </div>
    </div>

    <div class="content">
        Hábil por derecho con C.I. <span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span> Como padre o madre de familia, tutor o apoderado y en representación del/los estudiante(s):
    </div>

    <div class="content">
        @foreach($caso->estudiante->padres->first()->estudiantes ?? [] as $index => $estudiante)
            @if($index < 4)
                <div class="list-item">
                    {{ $index + 1 }}. <span class="dotted" style="min-width: 300px;">{{ strtoupper($estudiante->est_nombres) }} {{ strtoupper($estudiante->est_apellidos) }}</span> del curso <span class="dotted" style="min-width: 100px;">{{ $estudiante->curso->cur_nombre ?? '' }}</span>
                </div>
            @endif
        @endforeach
        @for($i = count($caso->estudiante->padres->first()->estudiantes ?? []); $i < 4; $i++)
            <div class="list-item">
                {{ $i + 1 }}. <span class="dotted" style="min-width: 300px;"></span> del curso <span class="dotted" style="min-width: 100px;"></span>
            </div>
        @endfor
    </div>

    <div class="content">
        1. por el consenso de ambas partes estipulado en el Reglamento Interno de la Unidad Educativa ratificar el uso del uniforme para asistir a clases diariamente de lunes a viernes con las siguientes características:
    </div>

    <div class="content">
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Varones: Pantalón plomo, polo amarillo y chompa y chamarra aviación.
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Damas: falda ploma 4 cm escoses hasta 4 cm sobre la rodilla, polo amarillo y chamarra aviación.
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Alternativamente el uso del deportivo de la unidad durante las clases de Educación Física
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Varones cabello: Corte escolar o cadete.
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Damas cabello: recogido con moño blanco en caso de tenerlo corto con vincha blanca recogido hacia atrás
        </div>
    </div>

    <div class="image-section">
        <div style="display: inline-block; margin: 0 20px; vertical-align: top; width: 150px; text-align: center;">
            @if(file_exists(public_path('img/mono.png')))
                <img src="{{ public_path('img/mono.png') }}" style="width: 150px; height: 150px; object-fit: cover;" alt="Moño">
            @endif
            <div class="image-caption">CABELLO RECOGIDO CON MOÑO BLANCO</div>
        </div>
        <div style="display: inline-block; margin: 0 20px; vertical-align: top; width: 150px; text-align: center;">
            @if(file_exists(public_path('img/corte.png')))
                <img src="{{ public_path('img/corte.png') }}" style="width: 150px; height: 150px; object-fit: cover;" alt="Corte">
            @endif
            <div class="image-caption">CORTE ESCOLAR O CADETE</div>
        </div>
    </div>

    <div class="content">
        Para la constancia firmo el presente compromiso.
    </div>

    <div class="content" style="margin-top: 20px; text-align: center;">
        El Alto <span class="dotted" style="min-width: 100px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d') }}</span> de <span class="dotted" style="min-width: 150px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->locale('es')->translatedFormat('F') }}</span> de 2026
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div style="font-weight: bold; margin-bottom: 10px;">FIRMA DEL PADRE O TUTORA</div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px;">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 150px;">{{ $caso->estudiante->padres->first()->pfam_celular ?? '' }}</span>
            </div>
            <div class="signature-item">
                Dirección Actual: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item" style="margin-top: 15px;">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div style="font-weight: bold; margin-bottom: 10px;">FIRMA DE LA MADRE O TUTORA</div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                Cel.: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item">
                Dirección Actual: <span class="dotted" style="min-width: 150px;"></span>
            </div>
            <div class="signature-item" style="margin-top: 15px;">
                <span class="dotted" style="min-width: 200px;"></span>
            </div>
        </div>
    </div>
</body>
</html>
