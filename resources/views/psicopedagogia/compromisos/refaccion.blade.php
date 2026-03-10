<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compromiso de Refacción de Mobiliario e Infraestructura</title>
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

    <div class="title">COMPROMISO DE REFACCION<br>DE MOBILIARIO E INFRAESTRUCTURA</div>

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
        Me comprometo a realizar un seguimiento constante en el cumplimiento y el buen comportamiento de mi hijo en la Unidad Educativa Privada Interandino Boliviano. He sido informado de los destrozos ocasionados con la mobiliaria (Silla, Mesa, Paredes, Pizarra, Ventana, Vidrios, Puerta de la Unidad Educativa.
    </div>

    <div class="content">
        En caso de se destruya o rayones de la mobiliaria (Silla, Mesa, Paredes, Techo, Ventana, Vidrios, Puerta) de la Unidad Educativa, se realizará el pago correspondiente al costo de la refacción de manera voluntaria, sin que exista presión alguna, vicio dolo y no presentare ninguna queja o denuncia ante la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1 (DDEEA-1).
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
