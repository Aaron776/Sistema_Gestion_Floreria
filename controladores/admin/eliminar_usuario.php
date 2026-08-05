<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_usuario = trim(Crypto::decrypt($_POST['id_usuario']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El ID del usuario es requerido";
    } elseif (!is_numeric($id_usuario) || $id_usuario <= 0) {
        $errores[] = "El ID del usuario no es válido";
    } elseif ($id_usuario == $_SESSION['id_usuario']) {
        $errores[] = "No puedes eliminar tu propia cuenta mientras estás en sesión";
    }


    // Verificar que el usuario exista y este ene stado activo
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_usuario, rol, nombre FROM usuarios WHERE id_usuario=:id_usuario AND estado='activo' LIMIT 1");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario = $sql->fetch(PDO::FETCH_OBJ);

            if (!$usuario) {
                $errores[] = "El usuario que desea eliminar no existe o no está activo";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar existencia: " . $e->getMessage());
            $errores[] = "Error al consultar el usuario en la base de datos";
        }
    }

    // Protección: No permitir eliminar al único administrador
    if (empty($errores) && $usuario->rol === 'admin') {
        try {
            $sqlCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND estado='activo'");
            $totalAdmins = $sqlCount->fetchColumn();

            if ($totalAdmins <= 1) {
                $errores[] = "Protección de Sistema: No puedes eliminar al único administrador activo";
            }
        } catch (PDOException $e) {
            error_log("Error al contar administradores: " . $e->getMessage());
            $errores[] = "Error de seguridad al validar roles críticos";
        }
    }

    // Verificar pedidos activos si es vendedor
    if (empty($errores) && $usuario->rol === 'vendedor') {
        try {
            $sqlAct = $conexion->prepare(
                "SELECT COUNT(*) FROM pedidos WHERE id_usuario=:id_usuario AND estado NOT IN ('entregado','cancelado')"
            );
            $sqlAct->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sqlAct->execute();
            if ($sqlAct->fetchColumn() > 0) {
                $errores[] = "El vendedor tiene pedidos activos. Reasígnelos antes de desactivarlo.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar pedidos: " . $e->getMessage());
            $errores[] = "Error al consultar pedidos del vendedor";
        }
    }

    // Verificar entregas pendientes si es repartidor
    if (empty($errores) && $usuario->rol === 'repartidor') {
        try {
            $sqlEnt = $conexion->prepare(
                "SELECT COUNT(*) FROM entregas WHERE id_repartidor=:id_repartidor AND estado != 'entregado'"
            );
            $sqlEnt->bindParam(":id_repartidor", $id_usuario, PDO::PARAM_INT);
            $sqlEnt->execute();
            if ($sqlEnt->fetchColumn() > 0) {
                $errores[] = "El repartidor tiene entregas pendientes. Reasígnelas antes de desactivarlo.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar entregas: " . $e->getMessage());
            $errores[] = "Error al consultar entregas del repartidor";
        }
    }

    // Si no hay errores, eliminar al usuario
    if (empty($errores)) {
        try {
            // Desactivar usuario
            $sql = $conexion->prepare("UPDATE usuarios SET estado='inactivo' WHERE id_usuario=:id_usuario");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // Notificación en la BD
            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Usuario desactivado";
                $mensaje_notif = "Has desactivado al usuario «{$usuario->nombre}» (rol: {$usuario->rol}).";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            $_SESSION['exito'] = "Usuario «{$usuario->nombre}» desactivado correctamente.";
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al eliminar el usuario: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al intentar eliminar el usuario"];
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
