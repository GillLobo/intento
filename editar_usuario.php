<?php
// editar_usuario.php
session_start();
include 'connection.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header("Location: login.php?msg=no_autorizado");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: configuracion_usuarios.php?msg=error"); 
    exit;
}

$errores = [];
$mensaje_exito = '';

// Cargar datos actuales del usuario
$stmt = $conn->prepare("SELECT id, nombre, email, rol, estado FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    header("Location: configuracion_usuarios.php?msg=error"); 
    exit;
}
$user = $res->fetch_assoc();
$stmt->close();

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = ($_POST['rol'] === 'Administrador') ? 'Administrador' : 'Supervisor';
    $estado = ($_POST['estado'] === 'activo') ? 'activo' : 'inactivo';

    // ================================
    //        VALIDACIONES
    // ================================
    
    // NOMBRE
    if ($nombre === '') {
        $errores[] = "El nombre es obligatorio.";
    } elseif (!preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,100}$/u", $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios (2-100 caracteres).";
    }

    // EMAIL
    if ($email === '') {
        $errores[] = "El correo es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no tiene un formato válido.";
    } elseif (!preg_match("/@ujgh\.edu\.ve$/i", $email)) {
        $errores[] = "El correo debe pertenecer al dominio @ujgh.edu.ve.";
    } else {
        // Verificar si el email ya existe en otro usuario
        $chk = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $chk->bind_param("si", $email, $id);
        $chk->execute();
        $chk->store_result();
        
        if ($chk->num_rows > 0) {
            $errores[] = "El correo ya está registrado por otro usuario.";
        }
        $chk->close();
    }

    // Si no hay errores, actualizar
    if (empty($errores)) {
        $upd = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, estado=? WHERE id=?");
        $upd->bind_param("ssssi", $nombre, $email, $rol, $estado, $id);
        
        if ($upd->execute()) {
            $upd->close();
            
            // Actualizar los datos locales para mostrar en el formulario
            $user['nombre'] = $nombre;
            $user['email'] = $email;
            $user['rol'] = $rol;
            $user['estado'] = $estado;
            
            $mensaje_exito = "✔ Usuario actualizado correctamente.";
        } else {
            $errores[] = "Error al actualizar el usuario: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema UJGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset y base */
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
        
        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 35px;
            transition: all 0.3s ease;
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
        
        /* Mensajes de alerta */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ffeaea, #ffcccc);
            border-left: 5px solid #e74c3c;
            color: #c0392b;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d5ffd5, #a3ffa3);
            border-left: 5px solid #27ae60;
            color: #1e8449;
        }
        
        /* Formulario */
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #fafafa;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #007bff;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        /* Botones */
        .buttons-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: 140px;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056cc);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056cc, #004099);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 86, 204, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #484e53);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(90, 98, 104, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        /* Información del usuario */
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .user-info p {
            margin: 5px 0;
            color: #495057;
        }
        
        .user-info strong {
            color: #2c3e50;
        }
        
        /* Animación */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 25px 20px;
                margin: 15px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .buttons-container {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 20px 15px;
                border-radius: 12px;
            }
            
            .form-input, .form-select {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn {
                padding: 12px 14px;
                font-size: 15px;
            }
        }
        
        /* Iconos */
        .icon {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-user-edit icon"></i> Editar Usuario</h2>
        
        <!-- Información del usuario -->
        <div class="user-info">
            <p><strong>ID:</strong> #<?php echo $user['id']; ?></p>
            <p><strong>Última actualización:</strong> Edición de datos de usuario</p>
        </div>
        
        <!-- Mensajes de error/éxito -->
        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <?php foreach ($errores as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success">
                <p><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Formulario -->
        <form method="POST" id="editUserForm">
            <div class="form-group">
                <label for="nombre"><i class="fas fa-user icon"></i> Nombre completo</label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       class="form-input"
                       value="<?php echo htmlspecialchars($user['nombre']); ?>"
                       placeholder="Ej: Juan Pérez"
                       required
                       pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,100}"
                       title="Solo letras y espacios (2-100 caracteres)">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope icon"></i> Correo institucional</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-input"
                       value="<?php echo htmlspecialchars($user['email']); ?>"
                       placeholder="usuario@ujgh.edu.ve"
                       required
                       pattern="^[a-zA-Z0-9._%+-]+@ujgh\.edu\.ve$"
                       title="El correo debe terminar en @ujgh.edu.ve">
            </div>
            
            <div class="form-group">
                <label for="rol"><i class="fas fa-user-tag icon"></i> Rol del usuario</label>
                <select id="rol" name="rol" class="form-select">
                    <option value="Supervisor" <?php echo ($user['rol'] === 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="Administrador" <?php echo ($user['rol'] === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                </select>
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    <i class="fas fa-info-circle"></i> Administrador: acceso completo | Supervisor: acceso limitado
                </small>
            </div>
            
            <div class="form-group">
                <label for="estado"><i class="fas fa-power-off icon"></i> Estado de la cuenta</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="activo" <?php echo ($user['estado'] === 'activo') ? 'selected' : ''; ?>>Activo (puede acceder al sistema)</option>
                    <option value="inactivo" <?php echo ($user['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo (no puede acceder)</option>
                </select>
            </div>
            
            <div class="buttons-container">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save icon"></i> Guardar Cambios
                </button>
                
                <a href="configuracion_usuarios.php" class="btn btn-secondary">
                    <i class="fas fa-times icon"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación en tiempo real del nombre
        const nombreInput = document.getElementById('nombre');
        nombreInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,100}$/.test(value);
            
            if (value === '') {
                this.style.borderColor = '#e0e0e0';
            } else if (isValid) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        });
        
        // Validación en tiempo real del email
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[a-zA-Z0-9._%+-]+@ujgh\.edu\.ve$/.test(value);
            
            if (value === '') {
                this.style.borderColor = '#e0e0e0';
            } else if (isValid) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        });
        
        // Confirmación antes de enviar el formulario
        const form = document.getElementById('editUserForm');
        form.addEventListener('submit', function(e) {
            // Opcional: Mostrar confirmación si hay cambios importantes
            const rolSelect = document.getElementById('rol');
            const estadoSelect = document.getElementById('estado');
            
            // Si se cambia el rol a Administrador o estado a inactivo
            if (rolSelect.value === 'Administrador' || estadoSelect.value === 'inactivo') {
                if (!confirm('¿Está seguro de realizar estos cambios? Cambiar el rol o estado puede afectar los permisos del usuario.')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
        
        // Efecto de cambio visual en selects
        const selects = document.querySelectorAll('.form-select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                if (this.value === 'Administrador') {
                    this.style.borderColor = '#007bff';
                    this.style.boxShadow = '0 0 0 3px rgba(0, 123, 255, 0.2)';
                } else if (this.value === 'inactivo') {
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.2)';
                } else {
                    this.style.borderColor = '#28a745';
                    this.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.2)';
                }
            });
        });
    });
    </script>
</body>
</html>