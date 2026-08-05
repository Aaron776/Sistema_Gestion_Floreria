<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede editar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['categoria']) && isset($_POST['descripcion']) && isset($_POST['precio']) && isset($_POST['stock']) && isset($_POST['id_producto'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo"];
        header("Location: ../../admin/editar_producto.php?id_producto=" . urlencode($_POST['id_producto']));
        exit;
    }

    // Recibir datos
    $id_producto = Crypto::decrypt($_POST['id_producto']);
    if ($id_producto === null || $id_producto === false) {
        $_SESSION['errores'] = ["ID de producto inválido"];
        header("Location: ../../admin/gestion_productos.php");
        exit;
    }

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = trim($_POST['categoria']);
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    $errores = [];

    // Validacion y sanitizacion

    if (empty($id_producto)) {
        $errores[] = "El ID del producto es requerido";
    } elseif (!is_numeric($id_producto) || $id_producto <= 0) {
        $errores[] = "El ID del producto no es válido";
    }

    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]+$/", $nombre)) {
        $errores[] = "El nombre solo puede contener letras, números y guiones";
    } elseif (strlen($nombre) > 150) {
        $errores[] = "El nombre es muy largo (máximo 150 caracteres)";
    }

    if (!empty($descripcion)) {
        if (strlen($descripcion) > 500) {
            $errores[] = "La descripción es muy larga (máximo 500 caracteres)";
        }
    }

    if ($categoria === "") {
        $errores[] = "La categoría es requerida";
    }

    if ($precio === "") {
        $errores[] = "El precio es requerido";
    } elseif (!filter_var($precio, FILTER_VALIDATE_FLOAT) && $precio !== "0" && $precio !== "0.00") {
        $errores[] = "El precio no es válido";
    }

    if ($stock === "") {
        $errores[] = "El stock es requerido";
    } elseif (!filter_var($stock, FILTER_VALIDATE_INT) && $stock !== "0") {
        $errores[] = "El stock no es válido";
    }

    // Verificar que el producto que se quiere editar exista en la base de datos
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_producto FROM productos WHERE id_producto = :id_producto LIMIT 1");
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();
            $producto_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (!$producto_existe) {
                $errores[] = "El producto que quiere editar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el producto: " . $e->getMessage());
            $errores[] = "Error al verificar el producto";
        }
    }

    // Si no hay errores iniciales, procedemos con la lógica de imagen
    if (empty($errores)) {
        // Consultar imagen actual para saber cuál eliminar después si se sube una nueva
        $query_img = $conexion->prepare("SELECT imagen FROM productos WHERE id_producto = :id_producto");
        $query_img->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $query_img->execute();
        $producto_actual = $query_img->fetch(PDO::FETCH_ASSOC);
        $imagen_anterior = $producto_actual['imagen'] ?? "";

        $nueva_imagen_subida = false;
        $imagen_nombre = $imagen_anterior;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen_temp = $_FILES['imagen']['tmp_name'];
            $imagen_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array(strtolower($imagen_extension), $extensiones_permitidas)) {
                $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp)";
            }

            $tamano_maximo = 2 * 1024 * 1024; // 2MB
            if ($_FILES['imagen']['size'] > $tamano_maximo) {
                $errores[] = "La imagen no debe superar los 2MB";
            }

            if (empty($errores)) {
                $imagen_nombre = uniqid("producto_", true) . "." . $imagen_extension;
                $directorioDestino = __DIR__ . "/../../app/fotos_productos/";
                if (move_uploaded_file($imagen_temp, $directorioDestino . $imagen_nombre)) {
                    $nueva_imagen_subida = true;
                } else {
                    $errores[] = "Error al mover la imagen al servidor.";
                }
            }
        }
    }

    // Si no hay errores, editar el producto dentro de una transacción
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Actualizar producto en la base de datos
            $sql = $conexion->prepare(
                "UPDATE productos 
                 SET id_categoria = :id_categoria, nombre = :nombre, 
                     descripcion = :descripcion, precio = :precio, 
                     stock = :stock, imagen = :imagen 
                 WHERE id_producto = :id_producto LIMIT 1"
            );
            $sql->bindParam(":id_categoria", $categoria, PDO::PARAM_INT);
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":stock", $stock, PDO::PARAM_INT);
            $sql->bindParam(":imagen", $imagen_nombre, PDO::PARAM_STR);
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();

            // Notificación en la BD
            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Producto actualizado";
                $mensaje_notif = "Has actualizado el producto «{$nombre}».";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            // Notificación de stock bajo o agotado
            if ((int)$stock <= 10) {
                try {
                    $notif_stock = $conexion->prepare(
                        "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                         VALUES (:id_usuario, :titulo, :mensaje, 'inventario')"
                    );
                    if ((int)$stock === 0) {
                        $titulo_stock = "Sin stock - {$nombre}";
                        $mensaje_stock = "El producto «{$nombre}» se quedó sin stock.";
                    } else {
                        $titulo_stock = "Stock bajo - {$nombre}";
                        $mensaje_stock = "El producto «{$nombre}» tiene solo {$stock} unidades en stock.";
                    }
                    $notif_stock->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                    $notif_stock->bindParam(":titulo", $titulo_stock, PDO::PARAM_STR);
                    $notif_stock->bindParam(":mensaje", $mensaje_stock, PDO::PARAM_STR);
                    $notif_stock->execute();
                } catch (PDOException $e) {
                    error_log("Error al crear notificación de stock bajo: " . $e->getMessage());
                }
            }

            $conexion->commit();

            // LIMPIEZA: Si subimos una imagen nueva, borramos la antigua
            if ($nueva_imagen_subida && !empty($imagen_anterior)) {
                $ruta_anterior = __DIR__ . "/../../app/fotos_productos/" . $imagen_anterior;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }

            $_SESSION['exito'] = "Producto «{$nombre}» editado correctamente.";
            header("Location: ../../admin/gestion_productos.php");
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al editar el producto: " . $e->getMessage());
            $errores[] = "Error crítico al editar el producto en la base de datos";
        }
    }

    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/editar_producto.php?id_producto=" . urlencode(Crypto::encrypt($id_producto)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_productos.php");
    exit;
}
