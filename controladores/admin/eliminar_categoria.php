<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_categoria'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_categoria = trim(Crypto::decrypt($_POST['id_categoria']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_categoria)) {
        $errores[] = "El ID de la categoría es requerido";
    } elseif (!is_numeric($id_categoria) || $id_categoria <= 0) {
        $errores[] = "El ID de la categoría no es válido";
    }


    // Verificar que la categoría exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_categoria, nombre FROM categorias WHERE id_categoria=:id_categoria LIMIT 1");
            $sql->bindParam(":id_categoria", $id_categoria, PDO::PARAM_INT);
            $sql->execute();
            $categoria = $sql->fetch(PDO::FETCH_OBJ);

            if (!$categoria) {
                $errores[] = "La categoría que desea eliminar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar existencia: " . $e->getMessage());
            $errores[] = "Error al consultar la categoría en la base de datos";
        }
    }

    // Verificar si la categoría tiene productos asociados
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT COUNT(*) as total FROM productos WHERE id_categoria=:id_categoria");
            $sql->bindParam(":id_categoria", $id_categoria, PDO::PARAM_INT);
            $sql->execute();
            $resultado = $sql->fetch(PDO::FETCH_OBJ);

            if ($resultado && $resultado->total > 0) {
                $errores[] = "No se puede eliminar la categoría «{$categoria->nombre}» porque tiene productos asociados.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar productos de la categoría: " . $e->getMessage());
            $errores[] = "Error interno al verificar las dependencias de la categoría.";
        }
    }

    // Si no hay errores, eliminar la categoria
    if (empty($errores)) {
        try {
            // Eliminar categoría
            $sql = $conexion->prepare("DELETE FROM categorias WHERE id_categoria=:id_categoria");
            $sql->bindParam(":id_categoria", $id_categoria, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "Categoría «{$categoria->nombre}» eliminada correctamente.";
            header("Location: ../../admin/gestion_categorias.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al eliminar la categoría: " . $e->getMessage());
            $errores[] = "Ocurrió un problema técnico al intentar eliminar la categoría.";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/gestion_categorias.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_categorias.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_categorias.php");
    exit;
}
