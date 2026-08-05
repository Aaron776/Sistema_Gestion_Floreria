<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['password']) && isset($_POST['email']) && isset($_POST['rol']) && isset($_POST['confirmar_password']) && isset($_POST['telefono'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $rol = trim($_POST['rol']);
    $telefono = trim($_POST['telefono']);
    $direccion=trim($_POST['direccion'] ?? '');
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)){
        $errores[]="El nombre solo puede contener letras y espacios";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(empty($telefono)){
        $errores[]="El telefono es requerido";
    }elseif(!preg_match("/^[0-9]+$/", $telefono)){
        $errores[]="El telefono solo puede contener numeros";
    }elseif(strlen($telefono)>20){
        $errores[]="El telefono es muy largo";
    }

    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email es muy largo";
    }

    if(empty($password)){
        $errores[]="La contraseña es requerida";
    }elseif(strlen($password)<5){
        $errores[]="La contraseña debe tener al menos 5 caracteres";
    }

    if(empty($confirmar_password)){
        $errores[]="La confirmacion de contraseña es requerida";
    }elseif($confirmar_password !== $password){
        $errores[]="Las contraseñas no coinciden";
    }

    if(empty($rol)){
        $errores[]="El rol es requerido";
    }elseif(!in_array($rol, ['admin', 'vendedor','repartidor'])){
        $errores[]="El rol asignado no es válido para este sistema";
    }

    if(!empty($direccion)){
        if(strlen($direccion)>500){
            $errores[]="La direccion es muy larga";
        }
    }

    // Verificar si no existe otro usuario con el mismo email o telefono (independiente del estado por el UNIQUE INDEX)
    try{
        $sql=$conexion->prepare("SELECT email, telefono FROM usuarios WHERE email=:email OR telefono=:telefono LIMIT 1");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->execute();
        $usuario_repetido=$sql->fetch(PDO::FETCH_OBJ);
        if($usuario_repetido){
            if($usuario_repetido->email === $email){
                $errores[]="Este email ya está registrado en el sistema";
            }
            if($usuario_repetido->telefono === $telefono){
                $errores[]="Este teléfono ya está registrado en el sistema";
            }
        }
    }catch(PDOException $e){
        error_log("Error al verificar el usuario: " . $e->getMessage());
        $errores[]="Error al verificar disponibilidad del email y telefono";
    }

    // Si no hay errores, registrar al usuario
    if(empty($errores)){
        try{
            // Insertar usuario en la base de datos
            $password_hash=password_hash($password, PASSWORD_DEFAULT);
            $sql=$conexion->prepare("INSERT INTO usuarios (nombre, telefono,direccion, email, password, rol) VALUES (:nombre,:telefono,:direccion,:email,:password,:rol)");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":password", $password_hash, PDO::PARAM_STR);
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->execute();

            // Notificación en la BD
            try {
                $notif = $conexion->prepare(
                    "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo)
                     VALUES (:id_usuario, :titulo, :mensaje, 'sistema')"
                );
                $titulo_notif = "Usuario creado";
                $mensaje_notif = "Has creado el usuario «{$nombre}» con rol «{$rol}».";
                $notif->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
                $notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $notif->execute();
            } catch (PDOException $e) {
                error_log("Error al crear notificación: " . $e->getMessage());
            }

            $_SESSION['exito']="Usuario «{$nombre}» registrado como «{$rol}» correctamente.";
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar el usuario: " . $e->getMessage());
            $_SESSION['errores'] = ["Error crítico al guardar el usuario en la base de datos"];
            header("Location: ../../admin/agregar_usuario.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_usuario.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/agregar_usuario.php");
    exit;
}



?>