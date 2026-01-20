<?php
// editar_asignacion.php
include 'connection.php';

// =====================
// VALIDAR ID RECIBIDO
// =====================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: asignar_materias.php?msg=error_id");
    exit;
}

$id = intval($_GET['id']);

// =====================
// TRAER ASIGNACIÓN CON JOIN
// =====================
$stmt = $conn->prepare("
    SELECT a.*,
           p.DESCRIP AS NombrePlan,
           m.NOMMAT AS NombreMateria,
           pr.NOMAPE AS NombreProfesor
    FROM asignacion a
    INNER JOIN planificacion p ON a.IDPLA = p.IDPLA
    INNER JOIN materias m ON a.IDMAT = m.IDMAT
    INNER JOIN profesores pr ON a.IDPROF = pr.IDPROF
    WHERE a.IDASIG = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

// Validar existencia
if ($res->num_rows === 0) {
    header("Location: asignar_materias.php?msg=no_encontrado");
    exit;
}

$asig = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Asignación</title>

<style>
body{font-family:Arial;background:#f4f4f4;padding:20px}
.card{max-width:650px;margin:auto;background:#fff;padding:20px;border-radius:8px;
box-shadow:0 4px 12px rgba(0,0,0,0.1)}
label{display:block;margin-top:12px;font-weight:bold}
input,select{
    width:100%;padding:9px;margin-top:5px;
    border:1px solid #ccc;border-radius:6px;
}
.btn{padding:10px 14px;border-radius:6px;text-decoration:none;
     color:#fff;font-weight:bold;display:inline-block;margin-top:18px}
.blue{background:#007bff}
.gray{background:#6c757d}
.error{color:#d00;font-weight:bold;margin-top:10px}
</style>

</head>
<body>

<div class="card">
    <h2>Editar Asignación #<?php echo $asig['IDASIG']; ?></h2>

    <!-- FORMULARIO -->
    <form action="actualizar_asignacion.php" method="POST">

        <!-- ID oculto -->
        <input type="hidden" name="IDASIG" value="<?php echo $asig['IDASIG']; ?>">

        <!-- ========================= -->
        <!-- SELECT: PLANIFICACIÓN     -->
        <!-- ========================= -->
        <label>Planificación</label>
        <select name="IDPLA" required>
            <option value="<?php echo $asig['IDPLA']; ?>">
                <?php echo $asig['NombrePlan']; ?>
            </option>
            <?php
            $q = $conn->query("SELECT IDPLA, DESCRIP FROM planificacion ORDER BY DESCRIP ASC");
            while ($row = $q->fetch_assoc()):
                if ($row['IDPLA'] == $asig['IDPLA']) continue;
            ?>
                <option value="<?php echo $row['IDPLA']; ?>">
                    <?php echo $row['DESCRIP']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- ========================= -->
        <!-- SELECT: MATERIAS          -->
        <!-- ========================= -->
        <label>Materia</label>
        <select name="IDMAT" required>
            <option value="<?php echo $asig['IDMAT']; ?>">
                <?php echo $asig['NombreMateria']; ?>
            </option>
            <?php
            $q = $conn->query("SELECT IDMAT, NOMMAT FROM materias ORDER BY NOMMAT ASC");
            while ($row = $q->fetch_assoc()):
                if ($row['IDMAT'] == $asig['IDMAT']) continue;
            ?>
                <option value="<?php echo $row['IDMAT']; ?>">
                    <?php echo $row['NOMMAT']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- ========================= -->
        <!-- SELECT: PROFESORES        -->
        <!-- ========================= -->
        <label>Profesor</label>
        <select name="IDPROF" required>
            <option value="<?php echo $asig['IDPROF']; ?>">
                <?php echo $asig['NombreProfesor']; ?>
            </option>
            <?php
            $q = $conn->query("SELECT IDPROF, NOMAPE FROM profesores WHERE ESTADO = 'Activo' ORDER BY NOMAPE ASC");
            while ($row = $q->fetch_assoc()):
                if ($row['IDPROF'] == $asig['IDPROF']) continue;
            ?>
                <option value="<?php echo $row['IDPROF']; ?>">
                    <?php echo $row['NOMAPE']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- ========================= -->
        <!-- FECHAS -->
        <!-- ========================= -->
        <label>Fecha Inicio</label>
        <input type="date" name="FECINI" required 
               value="<?php echo $asig['FECINI']; ?>">

        <label>Fecha Culminación</label>
        <input type="date" name="FECCUL" required 
               value="<?php echo $asig['FECCUL']; ?>">

        <!-- ========================= -->
        <!-- HORAS -->
        <!-- ========================= -->
        <label>Hora Inicio</label>
        <input type="time" name="HORAINI" required 
               value="<?php echo $asig['HORAINI']; ?>">

        <label>Hora Culminación</label>
        <input type="time" name="HORACUL" required 
               value="<?php echo $asig['HORACUL']; ?>">

        <!-- BOTONES -->
        <button class="btn blue" type="submit">💾 Guardar Cambios</button>
        <a class="btn gray" href="asignar_materias.php">⬅ Volver</a>

    </form>
</div>

</body>
</html>
