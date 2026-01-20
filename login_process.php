<?php
session_start();
include 'connection.php';

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validación básica
if ($email === '' || $password === '') {
    header("Location: login.php?msg=campos_vacios");
    exit;
}

// Traemos todos los datos necesarios
$stmt = $conn->prepare("SELECT id, nombre, email, password, rol, estado FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Usuario encontrado
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verificar contraseña
    if (password_verify($password, $user['password'])) {

        // VALIDACIÓN DE ROL Y ESTADO
        if ($user['rol'] == 'Administrador') {
            // admin siempre entra
            // Guardamos datos en sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_email'] = $user['email'];
            $_SESSION['usuario_rol'] = $user['rol'];
            $_SESSION['usuario_estado'] = $user['estado'];

            // Redirigir al dashboard
            header("Location: dashboard_admin.php");
            exit;
        }
        else if ($user['rol'] == 'Supervisor') {
            if ($user['estado'] !== 'activo') {
                // supervisor inactivo NO puede entrar
                header("Location: login.php?msg=supervisor_inactivo");
                exit;
            }
        }
        else {
            // Cualquier otro rol (por si acaso)
            header("Location: login.php?msg=rol_no_autorizado");
            exit;
        }

        // Guardamos datos en sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['usuario_rol'] = $user['rol'];
        $_SESSION['usuario_estado'] = $user['estado'];

        // Redirigir al dashboard
        header("Location: dashboard.php");
        exit;

    } else {
        // Contraseña incorrecta
        header("Location: login.php?msg=pass_incorrecta");
        exit;
    }

} else {
    // No existe el usuario
    header("Location: login.php?msg=no_existe");
    exit;
}

// Cerrar conexión
$stmt->close();
$conn->close();
?>
