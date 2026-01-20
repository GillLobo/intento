<?php
// generar_nuevo_comunicado.php
include 'connection.php';

$comunicado = '0001-UJGH-DIP';
$q = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(comunicado, '-', 1), '-', -1) AS UNSIGNED)) as max_num FROM formularios");
if ($q && $row = $q->fetch_assoc()) {
    $max_num = $row['max_num'] ?? 0;
    $nuevo_numero = $max_num + 1;
    $comunicado = str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT) . '-UJGH-DIP';
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'comunicado' => $comunicado]);
?>