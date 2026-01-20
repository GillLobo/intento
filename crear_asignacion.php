<?php
session_start();
include 'connection.php';

$dashboard = "dashboard.php";
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador') {
    $dashboard = "dashboard_admin.php";
}

$mensaje = '';
$tipo = '';

// Consultas completas para los selects
$prof = $conn->query("SELECT IDPROF, NOMAPE FROM profesores ORDER BY NOMAPE");
$mat = $conn->query("SELECT IDMAT, NOMMAT FROM materias ORDER BY NOMMAT");
$plan = $conn->query("SELECT IDPLA, DESCRIP FROM planificacion ORDER BY DESCRIP");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Asignación | Postgrado UJGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 91, 187, 0.15);
            overflow: hidden;
            border: 1px solid rgba(0, 91, 187, 0.1);
        }

        .form-header {
            background: linear-gradient(to right, #005bbb, #004494);
            color: white;
            padding: 25px;
            text-align: center;
            border-bottom: 4px solid #ffc107;
        }

        .form-header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .form-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .form-label i {
            color: #005bbb;
            width: 20px;
            text-align: center;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
            color: #333;
        }

        .form-input:focus {
            outline: none;
            border-color: #005bbb;
            box-shadow: 0 0 0 3px rgba(0, 91, 187, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .form-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            text-align: center;
        }

        .btn-save {
            background: linear-gradient(to right, #28a745, #219653);
            color: white;
        }

        .btn-save:hover {
            background: linear-gradient(to right, #219653, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
        }

        /* ===== ESTILOS PARA BUSCADORES ===== */
        .search-container {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: white;
            border: 2px solid #e1e5eb;
            border-top: none;
            border-radius: 0 0 10px 10px;
            z-index: 100;
            display: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .search-results.show {
            display: block;
        }

        .result-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        .result-item:hover {
            background: #f8f9fa;
        }

        .result-item.selected {
            background: #e3f2fd;
            color: #005bbb;
            font-weight: 600;
        }

        .result-count {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .required::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        @media (max-width: 768px) {
            .form-container {
                max-width: 100%;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .form-header {
                padding: 20px;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .form-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-link"></i> Nueva Asignación</h1>
            <p>Asignar materia a profesor - Postgrado UJGH</p>
        </div>
        
        <form method="POST" action="procesar_asignacion.php" class="form-body">
            <!-- Buscador de Profesor -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user-tie"></i>
                    <span class="required">Profesor</span>
                </label>
                <div class="search-container">
                    <input type="text" 
                           class="form-input" 
                           id="profesorSearch" 
                           placeholder="Escriba para buscar profesor..."
                           autocomplete="off">
                    <div class="search-results" id="profesorResults">
                        <?php 
                        $prof->data_seek(0);
                        while($p = $prof->fetch_assoc()): 
                        ?>
                            <div class="result-item" 
                                 data-id="<?= $p['IDPROF'] ?>"
                                 data-text="<?= htmlspecialchars($p['NOMAPE']) ?>">
                                <?= htmlspecialchars($p['NOMAPE']) ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <input type="hidden" name="IDPROF" id="profesorId" required>
                    <div class="result-count" id="profesorCount">
                        <?= $prof->num_rows ?> profesores disponibles
                    </div>
                </div>
            </div>
            
            <!-- Buscador de Materia -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-book"></i>
                    <span class="required">Materia</span>
                </label>
                <div class="search-container">
                    <input type="text" 
                           class="form-input" 
                           id="materiaSearch" 
                           placeholder="Escriba para buscar materia..."
                           autocomplete="off">
                    <div class="search-results" id="materiaResults">
                        <?php 
                        $mat->data_seek(0);
                        while($m = $mat->fetch_assoc()): 
                        ?>
                            <div class="result-item" 
                                 data-id="<?= $m['IDMAT'] ?>"
                                 data-text="<?= htmlspecialchars($m['NOMMAT']) ?>">
                                <?= htmlspecialchars($m['NOMMAT']) ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <input type="hidden" name="IDMAT" id="materiaId" required>
                    <div class="result-count" id="materiaCount">
                        <?= $mat->num_rows ?> materias disponibles
                    </div>
                </div>
            </div>
            
            <!-- Plan -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="required">Planificación</span>
                </label>
                <select name="IDPLA" class="form-input" required>
                    <option value="">Seleccione el PAP</option>
                    <?php 
                    $plan->data_seek(0);
                    while($pl = $plan->fetch_assoc()): 
                    ?>
                        <option value="<?= $pl['IDPLA'] ?>"><?= htmlspecialchars($pl['DESCRIP']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Fechas -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-plus"></i>
                        <span class="required">Inicio</span>
                    </label>
                    <input type="date" name="FECINI" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-check"></i>
                        <span class="required">Culmina</span>
                    </label>
                    <input type="date" name="FECCUL" class="form-input" required>
                </div>
            </div>
            
            <!-- Horas -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i>
                        <span class="required">Hora Inicio</span>
                    </label>
                    <input type="time" name="HORAINI" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i>
                        <span class="required">Hora Culmina</span>
                    </label>
                    <input type="time" name="HORACUL" class="form-input" required>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="form-actions">
                <button type="submit" class="form-btn btn-save">
                    <i class="fas fa-save"></i> Guardar Asignación
                </button>
                <a href="asignar_materias.php" class="form-btn btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        // ===== SISTEMA DE BÚSQUEDA EN TIEMPO REAL =====
        
        // Configuración para profesores
        const profesorSearch = document.getElementById('profesorSearch');
        const profesorResults = document.getElementById('profesorResults');
        const profesorId = document.getElementById('profesorId');
        const profesorCount = document.getElementById('profesorCount');
        const profesorItems = profesorResults.querySelectorAll('.result-item');
        
        // Configuración para materias
        const materiaSearch = document.getElementById('materiaSearch');
        const materiaResults = document.getElementById('materiaResults');
        const materiaId = document.getElementById('materiaId');
        const materiaCount = document.getElementById('materiaCount');
        const materiaItems = materiaResults.querySelectorAll('.result-item');
        
        // Función para buscar en tiempo real
        function searchItems(searchInput, items, resultsContainer, countElement) {
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            // Mostrar/ocultar resultados
            if (searchTerm.length > 0) {
                resultsContainer.classList.add('show');
            } else {
                resultsContainer.classList.remove('show');
            }
            
            let visibleCount = 0;
            
            // Filtrar items
            items.forEach(item => {
                const text = item.getAttribute('data-text').toLowerCase();
                
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Actualizar contador
            if (searchTerm.length > 0) {
                countElement.textContent = `${visibleCount} resultados encontrados`;
            } else {
                countElement.textContent = `${items.length} disponibles`;
            }
            
            return visibleCount;
        }
        
        // Función para seleccionar un item
        function selectItem(item, searchInput, hiddenInput, resultsContainer) {
            const id = item.getAttribute('data-id');
            const text = item.getAttribute('data-text');
            
            // Quitar selección previa
            resultsContainer.querySelectorAll('.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Marcar como seleccionado
            item.classList.add('selected');
            
            // Actualizar campos
            searchInput.value = text;
            hiddenInput.value = id;
            
            // Estilo para indicar selección
            searchInput.style.borderColor = '#005bbb';
            searchInput.style.boxShadow = '0 0 0 3px rgba(0, 91, 187, 0.1)';
            
            // Ocultar resultados
            resultsContainer.classList.remove('show');
            
            // Actualizar contadores
            updateCounters();
        }
        
        // Función para actualizar contadores
        function updateCounters() {
            const profesorSelected = profesorId.value !== '';
            const materiaSelected = materiaId.value !== '';
            
            if (profesorSelected) {
                profesorCount.textContent = '✓ Profesor seleccionado';
                profesorCount.style.color = '#28a745';
            } else {
                profesorCount.textContent = `${profesorItems.length} profesores disponibles`;
                profesorCount.style.color = '#6c757d';
            }
            
            if (materiaSelected) {
                materiaCount.textContent = '✓ Materia seleccionada';
                materiaCount.style.color = '#28a745';
            } else {
                materiaCount.textContent = `${materiaItems.length} materias disponibles`;
                materiaCount.style.color = '#6c757d';
            }
        }
        
        // ===== CONFIGURACIÓN PARA PROFESORES =====
        profesorSearch.addEventListener('input', () => {
            searchItems(profesorSearch, profesorItems, profesorResults, profesorCount);
        });
        
        profesorSearch.addEventListener('focus', () => {
            if (profesorSearch.value.length > 0) {
                profesorResults.classList.add('show');
            }
        });
        
        // Seleccionar profesor al hacer clic
        profesorItems.forEach(item => {
            item.addEventListener('click', () => {
                selectItem(item, profesorSearch, profesorId, profesorResults);
            });
        });
        
        // ===== CONFIGURACIÓN PARA MATERIAS =====
        materiaSearch.addEventListener('input', () => {
            searchItems(materiaSearch, materiaItems, materiaResults, materiaCount);
        });
        
        materiaSearch.addEventListener('focus', () => {
            if (materiaSearch.value.length > 0) {
                materiaResults.classList.add('show');
            }
        });
        
        // Seleccionar materia al hacer clic
        materiaItems.forEach(item => {
            item.addEventListener('click', () => {
                selectItem(item, materiaSearch, materiaId, materiaResults);
            });
        });
        
        // ===== FUNCIONALIDADES ADICIONALES =====
        
        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!profesorSearch.contains(e.target) && !profesorResults.contains(e.target)) {
                profesorResults.classList.remove('show');
            }
            
            if (!materiaSearch.contains(e.target) && !materiaResults.contains(e.target)) {
                materiaResults.classList.remove('show');
            }
        });
        
        // Limpiar selección con doble clic
        profesorSearch.addEventListener('dblclick', () => {
            if (profesorId.value) {
                profesorSearch.value = '';
                profesorId.value = '';
                profesorSearch.style.borderColor = '';
                profesorSearch.style.boxShadow = '';
                updateCounters();
            }
        });
        
        materiaSearch.addEventListener('dblclick', () => {
            if (materiaId.value) {
                materiaSearch.value = '';
                materiaId.value = '';
                materiaSearch.style.borderColor = '';
                materiaSearch.style.boxShadow = '';
                updateCounters();
            }
        });
        
        // ===== VALIDACIÓN DE FECHAS =====
        const fechaInicio = document.querySelector('input[name="FECINI"]');
        const fechaFin = document.querySelector('input[name="FECCUL"]');
        
        if (fechaInicio && fechaFin) {
            const today = new Date().toISOString().split('T')[0];
            fechaInicio.min = today;
            
            fechaInicio.addEventListener('change', function() {
                fechaFin.min = this.value;
            });
        }
        
        // ===== VALIDACIÓN DEL FORMULARIO =====
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (!profesorId.value || !materiaId.value) {
                e.preventDefault();
                
                if (!profesorId.value) {
                    profesorSearch.style.borderColor = '#dc3545';
                    profesorSearch.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                }
                
                if (!materiaId.value) {
                    materiaSearch.style.borderColor = '#dc3545';
                    materiaSearch.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                }
                
                // Mostrar resultados si están vacíos
                if (!profesorId.value) {
                    profesorResults.classList.add('show');
                }
                
                if (!materiaId.value) {
                    materiaResults.classList.add('show');
                }
            }
        });
        
        // ===== EFECTOS VISUALES =====
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        document.querySelectorAll('.form-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Inicializar contadores
        updateCounters();
    </script>
</body>
</html>