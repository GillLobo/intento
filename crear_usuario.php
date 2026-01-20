<?php
session_start();
include 'connection.php';

// ACCESO: solo Administrador
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header("Location: login.php?msg=no_autorizado");
    exit;
}

$errores = [];
$exito = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = ($_POST['rol'] === 'Administrador') ? 'Administrador' : 'Supervisor';
    $estado   = ($_POST['estado'] === 'activo') ? 'activo' : 'inactivo';

    // ================================
    //        VALIDACIONES
    // ================================

    // NOMBRE
    if ($nombre === '') {
        $errores[] = "El nombre es obligatorio.";
    } elseif (!preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,100}$/u", $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios (2-100 caracteres).";
    }

    // EMAIL FORMATO GENERAL
    if ($email === '') {
        $errores[] = "El correo es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no tiene un formato válido.";
    } 
    // VALIDACIÓN DEL DOMINIO UJGH
    elseif (!preg_match("/@ujgh\.edu\.ve$/i", $email)) {
        $errores[] = "El correo debe pertenecer al dominio @ujgh.edu.ve.";
    } 
    else {
        // Verificar duplicados
        $chk = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $errores[] = "El correo ya está registrado.";
        }

        $chk->close();
    }

    // CONTRASEÑA
    if ($password === '') {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{6,12}$/', $password)) {
        $errores[] = "La contraseña debe tener 6-12 caracteres, incluir mayúsculas, minúsculas, números y un símbolo.";
    }

    // ================================
    //        INSERTAR EN BD
    // ================================
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $ins = $conn->prepare("
            INSERT INTO usuarios (nombre, email, password, rol, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->bind_param("sssss", $nombre, $email, $hash, $rol, $estado);

        if ($ins->execute()) {
            $ins->close();
            header("Location: configuracion_usuarios.php?msg=creado");
            exit;
        } else {
            $errores[] = "Error al crear usuario. Intenta nuevamente.";
            $ins->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Usuario</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    
    .form-wrapper {
        width: 100%;
        max-width: 500px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 35px;
    }
    
    h2 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 25px;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 12px;
    }
    
    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: linear-gradient(to right, #007bff, #00b894);
        border-radius: 2px;
    }
    
    .alert-error {
        background: linear-gradient(135deg, #ffeaea, #ffcccc);
        border-left: 5px solid #e74c3c;
        color: #c0392b;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }
    
    .alert-error p {
        margin: 5px 0;
    }
    
    .user-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    label {
        font-weight: 600;
        color: #34495e;
        margin-bottom: 5px;
        display: block;
        font-size: 0.95rem;
    }
    
    .input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s;
        background-color: #fafafa;
    }
    
    .input:focus {
        outline: none;
        border-color: #007bff;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    select.input {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
        padding-right: 45px;
    }
    
    /* Contenedor para contraseña con ojo */
    .password-container {
        position: relative;
        width: 100%;
    }
    
    .password-container .input {
        padding-right: 50px; /* Espacio para el ojo */
    }
    
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 18px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.3s;
        border-radius: 50%;
    }
    
    .toggle-password:hover {
        color: #007bff;
        background-color: rgba(0, 123, 255, 0.1);
    }
    
    .btn-submit, .btn-cancel {
        padding: 14px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #007bff, #0056cc);
        color: white;
    }
    
    .btn-submit:hover {
        background: linear-gradient(135deg, #0056cc, #004099);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 86, 204, 0.3);
    }
    
    .btn-cancel {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }
    
    .btn-cancel:hover {
        background: linear-gradient(135deg, #5a6268, #484e53);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(90, 98, 104, 0.3);
    }
    
    @media (max-width: 768px) {
        .form-wrapper {
            padding: 25px 20px;
            margin: 15px;
        }
        
        h2 {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 480px) {
        body {
            padding: 10px;
        }
        
        .form-wrapper {
            padding: 20px 15px;
            border-radius: 12px;
        }
        
        .input {
            padding: 12px 14px;
            font-size: 15px;
        }
        
        .btn-submit, .btn-cancel {
            padding: 12px;
            font-size: 15px;
        }
    }
</style>
</head>
<body>

<div class="form-wrapper">

    <h2>Crear Usuario</h2>

    <?php if (!empty($errores)): ?>
        <div class="alert-error">
            <?php foreach ($errores as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="user-form" novalidate>

        <div>
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre"
                   value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>"
                   placeholder="Nombre y Apellido"
                   required
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,100}"
                   title="Solo letras y espacios (2-100 caracteres)"
                   class="input">
        </div>

        <div>
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email"
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                   placeholder="correo@ujgh.edu.ve"
                   required
                   pattern="^[a-zA-Z0-9._%+-]+@ujgh\.edu\.ve$"
                   title="El correo debe terminar en @ujgh.edu.ve"
                   class="input">
        </div>

        <div>
            <label for="password">Contraseña</label>
            <div class="password-container">
                <input type="password" id="password" name="password"
                       placeholder="Usa mayúsculas, números y símbolos"
                       required minlength="6" maxlength="12"
                       class="input"
                       title="Debe tener 6-12 caracteres, mayúsculas, minúsculas, números y un símbolo">
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                Debe contener: 6-12 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 símbolo
            </small>
        </div>

        <div>
            <label for="rol">Rol</label>
            <select id="rol" name="rol" class="input">
                <option value="Supervisor">Supervisor</option>
                <option value="Administrador">Administrador</option>
            </select>
        </div>

        <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado" class="input">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 10px;">
            <button type="submit" class="btn-submit">Crear Usuario</button>
            <a href="configuracion_usuarios.php" class="btn-cancel">Cancelar</a>
        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = togglePassword.querySelector('i');
    
    // Función para mostrar/ocultar contraseña
    function togglePasswordVisibility() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Cambiar icono
        if (type === 'text') {
            eyeIcon.className = 'fas fa-eye-slash';
            togglePassword.style.color = '#007bff';
            togglePassword.setAttribute('title', 'Ocultar contraseña');
        } else {
            eyeIcon.className = 'fas fa-eye';
            togglePassword.style.color = '';
            togglePassword.setAttribute('title', 'Mostrar contraseña');
        }
        
        // Mantener el foco en el campo
        password.focus();
    }
    
    // Evento clic en el botón
    togglePassword.addEventListener('click', togglePasswordVisibility);
    
    // Opcional: Permitir mostrar/ocultar con Enter cuando el botón tiene foco
    togglePassword.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            togglePasswordVisibility();
        }
    });
    
    // Validación en tiempo real de contraseña
    password.addEventListener('input', function() {
        const value = this.value;
        const hasUpper = /[A-Z]/.test(value);
        const hasLower = /[a-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSymbol = /[\W_]/.test(value);
        const validLength = value.length >= 6 && value.length <= 12;
        
        // Opcional: Cambiar color del borde según validación
        if (value === '') {
            this.style.borderColor = '#e0e0e0';
        } else if (hasUpper && hasLower && hasNumber && hasSymbol && validLength) {
            this.style.borderColor = '#28a745';
        } else {
            this.style.borderColor = '#dc3545';
        }
    });
});
</script>

</body>
</html>