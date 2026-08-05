<?php
require_once '../autorizacion/auth.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<style>
    .create-container {
        max-width: 560px;
        width: 100%;
        margin: 2rem auto;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 40px;
        padding: 2.8rem 2.5rem;
        border: 1px solid rgba(255, 245, 235, 0.5);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #7f6e5d;
        text-decoration: none;
        font-size: 0.85rem;
        margin-bottom: 1rem;
        transition: color 0.2s;
    }

    .back-link:hover {
        color: #c87a5a;
    }

    .create-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .create-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
    }

    .create-header p {
        color: #7f6e5d;
        font-size: 0.95rem;
        margin-top: 0.2rem;
    }

    .create-header .badge-admin {
        display: inline-block;
        background: #ede6f0;
        color: #7a5a8a;
        padding: 0.2rem 0.8rem;
        border-radius: 60px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 0.3rem;
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

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 2.8rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: #faf8f5;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.2s;
        color: #2d2a24;
        appearance: none;
    }

    .form-group select {
        padding-right: 2.8rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.2rem center;
        cursor: pointer;
    }

    .form-group select:focus {
        outline: none;
        border-color: #c87a5a;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
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

    .form-group input.error,
    .form-group select.error {
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

    .form-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 0.5rem;
    }

    .btn-create {
        flex: 1;
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

    .btn-create:hover {
        background: #1e1a16;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-create i {
        color: #c87a5a;
    }

    .btn-cancel {
        padding: 0.9rem 1.8rem;
        border-radius: 60px;
        border: 2px solid #dccfc2;
        background: transparent;
        color: #2d2a24;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.25s;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        text-decoration: none;
    }

    .btn-cancel:hover {
        border-color: #b05b4b;
        background: #fde8e4;
        transform: translateY(-2px);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .create-container {
            padding: 2rem 1.5rem;
            border-radius: 28px;
        }

        .create-header h1 {
            font-size: 1.4rem;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-create,
        .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<div class="create-container">
    <div class="create-header">
        <h1>Agregar nuevo usuario</h1>
        <p>Completa los datos para crear una cuenta</p>
        <span class="badge-admin"><i class="fas fa-user-shield"></i> Panel de Administración</span>
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

    <form id="createUserForm" action="../controladores/admin/agregar_usuario.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label for="fullname">Nombre completo <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" id="fullname" name="nombre" placeholder="Ej: María Rodríguez" required>
            </div>
        </div>

        <!-- Teléfono y Dirección -->
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Teléfono <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="telefono" placeholder="Ej: 555-1234" required>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Dirección <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="address" name="direccion" placeholder="Ej: Calle Primavera 123" required>
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
        </div>

        <!-- ROL -->
        <div class="form-group">
            <label for="role">Rol <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-user-tag"></i>
                <select id="role" name="rol" required>
                    <option value="">Selecciona un rol</option>
                    <option value="admin">Administrador</option>
                    <option value="vendedor">Vendedor</option>
                    <option value="repartidor">Repartidor</option>
                </select>
            </div>
        </div>

        <!-- Contraseña -->
        <div class="form-group">
            <label for="password">Contraseña <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Mínimo 5 caracteres" required minlength="5">
            </div>
        </div>

        <!-- Repetir contraseña -->
        <div class="form-group">
            <label for="passwordConfirm">Repetir contraseña <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-check-circle"></i>
                <input type="password" id="passwordConfirm" name="confirmar_password" placeholder="Repite la contraseña" required>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn-create">
                <i class="fas fa-user-plus"></i> Crear usuario
            </button>
            <a href="gestion_usuarios.php" class="btn-cancel">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>
<?php include_once "../templates/footer.php"; ?>