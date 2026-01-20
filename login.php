<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Postgrado UJGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES Y RESET ===== */
        :root {
            --primary: #005bbb;
            --primary-dark: #004494;
            --primary-light: #3a86ff;
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gradient: linear-gradient(135deg, #005bbb 0%, #3a86ff 100%);
            --gradient-dark: linear-gradient(135deg, #004494 0%, #005bbb 100%);
            --shadow: 0 10px 30px rgba(0, 91, 187, 0.15);
            --shadow-lg: 0 20px 50px rgba(0, 91, 187, 0.25);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e7f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);
            position: relative;
            overflow-x: hidden;
        }

        /* ===== FONDO DECORATIVO ===== */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(58, 134, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(0, 91, 187, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 193, 7, 0.03) 0%, transparent 50%);
            z-index: -1;
        }

        /* ===== CONTENEDOR PRINCIPAL ===== */
        .login-container {
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            min-height: 600px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== LADO IZQUIERDO (FORMULARIO) ===== */
        .form-side {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: white;
        }

        .form-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--gradient);
        }

        /* ===== LOGO Y ENCABEZADO ===== */
        .login-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(0, 91, 187, 0.2);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logo-text h1 {
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .logo-text p {
            font-size: 14px;
            color: var(--secondary);
            font-weight: 400;
        }

        /* ===== FORMULARIO ===== */
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            text-align: center;
        }

        .form-subtitle {
            color: var(--secondary);
            text-align: center;
            margin-bottom: 30px;
            font-size: 15px;
        }

        /* ===== INPUTS ===== */
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 18px;
            transition: var(--transition);
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
            background: white;
            color: var(--dark);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.1);
        }

        .input-group input:focus + i {
            color: var(--primary-light);
        }

        .input-group input::placeholder {
            color: #adb5bd;
        }

        /* ===== OJO CONTRASEÑA ===== */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: rgba(0, 91, 187, 0.05);
        }

        /* ===== ENLACE RECUPERAR CONTRASEÑA ===== */
        /* 
        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        */

        /* ===== BOTÓN ENVIAR ===== */
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 91, 187, 0.3);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* ===== ALERTAS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
            border-left: 4px solid transparent;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert i {
            font-size: 18px;
        }

        /* ===== LADO DERECHO (BIENVENIDA) ===== */
        .welcome-side {
            flex: 1;
            background: var(--gradient);
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .welcome-icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .welcome-text {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 40px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            opacity: 0.9;
        }

        .feature i {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* ===== FOOTER ===== */
        .login-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.8;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }

            .form-side, .welcome-side {
                padding: 40px 30px;
            }

            .welcome-side {
                order: -1;
                padding-top: 50px;
            }

            .welcome-icon {
                font-size: 60px;
            }

            .welcome-title {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .form-side, .welcome-side {
                padding: 30px 20px;
            }

            .logo {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }

            .logo-text h1 {
                font-size: 24px;
            }

            .form-title {
                font-size: 20px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .welcome-icon {
                font-size: 50px;
            }

            .input-group input {
                padding: 16px 20px 16px 50px;
            }
        }

        /* ===== EFECTOS ESPECIALES ===== */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            background: rgba(0, 91, 187, 0.03);
            border-radius: 50%;
            animation: floatAround 15s infinite linear;
        }

        @keyframes floatAround {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(20px, 20px) rotate(90deg); }
            50% { transform: translate(0, 40px) rotate(180deg); }
            75% { transform: translate(-20px, 20px) rotate(270deg); }
            100% { transform: translate(0, 0) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Elementos flotantes decorativos -->
    <div class="floating-elements">
        <div class="floating-element" style="width: 100px; height: 100px; top: 10%; left: 5%;"></div>
        <div class="floating-element" style="width: 150px; height: 150px; top: 60%; right: 10%;"></div>
        <div class="floating-element" style="width: 80px; height: 80px; bottom: 20%; left: 20%;"></div>
    </div>

    <!-- Contenedor principal -->
    <div class="login-container">
        <!-- Lado izquierdo: Formulario -->
        <div class="form-side">
            <div class="login-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Postgrado UJGH</h1>
                        <p>Sistema de Gestión Académica</p>
                    </div>
                </div>
            </div>

            <form class="login-form" method="POST" action="login_process.php">
                <h2 class="form-title">Iniciar Sesión</h2>
                <p class="form-subtitle">Ingresa tus credenciales para continuar</p>

                <!-- Campo Email -->
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>

                <!-- Campo Contraseña -->
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <!-- Enlace recuperar contraseña (COMENTADO) -->
                <!-- 
                <div class="forgot-password">
                    <a href="recuperar.php">
                        <i class="fas fa-key"></i>
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                -->

                <!-- Mensajes del sistema -->
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert <?php echo ($_GET['msg'] === 'login_ok') ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo ($_GET['msg'] === 'login_ok') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php
                            $messages = [
                                "campos_vacios" => "Debe llenar todos los campos.",
                                "no_existe" => "El correo ingresado no está registrado.",
                                "pass_incorrecta" => "Contraseña incorrecta.",
                                "supervisor_inactivo" => "Cuenta inactiva. Contacte a un administrador.",
                                "rol_no_autorizado" => "Rol no autorizado para este acceso.",
                                "login_ok" => "Inicio de sesión exitoso. Redirigiendo..."
                            ];
                            echo $messages[$_GET['msg']] ?? "Error desconocido.";
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Botón de envío -->
                <div class="buttons-container" style="display: flex; gap: 20px; margin-top: 20px;">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                    <a href="index.html" style="text-decoration: none; type="submit" class="submit-btn">
                        <i class="fas fa-home"></i> <!-- Icono más apropiado -->
                        Volver
                    </a>
                </div>
            </form>
        </div>

        <!-- Lado derecho: Bienvenida -->
        <div class="welcome-side">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="fas fa-university"></i>
                </div>
                <h2 class="welcome-title">Bienvenido al Sistema</h2>
                <p class="welcome-text">
                    Accede al panel de control del sistema de gestión académica 
                    de postgrado de la Universidad José Gregorio Hernández.
                </p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Sistema seguro y confiable</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Gestión académica completa</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <span>Control de usuarios y permisos</span>
                    </div>
                </div>

                <div class="login-footer">
                    <p>Sistema de Gestión Académica - Versión 1.0</p>
                    <p>Esta es la versión inicial de la aplicación. Reportar errores a: handryperozo650@ujgh.edu.ve</p>
                    <p>© <?php echo date('Y'); ?> Universidad Dr. José Gregorio Hernández</p>
                    <p>Realizado por Handry Perozo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Mostrar/ocultar contraseña
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Cambiar icono
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.className = 'fas fa-eye-slash';
                this.style.color = '#005bbb';
            } else {
                icon.className = 'fas fa-eye';
                this.style.color = '';
            }
        });

        // Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }, 5000);
            });

            // Efecto de enfoque en inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });

        // Efecto de carga en botón
        const submitBtn = document.querySelector('.submit-btn');
        const form = document.querySelector('.login-form');
        
        form.addEventListener('submit', function(e) {
            const btnText = submitBtn.querySelector('i').nextSibling;
            if (btnText) {
                btnText.textContent = ' Verificando...';
            }
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            
            // Simular carga solo si no hay errores de validación HTML5
            if (form.checkValidity()) {
                setTimeout(() => {
                    if (btnText) {
                        btnText.textContent = ' Iniciar Sesión';
                    }
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }, 2000);
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                if (btnText) {
                    btnText.textContent = ' Iniciar Sesión';
                }
            }
        });
    </script>
</body>
</html>