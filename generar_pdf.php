<?php
require('fpdf/fpdf.php');
include 'connection.php';

if (!isset($_GET['id'])) {
    die("Error: No se recibió el ID del formulario.");
}

$id = intval($_GET['id']);

// ======================================
// OBTENER ENCABEZADO + DOCENTES
// ======================================
$sql = "
    SELECT 
        f.id,
        f.para,
        f.de_nombre,
        f.asunto,
        f.fecha,
        f.comunicado,
        f.parrafo,
        p.DESCRIP AS periodo,
        d.cedula,
        d.docente,
        d.asignatura,
        d.horas
    FROM formularios f
    LEFT JOIN formulario_docentes d ON d.formulario_id = f.id
    LEFT JOIN planificacion p ON p.IDPLA = f.pap_id
    WHERE f.id = $id
    ORDER BY d.id ASC
";

$res = $conn->query($sql);

if (!$res || $res->num_rows == 0) {
    die("Error: No se encontró el formulario.");
}

// Encabezado
$docentes = [];
$encabezado = null;
while ($row = $res->fetch_assoc()) {
    // Guardar encabezado sólo la primera vez
    if ($encabezado === null) {
        $encabezado = $row;
    }

    // Agregar sólo filas que contienen datos de docentes
    if (!is_null($row['cedula'])) {
        $docentes[] = $row;
    }
}

// ======================================
// CREAR PDF
// ======================================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetMargins(20, 15, 20); // márgenes amplios
$pdf->SetFont('Arial', '', 11);

// LOGO CENTRADO (más grande)
$logo = 'logo.jpg';
if(file_exists($logo)){
    $logoWidth = 100; // ancho más grande
    $x = ($pdf->GetPageWidth() - $logoWidth) / 2;
    $pdf->Image($logo, $x, 15, $logoWidth); // centrado
}
$pdf->Ln(45); // espacio debajo del logo

// DATOS PRINCIPALES
$pdf->SetFont('Arial', '', 11);
$datos = [
    "PARA:" => $encabezado['para'],
    "DE:" => $encabezado['de_nombre'],
    "ASUNTO:" => $encabezado['asunto'],
    "FECHA:" => date('d/m/Y', strtotime($encabezado['fecha'])),
    "COMUNICADO:" => $encabezado['comunicado'],
    "PERIODO:" => $encabezado['periodo']
];

foreach($datos as $label => $valor){
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(35, 7, $label, 0, 0);
    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0, 7, utf8_decode($valor));
}
$pdf->Ln(5);

// PÁRRAFO JUSTIFICADO
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0, 7, utf8_decode($encabezado['parrafo']));
$pdf->Ln(7);

// ======================================
// TABLA DE DOCENTES
// ======================================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode("DOCENTES Y ASIGNATURAS"), 0, 1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30, 8, "Cedula", 1,0,'C');
$pdf->Cell(55, 8, "Docente", 1,0,'C');
$pdf->Cell(80, 8, "Asignatura", 1,0,'C');
$pdf->Cell(20, 8, "Horas", 1,1,'C');

$pdf->SetFont('Arial','',10);
foreach ($docentes as $d) {
    if ($d['cedula'] === null) continue;
    $pdf->Cell(30, 8, $d['cedula'], 1,0,'C');
    $pdf->Cell(55, 8, utf8_decode($d['docente']), 1,0,'C');
    $pdf->Cell(80, 8, utf8_decode($d['asignatura']), 1,0,'C');
    $pdf->Cell(20, 8, $d['horas'], 1,1,'C');
}
$pdf->Ln(15);

// FIRMA
$pdf->SetFont('Arial','',11);
$pdf->Cell(0, 10, "_______________________________", 0,1,'C');
$pdf->Cell(0, 7, utf8_decode($encabezado['de_nombre']), 0,1,'C');
$pdf->Cell(0, 7, "Decano DIP", 0,1,'C');

// SALIDA
$pdf->Output("I", "Comunicado-" . $encabezado['comunicado'] . ".pdf");
exit;
?>
