<?php
require_once '../../conexion/bd.php';
require_once '../../conexion/session.php';
require_once '../../helpers/Encriptar.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../../.env'); // Carga las variables de entorno

// Verificar que tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errores'] = ["Tu sesión ha expirado. Intenta de nuevo."];
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }

    //Recibir datos
    $id_usuario = Crypto::decrypt(trim($_POST['id_usuario']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El id del usuario es obligatorio";
    } elseif (!is_numeric($id_usuario)) {
        $errores[] = "El id del usuario debe ser numerico";
    } elseif ($id_usuario <= 0) {
        $errores[] = "El id del usuario debe ser mayor a cero";
    }

    // Verificar que el usuario a editar existe Y está activo en la base de datos
    // (No se deben poder editar la contraseña de usuarios que han sido desactivados/eliminados)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id_usuario, email FROM usuarios WHERE id_usuario=:id_usuario AND estado='activo' LIMIT 1");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (!$usuario_existe) {
                $errores[] = "El usuario no existe o ha sido desactivado y no puede editar la contraseña.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar existencia: " . $e->getMessage());
            $errores[] = "Error al consultar el usuario en la base de datos";
        }
    }

    // Si no hay errores procedemos a editar la contraseña del usuario
    if (empty($errores)) {
        // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE id_usuario=:id_usuario");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Registrar Notificación al Usuario Afectado
            $mensaje_audit = "El administrador " . $_SESSION['nombre'] . " ha restablecido tu contraseña del sistema, revisa tu bandeja de entrada.";
            $usuario_destino = $usuario_existe->id_usuario;
            $titulo_notif = "Contraseña restablecida";

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, leida) 
                                           VALUES (:id_usuario, :titulo, :mensaje, 'sistema', 'no')");
            $sql_notif->bindParam(":id_usuario", $usuario_destino, PDO::PARAM_INT);
            $sql_notif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $mensaje_audit, PDO::PARAM_STR);
            $sql_notif->execute();

            // 3. Enviar correo con PHPMailer
            $mail = new PHPMailer(true);

            // Configuramos PHPMailer
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['MAIL_USER'];
            $mail->Password   = $env['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Opciones para evitar errores de certificado SSL en entorno local (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom($env['MAIL_USER'], 'Florería Pétalos');
            $mail->addAddress($usuario_existe->email);

            $mail->isHTML(true);
            $mail->Subject = 'Nueva Contraseña - Florería Pétalos';
            $mail->Body    = "
                <div style='background-color: #f4f1eb; padding: 50px 20px; font-family: \"Inter\", \"Segoe UI\", Helvetica, Arial, sans-serif;'>
                    <div style='max-width: 560px; margin: 0 auto; background: rgba(255,255,255,0.92); border: 1px solid rgba(200,122,90,0.3); border-radius: 40px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.06);'>

                        <!-- Header -->
                        <div style='padding: 40px 30px; text-align: center; border-bottom: 1px solid #ede8e0;'>
                            <h1 style='margin: 0; font-size: 28px; font-weight: 700; color: #2d2a24; letter-spacing: -0.5px;'>
                                <span style='color: #c87a5a;'>Pétalos</span> 🌸
                            </h1>
                            <p style='margin: 8px 0 0; font-size: 12px; color: #7f6e5d; text-transform: uppercase; letter-spacing: 2px;'>Florería & Eventos</p>
                        </div>

                        <!-- Contenido -->
                        <div style='padding: 45px 40px; color: #2d2a24;'>
                            <h2 style='margin-top: 0; font-size: 22px; font-weight: 700;'>Hola,</h2>
                            <p style='line-height: 1.8; color: #5a4d3f; font-size: 15px;'>Un administrador ha restablecido tu contraseña de acceso. Tus nuevas credenciales temporales son:</p>

                            <!-- Box de Contraseña -->
                            <div style='margin: 35px 0; background: #faf8f5; border: 2px solid #c87a5a; border-radius: 24px; padding: 28px; text-align: center;'>
                                <p style='margin: 0 0 10px; font-size: 11px; color: #c87a5a; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px;'>Nueva Contraseña Temporal</p>
                                <p style='margin: 0; font-size: 32px; font-weight: 800; color: #2d2a24; letter-spacing: 4px; font-family: \"Courier New\", monospace;'>{$nuevaPassword}</p>
                            </div>

                            <div style='background: #fde8e4; border-left: 4px solid #b05b4b; padding: 18px 20px; border-radius: 14px; margin-bottom: 28px;'>
                                <p style='margin: 0; font-size: 13px; color: #8f3d2f; line-height: 1.6;'>
                                    <strong>🔒 Acción Requerida:</strong> Por seguridad, cambia esta contraseña inmediatamente después de iniciar sesión desde la opción <strong>\"Cambiar Contraseña\"</strong> de tu perfil.
                                </p>
                            </div>

                            <div style='text-align: center;'>
                                <a href='{$env['BASE_URL']}' style='display: inline-block; background: #2d2a24; color: #f4f1eb; text-decoration: none; padding: 16px 40px; border-radius: 60px; font-weight: 600; font-size: 15px;'>Acceder al Sistema</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style='padding: 25px 30px; background: #f8f5f0; border-top: 1px solid #ede8e0; text-align: center;'>
                            <p style='margin: 0; font-size: 12px; color: #7f6e5d;'>
                                © 2026 Florería Pétalos<br>
                                <span style='font-size: 10px; opacity: 0.7;'>Este es un mensaje automático, por favor no respondas.</span>
                            </p>
                        </div>
                    </div>
                </div>
            ";

            $mail->send();

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada y enviada correctamente a: " . $usuario_existe->email;
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password: " . $e->getMessage());

            $_SESSION['errores'] = ["Error al procesar la solicitud. Intenta de nuevo más tarde."];
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error en el envio del formulario"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
