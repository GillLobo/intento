<?php
// toggle_estado.php
session_start();
include 'connection.php';
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header("Location: login.php?msg=no_autorizado");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: configuracion_usuarios.php?msg=error"); exit;
}

// obtener estado actual
$stmt = $conn->prepare("SELECT estado FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { $stmt->close(); header("Location: configuracion_usuarios.php?msg=error"); exit; }
$row = $res->fetch_assoc();
$stmt->close();

$nuevo = ($row['estado'] === 'activo') ? 'inactivo' : 'activo';
$upd = $conn->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
$upd->bind_param("si", $nuevo, $id);
$ok = $upd->execute();
$upd->close();

header("Location: configuracion_usuarios.php?msg=" . ($ok ? 'toggle' : 'error'));
exit;
