<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Documento Privado de Transferencia</title>
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
        .list-item { margin: 8px 0 8px 30px; text-align: justify; }
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

    <div class="title">DOCUMENTO PRIVADO<br>DE TRANSFERENCIA</div>

    <div class="content">
        <div class="info-line">
            Yo <span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span> C.I. <span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Padre/Madre/Tutor del estudiante <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span>
        </div>
        <div class="info-line">
            Del Nivel <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>Curso <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        <div class="list-item">
            ✓ Como responsable de mi hijo(a) he sido informado (a) de la actitud de indisciplina e incumplimiento al Reglamento Interno de la Unidad Educativa.
        </div>
        <div class="list-item">
            ✓ Con el Propósito de que ya no continúe como estudiante regular de la Unidad Educativa, acepto la transferencia a otra unidad, revisando la <strong>Resolución Ministerial (0070/2024) Cap. 7- Art. 98 donde se prohíbe a la comunidad educativa estos actos de robo, hurto.</strong>
        </div>
        <div class="list-item">
            ✓ Así mismo contemplada como una de las faltas muy graves dentro de nuestra Unidad Educativa en el <strong>Reglamento Interno Inc. B (COMETER ACTOS DE ROBOS) Inc. I (HURTOS ASI MISMO FALCEDAD DE MATERIAL)</strong> tiene como sanción: Amonestación documentada de transferencia a otra Unidad Educativa y alejamiento definitivo en estricto cumplimiento al Reglamento Interno de la Institución.
        </div>
    </div>

    <div class="content">
        Para constancia firmo el presente compromiso por la buena salud académica y disciplinaria de mi hijo(a).
    </div>

    <div class="content" style="margin-top: 30px; text-align: center;">
        El Alto <span class="dotted" style="min-width: 50px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d') }}</span> de <span class="dotted" style="min-width: 100px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->locale('es')->translatedFormat('F') }}</span> de 2026
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
                C.I.: <span class="dotted" style="min-width: 200px;">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
            </div>
            <div class="signature-item">
                Cel. <span class="dotted" style="min-width: 200px;">{{ $caso->estudiante->padres->first()->pfam_celular ?? '' }}</span>
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
                C.I.: <span class="dotted" style="min-width: 200px;"></span>
            </div>
            <div class="signature-item">
                Cel. <span class="dotted" style="min-width: 200px;"></span>
            </div>
        </div>
    </div>
</body>
</html>
