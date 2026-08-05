<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['telefono']) && isset($_POST['confirmar_password']) && isset($_POST['direccion'])){
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Sesión expirada, intenta de nuevo"];
        header("Location: ../registro.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion=trim($_POST['direccion']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmar_password = trim($_POST['confirmar_password']);
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
    }elseif(!preg_match("/^[+\-\s0-9]+$/", $telefono)){
        $errores[]="El teléfono solo puede contener números, +, - y espacios";
    }elseif(strlen($telefono)>20){
        $errores[]="El telefono es muy largo";
    }

    if(empty($direccion)){
        $errores[]="La direccion es requerida";
    }elseif(strlen($direccion)>100){
        $errores[]="La direccion es muy larga";
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
        $errores[]="La confirmacion de la contraseña es requerida";
    }elseif($password !== $confirmar_password){
        $errores[]="Las contraseñas no coinciden";
    }

    // Verificar si existe otro usuario con el mismo email o telefono
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT email,telefono FROM usuarios WHERE email=:email OR telefono=:telefono limit 1");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->execute();
            $usuario_existe=$sql->fetch(PDO::FETCH_OBJ);
            if($usuario_existe){
                if($usuario_existe->email === $email){
                    $errores[]="Ya existe un usuario con este email";
                }
                if($usuario_existe->telefono === $telefono){
                    $errores[]="Ya existe un usuario con este telefono";
                }
            }
        }catch(PDOException $e){
            error_log("Error al verificar duplicados: " . $e->getMessage());
        }
    }
    
    // Si no hay errores, registrar usaurio cliente
    if(empty($errores)){
        try{
            $password_hasheada=password_hash($password, PASSWORD_DEFAULT);
            $sql=$conexion->prepare("INSERT INTO usuarios (nombre,telefono,direccion,email,password,rol) VALUES (:nombre,:telefono,:direccion,:email,:password_hasheada,'cliente')");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":password_hasheada", $password_hasheada, PDO::PARAM_STR);
            $sql->execute();
            
            $_SESSION['exito'] = "Registro exitoso. Ahora puedes iniciar sesión.";
            header("Location: ../login.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar usuario: " . $e->getMessage());
            $errores[]="Error al registrar usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../registro.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al registrar usuario"];
    header("Location: ../registro.php");
    exit;
}



?>