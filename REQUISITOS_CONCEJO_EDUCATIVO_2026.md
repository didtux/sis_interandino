# REQUISITOS — SISTEMA INTERANDINO BOLIVIANO
## Módulo Académico — Concejo Educativo 2026

> **Documento para agente de programación.**  
> Stack: Laravel 8 · Eloquent · MySQL (MariaDB 10.4) · BD: `interandino`  
> Preparado con base en el PDF de requerimientos del concejo y el volcado SQL actualizado al 03-05-2026.

---

## 1. CONTEXTO Y TABLAS CLAVE

### 1.1 Tablas principales involucradas

| Tabla | Propósito |
|---|---|
| `colegio_estudiantes` | Kárdex de cada estudiante (foto, CI, curso, estado `est_visible`) |
| `colegio_cursos` | Cursos/paralelos (`cur_codigo`, `cur_nombre`, `cur_visible`) |
| `colegio_lista_curso` | Lista oficial por curso y gestión (`lista_numero`, `lista_gestion`) |
| `colegio_materias` | Materias (`mat_campo`, `mat_orden`) |
| `colegio_curso_materia` | Relación curso ↔ materia |
| `colegio_curso_materia_docente` | Relación curso ↔ materia ↔ docente (`curmatdoc_id`) |
| `colegio_docentes` | Datos del docente |
| `colegio_notas` | Nota por período, por `curmatdoc_id`, por `est_codigo` |
| `colegio_notas_detalle` | Detalle sub-columnas de notas (si aplica) |
| `colegio_materia_grupos` | Grupos de promedio (e.g. "Comunicación y Lenguaje") |
| `colegio_materia_grupo_detalle` | Materias que integran cada grupo |
| `notas_config_periodos` | Períodos: 1er, 2do, 3er Trimestre (`periodo_id`, `periodo_numero`) |
| `notas_config_dimensiones` | Dimensiones: SER(10), SABER(45), HACER(40), AUTOEVALUACION(5) |
| `asistencia_atrasos` | Atrasos por estudiante |
| `asistencia_permisos` | Permisos y licencias (`permiso_tipo`: PERMISO / LICENCIA) |
| `colegio_asistencia` | Asistencias diarias por estudiante |
| `enfermeria_registros` | Registros de enfermería por estudiante |
| `psicopedagogia_casos` | Compromisos verbales/escritos del área de psicología |
| `cole_padresfamilia` | Padres/tutores con teléfono (WhatsApp) |
| `rela_estudiantespadres` | Relación estudiante ↔ padre |
| `inscripciones` | Inscripción del estudiante por gestión |

### 1.2 Estado del estudiante

El campo `est_visible` en `colegio_estudiantes` determina el estado:
- `1` = ACTIVO
- `0` = RETIRADO / INACTIVO

En **todos** los reportes, los estudiantes con `est_visible = 0` deben aparecer visualmente distinguidos (texto en **rojo** o etiqueta `RETIRADO`). No deben ocultarse, deben incluirse en listas y centralizadores.

### 1.3 Orden de estudiantes en lista

El orden proviene de `colegio_lista_curso.lista_numero` filtrado por `lista_gestion = YEAR(NOW())` y `cur_codigo`. Los estudiantes nuevos cuyo apellido comience con "A" se ubican al **final** de la lista (no en orden alfabético estándar), respetando el `lista_numero` ya registrado.

### 1.4 Orden de cursos

Los cursos deben ordenarse de menor a mayor nivel educativo. Secuencia esperada:

```
PreKinder → Kinder → 1ro Primaria A → 1ro Primaria B → 2do Primaria A → 2do Primaria B → ... → 6to Secundaria B
```

La tabla `colegio_cursos` no tiene un campo de orden explícito. **Se debe agregar un campo `cur_orden INT DEFAULT 0`** o aplicar un ORDER CASE en todas las consultas que listen cursos. Se recomienda agregar `cur_orden` y asignar valores al momento de migración.

---

## 2. MÓDULO NOTAS — INTERFAZ Y LÓGICA

### 2.1 Vista principal de notas

**Ruta sugerida:** `Rendimiento / Notas`

Filtros disponibles:
- Selector de trimestre (`notas_config_periodos.periodo_id`)
- Selector de curso (`colegio_cursos` — orden de menor a mayor nivel)
- Búsqueda por nombre de estudiante

La tabla muestra estudiantes en el orden de `colegio_lista_curso.lista_numero`. Columnas: número de lista, nombre, y una columna por cada materia asignada al curso en ese período.

**Regla de visualización de notas:**
- Las notas de cada celda se muestran como el `nota_promedio_trimestral` de `colegio_notas`.
- Notas reprobadas (< 51) deben destacarse visualmente (rojo o negrita roja).
- Estudiantes retirados (`est_visible = 0`) aparecen con nombre en rojo o badge `RETIRADO`.

**Acciones disponibles desde la interfaz:**
- Botón editar por estudiante (lápiz) → abre modal con detalle de dimensiones (SER, SABER, HACER, AUTOEVALUACION).
- Botón eliminar nota por estudiante.
- Botón **Importar Registro** → importar notas desde Excel (flujo existente: `notas_importaciones`).
- Botón **Importar Centralizador** → importar centralizador desde Excel.

**Botones de generación de reportes (iconos en cabecera):**

| Botón | Reporte generado |
|---|---|
| Cen. Anual | Centralizador Anual (PDF) |
| Cen. Trim | Centralizador Trimestral (PDF) |
| Doc. Concejo | Documento para Concejo (PDF) |
| C.H. Curso | Cuadro de Honor por Curso (PDF) |
| C.H. Colegio | Cuadro de Honor por Colegio (PDF) |
| C.H. Nivel | Cuadro de Honor por Nivel (PDF) |

### 2.2 Lógica de promedios

**Regla fundamental:** cada materia es independiente por campo. Solo se promedian entre sí las materias que estén explícitamente agrupadas en `colegio_materia_grupos` + `colegio_materia_grupo_detalle`.

```sql
-- Ejemplo de grupo existente:
-- grupo_id=1, grupo_nombre='comunicaciony lenguaje'
-- detalle: literatura, mate, quimica (orden 0,1,2)
```

- El promedio de un grupo = promedio aritmético de `nota_promedio_trimestral` de las materias del grupo para ese estudiante y período.
- Las materias sin grupo no se promedian con ninguna otra.
- **En boletines y centralizadores:** promedios expresados como **números enteros** (`ROUND()`).
- **En cuadro de honor:** usar decimales con 1 decimal (`ROUND(x, 1)`) para desempate.

### 2.3 Configuración de agrupaciones (desde UI)

Pantalla: `Parametrización / Materias por Curso → Configuración carga de Notas`

- Permitir crear/editar grupos en `colegio_materia_grupos`.
- Permitir asignar materias a grupos en `colegio_materia_grupo_detalle` (con `detalle_orden`).
- Un grupo puede contener materias de distintos campos (`mat_campo`).
- Una materia solo puede pertenecer a un grupo a la vez (validar unicidad en `colegio_materia_grupo_detalle.mat_codigo`).

---

## 3. REPORTES — ESPECIFICACIONES DETALLADAS

### 3.1 Centralizador Trimestral (PDF)

**Query base:**

```sql
SELECT 
  lc.lista_numero,
  CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre_completo,
  e.est_foto,
  e.est_visible,
  -- por cada materia del curso:
  n.nota_promedio_trimestral,
  -- suma total de notas
  SUM(n.nota_promedio_trimestral) AS suma_total,
  -- promedio general (entero)
  ROUND(AVG(n.nota_promedio_trimestral)) AS promedio_trimestral,
  -- ranking dentro del curso
  RANK() OVER (ORDER BY AVG(n.nota_promedio_trimestral) DESC) AS cuadro_honor,
  -- faltas y atrasos del trimestre
  COUNT(DISTINCT CASE WHEN ca.estado = 'F' THEN ca.fecha END) AS faltas,
  COUNT(DISTINCT aa.atraso_id) AS atrasos,
  COUNT(DISTINCT aa.atraso_id) AS tardanzas
FROM colegio_lista_curso lc
JOIN colegio_estudiantes e ON e.est_codigo = lc.est_codigo
LEFT JOIN colegio_notas n ON n.est_codigo = e.est_codigo AND n.periodo_id = :periodo_id
LEFT JOIN colegio_asistencia ca ON ca.est_codigo = e.est_codigo -- filtrar por trimestre
LEFT JOIN asistencia_atrasos aa ON aa.estud_codigo = e.est_codigo -- filtrar por trimestre
WHERE lc.cur_codigo = :cur_codigo AND lc.lista_gestion = :gestion
ORDER BY lc.lista_numero ASC
```

**Formato visual del reporte:**
- Encabezado: logo de la institución, nombre, trimestre, curso, fecha/hora de impresión, Control-Cole.
- Columnas: Nro, Apellidos y Nombres, una columna por materia (abreviatura), Sumatoria, Promedio Trimestral, Cuadro de Honor (Nro de lugar), Nro Reprobaciones, Faltas y Atrasos (DT, TA, TL por trimestre), Uniformes, Indisciplina, Decomisos, Compromisos (SI / NO).
- Los **3 primeros lugares** del ranking deben estar marcados visualmente (fondo verde o número en verde/negrita).
- Todos los estudiantes llevan su número de lugar (1°, 2°, 3°, 4°...).
- Estudiantes retirados: fila en rojo o con badge "RETIRADO".

### 3.2 Centralizador Anual (PDF)

Mismo formato que el trimestral, pero muestra los 3 trimestres lado a lado más el total anual.

- Promedio anual = promedio de los 3 promedios trimestrales (entero).
- Para el cuadro de honor anual usar el decimal para desempate.
- Ranking anual recalculado al finalizar los 3 períodos.
- El formato en Excel ya fue entregado al desarrollador — respetar esa estructura de columnas.

### 3.3 Boletín Individual (PDF por estudiante)

Campos requeridos:
- **Cabecera:** logo, nombre de la institución, año de escolaridad, curso/sección.
- **Datos del estudiante:** nombre completo, foto (`est_foto`), grado, colegio de procedencia (`est_ueprocedencia`).
- **Tabla de notas:** filas = materias; columnas = 1er Trim, 2do Trim, 3er Trim, Puntaje Final.
  - Materias agrupadas aparecen bajo su grupo con su promedio de grupo.
  - Promedio por materia = entero; puntaje final = entero.
- **Sección Asistencia (por trimestre):**
  - Atrasos (de `asistencia_atrasos`)
  - Faltas (de `colegio_asistencia` donde `estado = 'F'`)
  - Licencias (de `asistencia_permisos` donde `permiso_tipo = 'LICENCIA'`)
  - Horas clase (calculado desde `asistencia_configuracion` y días del período)
  - Total anual de cada uno.
- **Sección Psicología/Enfermería:**
  - Enfermería: `COUNT(enf_id)` de `enfermeria_registros` por trimestre.
  - Compromisos: `COUNT(psico_id)` de `psicopedagogia_casos` por trimestre, indicando si son VERBAL o ESCRITO.
- **Sección Control y Seguimiento:** Cláusulas aprobado/reprobado por materia.

### 3.4 Documento para Concejo (módulo aparte)

> **Importante:** Este módulo debe ser una sección INDEPENDIENTE en el menú, no integrada al módulo de notas estándar.

**Ruta sugerida:** `Concejo / Documento Concejo`

**Contenido por estudiante:**
- Foto del estudiante (`est_foto`)
- Nombre completo, Grado (`cur_nombre`), Colegio de procedencia
- Atrasos totales del año
- Faltas totales del año
- Licencias totales del año
- Registros de enfermería (conteo) → `enfermeria_registros`
- Compromisos verbales y escritos → `psicopedagogia_casos.psico_tipo_acuerdo`
- Tabla de notas por materia: filas = Período (1er Trim, 2do Trim, 3er Trim, Promedio); columnas = cada materia asignada al curso.
- El promedio final se expresa en **número entero** para determinar aprobado/reprobado.
- Aprobado ≥ 51, Reprobado < 51.

**Query de notas para concejo:**

```sql
SELECT 
  m.mat_nombre,
  MAX(CASE WHEN n.periodo_id = 1 THEN ROUND(n.nota_promedio_trimestral) END) AS t1,
  MAX(CASE WHEN n.periodo_id = 2 THEN ROUND(n.nota_promedio_trimestral) END) AS t2,
  MAX(CASE WHEN n.periodo_id = 3 THEN ROUND(n.nota_promedio_trimestral) END) AS t3,
  ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio_decimal -- para mostrar con decimales en la tabla
FROM colegio_notas n
JOIN colegio_curso_materia_docente cmd ON cmd.curmatdoc_id = n.curmatdoc_id
JOIN colegio_materias m ON m.mat_codigo = cmd.mat_codigo
WHERE n.est_codigo = :est_codigo
GROUP BY m.mat_codigo, m.mat_nombre
ORDER BY m.mat_orden ASC
```

**Generación PDF:** el documento debe poder generarse en PDF con encabezado institucional (logo, nombre U.E., año escolar, fecha, "Control-cole").

### 3.5 Cuadro de Honor

Se generan 3 variantes: **por Curso**, **por Nivel**, **por Colegio (todos los cursos)**.

**Lógica de ranking:**

```sql
-- Suma total de notas del estudiante en todos los períodos del año
SELECT 
  e.est_codigo,
  CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre,
  SUM(n.nota_promedio_trimestral) AS suma_anual,
  ROUND(AVG(n.nota_promedio_trimestral), 1) AS promedio_decimal,
  RANK() OVER (PARTITION BY lc.cur_codigo ORDER BY SUM(n.nota_promedio_trimestral) DESC) AS posicion
FROM colegio_lista_curso lc
JOIN colegio_estudiantes e ON e.est_codigo = lc.est_codigo AND e.est_visible = 1
JOIN colegio_notas n ON n.est_codigo = e.est_codigo
WHERE lc.cur_codigo = :cur_codigo AND lc.lista_gestion = :gestion
GROUP BY e.est_codigo
```

**Formato del reporte (según imagen de referencia):**
- Encabezado con logo de la U.E. (Interandino Boliviano), dirección, teléfono.
- Título: "CUADRO DE HONOR" + nombre del curso.
- Cabecera derecha: Trimestre o Año, Nivel (lugar dentro del nivel), Unidad Educativa (lugar dentro de la UE), Promedio del Curso.
- Tabla: Posición | Nombre del Alumno | Suma | Promedio (con 1 decimal).
- Para **por Colegio** y **por Nivel**, la `PARTITION BY` cambia a nivel o global.

**Notas importantes:**
- El desempate se hace por decimales (`suma_anual` primero, luego `promedio_decimal`).
- Los promedios mostrados en la tabla del cuadro de honor usan **1 decimal** (no entero).
- Solo estudiantes activos (`est_visible = 1`) participan del cuadro de honor.

### 3.6 Lista de Mejores 3 por Curso (Excel y PDF)

Generar un reporte con los top 3 de cada curso ordenados de mayor a menor.

```sql
SELECT 
  cc.cur_nombre AS curso,
  sub.posicion,
  CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre
FROM (
  SELECT 
    lc.cur_codigo,
    e.est_codigo,
    RANK() OVER (PARTITION BY lc.cur_codigo ORDER BY SUM(n.nota_promedio_trimestral) DESC) AS posicion
  FROM colegio_lista_curso lc
  JOIN colegio_estudiantes e ON e.est_codigo = lc.est_codigo AND e.est_visible = 1
  JOIN colegio_notas n ON n.est_codigo = e.est_codigo
  WHERE lc.lista_gestion = :gestion
  GROUP BY lc.cur_codigo, e.est_codigo
) sub
JOIN colegio_estudiantes e ON e.est_codigo = sub.est_codigo
JOIN colegio_cursos cc ON cc.cur_codigo = sub.cur_codigo
WHERE sub.posicion <= 3
ORDER BY [orden_de_cursos], sub.posicion ASC
```

El formato de salida agrupa por curso (como se muestra en el PDF de referencia página 9).

---

## 4. MÓDULO PARAMETRIZACIÓN

### 4.1 Paralelos / Cursos

- Lista de cursos con: Nivel, Nombre, Abreviado, Cupo, Estado.
- Crear/editar curso: nombre, abreviatura, cupo, nivel, estado.
- **Agregar campo `cur_orden INT DEFAULT 0`** a `colegio_cursos` para ordenamiento.
- Los cursos deben ordenarse de menor a mayor nivel (PreKinder → ... → 6to Sec B).
- Filtros: por servicio (PRE-INSCRIPCIÓN, INSCRIPCIÓN), por curso, búsqueda por nombre.
- El estado debe ser claramente visible (ACTIVO / INACTIVO).

### 4.2 Niveles

Tabla `colegio_cursos` actualmente no tiene un campo de "nivel" separado. Se requiere:
- Crear tabla `colegio_niveles` o agregar campo `cur_nivel VARCHAR(20)` (INICIAL, PRIMARIA, SECUNDARIA).
- Mostrar listado de niveles con abreviatura y estado.
- Permitir crear/editar/eliminar niveles.

> **Alternativa sin migración:** usar el patrón del nombre del curso para inferir el nivel y aplicar un `CASE` en las consultas.

### 4.3 Gestiones

- Tabla de gestiones con nombre literal, abreviado y estado (ACTIVO/INACTIVO).
- Actualmente gestionado implícitamente por `lista_gestion` y `notas_config_periodos.periodo_gestion`.
- Se recomienda crear tabla `colegio_gestiones` o reutilizar la inferencia del año.
- El estado de la gestión debe ser visible (gestión 2025 = INACTIVO, 2026 = ACTIVO).

### 4.4 Materias por Curso

Pantalla: `Parametrización / Materias por Curso`

Columnas visibles: Nro, Materia, Docente, Agrupar (grupo de promedio), Campo, Nom. Hoja, Nom. Col, Nom. Fila.

- Permitir asignar docente a cada materia del curso (`colegio_curso_materia_docente`).
- Permitir asignar un grupo de promedio (`colegio_materia_grupos`) a cada materia.
- **Botón "Configuración carga de Notas"** para gestionar grupos y sus materias.

### 4.5 Horarios / Turnos

- La creación de turnos debe contemplar turnos independientes (no solo para asistencia).
- Campos: descripción, período, tipo de horario (TURNO MAÑANA / TARDE / NOCHE).
- Filtros: por período, por tipo de horario.
- Los turnos deben poder asignarse a cursos independientemente de la configuración de asistencia.

### 4.6 Datos de la Institución (Unidad Educativa)

Pantalla: `Unidad Educativa / Editar Perfil`

Campos editables:
- Logo (upload de imagen)
- Denominación
- Nombre de la unidad educativa
- Dirección
- Teléfono
- Ciudad
- Email

Estos datos se usan en todos los encabezados de los reportes PDF. Deben recuperarse desde la BD (tabla de configuración del sistema, ya existente o a crear).

---

## 5. MÓDULO ESTUDIANTES

### 5.1 Registro de Estudiante (Kárdex)

Pantalla: `Académico / Registro Estudiantes`

Campos del formulario (según imagen de referencia):
- Foto (upload)
- Nombre, Apellido Paterno, Apellido Materno
- CI + LP (lugar expedición)
- Fecha de nacimiento (`est_fechanac`)
- Sexo (MASCULINO / FEMENINO)
- Lugar de nacimiento (`est_lugarnac`)
- Teléfono WhatsApp (`est_celular`)
- Zona, Calle, Nro
- Unidad de procedencia (`est_ueprocedencia`)
- RUDE (`est_rude`)
- Código (auto-generado)
- Número de tarjeta
- Curso y paralelo (selector)
- Botón "Guardar Cambios"

### 5.2 Lista de Estudiantes — Filtros y Acciones

Filtros: por curso, por estado (REGISTRADOS / RETIRADOS / TODOS), búsqueda por nombre.

Columnas: Código, Estudiante, CI, Sexo, Fecha Reg., Fecha Ret., Estado.

Acciones por estudiante (iconos):
- Subir (mover en lista)
- Bajar (mover en lista)
- Editar
- Ver detalle
- Cambiar estado (retirar/activar)
- Eliminar

**Botones de exportación:**
- **Listado** (PDF): lista del curso con foto y datos básicos.
- **Listado Excel** (`xlsm` o `xlsx`): datos completos incluyendo teléfono de padres.
- **Lis. Contactos** (PDF): lista con nombre del estudiante + nombre y teléfono del padre/tutor → desde `cole_padresfamilia` + `rela_estudiantespadres`.
- **Numerar**: reasignar `lista_numero` automáticamente.
- **Códigos QR**: generar PDF con QR de cada estudiante (código = `est_codigo`).

### 5.3 Lista de Reprobados por Curso

Desde la vista de estudiantes, botón "Reprobados":

```sql
SELECT 
  lc.lista_numero,
  CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre,
  COUNT(CASE WHEN ROUND(n.nota_promedio_trimestral) < 51 THEN 1 END) AS materias_reprobadas
FROM colegio_lista_curso lc
JOIN colegio_estudiantes e ON e.est_codigo = lc.est_codigo
JOIN colegio_notas n ON n.est_codigo = e.est_codigo AND n.periodo_id = :periodo_id
WHERE lc.cur_codigo = :cur_codigo AND lc.lista_gestion = :gestion
HAVING materias_reprobadas > 0
ORDER BY lc.lista_numero ASC
```

---

## 6. MÓDULO DOCENTES

### 6.1 Datos del Docente

Campos requeridos (formulario de alta/edición):
- Nombre, Apellido Paterno, Apellido Materno
- CI
- Teléfono
- Sexo (MASCULINO / FEMENINO)
- Descripción / Especialidad
- Horarios de entrevista: múltiples rangos con Día, Turno, Hora inicio, Hora fin

La tabla `colegio_docentes` actualmente no tiene campos de teléfono, sexo, horarios de entrevista. **Agregar columnas:**

```sql
ALTER TABLE colegio_docentes 
  ADD COLUMN doc_telefono VARCHAR(20) DEFAULT NULL AFTER doc_ci,
  ADD COLUMN doc_sexo VARCHAR(15) DEFAULT NULL AFTER doc_telefono,
  ADD COLUMN doc_descripcion TEXT DEFAULT NULL AFTER doc_sexo;
```

Para los horarios de entrevista, crear tabla:

```sql
CREATE TABLE colegio_docente_horarios (
  horario_id INT AUTO_INCREMENT PRIMARY KEY,
  doc_codigo VARCHAR(14) NOT NULL,
  horario_turno VARCHAR(20) NOT NULL,
  horario_dia VARCHAR(15) NOT NULL,
  horario_inicio TIME NOT NULL,
  horario_fin TIME NOT NULL,
  horario_estado TINYINT(1) DEFAULT 1,
  FOREIGN KEY (doc_codigo) REFERENCES colegio_docentes(doc_codigo)
);
```

---

## 7. REGLAS DE NEGOCIO TRANSVERSALES

### 7.1 Estudiantes retirados en reportes

En **todos** los reportes (centralizadores, boletines, listas, cuadro de honor):
- Estudiantes con `est_visible = 0` aparecen en la lista en su posición original de `lista_numero`.
- Se distinguen visualmente con texto rojo o badge "RETIRADO" en PDF/Excel.
- **No participan** en el cuadro de honor ni en el ranking de mejores.
- **Sí aparecen** en el centralizador (sin ocultar) para mantener el conteo oficial.

### 7.2 Orden de la lista

- La lista se ordena siempre por `lista_numero ASC` de `colegio_lista_curso`.
- Los estudiantes nuevos con apellido que empiece en "A" van al final (asignar `lista_numero` mayor que todos los existentes al momento de inscripción).
- Los `lista_numero` no se reordenan automáticamente salvo acción explícita del usuario.

### 7.3 Promedios numéricos

| Contexto | Formato |
|---|---|
| Boletines | Entero (`ROUND(x, 0)`) |
| Centralizadores | Entero para mostrar, decimal para ordenar |
| Cuadro de Honor | 1 decimal (`ROUND(x, 1)`) |
| Concejo (aprobado/reprobado) | Entero |
| Cálculo interno de ranking | Decimal completo |

### 7.4 Asistencias en reportes

Los datos de asistencia en centralizadores y boletines deben provenir **exactamente** de las tablas:
- Faltas: `colegio_asistencia` donde `estado = 'F'` filtrado por rango de fechas del trimestre.
- Atrasos: `asistencia_atrasos` filtrado por `atraso_fecha` en rango del trimestre.
- Licencias: `asistencia_permisos` donde `permiso_tipo = 'LICENCIA'` y fechas en rango del trimestre.

No debe haber cálculos aproximados; usar los datos exactos del sistema.

---

## 8. MIGRACIONES NECESARIAS

Las siguientes modificaciones de BD son necesarias para implementar estos requisitos:

```sql
-- 1. Orden de cursos
ALTER TABLE colegio_cursos 
  ADD COLUMN cur_orden INT DEFAULT 0 AFTER cur_nombre;

-- Asignar valores de orden iniciales
UPDATE colegio_cursos SET cur_orden = CASE cur_codigo
  WHEN 'PreKinder' THEN 1
  WHEN 'Kinder'    THEN 2
  WHEN '1roPRIM'   THEN 3
  WHEN '2doPRIM'   THEN 5
  WHEN '5500504'   THEN 6  -- 2do Primaria B
  WHEN '3roPRIM'   THEN 7
  WHEN 'eb67ecb'   THEN 8  -- 3ro Primaria B
  WHEN '4toPRIM'   THEN 9
  WHEN '5e7c089'   THEN 10 -- 4to Primaria B
  WHEN '5toPRIM'   THEN 11
  WHEN '6toPRIM'   THEN 13
  WHEN '4cb7d92'   THEN 14 -- 6to Primaria B
  WHEN '1roSEC'    THEN 15
  WHEN 'af2534c'   THEN 16
  WHEN '2doSEC'    THEN 17
  WHEN '556e9b7'   THEN 18
  WHEN '3roSEC'    THEN 19
  WHEN '64b2b3e'   THEN 20
  WHEN '4toSEC'    THEN 21
  WHEN 'ffb2f67'   THEN 22
  WHEN '5toSEC'    THEN 23
  WHEN '6toSEC'    THEN 25
  ELSE 99
END;

-- 2. Campos adicionales en docentes
ALTER TABLE colegio_docentes 
  ADD COLUMN doc_telefono VARCHAR(20) DEFAULT NULL AFTER doc_ci,
  ADD COLUMN doc_sexo VARCHAR(15) DEFAULT NULL AFTER doc_telefono,
  ADD COLUMN doc_descripcion TEXT DEFAULT NULL AFTER doc_sexo;

-- 3. Tabla de horarios de entrevista para docentes
CREATE TABLE IF NOT EXISTS colegio_docente_horarios (
  horario_id INT AUTO_INCREMENT PRIMARY KEY,
  doc_codigo VARCHAR(14) NOT NULL,
  horario_turno VARCHAR(20) NOT NULL,
  horario_dia VARCHAR(15) NOT NULL,
  horario_inicio TIME NOT NULL,
  horario_fin TIME NOT NULL,
  horario_estado TINYINT(1) DEFAULT 1,
  horario_fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabla de configuración institucional (si no existe)
CREATE TABLE IF NOT EXISTS sistema_configuracion (
  config_id INT AUTO_INCREMENT PRIMARY KEY,
  config_logo VARCHAR(255) DEFAULT NULL,
  config_denominacion VARCHAR(200) DEFAULT NULL,
  config_nombre_ue VARCHAR(200) DEFAULT NULL,
  config_direccion VARCHAR(255) DEFAULT NULL,
  config_telefono VARCHAR(50) DEFAULT NULL,
  config_ciudad VARCHAR(100) DEFAULT NULL,
  config_email VARCHAR(100) DEFAULT NULL,
  config_fecha DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 9. RESUMEN DE TAREAS POR MÓDULO

### Módulo Notas
- [ ] Refactorizar vista principal de notas con filtros de trimestre y curso
- [ ] Botones de generación de reportes en cabecera (6 botones)
- [ ] Edición de nota abre modal con detalle de dimensiones
- [ ] Marcar notas < 51 en rojo
- [ ] Marcar estudiantes retirados en rojo con badge

### Módulo Reportes
- [ ] Centralizador Trimestral PDF (con ranking, marcando top 3, incluyendo retirados)
- [ ] Centralizador Anual PDF (igual que trimestral + columnas anuales)
- [ ] Boletín Individual PDF (notas + asistencias + psicología + grupos de promedio)
- [ ] Cuadro de Honor PDF × 3 variantes (curso, nivel, colegio) — con decimales para ranking
- [ ] Lista de Mejores 3 × Curso (Excel + PDF)

### Módulo Concejo (módulo aparte)
- [ ] Documento individual para Concejo Educativo (foto, datos, notas en enteros, asistencias)
- [ ] Generación en PDF con encabezado institucional

### Módulo Parametrización
- [ ] Cursos: agregar campo `cur_orden`, UI con creación y ordenamiento
- [ ] Niveles: listado y CRUD
- [ ] Gestiones: listado con estado visible
- [ ] Materias por Curso: asignación de docente y de grupo de promedio
- [ ] Horarios/Turnos: turnos independientes adicionales
- [ ] Unidad Educativa: formulario editable con upload de logo

### Módulo Estudiantes
- [ ] Formulario de kárdex con todos los campos (foto, RUDE, zona, calle, UE procedencia)
- [ ] Exportación Excel con datos de padres
- [ ] Exportación PDF lista de contactos (padres + teléfonos)
- [ ] Generación de QR por estudiante
- [ ] Lista de reprobados por curso
- [ ] Incluir retirados en listas (en rojo)

### Módulo Docentes
- [ ] Formulario con campos ampliados (teléfono, sexo, horarios de entrevista)
- [ ] CRUD de horarios de entrevista por docente

---

*Documento generado el 03-05-2026 — U.E. Interandino Boliviano — Sistema Control-Cole*
