<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pedido'], $_POST['repartidor'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo"];
        header("Location: ../../vendedor/asignar_repartidor.php?id_pedido=" . urlencode($_POST['id_pedido']));
        exit;
    }

    
    // Recuperar Datos
    $id_pedido = trim(Crypto::decrypt($_POST['id_pedido']));
    $id_repartidor = trim($_POST['repartidor']);
    $fecha_entrega = !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : null;
    $observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $errores = [];

    // Validaciones
    if (empty($id_pedido) || !is_numeric($id_pedido) || $id_pedido <= 0) {
        $errores[] = "El ID del pedido no es válido.";
    }

    if (empty($id_repartidor) || !is_numeric($id_repartidor) || $id_repartidor <= 0) {
        $errores[] = "Debes seleccionar un repartidor válido.";
    }

    if(!empty($observaciones) && strlen($observaciones) > 255){
        $errores[] = "Las observaciones no deben exceder los 255 caracteres.";
    }

    if(!empty($fecha_entrega)){
        $ts = strtotime($fecha_entrega);
        if($ts === false){
            $errores[] = "La fecha de entrega no es una fecha válida.";
        } elseif($ts <= time()){
            $errores[] = "La fecha de entrega debe ser mayor a la fecha actual.";
        }
    }

    // Verificar si el pedido existe, está listo, repartidor activo y sin entrega activa
    if (empty($errores)) {
        try {
            // Verificar que el pedido existe y está listo
            $sql_pedido = $conexion->prepare("SELECT id_pedido, id_cliente FROM pedidos WHERE id_pedido = :id AND estado = 'listo'");
            $sql_pedido->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_pedido->execute();
            $pedido = $sql_pedido->fetch(PDO::FETCH_OBJ);

            if (!$pedido) {
                $errores[] = "El pedido no existe o no está en estado listo.";
            }

            // Verificar que el repartidor existe y está activo
            $sql_rep = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND rol = 'repartidor' AND estado = 'activo'");
            $sql_rep->bindParam(":id", $id_repartidor, PDO::PARAM_INT);
            $sql_rep->execute();
            if (!$sql_rep->fetch()) {
                $errores[] = "El repartidor seleccionado no existe o no está activo.";
            }

            // Verificar que el pedido no tenga ya una entrega asignada
            $sql_existente = $conexion->prepare("SELECT id_entrega FROM entregas WHERE id_pedido = :id AND estado != 'entregado'");
            $sql_existente->bindParam(":id", $id_pedido, PDO::PARAM_INT);
            $sql_existente->execute();
            if ($sql_existente->fetch()) {
                $errores[] = "Este pedido ya tiene un repartidor asignado.";
            }

        } catch (PDOException $e) {
            error_log("Error al validar asignación: " . $e->getMessage());
            $errores[] = "Error al validar los datos.";
        }
    }


    // Si no hay errores, se prcede asignar un raprtidor a la entrega del pedido
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Insertar en la tabla entregas
            $sql_entrega = $conexion->prepare("INSERT INTO entregas (id_pedido, id_repartidor, estado, fecha_entrega, observaciones) VALUES (:id_pedido, :id_repartidor, 'pendiente', :fecha_entrega, :observaciones)");
            $sql_entrega->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $sql_entrega->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
            $sql_entrega->bindParam(":fecha_entrega", $fecha_entrega);
            $sql_entrega->bindParam(":observaciones", $observaciones);
            $sql_entrega->execute();

            // Notificar al repartidor, vendedor, cliente y admins
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
            $notif_id = 0;
            $notif_titulo = '';
            $notif_mensaje = '';
            $notif_tipo = 'entrega';
            $sql_notif->bindParam(":id_usuario", $notif_id, PDO::PARAM_INT);
            $sql_notif->bindParam(":titulo", $notif_titulo, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $notif_mensaje, PDO::PARAM_STR);
            $sql_notif->bindParam(":tipo", $notif_tipo, PDO::PARAM_STR);

            // Repartidor
            $notif_id = $id_repartidor;
            $notif_titulo = "Nuevo pedido asignado";
            $notif_mensaje = "Se te ha asignado el pedido #{$id_pedido} para entrega.";
            $sql_notif->execute();

            // Vendedor que asignó
            $id_vendedor = $_SESSION['id_usuario'];
            $notif_id = $id_vendedor;
            $notif_titulo = "Repartidor asignado";
            $notif_mensaje = "Has asignado el repartidor al pedido #{$id_pedido} exitosamente.";
            $sql_notif->execute();

            // Cliente
            if (!empty($pedido->id_cliente) && $pedido->id_cliente != $id_vendedor) {
                $notif_id = $pedido->id_cliente;
                $notif_titulo = "Repartidor asignado";
                $notif_mensaje = "Tu pedido #{$id_pedido} ya tiene un repartidor asignado. ¡Pronto lo recibirás!";
                $sql_notif->execute();
            }

            // Admins
            $sql_admins = $conexion->query("SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
            while ($admin = $sql_admins->fetch(PDO::FETCH_OBJ)) {
                if ($admin->id_usuario != $id_vendedor && $admin->id_usuario != $pedido->id_cliente) {
                    $notif_id = $admin->id_usuario;
                    $notif_titulo = "Repartidor asignado";
                    $notif_mensaje = "Se asignó un repartidor al pedido #{$id_pedido}.";
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Repartidor asignado exitosamente al pedido #{$id_pedido}.";
            header("Location: ../../vendedor/gestion_pedidos.php");
            exit;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al asignar repartidor: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al asignar el repartidor."];
            header("Location: ../../vendedor/asignar_repartidor.php?id_pedido=" . urlencode($_POST['id_pedido']));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../vendedor/asignar_repartidor.php?id_pedido=" . urlencode($_POST['id_pedido']));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes."];
    header("Location: ../../vendedor/gestion_pedidos.php");
    exit;
}
