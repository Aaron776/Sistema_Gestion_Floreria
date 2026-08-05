<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (!isset($_GET['id_pedido'])) {
    header("Location: gestion_pedidos.php");
    exit();
}

$id_pedido = Crypto::decrypt($_GET['id_pedido']);

try {
    // Verificar que el pedido existe y está en estado listo
    $sql_pedido = $conexion->prepare("SELECT id_pedido, id_cliente FROM pedidos WHERE id_pedido = :id AND estado = 'listo'");
    $sql_pedido->bindParam(":id", $id_pedido, PDO::PARAM_INT);
    $sql_pedido->execute();
    $pedido = $sql_pedido->fetch(PDO::FETCH_OBJ);

    if (!$pedido) {
        header("Location: gestion_pedidos.php");
        exit();
    }

    // Verificar que no tenga ya una entrega asignada (pendiente o en_camino)
    $sql_entrega = $conexion->prepare("SELECT id_entrega FROM entregas WHERE id_pedido = :id AND estado != 'entregado'");
    $sql_entrega->bindParam(":id", $id_pedido, PDO::PARAM_INT);
    $sql_entrega->execute();
    if ($sql_entrega->fetch()) {
        $_SESSION['errores'] = ["Este pedido ya tiene un repartidor asignado."];
        header("Location: gestion_pedidos.php");
        exit();
    }

    // Traer todos los repartidores activos
    $sql_repartidores = $conexion->prepare("SELECT id_usuario, nombre, email FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
    $sql_repartidores->execute();
    $repartidores = $sql_repartidores->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar asignación: " . $e->getMessage());
    header("Location: gestion_pedidos.php");
    exit();
}

include_once '../templates/header.php';
?>
<style>
    .create-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 3.5rem);
        padding: 2rem 1.5rem;
        background-image:
            radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.06) 0%, transparent 50%);
    }

    .create-container {
        max-width: 520px;
        width: 100%;
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

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.82rem;
        color: #2d2a24;
        margin-bottom: 0.3rem;
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
    }

    .form-group select,
    .form-group input,
    .form-group textarea {
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

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
        padding: 0.8rem 1rem 0.8rem 2.8rem;
        line-height: 1.5;
        border-radius: 24px;
    }

    .form-group select {
        padding-right: 2.8rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.2rem center;
        cursor: pointer;
    }

    .form-group select:focus,
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #c87a5a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

    .form-group input[readonly] {
        background: #f5f2ed;
        cursor: not-allowed;
        opacity: 0.8;
    }

    .form-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 1.5rem;
    }

    .btn-assign {
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

    .btn-assign:hover {
        background: #1e1a16;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-assign i {
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

    @media (max-width: 480px) {
        .create-container {
            padding: 2rem 1.5rem;
            border-radius: 28px;
        }
        .create-header h1 {
            font-size: 1.4rem;
        }
        .form-actions {
            flex-direction: column;
        }
        .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<div class="create-wrapper">
    <div class="create-container">
        <div class="create-header">
            <div class="icon-wrapper">
                <i class="fas fa-user-tie"></i>
            </div>
            <h1>Asignar Repartidor</h1>
            <p>Selecciona un repartidor para el pedido #<?php echo $id_pedido; ?></p>
        </div>

        <?php if (isset($_SESSION['errores'])) : ?>
            <div class="alert alert-danger" style="margin:1rem 0;">
                <ul>
                    <?php foreach ($_SESSION['errores'] as $error) : ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['exito'])) : ?>
            <div class="alert alert-success" style="margin:1rem 0;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <form action="../controladores/vendedor/asignar_repartidor.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_pedido" value="<?= Crypto::encrypt($id_pedido) ?>">

            <div class="form-group">
                <label for="repartidor">Repartidor <span style="color:#b05b4b;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <select id="repartidor" name="repartidor" required>
                        <option value="">Selecciona un repartidor</option>
                        <?php foreach ($repartidores as $rep): ?>
                            <option value="<?= $rep->id_usuario ?>"><?= htmlspecialchars($rep->nombre) ?> — <?= htmlspecialchars($rep->email) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="fecha_entrega">Fecha estimada de entrega</label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="date" id="fecha_entrega" name="fecha_entrega" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <div class="input-wrapper">
                    <i class="fas fa-comment"></i>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="3" placeholder="Notas adicionales para el repartidor..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-assign">
                    <i class="fas fa-check"></i> Asignar repartidor
                </button>
                <a href="gestion_pedidos.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php include_once '../templates/footer.php'; ?>
