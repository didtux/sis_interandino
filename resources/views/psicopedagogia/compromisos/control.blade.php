<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compromiso de Control y Disciplina</title>
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
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .list-item { margin: 8px 0 8px 30px; text-align: justify; }
        .signature-section { margin-top: 40px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: left; padding: 10px 20px; }
        .signature-item { margin: 15px 0; }
        .page-break { page-break-before: always; }
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

    <div class="title">COMPROMISO DE CONTROL Y DISCIPLINA DE MI HIJO(A)</div>

    <div class="content">
        <div class="info-line">
            Yo<span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span>con C.I <span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Madre/Padre/Tutor, del estudiante: <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span>
        </div>
        <div class="info-line">
            del curso<span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>Nivel <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        En vista de que he sido informado sobre la indisciplina de mi hijo/a, me veo en la necesidad de realizar un estricto seguimiento a la formación personal dentro de la institución. Al ser informado (a) de las llamadas de atención y conducta inadecuada en la Unidad Educativa.
    </div>

    <table>
        <thead>
            <tr>
                <th>FECHA</th>
                <th>REFERENCIA</th>
                <th>MOTIVO (S)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d/m/Y') }}</td>
                <td>{{ $caso->psico_codigo }}</td>
                <td>{{ $caso->psico_caso }}</td>
            </tr>
        </tbody>
    </table>

    <div class="content">
        <strong>Mi compromiso de estudiante:</strong>
    </div>

    <div class="content">
        <div class="list-item">
            • Yo {{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }} tomo en cuenta que uno de los objetivos de la institución consiste en formar personas íntegras, responsables y respetuosas para que puedan enfrentar la realidad social actual con acierto y respeto. Por tal motivo mejoraré mi conducta de indisciplina dentro de la institución educativa y respetar el Reglamento Interno, a mis profesores y a mis compañeros, así mismo seré un agente de cambio, realizando las siguientes acciones:
        </div>
        <div class="list-item">
            {{ $caso->psico_solucion ?? '' }}
        </div>
    </div>

    <div class="page-break"></div>

    <div class="content">
        <strong>Me comprometo:</strong>
    </div>

    <div class="content">
        <div class="list-item">
            • Yo {{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }} padre/madre del estudiante, que mi hijo/a disminuirá gradualmente el comportamiento a actos de indisciplina dentro de la institución educativa.
        </div>
        <div class="list-item">
            • Mi hijo/a y mi persona se responsabilizan sobre lo acontecido, realizando las siguientes acciones de solución:
        </div>
        <div class="list-item">
            {{ $caso->psico_solucion ?? '' }}
        </div>
        <div class="list-item">
            • Teniendo en cuenta que esta llamada de atención es una alerta de prevención que la Unidad Educativa realiza, así mismo asumiré a las sanciones contempladas desde Dirección Académica y Depto. de Control y Seguimiento.
        </div>
        <div class="list-item">
            • En caso de que vuelva a incurrir nuevamente en conducta indisciplinaria (como máximo 2 veces) ya en la 3° vez se realizará la transferencia a otra Unidad Educativa de manera voluntaria, sin que exista presión alguna, vicio dolo y no presentare ninguna queja o denuncia ante la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1 (DDEEA-1).
        </div>
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
