<?php
// -----------------------------------------------------------
// planificacion.php - Gestión de Planificación Académica
// -----------------------------------------------------------
session_start();
include 'connection.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Determinar dashboard según rol
$dashboard = "dashboard.php";
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

// ==============================================
// PROCESAR ACCIONES (SIN MENSAJES DIRECTO EN NAVEGADOR)
// ==============================================

// 1. Procesar activar/desactivar
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    if ($id > 0 && ($accion === 'activar' || $accion === 'desactivar')) {
        $estado = $accion === 'activar' ? 'ACTIVO' : 'INACTIVO';
        $query = "UPDATE planificacion SET estado = ? WHERE IDPLA = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("si", $estado, $id);
            if ($stmt->execute()) {
                $_SESSION['toast_tipo'] = 'success';
                $_SESSION['toast_mensaje'] = $accion === 'activar' ? 
                    '✅ Período activado correctamente' : 
                    '✅ Período desactivado correctamente';
            } else {
                $_SESSION['toast_tipo'] = 'error';
                $_SESSION['toast_mensaje'] = '❌ Error al realizar la acción';
            }
            $stmt->close();
        }
        header("Location: planificacion.php");
        exit();
    }
}

// 2. Procesar creación de nuevo período
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_periodo'])) {
    $DESCRIP = trim($_POST['DESCRIP'] ?? '');
    $FECINI = $_POST['FECINI'] ?? '';
    $FECCUL = $_POST['FECCUL'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';
    
    $errores = [];
    
    // Validaciones
    if (empty($DESCRIP)) $errores[] = "⚠️ La descripción es obligatoria";
    if (empty($FECINI)) $errores[] = "⚠️ La fecha de inicio es obligatoria";
    if (empty($FECCUL)) $errores[] = "⚠️ La fecha de finalización es obligatoria";
    if ($FECCUL && $FECINI && $FECCUL <= $FECINI) $errores[] = "⚠️ La fecha de fin debe ser posterior a la fecha de inicio";
    
    if (empty($errores)) {
        $query = "INSERT INTO planificacion (DESCRIP, FECINI, FECCUL, estado) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ssss", $DESCRIP, $FECINI, $FECCUL, $estado);
            if ($stmt->execute()) {
                $_SESSION['toast_tipo'] = 'success';
                $_SESSION['toast_mensaje'] = '✅ Período creado correctamente';
            } else {
                $_SESSION['toast_tipo'] = 'error';
                $_SESSION['toast_mensaje'] = '❌ Error al crear el período';
            }
            $stmt->close();
        }
        
        header("Location: planificacion.php");
        exit();
    } else {
        $_SESSION['errores_formulario'] = $errores;
        $_SESSION['datos_formulario'] = $_POST;
    }
}

// Variables para toasts
$toast_tipo = '';
$toast_mensaje = '';
$js_errores_formulario = [];
$datos_formulario = [];

if (isset($_SESSION['toast_tipo'])) {
    $toast_tipo = $_SESSION['toast_tipo'];
    $toast_mensaje = $_SESSION['toast_mensaje'];
    unset($_SESSION['toast_tipo'], $_SESSION['toast_mensaje']);
}

if (isset($_SESSION['errores_formulario'])) {
    $js_errores_formulario = $_SESSION['errores_formulario'];
    unset($_SESSION['errores_formulario']);
}

if (isset($_SESSION['datos_formulario'])) {
    $datos_formulario = $_SESSION['datos_formulario'];
    unset($_SESSION['datos_formulario']);
}

// ==============================================
// CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA
// ==============================================

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Procesar búsqueda
$busqueda = '';
$where_clause = '';
$parametros_busqueda = [];

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $where_clause = " WHERE (p.DESCRIP LIKE ?)";
    $parametros_busqueda = array_fill(0, 1, "%$busqueda%");
}

// Obtener datos
$total_filas = 0;
$planificaciones = [];

// Contar total de registros
$query_contar = "SELECT COUNT(*) as total FROM planificacion p $where_clause";
if ($stmt_contar = $conn->prepare($query_contar)) {
    if (!empty($parametros_busqueda)) {
        $stmt_contar->bind_param("s", ...$parametros_busqueda);
    }
    $stmt_contar->execute();
    $result_contar = $stmt_contar->get_result();
    if ($row = $result_contar->fetch_assoc()) {
        $total_filas = $row['total'];
    }
    $stmt_contar->close();
}

// Calcular total de páginas
$total_paginas = ceil($total_filas / $registros_por_pagina);
if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

// Obtener registros con paginación
$query = "
    SELECT 
        p.IDPLA,
        p.DESCRIP,
        DATE_FORMAT(p.FECINI, '%d/%m/%Y') as FECINI_FORMAT,
        DATE_FORMAT(p.FECCUL, '%d/%m/%Y') as FECCUL_FORMAT,
        p.FECINI,
        p.FECCUL,
        p.estado
    FROM planificacion p
    $where_clause
    ORDER BY p.FECINI DESC, p.IDPLA DESC
    LIMIT ? OFFSET ?
";

if ($stmt = $conn->prepare($query)) {
    if (!empty($parametros_busqueda)) {
        $parametros = array_merge($parametros_busqueda, [$registros_por_pagina, $offset]);
        $tipos = str_repeat('s', count($parametros_busqueda)) . 'ii';
        $stmt->bind_param($tipos, ...$parametros);
    } else {
        $stmt->bind_param('ii', $registros_por_pagina, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $planificaciones[] = $row;
        }
    }
    
    $stmt->close();
}

// Función para calcular días restantes
function calcularDiasRestantes($fechaFin) {
    if (empty($fechaFin)) return 0;
    try {
        $hoy = new DateTime();
        $fin = new DateTime($fechaFin);
        
        if ($fin < $hoy) return 0;
        
        $diferencia = $hoy->diff($fin);
        return $diferencia->days;
    } catch (Exception $e) {
        return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Planificación Período Académico</title>
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
        
        .header-actions {
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
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 91, 187, 0.2);
        }
        
        .btn.success {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }
        
        .btn.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .btn.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .btn.small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        /* Tabla */
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            border: 1px solid #e1e5eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            padding: 15px;
            text-align: left;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .acciones {
            display: flex;
            gap: 8px;
        }
        
        /* Formulario */
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            min-width: 300px;
            max-width: 400px;
            padding: 15px 20px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease;
            transform: translateX(0);
            opacity: 1;
            transition: all 0.3s ease;
        }
        
        .toast.success {
            background: linear-gradient(135deg, #28a745, #218838);
            border-left: 5px solid #1e7e34;
        }
        
        .toast.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border-left: 5px solid #bd2130;
        }
        
        .toast.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            border-left: 5px solid #d39e00;
        }
        
        .toast.hiding {
            transform: translateX(100%);
            opacity: 0;
        }
        
        .toast-content {
            flex: 1;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .toast-close {
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            margin-left: 15px;
            opacity: 0.8;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Modal de confirmación */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9998;
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .modal-icon {
            font-size: 2rem;
        }
        
        .modal-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin: 0;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .search-container {
                flex-wrap: wrap;
            }
            
            .search-input {
                width: 100%;
            }
            
            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .toast {
                min-width: 250px;
                max-width: 90vw;
            }
        }
    </style>
</head>
<body>
    <!-- Contenedor para toasts -->
    <div id="toast-container" class="toast-container"></div>
    
    <!-- Modal de confirmación -->
    <div id="modal-confirm" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">⚠️</div>
                <h3 class="modal-title">Confirmar acción</h3>
            </div>
            <div class="modal-content" id="modal-message">
                ¿Está seguro de realizar esta acción?
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="cancelarAccion()">Cancelar</button>
                <button type="button" class="btn danger" id="confirm-button" onclick="ejecutarAccion()">Confirmar</button>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h1>📅 Planificación del Período Académico</h1>
        
        <div class="header-actions">
            <div class="contador">
                📋 <?php echo $total_filas; ?> períodos registrados
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <form method="GET" action="" class="search-container">
                    <input type="text" 
                           name="buscar" 
                           class="search-input" 
                           placeholder="Buscar por descripción..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn">🔍 Buscar</button>
                    <?php if (!empty($busqueda)): ?>
                        <a href="planificacion.php" class="btn secondary">🗑️ Limpiar</a>
                    <?php endif; ?>
                </form>
                
                <button type="button" class="btn success" onclick="mostrarFormulario()">
                    ➕ Nuevo Período
                </button>
            </div>
        </div>
        
        <!-- Formulario para crear nuevo período -->
        <div id="formulario-nuevo" class="form-container" style="display: none;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #005bbb;">
                📅 Crear Nuevo Período Académico
            </h3>
            
            <form id="form-crear-periodo" method="POST" action="">
                <input type="hidden" name="crear_periodo" value="1">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Descripción *</label>
                        <input type="text" 
                               name="DESCRIP" 
                               class="form-control"
                               value="<?php echo isset($datos_formulario['DESCRIP']) ? htmlspecialchars($datos_formulario['DESCRIP']) : ''; ?>"
                               placeholder="Ej: PAP I 2025"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha Inicio *</label>
                        <input type="date" 
                               name="FECINI" 
                               class="form-control"
                               value="<?php echo isset($datos_formulario['FECINI']) ? htmlspecialchars($datos_formulario['FECINI']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha Fin *</label>
                        <input type="date" 
                               name="FECCUL" 
                               class="form-control"
                               value="<?php echo isset($datos_formulario['FECCUL']) ? htmlspecialchars($datos_formulario['FECCUL']) : ''; ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group" style="max-width: 200px;">
                    <label class="form-label">Estado *</label>
                    <select name="estado" class="form-control">
                        <option value="ACTIVO" <?php echo (!isset($datos_formulario['estado']) || $datos_formulario['estado'] === 'ACTIVO') ? 'selected' : ''; ?>>ACTIVO</option>
                        <option value="INACTIVO" <?php echo (isset($datos_formulario['estado']) && $datos_formulario['estado'] === 'INACTIVO') ? 'selected' : ''; ?>>INACTIVO</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn success">💾 Guardar Período</button>
                    <button type="button" class="btn secondary" onclick="ocultarFormulario()">✖ Cancelar</button>
                </div>
            </form>
        </div>
        
        <!-- Tabla de planificaciones -->
        <div class="table-container">
            <?php if (count($planificaciones) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>DESCRIPCIÓN</th>
                            <th>FECHA DE INICIO</th>
                            <th>FECHA DE FINALIZACIÓN</th>
                            <th>DÍAS RESTANTES</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planificaciones as $plan): 
                            $dias_restantes = calcularDiasRestantes($plan['FECCUL']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plan['DESCRIP']); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo $plan['FECINI_FORMAT']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $dias_restantes > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $plan['FECCUL_FORMAT']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($plan['estado'] === 'ACTIVO' && $dias_restantes > 0): ?>
                                    <span class="badge badge-warning">
                                        <?php echo $dias_restantes; ?> días
                                    </span>
                                <?php elseif ($plan['estado'] === 'INACTIVO'): ?>
                                    <span class="badge badge-danger">❌ Inactivo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Finalizado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $plan['estado'] === 'ACTIVO' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $plan['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="acciones">
                                    <?php if ($plan['estado'] === 'ACTIVO'): ?>
                                        <button type="button" 
                                                class="btn small danger"
                                                onclick="mostrarConfirmacion('desactivar', <?php echo $plan['IDPLA']; ?>, '<?php echo addslashes($plan['DESCRIP']); ?>')">
                                            ❌ Desactivar
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn small success"
                                                onclick="mostrarConfirmacion('activar', <?php echo $plan['IDPLA']; ?>, '<?php echo addslashes($plan['DESCRIP']); ?>')">
                                            ✅ Activar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📅</div>
                    <h3>No hay períodos académicos registrados</h3>
                    <p style="color: #6c757d; margin-bottom: 20px;">
                        <?php if (!empty($busqueda)): ?>
                            No hay períodos que coincidan con "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php else: ?>
                            Comienza agregando el período académico.
                        <?php endif; ?>
                    </p>
                    <button class="btn success" onclick="mostrarFormulario()">➕ Agregar período</button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; margin-top: 30px; gap: 10px; flex-wrap: wrap;">
            <span style="color: #6c757d; margin-right: 20px;">
                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
            </span>
            
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=1<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="btn small">«« Primera</a>
            <?php endif; ?>
            
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="btn small">« Anterior</a>
            <?php endif; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="btn small">Siguiente »</a>
            <?php endif; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="btn small">Última »»</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align: right; margin-top: 30px;">
            <a href="<?php echo $dashboard; ?>" class="btn secondary">Volver</a>
        </div>
    </div>

    <script>
    // Variables globales para la confirmación
    let accionConfirmada = null;
    let idConfirmado = null;
    let descripcionConfirmada = null;
    
    // ============================
    // SISTEMA DE TOASTS
    // ============================
    
    function mostrarToast(mensaje, tipo = 'success', duracion = 5000) {
        const container = document.getElementById('toast-container');
        
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        
        const content = document.createElement('div');
        content.className = 'toast-content';
        content.textContent = mensaje;
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.textContent = '×';
        closeBtn.onclick = () => ocultarToast(toast);
        
        toast.appendChild(content);
        toast.appendChild(closeBtn);
        container.appendChild(toast);
        
        // Auto-eliminar después de la duración
        setTimeout(() => {
            if (toast.parentNode) {
                ocultarToast(toast);
            }
        }, duracion);
    }
    
    function ocultarToast(toast) {
        toast.classList.add('hiding');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
    
    // ============================
    // MODAL DE CONFIRMACIÓN
    // ============================
    
    function mostrarConfirmacion(accion, id, descripcion) {
        const mensaje = accion === 'activar' 
            ? `¿Está seguro de activar el período "${descripcion}"?`
            : `¿Está seguro de desactivar el período "${descripcion}"?`;
        
        document.getElementById('modal-message').textContent = mensaje;
        document.getElementById('modal-confirm').classList.add('active');
        
        // Guardar datos para la confirmación
        accionConfirmada = accion;
        idConfirmado = id;
        descripcionConfirmada = descripcion;
        
        // Cambiar color del botón según acción
        const btnConfirm = document.getElementById('confirm-button');
        btnConfirm.className = accion === 'activar' ? 'btn success' : 'btn danger';
        btnConfirm.textContent = accion === 'activar' ? '✅ Activar' : '❌ Desactivar';
    }
    
    function cancelarAccion() {
        document.getElementById('modal-confirm').classList.remove('active');
        accionConfirmada = null;
        idConfirmado = null;
        descripcionConfirmada = null;
    }
    
    function ejecutarAccion() {
        if (accionConfirmada && idConfirmado) {
            // Redirigir para ejecutar la acción
            window.location.href = `planificacion.php?accion=${accionConfirmada}&id=${idConfirmado}`;
        }
    }
    
    // Cerrar modal con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cancelarAccion();
        }
    });
    
    // ============================
    // FORMULARIO
    // ============================
    
    function mostrarFormulario() {
        document.getElementById('formulario-nuevo').style.display = 'block';
    }
    
    function ocultarFormulario() {
        document.getElementById('formulario-nuevo').style.display = 'none';
    }
    
    // Validación del formulario
    document.getElementById('form-crear-periodo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const descrip = this.DESCRIP.value.trim();
        const fecini = this.FECINI.value;
        const feccul = this.FECCUL.value;
        
        let errores = [];
        
        if (!descrip) errores.push('La descripción es obligatoria');
        if (!fecini) errores.push('La fecha de inicio es obligatoria');
        if (!feccul) errores.push('La fecha de finalización es obligatoria');
        
        if (fecini && feccul && feccul <= fecini) {
            errores.push('La fecha de fin debe ser posterior a la fecha de inicio');
        }
        
        if (errores.length > 0) {
            errores.forEach(error => {
                mostrarToast('⚠️ ' + error, 'warning', 4000);
            });
            return false;
        }
        
        // Mostrar toast de procesando
        mostrarToast('⏳ Procesando...', 'info');
        
        // Enviar formulario
        this.submit();
    });
    
    // ============================
    // INICIALIZACIÓN
    // ============================
    
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar toasts desde PHP
        <?php if (!empty($toast_mensaje)): ?>
            setTimeout(() => {
                mostrarToast('<?php echo addslashes($toast_mensaje); ?>', '<?php echo $toast_tipo; ?>');
            }, 300);
        <?php endif; ?>
        
        // Mostrar errores del formulario
        <?php foreach ($js_errores_formulario as $error): ?>
            setTimeout(() => {
                mostrarToast('<?php echo addslashes($error); ?>', 'warning');
            }, 500);
        <?php endforeach; ?>
        
        // Auto-mostrar formulario si hay errores
        <?php if (!empty($js_errores_formulario)): ?>
            mostrarFormulario();
        <?php endif; ?>
        
        // Configurar fechas mínimas
        const hoy = new Date().toISOString().split('T')[0];
        const fechaInicio = document.querySelector('input[name="FECINI"]');
        const fechaFin = document.querySelector('input[name="FECCUL"]');
        
        if (fechaInicio) {
            fechaInicio.min = hoy;
            fechaInicio.addEventListener('change', function() {
                if (fechaFin) {
                    fechaFin.min = this.value;
                    if (fechaFin.value && fechaFin.value < this.value) {
                        fechaFin.value = this.value;
                    }
                }
            });
        }
        
        if (fechaFin && fechaInicio) {
            fechaFin.min = fechaInicio.value || hoy;
        }
        
        // Destacar resultados de búsqueda
        <?php if (!empty($busqueda)): ?>
            const cells = document.querySelectorAll('tbody td:first-child');
            const busquedaLower = '<?php echo addslashes(strtolower($busqueda)); ?>';
            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(busquedaLower)) {
                    cell.style.backgroundColor = '#fff3cd';
                    cell.style.fontWeight = '600';
                }
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>