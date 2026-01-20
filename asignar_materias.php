<?php
session_start();
include 'connection.php';

$dashboard = "dashboard.php"; // por defecto supervisor

if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

$mensaje = '';
$tipo = ''; // 'exito' o 'error'

// ============================================
// CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA
// ============================================

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_profesor = isset($_GET['filtro_profesor']) ? $_GET['filtro_profesor'] : '';
$filtro_materia = isset($_GET['filtro_materia']) ? $_GET['filtro_materia'] : '';

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener lista de profesores para el filtro
$profesores_query = $conn->query("SELECT IDPROF, NOMAPE FROM profesores ORDER BY NOMAPE ASC");
$profesores = [];
while ($prof = $profesores_query->fetch_assoc()) {
    $profesores[$prof['IDPROF']] = $prof['NOMAPE'];
}

// Obtener lista de materias para el filtro
$materias_query = $conn->query("SELECT IDMAT, NOMMAT FROM materias ORDER BY NOMMAT ASC");
$materias = [];
while ($mat = $materias_query->fetch_assoc()) {
    $materias[$mat['IDMAT']] = $mat['NOMMAT'];
}

// Construir consulta base con filtros
$where_conditions = [];
$params = [];
$types = "";

if (!empty($busqueda)) {
    $where_conditions[] = "(p.NOMAPE LIKE ? OR m.NOMMAT LIKE ? OR pl.DESCRIP LIKE ?)";
    $search_param = "%{$busqueda}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($filtro_profesor)) {
    $where_conditions[] = "a.IDPROF = ?";
    $params[] = $filtro_profesor;
    $types .= "i";
}

if (!empty($filtro_materia)) {
    $where_conditions[] = "a.IDMAT = ?";
    $params[] = $filtro_materia;
    $types .= "i";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Consulta para contar total de registros (para paginación)
$count_sql = "SELECT COUNT(*) as total 
              FROM asignacion a
              INNER JOIN profesores p ON a.IDPROF = p.IDPROF
              INNER JOIN materias m ON a.IDMAT = m.IDMAT
              INNER JOIN planificacion pl ON a.IDPLA = pl.IDPLA
              $where_sql";
              
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
$sql = "SELECT a.IDASIG, a.IDPLA, a.IDMAT, a.IDPROF, 
               a.FECINI, a.FECCUL, a.HORAINI, a.HORACUL,
               p.NOMAPE AS profesor,
               m.NOMMAT AS materia,
               pl.DESCRIP AS plan
        FROM asignacion a
        INNER JOIN profesores p ON a.IDPROF = p.IDPROF
        INNER JOIN materias m ON a.IDMAT = m.IDMAT
        INNER JOIN planificacion pl ON a.IDPLA = pl.IDPLA
        $where_sql
        ORDER BY a.IDASIG DESC 
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Materias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES Y RESET ===== */
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #6c757d;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px 0;
            text-align: center;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
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
            background: linear-gradient(to right, #f39c12, #27ae60, #9b59b6);
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

        /* ===== MAIN CONTAINER ===== */
        .container {
            max-width: 1400px;
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

        /* ===== FORMULARIO DE BÚSQUEDA ===== */
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
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* ===== BOTONES ===== */
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
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.2);
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

        .btn-sm {
            padding: 10px 18px;
            font-size: 14px;
        }

        /* ===== TABLA ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--gray);
            margin-top: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
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

        .badge-plan {
            background: #e8f4fc;
            color: var(--primary);
            border: 1px solid #b3d9ff;
        }

        /* ===== PAGINACIÓN ===== */
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

        /* ===== ACCIONES ===== */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ===== ESTADOS VACÍOS ===== */
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

        /* ===== FOOTER ===== */
        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 1px solid var(--gray);
            flex-wrap: wrap;
            gap: 20px;
        }

        /* ===== RESPONSIVE ===== */
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

        /* ===== ANIMACIONES ===== */
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

        /* ===== ESTILOS ESPECÍFICOS PARA ASIGNACIONES ===== */
        .date-cell {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            font-weight: 600;
            color: var(--dark);
        }

        .time-cell {
            font-family: 'Courier New', monospace;
            background: #e8f4fc;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #cce7ff;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .profesor-cell {
            background: #f0f8ff;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #d6eaff;
            font-weight: 600;
            color: var(--primary);
        }

        .materia-cell {
            background: #f0fff4;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #d4edda;
            font-weight: 600;
            color: var(--success);
        }

        /* ===== ÍCONOS ESPECÍFICOS ===== */
        .icon-user {
            color: var(--primary);
            margin-right: 8px;
        }

        .icon-book {
            color: var(--success);
            margin-right: 8px;
        }

        .icon-date {
            color: var(--info);
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header slide-in">
        <h1><i class="fas fa-book-open"></i> Gestión de Asignaciones</h1>
        <p>Asignación de materias a profesores con planificación académica</p>
    </header>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- CARD PRINCIPAL -->
        <div class="card fade-in">
            <!-- CARD HEADER -->
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list-check"></i>
                    Lista de Asignaciones
                </div>
                <div class="action-buttons">
                    <a href="crear_asignacion.php" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Nueva Asignación
                    </a>
                </div>
            </div>

            <!-- CARD BODY -->
            <div class="card-body">
                <!-- FORMULARIO DE BÚSQUEDA -->
                <div class="search-container">
                    <form method="GET" action="" class="search-form">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-search"></i> Buscar
                            </label>
                            <input type="text" name="busqueda" class="form-input" 
                                   placeholder="Profesor, materia o plan..." 
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie"></i> Profesor
                            </label>
                            <select name="filtro_profesor" class="form-select">
                                <option value="">Todos los profesores</option>
                                <?php foreach($profesores as $id => $nombre): ?>
                                    <option value="<?= $id ?>" <?= $filtro_profesor == $id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-book"></i> Materia
                            </label>
                            <select name="filtro_materia" class="form-select">
                                <option value="">Todas las materias</option>
                                <?php foreach($materias as $id => $nombre): ?>
                                    <option value="<?= $id ?>" <?= $filtro_materia == $id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <?php if (!empty($busqueda) || !empty($filtro_profesor) || !empty($filtro_materia)): ?>
                                <a href="asignaciones.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- INFO DE PAGINACIÓN -->
                <div class="pagination-info">
                    <i class="fas fa-chart-bar"></i>
                    Mostrando <strong><?= $res->num_rows ?></strong> de <strong><?= $total_registros ?></strong> asignaciones
                    <?php if (!empty($busqueda)): ?>
                        - Resultados para: "<strong><?= htmlspecialchars($busqueda) ?></strong>"
                    <?php endif; ?>
                </div>

                <!-- TABLA -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Profesor</th>
                                <th>Materia</th>
                                <th>Planificación</th>
                                <th>Fecha de Inicio</th>
                                <th>Fecha de Culminación</th>
                                <th>Hora de Inicio</th>
                                <th>Hora de Culminación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res->num_rows > 0): ?>
                                <?php while ($row = $res->fetch_assoc()): ?>
                                <tr class="fade-in">
                                    <td>
                                        <div class="profesor-cell">
                                            <i class="fas fa-user-circle icon-user"></i>
                                            <strong><?= htmlspecialchars($row['profesor']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="materia-cell">
                                            <i class="fas fa-book icon-book"></i>
                                            <?= htmlspecialchars($row['materia']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-plan">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?= htmlspecialchars($row['plan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-cell">
                                            <i class="fas fa-calendar-plus"></i>
                                            <?= date('d/m/Y', strtotime($row['FECINI'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-cell">
                                            <i class="fas fa-calendar-check"></i>
                                            <?= date('d/m/Y', strtotime($row['FECCUL'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-cell">
                                            <i class="fas fa-clock"></i>
                                            <?= date('H:i', strtotime($row['HORAINI'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-cell">
                                            <i class="fas fa-clock"></i>
                                            <?= date('H:i', strtotime($row['HORACUL'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-warning btn-sm" 
                                               href="editar_asignacion.php?id=<?= $row['IDASIG'] ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-tasks"></i>
                                            <h3>No hay asignaciones registradas</h3>
                                            <p>No se encontraron asignaciones que coincidan con los criterios de búsqueda.</p>
                                            <a href="crear_asignacion.php" class="btn btn-primary">
                                                <i class="fas fa-plus-circle"></i> Crear primera asignación
                                            </a>
                                            <?php if (!empty($busqueda) || !empty($filtro_profesor) || !empty($filtro_materia)): ?>
                                                <a href="asignaciones.php" class="btn btn-secondary" style="margin-left: 10px;">
                                                    <i class="fas fa-redo"></i> Ver todas
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
                            <a href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&filtro_profesor=<?= urlencode($filtro_profesor) ?>&filtro_materia=<?= urlencode($filtro_materia) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?pagina=<?= $pagina_actual - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_profesor=<?= urlencode($filtro_profesor) ?>&filtro_materia=<?= urlencode($filtro_materia) ?>" 
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
                                <a href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_profesor=<?= urlencode($filtro_profesor) ?>&filtro_materia=<?= urlencode($filtro_materia) ?>" 
                                   class="page-link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($fin < $total_paginas) {
                            echo '<span class="page-link disabled">...</span>';
                        } ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?= $pagina_actual + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_profesor=<?= urlencode($filtro_profesor) ?>&filtro_materia=<?= urlencode($filtro_materia) ?>" 
                               class="page-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?pagina=<?= $total_paginas ?>&busqueda=<?= urlencode($busqueda) ?>&filtro_profesor=<?= urlencode($filtro_profesor) ?>&filtro_materia=<?= urlencode($filtro_materia) ?>" 
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
                        Total: <strong><?= $total_registros ?></strong> asignaciones registradas
                    </div>
                    <a href="<?= $dashboard ?>" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===== FUNCIONALIDADES JAVASCRIPT =====
        
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
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>