<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de base de datos
$host = '127.0.0.1';
$dbname = 'uepinter_interandino';
$username = 'uepinter_nico';
$password = 'batman581321';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? null;
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$hora = $_POST['hora'] ?? date('H:i:s');

if (!$codigo) {
    echo json_encode(['success' => false, 'message' => 'Código requerido']);
    exit;
}

try {
    // Verificar estudiante
    $stmt = $pdo->prepare("SELECT est_codigo, est_nombres, est_apellidos, cur_codigo FROM colegio_estudiantes WHERE est_codigo = ? AND est_visible = 1");
    $stmt->execute([$codigo]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
        exit;
    }
    
    // Determinar turno según hora (7-12 mañana, 13-22 tarde)
    $horaNum = (int)substr($hora, 0, 2);
    
    if ($horaNum >= 7 && $horaNum < 13) {
        $turno = 'Mañana';
    } elseif ($horaNum >= 13 && $horaNum <= 22) {
        $turno = 'Tarde';
    } else {
        echo json_encode(['success' => false, 'message' => 'Fuera de horario (7-12 o 13-22)']);
        exit;
    }
    
    // Verificar si ya registró en este turno hoy
    if ($turno == 'Mañana') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM colegio_asistencia 
            WHERE estud_codigo = ? 
            AND DATE(asis_fecha) = ? 
            AND TIME(asis_hora) >= '07:00:00' 
            AND TIME(asis_hora) < '13:00:00'
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM colegio_asistencia 
            WHERE estud_codigo = ? 
            AND DATE(asis_fecha) = ? 
            AND TIME(asis_hora) >= '13:00:00' 
            AND TIME(asis_hora) <= '22:00:00'
        ");
    }
    $stmt->execute([$codigo, $fecha]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya registrado en turno ' . $turno]);
        exit;
    }
    
    // Registrar asistencia
    $stmt = $pdo->prepare("INSERT INTO colegio_asistencia (estud_codigo, asis_fecha, asis_hora, asis_fecha2) VALUES (?, ?, ?, NOW())");
    $resultado = $stmt->execute([$codigo, $fecha, $hora]);
    
    if (!$resultado) {
        echo json_encode(['success' => false, 'message' => 'Error al insertar en BD']);
        exit;
    }
    
    // Obtener curso
    $stmt = $pdo->prepare("SELECT cur_nombre FROM colegio_cursos WHERE cur_codigo = ?");
    $stmt->execute([$estudiante['cur_codigo']]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Asistencia registrada - ' . $turno,
        'estudiante' => [
            'codigo' => $estudiante['est_codigo'],
            'nombres' => $estudiante['est_nombres'],
            'apellidos' => $estudiante['est_apellidos'],
            'curso' => $curso['cur_nombre'] ?? 'N/A'
        ],
        'turno' => $turno
    ]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
