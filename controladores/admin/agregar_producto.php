<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['categoria']) && isset($_POST['descripcion']) && isset($_POST['precio'])&& isset($_POST['stock'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo"];
        header("Location: ../../admin/agregar_categoria.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria=trim($_POST['categoria']);
    $precio=trim($_POST['precio']);
    $stock=trim($_POST['stock']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]+$/", $nombre)){
        $errores[]="El nombre solo puede contener letras, números y guiones";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(!empty($descripcion)){
        if(strlen($descripcion)>500){
            $errores[]="La descripcion es muy larga";
        }
    }

    if($categoria === ""){
        $errores[]="La categoria es requerida";
    }

    if($precio === ""){
        $errores[]="El precio es requerido";
    }elseif(!filter_var($precio, FILTER_VALIDATE_FLOAT) && $precio !== "0" && $precio !== "0.00"){
        $errores[]="El precio no es valido";
    }

    if($stock === ""){
        $errores[]="El stock es requerido";
    }elseif(!filter_var($stock, FILTER_VALIDATE_INT) && $stock !== "0"){
        $errores[]="El stock no es valido";
    }

     // Manejo del archivo (si se subió una foto)
    $foto = null;
    // 1. Verificar si el campo 'foto' existe en $_FILES y si no hubo errores críticos de subida (como archivo muy pesado para PHP)
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // 2. Definir los tipos de imágen permitidos (MIME types)
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['imagen']['type']; // obtener el tipo de archivo de la imagen que se subio
        
        // 3. Validar que el tipo de archivo esté en la lista blanca
        if (!in_array($file_type, $allowed_types)) {
            $errores[] = "Tipo de archivo no permitido. Solo JPG, JPEG y PNG.";
        // 4. Validar que el tamaño no supere los 2MB (2 * 1024 * 1024 bytes)
        } elseif ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
            $errores[] = "La foto es demasiado grande (máx. 2MB).";
        } else {
            // 5. Obtener la extensión original del archivo (ej: jpg, png)
            $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            // 6. Generar un nombre único e irrepetible para evitar choques (ej: pet_65f1a23b4c5d6.jpg)
            $nombreArchivo = uniqid('producto_', true) . '.' . $extension;
            // 7. Definir la ruta donde se guardará (subiendo 2 niveles para llegar a la carpeta raíz 'app')
            $directorioDestino = '../../app/fotos_productos/';
            
            // 8. Verificar si la carpeta existe; si no, crearla automáticamente con permisos de escritura
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0777, true);
            }
            
            // 9. Combinar el directorio con el nombre único para tener la ruta completa
            $rutaDestino = $directorioDestino . $nombreArchivo;
            // 10. Mover el archivo desde la carpeta temporal de PHP a su ubicación final en el servidor
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                // 11. Si todo salió bien, guardamos el nombre en la variable que irá a la base de datos
                $foto = $nombreArchivo;
            } else {
                $errores[] = "Error al guardar la imagen en el servidor.";
            }
        }
    }

    

    // Si no hay errores, registrar el prodcuto
    if(empty($errores)){
        try{
            // Insertar producto en la base de datos
            $sql=$conexion->prepare("INSERT INTO productos (id_categoria, nombre, descripcion, precio, stock, imagen) VALUES (:id_categoria,:nombre,:descripcion,:precio,:stock,:imagen)");
            $sql->bindParam(":id_categoria", $categoria, PDO::PARAM_INT);
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":stock", $stock, PDO::PARAM_INT);
            $sql->bindParam(":imagen", $foto, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito']="Producto «{$nombre}» creado correctamente.";
            header("Location: ../../admin/gestion_productos.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar el producto: " . $e->getMessage());
            $_SESSION['errores']=["Error crítico al guardar el producto en la base de datos"];
            header("Location: ../../admin/agregar_producto.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_producto.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/agregar_producto.php");
    exit;
}



?>