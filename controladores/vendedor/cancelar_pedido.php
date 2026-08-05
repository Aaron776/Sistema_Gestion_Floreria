<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pedido'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo."];
        header("Location: ../../vendedor/gestion_pedidos.php");
        exit;
    }

    // Recibir datos
    $id_pedido = trim(Crypto::decrypt($_POST['id_pedido']));
    $id_vendedor = $_SESSION['id_usuario'];
    $errores = [];

    // Validaciones
    if (empty($id_pedido) || !is_numeric($id_pedido) || $id_pedido <= 0) {
        $errores[] = "El ID del pedido no es válido.";
    }

    // Verifiacar si el pedido existe y si fue registrado por el vendedor logueado y esta en estado pendiente o preparando
    if (empty($errores)) {
        try {
            $sql_pedido = $conexion->prepare("SELECT id_pedido, id_cliente, id_usuario, estado FROM pedidos WHERE id_pedido = :id AND estado IN ('pendiente', 'preparando') AND id_usuario = :id_usuario");
            $sql_pedido->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_pedido->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
            $sql_pedido->execute();
            $pedido = $sql_pedido->fetch(PDO::FETCH_OBJ);

            if (!$pedido) {
                $errores[] = "El pedido no existe o ya no se puede cancelar.";
            }
        } catch (PDOException $e) {
            error_log("Error al validar pedido: " . $e->getMessage());
            $errores[] = "Error al validar el pedido.";
        }
    }

    // Si no hay errores procedemos a cancelar el pedido
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Actualizamoe el estado del pedido a cancelado y regresamos el stock de los productos
            $sql_update = $conexion->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id_pedido = :id");
            $sql_update->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_update->execute();

            // Regresamos el stock de los productos del pedido
            $sql_productos = $conexion->prepare("SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = :id");
            $sql_productos->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_productos->execute();
            $productos = $sql_productos->fetchAll(PDO::FETCH_OBJ);

            foreach ($productos as $item) {
                $sql_update_stock = $conexion->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id_producto = :id");
                $sql_update_stock->bindParam(":id", $item->id_producto, PDO::PARAM_INT);
                $sql_update_stock->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $sql_update_stock->execute();

                $sql_mov = $conexion->prepare("INSERT INTO movimientos_inventario (id_producto, id_usuario, tipo, cantidad, motivo) VALUES (:id_producto, :id_usuario, 'entrada', :cantidad, :motivo)");
                $sql_mov->bindParam(":id_producto", $item->id_producto, PDO::PARAM_INT);
                $sql_mov->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
                $sql_mov->bindParam(":cantidad", $item->cantidad, PDO::PARAM_INT);
                $motivo = "Cancelación del pedido #{$id_pedido}";
                $sql_mov->bindParam(":motivo", $motivo, PDO::PARAM_STR);
                $sql_mov->execute();
            }

            // Ingresamos notifiaciones a la BD
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
            $notif_id = 0;
            $notif_titulo = '';
            $notif_mensaje = '';
            $notif_tipo = 'pedido';
            $sql_notif->bindParam(":id_usuario", $notif_id, PDO::PARAM_INT);
            $sql_notif->bindParam(":titulo", $notif_titulo, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $notif_mensaje, PDO::PARAM_STR);
            $sql_notif->bindParam(":tipo", $notif_tipo, PDO::PARAM_STR);

            // Vendedor
            $notif_id = $id_vendedor;
            $notif_titulo = "Pedido Cancelado";
            $notif_mensaje = "Has cancelado el pedido #{$id_pedido}.";
            $sql_notif->execute();

            // Cliente
            if (!empty($pedido->id_cliente) && $pedido->id_cliente != $id_vendedor) {
                $notif_id = $pedido->id_cliente;
                $notif_titulo = "Pedido Cancelado";
                $notif_mensaje = "Tu pedido #{$id_pedido} ha sido cancelado.";
                $sql_notif->execute();
            }

            // Admins
            $sql_admins = $conexion->query("SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
            while ($admin = $sql_admins->fetch(PDO::FETCH_OBJ)) {
                if ($admin->id_usuario != $id_vendedor && $admin->id_usuario != $pedido->id_cliente) {
                    $notif_id = $admin->id_usuario;
                    $notif_titulo = "Pedido Cancelado";
                    $notif_mensaje = "El pedido #{$id_pedido} ha sido cancelado.";
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Pedido #{$id_pedido} cancelado exitosamente.";
            header("Location: ../../vendedor/gestion_pedidos.php");
            exit;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al cancelar pedido: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al cancelar el pedido."];
            header("Location: ../../vendedor/gestion_pedidos.php");
            exit;
        }
    }else{
        $_SESSION['errores'] = $errores;
        header("Location: ../../vendedor/gestion_pedidos.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes."];
    header("Location: ../../vendedor/gestion_pedidos.php");
    exit;
}
