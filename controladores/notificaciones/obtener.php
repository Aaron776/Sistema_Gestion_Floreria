<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['id_usuario'];

try {
    // 1. Contar no leídas
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :uid AND leida = 'no'");
    $sql_count->execute([':uid' => $usuario_id]);
    $no_leidas = $sql_count->fetchColumn();

    // 2. Obtener las últimas notificaciones
    $sql_list = $conexion->prepare("SELECT id_notificacion, titulo, mensaje, tipo, leida, fecha
                                    FROM notificaciones
                                    WHERE id_usuario = :uid
                                    ORDER BY fecha DESC, id_notificacion DESC
                                    LIMIT 10");
    $sql_list->execute([':uid' => $usuario_id]);
    $notificaciones = $sql_list->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'no_leidas' => (int)$no_leidas,
        'listado' => $notificaciones
    ]);

} catch (PDOException $e) {
    error_log("Error al obtener notificaciones: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
