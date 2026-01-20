<?php
// ---------------------------------------------
// dashboard_admin.php
// ---------------------------------------------
// Este archivo es la página principal a la que se accede
// después de iniciar sesión correctamente.
// Está protegida mediante sesiones: si el usuario intenta entrar
// sin haber iniciado sesión, será redirigido al login.

// Iniciamos la sesión para poder acceder a las variables guardadas
session_start();

// Si no hay una sesión activa (usuario no autenticado), redirigimos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // Redirige si no hay sesión
    exit;
}

// Guardamos los datos del usuario en variables para mostrarlos en el panel
$nombreUsuario = $_SESSION['usuario_nombre'];
$emailUsuario  = $_SESSION['usuario_email'];
$rolUsuario = $_SESSION['usuario_rol'];

// Determinar la clase del badge según el rol
$rolClase = '';
$rolTexto = '';

if (strtolower($rolUsuario) === 'administrador') {
    $rolClase = 'role-admin';
    $rolTexto = 'Administrador';
} elseif (strtolower($rolUsuario) === 'supervisor') {
    $rolClase = 'role-supervisor';
    $rolTexto = 'Supervisor';
} else {
    $rolClase = 'role-supervisor'; // Por defecto
    $rolTexto = htmlspecialchars($rolUsuario);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | Postgrado UJGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES Y RESET ===== */
        :root {
            --primary: #005bbb;
            --primary-dark: #004494;
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #8e44ad;
            --accent-light: #a569bd;
            --border-radius: 12px;
            --box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e7f0 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 91, 187, 0.2);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #ffc107, #28a745, #8e44ad);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title i {
            font-size: 32px;
            color: rgba(255, 255, 255, 0.9);
        }

        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
            font-weight: 400;
        }

        /* ===== BOTÓN ADMINISTRAR USUARIOS ===== */
        .admin-btn {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(142, 68, 173, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .admin-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(142, 68, 173, 0.3);
        }

        .admin-btn:hover::before {
            left: 100%;
        }

        .admin-btn i {
            font-size: 16px;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* ===== WELCOME CARD ===== */
        .welcome-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .welcome-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            box-shadow: 0 6px 15px rgba(0, 91, 187, 0.2);
        }

        .welcome-title {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .welcome-text {
            color: var(--secondary);
            font-size: 16px;
            margin-bottom: 25px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== USER INFO SECTION (CORREGIDO) ===== */
        .user-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin: 25px auto;
            max-width: 550px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 4px 12px rgba(0, 91, 187, 0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 600px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .info-item-full {
                grid-column: span 2;
            }
        }

        .info-item {
            background: white;
            border-radius: 8px;
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .info-item:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 91, 187, 0.1);
        }

        .info-item-full {
            background: white;
            border-radius: 8px;
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .info-item-full:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(142, 68, 173, 0.1);
        }

        .info-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .icon-profile {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .icon-email {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
        }

        .icon-access {
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 4px;
            display: block;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .info-detail {
            font-size: 13px;
            color: var(--secondary);
            font-weight: 400;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .role-admin {
            background: linear-gradient(135deg, #f3e8ff 0%, #e0c3ff 100%);
            color: var(--accent);
            border: 1px solid #e0c3ff;
        }

        .role-supervisor {
            background: linear-gradient(135deg, #e8f4fc 0%, #b3d9ff 100%);
            color: var(--primary);
            border: 1px solid #b3d9ff;
        }

        /* ===== DASHBOARD CARDS ===== */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 91, 187, 0.1);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            transition: var(--transition);
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 91, 187, 0.2);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .card-description {
            color: var(--secondary);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .card-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            width: 100%;
        }

        .card-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 91, 187, 0.2);
        }

        .card-btn i {
            font-size: 16px;
        }

        /* ===== CARD COLORS ===== */
        .card-planificacion {
            border-top: 4px solid var(--success);
        }

        .card-planificacion .card-icon {
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
        }

        .card-planificacion .card-btn {
            background: var(--success);
        }

        .card-planificacion .card-btn:hover {
            background: #1e7e34;
        }

        .card-reportes {
            border-top: 4px solid var(--warning);
        }

        .card-reportes .card-icon {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
        }

        .card-reportes .card-btn {
            background: var(--warning);
            color: var(--dark);
        }

        .card-reportes .card-btn:hover {
            background: #e0a800;
        }

        .card-historial {
            border-top: 4px solid var(--info);
        }

        .card-historial .card-icon {
            background: linear-gradient(135deg, var(--info) 0%, #117a8b 100%);
        }

        .card-historial .card-btn {
            background: var(--info);
        }

        .card-historial .card-btn:hover {
            background: #117a8b;
        }

        .card-profesores {
            border-top: 4px solid var(--danger);
        }

        .card-profesores .card-icon {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
        }

        .card-profesores .card-btn {
            background: var(--danger);
        }

        .card-profesores .card-btn:hover {
            background: #c82333;
        }

        .card-asignaciones {
            border-top: 4px solid var(--accent);
        }

        .card-asignaciones .card-icon {
            background: linear-gradient(135deg, var(--accent) 0%, #7d3c98 100%);
        }

        .card-asignaciones .card-btn {
            background: var(--accent);
        }

        .card-asignaciones .card-btn:hover {
            background: #7d3c98;
        }

        .card-logout {
            border-top: 4px solid #6c757d;
        }

        .card-logout .card-icon {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
        }

        .card-logout .card-btn {
            background: #6c757d;
        }

        .card-logout .card-btn:hover {
            background: #545b62;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .welcome-card {
                padding: 30px;
            }
            
            .header-title {
                font-size: 24px;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .admin-btn {
                order: -1;
                align-self: flex-end;
                margin-bottom: 10px;
            }
            
            .header-title {
                font-size: 22px;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 20px auto;
            }
            
            .welcome-card {
                padding: 25px 20px;
            }
            
            .welcome-title {
                font-size: 24px;
            }
            
            .user-avatar {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .info-item-full {
                grid-column: span 1;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px 0;
            }
            
            .header-title {
                font-size: 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .admin-btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .dashboard-card {
                padding: 25px 20px;
            }
            
            .card-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .card-title {
                font-size: 18px;
            }
            
            .info-item, .info-item-full {
                padding: 15px;
            }
            
            .info-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        /* ===== ANIMACIONES ===== */
        .fade-in {
            animation: fadeIn 0.6s ease;
        }

        .slide-in {
            animation: slideIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: var(--secondary);
            font-size: 14px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            margin-top: 40px;
        }

        .footer p {
            margin: 5px 0;
        }

        .system-info {
            background: rgba(0, 91, 187, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="header-content">
            <a href="configuracion_usuarios.php" class="admin-btn">
                <i class="fas fa-users-cog"></i>
                <span class="btn-text">Administrar Usuarios</span>
            </a>
            
            <div class="header-info">
                <div class="header-title">
                    <i class="fas fa-graduation-cap"></i>
                    Panel de Control - Postgrado UJGH
                </div>
                <div class="header-subtitle">
                    Sistema de Gestión Académica de Postgrado
                </div>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <!-- WELCOME CARD -->
        <div class="welcome-card fade-in">
            <div class="user-avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            
            <h1 class="welcome-title">¡Hola, <?php echo htmlspecialchars($nombreUsuario); ?>!</h1>
            <p class="welcome-text">Has iniciado sesión correctamente en el sistema de gestión académica de postgrado de la UJGH.</p>
            
            <!-- USER INFO SECTION (MEJORADO) -->
            <div class="user-info fade-in" style="animation-delay: 0.2s">
                <div class="info-grid">
                    <!-- PERFIL -->
                    <div class="info-item">
                        <div class="info-icon icon-profile">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Perfil</span>
                            <div class="info-value">Usuario del Sistema</div>
                            <span class="role-badge <?php echo $rolClase; ?>">
                                <?php echo $rolTexto; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- CORREO -->
                    <div class="info-item">
                        <div class="info-icon icon-email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Correo Institucional</span>
                            <div class="info-value"><?php echo htmlspecialchars($emailUsuario); ?></div>
                            <div class="info-detail">Correo UJGH autorizado</div>
                        </div>
                    </div>
                    
                    <!-- ACCESO -->
                    <div class="info-item-full">
                        <div class="info-icon icon-access">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Último Acceso</span>
                            <div class="info-value" id="current-time"><?php echo date('d/m/Y H:i:s'); ?></div>
                            <div class="info-detail">Sistema activo | Sesión iniciada correctamente</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-cards">
            <!-- Planificación Académica -->
            <div class="dashboard-card card-planificacion fade-in" style="animation-delay: 0.1s">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="card-title">Planificación Académica</h3>
                <p class="card-description">Gestiona y organiza las actividades académicas del periodo en curso.</p>
                <a href="planificacion.php" class="card-btn">
                    <i class="fas fa-arrow-right"></i>
                    Acceder a Planificación
                </a>
            </div>

            <!-- Formulario de Reportes -->
            <div class="dashboard-card card-reportes fade-in" style="animation-delay: 0.2s">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="card-title">Llenar Reporte</h3>
                <p class="card-description">Completa y envía reportes académicos y administrativos.</p>
                <a href="formulario.php" class="card-btn">
                    <i class="fas fa-edit"></i>
                    Crear Reporte
                </a>
            </div>

            <!-- Historial de Reportes -->
            <div class="dashboard-card card-historial fade-in" style="animation-delay: 0.3s">
                <div class="card-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="card-title">Historial de Reportes</h3>
                <p class="card-description">Consulta y gestiona el historial completo de reportes enviados.</p>
                <a href="reportes.php" class="card-btn">
                    <i class="fas fa-history"></i>
                    Ver Historial
                </a>
            </div>

            <!-- Gestión de Profesores -->
            <div class="dashboard-card card-profesores fade-in" style="animation-delay: 0.4s">
                <div class="card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="card-title">Gestión de Profesores</h3>
                <p class="card-description">Administra la información y estado del personal docente.</p>
                <a href="profesores.php" class="card-btn">
                    <i class="fas fa-user-tie"></i>
                    Gestionar Profesores
                </a>
            </div>

            <!-- Asignación de Materias -->
            <div class="dashboard-card card-asignaciones fade-in" style="animation-delay: 0.5s">
                <div class="card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="card-title">Asignar Materias</h3>
                <p class="card-description">Asigna materias a profesores con planificación académica.</p>
                <a href="asignar_materias.php" class="card-btn">
                    <i class="fas fa-tasks"></i>
                    Ver Asignaciones
                </a>
            </div>

            <!-- Cerrar Sesión -->
            <div class="dashboard-card card-logout fade-in" style="animation-delay: 0.6s">
                <div class="card-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 class="card-title">Cerrar Sesión</h3>
                <p class="card-description">Finaliza tu sesión actual de manera segura en el sistema.</p>
                <a href="logout.php" class="card-btn">
                    <i class="fas fa-lock"></i>
                    Salir del Sistema
                </a>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer fade-in" style="animation-delay: 0.7s">
            <p>Sistema de Gestión Académica - Postgrado UJGH</p>
            <p>© <?php echo date('Y'); ?> Universidad Dr. José Gregorio Hernández</p>
            <p>Realizado por Handry Perozo</p>
            <div class="system-info">
                <p><i class="fas fa-shield-alt"></i> Sistema seguro | <i class="fas fa-sync-alt"></i> Última actualización: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>

    <script>
        // ===== FUNCIONALIDADES JAVASCRIPT =====
        
        // Efecto hover en tarjetas
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Efecto en botones
        document.querySelectorAll('.card-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Efecto en botón de administración
        const adminBtn = document.querySelector('.admin-btn');
        if (adminBtn) {
            adminBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            adminBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        }

        // Animación de entrada escalonada para tarjetas
        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${0.1 + (index * 0.1)}s`;
        });

        // Actualizar hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const date = now.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            const time = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = `${date} ${time}`;
            }
        }

        // Actualizar cada segundo
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime(); // Llamada inicial

        // Efecto hover en items de información
        document.querySelectorAll('.info-item, .info-item-full').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>