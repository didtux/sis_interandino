# 🏫 SISTEMA — Unidad Educativa Privada Interandino Boliviano

> Sistema de gestión escolar integral con 14 módulos funcionales, 13 roles y más de 650 usuarios.

---

## 📋 Tabla de Contenidos

- [Visión General](#visión-general)
- [Arquitectura de Módulos](#arquitectura-de-módulos)
- [Roles del Sistema](#roles-del-sistema)
- [Matriz de Accesos](#matriz-de-accesos)
- [Módulos — Detalle Técnico](#módulos--detalle-técnico)
- [Flujos Clave](#flujos-clave)
- [Integraciones entre Módulos](#integraciones-entre-módulos)
- [Notas para el Agente de Código](#notas-para-el-agente-de-código)

---

## Visión General

| Concepto         | Valor                          |
|------------------|-------------------------------|
| Módulos totales  | 14                            |
| Roles totales    | 13                            |
| Usuarios totales | 650+ (aprox.)                 |
| App móvil        | Sí — Portal Padres de Familia |
| Facturación      | Electrónica (Impuestos Nac.)  |
| Normativa notas  | Dimensiones MINEDU Bolivia    |

---

## Arquitectura de Módulos

```
SISTEMA PRINCIPAL
│
├── M01 — Gestión de Usuarios y Configuración Global
├── M02 — Gestión Académica y Calificaciones
├── M03 — Gestión de Estudiantes
├── M04 — Rankings y Reportes Académicos
├── M05 — Control de Asistencias
├── M06 — Módulo de Licencias
├── M07 — Horarios
├── M08 — Enfermería Escolar
├── M09 — Departamento de Psicología
├── M10 — Regencia Escolar
├── M11 — Transporte Escolar
├── M12 — Contabilidad y Caja Principal
├── M13 — Ventas y Almacén           ← [BETA]
└── M14 — Portal y App de Padres
```

---

## Roles del Sistema

| ID  | Rol                          | Usuarios       | Tipo de Acceso               |
|-----|------------------------------|----------------|------------------------------|
| R01 | Super Administrador          | 1              | Control total del sistema    |
| R02 | Director General             | 1              | Solo lectura — Vista 360°    |
| R03 | Directora Administrativa     | 1              | Control académico            |
| R04 | Secretaría Académica         | Variable       | Gestión notas + reportes     |
| R05 | Cuerpo Docente               | 35             | Carga de calificaciones      |
| R06 | Dpto. Contable / Cajero Ppal | 2              | Gestión financiera           |
| R07 | Cajero 2 / Ventas y Almacén  | Variable       | Ventas y almacén (BETA)      |
| R08 | Control de Asistencias       | Variable       | Lector QR/Barras             |
| R09 | Enfermería Escolar           | Variable       | Registro médico              |
| R10 | Departamento de Psicología   | Variable       | Evaluaciones y seguimiento   |
| R11 | Regencia Escolar             | Variable       | Control disciplinario        |
| R12 | Transporte Escolar           | Variable       | Gestión de rutas             |
| R13 | Padres de Familia            | 600+           | App móvil — Solo sus hijos   |

---

## Matriz de Accesos

> **Leyenda:** `✓` Acceso completo · `L` Solo lectura · `P` Acceso parcial · `—` Sin acceso

| Rol / Módulo              | M01 | M02 | M03 | M04 | M05 | M06 | M07 | M08 | M09 | M10 | M11 | M12 | M13 | M14 |
|---------------------------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| R01 Super Admin           | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   | ✓   |
| R02 Director General      | L   | L   | L   | L   | L   | L   | L   | L   | L   | L   | L   | L   | L   | L   |
| R03 Dir. Administrativa   | —   | ✓   | ✓   | ✓   | —   | —   | L   | —   | —   | —   | —   | —   | —   | —   |
| R04 Secretaría Académica  | —   | ✓   | ✓   | ✓   | —   | —   | ✓   | —   | —   | —   | —   | —   | —   | ✓   |
| R05 Docentes              | —   | P   | —   | —   | P   | —   | L   | —   | —   | —   | —   | —   | —   | —   |
| R06 Contable / Cajero     | —   | —   | L   | —   | —   | —   | —   | —   | —   | —   | ✓   | ✓   | —   | —   |
| R07 Ventas / Almacén      | —   | —   | —   | —   | —   | —   | —   | —   | —   | —   | —   | —   | ✓   | —   |
| R08 Control Asistencias   | —   | —   | —   | —   | ✓   | ✓   | L   | —   | —   | —   | —   | —   | —   | —   |
| R09 Enfermería            | —   | —   | L   | —   | —   | —   | —   | ✓   | —   | —   | —   | —   | —   | —   |
| R10 Psicología            | —   | —   | L   | —   | —   | —   | —   | —   | ✓   | L   | —   | —   | —   | —   |
| R11 Regencia              | —   | —   | L   | —   | —   | —   | —   | —   | L   | ✓   | —   | —   | —   | —   |
| R12 Transporte            | —   | —   | L   | —   | —   | —   | L   | —   | —   | —   | ✓   | L   | —   | —   |
| R13 Padres de Familia     | —   | L   | —   | —   | L   | —   | L   | —   | —   | —   | —   | L   | —   | ✓   |

---

## Módulos — Detalle Técnico

### M01 — Gestión de Usuarios y Configuración Global
**Acceso exclusivo:** R01 (Super Administrador)

Funciones:
- CRUD completo de usuarios del sistema
- Configuración de parámetros globales
- Asignación y revocación de permisos por rol
- Gestión de datos institucionales (nombre, logo, NIT, dirección, teléfonos)
- Gestión de gestiones académicas (crear, activar, cerrar, bloquear)
- Gestión de períodos académicos (trimestres, fechas de inicio/cierre)
- Gestión de niveles educativos (Inicial, Primaria, Secundaria)
- Gestión de cursos, paralelos y materias
- Registro y asignación de docentes a materias/cursos/paralelos
- Configuración de servicios institucionales (colegiatura, transporte, otros)
- Configuración de recibos y pagos
- Configuración de fechas y horarios de asistencia
- Generación y administración de códigos QR y códigos de barras (4 por estudiante)
- Respaldos y restauración de base de datos
- Auditoría completa de actividades
- Monitoreo de rendimiento en tiempo real
- Configuración de integraciones entre módulos
- Políticas de seguridad y acceso

---

### M02 — Gestión Académica y Calificaciones
**Acceso:** R01 ✓ · R02 L · R03 ✓ · R04 ✓ · R05 P (solo sus materias) · R13 L (solo sus hijos)

Estructura de calificaciones (MINEDU Bolivia):
```
NOTA FINAL = SER(5%) + SABER(45%) + HACER(40%) + AUTOEVALUACIÓN(5%)
```

Funciones:
- Carga de calificaciones por dimensión MINEDU
- Edición y corrección de calificaciones con registro de auditoría
- Autorización de cambios excepcionales (R03)
- Bloqueo y desbloqueo de trimestres académicos (R03)
- Aprobación final de calificaciones por curso (R03)
- Validación y cierre de boletines por período
- Generación de boletines:
  - Por estudiante
  - Por materia
  - Centralizadores de calificaciones
  - Trimestrales en formato oficial
- Emisión de certificados de estudio y constancias
- Configuración de dimensiones MINEDU por área
- Control de planificación curricular docente
- Kardex académico histórico del estudiante
- Reportes de rendimiento docente (cumplimiento de carga)
- Seguimiento académico individualizado

---

### M03 — Gestión de Estudiantes
**Acceso:** R01 ✓ · R02 L · R03 ✓ · R04 ✓ · R06 L · R09 L · R10 L · R11 L · R12 L

Funciones:
- CRUD de estudiantes (sin eliminación histórica — solo activar/desactivar)
- Alta para preinscripción e inscripción
- Edición de información personal, académica y de contacto
- Asignación a curso, paralelo y gestión académica
- Asignación de servicios institucionales
- Archivo digital de expedientes estudiantiles
- Archivo digital de actas, resoluciones y documentos oficiales
- Ficha integral del estudiante para consejos educativos
- Seguimiento académico individualizado

---

### M04 — Rankings y Reportes Académicos
**Acceso:** R01 ✓ · R02 L · R03 ✓ · R04 ✓

Funciones:
- Ranking académico por curso
- Ranking académico por nivel
- Ranking académico institucional
- Identificación del mejor alumno: por curso / nivel / institucional
- Ranking dinámico del mejor alumno de la semana en tiempo real
- Reportes comparativos por período académico
- Reportes de estudiantes con bajo rendimiento
- Reportes consolidados institucionales
- Exportación de reportes ejecutivos (PDF, Excel)
- Proyecciones y pronósticos basados en datos históricos

---

### M05 — Control de Asistencias
**Acceso:** R01 ✓ · R02 L · R05 P · R08 ✓ · R13 L

Funciones:
- Registro de entrada mediante QR / código de barras
  - Cada estudiante tiene 4 códigos asignados
- Clasificación automática: `Puntual` / `Tardanza` / `Falta`
- Registro diario de asistencias por docente (en aula)
- Alertas automáticas por patrones de ausencia recurrente
- Configuración de horarios de ingreso diferenciados por nivel
- Reportes estadísticos por curso y nivel
- Integración con módulo de horarios para validar asistencia

---

### M06 — Módulo de Licencias
**Acceso:** R01 ✓ · R02 L · R08 ✓

Funciones:
- Control de justificaciones médicas o familiares
- Reportes estadísticos de licencias por curso y nivel
- Integración con módulo de horarios

---

### M07 — Horarios
**Acceso:** R01 ✓ · R02 L · R03 L · R04 ✓ · R05 L · R08 L · R12 L · R13 L

Funciones:
- Creación de horarios por curso, paralelo y nivel
- Asignación de docentes, materias y aulas
- Gestión de 2 paralelos por curso en Secundaria
- Visualización diferenciada por: docente / estudiante / padre / curso
- Exportación: PDF / Excel / Calendario digital
- Comunicación automática de cambios de horario
- Integración con: asistencias · transporte · enfermería

---

### M08 — Enfermería Escolar
**Acceso:** R01 ✓ · R02 L · R09 ✓

Funciones:
- Cardex digital completo por estudiante
- Registro de atenciones médicas diarias
- Control de medicamentos administrados
- Comunicación de emergencias a padres (integrado con M14)
- Inventario de botiquín y medicamentos
- Reportes de enfermedades recurrentes
- Estadísticas de atención por estudiante (mes / trimestre)

---

### M09 — Departamento de Psicología
**Acceso:** R01 ✓ · R02 L · R10 ✓ · R11 L

Funciones:
- Evaluaciones psicológicas de estudiantes
- Seguimiento de casos especiales
- Comunicación con padres sobre desarrollo académico
- Coordinación con regencia para casos conductuales
- Registro de sesiones con padres de familia
- Alertas de riesgo psicológico
- Estadísticas de atención psicológica

---

### M10 — Regencia Escolar
**Acceso:** R01 ✓ · R02 L · R10 L · R11 ✓

Funciones:
- Sistema digital de amonestaciones
- Registro de incidentes conductuales
- Seguimiento de casos disciplinarios
- Generación de actas de compromiso
- Comunicación formal con padres
- Reportes estadísticos de disciplina por curso
- Coordinación con psicología escolar
- Registro de medidas correctivas aplicadas
- Control de horarios de reuniones con padres y estudiantes

---

### M11 — Transporte Escolar
**Acceso:** R01 ✓ · R02 L · R06 ✓ · R12 ✓

Funciones:
- Gestión completa de rutas escolares
- Registro de choferes (licencias, horarios, contactos)
- Asignación de estudiantes a rutas específicas
- Seguimiento de llegadas y partidas
- Reportes de incidencias en transporte
- Cobro integrado con módulo contable (M12)
- Coordinación con horario académico (M07)

---

### M12 — Contabilidad y Caja Principal
**Acceso:** R01 ✓ · R02 L · R06 ✓ · R12 L · R13 L

> Sistema financiero completamente independiente dentro del sistema principal.

Ítems de cobro configurables:
```
- Cuota básica de colegiatura
- Cuota básica de transporte
- Banderas (actividades especiales)
- Desayuno escolar
- Materiales específicos
- Actividades extracurriculares
```

Funciones:
- Creación ilimitada de ítems de cobro
- Configuración de cuotas variables por tipo
- Restricción automática a padres morosos (> 30 días)
- Reportes de flujo de caja: diario / semanal / mensual
- Control de morosidad con auditoría
- Configuración y emisión de recibos
- Registro y anulación de pagos (con auditoría)

---

### M13 — Ventas y Almacén ⚠️ BETA
**Acceso:** R01 ✓ · R07 ✓

> Módulo completamente separado del cobro académico. Sin acceso a colegiaturas ni deudas.

Funciones:
- Gestión de proveedores (registro, edición, historial)
- Recepción de productos desde proveedores
- Categorías de productos
- CRUD de productos (uniformes, libros, materiales)
- Control de inventario y stock en tiempo real
- Kardex de movimientos de almacén
- Ingresos y salidas de almacén
- Apertura y cierre de caja de ventas
- Registro de ventas diarias
- Emisión de facturas (vinculadas a Impuestos Nacionales — facturación electrónica)
- Emisión de recibos
- Cumplimiento normativa de facturación electrónica vigente
- Reportes: ventas diarias / por producto / stock y movimientos

---

### M14 — Portal y App de Padres de Familia
**Acceso:** R01 ✓ · R04 ✓ · R13 ✓ (App — solo sus hijos)

> Los padres acceden exclusivamente vía aplicación móvil (APK) a información de sus propios hijos.

**Vista del padre (R13 — acceso de lectura restringido):**
- Calificaciones en tiempo real
- Asistencias y faltas
- Horario de clases
- Estado de pagos (morosidad)
- Alertas automáticas:
  - Faltas consecutivas
  - Bajas calificaciones
  - Morosidad en pagos
  - Emergencias médicas
  - Incidentes disciplinarios
- Confirmación de asistencia a eventos
- Actualización de datos de contacto

**Administración del portal (R01/R04 — gestión de contenidos):**
- Agenda escolar por gestión (ej. Agenda 2026)
- Circulares institucionales
- Revistas escolares
- Listas de útiles por nivel y curso
- Reglamento interno
- Normativas y resoluciones internas
- Horarios institucionales especiales
- Misión, visión y valores
- Gestión de eventos escolares
- Documentos descargables (PDF, imágenes)
- Control de visibilidad por nivel, curso o estudiante
- Avisos de actualización de app y mantenimiento
- Programación de fechas de publicación y retiro
- Registro de lectura de comunicados
- Historial y auditoría de contenidos publicados

---

## Flujos Clave

### Flujo de Calificaciones
```
Docente (R05)
  → Carga notas por dimensión MINEDU en M02
  → Secretaría Académica (R04) valida y puede corregir con auditoría
  → Directora Administrativa (R03) aprueba y bloquea trimestre
  → Boletines generados y disponibles para padres en M14 (App)
```

### Flujo de Asistencia
```
Control Asistencias (R08)
  → Escanea QR/barras en M05 → Clasificación automática
  → Docente (R05) registra asistencia en aula en M05
  → Alertas automáticas enviadas a padres vía M14 si hay ausencias
  → Padre (R13) visualiza asistencias en App M14
```

### Flujo de Morosidad
```
Contable (R06)
  → Registra cuotas y pagos en M12
  → Sistema aplica restricción automática a morosos >30 días
  → Padre (R13) recibe alerta de morosidad en App M14
  → Transporte (R12) consulta estado de pago en M12
```

### Flujo Disciplinario
```
Docente (R05) reporta incidente → Observación conductual en M02
  → Regencia (R11) registra amonestación en M10
  → Psicología (R10) abre caso en M09 si aplica
  → Ambos coordinan; R11 genera acta de compromiso
  → Comunicación formal a padres vía M14
```

### Flujo de Emergencia Médica
```
Enfermería (R09)
  → Registra atención en M08
  → Comunica emergencia a padres vía M14 (alerta automática)
  → Padre recibe notificación en App
```

---

## Integraciones entre Módulos

```
M05 (Asistencias) ←→ M07 (Horarios)     Validación por horario de ingreso por nivel
M05 (Asistencias) ←→ M14 (App Padres)   Alertas automáticas de faltas
M06 (Licencias)   ←→ M07 (Horarios)     Justificación integrada al horario
M07 (Horarios)    ←→ M11 (Transporte)   Coordinación de rutas con horario académico
M07 (Horarios)    ←→ M08 (Enfermería)   Horarios de atención de enfermería
M08 (Enfermería)  ←→ M14 (App Padres)   Alertas de emergencias médicas
M10 (Regencia)    ←→ M09 (Psicología)   Derivación y coordinación de casos
M11 (Transporte)  ←→ M12 (Contabilidad) Cobro de cuota de transporte integrado
M12 (Contabilidad)←→ M14 (App Padres)   Alertas de morosidad al padre
M02 (Calificaciones)←→ M14 (App Padres) Notas en tiempo real para el padre
```

---

## Notas para el Agente de Código

### Reglas de Negocio Críticas

```
[ESTUDIANTES]
- Los estudiantes nunca se eliminan, solo se activan/desactivan.
- Historial académico debe conservarse aunque el estudiante esté inactivo.

[CALIFICACIONES]
- Dimensiones MINEDU obligatorias: SER(5%) + SABER(45%) + HACER(40%) + AUTOEVALUACIÓN(5%) = 100%
- Las calificaciones solo pueden ser modificadas por R04 (con auditoría) o R03.
- El bloqueo de trimestre es responsabilidad exclusiva de R03.
- Toda modificación de calificación debe quedar registrada en auditoría.

[MOROSIDAD]
- Restricción automática se aplica cuando la deuda supera 30 días.
- La restricción de morosidad afecta el acceso del padre a ciertas funciones del portal.

[MÓDULO M13 — VENTAS]
- Completamente aislado del módulo M12 (Contabilidad).
- No puede leer ni escribir en tablas de colegiaturas, deudas académicas ni pagos.
- Tiene su propia caja independiente de la caja principal.

[AUDITORÍA]
- Todo cambio crítico (notas, pagos, usuarios, permisos) debe registrarse con: usuario, timestamp, acción, valor anterior, valor nuevo.

[CÓDIGOS QR/BARRAS]
- Cada estudiante tiene exactamente 4 códigos asignados.
- El sistema de asistencia acepta cualquiera de los 4 códigos del mismo estudiante.

[ROLES Y PERMISOS]
- El Super Administrador (R01) es el único que puede crear, modificar o eliminar cualquier usuario.
- Los Padres (R13) solo acceden a datos de sus propios hijos — nunca de otros estudiantes.
- El Director General (R02) tiene solo lectura en TODO el sistema, sin excepciones.
```

### Entidades Principales

```
Institución
  └── GestionAcademica (año lectivo)
        └── PeriodoAcademico (trimestre)
              └── Nivel (Inicial / Primaria / Secundaria)
                    └── Curso
                          └── Paralelo
                                ├── Estudiante  ←→  Materia  ←→  Docente
                                └── Horario

Estudiante
  ├── Calificaciones (por dimensión MINEDU, materia, período)
  ├── Asistencias
  ├── Licencias
  ├── FichaEnfermeria
  ├── FichaPsicologia
  ├── FichaRegencia
  ├── Servicios (colegiatura, transporte, etc.)
  └── CodigosQR[4]

Usuario
  └── Rol  →  Permisos (módulos + nivel de acceso)
```

### Módulos con Estado BETA

| Módulo | Estado | Notas                                 |
|--------|--------|---------------------------------------|
| M13    | BETA   | Ventas y Almacén — en desarrollo      |

### Consideraciones de Arquitectura

- **Separación de cajas:** M12 (Caja Principal) y M13 (Caja Ventas) deben ser completamente independientes a nivel de base de datos y lógica de negocio.
- **App de padres (M14):** Consume API del sistema principal. Requiere autenticación por estudiante vinculado al padre.
- **Códigos QR/Barras:** El módulo M05 debe soportar lectura en tiempo real con clasificación automática según horario de ingreso configurado por nivel (M01).
- **Auditoría:** Implementar log inmutable para todas las entidades críticas. No debe ser modificable por ningún rol, incluido R01.
- **Bloqueo de trimestre (M02/R03):** Una vez bloqueado, ningún docente puede modificar calificaciones. Solo R03 puede desbloquear.
- **Historial académico:** El kardex del estudiante es inmutable en su registro histórico. Solo se puede agregar o corregir con auditoría, nunca eliminar.

---

*Documento generado desde: `CONTRATO_SISTEMA.docx` — Anexo A — Alcance Funcional del Sistema*
