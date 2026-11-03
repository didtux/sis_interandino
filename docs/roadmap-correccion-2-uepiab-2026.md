# Roadmap — 2ª corrección UEPIAB (sept. 2026) + nueva gestión 2027

Base: `2ULTIMA CORRECION UEPIAB SEPTIEMBRE 2026.pdf` (17 páginas) contrastado contra el código
(commit `c59c1ce`) y la base `interandino` local. Todos los errores marcados como ROTO se
reprodujeron con peticiones reales dentro de transacciones revertidas; no se modificó nada.

Fuera de alcance por pedido: **pasarela de pagos** y **APK de padres**. Sí se incluyen los módulos
que esa app mostraría (revista, lista de útiles, reglamento, horarios, agenda, misión y visión),
porque van en el portal web de padres.

---

## 0. Lo urgente que no está en el documento

1. **El 1 de enero de 2027 el sistema cambia de gestión solo.** El año sale de `date('Y')` en
   113 lugares (42 archivos) y `colegio_gestiones.ges_estado` no lo lee nadie
   (`Gestion::activa()` no se usa). Ese día caja deja de poder cobrar deudas de 2026, las listas
   de curso y la mora pasan a 2027 vacío, y `Estudiante::inscripcion()` agarraría una inscripción
   2027 apenas exista. **Esto pasa aunque no se haga la pre-inscripción.** Fase B tiene que estar
   en producción antes de fin de diciembre.
2. **Padres duplicados:** 138 CI de padre tienen varios `pfam_codigo`, cada uno con un hijo.
   Es la causa de "al buscar al papá sale un solo hijo" (caja) y de "el portal no muestra datos
   reales" (la madre VILDA COAQUIRA tiene dos registros y su usuario ve un solo hijo). Lo
   provoca `InscripcionController.php:282,491`, que crea un padre nuevo en cada inscripción sin
   buscar por CI.
3. **Recibos con MAX+1 sin bloqueo** (`PagoController.php:249-255`, `InscripcionController.php:383-411`):
   dos cajeras cobrando al mismo tiempo pueden sacar el mismo número. Con la pre-inscripción
   va a haber cola en caja.

---

## 1. Estado de cada punto del documento

Leyenda: ✅ hecho · 🟡 parcial · 🔴 roto · ⬜ no existe · 📅 depende de la nueva gestión

### Web pública
| Pedido | Estado | Qué falta |
|---|---|---|
| Botón "Solicitar inscripción" → formulario de contacto | 🟡 | Ya enlaza a `/contacto` (`niveles.blade.php:282`), pero **el formulario no envía nada**: sin `action`, sin `@csrf`, inputs sin `name`, el JS simula "¡Mensaje recibido!". Los mensajes se pierden. → Fase A |
| Correo institucional | ⬜ | `MAIL_HOST` de desarrollo, cero usos de `Mail::`. Requiere SMTP real del colegio. → Fase A (opcional) |
| Página parametrizable (requisitos de pre/inscripción con texto, fotos, niveles, fechas, links) | ⬜ | Todo el contenido está fijo en Blade; sólo el logo sale de la base. → Fase F |

### Asistencias
| Pedido | Estado | Qué falta |
|---|---|---|
| Usuario para la APK del encargado | 🟡 | `/escaneo` existe con login y auditoría; falta apuntar la APK ahí y dar de alta el usuario (pendiente de Fase 1, necesita el proyecto Android) |
| Excel del reporte trimestral sólo sale un total | 🔴 | `reporteTrimestralExcel` (`AsistenciaController.php:1067-1106`) arma 5 totales; las columnas TOTAL salen vacías y los encabezados corridos (falta "N°"). → Fase A |
| Turno tarde editable sin afectar mañana | ✅ | Verificado (07:55 queda intacta al marcar/editar/borrar en tarde). Caso borde: marcas antes de 07:00 |
| Reporte mensual por semanas, 1 hoja | ✅ | Hecho en Fase 4 |
| Licencias: mejorar visibilidad para imprimir | 🟡 | Matriz baja a 7px con >20 columnas → oficio horizontal, mínimo 8px, celdas vacías en vez de 0. → Fase G |
| Ranking de licencias recurrentes por curso | ⬜ | → Fase G |

### Notas
| Pedido | Estado |
|---|---|
| Reporte de asistencia del docente en 1 hoja | ✅ Fase 2 |
| Dirección marca rosa / qué color gana | ✅ existe (pantalla Advertencia Parcial); **decisión del colegio pendiente**: hoy gana rosa |
| Hoja docente en 1-2 hojas, espacio para sello y firma, con naranja | ✅ Fase 2 |
| Nombre de cada casillero + reflejado en PDF | ✅ Fase 4 |

### Estudiantes observados
| Pedido | Estado |
|---|---|
| "No se puede probar si bloquea en otra gestión" | 📅 El bloqueo ya arrastra entre gestiones (`obs_gestion <=`), pero no hay forma de inscribir a 2027 hasta que exista la gestión. Se prueba con la pre-inscripción (Fase D), que lo consulta. |

### Caja / Inscripciones / Pagos
| Pedido | Estado | Causa / arreglo |
|---|---|---|
| Pestaña descuentos mezcla estudiantes / años | ✅ Fase 4 (listado por gestión) | — |
| Registro especial no deja meses anteriores | ✅ Fase 3 | — |
| Excel Listado de pagos | ✅ | Descarga xlsx válido |
| Excel Resumen anual "cursos mezclados" | 🟡 | Ordena por `cur_codigo` (`PagoController.php:502`) → ordenar por `cur_orden`. 15 min |
| PDF de listas: una hoja por curso, en orden | ✅ Fase 2 | — |
| Mora → Registrar pago: pide reescribir el nombre / no muestra opción de pagar | 🔴 | La preselección dispara el `change` antes de registrar los handlers (`pagos/create.blade.php:288-294` vs `:297,:317`). Mover el bloque al final del `ready`. 10 min |
| Al elegir el papá salen todos sus hijos | 🔴 datos | El código está bien; son los padres duplicados (sección 0.2) |
| "Otro" borra al estudiante elegido | 🔴 | El handler limpia la tabla (`:302-308`). 30 min |
| Caja diaria sin MIXTO, título "Reporte de caja diaria", QR a QR y efectivo a efectivo | 🟡 | El PDF carta ya cumple. Falta el **térmico** (sigue imprimiendo "MIXTO" sin partir montos) y **2.477 pagos EFECTIVO de feb–jul tienen efectivo=0 y QR=0**, así que la tarjeta EFECTIVO sale en 0 esos días. 1 UPDATE + 30 min |
| No deja anular pago de servicios ("Error al anular") | 🔴 | El backend anula bien (200). El JS usa URL absoluta fija `'/pagos-servicios/'+id` (`pagos-servicios/index.blade.php:333`) y oculta el código de error → falla si el sistema está en subcarpeta, o por sesión vencida. Mismo patrón en transporte, inscripciones y cambio de contraseña. 20 min |

### Transporte
| Pedido | Estado | Nota |
|---|---|---|
| Cobro igual que mensualidad con montos variables | ✅ | — |
| Juntar choferes y vehículos | ⬜ | 3–5 h |
| PDF de choferes con sus buses | ⬜ | 1–2 h |
| Anular recibos | 🔴 | Mismo bug JS que servicios |
| Editar pagos de más de 1 estudiante | ⬜ | El recibo agrupado no tiene botón de editar por fila (`transporte/pagos/index.blade.php:142`) |
| Reporte de mora con filtro por curso | ⬜ | 4–6 h |
| Buscar por bus, no por ruta | 🟡 | Sólo hay filtro por ruta. 1 h |
| Elegir mes en la lista de una ruta | ✅ Fase 3 | — |
| Número de bus en el recibo | ✅ | Detalle: la ruta RUTA1771936270 tiene 2 buses activos |
| Lector QR del chofer no funciona | 🟡 | El backend registra bien. Fallas reales: **18 de 20 choferes no tienen usuario**; la cámara del celular exige **HTTPS**; la librería viene de unpkg; sin antirrebote (lee el mismo QR 5 veces/seg) |
| Comprobante térmico de servicios | ✅ Fase 2 | — |

### Ventas
| Pedido | Estado | Nota |
|---|---|---|
| Escáner QR en vez de código de barras | 🔴 | El QR de la etiqueta codifica `codigo|nombre|categoria|precio` y la venta busca por `prod_item` → nunca encuentra. Además hace ~215 AJAX síncronos por lectura. 2–3 h |
| Ticket automático al cerrar la venta | ✅ | Riesgo de bloqueador de popups → redirigir en vez de `window.open` |
| Reportes diarios, mensuales, anuales | 🟡 | Presets existen, pero **"Hoy" sale siempre vacío**: `whereBetween` sobre `datetime` deja fuera el último día (`VentaController.php:256,316`). 15 min |
| Nuevos formatos de recibo térmico / carta | ⏳ | El colegio los va a reenviar |

### Agregar al sistema
| Pedido | Estado |
|---|---|
| Caja chica, egresos diarios, sueldos y salarios, egresos contables | ⬜ — el sistema sólo registra ingresos. Necesita su propio relevamiento (Fase I) |
| Bloqueo de notas a padres por mora (automático desde 2 meses, manual o configurable) | ⬜ — Fase E |
| Datos reales de mensualidades en el portal | 🔴 datos — padres duplicados (0.2). La fórmula del saldo ya es la de caja |
| Subir comprobante YAPE/QR para que caja apruebe (no pasarela) | ❌ Quitado (28/10): el colegio se refería a una pasarela de pagos, fuera de alcance. Caja sigue registrando pagos QR con foto como antes |
| Agenda, revistas, lista de útiles por curso y año, reglamento, horarios, misión y visión | ⬜ — Fase E. La tabla `agenda` existe pero tiene 0 filas y no se muestra a padres; no hay horario de clases por curso |
| Chat / canal de atención | ⬜ — Fase H |
| Nueva gestión / inscripción online | 📅 — Fases B y D |
| Roles con "acceso denegado" | 🔴 — Fase C |

---

## 2. Roles y permisos — por qué sale "Acceso denegado"

El sistema **no usa spatie** (está instalado pero sus tablas no existen). Usa tablas propias:
`rol_roles` (17), `rol_modulos` (49, con padre/hijo) y `rol_permisos` (207 filas, ver / crear /
editar / eliminar). El control lo hace el middleware `CheckPermiso`, y el texto "Acceso Denegado"
es la vista `errors/403.blade.php`, que es igual para cualquier 403 y oculta la causa.

Causas comprobadas con usuarios reales:

1. **La pantalla guarda el permiso en el módulo hijo y el middleware revisa el padre.**
   `CheckPermiso` mira sólo el primer tramo de la URL: todo `/asistencia-config/*` se controla
   como `asistencia-config`, nunca como `asistencia-config.permisos`. El rol LICENCIAS UEPIAB
   tiene ver/crear/editar/eliminar en "permisos" → `GET` y `POST /asistencia-config/permisos`
   dan **403**. Igual Director General en atrasos, Directora Académica en reportes, y
   `asistencias.registro`. El menú además esconde todo el grupo si falta el padre.
   **Este es casi seguro el caso que reporta el cliente.**
2. **Todo POST se trata como "crear".** 51 rutas que no son alta: `estudiantes/{id}/toggle-estado`
   (pide crear), `inscripciones/{id}/pagar` (pide crear), generar un reporte (pide crear). No
   existen las acciones "anular" ni "exportar".
3. **Roles escritos a mano en 14 controladores, ~10 vistas y 6 lugares del menú**
   (`[1,9,10,11]`…). Ningún tilde los habilita: Psicopedagogía no entra a Comunicados ni Kardex
   docente, Caja no entra a Observados, Directora Académica no entra a Auditoría.
   Bug aparte: `AlertaParcialController.php:22` y `kardex-estudiante/index.blade.php:6` incluyen
   el **rol 4 = Chofer**.
4. **Hueco de seguridad inverso:** 107 rutas pasan sin control porque su prefijo no está en el
   mapa (reportes `*-reporte*`, `*-excel`, `parametrizacion/*`). **Un Docente puede descargar
   `/pagos-reporte-excel`** (datos financieros, verificado 200) y entrar a gestiones.
5. Módulos que existen y **no están en el catálogo**: auditoría, comunicados, kardex docente y
   estudiante, observados, advertencia parcial, reimpresiones de boletín, configuración de
   notas, asistencia de clases, horarios especiales, reportes de licencias, caja, escaneo,
   estudiantes con descuento.

Rediseño: mantener las tablas propias (ya tienen UI y datos), catálogo único en código +
seeder, middleware por **nombre de ruta** con "negar por defecto", acciones ver / crear / editar /
eliminar / anular / exportar, capacidades especiales (aprobar notas, configurar notas, override
de observados, reimpresiones, auditoría…) que reemplazan los roles a mano, menú generado desde el
mismo catálogo, y 403 que diga qué permiso falta.

---

## 3. Pre-inscripción y nueva gestión — cómo funcionaría

### Hechos del modelo actual
- `colegio_estudiantes.cur_codigo` es **un solo curso, sin historial**. La inscripción no lo
  actualiza. `colegio_lista_curso` sí es por gestión, pero sólo tiene 2026.
- `colegio_cursos` no tiene "curso siguiente" ni columna de paralelo.
- **Aprobado/reprobado nunca se guarda**; se calcula al imprimir (≥ 51). No existe regla de
  cuántas materias reprobadas hacen repetir.
- La "preinscripción" de hoy es un número fijo en la ficha del alumno (300 o 0), sin fecha, sin
  gestión y sin recibo; se usó para precargar el pago al inscribir.
- Las mensualidades no guardan gestión ni mes: se deducen de `YEAR(pagos_fecha)` y del texto del
  concepto ("Mensualidad Febrero").
- Anular pone `estado=0` sin motivo, usuario ni fecha. **No existe devolución.**
- Hay dos series de recibo distintas (REC 5 dígitos en mensualidades, REC 6 dígitos en
  inscripción), ambas MAX+1. El recibo se arma con jsPDF en el navegador.
- Descuentos: 128 de 136 son entradas "por familia" con porcentaje 0,05; el monto real está
  guardado fijo en la inscripción. `actualizarDescuento` recalcula por porcentaje → un "5%"
  guardado como 0,05 descontaría Bs 2,50.

### Diseño
**Gestión con tres estados independientes** en `colegio_gestiones`:
`ges_estado` = gestión académica vigente (una sola), `ges_inscripcion_abierta` (2027 puede estar
abierta mientras 2026 sigue vigente), `ges_cerrada` (sin más notas ni cobros nuevos), más fechas de
pre-inscripción y monto de reserva. Un servicio `GestionActual` reemplaza `date('Y')`.

**Tarifario por gestión y curso/nivel** (`colegio_tarifas`): hoy el monto anual se escribe a mano.

**Curso siguiente** en `colegio_cursos` (`cur_siguiente`, `cur_grado`, `cur_paralelo`), cargado
una vez: 1ro Prim A → 2do Prim A, 1ro Prim B → 2do Prim B, 6to Sec → egresa.

**Historial por gestión** (`estudiante_gestion`: estudiante, gestión, curso, estado, resultado
PENDIENTE / PROMOVIDO / REPROBADO / RETIRADO / EGRESADO, promedio final, materias reprobadas,
corrección manual con motivo). Los datos personales del alumno y de los padres **no se duplican**:
siguen en una sola ficha global; lo que cambia por año es curso, inscripción y resultado.
Los reportes de años pasados leen el curso de esta tabla y no de la ficha, para que el boletín
2026 no salga con las materias del curso 2027.

**Flujo**

1. **Abrir 2027** (noviembre): crear la gestión con inscripción abierta, cargar tarifas y monto
   de reserva. 2026 sigue intacta.
2. **Pre-inscribir alumno antiguo:** se elige de la nómina 2026; el curso destino se sugiere con
   `cur_siguiente`. Se consulta la lista de **observados** (bloquea, override de dirección) y la
   **mora 2026** (aviso o bloqueo, según política). Se cobra la reserva con recibo propio
   (serie RSV) y queda **RESERVADA y condicional** al resultado de 2026.
3. **Alumno nuevo:** sus datos quedan en la pre-inscripción, **no** en la tabla de estudiantes
   (si no, aparecería en listas, asistencia y notas de 2026). El padre sí se crea, buscando antes
   por CI.
4. **Descuentos:** en la pre-inscripción el descuento es una propuesta. Se agrega tipo
   PORCENTAJE / MONTO y gestión de vigencia (sirve para un descuento por pre-inscripción
   temprana). Al convertir se recalcula contra la tarifa 2027.
5. **Cierre académico** (diciembre): se calcula el resultado 2026 con la misma fórmula del
   boletín.
   - PROMOVIDO → la pre-inscripción pasa a CONFIRMADA.
   - **REPROBADO** → el curso destino vuelve al mismo grado y se recalcula la tarifa; se avisa a
     la familia, que elige seguir (la reserva se mantiene) o pedir devolución.
   - 6to Secundaria → EGRESADO.
6. **Convertir en inscripción** (dic./ene.): se crea la inscripción 2027 con tarifa y descuentos,
   y la reserva entra como monto ya pagado. Como la cuota de febrero ya se calcula como
   mensualidad − monto pagado, **la reserva se descuenta sola de febrero** sin tocar el
   cálculo de cuotas. Ese ingreso no vuelve a contar en la caja del día.
7. **Activar 2027** (1 de febrero, asistente con lista de verificación): se cambia la gestión
   vigente, a cada inscrito 2027 se le asigna el curso destino, los nuevos se crean desde su
   pre-inscripción, los no reinscritos pasan a no visibles, se genera la lista de curso 2027 y
   se crean periodos y dimensiones de notas.
8. **Durante 2027** caja puede elegir gestión al cobrar: la vigente por defecto y la anterior si
   tiene saldo.

**Devoluciones — documento propio, el recibo original no se toca.** Anular queda sólo para
corregir un error de caja (mismo día, con motivo y usuario). Devolver es una salida de dinero con
recibo DEV, motivo (REPROBADO / DESISTE / SIN CUPO / DUPLICADO / OTRO), monto retenido y devuelto,
y estados SOLICITADA → APROBADA → PAGADA. El reporte de caja la muestra como egreso el día que se
paga.

**Recibos:** tabla de secuencias con bloqueo (`SELECT … FOR UPDATE`), conservando las series
actuales y agregando RSV/TSV (reserva) y DEV. El recibo pasa al servidor (Blade + DomPDF) en media
carta y térmico 80 mm. El de reserva imprime "Reserva de cupo – Gestión 2027", el curso destino,
que es condicional al resultado 2026 y la política de devolución.

---

## 4. Roadmap

Hoy es 28/10/2026. Estimaciones en días de un desarrollador.

### Fase A — Correcciones rápidas (≈ 4–5 días) · semana 1
- Mora → registrar pago (preselección), "Otro" no borra al alumno.
- Anular servicios / transporte / inscripciones: URLs con `route()` y mensaje real del error.
- Caja térmica sin MIXTO y con efectivo/QR partidos; UPDATE de los 2.477 pagos con montos en 0.
- Resumen anual Excel ordenado por `cur_orden`.
- Ventas: "Hoy" vacío (`whereDate`); ticket por redirección.
- Excel trimestral de asistencia con el detalle del PDF.
- **Roles, arreglo de urgencia:** el middleware busca el slug más largo de la URL y acepta
  permiso del hijo o del padre; mapear los prefijos sin control; mostrar en el 403 qué faltó;
  quitar el rol 4 de las dos listas. (0,5–1 día)
- **Formulario de contacto real** + `?motivo=inscripcion` desde "Solicitar inscripción" +
  bandeja en el admin con badge y botón de WhatsApp; antispam (honeypot + throttle).
  Correo opcional si el colegio da un SMTP. (1,5–2 días)

### Fase B — Base de gestión (≈ 8–10 días) · semanas 2–3 · **antes del 31/12**
- Servicio `GestionActual` y reemplazo de `date('Y')` en caja, inscripción, cursos/listas,
  estudiantes, notas, portal y dashboard.
- `pagos_gestion` y `pagos_mes` en mensualidades, carga inicial y cambio de las 14 consultas
  por año de fecha.
- Secuencias de recibo con bloqueo + recibo en servidor (media carta y térmico).
- **Unificar padres duplicados por CI** (script con revisión previa) y buscar por CI antes de
  crear un padre.

### Fase C — Roles y permisos definitivo (≈ 6–9 días) · semanas 3–4
Catálogo + seeder, middleware por nombre de ruta con negar por defecto, acciones anular y
exportar, capacidades especiales, migración de las listas a mano sin quitar accesos actuales,
menú generado, UI de roles por módulo, pruebas por rol.

### Fase D — Pre-inscripción y nueva gestión (≈ 22–24 días) · semanas 4–8
Orden: tarifario y curso siguiente → pre-inscripción con reserva y recibo → descuentos
corregidos → resultado anual y promoción → conversión a inscripción → devoluciones y
anulación con registro → asistente de activación y reportes por gestión histórica → resto de
`date('Y')` → ensayo completo nov→feb sobre una copia de la base.
La pre-inscripción (primeros dos bloques) puede salir antes que el resto si el colegio quiere
abrirla en noviembre.

### Fase E — Portal de padres (≈ 13–14 días) · semanas 8–11
- Bloqueo de notas por mora: `MoraService` único (caja, portal y bloqueo leen lo mismo),
  parámetros activo / modo AUTO-MANUAL-AMBOS / meses mínimos / desde qué mes / mensaje, y
  bloqueo o exoneración manual por estudiante con motivo. (2,5–3)
- Publicaciones institucionales: un solo módulo para REVISTA, LISTA DE ÚTILES (por curso y
  gestión), REGLAMENTO, HORARIO, MISIÓN Y VISIÓN y AGENDA, con "duplicar a la gestión
  siguiente"; revistas en visor PDF tipo libro. Agenda del hijo sobre la tabla existente. (6–7)
- ~~Comprobante YAPE/QR subido por el padre~~ — quitado el 28/10: el pedido era una pasarela de
  pagos, fuera de alcance.

### Fase F — Web pública editable (≈ 4–5 días) · semana 12
Secciones y bloques editables (texto, imagen, archivo, link, fechas, por nivel y gestión,
con vigencia), redes y datos de contacto desde la configuración, página nueva `/inscripciones`
con requisitos por nivel.

### Fase G — Mejoras de módulos (≈ 4–5 días) · en paralelo donde haya hueco
Transporte (choferes + vehículos en una pantalla, PDF de choferes con buses, buscar por bus,
mora por curso, editar recibos de varios alumnos, QR del chofer con antirrebote y librería
local), ventas con QR por cámara, ranking de licencias recurrentes, matriz de licencias legible.

### Fase H — Canal de atención a padres (≈ 3–4 días)
Tickets por área (secretaría, caja, dirección, psicopedagogía, docente) con hilo de mensajes y
badges. Un chat en tiempo real son 8–12 días más infraestructura (websockets, colas); no se
recomienda ahora.

### Fase I — Caja chica, egresos, sueldos, contabilidad (a dimensionar)
No existe nada de salida de dinero. Requiere relevamiento con contabilidad (plan de cuentas,
quién autoriza, arqueo). Las devoluciones de la Fase D ya dejan la primera salida de dinero
registrada en caja.

**Total Fases A–H: ≈ 64–75 días de desarrollo** (sin la Fase I).

### Operativo (no es código)
- Dar de alta usuarios a los 18 choferes sin cuenta; HTTPS en el dominio para la cámara.
- Usuario de escaneo + apuntar la APK de asistencia a `/escaneo` (sigue pendiente de Fase 1).
- Rotar la contraseña de `uepinter_nico` y aplicar los SQL pendientes de Fases 3–4.

---

## 5. Decisiones que tiene que tomar el colegio

1. **Regla de reprobación:** cuántas materias bajo 51 hacen repetir el año.
2. **Reserva:** monto, si se descuenta de febrero (propuesto) y **política de devolución**
   (cuánto se retiene y en qué casos).
3. **Mora 2026 al pre-inscribir:** sólo aviso o bloqueo.
4. **Bloqueo del portal:** desde cuántos meses, desde qué mes, automático o manual.
5. **Fusión de padres duplicados:** aprobar la lista antes de unificar.
6. Asignaciones de docentes por gestión o reescritura (hoy cambiar el docente reescribe el que
   figura en notas pasadas).
7. Pendientes de antes: color cuando reportan docente y dirección; desempate del cuadro de honor
   anual; A3 o menos columnas en el reporte general anual.
8. SMTP real para el correo institucional.
