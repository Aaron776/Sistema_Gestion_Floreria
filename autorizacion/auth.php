<?php
// Inicia la sesión de forma segura
require_once __DIR__ . '/../conexion/session.php';

if (empty($_SESSION['iniatiated'])) {
    session_regenerate_id(true); // Regenera el ID de la sesión
    $_SESSION['iniatiated'] = true; // Establece la variable 'iniatiated' en true
}

// Comprueba si el usuario no está logueado o la variable 'logueado' no es true
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: ../index.php"); // Redirige al login (o página principal)
    exit(); // Detiene la ejecución del script después de la redirección
}
