<?php
// obtener_detalles.php
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => true, 'mensaje' => 'ID no especificado']);
    exit;
}

$id = (int)$_GET['id'];

// Consulta para obtener datos del formulario
$query_formulario = "
    SELECT 
        f.*,
        p.DESCRIP as pap_nombre
    FROM formularios f
    LEFT JOIN planificacion p ON f.pap_id = p.IDPLA
    WHERE f.id = ?
";

$stmt = $conn->prepare($query_formulario);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Reporte no encontrado']);
    exit;
}

$formulario = $result->fetch_assoc();
$stmt->close();

// Consulta para obtener docentes relacionados
$query_docentes = "
    SELECT cedula, docente, asignatura, horas
    FROM formulario_docentes
    WHERE formulario_id = ?
    ORDER BY id
";

$stmt2 = $conn->prepare($query_docentes);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$docentes = [];
while ($row = $result2->fetch_assoc()) {
    $docentes[] = $row;
}
$stmt2->close();

// Formatear fecha
$fecha_formateada = '';
if (!empty($formulario['fecha'])) {
    $fecha_obj = new DateTime($formulario['fecha']);
    $fecha_formateada = $fecha_obj->format('d/m/Y');
}

// Preparar respuesta
$respuesta = [
    'error' => false,
    'id' => $formulario['id'],
    'comunicado' => $formulario['comunicado'],
    'fecha' => $formulario['fecha'],
    'fecha_formateada' => $fecha_formateada,
    'asunto' => $formulario['asunto'],
    'para' => $formulario['para'],
    'de_nombre' => $formulario['de_nombre'],
    'parrafo' => $formulario['parrafo'],
    'pap_nombre' => $formulario['pap_nombre'],
    'docentes' => $docentes
];

echo json_encode($respuesta);
?>