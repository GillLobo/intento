<?php
include 'connection.php';

$mensaje = "";
$clase_mensaje = "error";

// Inicializar variables para mantener datos en el formulario
$ciprof = $nomape = $nivel = $correo = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Sanitizar entrada y mantener datos
    $ciprof = trim($_POST['ciprof'] ?? '');
    $nomape = trim($_POST['nomape'] ?? '');
    $nivel  = trim($_POST['nivel'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    // ==================================================
    // VALIDACIONES BACKEND
    // ==================================================

    // CIPROF → numérico, 6 a 10 dígitos
    if (!preg_match("/^[0-9]{6,10}$/", $ciprof)) {
        $mensaje = "❌ El CIPROF debe tener entre 6 y 10 números.";
    }

    // Nombre y Apellido → solo letras, tildes y espacios
    elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,60}$/", $nomape)) {
        $mensaje = "❌ El Nombre y Apellido debe contener solo letras y mínimo 3 caracteres.";
    }

    // Nivel Académico
    elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,60}$/", $nivel)) {
        $mensaje = "❌ El Nivel Académico debe ser válido (solo letras, mínimo 3 caracteres).";
    }

    // Correo UJGH → validar formato + dominio
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)
            || !preg_match("/@ujgh\.edu\.ve$/i", $correo)) {
        $mensaje = "❌ Debe ser un correo institucional válido (@ujgh.edu.ve).";
    }

    else {
        // Comprobar si EXISTE CONFLICTO
        $stmt_check = $conn->prepare("SELECT CIPROF, CORREO FROM profesores WHERE CIPROF=? OR CORREO=? LIMIT 1");
        $stmt_check->bind_param("is", $ciprof, $correo);
        $stmt_check->execute();
        $existe = $stmt_check->get_result();

        if ($existe->num_rows > 0) {
            $mensaje = "⚠️ El CIPROF o el correo ya existen en la base de datos.";
        } else {

            // INSERTAR
            $stmt = $conn->prepare("
                INSERT INTO profesores (CIPROF, NOMAPE, NIVELACADEMICO, CORREO, ESTADO)
                VALUES (?, ?, ?, ?, 'Activo')
            ");
            $stmt->bind_param("isss", $ciprof, $nomape, $nivel, $correo);

            if ($stmt->execute()) {
                $mensaje = "✔ Profesor agregado correctamente.";
                $clase_mensaje = "exito";
                // Limpiar campos si fue exitoso
                $ciprof = $nomape = $nivel = $correo = "";
            } else {
                $mensaje = "❌ Error al agregar profesor: " . $conn->error;
            }

            $stmt->close();
        }

        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agregar Profesor</title>
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
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Contenedor principal */
.container-profesor {
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    padding: 35px 30px;
    transition: all 0.3s ease;
}

/* Título */
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

/* Grupos de formulario */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #34495e;
    font-weight: 500;
    font-size: 0.95rem;
}

/* Campos de entrada */
input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s;
    background-color: #fafafa;
}

input:focus {
    outline: none;
    border-color: #007bff;
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

input:invalid:not(:focus):not(:placeholder-shown) {
    border-color: #e74c3c;
}

/* Contenedor de botones - DISEÑO MEJORADO */
.botones-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 30px;
}

/* Botones principales - más compactos */
.btn {
    padding: 12px 16px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    min-height: 48px;
}

.btn-guardar {
    background: linear-gradient(135deg, #007bff, #0056cc);
    color: white;
    grid-column: span 2;
}

.btn-guardar:hover {
    background: linear-gradient(135deg, #0056cc, #004099);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 86, 204, 0.3);
}

.btn-limpiar {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-limpiar:hover {
    background: linear-gradient(135deg, #5a6268, #484e53);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(90, 98, 104, 0.3);
}

/* Botón Volver - ahora estilo botón normal */
.btn-volver {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    text-decoration: none;
    text-align: center;
}

.btn-volver:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(19, 132, 150, 0.3);
}

/* Mensajes */
.mensaje {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-size: 0.95rem;
    line-height: 1.5;
    animation: fadeIn 0.5s ease;
}

.mensaje.error {
    background: linear-gradient(135deg, #ffeaea, #ffcccc);
    border-left: 5px solid #e74c3c;
    color: #c0392b;
}

.mensaje.exito {
    background: linear-gradient(135deg, #d5ffd5, #a3ffa3);
    border-left: 5px solid #27ae60;
    color: #1e8449;
}

/* Animación */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .container-profesor {
        padding: 25px 20px;
        margin: 15px;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .botones-container {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .btn-guardar {
        grid-column: span 1;
    }
    
    .btn {
        padding: 14px 16px;
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }
    
    .container-profesor {
        padding: 20px 15px;
        border-radius: 12px;
    }
    
    input {
        padding: 12px 14px;
        font-size: 15px;
    }
    
    .btn {
        padding: 12px 14px;
        font-size: 14px;
        min-height: 44px;
    }
}

/* Iconos para botones */
.icono {
    font-size: 16px;
}
</style>
</head>
<body>

<div class="container-profesor">

    <?php if($mensaje): ?>
        <p class="mensaje <?= $clase_mensaje ?>"><?= $mensaje ?></p>
    <?php endif; ?>

    <h2>Agregar Profesor</h2>

    <form method="POST" id="formProfesor">

        <div class="form-group">
            <label for="ciprof">Cédula de Identidad (CIPROF)</label>
            <input type="text" 
                   id="ciprof"
                   name="ciprof" 
                   placeholder="Ej: 12345678" 
                   required
                   value="<?= htmlspecialchars($ciprof) ?>"
                   maxlength="10"
                   onkeypress="return event.key >= '0' && event.key <= '9';"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
        </div>

        <div class="form-group">
            <label for="nomape">Nombre y Apellido</label>
            <input type="text" 
                   id="nomape"
                   name="nomape" 
                   placeholder="Ej: María González" 
                   required
                   value="<?= htmlspecialchars($nomape) ?>"
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{3,60}"
                   maxlength="60"
                   title="Solo letras, mínimo 3 caracteres">
        </div>

        <div class="form-group">
            <label for="nivel">Nivel Académico</label>
            <input type="text" 
                   id="nivel"
                   name="nivel" 
                   placeholder="Ej: Doctor, Magister" 
                   required
                   value="<?= htmlspecialchars($nivel) ?>"
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{3,60}"
                   maxlength="60"
                   title="Solo letras, mínimo 3 caracteres">
        </div>

        <div class="form-group">
            <label for="correo">Correo Institucional</label>
            <input type="email" 
                   id="correo"
                   name="correo" 
                   placeholder="usuario@ujgh.edu.ve" 
                   required
                   value="<?= htmlspecialchars($correo) ?>"
                   pattern="^[a-zA-Z0-9._%+-]+@ujgh\.edu\.ve$"
                   title="Debe ser un correo institucional @ujgh.edu.ve">
        </div>

        <div class="botones-container">
            <button type="submit" class="btn btn-guardar">
                <span class="icono">💾</span> Guardar Profesor
            </button>
            
            <button type="button" class="btn btn-limpiar" onclick="limpiarFormulario()">
                <span class="icono">🗑️</span> Limpiar
            </button>
            
            <a href="profesores.php" class="btn btn-volver">
                <span class="icono">↶</span> Volver
            </a>
        </div>

    </form>

</div>

<script>
// Función para limpiar el formulario
function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas limpiar todos los campos?')) {
        document.getElementById('formProfesor').reset();
        // También podemos enfocar el primer campo
        document.getElementById('ciprof').focus();
    }
}

// Validación en tiempo real para solo números en CIPROF
document.getElementById('ciprof').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Validación para solo letras en nombre y nivel (permitiendo tildes y espacios)
document.getElementById('nomape').addEventListener('input', function(e) {
    // Permitir letras, espacios y tildes
    this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
});

document.getElementById('nivel').addEventListener('input', function(e) {
    // Permitir letras, espacios y tildes
    this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
});

// Prevenir envío con Enter fuera del formulario
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
        e.preventDefault();
    }
});

// Opcional: Función para validar email institucional en tiempo real
document.getElementById('correo').addEventListener('blur', function(e) {
    const email = this.value;
    if (email && !email.endsWith('@ujgh.edu.ve')) {
        this.setCustomValidity('Debe ser un correo @ujgh.edu.ve');
        this.reportValidity();
    } else {
        this.setCustomValidity('');
    }
});
</script>

</body>
</html>