<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/cmd/{command}', function($command) {
    Artisan::call($command);
    dd(Artisan::output());
});

Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas del Sistema de Colegio
Route::middleware(['auth', 'permiso', 'auditoria'])->group(function () {
    // Usuarios
    Route::resource('usuarios', App\Http\Controllers\UserController::class);
    
    // Roles y Permisos
    Route::resource('roles', App\Http\Controllers\RolController::class);

    // Auditoría
    Route::get('auditoria', [App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');
    
    // Estudiantes
    Route::resource('estudiantes', App\Http\Controllers\EstudianteController::class);
    Route::get('estudiantes/{id}/kardex', [App\Http\Controllers\EstudianteController::class, 'kardex'])->name('estudiantes.kardex');
    Route::get('estudiantes-reporte-general', [App\Http\Controllers\EstudianteController::class, 'reporteGeneral'])->name('estudiantes.reporte-general');
    
    // Cursos
    Route::post('cursos/{id}/guardar-lista', [App\Http\Controllers\CursoController::class, 'guardarLista'])->name('cursos.guardar-lista');
    Route::post('cursos/{id}/auto-lista', [App\Http\Controllers\CursoController::class, 'autoLista'])->name('cursos.auto-lista');
    Route::post('cursos/{id}/asignar-materias', [App\Http\Controllers\CursoController::class, 'asignarMaterias'])->name('cursos.asignar-materias');
    Route::post('cursos/{id}/asignar-docente', [App\Http\Controllers\CursoController::class, 'asignarDocente'])->name('cursos.asignar-docente');
    Route::delete('cursos/{id}/quitar-docente/{matCodigo}', [App\Http\Controllers\CursoController::class, 'quitarDocente'])->name('cursos.quitar-docente');
    Route::resource('cursos', App\Http\Controllers\CursoController::class);
    
    // Docentes
    Route::post('docentes/{id}/crear-usuario', [App\Http\Controllers\DocenteController::class, 'crearUsuario'])->name('docentes.crear-usuario');
    Route::resource('docentes', App\Http\Controllers\DocenteController::class);
    
    // Asistencias
    Route::resource('asistencias', App\Http\Controllers\AsistenciaController::class);
    Route::get('asistencias/curso/{curso}', [App\Http\Controllers\AsistenciaController::class, 'porCurso'])->name('asistencias.por-curso');
    Route::post('asistencias/registrar-masivo', [App\Http\Controllers\AsistenciaController::class, 'registrarMasivo'])->name('asistencias.registrar-masivo');
    Route::get('api/estudiantes-por-curso/{curso}', [App\Http\Controllers\AsistenciaController::class, 'estudiantesPorCurso']);
    Route::get('asistencias-reporte-trimestral', [App\Http\Controllers\AsistenciaController::class, 'reporteTrimestral'])->name('asistencias.reporte-trimestral');
    Route::get('asistencias-reporte-anual', [App\Http\Controllers\AsistenciaController::class, 'reporteAnual'])->name('asistencias.reporte-anual');
    Route::get('asistencias-reporte-trimestral-excel', [App\Http\Controllers\AsistenciaController::class, 'reporteTrimestralExcel'])->name('asistencias.reporte-trimestral-excel');
    Route::get('asistencias-reporte-anual-excel', [App\Http\Controllers\AsistenciaController::class, 'reporteAnualExcel'])->name('asistencias.reporte-anual-excel');
    Route::get('asistencias-reporte-atrasos', [App\Http\Controllers\AsistenciaController::class, 'reporteAtrasos'])->name('asistencias.reporte-atrasos');
    Route::get('asistencias-reporte-faltas', [App\Http\Controllers\AsistenciaController::class, 'reporteFaltas'])->name('asistencias.reporte-faltas');
    Route::get('asistencias-cursos-por-turno', [App\Http\Controllers\AsistenciaController::class, 'cursosPorTurno'])->name('asistencias.cursos-por-turno');
    Route::post('asistencias-limpiar-duplicados', [App\Http\Controllers\AsistenciaController::class, 'limpiarDuplicados'])->name('asistencias.limpiar-duplicados');
    
    // Materias
    Route::resource('materias', App\Http\Controllers\MateriaController::class);
    Route::post('materias-grupos/guardar', [App\Http\Controllers\MateriaController::class, 'guardarGrupo'])->name('materias.guardar-grupo');
    Route::delete('materias-grupos/{id}', [App\Http\Controllers\MateriaController::class, 'eliminarGrupo'])->name('materias.eliminar-grupo');
    
    // Notas
    Route::get('notas', [App\Http\Controllers\NotaController::class, 'index'])->name('notas.index');
    Route::get('notas/configuracion', [App\Http\Controllers\NotaController::class, 'configuracion'])->name('notas.configuracion');
    Route::post('notas/configuracion/periodo', [App\Http\Controllers\NotaController::class, 'guardarPeriodo'])->name('notas.guardar-periodo');
    Route::delete('notas/configuracion/periodo/{id}', [App\Http\Controllers\NotaController::class, 'eliminarPeriodo'])->name('notas.eliminar-periodo');
    Route::post('notas/configuracion/dimension', [App\Http\Controllers\NotaController::class, 'guardarDimension'])->name('notas.guardar-dimension');
    Route::delete('notas/configuracion/dimension/{id}', [App\Http\Controllers\NotaController::class, 'eliminarDimension'])->name('notas.eliminar-dimension');
    Route::get('notas/calificar/{curmatdoc}/{periodo}', [App\Http\Controllers\NotaController::class, 'calificar'])->name('notas.calificar');
    Route::post('notas/guardar', [App\Http\Controllers\NotaController::class, 'guardar'])->name('notas.guardar');
    Route::post('notas/aprobar/{curmatdoc}/{periodo}', [App\Http\Controllers\NotaController::class, 'aprobar'])->name('notas.aprobar');
    Route::get('notas-reporte-personal', [App\Http\Controllers\NotaController::class, 'reportePersonal'])->name('notas.reporte-personal');
    Route::get('notas-reporte-centralizador', [App\Http\Controllers\NotaController::class, 'reporteCentralizador'])->name('notas.reporte-centralizador');
    Route::get('notas-reporte-general', [App\Http\Controllers\NotaController::class, 'reporteGeneral'])->name('notas.reporte-general');
    // Reportes de Notas


    // Asistencia de Clases (Docentes)
    Route::get('asistencia-clases', [App\Http\Controllers\AsistenciaClaseController::class, 'index'])->name('asistencia-clases.index');
    Route::get('asistencia-clases/{curmatdoc}/{periodo}/general', [App\Http\Controllers\AsistenciaClaseController::class, 'vistaGeneral'])->name('asistencia-clases.vista-general');
    Route::get('asistencia-clases/{curmatdoc}/{periodo}/reporte-pdf', [App\Http\Controllers\AsistenciaClaseController::class, 'reportePdf'])->name('asistencia-clases.reporte-pdf');
    Route::get('asistencia-clases/{curmatdoc}/{periodo}', [App\Http\Controllers\AsistenciaClaseController::class, 'registrar'])->name('asistencia-clases.registrar');
    Route::post('asistencia-clases/guardar', [App\Http\Controllers\AsistenciaClaseController::class, 'guardar'])->name('asistencia-clases.guardar');

    // Asistencia Actividades
    Route::post('actividades-asistencia/{id}/categoria', [App\Http\Controllers\ActividadAsistenciaController::class, 'storeCategoria'])->name('actividades-asistencia.store-categoria');
    Route::delete('actividades-asistencia/categoria/{id}', [App\Http\Controllers\ActividadAsistenciaController::class, 'destroyCategoria'])->name('actividades-asistencia.destroy-categoria');
    Route::get('actividades-asistencia/registrar/{catId}', [App\Http\Controllers\ActividadAsistenciaController::class, 'registrar'])->name('actividades-asistencia.registrar');
    Route::post('actividades-asistencia/guardar-registro', [App\Http\Controllers\ActividadAsistenciaController::class, 'guardarRegistro'])->name('actividades-asistencia.guardar-registro');
    Route::delete('actividades-asistencia/registro/{id}', [App\Http\Controllers\ActividadAsistenciaController::class, 'eliminarRegistro'])->name('actividades-asistencia.eliminar-registro');
    Route::put('actividades-asistencia/registro/{id}/observacion', [App\Http\Controllers\ActividadAsistenciaController::class, 'actualizarObservacion'])->name('actividades-asistencia.actualizar-observacion');
    Route::get('actividades-asistencia-reporte-pdf/{id}', [App\Http\Controllers\ActividadAsistenciaController::class, 'reportePdf'])->name('actividades-asistencia.reporte-pdf');
    Route::resource('actividades-asistencia', App\Http\Controllers\ActividadAsistenciaController::class);
    Route::get('api/actividades/buscar-estudiante/{codigo}', [App\Http\Controllers\ActividadAsistenciaController::class, 'buscarEstudiante']);
    
    // Agenda
    Route::resource('agenda', App\Http\Controllers\AgendaController::class);
    
    // Pagos
    Route::resource('pagos', App\Http\Controllers\PagoController::class);
    
    // Padres de Familia
    Route::post('padres/{id}/crear-usuario', [App\Http\Controllers\PadreFamiliaController::class, 'crearUsuario'])->name('padres.crear-usuario');
    Route::post('padres/{id}/vincular', [App\Http\Controllers\PadreFamiliaController::class, 'vincularEstudiante'])->name('padres.vincular');
    Route::post('padres/{id}/desvincular/{estudianteId}', [App\Http\Controllers\PadreFamiliaController::class, 'desvincularEstudiante'])->name('padres.desvincular');
    Route::resource('padres', App\Http\Controllers\PadreFamiliaController::class);

    
    // Configuración de Asistencia
    Route::prefix('asistencia-config')->name('asistencia-config.')->group(function () {
        // Configuración de horarios
        Route::get('/', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'index'])->name('index');
        Route::post('/configuracion', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'storeConfiguracion'])->name('configuracion.store');
        Route::put('/configuracion/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'updateConfiguracion'])->name('configuracion.update');
        Route::delete('/configuracion/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'destroyConfiguracion'])->name('configuracion.destroy');
        
        // Atrasos
        Route::get('/atrasos', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'atrasos'])->name('atrasos');
        Route::get('/atrasos/reporte-pdf', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'atrasosReportePdf'])->name('atrasos.reporte-pdf');
        
        // Permisos
        Route::get('/permisos', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'permisos'])->name('permisos');
        Route::post('/permisos', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'storePermiso'])->name('permisos.store');
        Route::put('/permisos/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'updatePermiso'])->name('permisos.update');
        Route::delete('/permisos/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'destroyPermiso'])->name('permisos.destroy');
        Route::get('/permisos/{id}/imprimir', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'imprimirPermiso'])->name('permisos.imprimir');
        Route::get('/permisos/reporte-pdf', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'reportePermisosPdf'])->name('permisos.reporte-pdf');
        
        // Fechas Festivas
        Route::get('/festivos', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'fechasFestivas'])->name('festivos');
        Route::post('/festivos', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'storeFestivo'])->name('festivos.store');
        Route::put('/festivos/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'updateFestivo'])->name('festivos.update');
        Route::delete('/festivos/{id}', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'destroyFestivo'])->name('festivos.destroy');
        
        // Reportes
        Route::get('/reportes', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'reportes'])->name('reportes');
        Route::post('/reportes/generar', [App\Http\Controllers\ConfiguracionAsistenciaController::class, 'generarReporte'])->name('reportes.generar');
    });
    
    // Módulo de Ventas
    Route::resource('categorias', App\Http\Controllers\CategoriaController::class);
    Route::resource('productos', App\Http\Controllers\ProductoController::class);
    Route::get('productos/{id}/etiqueta', [App\Http\Controllers\ProductoController::class, 'etiqueta'])->name('productos.etiqueta');
    Route::resource('ventas', App\Http\Controllers\VentaController::class);
    Route::get('ventas-reportes', [App\Http\Controllers\VentaController::class, 'reportes'])->name('ventas.reportes');
    Route::get('ventas-reporte-pdf', [App\Http\Controllers\VentaController::class, 'reportePdf'])->name('ventas.reporte-pdf');
    Route::get('ventas-reporte-excel', [App\Http\Controllers\VentaController::class, 'reporteExcel'])->name('ventas.reporte-excel');
    Route::get('ventas-reporte-producto-pdf', [App\Http\Controllers\VentaController::class, 'reporteProductoPdf'])->name('ventas.reporte-producto-pdf');
    Route::get('ventas-reporte-arqueo-pdf', [App\Http\Controllers\VentaController::class, 'reporteArqueoPdf'])->name('ventas.reporte-arqueo-pdf');
    Route::put('ventas/{id}/anular', [App\Http\Controllers\VentaController::class, 'anular'])->name('ventas.anular');
    Route::get('ventas/{id}/recibo', [App\Http\Controllers\VentaController::class, 'recibo'])->name('ventas.recibo');
    Route::resource('proveedores', App\Http\Controllers\ProveedorController::class);
    Route::resource('movimientos', App\Http\Controllers\MovimientoAlmacenController::class);
    Route::get('reporte-stock', [App\Http\Controllers\MovimientoAlmacenController::class, 'reporteStock'])->name('movimientos.reporte-stock');
    Route::get('reporte-stock-pdf', [App\Http\Controllers\MovimientoAlmacenController::class, 'reporteStockPdf'])->name('movimientos.reporte-stock-pdf');
    
    // Módulo de Inscripciones
    Route::post('inscripciones/cargar-excel', [App\Http\Controllers\InscripcionController::class, 'cargarExcel'])->name('inscripciones.cargar-excel');
    Route::post('inscripciones/eliminar-carga', [App\Http\Controllers\InscripcionController::class, 'eliminarCargaMasiva'])->name('inscripciones.eliminar-carga');
    Route::resource('inscripciones', App\Http\Controllers\InscripcionController::class);
    Route::post('inscripciones/{id}/pagar', [App\Http\Controllers\InscripcionController::class, 'registrarPago'])->name('inscripciones.pagar');
    Route::put('inscripciones/{id}/anular', [App\Http\Controllers\InscripcionController::class, 'anular'])->name('inscripciones.anular');
    Route::put('inscripciones/{id}/actualizar-descuento', [App\Http\Controllers\InscripcionController::class, 'actualizarDescuento'])->name('inscripciones.actualizar-descuento');
    Route::get('inscripciones-reportes', [App\Http\Controllers\InscripcionController::class, 'reportes'])->name('inscripciones.reportes');
    Route::get('inscripciones-reporte-pdf', [App\Http\Controllers\InscripcionController::class, 'reportePdf'])->name('inscripciones.reporte-pdf');
    
    // Módulo de Descuentos
    Route::resource('descuentos', App\Http\Controllers\DescuentoController::class);
    
    // Módulo de Transporte
    Route::resource('vehiculos', App\Http\Controllers\VehiculoController::class);
    Route::post('choferes/{id}/crear-usuario', [App\Http\Controllers\ChoferController::class, 'crearUsuario'])->name('choferes.crear-usuario');
    Route::resource('choferes', App\Http\Controllers\ChoferController::class);
    
    // Rutas específicas ANTES del resource
    Route::get('rutas/reporte-pdf', [App\Http\Controllers\RutaController::class, 'reportePdf'])->name('rutas.reporte-pdf');
    Route::get('rutas/{id}/detalle', [App\Http\Controllers\RutaController::class, 'detalle'])->name('rutas.detalle');
    Route::resource('rutas', App\Http\Controllers\RutaController::class);
    
    Route::resource('asignaciones-transporte', App\Http\Controllers\AsignacionTransporteController::class);
    Route::get('pagos-transporte/historial/{est_codigo}', [App\Http\Controllers\PagoTransporteController::class, 'historialPagos']);
    Route::get('pagos-transporte/reporte-ingresos', [App\Http\Controllers\PagoTransporteController::class, 'reporteIngresos'])->name('pagos-transporte.reporte-ingresos');
    Route::put('pagos-transporte/{id}/anular', [App\Http\Controllers\PagoTransporteController::class, 'anular'])->name('pagos-transporte.anular');
    Route::resource('pagos-transporte', App\Http\Controllers\PagoTransporteController::class);
    Route::resource('estudiantes-rutas', App\Http\Controllers\EstudianteRutaController::class);
    
    // Módulo de Servicios
    Route::resource('servicios', App\Http\Controllers\ServicioController::class);
    Route::get('pagos-servicios/{id}/recibo', [App\Http\Controllers\PagoServicioController::class, 'recibo'])->name('pagos-servicios.recibo');
    Route::put('pagos-servicios/{id}/anular', [App\Http\Controllers\PagoServicioController::class, 'anular'])->name('pagos-servicios.anular');
    Route::resource('pagos-servicios', App\Http\Controllers\PagoServicioController::class);
    Route::get('pagos-servicios-reporte-pdf', [App\Http\Controllers\PagoServicioController::class, 'reportePdf'])->name('pagos-servicios.reporte-pdf');
    
    // Reportes de Pagos
    Route::get('pagos-reporte-pdf', [App\Http\Controllers\PagoController::class, 'reportePdf'])->name('pagos.reporte-pdf');
    Route::get('pagos-reporte-excel', [App\Http\Controllers\PagoController::class, 'reporteExcel'])->name('pagos.reporte-excel');
    Route::get('pagos-resumen-anual', [App\Http\Controllers\PagoController::class, 'resumenAnual'])->name('pagos.resumen-anual');
    Route::get('pagos-resumen-anual-pdf', [App\Http\Controllers\PagoController::class, 'resumenAnualPdf'])->name('pagos.resumen-anual-pdf');
    Route::get('pagos-resumen-anual-excel', [App\Http\Controllers\PagoController::class, 'resumenAnualExcel'])->name('pagos.resumen-anual-excel');
    Route::get('pagos-mora', [App\Http\Controllers\PagoController::class, 'mora'])->name('pagos.mora');
    Route::get('pagos-mora-pdf', [App\Http\Controllers\PagoController::class, 'moraPdf'])->name('pagos.mora-pdf');
    Route::put('pagos/{id}/anular', [App\Http\Controllers\PagoController::class, 'anular'])->name('pagos.anular');
    
    // API para cargar padres por estudiante
    Route::get('api/estudiante-padres/{est_codigo}', [App\Http\Controllers\PagoController::class, 'getPadresByEstudiante']);
    Route::get('api/estudiante-inscripcion/{est_codigo}', [App\Http\Controllers\PagoController::class, 'getEstudianteInscripcion']);
    Route::get('api/estudiantes/{est_codigo}/padres', [App\Http\Controllers\EstudianteController::class, 'getPadres']);
    Route::get('api/padres/{id}', [App\Http\Controllers\PadreFamiliaController::class, 'show']);
    Route::get('api/producto-por-barcode/{barcode}', [App\Http\Controllers\ProductoController::class, 'buscarPorBarcode']);
    Route::get('api/venta-por-codigo/{codigo}', [App\Http\Controllers\VentaController::class, 'getPorCodigo']);
    
    // Módulo de Psicopedagogía
    Route::get('psicopedagogia/buscar-estudiante/{codigo}', [App\Http\Controllers\PsicopedagogiaController::class, 'buscarEstudiante']);
    Route::get('psicopedagogia/reporte-pdf', [App\Http\Controllers\PsicopedagogiaController::class, 'reportePdf'])->name('psicopedagogia.reporte-pdf');
    Route::get('psicopedagogia/{id}/compromiso-pdf', [App\Http\Controllers\PsicopedagogiaController::class, 'compromisoPdf'])->name('psicopedagogia.compromiso-pdf');
    Route::resource('psicopedagogia', App\Http\Controllers\PsicopedagogiaController::class);
    
    // Módulo de Enfermería
    Route::get('enfermeria/buscar-estudiante/{codigo}', [App\Http\Controllers\EnfermeriaController::class, 'buscarEstudiante']);
    Route::get('enfermeria/reporte-pdf', [App\Http\Controllers\EnfermeriaController::class, 'reportePdf'])->name('enfermeria.reporte-pdf');
    Route::get('enfermeria/reporte-docentes-pdf', [App\Http\Controllers\EnfermeriaController::class, 'reporteDocentesPdf'])->name('enfermeria.reporte-docentes-pdf');
    Route::resource('enfermeria', App\Http\Controllers\EnfermeriaController::class);
});