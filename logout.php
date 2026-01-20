<?php
// Archivo: logout.php
// ---------------------------------------------
// Este script destruye la sesión del usuario y lo redirige al login.

session_start();    // Inicia la sesión para poder destruirla
session_unset();    // Elimina las variables de sesión
session_destroy();  // Destruye la sesión completa

// Redirige al login
header("Location: login.php");
exit;
?>
