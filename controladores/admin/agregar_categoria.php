<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['descripcion'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Solicitud no válida, intente de nuevo"];
        header("Location: ../../admin/agregar_categoria.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
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



    // Si no hay errores, registrar al la categoria
    if(empty($errores)){
        try{
            
            // Insertar categoria en la base de datos
            $sql=$conexion->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (:nombre,:descripcion)");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->execute();

            // Notificación en la BD
            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Categoría creada";
                $mensaje_notif = "Has creado la categoría «{$nombre}».";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            $_SESSION['exito']="Categoría «{$nombre}» creada correctamente.";
            header("Location: ../../admin/gestion_categorias.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar la categoría: " . $e->getMessage());
            $_SESSION['errores']=["Error crítico al guardar la categoría en la base de datos"];
            header("Location: ../../admin/agregar_categoria.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_categoria.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_categorias.php");
    exit;
}



?>