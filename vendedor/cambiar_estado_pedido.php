<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_GET['id_pedido'])) {
    header("Location: gestion_pedidos.php");
    exit;
}

$id_pedido = Crypto::decrypt($_GET['id_pedido']);
if (empty($id_pedido) || $id_pedido <= 0 || !is_numeric($id_pedido)) {
    header("Location: gestion_pedidos.php");
    exit;
}

try {
    $stmt = $conexion->prepare("
        SELECT p.id_pedido, p.estado, p.total,
               u.nombre AS cliente_nombre, u.email AS cliente_email,
               GROUP_CONCAT(DISTINCT CONCAT(pr.nombre, ' (x', dp.cantidad, ')') SEPARATOR ', ') AS productos
        FROM pedidos p
        LEFT JOIN usuarios u ON p.id_cliente = u.id_usuario
        LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
        LEFT JOIN productos pr ON dp.id_producto = pr.id_producto
        WHERE p.id_pedido = :id_pedido AND p.estado IN ('pendiente', 'preparando')
        GROUP BY p.id_pedido
    ");
    $stmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmt->execute();
    $pedido = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$pedido) {
        $_SESSION['errores'] = ["El pedido no existe o ya no se puede cambiar su estado."];
        header("Location: gestion_pedidos.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error en cambiar_estado_pedido: " . $e->getMessage());
    $_SESSION['errores'] = ["Error al consultar el pedido."];
    header("Location: gestion_pedidos.php");
    exit;
}

// Definir el siguiente estado según el estado actual
$siguiente_estado = ($pedido->estado === 'pendiente') ? 'preparando' : 'listo';

include_once '../templates/header.php';

// Mostrar errores desde la sesión
if (!empty($_SESSION['errores'])):
?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <ul>
            <?php foreach ($_SESSION['errores'] as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php
    unset($_SESSION['errores']);
endif;

// Mostrar éxito desde la sesión
if (!empty($_SESSION['exito'])):
?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($_SESSION['exito']) ?>
    </div>
<?php
    unset($_SESSION['exito']);
endif;
?>
<style>
    /* ===== CENTRAR CONTENIDO ===== */
    .change-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 7rem);
        padding: 2rem 1.5rem;
        background-image:
            radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.06) 0%, transparent 50%);
    }

    .change-container {
        max-width: 520px;
        width: 100%;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 40px;
        padding: 2.8rem 2.5rem;
        border: 1px solid rgba(255, 245, 235, 0.5);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
        transition: all 0.3s;
    }

    .change-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .change-header .brand {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
        text-decoration: none;
        margin-bottom: 0.3rem;
    }

    .change-header .brand i {
        color: #c87a5a;
        font-size: 1.8rem;
    }

    .change-header .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #7f6e5d;
        text-decoration: none;
        font-size: 0.85rem;
        margin-bottom: 0.8rem;
        transition: color 0.2s;
    }

    .change-header .back-link:hover {
        color: #c87a5a;
    }

    .change-header .icon-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        background: rgba(200, 122, 90, 0.1);
        border-radius: 50%;
        margin-bottom: 0.8rem;
    }

    .change-header .icon-wrapper i {
        font-size: 2rem;
        color: #c87a5a;
    }

    .change-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
    }

    .change-header p {
        color: #7f6e5d;
        font-size: 0.95rem;
        margin-top: 0.2rem;
    }

    .change-header .badge-admin {
        display: inline-block;
        background: #ede6f0;
        color: #7a5a8a;
        padding: 0.2rem 0.8rem;
        border-radius: 60px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 0.3rem;
    }

    /* ===== INFO DEL PEDIDO ===== */
    .order-info-box {
        background: #faf8f5;
        border: 1px solid #ede8e0;
        border-radius: 16px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem 1rem;
    }

    .order-info-box .info-item {
        display: flex;
        flex-direction: column;
    }

    .order-info-box .info-item .info-label {
        font-size: 0.65rem;
        font-weight: 600;
        color: #7f6e5d;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .order-info-box .info-item .info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #2d2a24;
    }

    .order-info-box .info-item .info-value .status-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.6rem;
        border-radius: 60px;
        font-weight: 600;
    }

    .status-badge.pendiente { background: #f9ede0; color: #a87d58; }
    .status-badge.preparando { background: #e0edf5; color: #4a7a8a; }
    .status-badge.listo { background: #d4e8f0; color: #3a7a8a; }
    .status-badge.en-camino { background: #ede6f0; color: #7a5a8a; }
    .status-badge.entregado { background: #e2f0e6; color: #4a7a5c; }
    .status-badge.cancelado { background: #fde8e4; color: #b05b4b; }

    /* ===== FORMULARIO ===== */
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

    .form-group select {
        width: 100%;
        padding: 0.9rem 1rem 0.9rem 2.8rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: #faf8f5;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.2s;
        color: #2d2a24;
        appearance: none;
        padding-right: 2.8rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.2rem center;
        cursor: pointer;
    }

    .form-group select:focus {
        outline: none;
        border-color: #c87a5a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

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

    /* ===== VISTA PREVIA DEL ESTADO ===== */
    .status-preview {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 0.8rem;
        background: #f8f5f0;
        border-radius: 16px;
        margin-bottom: 1.5rem;
        border: 1px solid #ede8e0;
    }

    .status-preview .preview-label {
        font-size: 0.8rem;
        color: #7f6e5d;
        font-weight: 500;
    }

    .status-preview .preview-status {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.25rem 1rem;
        border-radius: 60px;
    }

    .status-preview .preview-status.pendiente { background: #f9ede0; color: #a87d58; }
    .status-preview .preview-status.preparando { background: #e0edf5; color: #4a7a8a; }
    .status-preview .preview-status.listo { background: #d4e8f0; color: #3a7a8a; }
    .status-preview .preview-status.en-camino { background: #ede6f0; color: #7a5a8a; }
    .status-preview .preview-status.entregado { background: #e2f0e6; color: #4a7a5c; }
    .status-preview .preview-status.cancelado { background: #fde8e4; color: #b05b4b; }

    /* ===== BOTONES ===== */
    .form-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 0.5rem;
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

    .btn-update:disabled {
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
    @media (max-width: 768px) {
        .change-wrapper {
            padding: 1rem 0.8rem;
            min-height: calc(100vh - 5rem);
        }
    }

    @media (max-width: 480px) {
        .change-container {
            padding: 2rem 1.5rem;
            border-radius: 28px;
        }
        .change-header h1 {
            font-size: 1.4rem;
        }
        .change-header .icon-wrapper {
            width: 54px;
            height: 54px;
        }
        .change-header .icon-wrapper i {
            font-size: 1.6rem;
        }
        .order-info-box {
            grid-template-columns: 1fr;
            gap: 0.3rem;
        }
        .form-actions {
            flex-direction: column;
        }
        .btn-update, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
        .status-preview {
            flex-direction: column;
            gap: 0.4rem;
        }
    }
</style>
<div class="change-wrapper">
    <div class="change-container">
        <div class="change-header">
            <a href="gestion_pedidos.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver a pedidos
            </a>
            <div class="icon-wrapper">
                <i class="fas fa-sync-alt"></i>
            </div>
            <h1>Cambiar estado del pedido</h1>
            <p>Actualiza el estado actual del pedido</p>
            <span class="badge-admin"><i class="fas fa-user-shield"></i> Panel del Vendedor</span>
        </div>

        <div class="order-info-box">
            <div class="info-item">
                <span class="info-label">Pedido</span>
                <span class="info-value">#<?= htmlspecialchars($pedido->id_pedido) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Cliente</span>
                <span class="info-value"><?= htmlspecialchars($pedido->cliente_nombre ?? '—') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Productos</span>
                <span class="info-value"><?= htmlspecialchars($pedido->productos ?? '—') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado actual</span>
                <span class="info-value">
                    <span class="status-badge <?= str_replace('_', '-', $pedido->estado) ?>"><?= htmlspecialchars(ucfirst($pedido->estado)) ?></span>
                </span>
            </div>
        </div>

        <form action="../controladores/vendedor/cambiar_estado_pedido.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_pedido" value="<?= Crypto::encrypt($pedido->id_pedido) ?>">

            <div class="form-group">
                <label for="status">Nuevo estado <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag"></i>
                    <select id="status" name="estado" required>
                        <option value="">Selecciona un estado</option>
                        <option <?php if($pedido->estado == 'preparando') echo 'selected'; ?> value="preparando">🔄 Preparando</option>
                        <option <?php if($pedido->estado == 'listo') echo 'selected'; ?> value="listo">✅ Listo</option>
                    </select>
                </div>
                <div class="error-text" id="statusError">
                    <i class="fas fa-exclamation-circle"></i> Selecciona un estado
                </div>
            </div>

            <div class="status-preview">
                <span class="preview-label">Estado seleccionado:</span>
                <span class="preview-status" id="previewStatus">—</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-update" id="submitBtn">
                    <i class="fas fa-save"></i> Actualizar estado
                </button>
                <a href="gestion_pedidos.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const statusSelect = document.getElementById('status');
    const previewStatus = document.getElementById('previewStatus');
    const submitBtn = document.getElementById('submitBtn');
    const statusError = document.getElementById('statusError');

    const statusMap = {
        'preparando': { class: 'preparando', text: '🔄 Preparando' },
        'listo': { class: 'listo', text: '✅ Listo' },
        'cancelado': { class: 'cancelado', text: '❌ Cancelado' }
    };

    statusSelect.addEventListener('change', function() {
        const value = this.value;
        if (value && statusMap[value]) {
            previewStatus.className = 'preview-status ' + statusMap[value].class;
            previewStatus.textContent = statusMap[value].text;
        } else {
            previewStatus.className = 'preview-status';
            previewStatus.textContent = '—';
        }
        this.classList.remove('error');
        statusError.classList.remove('show');
    });

    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;

        statusError.classList.remove('show');
        statusSelect.classList.remove('error');

        if (statusSelect.value === '') {
            statusError.classList.add('show');
            statusSelect.classList.add('error');
            isValid = false;
            e.preventDefault();
        }

        if (isValid) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        }
    });
</script>
<?php include_once '../templates/footer.php'; ?>
