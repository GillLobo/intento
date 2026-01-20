<?php
// -----------------------------------------------------------
// acciones_planificacion.php - SOLO ACTIVAR/DESACTIVAR
// -----------------------------------------------------------
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

// Validar que solo sean acciones permitidas
if ($accion !== 'activar' && $accion !== 'desactivar') {
    echo json_encode(['success' => false, 'message' => 'Acción no permitida']);
    exit();
}

try {
    switch ($accion) {
        case 'activar':
            $query = "UPDATE planificacion SET estado = 'ACTIVO' WHERE IDPLA = ?";
            $mensaje = 'Período activado correctamente';
            break;
            
        case 'desactivar':
            $query = "UPDATE planificacion SET estado = 'INACTIVO' WHERE IDPLA = ?";
            $mensaje = 'Período desactivado correctamente';
            break;
            
        default:
            // No debería llegar aquí por la validación anterior
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit();
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $mensaje]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la acción']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>