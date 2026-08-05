<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pedido'], $_POST['estado'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo."];
        header("Location: ../../vendedor/gestion_pedidos.php");
        exit;
    }

    // Recuperacion de datos
    $id_pedido = trim(Crypto::decrypt($_POST['id_pedido']));
    $id_vendedor=$_SESSION['id_usuario'];
    $nuevo_estado = trim($_POST['estado']);
    $errores = [];

    // Validaciones
    if (empty($id_pedido) || !is_numeric($id_pedido) || $id_pedido <= 0) {
        $errores[] = "El ID del pedido no es válido.";
    }

    $estados_validos = ['preparando', 'listo'];
    if (empty($nuevo_estado)) {
        $errores[] = "Debes seleccionar un estado.";
    } else if (!in_array($nuevo_estado, $estados_validos)) {
        $errores[] = "El estado seleccionado no es válido.";
    }

    // Verificar si el pedido existe en la base de datos, si fue creado por ese vendedor y si esta en esatdo pendiente o preparando
    if (empty($errores)) {
        try {
            $sql_pedido = $conexion->prepare("SELECT id_pedido, id_cliente, id_usuario, estado FROM pedidos WHERE id_pedido = :id AND estado IN ('pendiente', 'preparando') AND id_usuario=:id_usuario");
            $sql_pedido->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_pedido->bindParam(":id_usuario",$id_vendedor , PDO::PARAM_INT);
            $sql_pedido->execute();
            $pedido = $sql_pedido->fetch(PDO::FETCH_OBJ);

            if (!$pedido) {
                $errores[] = "El pedido no existe o ya no se puede cambiar su estado.";
            } else {
                // Validar transiciones legales
                if ($pedido->estado === 'pendiente' && !in_array($nuevo_estado, ['preparando', 'cancelado'])) {
                    $errores[] = "De un pedido pendiente solo puedes pasar a preparando o cancelado.";
                }
                if ($pedido->estado === 'preparando' && !in_array($nuevo_estado, ['listo', 'cancelado'])) {
                    $errores[] = "De un pedido en preparación solo puedes pasar a listo o cancelado.";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al validar pedido: " . $e->getMessage());
            $errores[] = "Error al validar el pedido.";
        }
    }

    // Si no hay errores procedemos a cambir el edatdo del pedido
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Actualziar edtado del pedido
            $sql_update = $conexion->prepare("UPDATE pedidos SET estado = :estado WHERE id_pedido = :id");
            $sql_update->bindParam(":estado", $nuevo_estado, PDO::PARAM_STR);
            $sql_update->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_update->execute();

            // Notifiaciones para la BD
            $titulo_notif = ($nuevo_estado === 'cancelado') ? "Pedido Cancelado" : "Estado del Pedido Actualizado";
            $tipo_notif = 'pedido';

            switch ($nuevo_estado) {
                case 'preparando':
                    $mensaje_vendedor = "Has cambiado el pedido #{$id_pedido} a preparando.";
                    $mensaje_cliente = "Tu pedido #{$id_pedido} está siendo preparado.";
                    $mensaje_admin = "El pedido #{$id_pedido} ha cambiado a preparando.";
                    break;
                case 'listo':
                    $mensaje_vendedor = "El pedido #{$id_pedido} está listo para entrega.";
                    $mensaje_cliente = "Tu pedido #{$id_pedido} está listo y pronto será asignado a un repartidor.";
                    $mensaje_admin = "El pedido #{$id_pedido} está listo para entrega.";
                    break;
            }

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
            $notif_id_usuario = 0;
            $notif_titulo = '';
            $notif_mensaje = '';
            $sql_notif->bindParam(":id_usuario", $notif_id_usuario, PDO::PARAM_INT);
            $sql_notif->bindParam(":titulo", $notif_titulo, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $notif_mensaje, PDO::PARAM_STR);
            $sql_notif->bindParam(":tipo", $tipo_notif, PDO::PARAM_STR);

            // Vendedor que hizo el cambio
            $notif_id_usuario = $id_vendedor;
            $notif_titulo = $titulo_notif;
            $notif_mensaje = $mensaje_vendedor;
            $sql_notif->execute();

            // Cliente
            if (!empty($pedido->id_cliente) && $pedido->id_cliente != $id_vendedor) {
                $notif_id_usuario = $pedido->id_cliente;
                $notif_titulo = $titulo_notif;
                $notif_mensaje = $mensaje_cliente;
                $sql_notif->execute();
            }

            // Admins
            $sql_admins = $conexion->query("SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
            $admins = $sql_admins->fetchAll(PDO::FETCH_OBJ);
            foreach ($admins as $admin) {
                if ($admin->id_usuario != $id_vendedor && $admin->id_usuario != $pedido->id_cliente) {
                    $notif_id_usuario = $admin->id_usuario;
                    $notif_titulo = $titulo_notif;
                    $notif_mensaje = $mensaje_admin;
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Estado del pedido #{$id_pedido} actualizado a " . ucfirst($nuevo_estado) . ".";
            header("Location: ../../vendedor/gestion_pedidos.php");
            exit;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al cambiar estado: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al actualizar el estado del pedido."];
            header("Location: ../../vendedor/cambiar_estado_pedido.php?id_pedido=" . urlencode($_POST['id_pedido']));
            exit;
        }
    }else{
        $_SESSION['errores'] = $errores;
        header("Location: ../../vendedor/cambiar_estado_pedido.php?id_pedido=" . urlencode($_POST['id_pedido']));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes."];
    header("Location: ../../vendedor/gestion_pedidos.php");
    exit;
}
