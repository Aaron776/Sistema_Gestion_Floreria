<?php
require_once 'conexion/session.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pétalos · Crear cuenta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f1eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background-image:
                radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.06) 0%, transparent 50%);
        }

        .register-container {
            max-width: 520px;
            width: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 2.8rem 2.5rem;
            border: 1px solid rgba(255, 245, 235, 0.5);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
            max-height: 98vh;
            overflow-y: auto;
        }

        .register-container::-webkit-scrollbar {
            width: 4px;
        }

        .register-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .register-container::-webkit-scrollbar-thumb {
            background: #dccfc2;
            border-radius: 10px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d2a24;
            text-decoration: none;
            margin-bottom: 0.3rem;
        }

        .register-header .brand i {
            color: #c87a5a;
            font-size: 2rem;
        }

        .register-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2d2a24;
            margin-top: 0.3rem;
        }

        .register-header p {
            color: #7f6e5d;
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.82rem;
            color: #2d2a24;
            margin-bottom: 0.3rem;
        }

        .form-group label .required {
            color: #b05b4b;
            margin-left: 2px;
        }

        .form-group .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 1rem;
            color: #a28d7a;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .form-group .input-wrapper:focus-within i {
            color: #c87a5a;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border-radius: 60px;
            border: 2px solid #ede8e0;
            background: #faf8f5;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s;
            color: #2d2a24;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c87a5a;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
        }

        .form-group input::placeholder {
            color: #b5aca2;
        }

        .form-group input.error {
            border-color: #b05b4b;
            background: #fde8e4;
        }

        .form-group .error-text {
            font-size: 0.75rem;
            color: #b05b4b;
            margin-top: 0.3rem;
            display: none;
            align-items: center;
            gap: 0.4rem;
        }

        .form-group .error-text.show {
            display: flex;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .password-hint {
            font-size: 0.7rem;
            color: #7f6e5d;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .password-hint i {
            color: #c87a5a;
        }

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin: 1.2rem 0 1.5rem;
            font-size: 0.85rem;
            color: #6b5d4f;
        }

        .terms input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            margin-top: 2px;
            accent-color: #c87a5a;
            cursor: pointer;
        }

        .terms a {
            color: #c87a5a;
            text-decoration: none;
            font-weight: 500;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .btn-register {
            width: 100%;
            padding: 0.9rem;
            border-radius: 60px;
            border: none;
            background: #2d2a24;
            color: #f4f1eb;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.25s;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-register:hover {
            background: #1e1a16;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
        }

        .btn-register i {
            color: #c87a5a;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.8rem 0;
            color: #b5aca2;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ede8e0;
        }

        .social-register {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.8rem;
        }

        .social-register a {
            width: 48px;
            height: 48px;
            border-radius: 48px;
            background: #f8f5f0;
            border: 1px solid #ede8e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b5d4f;
            font-size: 1.2rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .social-register a:hover {
            background: #f1ebe4;
            border-color: #c87a5a;
            color: #c87a5a;
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            font-size: 0.9rem;
            color: #6b5d4f;
        }

        .login-link a {
            color: #c87a5a;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: #b05b4b;
            text-decoration: underline;
        }

        .success-message {
            background: #e2f0e6;
            color: #3f6a4f;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            display: none;
            align-items: center;
            gap: 0.6rem;
        }

        .success-message.show {
            display: flex;
        }

        /* ===== ALERTAS DEL SISTEMA ===== */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 16px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            border: 1px solid transparent;
            animation: alertFadeIn 0.3s ease;
        }

        .alert i {
            font-size: 1.1rem;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .alert-success {
            background: #e2f0e6;
            border-color: #c8dfd0;
            color: #3f6a4f;
        }

        .alert-success i {
            color: #3f6a4f;
        }

        .alert-danger {
            background: #fde8e4;
            border-color: #f5d0c8;
            color: #8f3d2f;
        }

        .alert-danger i {
            color: #b05b4b;
        }

        .alert ul {
            margin: 0;
            padding: 0;
            list-style: none;
            flex: 1;
        }

        .alert ul li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
        }

        .alert ul li:last-child {
            margin-bottom: 0;
        }

        @keyframes alertFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 2rem 1.5rem;
                border-radius: 28px;
                max-height: 100vh;
            }

            .register-header h1 {
                font-size: 1.4rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .btn-register {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>

    <div class="register-container">
        <div class="register-header">
            <a href="#" class="brand">
                <i class="fas fa-seedling"></i>
                <span>Pétalos</span>
            </a>
            <h1>Crear cuenta nueva</h1>
            <p>Comienza a gestionar tu floristería</p>
        </div>

        <?php if (isset($_SESSION['errores'])) : ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($_SESSION['errores'] as $error) : ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['exito'])) : ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <form id="registerForm" action="controladores/registro.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                <label for="fullname">Nombre completo <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="fullname" name="nombre" placeholder="Ej: María Rodríguez" required>
                </div>
                <div class="error-text" id="fullnameError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Por favor, ingresa tu nombre completo</span>
                </div>
            </div>

            <!-- Teléfono y Dirección (fila) -->
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Teléfono <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="telefono" placeholder="Ej: 555-1234" required>
                    </div>
                    <div class="error-text" id="phoneError">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Ingresa un número de teléfono válido</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Dirección <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="address" name="direccion" placeholder="Ej: Calle Primavera 123" required>
                    </div>
                    <div class="error-text" id="addressError">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Por favor, ingresa tu dirección</span>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Correo electrónico <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="error-text" id="emailError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Ingresa un correo electrónico válido</span>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <label for="password">Contraseña <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8">
                </div>
                <div class="password-hint">
                    <i class="fas fa-info-circle"></i>
                    <span>Mínimo 8 caracteres, incluye mayúscula, minúscula y número</span>
                </div>
                <div class="error-text" id="passwordError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>La contraseña debe tener al menos 8 caracteres</span>
                </div>
            </div>

            <!-- Repetir contraseña -->
            <div class="form-group">
                <label for="passwordConfirm">Repetir contraseña <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" id="passwordConfirm" name="confirmar_password" placeholder="Repite tu contraseña" required>
                </div>
                <div class="error-text" id="passwordConfirmError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Las contraseñas no coinciden</span>
                </div>
            </div>

            <!-- Términos y condiciones -->
            <div class="terms">
                <input type="checkbox" id="terms" required>
                <label for="terms">
                    Acepto los <a href="#">Términos y Condiciones</a> y la
                    <a href="#">Política de Privacidad</a>
                </label>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Crear cuenta
            </button>
        </form>

        <div class="divider">o regístrate con</div>

        <div class="social-register">
            <a href="#" aria-label="Google"><i class="fab fa-google"></i></a>
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Apple"><i class="fab fa-apple"></i></a>
        </div>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
        </div>
    </div>

</body>

</html>