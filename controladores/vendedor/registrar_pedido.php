<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo vendedor puede registrar pedido)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cliente'], $_POST['producto'], $_POST['direccion_entrega'], $_POST['cantidad'], $_POST['metodo_pago'])) {

    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo"];
        header("Location: ../../vendedor/registrar_pedido.php");
        exit;
    }

    // Recibir datos
    $id_vendedor = $_SESSION['id_usuario'];
    $cliente = trim($_POST['cliente']);
    $producto = trim($_POST['producto']);
    $direccion_entrega = trim($_POST['direccion_entrega']);
    $cantidad = trim($_POST['cantidad']);
    $mensaje_tarjeta = trim($_POST['mensaje_tarjeta'] ?? '');
    $metodo_pago = trim($_POST['metodo_pago']);
    $referencia = trim($_POST['referencia'] ?? '');
    $pagado_ahora = isset($_POST['pagado_ahora']) && $_POST['pagado_ahora'] === '1';
    $estado_pago = $pagado_ahora ? 'pagado' : 'pendiente';
    $errores = [];

    // ===== VALIDACIONES =====

    // Cliente
    if (empty($cliente)) {
        $errores[] = "El cliente es requerido";
    } elseif (!is_numeric($cliente) || $cliente <= 0) {
        $errores[] = "El cliente no es válido";
    }

    // Producto
    if (empty($producto)) {
        $errores[] = "El producto es requerido";
    } elseif (!is_numeric($producto) || $producto <= 0) {
        $errores[] = "El producto no es válido";
    }

    // Dirección de entrega
    if (empty($direccion_entrega)) {
        $errores[] = "La dirección de entrega es requerida";
    } elseif (strlen($direccion_entrega) > 200) {
        $errores[] = "La dirección de entrega es muy larga";
    }

    // Cantidad (debe ser entero >= 1)
    if (empty($cantidad)) {
        $errores[] = "La cantidad es requerida";
    } elseif (!filter_var($cantidad, FILTER_VALIDATE_INT) || $cantidad < 1) {
        $errores[] = "La cantidad debe ser al menos 1";
    }

    // Mensaje de tarjeta (opcional, max 500)
    if (!empty($mensaje_tarjeta) && strlen($mensaje_tarjeta) > 500) {
        $errores[] = "El mensaje de la tarjeta es muy largo (máximo 500 caracteres)";
    }

    // Método de pago
    $metodos_validos = ['efectivo', 'tarjeta', 'transferencia', 'paypal'];
    if (empty($metodo_pago)) {
        $errores[] = "El método de pago es requerido";
    } elseif (!in_array($metodo_pago, $metodos_validos)) {
        $errores[] = "El método de pago no es válido";
    }

    // Referencia (obligatoria para tarjeta y transferencia)
    if (in_array($metodo_pago, ['tarjeta', 'transferencia'])) {
        if (empty($referencia)) {
            $errores[] = "La referencia es requerida para pagos con {$metodo_pago}";
        } elseif (strlen($referencia) > 100) {
            $errores[] = "La referencia es muy larga (máximo 100 caracteres)";
        }
    }

    // Si no hay errores de formato, validar contra la BD
    if (empty($errores)) {
        try {
            // Verificar que el cliente existe, está activo y es rol cliente
            $sql_cliente = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND rol = 'cliente' AND estado = 'activo'");
            $sql_cliente->bindParam(":id", $cliente, PDO::PARAM_INT);
            $sql_cliente->execute();
            if (!$sql_cliente->fetch()) {
                $errores[] = "El cliente seleccionado no existe o no está activo";
            }

            // Verificar que el vendedor está activo
            $sql_vendedor = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND estado = 'activo'");
            $sql_vendedor->bindParam(":id", $id_vendedor, PDO::PARAM_INT);
            $sql_vendedor->execute();
            if (!$sql_vendedor->fetch()) {
                $errores[] = "No tienes permisos para registrar pedidos";
            }

            // Verificar que el producto existe, está activo y obtener precio + stock reales
            $sql_producto = $conexion->prepare("SELECT id_producto, precio, stock FROM productos WHERE id_producto = :id AND estado = 'activo'");
            $sql_producto->bindParam(":id", $producto, PDO::PARAM_INT);
            $sql_producto->execute();
            $producto_data = $sql_producto->fetch(PDO::FETCH_OBJ);

            if (!$producto_data) {
                $errores[] = "El producto seleccionado no existe o no está activo";
            } else {
                // Validar stock suficiente
                if ($cantidad > $producto_data->stock) {
                    $errores[] = "Stock insuficiente. Disponible: {$producto_data->stock}";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al validar datos del pedido: " . $e->getMessage());
            $errores[] = "Error al validar los datos. Intente de nuevo.";
        }
    }

    // Si no hay errores, registrar el pedido
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Precio REAL del servidor (no confiar en el POST)
            $precio_real = $producto_data->precio;
            $subtotalCalculado = $precio_real * $cantidad;

            // 1. Insertar pedido (con total y estado)
            $sql = $conexion->prepare("INSERT INTO pedidos (id_cliente, id_usuario, direccion_entrega, mensaje_tarjeta, total) VALUES (:id_cliente, :id_usuario, :direccion_entrega, :mensaje_tarjeta, :total)");
            $sql->bindParam(":id_cliente", $cliente, PDO::PARAM_INT);
            $sql->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
            $sql->bindParam(":direccion_entrega", $direccion_entrega, PDO::PARAM_STR);
            $sql->bindParam(":mensaje_tarjeta", $mensaje_tarjeta, PDO::PARAM_STR);
            $sql->bindParam(":total", $subtotalCalculado, PDO::PARAM_STR);
            $sql->execute();

            $id_pedido = $conexion->lastInsertId();

            // 2. Insertar detalle del pedido con subtotal calculado correctamente
            $sql_detalle = $conexion->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :subtotal)");
            $sql_detalle->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $sql_detalle->bindParam(":id_producto", $producto, PDO::PARAM_INT);
            $sql_detalle->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $sql_detalle->bindParam(":precio_unitario", $precio_real, PDO::PARAM_STR);
            $sql_detalle->bindParam(":subtotal", $subtotalCalculado, PDO::PARAM_STR);
            $sql_detalle->execute();

            // 3. Registrar pago
            $sql_pago = $conexion->prepare("INSERT INTO pagos (id_pedido, id_usuario, metodo_pago, referencia, monto, estado) VALUES (:id_pedido, :id_usuario, :metodo_pago, :referencia, :monto, :estado)");
            $sql_pago->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
            $sql_pago->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
            $sql_pago->bindParam(":metodo_pago", $metodo_pago, PDO::PARAM_STR);
            $referencia_bd = $referencia ?: null;
            $sql_pago->bindParam(":referencia", $referencia_bd, PDO::PARAM_STR);
            $sql_pago->bindParam(":monto", $subtotalCalculado, PDO::PARAM_STR);
            $sql_pago->bindParam(":estado", $estado_pago, PDO::PARAM_STR);
            $sql_pago->execute();

            // 4. Decrementar stock del producto
            $sql_stock = $conexion->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id_producto = :id");
            $sql_stock->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $sql_stock->bindParam(":id", $producto, PDO::PARAM_INT);
            $sql_stock->execute();

            // Obtener stock restante después del decremento
            $sql_stock_actual = $conexion->prepare("SELECT stock, nombre FROM productos WHERE id_producto = :id");
            $sql_stock_actual->bindParam(":id", $producto, PDO::PARAM_INT);
            $sql_stock_actual->execute();
            $producto_stock = $sql_stock_actual->fetch(PDO::FETCH_OBJ);
            $stock_restante = $producto_stock->stock;
            $nombre_producto = $producto_stock->nombre;

            // Verificar si el stock llegó a 0 → marcar como agotado
            if ($stock_restante == 0) {
                $sql_agotar = $conexion->prepare("UPDATE productos SET estado = 'agotado' WHERE id_producto = :id");
                $sql_agotar->bindParam(":id", $producto, PDO::PARAM_INT);
                $sql_agotar->execute();
            }

            // 5. Registrar movimiento de inventario (salida)
            $sql_mov = $conexion->prepare("INSERT INTO movimientos_inventario (id_producto, id_usuario, tipo, cantidad, motivo) VALUES (:id_producto, :id_usuario, 'salida', :cantidad, :motivo)");
            $sql_mov->bindParam(":id_producto", $producto, PDO::PARAM_INT);
            $sql_mov->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
            $sql_mov->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $motivo = "Pedido #$id_pedido registrado por vendedor";
            $sql_mov->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql_mov->execute();

            // 6. Notificar a todos los admins
            $sql_admins = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND estado='activo'");
            $sql_admins->execute();
            $admins = $sql_admins->fetchAll(PDO::FETCH_OBJ);

            // 7. Notificar stock agotado a todos los admins
            if ($stock_restante == 0) {
                $sql_notif_agotado = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'inventario')");
                $titulo_agotado = "Producto agotado";
                $mensaje_agotado = "El producto \"$nombre_producto\" (ID: $producto) se ha agotado tras el pedido #$id_pedido. Se requiere reabastecimiento.";
                foreach ($admins as $admin) {
                    $sql_notif_agotado->bindParam(":id_usuario", $admin->id_usuario, PDO::PARAM_INT);
                    $sql_notif_agotado->bindParam(":titulo", $titulo_agotado, PDO::PARAM_STR);
                    $sql_notif_agotado->bindParam(":mensaje", $mensaje_agotado, PDO::PARAM_STR);
                    $sql_notif_agotado->execute();
                }
            }

            // 8. Notificar stock bajo a todos los admins (menos de 10 unidades y no está agotado)
            if ($stock_restante > 0 && $stock_restante < 10) {
                $sql_notif_bajo = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'inventario')");
                $titulo_bajo = "Stock bajo";
                $mensaje_bajo = "El producto \"$nombre_producto\" (ID: $producto) tiene solo $stock_restante unidades disponibles tras el pedido #$id_pedido.";
                foreach ($admins as $admin) {
                    $sql_notif_bajo->bindParam(":id_usuario", $admin->id_usuario, PDO::PARAM_INT);
                    $sql_notif_bajo->bindParam(":titulo", $titulo_bajo, PDO::PARAM_STR);
                    $sql_notif_bajo->bindParam(":mensaje", $mensaje_bajo, PDO::PARAM_STR);
                    $sql_notif_bajo->execute();
                }
            }

            // 9. Notificar a todos los admins sobre el nuevo pedido
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'pedido')");
            $titulo = "Nuevo pedido registrado";
            $mensaje_notif = "El vendedor registró el pedido #$id_pedido para el cliente #$cliente. Total: $" . number_format($subtotalCalculado, 2);

            foreach ($admins as $admin) {
                $sql_notif->bindParam(":id_usuario", $admin->id_usuario, PDO::PARAM_INT);
                $sql_notif->bindParam(":titulo", $titulo, PDO::PARAM_STR);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // 10. Notificar al cliente que su pedido fue registrado
            $sql_notif_cliente = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'pedido')");
            $sql_notif_cliente->bindParam(":id_usuario", $cliente, PDO::PARAM_INT);
            $titulo_cliente = "Tu pedido ha sido registrado";
            $mensaje_cliente = "Tu pedido #$id_pedido ha sido registrado exitosamente. Total: $" . number_format($subtotalCalculado, 2) . ". Pronto receberás más información sobre el estado de tu pedido.";
            $sql_notif_cliente->bindParam(":titulo", $titulo_cliente, PDO::PARAM_STR);
            $sql_notif_cliente->bindParam(":mensaje", $mensaje_cliente, PDO::PARAM_STR);
            $sql_notif_cliente->execute();

            // 11. Notificar al vendedor que registró el pedido
            $sql_notif_vendedor = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, 'pedido')");
            $sql_notif_vendedor->bindParam(":id_usuario", $id_vendedor, PDO::PARAM_INT);
            $titulo_vendedor = "Pedido registrado";
            $mensaje_vendedor = "Has registrado el pedido #$id_pedido por $" . number_format($subtotalCalculado, 2) . " para el cliente #$cliente.";
            $sql_notif_vendedor->bindParam(":titulo", $titulo_vendedor, PDO::PARAM_STR);
            $sql_notif_vendedor->bindParam(":mensaje", $mensaje_vendedor, PDO::PARAM_STR);
            $sql_notif_vendedor->execute();

            $conexion->commit();

            $_SESSION['exito'] = "Pedido #$id_pedido registrado correctamente.";
            header("Location: ../../vendedor/gestion_pedidos.php");
            exit;

        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al registrar el pedido: " . $e->getMessage());
            $errores[] = "Error crítico al guardar el pedido en la base de datos";
        }
    }

    // Si hubo errores, redirigir de vuelta
    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: ../../vendedor/registrar_pedido.php");
        exit;
    }

} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../vendedor/gestion_pedidos.php");
    exit;
}
