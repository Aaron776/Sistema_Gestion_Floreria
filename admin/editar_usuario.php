<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_usuario = Crypto::decrypt($_GET['id_usuario']);

// Traer  datos del usuarios que se va a editar
try {
    $sql = $conexion->prepare("SELECT nombre,email,direccion,telefono,rol FROM usuarios WHERE id_usuario = :id_usuario");
    $sql->bindParam(":id_usuario", $id_usuario);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);
    if ($usuario == false) {
        header("Location: gestion_usuarios.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error en la consulta: " . $e->getMessage());
    header("Location: gestion_usuarios.php");
    exit();
}

include_once '../templates/header.php';
?>
<style>
    body {
        background-image:
            radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.06) 0%, transparent 50%);
    }

    .edit-container {
        margin: 0 auto;
        max-width: 560px;
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

    .edit-container::-webkit-scrollbar {
        width: 4px;
    }

    .edit-container::-webkit-scrollbar-track {
        background: transparent;
    }

    .edit-container::-webkit-scrollbar-thumb {
        background: #dccfc2;
        border-radius: 10px;
    }

    .edit-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .edit-header .brand {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
        text-decoration: none;
        margin-bottom: 0.3rem;
    }

    .edit-header .brand i {
        color: #c87a5a;
        font-size: 1.8rem;
    }

    .edit-header .user-id {
        display: inline-block;
        background: #f8f5f0;
        color: #6b5d4f;
        padding: 0.2rem 1rem;
        border-radius: 60px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.3rem;
    }

    .edit-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
    }

    .edit-header p {
        color: #7f6e5d;
        font-size: 0.95rem;
        margin-top: 0.2rem;
    }

    .edit-header .badge-admin {
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

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.8rem;
    }

    .form-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 1.2rem;
    }

    .btn-update {
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

    .btn-update:hover {
        background: #1e1a16;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-update i {
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

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .edit-container {
            padding: 2rem 1.5rem;
            border-radius: 28px;
            max-height: 100vh;
        }

        .edit-header h1 {
            font-size: 1.4rem;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-update,
        .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<div class="edit-container">
    <div class="edit-header">
        <a href="gestion_usuarios.php" class="brand">
            <i class="fas fa-seedling"></i>
            <span>Pétalos</span>
        </a>
        <h1>Editar usuario</h1>
        <p>Modifica los datos del usuario</p>
        <span class="user-id"><i class="fas fa-hashtag"></i> ID: #<?php echo htmlspecialchars($id_usuario); ?></span>
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

    <form id="editUserForm" action="../controladores/admin/editar_usuario.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id_usuario" value="<?= htmlspecialchars(Crypto::encrypt($id_usuario)); ?>">
        <div class="form-group">
            <label for="fullname">Nombre completo <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" id="fullname" name="nombre" value="<?php echo htmlspecialchars($usuario->nombre); ?>" required>
            </div>
        </div>

        <!-- Teléfono y Dirección -->
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Teléfono <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="telefono" value="<?php echo htmlspecialchars($usuario->telefono); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Dirección <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="address" name="direccion" value="<?php echo htmlspecialchars($usuario->direccion); ?>" required>
                </div>
            </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Correo electrónico <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario->email); ?>" required>
            </div>
        </div>

        <!-- ROL -->
        <div class="form-group">
            <label for="role">Rol <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-user-tag"></i>
                <select id="role" name="rol" required>
                    <option value="admin" <?php echo $usuario->rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="vendedor" <?php echo $usuario->rol === 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                    <option value="repartidor" <?php echo $usuario->rol === 'repartidor' ? 'selected' : ''; ?>>Repartidor</option>
                </select>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions" style="margin-top:1.2rem;">
            <button type="submit" class="btn-update">
                <i class="fas fa-save"></i> Actualizar usuario
            </button>
            <a href="gestion_usuarios.php" class="btn-cancel">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>


<?php include_once "../templates/footer.php"; ?>