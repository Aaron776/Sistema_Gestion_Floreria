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

$id_categoria = Crypto::decrypt($_GET['id_categoria']);

// Traer datos de la categoria a editar
try {
    $sql = $conexion->prepare("SELECT nombre, descripcion FROM categorias WHERE id_categoria = :id_categoria");
    $sql->bindParam(":id_categoria", $id_categoria);
    $sql->execute();
    $categoria = $sql->fetch(PDO::FETCH_OBJ);
    if ($categoria == false) {
        header("Location: gestion_categorias.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error en la consulta: " . $e->getMessage());
    header("Location: gestion_categorias.php");
    exit();
}

include_once '../templates/header.php';
?>
<style>
    .create-container {
      max-width: 520px;
      width: 100%;
      margin: 0 auto;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 40px;
        padding: 2.8rem 2.5rem;
        border: 1px solid rgba(255, 245, 235, 0.5);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
    }

    .create-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .create-header .brand {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
        text-decoration: none;
        margin-bottom: 0.3rem;
    }

    .create-header .brand i {
        color: #c87a5a;
        font-size: 1.8rem;
    }

    .create-header .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #7f6e5d;
        text-decoration: none;
        font-size: 0.85rem;
        margin-bottom: 0.8rem;
        transition: color 0.2s;
    }

    .create-header .back-link:hover {
        color: #c87a5a;
    }

    .create-header .icon-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        background: rgba(200, 122, 90, 0.1);
        border-radius: 50%;
        margin-bottom: 0.8rem;
    }

    .create-header .icon-wrapper i {
        font-size: 2rem;
        color: #c87a5a;
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
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        color: #2d2a24;
        margin-bottom: 0.4rem;
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
        font-size: 1rem;
        transition: color 0.2s;
    }

    .form-group .input-wrapper:focus-within i {
        color: #c87a5a;
    }

    .form-group input,
    .form-group textarea {
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

    .form-group textarea {
        padding: 0.9rem 1rem 0.9rem 2.8rem;
        border-radius: 24px;
        min-height: 100px;
        resize: vertical;
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
    }

    .form-group textarea::placeholder {
        color: #b5aca2;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #c87a5a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

    .form-group input::placeholder {
        color: #b5aca2;
    }

    .form-group input.error,
    .form-group textarea.error {
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

    .btn-create:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
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
    @media (max-width: 480px) {
        .create-container {
            padding: 2rem 1.5rem;
            border-radius: 28px;
        }

        .create-header h1 {
            font-size: 1.4rem;
        }

        .create-header .icon-wrapper {
            width: 54px;
            height: 54px;
        }

        .create-header .icon-wrapper i {
            font-size: 1.6rem;
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
        <a href="gestion_categorias.php" class="brand">
            <i class="fas fa-seedling"></i>
            <span>Pétalos</span>
        </a>
        <div class="icon-wrapper">
            <i class="fas fa-tag"></i>
        </div>
        <h1>Editar categoría</h1>
        <p>Modifica los datos de la categoría</p>
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

    <form action="../controladores/admin/editar_categoria.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id_categoria" value="<?= htmlspecialchars(Crypto::encrypt($id_categoria), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="name">Nombre de la categoría <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-tag"></i>
                <input type="text" id="name" name="nombre" value="<?= htmlspecialchars($categoria->nombre, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Rosas, Arreglos, Follaje..." required>
            </div>
        </div>

        <!-- Descripción -->
        <div class="form-group">
            <label for="description">Descripción <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-align-left"></i>
                <textarea id="description" name="descripcion" placeholder="Describe brevemente esta categoría..." required><?= htmlspecialchars($categoria->descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn-create" id="submitBtn">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
            <a href="gestion_categorias.php" class="btn-cancel">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>
<?php include_once "../templates/footer.php"; ?>
