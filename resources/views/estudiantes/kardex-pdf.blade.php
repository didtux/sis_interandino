<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boleta de Inscripción - {{ $estudiante->est_codigo }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px 20px; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 70px; vertical-align: top; }
        .logo img { width: 60px; }
        .titulo { display: table-cell; text-align: center; vertical-align: middle; }
        .titulo h2 { margin: 0; font-size: 13px; }
        .titulo p { margin: 1px 0; font-size: 9px; }
        .foto { display: table-cell; width: 70px; text-align: right; vertical-align: top; }
        .foto img { width: 65px; height: 80px; object-fit: cover; border: 1px solid #000; }
        .foto-placeholder { width: 65px; height: 80px; border: 1px solid #000; text-align: center; line-height: 80px; font-size: 8px; color: #999; float: right; }
        .seccion-titulo { background-color: #4472C4; color: white; padding: 3px 5px; font-weight: bold; text-align: center; margin-top: 8px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 3px; }
        td { padding: 3px 5px; border: 1px solid #000; font-size: 10px; }
        .label { font-weight: bold; width: 25%; background-color: #f0f0f0; }
        .docs-table td { padding: 3px 5px; font-size: 9px; width: 50%; }
        .firma-container { display: table; width: 100%; margin-top: 60px; }
        .firma-cell { display: table-cell; width: 50%; text-align: center; padding: 0 30px; }
        .firma-line { border-top: 1px dotted #000; padding-top: 4px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="titulo">
            <h2>U.E. PRIVADA INTERANDINO BOLIVIANO</h2>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
            <p><strong>BOLETA DE INSCRIPCIÓN</strong></p>
        </div>
        <div class="foto">
            @if($estudiante->est_foto)
                <img src="{{ public_path('storage/' . $estudiante->est_foto) }}" alt="Foto">
            @else
                <div class="foto-placeholder">FOTO</div>
            @endif
        </div>
    </div>

    <div class="seccion-titulo">DATOS DEL ESTUDIANTE</div>
    <table>
        <tr>
            <td class="label">Nro:</td>
            <td>{{ $numero }}</td>
            <td class="label">Código:</td>
            <td>{{ $estudiante->est_codigo }}</td>
        </tr>
        <tr>
            <td class="label">Nombres y Apellidos:</td>
            <td colspan="3">{{ $estudiante->est_nombres }} {{ $estudiante->est_apellidos }}</td>
        </tr>
        <tr>
            <td class="label">Cédula de Identidad:</td>
            <td>{{ $estudiante->est_ci }}</td>
            <td class="label">Fecha de Nacimiento:</td>
            <td>{{ $estudiante->est_fechanac ? \Carbon\Carbon::parse($estudiante->est_fechanac)->format('d/m/Y') : '' }}</td>
        </tr>
        <tr>
            <td class="label">Lugar de Nacimiento:</td>
            <td colspan="3">{{ $estudiante->est_lugarnac ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Curso Actual:</td>
            <td>{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</td>
            <td class="label">Fecha de Inscripción:</td>
            <td>{{ $estudiante->est_fecha ? $estudiante->est_fecha->format('d/m/Y') : date('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Código RUDE:</td>
            <td>{{ $estudiante->est_rude ?? '' }}</td>
            <td class="label">Nro. Celular:</td>
            <td>{{ $estudiante->est_celular ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">U.E. de Procedencia:</td>
            <td colspan="3">{{ $estudiante->est_ueprocedencia ?? '' }}</td>
        </tr>
    </table>

    @php
        $padresVinculados = $estudiante->padres;
        $tienePadres = $padresVinculados->count() > 0;
    @endphp

    @if($tienePadres)
    <div class="seccion-titulo">DATOS DEL PADRE Y MADRE DE FAMILIA O TUTOR</div>
    <table>
        @foreach($padresVinculados as $p)
        <tr>
            <td class="label">Nombre ({{ $p->pfam_parentesco ?? 'Familiar' }}):</td>
            <td>{{ $p->pfam_nombres }}</td>
            <td class="label">CI:</td>
            <td>{{ $p->pfam_ci }}</td>
        </tr>
        <tr>
            <td class="label">Celular:</td>
            <td>{{ $p->pfam_numeroscelular ?? '' }}</td>
            <td class="label">Domicilio:</td>
            <td>{{ $p->pfam_domicilio ?? '' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <div class="seccion-titulo">Documentos</div>
    <table class="docs-table">
        <tr>
            <td>Contrato de serv. educativo:</td>
            <td>Cédula de identidad Estudiante:</td>
        </tr>
        <tr>
            <td>Acta de Compromiso:</td>
            <td>Cédula de identidad Padre:</td>
        </tr>
        <tr>
            <td>Compromiso del Padre y Estudiante:</td>
            <td>Cédula de identidad Madre:</td>
        </tr>
        <tr>
            <td>Tarjeta de Vacunas:</td>
            <td>Cédula de identidad Tutor(a):</td>
        </tr>
        <tr>
            <td>Certificado de Nacimiento Estudiante:</td>
            <td>Libreta electrónica gestión anterior:</td>
        </tr>
        <tr>
            <td>Registro RUDE:</td>
            <td></td>
        </tr>
    </table>

    <div class="firma-container">
        <div class="firma-cell">
            <div class="firma-line">Firma y/o sello de la unidad educativa</div>
        </div>
        <div class="firma-cell">
            <div class="firma-line">Firma Padre, Madre o Tutor</div>
        </div>
    </div>
</body>
</html>
