<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede activar al usuario)
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
    }


    // Verificar que el usuario exista y este ene stado inactivo
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_usuario, rol, nombre FROM usuarios WHERE id_usuario=:id_usuario AND estado='inactivo' LIMIT 1");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario = $sql->fetch(PDO::FETCH_OBJ);

            if (!$usuario) {
                $errores[] = "El usuario que desea activar no existe o no está inactivo";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar existencia: " . $e->getMessage());
            $errores[] = "Error al consultar el usuario en la base de datos";
        }
    }

    // Si no hay errores, activar al usuario
    if (empty($errores)) {
        try {
            // Activar usuario
            $sql = $conexion->prepare("UPDATE usuarios SET estado='activo' WHERE id_usuario=:id_usuario");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // Notificación en la BD
            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Usuario activado";
                $mensaje_notif = "Has activado al usuario «{$usuario->nombre}» (rol: {$usuario->rol}).";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            $_SESSION['exito'] = "Usuario «{$usuario->nombre}» activado correctamente.";
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al activar el usuario: " . $e->getMessage());
            $errores[] = "Error crítico al intentar activar el usuario";
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
