<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../login.php");
        exit;
    }

    // Control de intentos fallidos (protección contra fuerza bruta)
    $max_intentos = 3;
    $tiempo_bloqueo = 5 * 60; // 5 minutos
    if (!isset($_SESSION['intentos_fallidos'])) {
        $_SESSION['intentos_fallidos'] = 0;
        $_SESSION['bloqueo_hasta'] = 0;
    }

    if ($_SESSION['intentos_fallidos'] >= $max_intentos && time() < $_SESSION['bloqueo_hasta']) {
        $min_restantes = ceil(($_SESSION['bloqueo_hasta'] - time()) / 60);
        $_SESSION['errores'] = ["Demasiados intentos fallidos. Inténtalo de nuevo en {$min_restantes} minuto(s)."];
        header("Location: ../login.php");
        exit;
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $password = $_POST['password']; // No usar trim: conserva espacios legítimos de la contraseña
    $errores=[];

    // Validacion y sanitizacion
    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email) > 100){
        $errores[]="El email no puede superar los 100 caracteres";
    }

    if(empty($password)){
        $errores[]="La contraseña es requerida";
    }

    // Verificar si el usuario que quiere ingresar esta en estado inactivo
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT estado FROM usuarios WHERE email=:email");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $usuario_estado=$sql->fetch(PDO::FETCH_OBJ);
            if($usuario_estado && $usuario_estado->estado === 'inactivo'){
            $errores[]="Tu cuenta esta desactivada. Por favor, contacta al administrador.";
            }
        }catch(PDOException $e){
            error_log("Error al verificar estado de usuario: " . $e->getMessage());
            $errores[]="Error crítico al conectarse con el servidor.";
        }
    }
    
    // Si no hay errores, iniciar sesion
    if(empty($errores)){
        // Iniciar sesion
        try{
            $sql=$conexion->prepare("SELECT id_usuario,nombre,rol,password FROM usuarios WHERE email=:email AND estado='activo'");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $usuario=$sql->fetch(PDO::FETCH_OBJ);

            if($usuario && password_verify($password, $usuario->password)){
                // Regenerar el ID de sesión (previene fijación de sesión)
                session_regenerate_id(true);
                // Reiniciar contador de intentos fallidos
                $_SESSION['intentos_fallidos'] = 0;
                $_SESSION['bloqueo_hasta'] = 0;

                // 1. Actualizar el Ultimo acceso solo si la clave es correcta
                try {
                    $sqlAcceso = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id");
                    $sqlAcceso->bindParam(":id", $usuario->id_usuario, PDO::PARAM_INT);
                    $sqlAcceso->execute();
                } catch (PDOException $e) {
                    error_log("Error al actualizar el ultimo acceso: " . $e->getMessage());
                }

                // 2. Establecer variables de sesión
                $_SESSION['id_usuario']=$usuario->id_usuario;
                $_SESSION['nombre']=$usuario->nombre;
                $_SESSION['rol']=$usuario->rol;
                $_SESSION['logueado']=true;
                
                switch($usuario->rol){
                    case 'admin':
                        header("Location: ../admin/dash_admin.php");
                        break;
                    case 'vendedor':
                        header("Location: ../vendedor/dash_vendedor.php");
                        break;
                    case 'repartidor':
                        header("Location: ../repartidor/dash_repartidor.php");
                        break;
                    default:
                        header("Location: ../login.php");
                        break;
                }
                exit;
            }else{
                $_SESSION['intentos_fallidos']++;
                if ($_SESSION['intentos_fallidos'] >= $max_intentos) {
                    $_SESSION['bloqueo_hasta'] = time() + $tiempo_bloqueo;
                }
                $_SESSION['errores'] = ["Credenciales incorrectas"]; 
                header("Location: ../login.php");
                exit;
            }
        }catch(PDOException $e){
            error_log("Error al iniciar sesión: " . $e->getMessage());
            $_SESSION['errores'] = ["Error interno al iniciar sesión. Intente más tarde."];
            header("Location: ../login.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../login.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../login.php");
    exit;
}



?>