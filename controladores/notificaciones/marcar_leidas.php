<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Solicitud no válida']);
    exit;
}

$usuario_id = $_SESSION['id_usuario'];

try {
    $sql = $conexion->prepare("UPDATE notificaciones SET leida = 'si' WHERE id_usuario = :uid AND leida = 'no'");
    $sql->execute([':uid' => $usuario_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Notificaciones marcadas como leídas'
    ]);

} catch (PDOException $e) {
    error_log("Error al marcar notificaciones como leídas: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
