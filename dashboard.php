<?php
// ---------------------------------------------
// dashboard.php - VERSIÓN SUPERVISORES
// ---------------------------------------------
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$nombreUsuario = $_SESSION['usuario_nombre'];
$emailUsuario  = $_SESSION['usuario_email'];
$rolUsuario = $_SESSION['usuario_rol'];

// Determinar clase y texto según rol
$rolClase = '';
$rolTexto = '';

if (strtolower($rolUsuario) === 'administrador') {
    $rolClase = 'role-admin';
    $rolTexto = 'Administrador';
} elseif (strtolower($rolUsuario) === 'supervisor') {
    $rolClase = 'role-supervisor';
    $rolTexto = 'Supervisor';
} else {
    $rolClase = 'role-user';
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
        /* ===== VARIABLES Y RESET (MANTENIENDO CONSISTENCIA) ===== */
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
            --supervisor-primary: #1e90ff;
            --supervisor-secondary: #00bfff;
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

        /* ===== HEADER (IGUAL AL ADMIN PERO CON DETALLE SUPERVISOR) ===== */
        .header {
            background: linear-gradient(135deg, var(--supervisor-primary) 0%, var(--supervisor-secondary) 100%);
            color: white;
            padding: 25px 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(30, 144, 255, 0.2);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #ffc107, #28a745, #1e90ff);
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

        /* ===== BOTÓN ADMINISTRAR USUARIOS (OCULTO PARA SUPERVISOR) ===== */
        /* .admin-btn { display: none; } */

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
            border-left: 5px solid var(--supervisor-primary);
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
            background: linear-gradient(to right, var(--supervisor-primary), var(--supervisor-secondary));
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--supervisor-primary) 0%, var(--supervisor-secondary) 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            box-shadow: 0 6px 15px rgba(30, 144, 255, 0.2);
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

        /* ===== USER INFO SECTION ===== */
        .user-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin: 25px auto;
            max-width: 550px;
            border-left: 4px solid var(--supervisor-primary);
            box-shadow: 0 4px 12px rgba(30, 144, 255, 0.05);
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
            border-color: var(--supervisor-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(30, 144, 255, 0.1);
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
            border-color: var(--supervisor-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.1);
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
            background: linear-gradient(135deg, var(--supervisor-primary) 0%, var(--supervisor-secondary) 100%);
        }

        .icon-email {
            background: linear-gradient(135deg, #8e44ad 0%, #a569bd 100%);
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
            color: var(--supervisor-primary);
            border: 1px solid #b3d9ff;
        }

        .role-user {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            color: var(--secondary);
            border: 1px solid #ced4da;
        }

        /* ===== DASHBOARD CARDS (MANTENIENDO ESTRUCTURA) ===== */
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
            border-color: rgba(30, 144, 255, 0.1);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--supervisor-primary) 0%, var(--supervisor-secondary) 100%);
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
            box-shadow: 0 8px 20px rgba(30, 144, 255, 0.2);
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
            background: var(--supervisor-primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            width: 100%;
        }

        .card-btn:hover {
            background: var(--supervisor-secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(30, 144, 255, 0.2);
        }

        .card-btn i {
            font-size: 16px;
        }

        /* ===== CARD COLORS (AJUSTADOS PARA SUPERVISOR) ===== */
        .card-planificacion {
            border-top: 4px solid var(--supervisor-primary);
        }

        .card-planificacion .card-icon {
            background: linear-gradient(135deg, var(--supervisor-primary) 0%, #1c7ed6 100%);
        }

        .card-planificacion .card-btn {
            background: var(--supervisor-primary);
        }

        .card-planificacion .card-btn:hover {
            background: #1c7ed6;
        }

        .card-reportes {
            border-top: 4px solid var(--success);
        }

        .card-reportes .card-icon {
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
        }

        .card-reportes .card-btn {
            background: var(--success);
        }

        .card-reportes .card-btn:hover {
            background: #1e7e34;
        }

        .card-historial {
            border-top: 4px solid var(--accent);
        }

        .card-historial .card-icon {
            background: linear-gradient(135deg, var(--accent) 0%, #7d3c98 100%);
        }

        .card-historial .card-btn {
            background: var(--accent);
        }

        .card-historial .card-btn:hover {
            background: #7d3c98;
        }

        .card-profesores {
            border-top: 4px solid var(--warning);
        }

        .card-profesores .card-icon {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
        }

        .card-profesores .card-btn {
            background: var(--warning);
            color: var(--dark);
        }

        .card-profesores .card-btn:hover {
            background: #e0a800;
        }

        .card-asignaciones {
            border-top: 4px solid var(--info);
        }

        .card-asignaciones .card-icon {
            background: linear-gradient(135deg, var(--info) 0%, #117a8b 100%);
        }

        .card-asignaciones .card-btn {
            background: var(--info);
        }

        .card-asignaciones .card-btn:hover {
            background: #117a8b;
        }

        .card-logout {
            border-top: 4px solid var(--secondary);
        }

        .card-logout .card-icon {
            background: linear-gradient(135deg, var(--secondary) 0%, #545b62 100%);
        }

        .card-logout .card-btn {
            background: var(--secondary);
        }

        .card-logout .card-btn:hover {
            background: #545b62;
        }

        /* ===== RESPONSIVE DESIGN (IGUAL QUE ADMIN) ===== */
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
            background: rgba(30, 144, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 13px;
            color: var(--supervisor-primary);
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="header-content">
            <!-- Botón de administración oculto para supervisores -->
            <!-- <a href="configuracion_usuarios.php" class="admin-btn">
                <i class="fas fa-users-cog"></i>
                <span class="btn-text">Administrar Usuarios</span>
            </a> -->
            
            <div class="header-info">
                <div class="header-title">
                    <i class="fas fa-eye"></i>
                    Panel de Supervisión - Postgrado UJGH
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
                <i class="fas fa-user-shield"></i>
            </div>
            
            <h1 class="welcome-title">¡Bienvenido, Supervisor <?php echo htmlspecialchars($nombreUsuario); ?>!</h1>
            <p class="welcome-text">Has iniciado sesión en el sistema de monitoreo y supervisión académica del postgrado UJGH.</p>
            
            <!-- USER INFO SECTION -->
            <div class="user-info fade-in" style="animation-delay: 0.2s">
                <div class="info-grid">
                    <!-- PERFIL -->
                    <div class="info-item">
                        <div class="info-icon icon-profile">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Perfil</span>
                            <div class="info-value">Supervisor del Sistema</div>
                            <span class="role-badge <?php echo $rolClase; ?>">
                                <i class="fas fa-eye"></i> <?php echo $rolTexto; ?>
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
                            <div class="info-detail">Acceso autorizado para supervisión</div>
                        </div>
                    </div>
                    
                    <!-- ACCESO -->
                    <div class="info-item-full">
                        <div class="info-icon icon-access">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Sesión de Supervisión Activa</span>
                            <div class="info-value" id="current-time"><?php echo date('d/m/Y H:i:s'); ?></div>
                            <div class="info-detail">Monitoreo activo | Sistema operativo</div>
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
                <p class="card-description">Revisa y supervisa las actividades académicas del periodo en curso.</p>
                <a href="planificacion.php" class="card-btn">
                    <i class="fas fa-search"></i>
                    Supervisar Planificación
                </a>
            </div>

            <!-- Formulario de Reportes -->
            <div class="dashboard-card card-reportes fade-in" style="animation-delay: 0.2s">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="card-title">Generar Reporte</h3>
                <p class="card-description">Completa reportes de supervisión académica y administrativa.</p>
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
                <p class="card-description">Consulta el historial de reportes de supervisión enviados.</p>
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
                <h3 class="card-title">Supervisar Profesores</h3>
                <p class="card-description">Monitorea la información y carga académica del personal docente.</p>
                <a href="profesores.php" class="card-btn">
                    <i class="fas fa-user-tie"></i>
                    Ver Profesores
                </a>
            </div>

            <!-- Asignación de Materias -->
            <div class="dashboard-card card-asignaciones fade-in" style="animation-delay: 0.5s">
                <div class="card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="card-title">Asignar Materias</h3>
                <p class="card-description">Supervisa la asignación de materias a profesores.</p>
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
                <p class="card-description">Finaliza tu sesión de supervisión de manera segura.</p>
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
                <p><i class="fas fa-shield-alt"></i> Modo Supervisión Activo | <i class="fas fa-user-shield"></i> Supervisor: <?php echo htmlspecialchars($nombreUsuario); ?></p>
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