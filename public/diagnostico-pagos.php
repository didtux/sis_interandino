<?php
/**
 * Script de Diagnóstico - Módulo de Pagos
 * 
 * Ejecutar desde: http://localhost/Sis_Intterandino/public/diagnostico-pagos.php
 * 
 * Este script verifica:
 * 1. Existencia de campos en tabla pagos_mensualidades
 * 2. Registros sin código de recibo
 * 3. Estudiantes con inscripción activa
 * 4. Relaciones entre estudiantes, inscripciones y padres
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "<h1>Diagnóstico del Módulo de Pagos</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; }
</style>";

// 1. Verificar campos en tabla pagos_mensualidades
echo "<div class='section'>";
echo "<h2>1. Verificación de Campos en tabla pagos_mensualidades</h2>";

$tienePagosCodigo = Schema::hasColumn('pagos_mensualidades', 'pagos_codigo');
$tienePagosSinFactura = Schema::hasColumn('pagos_mensualidades', 'pagos_sin_factura');
$tienePagosEstado = Schema::hasColumn('pagos_mensualidades', 'pagos_estado');

echo "<p>Campo <strong>pagos_codigo</strong>: ";
echo $tienePagosCodigo ? "<span class='success'>✓ EXISTE</span>" : "<span class='error'>✗ NO EXISTE</span>";
echo "</p>";

echo "<p>Campo <strong>pagos_sin_factura</strong>: ";
echo $tienePagosSinFactura ? "<span class='success'>✓ EXISTE</span>" : "<span class='error'>✗ NO EXISTE</span>";
echo "</p>";

echo "<p>Campo <strong>pagos_estado</strong>: ";
echo $tienePagosEstado ? "<span class='success'>✓ EXISTE</span>" : "<span class='error'>✗ NO EXISTE</span>";
echo "</p>";

if (!$tienePagosCodigo || !$tienePagosSinFactura || !$tienePagosEstado) {
    echo "<p class='error'>⚠ ACCIÓN REQUERIDA: Ejecutar el archivo SQL: database/sql/alter_pagos_mensualidades_factura.sql</p>";
}
echo "</div>";

// 2. Verificar registros sin código
if ($tienePagosCodigo) {
    echo "<div class='section'>";
    echo "<h2>2. Registros sin Código de Recibo</h2>";
    
    $sinCodigo = DB::table('pagos_mensualidades')
        ->whereNull('pagos_codigo')
        ->orWhere('pagos_codigo', '')
        ->count();
    
    if ($sinCodigo > 0) {
        echo "<p class='warning'>⚠ Hay {$sinCodigo} registros sin código de recibo</p>";
        echo "<p>Ejecutar este SQL para corregir:</p>";
        echo "<pre>UPDATE `pagos_mensualidades` 
SET `pagos_codigo` = CONCAT('REC', LPAD(pagos_id, 5, '0'))
WHERE `pagos_codigo` IS NULL OR `pagos_codigo` = '';</pre>";
    } else {
        echo "<p class='success'>✓ Todos los registros tienen código de recibo</p>";
    }
    
    // Mostrar últimos 5 registros
    $ultimos = DB::table('pagos_mensualidades')
        ->select('pagos_id', 'pagos_codigo', 'pagos_sin_factura', 'concepto', 'pagos_precio')
        ->orderBy('pagos_id', 'desc')
        ->limit(5)
        ->get();
    
    echo "<h3>Últimos 5 Pagos Registrados:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Código</th><th>Sin Factura</th><th>Concepto</th><th>Precio</th></tr>";
    foreach ($ultimos as $pago) {
        echo "<tr>";
        echo "<td>{$pago->pagos_id}</td>";
        echo "<td>{$pago->pagos_codigo}</td>";
        echo "<td>" . ($pago->pagos_sin_factura ?? 0) . "</td>";
        echo "<td>{$pago->concepto}</td>";
        echo "<td>Bs. {$pago->pagos_precio}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}

// 3. Verificar estudiantes con inscripción activa
echo "<div class='section'>";
echo "<h2>3. Estudiantes con Inscripción Activa</h2>";

$year = date('Y');
$estudiantesInscritos = DB::table('colegio_estudiantes as e')
    ->join('inscripciones as i', 'e.est_codigo', '=', 'i.est_codigo')
    ->where('e.est_visible', 1)
    ->where('i.insc_estado', 1)
    ->where('i.insc_gestion', $year)
    ->count();

echo "<p>Estudiantes inscritos en {$year}: <strong>{$estudiantesInscritos}</strong></p>";

if ($estudiantesInscritos == 0) {
    echo "<p class='error'>⚠ NO hay estudiantes inscritos para el año actual</p>";
    echo "<p>Debes crear inscripciones desde el módulo de Inscripciones</p>";
} else {
    echo "<p class='success'>✓ Hay estudiantes inscritos</p>";
    
    // Mostrar algunos estudiantes
    $estudiantes = DB::table('colegio_estudiantes as e')
        ->join('inscripciones as i', 'e.est_codigo', '=', 'i.est_codigo')
        ->join('cole_padresfamilia as p', 'i.pfam_codigo', '=', 'p.pfam_codigo')
        ->leftJoin('colegio_cursos as c', 'e.cur_codigo', '=', 'c.cur_codigo')
        ->where('e.est_visible', 1)
        ->where('i.insc_estado', 1)
        ->where('i.insc_gestion', $year)
        ->select(
            'e.est_nombres',
            'e.est_apellidos',
            'c.cur_nombre',
            'p.pfam_nombres',
            'i.insc_monto_final',
            'i.insc_sin_factura'
        )
        ->limit(10)
        ->get();
    
    echo "<h3>Primeros 10 Estudiantes Inscritos:</h3>";
    echo "<table>";
    echo "<tr><th>Estudiante</th><th>Curso</th><th>Padre</th><th>Monto Final</th><th>Mensualidad</th><th>Tipo</th></tr>";
    foreach ($estudiantes as $est) {
        $mensualidad = $est->insc_monto_final / 10;
        $tipo = $est->insc_sin_factura ? 'TAL' : 'REC';
        echo "<tr>";
        echo "<td>{$est->est_nombres} {$est->est_apellidos}</td>";
        echo "<td>{$est->cur_nombre}</td>";
        echo "<td>{$est->pfam_nombres}</td>";
        echo "<td>Bs. " . number_format($est->insc_monto_final, 2) . "</td>";
        echo "<td>Bs. " . number_format($mensualidad, 2) . "</td>";
        echo "<td><strong>{$tipo}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 4. Verificar descuentos
echo "<div class='section'>";
echo "<h2>4. Descuentos Configurados</h2>";

$descuentos = DB::table('descuentos')
    ->where('desc_estado', 1)
    ->get();

echo "<p>Total de descuentos activos: <strong>" . count($descuentos) . "</strong></p>";

if (count($descuentos) > 0) {
    echo "<table>";
    echo "<tr><th>Nombre</th><th>Porcentaje</th><th>Sin Factura</th></tr>";
    foreach ($descuentos as $desc) {
        $sinFactura = stripos($desc->desc_nombre, 'sin factura') !== false ? 'Sí' : 'No';
        echo "<tr>";
        echo "<td>{$desc->desc_nombre}</td>";
        echo "<td>{$desc->desc_porcentaje}%</td>";
        echo "<td>{$sinFactura}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 5. Resumen y recomendaciones
echo "<div class='section'>";
echo "<h2>5. Resumen y Recomendaciones</h2>";

$problemas = [];
if (!$tienePagosCodigo || !$tienePagosSinFactura || !$tienePagosEstado) {
    $problemas[] = "Faltan campos en tabla pagos_mensualidades";
}
if ($tienePagosCodigo && $sinCodigo > 0) {
    $problemas[] = "Hay registros sin código de recibo";
}
if ($estudiantesInscritos == 0) {
    $problemas[] = "No hay estudiantes inscritos para el año actual";
}

if (count($problemas) > 0) {
    echo "<p class='error'><strong>⚠ PROBLEMAS DETECTADOS:</strong></p>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li class='error'>{$problema}</li>";
    }
    echo "</ul>";
    
    echo "<h3>Acciones Recomendadas:</h3>";
    echo "<ol>";
    echo "<li>Ejecutar: <code>database/sql/alter_pagos_mensualidades_factura.sql</code></li>";
    echo "<li>Ejecutar: <code>database/sql/alter_pagos_mensualidades_estado.sql</code></li>";
    echo "<li>Limpiar caché: <code>php artisan cache:clear</code></li>";
    echo "<li>Crear inscripciones para estudiantes del año actual</li>";
    echo "</ol>";
} else {
    echo "<p class='success'><strong>✓ TODO ESTÁ CORRECTO</strong></p>";
    echo "<p>El sistema está listo para registrar mensualidades.</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><small>Diagnóstico ejecutado el: " . date('Y-m-d H:i:s') . "</small></p>";
