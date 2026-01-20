<?php
// configuracion_usuarios.php
session_start();
include 'connection.php';

// Permitir solo ADMINISTRADOR
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
    header("Location: login.php?msg=no_autorizado");
    exit;
}

// ============================================
// CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA
// ============================================

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_rol = isset($_GET['filtro_rol']) ? $_GET['filtro_rol'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Construir consulta base con filtros
$where_conditions = [];
$params = [];
$types = "";

if (!empty($busqueda)) {
    $where_conditions[] = "(nombre LIKE ? OR email LIKE ?)";
    $search_param = "%{$busqueda}%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if (!empty($filtro_rol)) {
    $where_conditions[] = "rol = ?";
    $params[] = $filtro_rol;
    $types .= "s";
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Consulta para contar total de registros (para paginación)
$count_sql = "SELECT COUNT(*) as total FROM usuarios $where_sql";
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
$sql = "SELECT id, nombre, email, rol, estado, fecha_registro 
        FROM usuarios 
        $where_sql 
        ORDER BY id DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Añadir límite y offset a los parámetros
$params_limit = $params;
$params_limit[] = $registros_por_pagina;
$params_limit[] = $offset;
$types_limit = $types . "ii";

if (!empty($params)) {
    $stmt->bind_param($types_limit, ...$params_limit);
} else {
    $stmt->bind_param("ii", $registros_por_pagina, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

// Mensajes opcionales
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES Y RESET ===== */
        :root {
            --primary: #8e44ad;
            --primary-dark: #7d3c98;
            --secondary: #6c757d;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #ecf0f1;
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
            background: linear-gradient(135deg, #f8f0ff 0%, #e6f2ff 100%);
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        /* ===== CUSTOM ALERT MODAL ===== */
        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .custom-alert-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .custom-alert {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            overflow: hidden;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .custom-alert.active {
            transform: scale(1);
            opacity: 1;
        }

        .custom-alert-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .custom-alert-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .custom-alert-title {
            font-size: 22px;
            font-weight: 600;
        }

        .custom-alert-body {
            padding: 30px;
            font-size: 16px;
            line-height: 1.5;
            color: var(--dark);
            text-align: center;
        }

        .custom-alert-footer {
            padding: 20px 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .custom-alert-btn {
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

        .custom-alert-btn-confirm {
            background: var(--danger);
            color: white;
        }

        .custom-alert-btn-confirm:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
        }

        .custom-alert-btn-cancel {
            background: var(--secondary);
            color: white;
        }

        .custom-alert-btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
        }

        .custom-alert-btn-success {
            background: var(--success);
            color: white;
        }

        .custom-alert-btn-success:hover {
            background: #219653;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }

        /* ===== TOAST NOTIFICATIONS ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
        }

        .toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: currentColor;
            opacity: 0.3;
        }

        .toast-success {
            border-left-color: var(--success);
            color: var(--success);
        }

        .toast-info {
            border-left-color: var(--info);
            color: var(--info);
        }

        .toast-warning {
            border-left-color: var(--warning);
            color: var(--warning);
        }

        .toast-error {
            border-left-color: var(--danger);
            color: var(--danger);
        }

        .toast-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .toast-message {
            font-size: 14px;
            color: var(--secondary);
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .toast-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--dark);
        }

        /* ===== CONFIRM ACTION CARD ===== */
        .confirm-action-card {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 90%;
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .confirm-action-card.active {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        .confirm-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 16px 16px 0 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .confirm-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .confirm-title {
            font-size: 24px;
            font-weight: 600;
        }

        .confirm-body {
            padding: 30px;
            text-align: center;
        }

        .confirm-message {
            font-size: 17px;
            line-height: 1.6;
            color: var(--dark);
            margin-bottom: 30px;
        }

        .confirm-user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .user-avatar-mini {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .user-details {
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }

        .user-role {
            font-size: 14px;
            color: var(--secondary);
        }

        .confirm-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .confirm-btn {
            min-width: 140px;
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 15px;
        }

        .confirm-btn-danger {
            background: linear-gradient(135deg, var(--danger), #c0392b);
            color: white;
        }

        .confirm-btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
        }

        .confirm-btn-success {
            background: linear-gradient(135deg, var(--success), #219653);
            color: white;
        }

        .confirm-btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .confirm-btn-secondary {
            background: linear-gradient(135deg, var(--secondary), #5a6268);
            color: white;
        }

        .confirm-btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
        }

        /* ===== CONTINÚA EL CSS ANTERIOR SIN CAMBIOS ===== */
        .header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px 0;
            text-align: center;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.2);
            margin-bottom: 30px;
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
            background: linear-gradient(to right, #f39c12, #27ae60, #3498db);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .card-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body {
            padding: 30px;
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
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-input, .form-select {
            padding: 14px 16px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background: white;
            width: 100%;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }

        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(142, 68, 173, 0.2);
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
            background: #219653;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: #212529;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 10px 18px;
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--gray);
            margin-top: 20px;
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
            padding: 18px 16px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            border: none;
            white-space: nowrap;
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
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .table td {
            padding: 16px;
            color: #495057;
            vertical-align: middle;
            font-size: 14.5px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background: #f3e8ff;
            color: var(--primary);
            border: 1px solid #e0c3ff;
        }

        .badge-supervisor {
            background: #e8f4fc;
            color: var(--info);
            border: 1px solid #b3d9ff;
        }

        .badge-user {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }

        .badge-activo {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-inactivo {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 0;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid var(--gray);
            margin-top: 20px;
        }

        .pagination-info {
            color: var(--secondary);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            border-radius: 8px;
            background: white;
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            border: 1px solid var(--gray);
            transition: var(--transition);
            padding: 0 5px;
        }

        .page-link:hover {
            background: var(--light);
            border-color: var(--primary);
            transform: translateY(-1px);
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

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 70px;
            margin-bottom: 25px;
            opacity: 0.5;
            color: var(--primary);
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .empty-state p {
            max-width: 500px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }

        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 1px solid var(--gray);
            flex-wrap: wrap;
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .container {
                padding: 0 15px;
            }
            
            .card-body {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 26px;
            }
            
            .header p {
                font-size: 14px;
                padding: 0 15px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            
            .card-title {
                font-size: 20px;
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
                justify-content: center;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.6s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .email-cell {
            font-size: 13.5px;
            color: #6c757d;
            word-break: break-all;
        }

        .date-cell {
            font-size: 13px;
            color: #6c757d;
            white-space: nowrap;
        }

        .user-cell {
            font-weight: 600;
            color: var(--dark);
        }

        .icon-user {
            color: var(--primary);
            margin-right: 8px;
        }

        .icon-email {
            color: var(--secondary);
            margin-right: 8px;
        }

        .icon-date {
            color: var(--info);
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- CONTENEDOR DE ALERTAS PERSONALIZADAS -->
    <div class="custom-alert-overlay" id="customAlertOverlay">
        <div class="custom-alert" id="customAlert">
            <div class="custom-alert-header">
                <div class="custom-alert-icon" id="alertIcon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="custom-alert-title" id="alertTitle">Confirmar acción</div>
            </div>
            <div class="custom-alert-body" id="alertMessage">
                ¿Está seguro de realizar esta acción?
            </div>
            <div class="custom-alert-footer">
                <button class="custom-alert-btn custom-alert-btn-cancel" id="alertCancelBtn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="custom-alert-btn custom-alert-btn-confirm" id="alertConfirmBtn">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR DE CONFIRMACIÓN AVANZADA -->
    <div class="confirm-action-card" id="confirmCard">
        <div class="confirm-header">
            <div class="confirm-icon" id="confirmIcon">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="confirm-title" id="confirmTitle">Cambiar Estado de Usuario</div>
        </div>
        <div class="confirm-body">
            <div class="confirm-message" id="confirmMessage">
                ¿Está seguro de cambiar el estado de este usuario?
            </div>
            <div class="confirm-user-info" id="userInfo">
                <div class="user-avatar-mini" id="userAvatar">JD</div>
                <div class="user-details">
                    <div class="user-name" id="userName">Juan Pérez</div>
                    <div class="user-role" id="userRole">Supervisor</div>
                </div>
            </div>
            <div class="confirm-actions">
                <button class="confirm-btn confirm-btn-secondary" id="confirmCancelBtn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="confirm-btn" id="confirmActionBtn">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR DE NOTIFICACIONES TOAST -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- HEADER -->
    <header class="header slide-in">
        <h1><i class="fas fa-users-cog"></i> Administración de Usuarios</h1>
        <p>Gestión completa del sistema de usuarios - Solo acceso administrativo</p>
    </header>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- CARD PRINCIPAL -->
        <div class="card fade-in">
            <!-- CARD HEADER -->
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-user-shield"></i>
                    Panel de Administración
                </div>
                <div class="action-buttons">
                    <a href="crear_usuario.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </a>
                </div>
            </div>

            <!-- CARD BODY -->
            <div class="card-body">
                <!-- ALERTAS PHP -->
                <?php if ($msg === 'creado'): ?>
                    <div class="alert alert-success fade-in">
                        <i class="fas fa-check-circle"></i>
                        <div>Usuario creado correctamente.</div>
                    </div>
                <?php elseif ($msg === 'editado'): ?>
                    <div class="alert alert-success fade-in">
                        <i class="fas fa-check-circle"></i>
                        <div>Usuario actualizado correctamente.</div>
                    </div>
                <?php elseif ($msg === 'toggle'): ?>
                    <div class="alert alert-success fade-in">
                        <i class="fas fa-sync-alt"></i>
                        <div>Estado del usuario actualizado.</div>
                    </div>
                <?php elseif ($msg === 'eliminado'): ?>
                    <div class="alert alert-success fade-in">
                        <i class="fas fa-trash-restore"></i>
                        <div>Usuario eliminado correctamente.</div>
                    </div>
                <?php elseif ($msg === 'error'): ?>
                    <div class="alert alert-error fade-in">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>Ocurrió un error en la operación.</div>
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO DE BÚSQUEDA -->
                <div class="search-container">
                    <form method="GET" action="" class="search-form">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-search"></i> Buscar
                            </label>
                            <input type="text" name="busqueda" class="form-input" 
                                   placeholder="Nombre o email..." 
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tag"></i> Rol
                            </label>
                            <select name="filtro_rol" class="form-select">
                                <option value="">Todos los roles</option>
                                <option value="Administrador" <?= $filtro_rol === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                                <option value="Supervisor" <?= $filtro_rol === 'Supervisor' ? 'selected' : '' ?>>Supervisor</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-toggle-on"></i> Estado
                            </label>
                            <select name="filtro_estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?= $filtro_estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $filtro_estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <?php if (!empty($busqueda) || !empty($filtro_rol) || !empty($filtro_estado)): ?>
                                <a href="configuracion_usuarios.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- INFO DE PAGINACIÓN -->
                <div class="pagination-info">
                    <i class="fas fa-chart-bar"></i>
                    Mostrando <strong><?= $res->num_rows ?></strong> de <strong><?= $total_registros ?></strong> usuarios
                    <?php if (!empty($busqueda)): ?>
                        - Resultados para: "<strong><?= htmlspecialchars($busqueda) ?></strong>"
                    <?php endif; ?>
                </div>

                <!-- TABLA -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res->num_rows > 0): ?>
                                <?php while ($u = $res->fetch_assoc()): ?>
                                <tr class="fade-in">
                                    <td class="user-cell">
                                        <i class="fas fa-user icon-user"></i>
                                        <?= htmlspecialchars($u['nombre']) ?>
                                    </td>
                                    <td class="email-cell">
                                        <i class="fas fa-envelope icon-email"></i>
                                        <?= htmlspecialchars($u['email']) ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = 'badge-';
                                        $icon = 'user';
                                        
                                        if (strtolower($u['rol']) === 'administrador') {
                                            $badge_class .= 'admin';
                                            $icon = 'crown';
                                        } elseif (strtolower($u['rol']) === 'supervisor') {
                                            $badge_class .= 'supervisor';
                                            $icon = 'user-tie';
                                        } else {
                                            $badge_class .= 'user';
                                            $icon = 'user';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <i class="fas fa-<?= $icon ?>"></i>
                                            <?= $u['rol'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $u['estado'] ?>">
                                            <i class="fas fa-<?= $u['estado'] === 'activo' ? 'check-circle' : 'times-circle' ?>"></i>
                                            <?= ucfirst($u['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="date-cell">
                                        <i class="fas fa-calendar-alt icon-date"></i>
                                        <?= date('d/m/Y', strtotime($u['fecha_registro'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-warning btn-sm" 
                                               href="editar_usuario.php?id=<?= $u['id'] ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <?php if ($u['estado'] === 'activo'): ?>
                                                <a class="btn btn-danger btn-sm toggle-btn" 
                                                   href="#" 
                                                   data-id="<?= $u['id'] ?>"
                                                   data-name="<?= htmlspecialchars($u['nombre']) ?>"
                                                   data-role="<?= htmlspecialchars($u['rol']) ?>"
                                                   data-action="desactivar">
                                                    <i class="fas fa-toggle-off"></i> Desactivar
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-success btn-sm toggle-btn" 
                                                   href="#" 
                                                   data-id="<?= $u['id'] ?>"
                                                   data-name="<?= htmlspecialchars($u['nombre']) ?>"
                                                   data-role="<?= htmlspecialchars($u['rol']) ?>"
                                                   data-action="activar">
                                                    <i class="fas fa-toggle-on"></i> Activar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-user-slash"></i>
                                            <h3>No hay usuarios registrados</h3>
                                            <p>No se encontraron usuarios que coincidan con los criterios de búsqueda.</p>
                                            <a href="crear_usuario.php" class="btn btn-primary">
                                                <i class="fas fa-user-plus"></i> Crear primer usuario
                                            </a>
                                            <?php if (!empty($busqueda) || !empty($filtro_rol) || !empty($filtro_estado)): ?>
                                                <a href="configuracion_usuarios.php" class="btn btn-secondary" style="margin-left: 10px;">
                                                    <i class="fas fa-redo"></i> Ver todos
                                                </a>
                                            <?php endif; ?>
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
                        <i class="fas fa-file-alt"></i>
                        Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                    </div>
                    <div class="pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&filtro_rol=<?= urlencode($filtro_rol) ?>&filtro_estado=<?= urlencode($filtro_estado) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?pagina=<?= $pagina_actual - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_rol=<?= urlencode($filtro_rol) ?>&filtro_estado=<?= urlencode($filtro_estado) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                <i class="fas fa-angle-double-left"></i>
                            </span>
                            <span class="page-link disabled">
                                <i class="fas fa-angle-left"></i>
                            </span>
                        <?php endif; ?>

                        <?php
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        
                        if ($inicio > 1) {
                            echo '<span class="page-link disabled">...</span>';
                        }
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="page-link active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_rol=<?= urlencode($filtro_rol) ?>&filtro_estado=<?= urlencode($filtro_estado) ?>" 
                                   class="page-link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($fin < $total_paginas) {
                            echo '<span class="page-link disabled">...</span>';
                        } ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina_actual + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_rol=<?= urlencode($filtro_rol) ?>&filtro_estado=<?= urlencode($filtro_estado) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?pagina=<?= $total_paginas ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_rol=<?= urlencode($filtro_rol) ?>&filtro_estado=<?= urlencode($filtro_estado) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                <i class="fas fa-angle-right"></i>
                            </span>
                            <span class="page-link disabled">
                                <i class="fas fa-angle-double-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FOOTER ACCIONES -->
                <div class="footer-actions">
                    <div class="pagination-info">
                        <i class="fas fa-info-circle"></i>
                        Total: <strong><?= $total_registros ?></strong> usuarios en el sistema
                    </div>
                    <a href="dashboard_admin.php" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===== SISTEMA DE ALERTAS PERSONALIZADAS =====
        
        // Generar iniciales para avatar
        function getInitials(name) {
            return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
        }

        // Mostrar notificación toast
        function showToast(type, title, message, duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Animación de entrada
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto-remover después de la duración
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, duration);
        }

        // Mostrar confirmación avanzada
        function showConfirm(userId, userName, userRole, action) {
            const confirmCard = document.getElementById('confirmCard');
            const userAvatar = document.getElementById('userAvatar');
            const userNameEl = document.getElementById('userName');
            const userRoleEl = document.getElementById('userRole');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmIcon = document.getElementById('confirmIcon');
            const confirmTitle = document.getElementById('confirmTitle');
            const confirmActionBtn = document.getElementById('confirmActionBtn');
            
            // Configurar datos del usuario
            userAvatar.textContent = getInitials(userName);
            userNameEl.textContent = userName;
            userRoleEl.textContent = userRole;
            
            // Configurar según la acción
            if (action === 'desactivar') {
                confirmTitle.textContent = 'Desactivar Usuario';
                confirmMessage.textContent = `¿Está seguro de desactivar al usuario "${userName}"?`;
                confirmIcon.innerHTML = '<i class="fas fa-toggle-off"></i>';
                confirmActionBtn.className = 'confirm-btn confirm-btn-danger';
                confirmActionBtn.innerHTML = '<i class="fas fa-toggle-off"></i> Desactivar';
            } else {
                confirmTitle.textContent = 'Activar Usuario';
                confirmMessage.textContent = `¿Está seguro de activar al usuario "${userName}"?`;
                confirmIcon.innerHTML = '<i class="fas fa-toggle-on"></i>';
                confirmActionBtn.className = 'confirm-btn confirm-btn-success';
                confirmActionBtn.innerHTML = '<i class="fas fa-toggle-on"></i> Activar';
            }
            
            // Mostrar tarjeta de confirmación
            confirmCard.classList.add('active');
            
            // Configurar botones
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            const handleConfirm = () => {
                window.location.href = `toggle_estado.php?id=${userId}`;
            };
            
            const handleCancel = () => {
                confirmCard.classList.remove('active');
                confirmActionBtn.removeEventListener('click', handleConfirm);
                confirmCancelBtn.removeEventListener('click', handleCancel);
            };
            
            confirmActionBtn.addEventListener('click', handleConfirm);
            confirmCancelBtn.addEventListener('click', handleCancel);
        }

        // Configurar botones de activar/desactivar
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const userRole = this.getAttribute('data-role');
                const action = this.getAttribute('data-action');
                
                showConfirm(userId, userName, userRole, action);
            });
        });

        // Sistema de alertas simple
        function showAlert(title, message, type = 'warning') {
            const overlay = document.getElementById('customAlertOverlay');
            const alertBox = document.getElementById('customAlert');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');
            const alertIcon = document.getElementById('alertIcon');
            
            alertTitle.textContent = title;
            alertMessage.textContent = message;
            
            // Configurar icono según tipo
            const icons = {
                warning: 'fa-exclamation-triangle',
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                info: 'fa-info-circle'
            };
            
            alertIcon.innerHTML = `<i class="fas ${icons[type] || 'fa-exclamation-triangle'}"></i>`;
            
            overlay.classList.add('active');
            setTimeout(() => alertBox.classList.add('active'), 10);
            
            return new Promise((resolve) => {
                const confirmBtn = document.getElementById('alertConfirmBtn');
                const cancelBtn = document.getElementById('alertCancelBtn');
                
                const handleConfirm = () => {
                    cleanup();
                    resolve(true);
                };
                
                const handleCancel = () => {
                    cleanup();
                    resolve(false);
                };
                
                const cleanup = () => {
                    alertBox.classList.remove('active');
                    setTimeout(() => overlay.classList.remove('active'), 300);
                    confirmBtn.removeEventListener('click', handleConfirm);
                    cancelBtn.removeEventListener('click', handleCancel);
                };
                
                confirmBtn.addEventListener('click', handleConfirm);
                cancelBtn.addEventListener('click', handleCancel);
            });
        }

        // ===== FUNCIONALIDADES ADICIONALES =====
        
        // Resaltar filas al pasar el mouse
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 15px rgba(0, 0, 0, 0.07)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Efectos en botones
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

        // Auto-focus en búsqueda si está vacío
        window.addEventListener('load', () => {
            const searchInput = document.querySelector('input[name="busqueda"]');
            if (searchInput && !searchInput.value) {
                setTimeout(() => searchInput.focus(), 400);
            }
        });

        // Animación para elementos al aparecer
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                }
            });
        }, observerOptions);

        // Observar filas de la tabla
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            observer.observe(row);
        });

        // Mostrar mensaje de carga al filtrar
        const filterForm = document.querySelector('.search-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtrando...';
                    submitBtn.disabled = true;
                }
            });
        }

        // Ejemplo de uso de toast notifications
        <?php if ($msg === 'creado'): ?>
        setTimeout(() => {
            showToast('success', 'Usuario Creado', 'El usuario ha sido creado correctamente.');
        }, 1000);
        <?php elseif ($msg === 'editado'): ?>
        setTimeout(() => {
            showToast('success', 'Usuario Actualizado', 'Los cambios se han guardado correctamente.');
        }, 1000);
        <?php elseif ($msg === 'toggle'): ?>
        setTimeout(() => {
            showToast('success', 'Estado Cambiado', 'El estado del usuario ha sido actualizado.');
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>