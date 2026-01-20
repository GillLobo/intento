<?php
// -----------------------------------------------------------
// reportes.php (SIN MENSAJES DEL LOCALHOST)
// -----------------------------------------------------------

include 'connection.php';
session_start();

// Configuración de paginación
$reportes_por_pagina = 10;

// Determinar página actual
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Calcular offset
$offset = ($pagina_actual - 1) * $reportes_por_pagina;

$dashboard = "dashboard.php";
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

// Procesar búsqueda
$busqueda = '';
$where_clause = '';
$parametros_busqueda = [];

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $where_clause = " WHERE (f.asunto LIKE ? OR f.para LIKE ? OR f.de_nombre LIKE ? OR f.comunicado LIKE ?)";
    $parametros_busqueda = array_fill(0, 4, "%$busqueda%");
}

// Contar total de reportes
$query_contar = "
    SELECT COUNT(DISTINCT f.id) as total
    FROM formularios f
    LEFT JOIN planificacion p ON f.pap_id = p.IDPLA
    LEFT JOIN formulario_docentes fd ON f.id = fd.formulario_id
    $where_clause
";

$stmt_contar = $conn->prepare($query_contar);
if (!empty($parametros_busqueda)) {
    $stmt_contar->bind_param(str_repeat('s', count($parametros_busqueda)), ...$parametros_busqueda);
}
$stmt_contar->execute();
$result_contar = $stmt_contar->get_result();
$total_filas = $result_contar->fetch_assoc()['total'];
$stmt_contar->close();

// Calcular total de páginas
$total_paginas = ceil($total_filas / $reportes_por_pagina);
if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

// Obtener reportes con paginación
$query = "
    SELECT 
        f.id,
        f.asunto,
        f.fecha,
        f.comunicado,
        f.para,
        f.de_nombre,
        f.parrafo,
        f.pap_id,
        p.DESCRIP as pap_nombre,
        COUNT(fd.id) as cantidad_docentes
    FROM formularios f
    LEFT JOIN planificacion p ON f.pap_id = p.IDPLA
    LEFT JOIN formulario_docentes fd ON f.id = fd.formulario_id
    $where_clause
    GROUP BY f.id
    ORDER BY f.fecha DESC, f.id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);

if (!empty($parametros_busqueda)) {
    $parametros = array_merge($parametros_busqueda, [$reportes_por_pagina, $offset]);
    $tipos = str_repeat('s', count($parametros_busqueda)) . 'ii';
    $stmt->bind_param($tipos, ...$parametros);
} else {
    $stmt->bind_param('ii', $reportes_por_pagina, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$reportes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reportes[] = $row;
    }
}

$stmt->close();

function formatearFecha($fecha) {
    if (empty($fecha)) return 'No especificada';
    $fecha_obj = new DateTime($fecha);
    return $fecha_obj->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reportes de Comunicados</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f7f8fb 0%, #eef1f7 100%);
            margin: 0;
            padding: 24px;
            min-height: 100vh;
        }
        
        .card {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e1e5eb;
        }
        
        h1 {
            margin: 0 0 20px;
            color: #005bbb;
            border-bottom: 3px solid #f0f7ff;
            padding-bottom: 15px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        h1:before {
            content: "📊";
            font-size: 1.6rem;
        }
        
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .contador {
            background: linear-gradient(135deg, #005bbb 0%, #004a9c 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input {
            padding: 10px 15px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            width: 300px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: #005bbb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #005bbb 0%, #004a9c 100%);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 91, 187, 0.2);
        }
        
        .btn.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .btn.secondary:hover {
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
        }
        
        .btn.small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .btn.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        
        /* Estilos para la tabla */
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            border: 1px solid #e1e5eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.95rem;
        }
        
        tbody tr {
            border-bottom: 1px solid #e1e5eb;
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        td {
            padding: 15px;
            color: #495057;
            vertical-align: top;
        }
        
        .asunto-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 60px;
        }
        
        .badge-primary {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .acciones-cell {
            white-space: nowrap;
        }
        
        .acciones {
            display: flex;
            gap: 8px;
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eef1f7;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
            margin-right: 20px;
        }
        
        .page-link {
            padding: 8px 14px;
            border: 1px solid #dee2e6;
            background: white;
            color: #005bbb;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }
        
        .page-link:hover {
            background: #f8f9fa;
            border-color: #005bbb;
        }
        
        .page-link.active {
            background: linear-gradient(135deg, #005bbb 0%, #004a9c 100%);
            color: white;
            border-color: #005bbb;
        }
        
        .page-link.disabled {
            color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .page-link.disabled:hover {
            background: white;
            border-color: #dee2e6;
        }
        
        /* Modales personalizados */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal.detalles {
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f7ff;
        }
        
        .modal-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-icon {
            font-size: 1.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            color: #dc3545;
            background: #f8f9fa;
        }
        
        .modal-content {
            margin-bottom: 25px;
            color: #495057;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        /* Detalles del reporte */
        .detalle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .detalle-item {
            margin-bottom: 15px;
        }
        
        .detalle-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .detalle-value {
            color: #212529;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e1e5eb;
        }
        
        .detalle-value.parrafo {
            white-space: pre-wrap;
            line-height: 1.5;
            min-height: 100px;
        }
        
        .docentes-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #eef1f7;
        }
        
        .docentes-title {
            font-size: 1.1rem;
            color: #005bbb;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .docentes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .docentes-table th {
            background: #f8f9fa;
            padding: 10px;
            font-size: 0.85rem;
        }
        
        .docentes-table td {
            padding: 10px;
            border-bottom: 1px solid #e1e5eb;
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .empty-state p {
            max-width: 500px;
            margin: 0 auto 20px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .header-info {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-container {
                width: 100%;
            }
            
            .search-input {
                width: 100%;
            }
            
            .table-container {
                margin: 15px -20px;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            
            .detalle-grid {
                grid-template-columns: 1fr;
            }
            
            .modal {
                width: 95%;
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .acciones {
                flex-direction: column;
            }
            
            .btn.small {
                width: 100%;
                justify-content: center;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .modal-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reportes de Comunicados</h1>
        
        <div class="header-info">
            <div class="contador">
                <span>📋</span>
                <span id="total-reportes"><?php echo $total_filas; ?></span> reportes encontrados
            </div>
            
            <form method="GET" action="" class="search-container">
                <input type="text" 
                       name="buscar" 
                       class="search-input" 
                       placeholder="Buscar por asunto, destinatario, comunicado..."
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn">🔍 Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="reportes.php" class="btn secondary">🗑️ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container">
            <?php if (count($reportes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;">FECHA</th>
                            <th>ASUNTO</th>
                            <th style="width: 150px;">PERÍODO</th>
                            <th style="width: 120px;">DOCENTES</th>
                            <th style="width: 150px;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $reporte): ?>
                        <tr data-id="<?php echo $reporte['id']; ?>">
                            <td>
                                <div class="badge badge-primary">
                                    <?php echo formatearFecha($reporte['fecha']); ?>
                                </div>
                            </td>
                            <td class="asunto-cell" title="<?php echo htmlspecialchars($reporte['asunto']); ?>">
                                <?php echo htmlspecialchars($reporte['asunto']); ?>
                            </td>
                            <td>
                                <?php if (!empty($reporte['pap_nombre'])): ?>
                                    <span class="badge badge-success">
                                        <?php echo htmlspecialchars($reporte['pap_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f8f9fa; color:#6c757d;">
                                        Sin período
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background:#fff3cd; color:#856404;">
                                    👨‍🏫 <?php echo $reporte['cantidad_docentes']; ?> docente(s)
                                </span>
                            </td>
                            <td class="acciones-cell">
                                <div class="acciones">
                                    <button class="btn small ver-detalles" 
                                            data-id="<?php echo $reporte['id']; ?>"
                                            title="Ver detalles completos">
                                        👁️ Ver
                                    </button>
                                    <button class="btn small secondary abrir-pdf"
                                            data-id="<?php echo $reporte['id']; ?>"
                                            title="Descargar PDF">
                                        📄 PDF
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No se encontraron reportes</h3>
                    <p>
                        <?php if (!empty($busqueda)): ?>
                            No hay reportes que coincidan con "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php else: ?>
                            No se han generado reportes todavía.
                        <?php endif; ?>
                    </p>
                    <?php if (empty($busqueda)): ?>
                        <a href="formulario.php" class="btn">➕ Crear primer reporte</a>
                    <?php else: ?>
                        <a href="reportes.php" class="btn">↻ Ver todos los reportes</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
                (<?php echo $total_filas; ?> reportes)
            </div>
            
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=1<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="page-link" title="Primera página">««</a>
            <?php else: ?>
                <span class="page-link disabled">««</span>
            <?php endif; ?>
            
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="page-link" title="Página anterior">«</a>
            <?php else: ?>
                <span class="page-link disabled">«</span>
            <?php endif; ?>
            
            <?php 
            $inicio = max(1, $pagina_actual - 2);
            $fin = min($total_paginas, $pagina_actual + 2);
            
            for ($i = $inicio; $i <= $fin; $i++): 
            ?>
                <a href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="page-link <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="page-link" title="Página siguiente">»</a>
            <?php else: ?>
                <span class="page-link disabled">»</span>
            <?php endif; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="page-link" title="Última página">»»</a>
            <?php else: ?>
                <span class="page-link disabled">»»</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align: right; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eef1f7;">
            <a href="<?php echo $dashboard; ?>" class="btn secondary">Volver</a>
        </div>
    </div>
    
    <!-- Modal para confirmaciones -->
    <div class="modal-overlay" id="modal-confirm">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon" id="modal-confirm-icon">⚠️</div>
                <h3 class="modal-title" id="modal-confirm-title">Confirmar acción</h3>
            </div>
            <div class="modal-content" id="modal-confirm-content">
                ¿Está seguro de realizar esta acción?
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" id="modal-confirm-cancel">Cancelar</button>
                <button type="button" class="btn" id="modal-confirm-ok">Aceptar</button>
            </div>
        </div>
    </div>
    
    <!-- Modal para alertas -->
    <div class="modal-overlay" id="modal-alert">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon" id="modal-alert-icon">ℹ️</div>
                <h3 class="modal-title" id="modal-alert-title">Información</h3>
            </div>
            <div class="modal-content" id="modal-alert-content">
                Mensaje de información
            </div>
            <div class="modal-actions">
                <button type="button" class="btn" id="modal-alert-ok">Aceptar</button>
            </div>
        </div>
    </div>
    
    <!-- Modal para detalles del reporte -->
    <div class="modal-overlay" id="modal-detalles">
        <div class="modal detalles">
            <div class="modal-header">
                <h3 class="modal-title">
                    <span>📋</span>
                    <span id="modal-detalles-title">Detalles del Comunicado</span>
                </h3>
                <button class="modal-close" id="modal-detalles-cerrar">×</button>
            </div>
            <div id="modal-detalles-contenido">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>

    <script>
    // ============================
    // SISTEMA DE MODALES PERSONALIZADOS
    // (Reemplaza alert(), confirm(), etc.)
    // ============================
    
    // Modal de confirmación
    const modalConfirm = document.getElementById('modal-confirm');
    const modalConfirmTitle = document.getElementById('modal-confirm-title');
    const modalConfirmContent = document.getElementById('modal-confirm-content');
    const modalConfirmIcon = document.getElementById('modal-confirm-icon');
    const modalConfirmCancel = document.getElementById('modal-confirm-cancel');
    const modalConfirmOk = document.getElementById('modal-confirm-ok');
    
    // Modal de alerta
    const modalAlert = document.getElementById('modal-alert');
    const modalAlertTitle = document.getElementById('modal-alert-title');
    const modalAlertContent = document.getElementById('modal-alert-content');
    const modalAlertIcon = document.getElementById('modal-alert-icon');
    const modalAlertOk = document.getElementById('modal-alert-ok');
    
    // Modal de detalles
    const modalDetalles = document.getElementById('modal-detalles');
    const modalDetallesTitle = document.getElementById('modal-detalles-title');
    const modalDetallesContenido = document.getElementById('modal-detalles-contenido');
    const modalDetallesCerrar = document.getElementById('modal-detalles-cerrar');
    
    let confirmResolve = null;
    let alertResolve = null;
    
    // ============================
    // FUNCIONES PARA MOSTRAR MODALES
    // ============================
    
    // Mostrar modal de confirmación (reemplaza confirm())
    function mostrarConfirmacion(mensaje, titulo = 'Confirmar', icono = '⚠️') {
        modalConfirmTitle.textContent = titulo;
        modalConfirmContent.textContent = mensaje;
        modalConfirmIcon.textContent = icono;
        modalConfirm.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        return new Promise((resolve) => {
            confirmResolve = resolve;
        });
    }
    
    // Mostrar modal de alerta (reemplaza alert())
    function mostrarAlerta(mensaje, titulo = 'Información', icono = 'ℹ️') {
        modalAlertTitle.textContent = titulo;
        modalAlertContent.textContent = mensaje;
        modalAlertIcon.textContent = icono;
        modalAlert.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        return new Promise((resolve) => {
            alertResolve = resolve;
        });
    }
    
    // Mostrar modal de detalles
    function mostrarDetalles() {
        modalDetalles.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Ocultar todos los modales
    function ocultarTodosModales() {
        modalConfirm.classList.remove('active');
        modalAlert.classList.remove('active');
        modalDetalles.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    
    // ============================
    // EVENT LISTENERS PARA MODALES
    // ============================
    
    // Confirmación: Cancelar
    modalConfirmCancel.addEventListener('click', () => {
        if (confirmResolve) {
            confirmResolve(false);
            confirmResolve = null;
        }
        ocultarTodosModales();
    });
    
    // Confirmación: Aceptar
    modalConfirmOk.addEventListener('click', () => {
        if (confirmResolve) {
            confirmResolve(true);
            confirmResolve = null;
        }
        ocultarTodosModales();
    });
    
    // Alerta: Aceptar
    modalAlertOk.addEventListener('click', () => {
        if (alertResolve) {
            alertResolve();
            alertResolve = null;
        }
        ocultarTodosModales();
    });
    
    // Detalles: Cerrar
    modalDetallesCerrar.addEventListener('click', ocultarTodosModales);
    
    // Cerrar modales al hacer clic fuera
    [modalConfirm, modalAlert, modalDetalles].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                ocultarTodosModales();
            }
        });
    });
    
    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            ocultarTodosModales();
        }
    });
    
    // ============================
    // FUNCIONALIDAD DE LA TABLA
    // ============================
    
    // 1. Abrir PDF con confirmación
    document.querySelectorAll('.abrir-pdf').forEach(button => {
        button.addEventListener('click', async function() {
            const reporteId = this.getAttribute('data-id');
            
            const confirmado = await mostrarConfirmacion(
                '¿Desea abrir el PDF del comunicado?',
                'Abrir PDF',
                '📄'
            );
            
            if (confirmado) {
                // Abrir PDF en nueva pestaña
                window.open(`generar_pdf.php?id=${reporteId}`, '_blank');
            }
        });
    });
    
    // 2. Ver detalles del reporte
    document.querySelectorAll('.ver-detalles').forEach(button => {
        button.addEventListener('click', async function() {
            const reporteId = this.getAttribute('data-id');
            
            // Mostrar loading
            modalDetallesContenido.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 2rem; margin-bottom: 20px; opacity: 0.5;">⏳</div>
                    <p>Cargando detalles...</p>
                </div>
            `;
            
            modalDetallesTitle.textContent = 'Cargando...';
            mostrarDetalles();
            
            try {
                const response = await fetch(`obtener_detalles.php?id=${reporteId}`);
                const data = await response.json();
                
                if (data.error) {
                    modalDetallesContenido.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <div style="font-size: 2rem; margin-bottom: 20px;">❌</div>
                            <h3>Error</h3>
                            <p>${data.mensaje}</p>
                        </div>
                    `;
                    return;
                }
                
                // Actualizar título
                modalDetallesTitle.textContent = data.asunto || 'Detalles del Comunicado';
                
                // Construir HTML para detalles
                let html = `
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <div class="detalle-label">N° COMUNICADO</div>
                            <div class="detalle-value">${data.comunicado || 'No especificado'}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label">FECHA</div>
                            <div class="detalle-value">${data.fecha_formateada || 'No especificada'}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label">PERÍODO ACADÉMICO</div>
                            <div class="detalle-value">${data.pap_nombre || 'No especificado'}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label">PARA</div>
                            <div class="detalle-value">${data.para || 'No especificado'}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label">DE</div>
                            <div class="detalle-value">${data.de_nombre || 'No especificado'}</div>
                        </div>
                        <div class="detalle-item">
                            <div class="detalle-label">ASUNTO</div>
                            <div class="detalle-value">${data.asunto || 'No especificado'}</div>
                        </div>
                    </div>
                    
                    <div class="detalle-item full">
                        <div class="detalle-label">CONTENIDO DEL COMUNICADO</div>
                        <div class="detalle-value parrafo">${data.parrafo || 'No hay contenido'}</div>
                    </div>
                `;
                
                // Mostrar docentes si existen
                if (data.docentes && data.docentes.length > 0) {
                    html += `
                        <div class="docentes-section">
                            <h4 class="docentes-title">
                                <span>👨‍🏫</span>
                                DOCENTES ASIGNADOS (${data.docentes.length})
                            </h4>
                            <div class="table-container">
                                <table class="docentes-table">
                                    <thead>
                                        <tr>
                                            <th>CÉDULA</th>
                                            <th>DOCENTE</th>
                                            <th>ASIGNATURA</th>
                                            <th>HORAS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    data.docentes.forEach(docente => {
                        html += `
                            <tr>
                                <td>${docente.cedula || ''}</td>
                                <td>${docente.docente || ''}</td>
                                <td>${docente.asignatura || ''}</td>
                                <td>${docente.horas || 0}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }
                
                // Botones de acción, elimiar el de generar pdf
                html += `
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eef1f7; text-align: right;">
                        <!-- 
                        <button class="btn secondary abrir-pdf-desde-detalle" data-id="${reporteId}">
                            📄 Generar PDF
                        </button>
                        -->
                        <button class="btn" onclick="ocultarTodosModales()">
                            Cerrar
                        </button>
                    </div>
                `;
                
                modalDetallesContenido.innerHTML = html;
                
                // Agregar evento al botón de PDF dentro del modal
                modalDetallesContenido.querySelector('.abrir-pdf-desde-detalle')?.addEventListener('click', async function() {
                    const id = this.getAttribute('data-id');
                    const confirmado = await mostrarConfirmacion(
                        '¿Desea abrir el PDF del comunicado?',
                        'Abrir PDF',
                        '📄'
                    );
                    
                    if (confirmado) {
                        window.open(`generar_pdf.php?id=${id}`, '_blank');
                    }
                });
                
            } catch (error) {
                console.error('Error:', error);
                modalDetallesContenido.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc3545;">
                        <div style="font-size: 2rem; margin-bottom: 20px;">❌</div>
                        <h3>Error de conexión</h3>
                        <p>No se pudieron cargar los detalles. Intente nuevamente.</p>
                    </div>
                `;
            }
        });
    });
    
    // ============================
    // MEJORAS DE UX
    // ============================
    document.addEventListener('DOMContentLoaded', function() {
        // Resaltar búsqueda en la tabla
        const busqueda = "<?php echo addslashes($busqueda); ?>";
        if (busqueda) {
            const cells = document.querySelectorAll('td.asunto-cell');
            cells.forEach(cell => {
                const texto = cell.textContent.toLowerCase();
                const busquedaLower = busqueda.toLowerCase();
                if (texto.includes(busquedaLower)) {
                    cell.style.backgroundColor = '#fff3cd';
                    cell.style.borderLeft = '3px solid #ffc107';
                }
            });
        }
        
        // Auto-enfocar en campo de búsqueda si hay búsqueda previa
        if (busqueda) {
            document.querySelector('input[name="buscar"]').focus();
        }
        
        // Confirmar si hay parámetros de búsqueda
        if (busqueda && <?php echo count($reportes); ?> === 0) {
            setTimeout(() => {
                mostrarAlerta(
                    `No se encontraron reportes que coincidan con "${busqueda}"`,
                    'Búsqueda sin resultados',
                    '🔍'
                );
            }, 500);
        }
    });
    
    // ============================
    // FUNCIÓN PARA FORMATEAR FECHA
    // ============================
    function formatearFecha(fecha) {
        if (!fecha) return 'No especificada';
        const [year, month, day] = fecha.split('-');
        return `${day}/${month}/${year}`;
    }
    </script>
</body>
</html>