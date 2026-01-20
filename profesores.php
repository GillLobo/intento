<?php
// ---------------------------------------------
// profesores.php
// ---------------------------------------------
// CRUD completo (crear, leer, actualizar, estado activo/inactivo)
// para la tabla "profesores".
// Protegido por sesión, con alertas visuales.
// Con paginación y búsqueda/filtrado.
// ---------------------------------------------
// ---------------------------------------------

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'connection.php';

$dashboard = "dashboard.php"; // por defecto supervisor

if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

// Mensajes del sistema
$mensaje = '';
$tipoMensaje = ''; // 'exito' o 'error'

// --------------------------------------------------
// CREAR PROFESOR
// --------------------------------------------------
if (isset($_POST['agregar'])) {

    $ci     = trim($_POST['ciprof']);
    $nombre = trim($_POST['nomape']);
    $nivel  = trim($_POST['nivelacademico']);
    $correo = trim($_POST['correo']);
    $estado = 'Activo';

    // ==============================
    //   VALIDACIONES
    // ==============================

    // CIPROF: numérico, 6–10 dígitos
    if (!ctype_digit($ci) || strlen($ci) < 6 || strlen($ci) > 10) {
        $mensaje = "⚠️ El CIPROF debe ser numérico y tener entre 6 y 10 dígitos.";
        $tipoMensaje = "error";
    }

    // NOMBRE: solo letras y espacios, mínimo 3 caracteres
    elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,}$/", $nombre)) {
        $mensaje = "⚠️ El Nombre y Apellido debe contener solo letras y al menos 3 caracteres.";
        $tipoMensaje = "error";
    }

    // NIVEL ACADÉMICO: solo letras y espacios, mínimo 3 caracteres
    elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,}$/", $nivel)) {
        $mensaje = "⚠️ El Nivel Académico solo permite letras y mínimo 3 caracteres.";
        $tipoMensaje = "error";
    }

    // CORREO obligatorio y dominio UJGH
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            !preg_match("/@ujgh\.edu\.ve$/", $correo)) {
        $mensaje = "⚠️ El correo debe ser válido y pertenecer al dominio @ujgh.edu.ve.";
        $tipoMensaje = "error";
    }

    else {
        // VALIDAR DUPLICADOS: CI o correo
        $check = $conn->prepare("SELECT * FROM profesores WHERE CIPROF=? OR CORREO=?");
        $check->bind_param("is", $ci, $correo);
        $check->execute();
        $resultado = $check->get_result();

        if ($resultado->num_rows > 0) {
            $mensaje = "⚠️ El CIPROF o el correo ya existen en la base de datos.";
            $tipoMensaje = "error";
        } else {
            // INSERTAR
            $stmt = $conn->prepare("INSERT INTO profesores (CIPROF, NOMAPE, NIVELACADEMICO, CORREO, ESTADO) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $ci, $nombre, $nivel, $correo, $estado);

            if ($stmt->execute()) {
                $mensaje = "✅ Profesor agregado exitosamente.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "❌ Error al agregar profesor.";
                $tipoMensaje = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


// --------------------------------------------------
// CAMBIAR ESTADO (Activo / Inactivo)
// --------------------------------------------------
if (isset($_GET['estado'])) {
    $id = intval($_GET['estado']);
    $estado_actual = $_GET['valor'];

    $nuevo_estado = ($estado_actual === 'Activo') ? 'Inactivo' : 'Activo';

    $stmt = $conn->prepare("UPDATE profesores SET ESTADO = ? WHERE IDPROF = ?");
    $stmt->bind_param("si", $nuevo_estado, $id);
    if ($stmt->execute()) {
        $mensaje = "🔄 Estado actualizado a '$nuevo_estado'.";
        $tipoMensaje = "exito";
    } else {
        $mensaje = "❌ Error al actualizar estado.";
        $tipoMensaje = "error";
    }
    $stmt->close();
}

// --------------------------------------------------
// ACTUALIZAR DATOS DEL PROFESOR
// --------------------------------------------------
if (isset($_POST['editar'])) {
    $id = intval($_POST['idprof']);
    $ci = trim($_POST['ciprof']);
    $nombre = trim($_POST['nomape']);
    $nivel = trim($_POST['nivelacademico']);
    $correo = trim($_POST['correo']);

    $stmt = $conn->prepare("UPDATE profesores SET CIPROF=?, NOMAPE=?, NIVELACADEMICO=?, CORREO=? WHERE IDPROF=?");
    $stmt->bind_param("isssi", $ci, $nombre, $nivel, $correo, $id);
    if ($stmt->execute()) {
        $mensaje = "✏️ Datos del profesor actualizados correctamente.";
        $tipoMensaje = "exito";
    } else {
        $mensaje = "❌ Error al actualizar datos.";
        $tipoMensaje = "error";
    }
    $stmt->close();
}

// --------------------------------------------------
// CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA
// --------------------------------------------------

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$estado_filtro = isset($_GET['estado_filtro']) ? $_GET['estado_filtro'] : '';

// Configuración de paginación
$registros_por_pagina = 10; // Cambia este valor según necesites
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Construir consulta base con filtros
$where_conditions = [];
$params = [];
$types = "";

if (!empty($busqueda)) {
    $where_conditions[] = "(CIPROF LIKE ? OR NOMAPE LIKE ? OR NIVELACADEMICO LIKE ? OR CORREO LIKE ?)";
    $search_param = "%{$busqueda}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($estado_filtro) && in_array($estado_filtro, ['Activo', 'Inactivo'])) {
    $where_conditions[] = "ESTADO = ?";
    $params[] = $estado_filtro;
    $types .= "s";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Consulta para contar total de registros (para paginación)
$count_sql = "SELECT COUNT(*) as total FROM profesores $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_registros = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Calcular total de páginas
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta para obtener los registros de la página actual
$sql = "SELECT * FROM profesores $where_sql ORDER BY NOMAPE ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Añadir límite y offset a los parámetros
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Profesores</title>
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
            --gray: #e9ecef;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* ===== MODAL DE CONFIRMACIÓN PERSONALIZADO ===== */
        .custom-confirm-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .custom-confirm-modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
            transform: scale(0.9);
            animation: modalIn 0.3s ease forwards;
        }

        @keyframes modalIn {
            to { transform: scale(1); }
        }

        .confirm-header {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .confirm-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .confirm-title {
            font-size: 22px;
            font-weight: 600;
        }

        .confirm-body {
            padding: 30px;
            text-align: center;
        }

        .confirm-message {
            font-size: 16px;
            line-height: 1.5;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .professor-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: center;
        }

        .professor-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .professor-details {
            text-align: left;
        }

        .professor-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .professor-ci {
            font-size: 14px;
            color: var(--secondary);
        }

        .confirm-footer {
            padding: 20px 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .confirm-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            min-width: 120px;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .confirm-btn-cancel {
            background: var(--secondary);
            color: white;
        }

        .confirm-btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
        }

        .confirm-btn-activate {
            background: var(--success);
            color: white;
        }

        .confirm-btn-activate:hover {
            background: #219653;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }

        .confirm-btn-deactivate {
            background: var(--danger);
            color: white;
        }

        .confirm-btn-deactivate:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
        }

        /* ===== TUS ESTILOS ORIGINALES (SIN CAMBIOS) ===== */
        .header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 91, 187, 0.2);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #ffc107, #28a745, #17a2b8);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .container {
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: var(--light);
            padding: 20px 25px;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
            border-left: 5px solid;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .alert i {
            font-size: 20px;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: end;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.1);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 91, 187, 0.2);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--gray);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .table thead {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
        }

        .table th {
            padding: 16px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            border: none;
        }

        .table th:first-child {
            border-top-left-radius: 8px;
        }

        .table th:last-child {
            border-top-right-radius: 8px;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--gray);
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .table td {
            padding: 15px;
            color: #495057;
            vertical-align: middle;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            color: var(--secondary);
            font-size: 14px;
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            border: 1px solid var(--gray);
            transition: var(--transition);
        }

        .page-link:hover {
            background: var(--light);
            border-color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .modal {
            background: white;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.4s ease;
            overflow: hidden;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            background: var(--primary);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            background: var(--light);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid var(--gray);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray);
            flex-wrap: wrap;
            gap: 15px;
        }

        @media (max-width: 992px) {
            .container {
                padding: 0 15px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .table th, .table td {
                padding: 12px 10px;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 24px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .pagination-container {
                flex-direction: column;
                align-items: center;
            }
            
            .footer-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 91, 187, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(0, 91, 187, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 91, 187, 0); }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <!-- MODAL DE CONFIRMACIÓN PERSONALIZADO -->
    <div class="custom-confirm-overlay" id="confirmOverlay">
        <div class="custom-confirm-modal">
            <div class="confirm-header">
                <div class="confirm-icon" id="confirmIcon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="confirm-title" id="confirmTitle">Cambiar Estado</div>
            </div>
            <div class="confirm-body">
                <div class="confirm-message" id="confirmMessage">
                    ¿Está seguro de cambiar el estado de este profesor?
                </div>
                <div class="professor-info" id="professorInfo">
                    <div class="professor-avatar" id="professorAvatar">JD</div>
                    <div class="professor-details">
                        <div class="professor-name" id="professorName">Juan Pérez</div>
                        <div class="professor-ci" id="professorCI">C.I. 12345678</div>
                    </div>
                </div>
                <div class="confirm-footer">
                    <button class="confirm-btn confirm-btn-cancel" id="confirmCancelBtn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="confirm-btn" id="confirmActionBtn">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- HEADER -->
    <header class="header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Gestión de Profesores</h1>
        <p>Sistema de administración académica</p>
    </header>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- ALERTAS -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje === 'error' ? 'error' : 'success' ?> fade-in">
                <i class="fas fa-<?= $tipoMensaje === 'error' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- CARD PRINCIPAL -->
        <div class="card">
            <!-- CARD HEADER -->
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list"></i>
                    Lista de Profesores
                </div>
                <a href="create_profesor.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nuevo Profesor
                </a>
            </div>

            <!-- CARD BODY -->
            <div class="card-body">
                <!-- FORMULARIO DE BÚSQUEDA -->
                <div class="search-container">
                    <form method="GET" action="" class="search-form">
                        <div class="form-group">
                            <label class="form-label">Buscar profesor</label>
                            <input type="text" name="busqueda" class="form-input" 
                                   placeholder="CI, nombre, nivel o correo..." 
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select name="estado_filtro" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="Activo" <?= $estado_filtro === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="Inactivo" <?= $estado_filtro === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <?php if (!empty($busqueda) || !empty($estado_filtro)): ?>
                                <a href="profesores.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- INFO DE PAGINACIÓN -->
                <div class="pagination-info">
                    <i class="fas fa-info-circle"></i>
                    Mostrando <?= $result->num_rows ?> de <?= $total_registros ?> profesores
                    <?php if (!empty($busqueda)): ?>
                        - Resultados para: "<?= htmlspecialchars($busqueda) ?>"
                    <?php endif; ?>
                </div>

                <!-- TABLA -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre y Apellido</th>
                                <th>Nivel Académico</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="fade-in">
                                    <td><strong><?= $row['CIPROF'] ?></strong></td>
                                    <td><?= htmlspecialchars($row['NOMAPE']) ?></td>
                                    <td><?= htmlspecialchars($row['NIVELACADEMICO']) ?></td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($row['CORREO']) ?>" class="text-primary">
                                            <?= htmlspecialchars($row['CORREO']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status status-<?= strtolower($row['ESTADO']) ?>">
                                            <?= htmlspecialchars($row['ESTADO']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                    onclick="abrirModalEditar(
                                                        '<?= $row['IDPROF'] ?>',
                                                        '<?= $row['CIPROF'] ?>',
                                                        '<?= htmlspecialchars($row['NOMAPE']) ?>',
                                                        '<?= htmlspecialchars($row['NIVELACADEMICO']) ?>',
                                                        '<?= htmlspecialchars($row['CORREO']) ?>'
                                                    )">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button type="button" class="btn <?= $row['ESTADO'] === 'Activo' ? 'btn-danger' : 'btn-success' ?> btn-sm btn-toggle-status"
                                                    data-id="<?= $row['IDPROF'] ?>"
                                                    data-ci="<?= $row['CIPROF'] ?>"
                                                    data-nombre="<?= htmlspecialchars($row['NOMAPE']) ?>"
                                                    data-estado="<?= $row['ESTADO'] ?>"
                                                    data-url="?estado=<?= $row['IDPROF'] ?>&valor=<?= $row['ESTADO'] ?>&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>&pagina=<?= $pagina_actual ?>">
                                                <i class="fas fa-<?= $row['ESTADO'] === 'Activo' ? 'toggle-off' : 'toggle-on' ?>"></i>
                                                <?= $row['ESTADO'] === 'Activo' ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h3>No se encontraron profesores</h3>
                                            <p>No hay profesores que coincidan con los criterios de búsqueda</p>
                                            <a href="profesores.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-redo"></i> Ver todos
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Página <?= $pagina_actual ?> de <?= $total_paginas ?>
                    </div>
                    <div class="pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>" class="page-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?pagina=<?= $pagina_actual - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>" class="page-link">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="page-link disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>

                        <?php
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="page-link active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>" class="page-link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina_actual + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>" class="page-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?pagina=<?= $total_paginas ?>&busqueda=<?= urlencode($busqueda) ?>&estado_filtro=<?= urlencode($estado_filtro) ?>" class="page-link">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="page-link disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FOOTER ACCIONES -->
                <div class="footer-actions">
                    <div></div> <!-- Espaciador -->
                    <a href="<?= $dashboard ?>" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-user-edit"></i> Editar Profesor
                </div>
                <button class="modal-close">&times;</button>
            </div>
            <form id="formEditar" method="POST" action="">
                <input type="hidden" name="idprof" id="edit_idprof">
                
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Cédula (CIPROF):</label>
                        <input type="number" name="ciprof" id="edit_ciprof" class="form-input" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Nombre y Apellido:</label>
                        <input type="text" name="nomape" id="edit_nomape" class="form-input" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Nivel Académico:</label>
                        <input type="text" name="nivelacademico" id="edit_nivelacademico" class="form-input" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Correo:</label>
                        <input type="email" name="correo" id="edit_correo" class="form-input" required>
                        <small class="text-muted">Debe terminar en @ujgh.edu.ve</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelarEditar">Cancelar</button>
                    <button type="submit" name="editar" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== SISTEMA DE CONFIRMACIÓN PERSONALIZADO =====
        const confirmOverlay = document.getElementById('confirmOverlay');
        let actionUrl = '';
        
        // Función para obtener iniciales del nombre
        function getInitials(name) {
            return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
        }
        
        // Configurar botones de activar/desactivar
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const ci = this.getAttribute('data-ci');
                const nombre = this.getAttribute('data-nombre');
                const estado = this.getAttribute('data-estado');
                const url = this.getAttribute('data-url');
                
                // Guardar URL para la acción
                actionUrl = url;
                
                // Configurar modal según el estado
                const confirmTitle = document.getElementById('confirmTitle');
                const confirmMessage = document.getElementById('confirmMessage');
                const confirmIcon = document.getElementById('confirmIcon');
                const confirmActionBtn = document.getElementById('confirmActionBtn');
                const professorAvatar = document.getElementById('professorAvatar');
                const professorName = document.getElementById('professorName');
                const professorCI = document.getElementById('professorCI');
                
                // Configurar datos del profesor
                professorAvatar.textContent = getInitials(nombre);
                professorName.textContent = nombre;
                professorCI.textContent = `C.I. ${ci}`;
                
                if (estado === 'Activo') {
                    confirmTitle.textContent = 'Desactivar Profesor';
                    confirmMessage.textContent = `¿Está seguro de desactivar al profesor "${nombre}"?\n\nEl profesor no podrá ser asignado a nuevas materias hasta que sea reactivado.`;
                    confirmIcon.innerHTML = '<i class="fas fa-toggle-off"></i>';
                    confirmActionBtn.className = 'confirm-btn confirm-btn-deactivate';
                    confirmActionBtn.innerHTML = '<i class="fas fa-toggle-off"></i> Desactivar';
                } else {
                    confirmTitle.textContent = 'Activar Profesor';
                    confirmMessage.textContent = `¿Está seguro de activar al profesor "${nombre}"?\n\nEl profesor podrá ser asignado a nuevas materias.`;
                    confirmIcon.innerHTML = '<i class="fas fa-toggle-on"></i>';
                    confirmActionBtn.className = 'confirm-btn confirm-btn-activate';
                    confirmActionBtn.innerHTML = '<i class="fas fa-toggle-on"></i> Activar';
                }
                
                // Mostrar modal
                confirmOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Configurar botones del modal de confirmación
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        const confirmActionBtn = document.getElementById('confirmActionBtn');
        
        confirmCancelBtn.addEventListener('click', () => {
            confirmOverlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        confirmActionBtn.addEventListener('click', () => {
            // Redirigir a la URL de acción
            window.location.href = actionUrl;
        });
        
        // Cerrar modal al hacer clic fuera
        confirmOverlay.addEventListener('click', (e) => {
            if (e.target === confirmOverlay) {
                confirmOverlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && confirmOverlay.style.display === 'flex') {
                confirmOverlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // ===== FUNCIONALIDAD MODAL EDITAR =====
        const modal = document.getElementById('modalEditar');
        const closeBtns = document.querySelectorAll('.modal-close, #cancelarEditar');

        function abrirModalEditar(id, ci, nombre, nivel, correo) {
            // Llenar formulario
            document.getElementById('edit_idprof').value = id;
            document.getElementById('edit_ciprof').value = ci;
            document.getElementById('edit_nomape').value = nombre;
            document.getElementById('edit_nivelacademico').value = nivel;
            document.getElementById('edit_correo').value = correo;
            
            // Mostrar modal con animación
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Cerrar modal
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });

        // Cerrar al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // ===== MEJORAS VISUALES DINÁMICAS =====
        // Resaltar fila al pasar el mouse
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Efecto en botones
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (!this.classList.contains('disabled')) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto-focus en búsqueda al cargar
        window.addEventListener('load', () => {
            const searchInput = document.querySelector('input[name="busqueda"]');
            if (searchInput && !searchInput.value) {
                setTimeout(() => searchInput.focus(), 300);
            }
        });

        // ===== VALIDACIÓN EN TIEMPO REAL DEL CORREO =====
        const correoInput = document.getElementById('edit_correo');
        if (correoInput) {
            correoInput.addEventListener('blur', function() {
                const email = this.value;
                if (email && !email.endsWith('@ujgh.edu.ve')) {
                    this.style.borderColor = 'var(--danger)';
                    this.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
        }

        // ===== ANIMACIÓN PARA NUEVOS ELEMENTOS =====
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplicar a las filas de la tabla
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(row);
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>