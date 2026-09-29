<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de conformidad — Advertencia parcial</title>
    {{--
        Acta que firma el padre cuando se le comunica la advertencia parcial de
        un trimestre. Sigue el formato del acta de psicopedagogía
        (psicopedagogia/compromisos/conformidad.blade.php), que es la que el
        colegio ya usa, pero con el detalle de las materias observadas.

        Nota: en cole_padresfamilia NO existe pfam_apellidos — el nombre completo
        va todo en pfam_nombres.
    --}}
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.6; padding: 12mm 18mm; }
        .header { display: table; width: 100%; margin-bottom: 8px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .logo { display: table-cell; width: 78px; vertical-align: middle; }
        .logo img { width: 68px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; padding: 0 10px; }
        .header-info h3 { font-size: 12px; margin: 2px 0; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .title { text-align: center; margin: 16px 0 6px; font-size: 14px; font-weight: bold; text-decoration: underline; }
        .subtitle { text-align: center; font-size: 11px; margin-bottom: 12px; }
        .info-line { margin: 7px 0; }
        .dotted { border-bottom: 1px dotted #000; display: inline-block; min-width: 200px; }
        .content { text-align: justify; margin: 12px 0; }
        table.materias { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.materias th, table.materias td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; }
        table.materias th { background-color: #ededed; text-align: center; font-weight: bold; }
        table.materias td.centro { text-align: center; }
        .marca-doc { background-color: #f6a623; }
        .marca-dir { background-color: #f5b7d0; }
        .sin-obs { text-align: center; padding: 10px; font-style: italic; }
        .signature-section { margin-top: 42px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; padding: 8px 18px; vertical-align: top; }
        .signature-item { margin: 13px 0; }
        .leyenda { font-size: 8px; color: #444; margin-top: 4px; }
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

    <div class="title">ACTA DE CONFORMIDAD</div>
    <div class="subtitle">
        Advertencia parcial — {{ $periodo->periodo_nombre ?? ('Trimestre ' . $periodo->periodo_numero) }} · Gestión {{ $gestion }}
    </div>

    <div class="content">
        <div class="info-line">
            Yo <span class="dotted" style="min-width:300px;">{{ mb_strtoupper($padre->pfam_nombres ?? '', 'UTF-8') }}</span>
            C.I. <span class="dotted" style="min-width:120px;">{{ $padre->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Padre/Madre/Tutor del estudiante
            <span class="dotted" style="min-width:340px;">{{ mb_strtoupper($estudiante->est_apellidos . ' ' . $estudiante->est_nombres, 'UTF-8') }}</span>
        </div>
        <div class="info-line">
            Curso <span class="dotted" style="min-width:180px;">{{ $estudiante->curso->cur_nombre ?? '' }}</span>
            Nivel <span class="dotted" style="min-width:150px;">{{ $estudiante->curso->cur_nivel ?? '' }}</span>
            N° de lista <span class="dotted" style="min-width:50px;">{{ $numeroLista ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        Declaro haber sido informado(a) por la Dirección Académica y el Departamento de Control y
        Seguimiento de que mi hijo(a) presenta <strong>advertencia parcial</strong> en el
        {{ mb_strtolower($periodo->periodo_nombre ?? ('trimestre ' . $periodo->periodo_numero), 'UTF-8') }}
        de la gestión {{ $gestion }}, en las siguientes áreas:
    </div>

    @if(count($materiasObservadas))
        <table class="materias">
            <thead>
                <tr>
                    <th style="width:8%;">N°</th>
                    <th style="width:62%;">ÁREA / MATERIA</th>
                    <th style="width:30%;">REPORTADO POR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materiasObservadas as $i => $m)
                    <tr>
                        <td class="centro">{{ $i + 1 }}</td>
                        <td>{{ mb_strtoupper($m['nombre'], 'UTF-8') }}</td>
                        <td class="centro {{ $m['origen'] === 'director' ? 'marca-dir' : 'marca-doc' }}">
                            {{ $m['origen'] === 'director' ? 'DIRECCIÓN' : 'DOCENTE' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="leyenda">
            Total de áreas observadas: <strong>{{ count($materiasObservadas) }}</strong>.
            Naranja: reportado por el docente de área. Rosa: reportado por dirección.
        </div>
    @else
        <div class="sin-obs">El estudiante no registra áreas observadas en este trimestre.</div>
    @endif

    <div class="content">
        Me comprometo a realizar el seguimiento correspondiente en el hogar y a acompañar las medidas
        de nivelación que disponga la Unidad Educativa, entendiendo que de mantenerse esta situación
        el estudiante podría reprobar las áreas señaladas al cierre de la gestión.
    </div>

    <div class="content">
        Firmo la presente en señal de conformidad, sin que exista presión alguna, vicio ni dolo,
        dejando constancia de que la institución cumplió con informarme oportunamente.
    </div>

    <div class="content" style="margin-top: 22px; text-align: center;">
        El Alto, <span class="dotted" style="min-width:60px;">{{ $fecha->format('d') }}</span>
        de <span class="dotted" style="min-width:130px;">{{ $fecha->locale('es')->translatedFormat('F') }}</span>
        de <span class="dotted" style="min-width:70px;">{{ $fecha->format('Y') }}</span>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-item">Firma: <span class="dotted" style="min-width:230px;"></span></div>
            <div class="signature-item">Nombre: <span class="dotted" style="min-width:215px;">{{ mb_strtoupper($padre->pfam_nombres ?? '', 'UTF-8') }}</span></div>
            <div class="signature-item">C.I.: <span class="dotted" style="min-width:235px;">{{ $padre->pfam_ci ?? '' }}</span></div>
            <div class="signature-item" style="text-align:center;font-size:10px;">PADRE / MADRE / TUTOR</div>
        </div>
        <div class="signature-box">
            <div class="signature-item">Firma: <span class="dotted" style="min-width:230px;"></span></div>
            <div class="signature-item">Sello: <span class="dotted" style="min-width:235px;"></span></div>
            <div class="signature-item" style="margin-top:28px;text-align:center;font-size:10px;">
                DIRECCIÓN ACADÉMICA<br>DEPTO. DE CONTROL Y SEGUIMIENTO
            </div>
        </div>
    </div>
</body>
</html>
