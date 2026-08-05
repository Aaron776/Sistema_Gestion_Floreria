<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

if (!isset($_GET['id_pedido'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido no proporcionado']);
    exit;
}

$id_pedido = Crypto::decrypt($_GET['id_pedido']);
if (empty($id_pedido) || !is_numeric($id_pedido) || $id_pedido <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido']);
    exit;
}

try {
    // Datos del pedido con cliente y vendedor
    $sql = $conexion->prepare(
        "SELECT p.id_pedido, p.fecha_pedido, p.direccion_entrega, p.mensaje_tarjeta, 
                p.total, p.estado,
                cliente.nombre AS nombre_cliente, cliente.email AS email_cliente,
                cliente.telefono AS telefono_cliente, cliente.direccion AS direccion_cliente,
                vendedor.nombre AS nombre_vendedor
         FROM pedidos p
         JOIN usuarios cliente ON p.id_cliente = cliente.id_usuario
         LEFT JOIN usuarios vendedor ON p.id_usuario = vendedor.id_usuario
         WHERE p.id_pedido = :id_pedido"
    );
    $sql->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $sql->execute();
    $pedido = $sql->fetch(PDO::FETCH_OBJ);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit;
    }

    // Detalle del pedido (productos)
    $sql_detalle = $conexion->prepare(
        "SELECT dp.cantidad, dp.precio_unitario, dp.subtotal,
                pr.nombre AS nombre_producto
         FROM detalle_pedido dp
         JOIN productos pr ON dp.id_producto = pr.id_producto
         WHERE dp.id_pedido = :id_pedido"
    );
    $sql_detalle->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $sql_detalle->execute();
    $detalles = $sql_detalle->fetchAll(PDO::FETCH_OBJ);

    // Pago del pedido
    $sql_pago = $conexion->prepare(
        "SELECT metodo_pago, referencia, monto, fecha_pago, estado
         FROM pagos
         WHERE id_pedido = :id_pedido
         ORDER BY fecha_pago DESC
         LIMIT 1"
    );
    $sql_pago->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $sql_pago->execute();
    $pago = $sql_pago->fetch(PDO::FETCH_OBJ);

    echo json_encode([
        'pedido' => $pedido,
        'detalles' => $detalles,
        'pago' => $pago
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Error al obtener pedido: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los datos del pedido']);
}
