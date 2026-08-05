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
  <title>Pétalos · Iniciar sesión</title>
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
      padding: 1.5rem;
      background-image: 
        radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.06) 0%, transparent 50%);
    }

    .login-container {
      max-width: 440px;
      width: 100%;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-radius: 40px;
      padding: 2.8rem 2.5rem;
      border: 1px solid rgba(255, 245, 235, 0.5);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
    }

    .login-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .login-header .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.8rem;
      font-weight: 700;
      color: #2d2a24;
      text-decoration: none;
      margin-bottom: 0.5rem;
    }

    .login-header .brand i {
      color: #c87a5a;
      font-size: 2rem;
    }

    .login-header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #2d2a24;
      margin-top: 0.5rem;
    }

    .login-header p {
      color: #7f6e5d;
      font-size: 0.95rem;
      margin-top: 0.2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 0.85rem;
      color: #2d2a24;
      margin-bottom: 0.4rem;
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
      font-size: 1rem;
    }

    .form-group input {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 2.8rem;
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

    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.8rem;
      font-size: 0.85rem;
    }

    .form-options label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #6b5d4f;
      cursor: pointer;
    }

    .form-options label input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: #c87a5a;
      cursor: pointer;
    }

    .form-options a {
      color: #c87a5a;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .form-options a:hover {
      color: #b05b4b;
      text-decoration: underline;
    }

    .btn-login {
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

    .btn-login:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-login i {
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

    .social-login {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 1.8rem;
    }

    .social-login a {
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

    .social-login a:hover {
      background: #f1ebe4;
      border-color: #c87a5a;
      color: #c87a5a;
      transform: translateY(-2px);
    }

    .register-link {
      text-align: center;
      font-size: 0.9rem;
      color: #6b5d4f;
    }

    .register-link a {
      color: #c87a5a;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .register-link a:hover {
      color: #b05b4b;
      text-decoration: underline;
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

    @media (max-width: 480px) {
      .login-container {
        padding: 2rem 1.5rem;
        border-radius: 28px;
      }
      .login-header h1 {
        font-size: 1.4rem;
      }
      .form-options {
        flex-direction: column;
        gap: 0.8rem;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-header">
      <a href="#" class="brand">
        <i class="fas fa-seedling"></i>
        <span>Pétalos</span>
      </a>
      <h1>Bienvenido de vuelta</h1>
      <p>Inicia sesión para gestionar tu floristería</p>
    </div>

    <?php if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <form id="loginForm" action="controladores/login.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <div class="input-wrapper">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="tu@email.com" required>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>

      <div class="form-options">
        <label>
          <input type="checkbox" checked> Recordarme
        </label>
        <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
      </div>

      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Iniciar sesión
      </button>
    </form>

    <div class="divider">o continúa con</div>

    <div class="social-login">
      <a href="#" aria-label="Google"><i class="fab fa-google"></i></a>
      <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="Apple"><i class="fab fa-apple"></i></a>
    </div>

    <div class="register-link">
      ¿No tienes cuenta? <a href="registro.php">Crear cuenta nueva</a>
    </div>
  </div>
</body>
</html>