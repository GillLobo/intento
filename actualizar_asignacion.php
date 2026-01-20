<?php
// actualizar_asignacion.php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: asignar_materias.php?msg=error");
    exit;
}

$id     = $_POST['IDASIG'];
$idpla  = $_POST['IDPLA'];
$idmat  = $_POST['IDMAT'];
$idprof = $_POST['IDPROF'];
$fi     = $_POST['FECINI'];
$fc     = $_POST['FECCUL'];
$hi     = $_POST['HORAINI'];
$hc     = $_POST['HORACUL'];

$stmt = $conn->prepare("UPDATE asignacion SET 
    IDPLA = ?, 
    IDMAT = ?, 
    IDPROF = ?, 
    FECINI = ?, 
    FECCUL = ?, 
    HORAINI = ?, 
    HORACUL = ?
    WHERE IDASIG = ?");

$stmt->bind_param("iiissssi", $idpla, $idmat, $idprof, $fi, $fc, $hi, $hc, $id);

if ($stmt->execute()) {
    header("Location: asignar_materias.php?msg=editado");
} else {
    header("Location: asignar_materias.php?msg=error");
}

$stmt->close();
$conn->close();
?>
