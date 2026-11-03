<?php

/*
|--------------------------------------------------------------------------
| Textos editables de la web pública
|--------------------------------------------------------------------------
| Cada clave se edita en Web pública → Textos y se guarda en
| landing_secciones. Mientras no tenga fila, la página muestra el valor de
| aquí, que es el texto original de la web.
|
| Tipos:
|   texto    una línea, se muestra escapado
|   titulo   como texto, pero *palabra* se resalta y el salto de línea es <br>
|   parrafo  varias líneas de texto plano
|   lista    un elemento por línea
|   html     editor enriquecido (se limpia al guardar)
|   imagen   archivo en storage/public; sin valor la página usa su diseño
*/

$slides = [
    1 => ['Desde 2005 · El Alto, La Paz · Bolivia', "Unidad Educativa Privada\n*\"Interandino Boliviano\"*",
          "Formando estudiantes autónomos, reflexivos, críticos y productivos\ncon capacidad creativa e intelectual para el vivir bien."],
    2 => ['Nuestra Visión Institucional', "Educación *Inclusiva*\ny Comunitaria",
          "Institución pluralista con valores democráticos, intercultural y plurilingüe,\nen armonía con la madre naturaleza y la humanidad."],
    3 => ['Oferta Educativa Completa', "*Tres Niveles*\nde Formación",
          "Educación Inicial en Familia Comunitaria\nPrimaria Vocacional · Secundaria Comunitaria Productiva"],
    4 => ['¡Celebrando 20 Años!', "*20 Años* de\nExcelencia Educativa",
          "Desde el 25 de mayo de 2005 al servicio de El Alto,\nconstruyendo el futuro de Bolivia con dedicación y vocación."],
];
$inicio = [];
foreach ($slides as $n => [$badge, $titulo, $sub]) {
    $inicio["inicio.slide{$n}.badge"]     = ["Carrusel {$n} · etiqueta", 'texto', $badge];
    $inicio["inicio.slide{$n}.titulo"]    = ["Carrusel {$n} · título", 'titulo', $titulo];
    $inicio["inicio.slide{$n}.subtitulo"] = ["Carrusel {$n} · subtítulo", 'titulo', $sub];
    $inicio["inicio.slide{$n}.imagen"]    = ["Carrusel {$n} · imagen de fondo (opcional)", 'imagen', null];
}
foreach ([1 => ['20+', 'Años de Excelencia'], 2 => ['3', 'Niveles Educativos'], 3 => ['2005', 'Año de Fundación'],
          4 => ['R.A.', 'No. 311/2006'], 5 => ['3', 'Fundadores']] as $n => [$num, $etq]) {
    $inicio["inicio.cifra{$n}.numero"]   = ["Cifra {$n} · número", 'texto', $num];
    $inicio["inicio.cifra{$n}.etiqueta"] = ["Cifra {$n} · etiqueta", 'texto', $etq];
}

$valores = [];
foreach ([1 => ['Inclusión', 'Formamos estudiantes sin distinción, respetando la diversidad cultural, social y de capacidades en comunidad.'],
          2 => ['Excelencia', 'Comprometidos con la calidad educativa y la superación permanente de docentes y estudiantes.'],
          3 => ['Comunidad', 'Fortalecemos vínculos sociocomunitarios, la participación familiar y el trabajo colectivo solidario.'],
          4 => ['Innovación', 'Metodologías activas y actualizadas que estimulan el pensamiento crítico y la creatividad.']] as $n => [$t, $d]) {
    $valores["nosotros.valor{$n}.titulo"] = ["Valor {$n} · nombre", 'texto', $t];
    $valores["nosotros.valor{$n}.texto"]  = ["Valor {$n} · descripción", 'parrafo', $d];
}

$niveles = [];
foreach ([
    'inicial' => ['Educación Inicial',
        'Sembramos las bases del aprendizaje en los primeros años de vida, trabajando de la mano con la familia y la comunidad para estimular el desarrollo integral del niño en un ambiente seguro, cálido y creativo.',
        "Estimulación temprana y desarrollo psicomotriz\nDesarrollo del lenguaje oral y expresivo\nSocialización y convivencia comunitaria\nArte, música, juego y expresión creativa\nAprestamiento para educación primaria\nFormación en valores y hábitos saludables",
        'Niños de 3 a 5 años · Ambiente lúdico y familiar'],
    'primaria' => ['Educación Primaria',
        'Desarrollamos habilidades y conocimientos fundamentales integrando saberes culturales, científicos y vocacionales, construyendo la base académica sólida del estudiante de forma significativa y contextualizada.',
        "Matemáticas, Comunicación y Lenguaje\nCiencias Naturales, Sociales y Cosmos\nEducación física y artística integral\nTecnología y emprendimiento básico\nValores sociocomunitarios y ciudadanía\nInglés y lengua originaria",
        'De 1° a 6° de primaria · 6 a 12 años'],
    'secundaria' => ['Educación Secundaria',
        'Preparamos jóvenes para los retos del futuro con una formación productiva, científica y humanística, desarrollando el pensamiento crítico y la capacidad de contribuir activamente a su comunidad y al país.',
        "Ciencias exactas y experimentales\nHumanidades y Ciencias Sociales\nTécnica tecnológica productiva\nProyecto sociocomunitario productivo\nOrientación hacia la educación superior\nBachillerato técnico-humanístico",
        'De 1° a 6° de secundaria · 12 a 18 años'],
] as $k => [$nombre, $desc, $lista, $nota]) {
    $niveles["niveles.{$k}.descripcion"]      = ["{$nombre} · descripción", 'parrafo', $desc];
    $niveles["niveles.{$k}.caracteristicas"] = ["{$nombre} · características (una por línea)", 'lista', $lista];
    $niveles["niveles.{$k}.nota"]            = ["{$nombre} · edades / nota", 'texto', $nota];
}

return [
    'paginas' => [
        'general' => [
            'titulo' => 'Datos generales (pie de página)',
            'campos' => [
                'general.nombre_corto' => ['Nombre corto', 'texto', 'Interandino Boliviano'],
                'general.descripcion'  => ['Descripción del pie de página', 'parrafo', 'Unidad Educativa Privada comprometida con la formación integral de estudiantes en El Alto, La Paz, Bolivia desde el año 2005.'],
                'general.lema'         => ['Lema', 'texto', 'Contribuir, mejorar y desarrollar'],
                'general.ubicacion'    => ['Ubicación', 'texto', 'El Alto, La Paz — Bolivia'],
                'general.fundacion'    => ['Fecha de fundación', 'texto', '25 de Mayo de 2005'],
                'general.resolucion'   => ['Resolución administrativa', 'texto', 'No. 311/2006'],
            ],
        ],
        'inicio' => [
            'titulo' => 'Inicio',
            'campos' => $inicio + [
                'inicio.avisos.titulo' => ['Título de la sección de avisos', 'titulo', 'Avisos y *Novedades*'],
            ],
        ],
        'nosotros' => [
            'titulo' => 'Nosotros',
            'campos' => [
                'nosotros.hero_texto' => ['Texto bajo el título', 'parrafo', 'Conoce la misión, visión y valores que guían nuestra labor educativa en El Alto, Bolivia.'],
                'nosotros.intro'      => ['Introducción', 'parrafo', 'Los principios fundacionales que nos orientan en la formación de estudiantes autónomos, reflexivos y productivos para el vivir bien en Bolivia.'],
                'nosotros.vision'     => ['Visión', 'html', '<p>Ser una Unidad Educativa <strong>inclusiva, pluralista y comunitaria</strong> con valores y principios democráticos, formando estudiantes autónomos, reflexivos, críticos y productivos con capacidad creativa e intelectual.</p><p>Construir el conocimiento científico, técnico, tecnológico y artístico bajo la concepción del <strong>vivir bien en la diversidad</strong>, a través de una educación intracultural, intercultural y plurilingüe, en interrelación con la madre naturaleza y la humanidad.</p>'],
                'nosotros.mision'     => ['Misión', 'html', '<p>Formar estudiantes con <strong>principios y valores sociocomunitarios</strong>, ético-morales; autónomos, creativos, reflexivos y críticos; investigadores y constructores de su propio conocimiento científico, técnico, tecnológico y artístico.</p><p>En un ambiente <strong>cómodo, interactivo, con equidad y sin violencia</strong>, guiado por docentes profesionales formados en el modelo sociocomunitario productivo, comprometidos con la actualización permanente.</p>'],
            ] + $valores,
        ],
        'niveles' => [
            'titulo' => 'Niveles',
            'campos' => [
                'niveles.hero_texto' => ['Texto bajo el título', 'parrafo', 'Formación integral desde la infancia hasta el bachillerato en un ambiente comunitario y productivo.'],
                'niveles.intro'      => ['Introducción', 'parrafo', 'Acompañamos cada etapa del desarrollo del estudiante con metodologías actualizadas, docentes comprometidos y un enfoque sociocomunitario productivo.'],
            ] + $niveles + [
                'niveles.cta_titulo' => ['Llamado a inscribir · título', 'texto', '¿Interesado en inscribir a tu hijo/a?'],
                'niveles.cta_texto'  => ['Llamado a inscribir · texto', 'parrafo', 'Visítanos o escríbenos para recibir información detallada sobre el proceso de inscripción, calendario escolar y requisitos para cada nivel educativo.'],
            ],
        ],
        'historia' => [
            'titulo' => 'Historia',
            'campos' => [
                'historia.hero_texto' => ['Texto bajo el título', 'parrafo', 'Veinte años de compromiso con la educación en El Alto, construyendo historia y formando generaciones.'],
                'historia.intro'      => ['Introducción', 'parrafo', 'La historia de una institución que nació del compromiso de tres hermanos con la educación de El Alto y que hoy celebra dos décadas de excelencia.'],
                'historia.relato'     => ['Relato', 'html', '<p>La Unidad Educativa Privada <strong>"INTERANDINO BOLIVIANO"</strong> de la Ciudad de El Alto del Departamento de La Paz, fue fundada el <strong>25 de mayo del año 2005</strong> por iniciativa propia de los Señores Néstor Quispe Copana, René Víctor Quispe Copana y Juan Quispe Copana.</p><p>Quienes al observar la problemática educativa en la ciudad de El Alto decidieron invertir en la construcción de una Unidad Educativa diferente de las demás. Su visión principal era ser una institución <strong>inclusiva, pluralista y comunitaria</strong> con valores y principios democráticos.</p><p>Desde sus inicios, la institución se propuso formar estudiantes autónomos, reflexivos, críticos y productivos con capacidad creativa e intelectual en la construcción de sus conocimientos científicos, técnicos, tecnológicos y artísticos bajo la concepción del <strong>vivir bien en la diversidad</strong>.</p><p>Interandino Boliviano es una Unidad Educativa Privada donde se transmite una filosofía específica del mundo, del hombre y de la historia. Los principios y valores evangélicos se convierten en <strong>normas educativas de convivencia fraterna</strong>, en el logro por la educación integral dirigida por profesores dedicados, comprometidos y en constante actualización.</p><p>A través de sus tres niveles educativos — Inicial, Primaria Comunitaria Vocacional y Secundaria Comunitaria Productiva — la institución ha crecido sostenidamente, sumando familias que confían en su propuesta educativa diferenciada y adaptada al contexto cultural de El Alto y Bolivia.</p><p>Hoy, con <strong>veinte años de funcionamiento</strong>, Interandino Boliviano reafirma su compromiso de seguir contribuyendo, mejorando y desarrollando la educación en El Alto, formando cada año una nueva generación de estudiantes preparados para el futuro.</p>'],
                'historia.cita'       => ['Cita', 'texto', '"Adquiere Sabiduría, adquiere inteligencia"'],
                'historia.cita_fuente'=> ['Fuente de la cita', 'texto', 'Proverbios 4:5'],
            ],
        ],
        'inscripciones' => [
            'titulo' => 'Inscripciones',
            'campos' => [
                'inscripciones.hero_texto' => ['Texto bajo el título', 'parrafo', 'Requisitos, fechas y pasos para pre-inscribir e inscribir a tu hijo/a en nuestra Unidad Educativa.'],
                'inscripciones.intro'      => ['Introducción', 'html', '<p>La <strong>pre-inscripción</strong> reserva el cupo de tu hijo/a para la próxima gestión. La inscripción se completa al inicio del año escolar, presentando los documentos de cada nivel.</p>'],
                'inscripciones.pasos'      => ['Pasos (uno por línea)', 'lista', "Escríbenos o visítanos para solicitar información\nPresenta los requisitos del nivel en secretaría\nPaga la reserva de cupo en caja y recibe tu recibo\nCompleta la inscripción al inicio de la gestión"],
                'inscripciones.nota'       => ['Nota al pie', 'parrafo', 'Los cupos son limitados. Las fechas pueden cambiar; ante cualquier duda comunícate con secretaría.'],
            ],
        ],
    ],

    // Bloques repetibles (landing_bloques) y la página donde se ven
    'bloques' => [
        'AVISO'     => ['Avisos y novedades', 'Inicio', 'fas fa-bullhorn'],
        'REQUISITO' => ['Requisitos de inscripción', 'Inscripciones', 'fas fa-clipboard-check'],
        'DOCUMENTO' => ['Documentos descargables', 'Inscripciones', 'fas fa-file-download'],
        'AUTORIDAD' => ['Autoridades', 'Nosotros', 'fas fa-user-tie'],
        'HITO'      => ['Línea del tiempo', 'Historia', 'fas fa-history'],
    ],
];
