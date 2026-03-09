<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boleta de Inscripción - {{ $estudiante->est_codigo }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .logo { display: table-cell; width: 80px; vertical-align: top; }
        .logo img { width: 70px; }
        .titulo { display: table-cell; text-align: center; vertical-align: middle; }
        .titulo h2 { margin: 0; font-size: 14px; }
        .titulo p { margin: 2px 0; font-size: 10px; }
        .foto { display: table-cell; width: 80px; text-align: right; vertical-align: top; }
        .foto img { width: 70px; height: 90px; object-fit: cover; border: 1px solid #000; }
        .seccion-titulo { background-color: #4472C4; color: white; padding: 5px; font-weight: bold; text-align: center; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        td { padding: 5px; border: 1px solid #000; }
        .label { font-weight: bold; width: 35%; background-color: #f0f0f0; }
        .firma { margin-top: 80px; text-align: center; border-top: 1px dotted #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="{{ public_path('img/logo.png') }}" alt="Logo">
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
            @endif
        </div>
    </div>

    <div class="seccion-titulo">DATOS DEL ESTUDIANTE</div>
    <table>
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
            <td>{{ $estudiante->est_fecha ? $estudiante->est_fecha->format('d/m/Y') : ($estudiante->created_at ? $estudiante->created_at->format('d/m/Y') : date('d/m/Y')) }}</td>
        </tr>
        <tr>
            <td class="label">Registro RUDE:</td>
            <td>{{ $estudiante->est_rude ?? '' }}</td>
            <td class="label">Nro whatsapp:</td>
            <td>{{ $estudiante->est_celular ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Unidad de proc.:</td>
            <td colspan="3">{{ $estudiante->est_procedencia ?? '' }}</td>
        </tr>
    </table>

    <div class="seccion-titulo">DATOS DEL PADRE Y MADRE DE FAMILIA O TUTOR</div>
    <table>
        <tr>
            <td class="label">Nom. Padre:</td>
            <td>{{ $padres->where('padre_tipo', 'padre')->first()->padre_nombre ?? '' }}</td>
            <td class="label">Ocupación:</td>
            <td>{{ $padres->where('padre_tipo', 'padre')->first()->padre_ocupacion ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Nom. Madre:</td>
            <td>{{ $padres->where('padre_tipo', 'madre')->first()->padre_nombre ?? '' }}</td>
            <td class="label">Ocupación:</td>
            <td>{{ $padres->where('padre_tipo', 'madre')->first()->padre_ocupacion ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Nom. Tutor:</td>
            <td>{{ $padres->where('padre_tipo', 'tutor')->first()->padre_nombre ?? '' }}</td>
            <td class="label">Parentesco:</td>
            <td>{{ $padres->where('padre_tipo', 'tutor')->first()->padre_parentesco ?? '' }}</td>
        </tr>
    </table>

    <div class="seccion-titulo">Documentos</div>
    <table>
        <tr><td>Contrato de serv. educativo:</td></tr>
        <tr><td>Acta de Compromiso:</td></tr>
        <tr><td>Compromiso del Padre y Estudiante:</td></tr>
        <tr><td>Tarjeta de Vacunas:</td></tr>
        <tr><td>Certificado de Nacimiento Estudiante:</td></tr>
        <tr><td>Cédula de identidad Estudiante:</td></tr>
        <tr><td>Cédula de identidad Padre:</td></tr>
        <tr><td>Cédula de identidad Madre:</td></tr>
        <tr><td>Cédula de identidad Tutor(a):</td></tr>
        <tr><td>Libreta electrónica gestión anterior:</td></tr>
        <tr><td>Registro RUDE:</td></tr>
    </table>

    <div class="firma">
        Firma y/o sello de la unidad educativa
    </div>

    <div class="firma" style="margin-top: 30px;">
        Firma Padre Madre o Tutor
    </div>
</body>
</html>
