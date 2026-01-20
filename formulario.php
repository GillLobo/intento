<?php
// -----------------------------------------------------------
// formulario.php
// -----------------------------------------------------------

include 'connection.php';
session_start();

// Iniciar buffer de salida para permitir redirecciones incluso si hay salidas accidentales
if (!ob_get_level()) ob_start();

// Configuración para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirigir si no hay sesión
if (!isset($_SESSION['usuario_rol'])) {
    header('Location: login.php');
    exit();
}

$dashboard = ($_SESSION['usuario_rol'] === 'Administrador') 
    ? "dashboard_admin.php" 
    : "dashboard.php";

$mensaje = '';
$error = false;
$formulario_id = null;
$redirigir_a_pdf = false; // NUEVA VARIABLE PARA CONTROLAR REDIRECCIÓN

// Variables para persistencia
$para = $de_nombre = $asunto = $fecha = $parrafo = '';
$comunicado = '0001-UJGH-DIP'; // Valor por defecto
$pap = 0;
$cedulas_post = $docentes_post = $asignaturas_post = $horas_post = [];

// -----------------------------------------------------------
// 1. OBTENER OPCIONES DE PAP
// -----------------------------------------------------------
$pap_options = [];
$q = $conn->query("SELECT IDPLA, DESCRIP FROM planificacion ORDER BY IDPLA DESC");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $pap_options[] = $row;
    }
}

// -----------------------------------------------------------
// 2. GENERAR NÚMERO DE COMUNICADO
// -----------------------------------------------------------
function generarNuevoComunicado($conn) {
    // Intentar obtener el último comunicado de la base de datos
    $ultimo_numero = 0;
    
    $query = "SELECT comunicado FROM formularios ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ultimo_comunicado = $row['comunicado'];
        
        // Extraer el número del formato: 0001-UJGH-DIP
        if (preg_match('/^(\d{4})-UJGH-DIP$/', $ultimo_comunicado, $matches)) {
            $ultimo_numero = intval($matches[1]);
        }
    }
    
    // Incrementar el número
    $nuevo_numero = $ultimo_numero + 1;
    
    // Formatear a 4 dígitos con ceros a la izquierda
    return str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT) . '-UJGH-DIP';
}

// Generar el comunicado automáticamente
$comunicado = generarNuevoComunicado($conn);

// -----------------------------------------------------------
// 3. PROCESAR FORMULARIO
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    // Sanitizar datos generales
    $para      = trim($_POST['para'] ?? '');
    $de_nombre = trim($_POST['de_nombre'] ?? '');
    $asunto    = trim($_POST['asunto'] ?? '');
    $fecha     = trim($_POST['fecha'] ?? '');
    $com       = trim($_POST['comunicado'] ?? $comunicado);
    $pap       = intval($_POST['pap'] ?? 0);
    $parrafo   = trim($_POST['parrafo'] ?? '');

    // Datos de docentes
    $cedulas_post     = $_POST['cedula'] ?? [];
    $docentes_post    = $_POST['docente'] ?? [];
    $asignaturas_post = $_POST['asignatura'] ?? [];
    $horas_post       = $_POST['horas'] ?? [];

    // Validación de campos
    $validacion_pasa = true;
    
    // Validar campos PARA y DE
    $regex_nombre = '/^[a-zA-ZÁÉÍÓÚÑáéíóúñ .,-]+$/u';
    if (!preg_match($regex_nombre, $para)) {
        $mensaje = "⚠ El campo PARA solo permite letras, espacios y puntuación básica.";
        $validacion_pasa = false;
    } elseif (!preg_match($regex_nombre, $de_nombre)) {
        $mensaje = "⚠ El campo DE solo permite letras, espacios y puntuación básica.";
        $validacion_pasa = false;
    }
    
    // Validar campos obligatorios
    if ($validacion_pasa) {
        $campos_obligatorios = [
            'PARA' => $para,
            'DE' => $de_nombre,
            'ASUNTO' => $asunto,
            'FECHA' => $fecha,
            'PERIODO' => $pap,
            'PÁRRAFO' => $parrafo
        ];
        
        foreach ($campos_obligatorios as $nombre => $valor) {
            if (empty($valor)) {
                $mensaje = "⚠ Complete todos los campos obligatorios del encabezado.";
                $validacion_pasa = false;
                break;
            }
        }
    }

    // Validar que haya al menos un docente completo
    if ($validacion_pasa) {
        $hayDocenteValido = false;
        $docCount = count($cedulas_post);
        
        for ($i = 0; $i < $docCount; $i++) {
            $c = trim($cedulas_post[$i] ?? '');
            $d = trim($docentes_post[$i] ?? '');
            $a = trim($asignaturas_post[$i] ?? '');
            
            if (!empty($c) && !empty($d) && !empty($a)) {
                $hayDocenteValido = true;
                break;
            }
        }
        
        if (!$hayDocenteValido) {
            $mensaje = "⚠ Debe completar al menos un docente con todos sus datos.";
            $validacion_pasa = false;
        }
    }

    // Insertar en BD si pasó validación
    if ($validacion_pasa) {
        $conn->begin_transaction();
        
        try {
            // DEBUG: Verificar datos antes de insertar
            error_log("Insertando formulario con comunicado: " . $com);
            
            // Antes de insertar, reservar un número de comunicado dentro de la transacción
            $com_a_usar = $com;

            // Obtener el último comunicado bloqueando la fila para evitar condiciones de carrera
            $lastRes = $conn->query("SELECT comunicado FROM formularios ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $ultimo_numero = 0;
            if ($lastRes && $rowLast = $lastRes->fetch_assoc()) {
                $ultimo_comunicado = $rowLast['comunicado'];
                if (preg_match('/^(\d{4})-UJGH-DIP$/', $ultimo_comunicado, $m)) {
                    $ultimo_numero = intval($m[1]);
                }
            }

            $sugerido = str_pad($ultimo_numero + 1, 4, '0', STR_PAD_LEFT) . '-UJGH-DIP';

            // Si el comunicado enviado por formulario ya existe, o no se proporcionó, usar el sugerido
            if (empty($com_a_usar)) {
                $com_a_usar = $sugerido;
            } else {
                $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM formularios WHERE comunicado = ?");
                if ($chk) {
                    $chk->bind_param("s", $com_a_usar);
                    $chk->execute();
                    $resChk = $chk->get_result();
                    $rowChk = $resChk->fetch_assoc();
                    $cnt = intval($rowChk['cnt'] ?? 0);
                    $chk->close();
                    if ($cnt > 0) {
                        // Ya existe, usar el sugerido
                        $com_a_usar = $sugerido;
                    }
                } else {
                    // Si falla el prepare, caemos al sugerido por seguridad
                    $com_a_usar = $sugerido;
                }
            }

            // Insertar encabezado con el comunicado reservado
            $stmt = $conn->prepare("INSERT INTO formularios (para, de_nombre, asunto, fecha, comunicado, pap_id, parrafo) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $conn->error);
            }

            $stmt->bind_param("sssssis", $para, $de_nombre, $asunto, $fecha, $com_a_usar, $pap, $parrafo);

            if (!$stmt->execute()) {
                throw new Exception("Error guardando formulario: " . $stmt->error);
            }
            
            $formulario_id = $conn->insert_id;
            $stmt->close();
            
            // DEBUG: Verificar ID insertado
            error_log("Formulario insertado con ID: " . $formulario_id);

            // Insertar docentes
            $stmt2 = $conn->prepare("INSERT INTO formulario_docentes (formulario_id, cedula, docente, asignatura, horas) VALUES (?, ?, ?, ?, ?)");

            if (!$stmt2) {
                throw new Exception("Error preparando consulta de docentes: " . $conn->error);
            }
            
            $docCount = count($cedulas_post);
            $insertados = 0;
            
            for ($i = 0; $i < $docCount; $i++) {
                $c = trim($cedulas_post[$i] ?? '');
                $d = trim($docentes_post[$i] ?? '');
                $a = trim($asignaturas_post[$i] ?? '');
                $h = intval($horas_post[$i] ?? 48);
                
                // Validar fila completa
                if (empty($c) || empty($d) || empty($a)) {
                    continue;
                }
                
                if ($h <= 0) $h = 48;
                
                $stmt2->bind_param("isssi", $formulario_id, $c, $d, $a, $h);
                if (!$stmt2->execute()) {
                    throw new Exception("Error guardando docente #" . ($i+1));
                }
                $insertados++;
            }
            
            $stmt2->close();
            
            if ($insertados === 0) {
                throw new Exception("No se guardó ningún docente válido.");
            }
            
            $conn->commit();
            
            // CORRECCIÓN PRINCIPAL: EN VEZ DE ECHO, USAR REDIRECCIÓN
            if ($formulario_id) {
                // Guardar datos en sesión para mostrar después del PDF
                $_SESSION['mensaje_exito'] = "✅ Formulario guardado correctamente. Se guardaron {$insertados} docente(s). Comunicado: {$com_a_usar}";
                $_SESSION['comunicado_generado'] = $com_a_usar;
                $_SESSION['ultimo_formulario_id'] = $formulario_id;
                
                // ACTIVAR BANDERA DE REDIRECCIÓN
                $redirigir_a_pdf = true;
                
                // No poner mensaje aquí, se mostrará después del PDF
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "❌ Error: " . $e->getMessage();
            $error = true;
        }
    } else {
        $error = true;
    }
}

// -----------------------------------------------------------
// 4. REDIRECCIÓN INMEDIATA A PDF
// -----------------------------------------------------------
if ($redirigir_a_pdf && isset($_SESSION['ultimo_formulario_id'])) {
    $pdf_id = $_SESSION['ultimo_formulario_id'];

    // Limpiar buffer de salida
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Enviar una respuesta que abra el PDF en una nueva pestaña y luego regrese al formulario
    $pdf_url = 'generar_pdf.php?id=' . urlencode($pdf_id);
    // Nota: usamos window.open para abrir en nueva pestaña y luego redirigimos la pestaña actual
    echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Generando PDF</title></head><body>";
    echo "<script>
            try {
                window.open('" . $pdf_url . "', '_blank');
            } catch(e) {}
            // Volver al formulario para mostrar el mensaje almacenado en sesión
            window.location.replace('formulario.php');
          </script>";
    echo "</body></html>";
    exit();
}

// -----------------------------------------------------------
// 5. MOSTRAR MENSAJE DE ÉXITO DESPUÉS DE PDF
// -----------------------------------------------------------
if (isset($_SESSION['mensaje_exito']) && !$redirigir_a_pdf) {
    $mensaje = $_SESSION['mensaje_exito'];
    $clase_mensaje = 'exito';
    
    // Limpiar la variable de sesión después de mostrarla
    unset($_SESSION['mensaje_exito'], $_SESSION['comunicado_generado'], $_SESSION['ultimo_formulario_id']);
}

$clase_mensaje = $error ? 'error' : (!empty($mensaje) ? 'exito' : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Formulario de Comunicado</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f7f8fb 0%, #eef1f7 100%);
            padding: 24px;
            min-height: 100vh;
        }
        .card {
            max-width: 1000px;
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
        h1:before { content: "📄"; font-size: 1.6rem; }
        label {
            display: block;
            margin-top: 18px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        label.required:after { content: " *"; color: #e53935; }
        input, textarea, select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #fff;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #005bbb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.1);
        }
        input[readonly] { background-color: #f8f9fa; color: #6c757d; cursor: not-allowed; }
        textarea { min-height: 120px; resize: vertical; line-height: 1.5; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            align-items: start;
        }
        .full { grid-column: 1 / -1; }
        .btn {
            background: linear-gradient(135deg, #005bbb 0%, #004a9c 100%);
            color: #fff;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 91, 187, 0.2); }
        .btn.secondary { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); }
        .btn.secondary:hover { box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2); }
        .btn.warning { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; }
        .btn.warning:hover { box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2); }
        .mensaje-container { position: relative; margin-bottom: 25px; }
        .mensaje {
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .exito {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #b1dfbb;
        }
        .error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #f1b0b7;
        }
        .mensaje-icon {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .exito .mensaje-icon { background-color: #28a745; color: white; }
        .error .mensaje-icon { background-color: #dc3545; color: white; }
        .cuadro {
            margin-top: 20px;
            border: 2px solid #e1e5eb;
            padding: 20px;
            border-radius: 12px;
            background: #f8f9fa;
            transition: border-color 0.3s;
        }
        .cuadro:hover { border-color: #b8d4ff; }
        .fila {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: start;
        }
        .label-small {
            font-size: 0.85rem;
            color: #495057;
            margin-bottom: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .label-small:before { content: "•"; color: #005bbb; }
        .note {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 15px;
            padding: 12px;
            background: #f0f7ff;
            border-radius: 8px;
            border-left: 4px solid #005bbb;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .note:before { content: "💡"; font-size: 1.1rem; flex-shrink: 0; }
        .btn-eliminar-fila {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            padding: 10px 18px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .btn-eliminar-fila:hover { box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2); }
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #eef1f7;
        }
        .actions-left, .actions-right { display: flex; gap: 10px; }
        @media (max-width: 768px) {
            body { padding: 15px; }
            .card { padding: 20px; }
            .grid, .fila { grid-template-columns: 1fr; }
            .actions-bar { flex-direction: column; gap: 15px; }
            .actions-left, .actions-right { width: 100%; }
            .actions-bar .btn { width: 100%; justify-content: center; }
        }
        .fade-out { animation: fadeOut 0.5s ease-out forwards; }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); height: 0; padding: 0; margin: 0; overflow: hidden; }
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23005bbb' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding-right: 40px;
        }
        .error-input { border-color: #dc3545 !important; background-color: #fff5f5; }
        .error-text {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .error-text:before { content: "⚠"; }
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
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
        .modal-overlay.active .modal { transform: translateY(0); }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f7ff;
        }
        .modal-icon { font-size: 2rem; }
        .modal-title { font-size: 1.3rem; color: #2c3e50; margin: 0; }
        .modal-content { margin-bottom: 25px; color: #495057; line-height: 1.6; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 15px; }
        .comunicado-info {
            background-color: #e3f2fd;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #0d47a1;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Formulario de Comunicado</h1>

        <!-- Toast container (mensajes modernos) -->
        <div id="toast-container" style="position: fixed; top: 18px; right: 18px; z-index: 9999; display:flex; flex-direction: column; align-items: flex-end;"></div>

        <form id="formComunicado" method="POST" novalidate>
            <div class="grid">
                <div>
                    <label for="para" class="required">PARA</label>
                    <input type="text" id="para" name="para" required 
                           value="<?= htmlspecialchars($para) ?>"
                           placeholder="Nombre del destinatario">
                </div>

                <div>
                    <label for="de_nombre" class="required">DE</label>
                    <input type="text" id="de_nombre" name="de_nombre" required 
                           value="<?= htmlspecialchars($de_nombre) ?>"
                           placeholder="Nombre del remitente">
                </div>

                <div class="full">
                    <label for="asunto" class="required">ASUNTO</label>
                    <input type="text" id="asunto" name="asunto" required 
                           value="<?= htmlspecialchars($asunto) ?>"
                           placeholder="Ej: Reporte de pagos del período académico">
                </div>

                <div>
                    <label for="fecha" class="required">FECHA</label>
                    <input type="date" id="fecha" name="fecha" required 
                           value="<?= htmlspecialchars($fecha ?: date('Y-m-d')) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>

                <div>
                    <label for="comunicado">COMUNICADO</label>
                    <input type="text" id="comunicado" name="comunicado" 
                           value="<?= htmlspecialchars($comunicado) ?>" readonly>
                    <div class="comunicado-info">
                        Comunicado número: <strong><?= htmlspecialchars($comunicado) ?></strong>
                    </div>
                </div>

                <div class="full">
                    <label for="pap" class="required">PERIODO ACADÉMICO (PAP)</label>
                    <select id="pap" name="pap" required>
                        <option value="">-- Seleccione el periodo académico --</option>
                        <?php foreach ($pap_options as $p): ?>
                            <option value="<?= $p['IDPLA'] ?>" 
                                <?= ($pap == $p['IDPLA']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['DESCRIP']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="full">
                    <label for="parrafo" class="required">Párrafo del comunicado</label>
                    <textarea id="parrafo" name="parrafo" rows="5" required
                              placeholder="Escribe aquí el contenido del comunicado..."><?= htmlspecialchars($parrafo) ?></textarea>
                </div>

                <!-- SECCIÓN DOCENTES -->
                <div class="full">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <div style="font-size:1.2rem; color:#005bbb;">📚</div>
                        <label style="font-size:1.1rem; color:#005bbb; margin:0;">Información del Docente</label>
                    </div>
                    
                    <div class="note">
                        <strong>Nota:</strong> Ingrese la cédula y presione la tecla (TAB) para buscar automáticamente el nombre del profesor 
                        y sus materias asignadas. El sistema buscará automáticamente cuando salga del campo.
                    </div>

                    <div id="contenedor-docentes">
                        <?php
                        $num_filas = max(1, count($cedulas_post));
                        for ($i = 0; $i < $num_filas; $i++):
                        ?>
                        <div class="cuadro docente-fila" data-index="<?= $i ?>">
                            <div class="fila">
                                <div>
                                    <div class="label-small">CÉDULA DE IDENTIDAD</div>
                                    <input type="number" name="cedula[]" class="ced-input" 
                                           value="<?= htmlspecialchars($cedulas_post[$i] ?? '') ?>"
                                           placeholder="Ej: 12345678" min="1000000" max="9999999999"
                                           onblur="buscarDocente(this)">
                                </div>

                                <div>
                                    <div class="label-small">DOCENTE</div>
                                    <input type="text" name="docente[]" class="doc-input" 
                                           value="<?= htmlspecialchars($docentes_post[$i] ?? '') ?>"
                                           readonly>
                                </div>

                                <div>
                                    <div class="label-small">ASIGNATURA</div>
                                    <select name="asignatura[]" class="asig-input">
                                        <option value="">-- Seleccione asignatura --</option>
                                        <?php if (!empty($asignaturas_post[$i])): ?>
                                            <option value="<?= htmlspecialchars($asignaturas_post[$i]) ?>" selected>
                                                <?= htmlspecialchars($asignaturas_post[$i]) ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div>
                                    <div class="label-small">CANTIDAD DE HORAS</div>
                                    <input type="number" name="horas[]" class="horas-input" 
                                           min="1" max="200" value="<?= $horas_post[$i] ?? 48 ?>">
                                </div>
                            </div>

                            <?php if ($i > 0): ?>
                            <div style="margin-top:15px; text-align:right;">
                                <button type="button" class="btn secondary btn-eliminar-fila">
                                    🗑 Eliminar fila
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div style="margin-top:25px; text-align:center;">
                        <button type="button" class="btn" id="btn-agregar-docente">
                            ➕ Agregar otro docente
                        </button>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="full actions-bar">
                    <div class="actions-left">
                        <a href="<?= $dashboard ?>" class="btn secondary">Volver</a>
                        <button type="button" class="btn warning" id="btn-limpiar">
                            Limpiar
                        </button>
                    </div>
                    <div class="actions-right">
                        <button type="submit" name="guardar" class="btn" id="btn-submit">
                            📄 Generar Reporte
                        </button>

                        <a href="reportes.php" class="btn" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                            📊 Historial de Reportes 
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon" id="modal-icon">⚠</div>
                <h3 class="modal-title" id="modal-title">Confirmar acción</h3>
            </div>
            <div class="modal-content" id="modal-content">
                ¿Está seguro de realizar esta acción?
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" id="modal-cancel">Cancelar</button>
                <button type="button" class="btn" id="modal-confirm">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
    // ============================
    // 1. VARIABLES GLOBALES
    // ============================
    const modalOverlay = document.getElementById('modal-overlay');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');
    const modalIcon = document.getElementById('modal-icon');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm = document.getElementById('modal-confirm');
    let currentResolve = null;

    // ============================
    // 2. FUNCIONES BÁSICAS
    // ============================
    function mostrarError(input, mensaje) {
        input.classList.add('error-input');
        let errorDiv = input.parentNode.querySelector('.error-text');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-text';
            input.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = mensaje;
    }

    function limpiarError(input) {
        input.classList.remove('error-input');
        const errorDiv = input.parentNode.querySelector('.error-text');
        if (errorDiv) errorDiv.remove();
    }

    // ============================
    // 3. SISTEMA DE MODAL
    // ============================
    function showModal(options) {
        modalTitle.textContent = options.title || 'Confirmar acción';
        modalContent.textContent = options.message;
        modalIcon.textContent = options.icon || '⚠';
        
        if (options.type === 'alert') {
            modalCancel.style.display = 'none';
            modalConfirm.textContent = options.confirmText || 'Aceptar';
        } else {
            modalCancel.style.display = 'block';
            modalConfirm.textContent = options.confirmText || 'Aceptar';
        }
        
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        return new Promise((resolve) => { currentResolve = resolve; });
    }

    function hideModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
        if (currentResolve) {
            currentResolve(false);
            currentResolve = null;
        }
    }

    function customAlert(message, title = 'Información', icon = 'ℹ') {
        return showModal({
            title, message, icon,
            type: 'alert',
            confirmText: 'Aceptar'
        });
    }

    function customConfirm(message, title = 'Confirmar', icon = '❓') {
        return showModal({
            title, message, icon,
            type: 'confirm',
            cancelText: 'Cancelar',
            confirmText: 'Aceptar'
        });
    }

    modalCancel.addEventListener('click', hideModal);
    modalConfirm.addEventListener('click', () => {
        if (currentResolve) {
            currentResolve(true);
            currentResolve = null;
        }
        hideModal();
    });

    // Si hay mensaje en PHP, mostrarlo como toast en carga
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($mensaje)): ?>
            // Escapar comillas para JS
            mostrarToast('<?php echo addslashes($mensaje); ?>', '<?php echo $error ? 'error' : 'success'; ?>');
            <?php if (!$error && isset($_SESSION['comunicado_generado'])): ?>
                // Mostrar número de comunicado como toast secundario
                mostrarToast('Comunicado: <?php echo addslashes($_SESSION['comunicado_generado']); ?>', 'info', 3800);
                <?php unset($_SESSION['comunicado_generado']); ?>
            <?php endif; ?>
        <?php endif; ?>
    });

    // ============================
    // Sistema de toasts (para mensajes de sesión)
    // ============================
    function mostrarToast(mensaje, tipo = 'success', duration = 4500) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast-item ' + tipo;
        toast.style.minWidth = '260px';
        toast.style.marginTop = '10px';
        toast.style.padding = '12px 16px';
        toast.style.borderRadius = '10px';
        toast.style.color = '#fff';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.12)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.justifyContent = 'space-between';
        toast.style.gap = '12px';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';

        const text = document.createElement('div');
        text.style.flex = '1';
        text.textContent = mensaje;
        text.style.fontWeight = '600';
        text.style.fontSize = '0.95rem';

        const close = document.createElement('button');
        close.textContent = '✖';
        close.style.background = 'transparent';
        close.style.border = 'none';
        close.style.color = 'inherit';
        close.style.cursor = 'pointer';
        close.style.fontSize = '0.95rem';

        toast.appendChild(text);
        toast.appendChild(close);

        if (tipo === 'success') {
            toast.style.background = 'linear-gradient(135deg,#28a745,#218838)';
        } else if (tipo === 'error') {
            toast.style.background = 'linear-gradient(135deg,#dc3545,#c82333)';
        } else if (tipo === 'warning') {
            toast.style.background = 'linear-gradient(135deg,#ffc107,#e0a800)';
            toast.style.color = '#212529';
        } else {
            toast.style.background = 'linear-gradient(135deg,#17a2b8,#138496)';
        }

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        const remover = () => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 250);
        };

        close.addEventListener('click', remover);
        setTimeout(remover, duration);
    }

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) hideModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            hideModal();
        }
    });

    // ============================
    // 4. FUNCIONALIDAD DE BÚSQUEDA DE DOCENTES
    // ============================
    async function buscarDocente(inputCedula) {
        const ced = inputCedula.value.trim();
        
        // Si está vacío, limpiar campos
        if (!ced) {
            const fila = inputCedula.closest('.docente-fila');
            fila.querySelector('.doc-input').value = '';
            fila.querySelector('.asig-input').innerHTML = '<option value="">-- Seleccione asignatura --</option>';
            limpiarError(inputCedula);
            return;
        }
        
        // Validar formato de cédula
        if (ced.length < 6 || ced.length > 10) {
            mostrarError(inputCedula, 'La cédula debe tener entre 6 y 10 dígitos');
            return;
        }
        
        const fila = inputCedula.closest('.docente-fila');
        const docInput = fila.querySelector('.doc-input');
        const asigSelect = fila.querySelector('.asig-input');

        // Mostrar estado de carga
        docInput.value = 'Buscando...';
        docInput.className = 'doc-input loading';
        asigSelect.innerHTML = '<option value="">Buscando asignaturas...</option>';
        
        // Limpiar errores previos
        limpiarError(inputCedula);

            try {
            // Hacer la petición al servidor
            const response = await fetch('buscar_docente.php?cedula=' + encodeURIComponent(ced));
            
            if (!response.ok) {
                throw new Error('Error en la conexión con el servidor');
            }
            
            const data = await response.json();
            
            if (data.nombre === 'No encontrado' || !data.nombre || data.nombre.trim() === '') {
                docInput.value = '';
                docInput.className = 'doc-input';
                mostrarError(inputCedula, 'Docente no encontrado. Verifique la cédula.');
                asigSelect.innerHTML = '<option value="">Docente no encontrado</option>';
            } else {
                // Docente encontrado
                docInput.value = data.nombre;
                docInput.className = 'doc-input success';
                
                // Limpiar y llenar las asignaturas
                asigSelect.innerHTML = '';
                
                // Opción por defecto
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- Seleccione asignatura --';
                asigSelect.appendChild(defaultOption);
                
                if (data.asignaturas && data.asignaturas.length > 0) {
                    data.asignaturas.forEach(asignatura => {
                        const option = document.createElement('option');
                        option.value = asignatura;
                        option.textContent = asignatura;
                        asigSelect.appendChild(option);
                    });
                    
                    // Si solo hay una asignatura, seleccionarla automáticamente
                    if (data.asignaturas.length === 1) {
                        asigSelect.selectedIndex = 1;
                    }
                } else {
                    const noAsignaturasOption = document.createElement('option');
                    noAsignaturasOption.value = '';
                    noAsignaturasOption.textContent = 'No se encontraron asignaturas asignadas';
                    asigSelect.appendChild(noAsignaturasOption);
                }
                
                // Limpiar cualquier error previo
                limpiarError(inputCedula);
            }
        } catch (error) {
            console.error('Error en la búsqueda:', error);
            docInput.value = '';
            docInput.className = 'doc-input';
            asigSelect.innerHTML = '<option value="">Error al buscar docente</option>';
            mostrarError(inputCedula, 'Error en la conexión. Verifique su internet o contacte al administrador.');
        }
    }

    // ============================
    // 5. AGREGAR/ELIMINAR FILAS
    // ============================
    document.getElementById('btn-agregar-docente').addEventListener('click', function() {
        const contenedor = document.getElementById('contenedor-docentes');
        const filas = document.querySelectorAll('.docente-fila');
        const ultimaFila = filas[filas.length - 1];
        const nuevaFila = ultimaFila.cloneNode(true);
        
        // Limpiar valores
        const cedInput = nuevaFila.querySelector('.ced-input');
        const docInput = nuevaFila.querySelector('.doc-input');
        const asigSelect = nuevaFila.querySelector('.asig-input');
        const horasInput = nuevaFila.querySelector('.horas-input');
        
        cedInput.value = '';
        docInput.value = '';
        docInput.className = 'doc-input';
        asigSelect.innerHTML = '<option value="">-- Seleccione asignatura --</option>';
        horasInput.value = 48;
        
        // Asegurar que el evento onblur esté presente
        cedInput.setAttribute('onblur', 'buscarDocente(this)');
        
        // Mostrar botón eliminar
        const btnEliminar = nuevaFila.querySelector('.btn-eliminar-fila');
        if (btnEliminar) {
            btnEliminar.parentElement.style.display = 'block';
        }
        
        // Actualizar índice
        nuevaFila.dataset.index = filas.length;
        
        contenedor.appendChild(nuevaFila);
        
        // Scroll suave a la nueva fila
        nuevaFila.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Enfocar en la nueva cédula después de un breve delay
        setTimeout(() => {
            cedInput.focus();
        }, 300);
    });

    // Delegación de eventos para eliminar filas
    document.getElementById('contenedor-docentes').addEventListener('click', function(e) {
        const btnEliminar = e.target.closest('.btn-eliminar-fila');
        if (btnEliminar) {
            const fila = btnEliminar.closest('.docente-fila');
            const filas = document.querySelectorAll('.docente-fila');
            
            if (filas.length <= 1) {
                customAlert('Debe quedar al menos una fila de docente.', 'Advertencia', '⚠');
                return;
            }
            
            customConfirm('¿Está seguro de eliminar este docente del formulario?', 'Eliminar docente', '🗑')
                .then((confirmado) => {
                    if (confirmado) {
                        fila.remove();
                        // Reindexar filas restantes
                        document.querySelectorAll('.docente-fila').forEach((fila, index) => {
                            fila.dataset.index = index;
                        });
                    }
                });
        }
    });

    // ============================
    // 6. LIMPIAR FORMULARIO (CON NUEVO COMUNICADO)
    // ============================
    document.getElementById('btn-limpiar').addEventListener('click', async function() {
        const confirmado = await customConfirm(
            '¿Está seguro de limpiar todos los campos del formulario? Esta acción no se puede deshacer.', 
            'Limpiar formulario',
            '🧹'
        );
        
        if (confirmado) {
            await limpiarFormulario();
            await customAlert('Formulario limpiado correctamente. Se generó un nuevo número de comunicado.', 'Información', '✅');
        }
    });

    async function limpiarFormulario() {
        return new Promise((resolve) => {
            // Limpiar campos principales
            ['para', 'de_nombre', 'asunto', 'parrafo'].forEach(id => {
                const elem = document.getElementById(id);
                elem.value = '';
                limpiarError(elem);
            });
            
            document.getElementById('fecha').value = '<?= date("Y-m-d") ?>';
            document.getElementById('pap').selectedIndex = 0;
            
            // Obtener nuevo número de comunicado del servidor
            fetch('generar_nuevo_comunicado.php')
                .then(response => response.json())
                .then(data => {
                    // Aceptar tanto {success: true, comunicado: '...'} como {comunicado: '...'}
                    if (data && data.comunicado) {
                        document.getElementById('comunicado').value = data.comunicado;
                        console.log('Nuevo comunicado generado:', data.comunicado);
                    } else {
                        // Si falla, usar lógica local
                        generarComunicadoLocal();
                    }
                })
                .catch(() => {
                    // Si falla la petición, usar lógica local
                    generarComunicadoLocal();
                });
            
            // Limpiar primera fila de docente
            const primeraFila = document.querySelector('.docente-fila');
            if (primeraFila) {
                const cedInput = primeraFila.querySelector('.ced-input');
                const docInput = primeraFila.querySelector('.doc-input');
                const asigSelect = primeraFila.querySelector('.asig-input');
                const horasInput = primeraFila.querySelector('.horas-input');
                
                cedInput.value = '';
                docInput.value = '';
                docInput.className = 'doc-input';
                asigSelect.innerHTML = '<option value="">-- Seleccione asignatura --</option>';
                horasInput.value = 48;
                
                limpiarError(cedInput);
            }
            
            // Eliminar filas adicionales de docentes
            const filas = document.querySelectorAll('.docente-fila');
            for (let i = filas.length - 1; i > 0; i--) {
                filas[i].remove();
            }
            
            // Limpiar todos los errores visuales
            document.querySelectorAll('.error-input').forEach(input => {
                input.classList.remove('error-input');
            });
            document.querySelectorAll('.error-text').forEach(error => {
                error.remove();
            });
            
            // Enfocar en el primer campo después de un breve delay
            setTimeout(() => {
                document.getElementById('para').focus();
                resolve();
            }, 100);
        });
    }

    // Función auxiliar para generar comunicado localmente
    function generarComunicadoLocal() {
        const comunicadoActual = document.getElementById('comunicado').value;
        if (comunicadoActual) {
            // Extraer número y aumentar
            const match = comunicadoActual.match(/^(\d{4})-UJGH-DIP$/);
            if (match) {
                const numero = parseInt(match[1]) + 1;
                const nuevoComunicado = numero.toString().padStart(4, '0') + '-UJGH-DIP';
                document.getElementById('comunicado').value = nuevoComunicado;
            }
        }
    }

    // ============================
    // 7. VALIDACIÓN Y ENVÍO DEL FORMULARIO
    // ============================
    document.getElementById("formComunicado").addEventListener("submit", async function(e) {
        e.preventDefault();
        let isValid = true;
        
        // Validar campos básicos obligatorios
        const camposObligatorios = [
            {id: 'para', msg: 'El campo PARA es obligatorio'},
            {id: 'de_nombre', msg: 'El campo DE es obligatorio'},
            {id: 'asunto', msg: 'El ASUNTO es obligatorio'},
            {id: 'fecha', msg: 'La FECHA es obligatoria'},
            {id: 'pap', msg: 'Debe seleccionar un PERIODO ACADÉMICO'},
            {id: 'parrafo', msg: 'El PÁRRAFO es obligatorio'}
        ];
        
        camposObligatorios.forEach(campo => {
            const elem = document.getElementById(campo.id);
            if (!elem.value.trim()) {
                mostrarError(elem, campo.msg);
                isValid = false;
            } else {
                limpiarError(elem);
            }
        });
        
        // Validar fecha (puede ser futura con confirmación)
        const fechaInput = document.getElementById('fecha');
        const fechaSeleccionada = new Date(fechaInput.value);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        if (fechaSeleccionada > hoy) {
            const confirmado = await customConfirm(
                "⚠ La fecha seleccionada es futura. ¿Está seguro de continuar?", 
                "Fecha futura",
                "📅"
            );
            if (!confirmado) {
                fechaInput.focus();
                return false;
            }
        }
        
        // Validar docentes
        const cedulas = document.querySelectorAll('.ced-input');
        let hayDocenteCompleto = false;
        
        for (let i = 0; i < cedulas.length; i++) {
            const cedInput = cedulas[i];
            const fila = cedInput.closest('.docente-fila');
            const docInput = fila.querySelector('.doc-input');
            const asigSelect = fila.querySelector('.asig-input');
            
            const ced = cedInput.value.trim();
            const doc = docInput.value.trim();
            const asig = asigSelect.value;
            
            // Si la fila tiene algún dato, validar que esté completa
            if (ced || doc || asig) {
                if (ced && doc && asig) {
                    hayDocenteCompleto = true;
                    limpiarError(cedInput);
                    limpiarError(docInput);
                    limpiarError(asigSelect);
                } else {
                    // Marcar campos incompletos
                    if (!ced) mostrarError(cedInput, 'Cédula requerida');
                    if (!doc) mostrarError(docInput, 'Docente requerido');
                    if (!asig) mostrarError(asigSelect, 'Asignatura requerida');
                    isValid = false;
                }
            }
        }
        
        if (!hayDocenteCompleto && cedulas.length > 0) {
            await customAlert('❌ Debe completar al menos un docente con todos sus datos (cédula, nombre y asignatura).', 'Validación requerida', '⚠');
            isValid = false;
        }
        
        // Validar horas (entre 1 y 200)
        const horasInputs = document.querySelectorAll('.horas-input');
        horasInputs.forEach(input => {
            const horas = parseInt(input.value);
            if (isNaN(horas) || horas < 1 || horas > 200) {
                mostrarError(input, 'Las horas deben ser un número entre 1 y 200');
                isValid = false;
            } else {
                limpiarError(input);
            }
        });
        
        if (!isValid) {
            // Mostrar primer campo con error
            const primerError = document.querySelector('.error-input');
            if (primerError) {
                primerError.focus();
                window.scrollTo({
                    top: primerError.getBoundingClientRect().top + window.scrollY - 100,
                    behavior: 'smooth'
                });
            }
            return false;
        }
        
        // Mostrar estado de carga
        const submitBtn = document.getElementById('btn-submit');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '⏳ Generando PDF...';
        submitBtn.disabled = true;
        
        try {
            // Mostrar el número de comunicado que se va a usar
            const comunicadoNum = document.getElementById('comunicado').value;
            console.log('Enviando formulario con comunicado:', comunicadoNum);

            // Asegurar que el servidor reciba la variable 'guardar' cuando enviamos programáticamente
            if (!this.querySelector('input[name="guardar"]')) {
                const hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'guardar';
                hid.value = '1';
                this.appendChild(hid);
            }

            // Enviar formulario (ahora con el campo hidden 'guardar')
            this.submit();
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            customAlert('Error al enviar el formulario. Intente nuevamente.', 'Error', '❌');
        }
    });

    // ============================
    // 8. INICIALIZACIÓN
    // ============================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Formulario cargado - Comunicado actual:', '<?= $comunicado ?>');
        
        // Configurar fecha máxima como hoy
        document.getElementById('fecha').max = new Date().toISOString().split('T')[0];
        
        // Buscar docentes automáticamente si hay cédulas pre-cargadas
        const cedInputs = document.querySelectorAll('.ced-input');
        cedInputs.forEach((input, index) => {
            if (input.value.trim() !== '') {
                // Pequeño delay para evitar bloqueo de UI
                setTimeout(() => {
                    buscarDocente(input);
                }, 300 * (index + 1));
            }
        });
        
        // Auto-enfocar primer campo si no hay errores
        if (!document.querySelector('.error-input')) {
            setTimeout(() => {
                document.getElementById('para').focus();
            }, 500);
        }
        
        // Auto-ocultar mensajes después de 5 segundos
        const mensaje = document.getElementById('mensaje-alerta');
        if (mensaje) {
            setTimeout(() => {
                mensaje.classList.add('fade-out');
                setTimeout(() => {
                    if (mensaje.parentNode) {
                        mensaje.remove();
                    }
                }, 500);
            }, 5000);
        }
        
        // Validación en tiempo real para cédula (limitar a 10 dígitos)
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('ced-input')) {
                const ced = e.target.value;
                if (ced.length > 10) {
                    e.target.value = ced.substring(0, 10);
                }
            }
            
            // Validación en tiempo real para horas
            if (e.target.classList.contains('horas-input')) {
                const horas = parseInt(e.target.value);
                if (horas < 1) e.target.value = 1;
                if (horas > 200) e.target.value = 200;
            }
        });
        
        // También buscar docente con Enter en campo cédula
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('ced-input')) {
                e.preventDefault();
                buscarDocente(e.target);
            }
        });
    });
    </script>
</body>
</html>