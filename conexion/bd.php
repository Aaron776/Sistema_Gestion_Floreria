<?php
$env = parse_ini_file(__DIR__ . '/../.env'); // Carga las variables de entorno

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurar zona horaria de Quito/Guayaquil (UTC-5)
    date_default_timezone_set('America/Guayaquil');
    $conexion->exec("SET time_zone = '-05:00'");
    
    //echo "✅ Conexión a MySQL exitosa!";
} catch (PDOException $e) {
    error_log("Error al conectar a MySQL: " . $e->getMessage());
    die("Error interno del servidor. Contacte al administrador.");
}
?>