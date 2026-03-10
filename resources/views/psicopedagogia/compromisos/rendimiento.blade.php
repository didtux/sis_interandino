<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de Compromiso del Padre de Familia Rendimiento Académico - 2026</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; line-height: 1.3; padding: 15mm 20mm; }
        .header { display: table; width: 100%; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; padding: 0 10px; }
        .header-info h3 { font-size: 12px; margin: 2px 0; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .title { text-align: center; margin: 10px 0; font-size: 12px; font-weight: bold; text-decoration: underline; }
        .subtitle { text-align: center; margin: 5px 0; font-size: 10px; font-weight: bold; }
        .info-line { margin: 5px 0; }
        .dotted { border-bottom: 1px dotted #000; display: inline-block; min-width: 150px; }
        .content { text-align: justify; margin: 8px 0; }
        .list-item { margin: 5px 0 5px 30px; text-align: justify; }
        .signature-section { margin-top: 20px; display: table; width: 100%; }
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

    <div class="title">ACTA DE COMPROMISO DEL PADRE DE FAMILIA</div>
    <div class="subtitle">RENDIMIENTO ACADEMICO - 2026</div>
    <div class="subtitle">EDUCACION SECUNDARIA COMUNITARIA VOCACIONAL</div>

    <div class="content">
        En la ciudad de El Alto, en fecha <span class="dotted" style="min-width: 50px; text-align: center;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d') }}</span> / <span class="dotted" style="min-width: 50px; text-align: center;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('m') }}</span> / <span class="dotted" style="min-width: 50px; text-align: center;">2026</span> En ambientes de la Unidad Educativa Privada Interandino Boliviano presentes representante de familia y/o apoderado del/la estudiante, a objeto de firmar el compromiso de seguimiento al rendimiento académico, como en el reglamento interno de la Unidad Educativa, por tanto:
    </div>

    <div class="content">
        <div class="info-line">
            Yo<span class="dotted">{{ strtoupper($caso->estudiante->padres->first()->pfam_nombres ?? '') }} {{ strtoupper($caso->estudiante->padres->first()->pfam_apellidos ?? '') }}</span>padre con C.I.<span class="dotted">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
        </div>
        <div class="info-line">
            Yo<span class="dotted"></span>madre con C.I.<span class="dotted"></span>
        </div>
        <div class="info-line">
            Estudiante: <span class="dotted">{{ strtoupper($caso->estudiante->est_nombres) }} {{ strtoupper($caso->estudiante->est_apellidos) }}</span> Curso<span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>Nivel: <span class="dotted">{{ $caso->estudiante->curso->cur_nombre ?? '' }}</span>
        </div>
    </div>

    <div class="content">
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Me comprometo a realizar el seguimiento continuo del rendimiento académico de mi hijo/a, revisando que tenga el material solicitado por los maestros/as, así como tener las actividades extracurriculares en sus cuadernos, carpeta, archivador otros asistiendo a las reuniones de curso y de la unidad educativa y talleres organizados desde Dirección Académica, dando respuesta a las notificaciones en el grupo de WhatsApp, participando de las entrevistas, para fortalecerse la comunicación con los maestros/as u con su hijo/a, durante los tres trimestres y en fechas que sea llamado para recibir información de su hijo/a.
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Si el estudiante no tiene las bases necesarias en las Áreas de Ciencias Exactas, tomando como referencia la Evaluación Diagnostica y Parcial, participará de los Cursos de Reforzamiento de manera obligatoria, al mismo obtendrá un costo adicional al margen de las mensualidades y en horario extra al oficial. (Válido de Nivel Inicial 2da Sección a 6to de Secundaria)
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Con el propósito de generar independencia y seguridad en su formación solicitamos evitar la sobreprotección de los padres y/o apoderados al mismo lenguaje de la institución.
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> El o la estudiante tiene la obligación de pasar todas las áreas que correspondan al Nivel y al Grado, así también de cumplir (con carácter obligatorio) a todas las Actividades Curriculares programadas para mejorar su aprendizaje (festivales, desfiles, Graduación y Otros).
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> En el caso de que su rendimiento o promedio no sea aceptable y sea reincidente en el incumplimiento hasta el 1er Trimestre, conociendo el trabajo de apoyo realizado por el docente de Aula realizare la Transferencia a otra Unidad Educativa de manera voluntaria, sin que exista presión alguna, vicio, dolo y no presentare ninguna queja o denuncia ante la Dirección Departamental de Educación La Paz (DDELPZ), Dirección Distrital de Educación El Alto-1 (DDEEA-1)
        </div>
        <div class="list-item">
            <span style="font-family: DejaVu Sans, sans-serif;">➤</span> Por lo cual conociendo las normas establecidas en la unidad educativa Privada y en coordinación con la Dirección Académica, firmamos el presente compromiso acatando lo estipulado en el mismo, en caso de su incumplimiento seremos los directos responsables de los resultados de fin de gestión, en conformidad firmamos al pie del presente compromiso.
        </div>
    </div>

    <div class="content" style="margin-top: 20px; text-align: center;">
        El Alto, <span class="dotted" style="min-width: 100px;">{{ \Carbon\Carbon::parse($caso->psico_fecha)->format('d/m/Y') }}</span> de 2026
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px; margin-top: 15px;"></span>
            </div>
            <div style="font-weight: bold; margin-bottom: 10px; ">FIRMA DEL PADRE O TUTOR</div>
        
            <div class="signature-item">
                <span class="dotted" style="min-width: 200px; margin-top: 15px;"></span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px; margin-top: 15px;">{{ $caso->estudiante->padres->first()->pfam_ci ?? '' }}</span>
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
                <span class="dotted" style="min-width: 200px; margin-top: 15px;"></span>
            </div>
            <div style="font-weight: bold; margin-bottom: 10px;">FIRMA DE LA MADRE O TUTORA</div>

            <div class="signature-item">
                <span class="dotted" style="min-width: 200px; margin-top: 15px;"></span>
            </div>
            <div class="signature-item">
                C. I.: <span class="dotted" style="min-width: 150px; margin-top: 15px;"></span>
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
