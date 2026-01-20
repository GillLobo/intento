<?php
// -----------------------------------------------------------
// guardar_planificacion.php - SOLO CREAR planificación
// -----------------------------------------------------------
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos del formulario
$IDPLA = isset($_POST['IDPLA']) ? intval($_POST['IDPLA']) : 0;
$DESCRIP = isset($_POST['DESCRIP']) ? trim($_POST['DESCRIP']) : '';
$FECINI = isset($_POST['FECINI']) ? $_POST['FECINI'] : '';
$FECCUL = isset($_POST['FECCUL']) ? $_POST['FECCUL'] : '';
$estado = isset($_POST['estado']) ? $_POST['estado'] : 'ACTIVO';

// Validaciones
if (empty($DESCRIP) || empty($FECINI) || empty($FECCUL)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit();
}

if ($FECCUL <= $FECINI) {
    echo json_encode(['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio']);
    exit();
}

try {
    // SOLO INSERTAR - NO ACTUALIZAR
    // Si IDPLA es mayor a 0, alguien está intentando editar (no permitido)
    if ($IDPLA > 0) {
        echo json_encode(['success' => false, 'message' => 'La edición no está permitida']);
        exit();
    }
    
    // Insertar nuevo registro
    $query = "INSERT INTO planificacion (DESCRIP, FECINI, FECCUL, estado) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $DESCRIP, $FECINI, $FECCUL, $estado);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Período creado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear en la base de datos']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>