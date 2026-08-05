<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_producto'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_producto = trim(Crypto::decrypt($_POST['id_producto']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_producto)) {
        $errores[] = "El ID del producto es requerido";
    } elseif (!is_numeric($id_producto) || $id_producto <= 0) {
        $errores[] = "El ID del producto no es válido";
    }


    // Verificar que el producto exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_producto, nombre, imagen FROM productos WHERE id_producto=:id_producto LIMIT 1");
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();
            $producto = $sql->fetch(PDO::FETCH_OBJ);

            if (!$producto) {
                $errores[] = "El producto que desea eliminar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar existencia: " . $e->getMessage());
            $errores[] = "Error al consultar el producto en la base de datos";
        }
    }

    // Si no hay errores, "eliminar" (desactivar) el producto
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE productos SET estado='inactivo', imagen=NULL WHERE id_producto=:id_producto");
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();

            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Producto eliminado";
                $mensaje_notif = "Has eliminado al producto «{$producto->nombre}».";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            $conexion->commit();

            if (!empty($producto->imagen)) {
                $rutaImagen = "../../app/fotos_productos/" . $producto->imagen;
                if (file_exists($rutaImagen)) {
                    unlink($rutaImagen);
                }
            }

            $_SESSION['exito'] = "Producto «{$producto->nombre}» eliminado (inactivado) correctamente.";
            header("Location: ../../admin/gestion_productos.php");
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al eliminar el producto: " . $e->getMessage());
            $errores[] = "Ocurrió un problema técnico al intentar eliminar el producto.";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/gestion_productos.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_productos.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_productos.php");
    exit;
}
