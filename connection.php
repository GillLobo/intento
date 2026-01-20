<?php
// Archivo: connection.php
// ---------------------------------------------
// Este archivo se encarga de establecer la conexión
// con la base de datos MySQL usando la extensión mysqli.
// Archivo: connection.php para Railway

$servername = $_ENV["MYSQLHOST"];
$username   = $_ENV["MYSQLUSER"];
$password   = $_ENV["MYSQLPASSWORD"];
$database   = $_ENV["MYSQLDATABASE"];
$port       = $_ENV["MYSQLPORT"];

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Codificación
$conn->set_charset("utf8mb4");
?>