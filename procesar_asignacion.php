<?php
session_start();
include 'connection.php';

$dashboard = "dashboard.php"; // por defecto supervisor

if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

$mensaje = '';
$tipo = ''; // 'exito' o 'error'


$IDPROF = $_POST['IDPROF'];
$IDMAT  = $_POST['IDMAT'];
$IDPLA  = $_POST['IDPLA'];
$FECINI = $_POST['FECINI'];
$FECCUL = $_POST['FECCUL'];
$HORAINI = $_POST['HORAINI'];
$HORACUL = $_POST['HORACUL'];

$stmt = $conn->prepare("INSERT INTO asignacion 
(IDPLA, IDMAT, IDPROF, FECINI, FECCUL, HORAINI, HORACUL)
VALUES (?,?,?,?,?,?,?)");

$stmt->bind_param("iiissss", 
    $IDPLA, $IDMAT, $IDPROF, $FECINI, $FECCUL, $HORAINI, $HORACUL
);

if ($stmt->execute()) {
    header("Location: asignar_materias.php?msg=ok");
} else {
    header("Location: asignar_materias.php?msg=error");
}
?>
