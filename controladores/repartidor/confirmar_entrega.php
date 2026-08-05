<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'repartidor') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pedido'])) {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_pedido = trim(Crypto::decrypt($_POST['id_pedido']));
    $errores = [];

    // Validaciones
    if (empty($id_pedido)) {
        $errores[] = "El ID del pedido es requerido.";
    } elseif (!is_numeric($id_pedido) || $id_pedido <= 0) {
        $errores[] = "El ID del pedido no es válido.";
    }

    // Verificar que el pedido está asignado al repartidor y está en camino
    $pedido = null;
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT p.id_pedido, p.id_cliente, p.id_usuario, e.id_entrega FROM pedidos p JOIN entregas e ON p.id_pedido = e.id_pedido WHERE p.id_pedido = :id_pedido AND p.estado = 'en_camino' AND e.id_repartidor = :id_repartidor AND e.estado = 'en_camino'");
            $sql->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $id_repartidor_actual = $_SESSION['id_usuario'];
            $sql->bindParam(":id_repartidor", $id_repartidor_actual, PDO::PARAM_INT);
            $sql->execute();
            $pedido = $sql->fetch(PDO::FETCH_OBJ);

            if (!$pedido) {
                $errores[] = "El pedido no existe o no está asignado a ti como en camino.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el pedido: " . $e->getMessage());
            $errores[] = "Error al consultar el pedido en la base de datos.";
        }
    }

    // Si no hay errores, procesder a cambiar el edstado a entregado el pedido
    if (empty($errores) && $pedido) {
        try {
            $conexion->beginTransaction();

            $repartidor_id = $_SESSION['id_usuario'];
            $repartidor_nombre = $_SESSION['nombre'] ?? 'Repartidor';

            // 1. Actualizar entregas a entregado con fecha
            $sqlEntrega = $conexion->prepare("UPDATE entregas SET estado = 'entregado', fecha_entrega = NOW() WHERE id_entrega = :id_entrega");
            $sqlEntrega->bindParam(":id_entrega", $pedido->id_entrega, PDO::PARAM_INT);
            $sqlEntrega->execute();

            // 2. Actualizar pedido a entregado
            $sqlUpdate = $conexion->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = :id_pedido");
            $sqlUpdate->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $sqlUpdate->execute();

            // 3. Si el pago estaba pendiente (contra entrega), marcarlo como pagado y registrar quién lo cobró
            $sqlPago = $conexion->prepare("UPDATE pagos SET estado = 'pagado', id_usuario = :id_repartidor, fecha_pago = NOW() WHERE id_pedido = :id_pedido AND estado = 'pendiente'");
            $sqlPago->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $sqlPago->bindParam(":id_repartidor", $repartidor_id, PDO::PARAM_INT);
            $sqlPago->execute();

            // 4. Notificaciones
            $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'entrega')");
            $titulo_notif = "Pedido Entregado";

            // Repartidor
            $sqlNotif->execute([
                ':id_usuario' => $repartidor_id,
                ':titulo'     => $titulo_notif,
                ':mensaje'    => "Has entregado exitosamente el pedido #{$id_pedido}."
            ]);

            // Cliente
            if (!empty($pedido->id_cliente)) {
                $sqlNotif->execute([
                    ':id_usuario' => $pedido->id_cliente,
                    ':titulo'     => $titulo_notif,
                    ':mensaje'    => "Tu pedido #{$id_pedido} ha sido entregado exitosamente."
                ]);
            }

            // Vendedor
            if (!empty($pedido->id_usuario) && $pedido->id_usuario != $repartidor_id) {
                $sqlNotif->execute([
                    ':id_usuario' => $pedido->id_usuario,
                    ':titulo'     => $titulo_notif,
                    ':mensaje'    => "El pedido #{$id_pedido} que registraste ha sido entregado por {$repartidor_nombre}."
                ]);
            }

            // Admins
            $sqlAdmins = $conexion->query("SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
            $admins = $sqlAdmins->fetchAll(PDO::FETCH_OBJ);
            foreach ($admins as $admin) {
                if ($admin->id_usuario != $repartidor_id && $admin->id_usuario != $pedido->id_usuario) {
                    $sqlNotif->execute([
                        ':id_usuario' => $admin->id_usuario,
                        ':titulo'     => $titulo_notif,
                        ':mensaje'    => "El pedido #{$id_pedido} ha sido entregado por {$repartidor_nombre}."
                    ]);
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Entrega registrada exitosamente. El pedido #{$id_pedido} ha sido marcado como entregado por {$repartidor_nombre}.";
            header("Location: ../../repartidor/gestion_pedidos_en_camino.php");
            exit;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al confirmar entrega: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al procesar la entrega."];
            header("Location: ../../repartidor/gestion_pedidos_en_camino.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../repartidor/gestion_pedidos_en_camino.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes."];
    header("Location: ../../repartidor/gestion_pedidos_en_camino.php");
    exit;
}
