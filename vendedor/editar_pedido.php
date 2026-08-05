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

$id_pedido = Crypto::decrypt($_GET['id_pedido']);
if (empty($id_pedido) || $id_pedido <= 0 || !is_numeric($id_pedido)) {
    header("Location: gestion_pedidos.php");
    exit;
}

try {
    // Obtener datos del pedido + detalle + pago (solo si está pendiente)
    $sql = $conexion->prepare("
        SELECT p.id_cliente, p.direccion_entrega, p.mensaje_tarjeta, p.total,
               dp.id_producto, dp.cantidad, dp.precio_unitario,
               pg.metodo_pago, pg.referencia, pg.estado AS estado_pago
        FROM pedidos p
        JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
        LEFT JOIN pagos pg ON pg.id_pedido = p.id_pedido
        WHERE p.id_pedido = :id_pedido AND p.estado = 'pendiente'
    ");
    $sql->bindParam(":id_pedido", $id_pedido, PDO::PARAM_INT);
    $sql->execute();
    $pedido = $sql->fetch(PDO::FETCH_OBJ);
    if (!$pedido) {
        header("Location: gestion_pedidos.php");
        exit;
    }

    // Traer clientes activos
    $sql = $conexion->prepare("SELECT id_usuario, nombre, email FROM usuarios WHERE rol='cliente' AND estado='activo'");
    $sql->execute();
    $clientes = $sql->fetchAll(PDO::FETCH_OBJ);

    // Traer productos activos
    $sql = $conexion->prepare("SELECT id_producto, nombre, precio, stock FROM productos WHERE estado='activo'");
    $sql->execute();
    $productos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en consultas: " . $e->getMessage());
    $_SESSION['errores'] = ["Error al cargar los datos."];
    header("Location: gestion_pedidos.php");
    exit;
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
        max-width: 640px;
        width: 100%;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 40px;
        padding: 2.8rem 2.5rem;
        border: 1px solid rgba(255, 245, 235, 0.5);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
        max-height: none;
        overflow-y: visible;
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
    .form-group select,
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

    .form-group select {
        padding-right: 2.8rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.2rem center;
        cursor: pointer;
    }

    .form-group textarea {
        padding: 0.8rem 1rem 0.8rem 2.8rem;
        border-radius: 24px;
        min-height: 80px;
        resize: vertical;
        line-height: 1.6;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #c87a5a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #b5aca2;
    }

    .form-group input.error,
    .form-group select.error,
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

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.8rem;
    }

    .total-display {
        background: #faf8f5;
        border: 2px solid #ede8e0;
        border-radius: 16px;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .total-display .total-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #6b5d4f;
    }

    .total-display .total-amount {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d2a24;
        display: flex;
        align-items: center;
    }

    .total-display .total-amount input {
        border: none;
        background: transparent;
        font-size: inherit;
        font-weight: inherit;
        color: inherit;
        font-family: 'Inter', sans-serif;
        width: 120px;
        text-align: right;
        outline: none;
        padding: 0;
    }

    .total-display .total-amount i {
        color: #c87a5a;
        font-size: 1.2rem;
        margin-right: 0.3rem;
    }

    .toggle-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #2d2a24;
        white-space: nowrap;
    }

    .toggle-wrapper {
        display: flex !important;
        justify-content: center;
        align-items: center;
        padding: 0.6rem 0 !important;
        background: #f4f1eb !important;
        min-height: 48px;
    }

    .toggle-wrapper .toggle-label {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        cursor: pointer;
        user-select: none;
        font-size: 0.9rem;
    }

    .toggle-option {
        font-weight: 500;
        color: #8a8580;
        transition: color 0.25s;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .toggle-option.on { color: #8a8580; }

    .toggle-label input:checked ~ .toggle-option.off { color: #8a8580; }
    .toggle-label input:checked ~ .toggle-option.on { color: #2d2a24; font-weight: 600; }
    .toggle-label input:not(:checked) ~ .toggle-option.off { color: #2d2a24; font-weight: 600; }
    .toggle-label input:not(:checked) ~ .toggle-option.on { color: #8a8580; }

    .toggle-label input { display: none; }

    .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
        background: #d9d5d0;
        border-radius: 12px;
        transition: background 0.25s;
        flex-shrink: 0;
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.25s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .toggle-label input:checked + .toggle-switch {
        background: #c87a5a;
    }

    .toggle-label input:checked + .toggle-switch::after {
        transform: translateX(20px);
    }

    #referenciaGroup {
        margin-top: -0.5rem;
    }

    #referenciaGroup .hint {
        font-size: 0.8rem;
        color: #8a8580;
        margin-top: 0.25rem;
    }

    @media (max-width: 480px) {
        .toggle-option { font-size: 0.75rem; }
        .toggle-switch { width: 38px; height: 20px; }
        .toggle-switch::after { width: 14px; height: 14px; top: 3px; left: 3px; }
        .toggle-label input:checked + .toggle-switch::after { transform: translateX(18px); }
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

        .create-header .icon-wrapper {
            width: 54px;
            height: 54px;
        }

        .create-header .icon-wrapper i {
            font-size: 1.6rem;
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

        .total-display {
            flex-direction: column;
            gap: 0.3rem;
            text-align: center;
        }

        .total-display .total-amount {
            font-size: 1.5rem;
        }
    }
</style>

<div class="create-wrapper">
    <div class="create-container">
        <div class="create-header">
            <a href="#" class="brand">
                <i class="fas fa-seedling"></i>
                <span>Pétalos</span>
            </a>
            <div class="icon-wrapper">
                <i class="fas fa-edit"></i>
            </div>
            <h1>Editar pedido</h1>
            <p>Modifica los datos del pedido #<?= $id_pedido ?></p>
        </div>

        <?php if (isset($_SESSION['errores'])) : ?>
            <div class="alert alert-danger" style="margin:1rem 1.5rem;">
                <ul>
                    <?php foreach ($_SESSION['errores'] as $error) : ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['exito'])) : ?>
            <div class="alert alert-success" style="margin:1rem 1.5rem;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <form id="editOrderForm" action="../controladores/vendedor/editar_pedido.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_pedido" value="<?= Crypto::encrypt($id_pedido) ?>">

            <!-- Cliente -->
            <div class="form-group">
                <label for="cliente">Cliente <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <select id="cliente" name="cliente" required>
                        <option value="">Selecciona un cliente</option>
                        <?php foreach ($clientes as $item): ?>
                            <option value="<?= $item->id_usuario ?>" <?= $item->id_usuario == $pedido->id_cliente ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item->nombre) ?> - <?= htmlspecialchars($item->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Producto -->
            <div class="form-group">
                <label for="producto">Producto <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-box"></i>
                    <select id="producto" name="producto" required>
                        <option value="">Selecciona un producto</option>
                        <?php foreach ($productos as $item): ?>
                            <option value="<?= $item->id_producto ?>" data-precio="<?= $item->precio ?>" data-stock="<?= $item->stock ?>" <?= $item->id_producto == $pedido->id_producto ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Cantidad y Precio Unitario -->
            <div class="form-row">
                <div class="form-group">
                    <label for="cantidad">Cantidad <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-hashtag"></i>
                        <input type="number" id="cantidad" name="cantidad" placeholder="1" min="1" value="<?= $pedido->cantidad ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="precioUnitario">Precio Unitario</label>
                    <div class="input-wrapper">
                        <i class="fas fa-dollar-sign"></i>
                        <input type="number" id="precioUnitario" name="precio_unitario" placeholder="0.00" step="0.01" min="0" value="<?= number_format($pedido->precio_unitario, 2, '.', '') ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Dirección de entrega -->
            <div class="form-group">
                <label for="direccion">Dirección de entrega <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="direccion" name="direccion_entrega" placeholder="Calle, número, ciudad, código postal" value="<?= htmlspecialchars($pedido->direccion_entrega) ?>" required>
                </div>
            </div>

            <!-- Mensaje de entrega -->
            <div class="form-group">
                <label for="mensaje">Mensaje de entrega</label>
                <div class="input-wrapper">
                    <i class="fas fa-comment"></i>
                    <textarea id="mensaje" name="mensaje_tarjeta" placeholder="Instrucciones especiales, mensaje para el cliente, horario de entrega..."><?= htmlspecialchars($pedido->mensaje_tarjeta ?? '') ?></textarea>
                </div>
            </div>

            <!-- Total -->
            <div class="total-display">
                <span class="total-label"><i class="fas fa-calculator"></i> Total del pedido</span>
                <span class="total-amount"><i class="fas fa-dollar-sign"></i> <input type="number" id="totalAmount" name="total" step="0.01" min="0" value="<?= number_format($pedido->total, 2, '.', '') ?>" readonly></span>
            </div>

            <!-- Método de pago -->
            <div class="form-row">
                <div class="form-group">
                    <label for="metodo_pago">Método de pago <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-credit-card"></i>
                        <select id="metodo_pago" name="metodo_pago" required>
                            <option value="">Selecciona un método de pago</option>
                            <option value="efectivo" <?= ($pedido->metodo_pago ?? '') == 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                            <option value="tarjeta" <?= ($pedido->metodo_pago ?? '') == 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                            <option value="transferencia" <?= ($pedido->metodo_pago ?? '') == 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                            <option value="paypal" <?= ($pedido->metodo_pago ?? '') == 'paypal' ? 'selected' : '' ?>>PayPal</option>
                        </select>
                    </div>
                </div>

                <div class="form-group toggle-group">
                    <label>Estado del pago</label>
                    <div class="input-wrapper toggle-wrapper">
                        <input type="hidden" name="pagado_ahora" value="0">
                        <label class="toggle-label">
                            <span class="toggle-option off">Contra entrega</span>
                            <input type="checkbox" id="pagado_ahora" name="pagado_ahora" value="1" <?= ($pedido->estado_pago ?? 'pendiente') == 'pagado' ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-option on">Pagado ahora</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Referencia de pago (solo tarjeta/transferencia) -->
            <div class="form-group" id="referenciaGroup" style="<?= in_array($pedido->metodo_pago ?? '', ['tarjeta', 'transferencia']) ? 'display:block;' : 'display:none;' ?>">
                <label for="referencia">Referencia del pago</label>
                <div class="input-wrapper">
                    <i class="fas fa-hashtag"></i>
                    <input type="text" id="referencia" name="referencia" placeholder="Últimos 4 dígitos, ID de transacción o folio" value="<?= htmlspecialchars($pedido->referencia ?? '') ?>">
                </div>
                <p class="hint">Requerido para transferencias y tarjetas</p>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <button type="submit" class="btn-create" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
                <a href="gestion_pedidos.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const productoSelect = document.getElementById('producto');
    const precioInput = document.getElementById('precioUnitario');
    const cantidadInput = document.getElementById('cantidad');
    const totalAmount = document.getElementById('totalAmount');

    productoSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            const precio = parseFloat(selected.dataset.precio) || 0;
            const stock = parseInt(selected.dataset.stock) || 0;
            precioInput.value = precio.toFixed(2);
            cantidadInput.max = stock;
            if (parseInt(cantidadInput.value) > stock) {
                cantidadInput.value = stock;
            }
        } else {
            precioInput.value = '';
            cantidadInput.max = 1;
            cantidadInput.value = 1;
        }
        calcularTotal();
    });

    function calcularTotal() {
        const precio = parseFloat(precioInput.value) || 0;
        const cantidad = parseInt(cantidadInput.value) || 0;
        totalAmount.value = (precio * cantidad).toFixed(2);
    }

    cantidadInput.addEventListener('input', calcularTotal);

    document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const errorId = this.id + 'Error';
            const errorEl = document.getElementById(errorId);
            if (errorEl) errorEl.classList.remove('show');
        });
        input.addEventListener('change', function() {
            if (this.id === 'cliente' || this.id === 'producto') {
                this.classList.remove('error');
                const errorId = this.id + 'Error';
                const errorEl = document.getElementById(errorId);
                if (errorEl) errorEl.classList.remove('show');
            }
        });
    });

    const metodoPago = document.getElementById('metodo_pago');
    const referenciaGroup = document.getElementById('referenciaGroup');
    const referenciaInput = document.getElementById('referencia');

    metodoPago.addEventListener('change', function() {
        if (this.value === 'tarjeta' || this.value === 'transferencia') {
            referenciaGroup.style.display = 'block';
            referenciaInput.required = true;
            referenciaInput.placeholder = this.value === 'tarjeta' ? 'Últimos 4 dígitos de la tarjeta' : 'Número de folio o referencia de la transferencia';
        } else {
            referenciaGroup.style.display = 'none';
            referenciaInput.required = false;
            referenciaInput.value = '';
        }
    });
</script>
<?php include_once '../templates/footer.php'; ?>
