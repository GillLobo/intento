<?php
// ----------------------------------------------------------
// buscar_docente.php
// ----------------------------------------------------------
// Este script recibe una cédula de profesor y devuelve:
// - Su nombre completo.
// - Las asignaturas que tiene asignadas (desde la tabla 'asignacion').
// Todo se devuelve en formato JSON para que JavaScript lo procese en el formulario.
// ----------------------------------------------------------

include 'connection.php'; // Conexión a la base de datos

// Inicializamos variables de salida
$cedula = $_GET['cedula'] ?? '';
$respuesta = ['success' => false, 'nombre' => '', 'asignaturas' => [], 'message' => ''];

// Sanitizar cédula (permitir solo dígitos)
$cedula_solo = preg_replace('/\D+/', '', $cedula);
if ($cedula_solo === '' || strlen($cedula_solo) < 6 || strlen($cedula_solo) > 10) {
    $respuesta['message'] = 'Cédula inválida.';
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// 🔹 1. Buscar el profesor por su cédula
$stmt = $conn->prepare("
    SELECT IDPROF, NOMAPE 
    FROM profesores 
    WHERE CIPROF = ?
");
if (!$stmt) {
    $respuesta['message'] = 'Error en la consulta.';
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

$stmt->bind_param("i", $cedula_solo);
$stmt->execute();
$stmt->bind_result($idprof, $nombre);

if ($stmt->fetch()) {
    // Si encuentra al profesor, guardamos el nombre
    $respuesta['success'] = true;
    $respuesta['nombre'] = $nombre;
    $stmt->close();

    // 🔹 2. Buscar las asignaturas del profesor
    $query = "
        SELECT m.NOMMAT
        FROM asignacion a
        INNER JOIN materias m ON a.IDMAT = m.IDMAT
        WHERE a.IDPROF = ?
    ";

    $stmt2 = $conn->prepare($query);
    if ($stmt2) {
        $stmt2->bind_param("i", $idprof);
        $stmt2->execute();
        $result = $stmt2->get_result();

        // Guardamos todas las materias encontradas
        while ($row = $result->fetch_assoc()) {
            $respuesta['asignaturas'][] = $row['NOMMAT'];
        }

        $stmt2->close();
    }

} else {
    // Si no se encuentra el profesor
    $respuesta['message'] = 'No encontrado';
}

// Cerramos conexión
$conn->close();

// Devolvemos respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($respuesta);
?>
