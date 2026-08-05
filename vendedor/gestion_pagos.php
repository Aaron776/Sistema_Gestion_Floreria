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

$id_vendedor = $_SESSION['id_usuario'];

// Paginación
$resultados_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

// Iconos por método de pago (según el ENUM de la tabla pagos)
$iconos_metodo = [
    'efectivo' => 'fa-money-bill',
    'tarjeta' => 'fa-credit-card',
    'transferencia' => 'fa-university',
    'paypal' => 'fab fa-paypal',
];

// Iconos por estado del pago
$iconos_estado = [
    'pendiente' => 'fa-clock',
    'pagado' => 'fa-check-circle',
    'rechazado' => 'fa-times-circle',
];

// Consultas SQL
try {
    // Total de registros (sin límite)
    $sql_total = $conexion->prepare("SELECT COUNT(*) FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor");
    $sql_total->bindParam(':id_vendedor', $id_vendedor);
    $sql_total->execute();
    $total_registros = $sql_total->fetchColumn();

    $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));

    // Si la página solicitada excede el rango válido, volver a la página 1
    if ($pagina_actual > $total_paginas) {
        header("Location: gestion_pagos.php");
        exit;
    }

    $offset = ($pagina_actual - 1) * $resultados_por_pagina;

    // Rango visible en la página actual (para el texto "Mostrando X–Y de Z")
    $registros_inicio = ($pagina_actual - 1) * $resultados_por_pagina + 1;
    $registros_fin = min($total_registros, $pagina_actual * $resultados_por_pagina);

    // Traer pedidos que creó este vendedor paginados
    $sql = $conexion->prepare("SELECT pagos.id_pago as id_pago,u.nombre as cliente,u.email as email_cliente,pagos.fecha_pago as fecha_pago,pagos.estado as estado,pagos.monto as monto,pagos.metodo_pago as metodo_pago,pr.nombre as producto FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor ORDER BY pagos.fecha_pago DESC LIMIT :limite OFFSET :offset");
    $sql->bindValue(':id_vendedor', $id_vendedor, PDO::PARAM_INT);
    $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $pagos = $sql->fetchAll(PDO::FETCH_OBJ);

    // Obtener total recaudado de los pagos de los pedidos pero solo aquellos pagos en estado pagado
    $sql = $conexion->prepare("SELECT SUM(pagos.monto) as total FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor AND pagos.estado='pagado'");
    $sql->bindParam(':id_vendedor', $id_vendedor);
    $sql->execute();
    $total_recaudado = $sql->fetchColumn();

    // Obtener catidad de pagos en estado pendiente
    $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor AND pagos.estado='pendiente'");
    $sql->bindParam(':id_vendedor', $id_vendedor);
    $sql->execute();
    $total_pendiente = $sql->fetchColumn();

    // Obtener catidad de pagos en estado rechazado
    $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor AND pagos.estado='rechazado'");
    $sql->bindParam(':id_vendedor', $id_vendedor);
    $sql->execute();
    $total_rechazado = $sql->fetchColumn();

    // Obtener catidad de pagos en estado pagado
    $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pagos JOIN pedidos p ON p.id_pedido = pagos.id_pedido JOIN detalle_pedido dp ON p.id_pedido=dp.id_pedido JOIN usuarios u ON p.id_cliente=u.id_usuario JOIN productos pr ON dp.id_producto=pr.id_producto WHERE p.id_usuario=:id_vendedor AND pagos.estado='pagado'");
    $sql->bindParam(':id_vendedor', $id_vendedor);
    $sql->execute();
    $total_pagado = $sql->fetchColumn();
} catch (PDOException $e) {
    error_log("Error en la consultas a la base de datos: " . $e->getMessage());
    $pagos = [];
    $total_registros = 0;
    $total_paginas = 1;
    $pagina_actual = 1;
}
include_once '../templates/header.php';
?>
<style>
    /* ===== CONTENEDOR ===== */
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }

    /* ===== HEADER DE LA VISTA ===== */
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .admin-header .title-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .admin-header .title-section h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d2a24;
    }

    /* Icono del título (antes era un style en línea repetido) */
    .admin-header .title-section h1 i {
        color: #c87a5a;
        margin-right: 0.5rem;
    }

    .admin-header .title-section .badge {
        background: #c87a5a;
        color: #fff;
        padding: 0.2rem 0.8rem;
        border-radius: 60px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .admin-header .header-actions {
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    /* ===== BOTONES (solo .btn-outline se usa en esta vista; .btn-primary quedó huérfano y se eliminó) ===== */
    .btn-outline {
        padding: 0.7rem 1.6rem;
        border-radius: 60px;
        border: 2px solid #dccfc2;
        background: transparent;
        color: #2d2a24;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.25s;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
    }

    .btn-outline:hover {
        border-color: #c87a5a;
        background: #f8f5f0;
    }

    /* ===== TARJETAS ESTADÍSTICAS ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.2rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #ffffff;
        padding: 1.2rem 1.5rem;
        border-radius: 20px;
        border: 1px solid #f1ebe4;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.2s;
    }

    .stat-card:hover {
        border-color: #dbcbc0;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .stat-card .stat-icon.pink {
        background: #fde8e4;
        color: #c87a5a;
    }

    .stat-card .stat-icon.green {
        background: #e2f0e6;
        color: #4a7a5c;
    }

    .stat-card .stat-icon.orange {
        background: #f9ede0;
        color: #a87d58;
    }

    .stat-card .stat-icon.blue {
        background: #e0edf5;
        color: #4a7a8a;
    }

    .stat-card .stat-info {
        flex: 1;
    }

    .stat-card .stat-info .stat-number {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d2a24;
        line-height: 1.2;
    }

    .stat-card .stat-info .stat-label {
        font-size: 0.75rem;
        color: #7f6e5d;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .stat-card .stat-info .stat-change {
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        margin-top: 2px;
    }

    .stat-change.up {
        color: #4a7a5c;
    }

    .stat-change.down {
        color: #b05b4b;
    }

    /* ===== FILTROS ===== */
    .filters-bar {
        background: #ffffff;
        padding: 1rem 1.5rem;
        border-radius: 20px;
        border: 1px solid #f1ebe4;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .filters-bar .search-wrapper {
        flex: 1;
        min-width: 200px;
        position: relative;
    }

    .filters-bar .search-wrapper i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #a28d7a;
    }

    .filters-bar .search-wrapper input {
        width: 100%;
        padding: 0.6rem 1rem 0.6rem 2.8rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: #faf8f5;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .filters-bar .search-wrapper input:focus {
        outline: none;
        border-color: #c87a5a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.06);
    }

    .filters-bar select {
        padding: 0.6rem 2.5rem 0.6rem 1rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: #faf8f5;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        color: #2d2a24;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 140px;
    }

    .filters-bar select:focus {
        outline: none;
        border-color: #c87a5a;
        background-color: #ffffff;
    }

    .filters-bar .filter-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-filter {
        padding: 0.6rem 1.2rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: transparent;
        font-weight: 500;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
        color: #6b5d4f;
    }

    .btn-filter:hover {
        border-color: #c87a5a;
        color: #2d2a24;
    }

    .btn-filter.active {
        background: #2d2a24;
        border-color: #2d2a24;
        color: #f4f1eb;
    }

    /* ===== TABLA ===== */
    .table-container {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid #f1ebe4;
        overflow: hidden;
        overflow-x: auto;
    }

    .table-container .table-header {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #f1ebe4;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.8rem;
    }

    .table-container .table-header .table-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #2d2a24;
    }

    .table-container .table-header .table-title span {
        color: #7f6e5d;
        font-weight: 400;
    }

    /* Icono del título de la tabla (antes era style en línea) */
    .table-container .table-header .table-title i {
        color: #c87a5a;
        margin-right: 0.5rem;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .table-container thead {
        background: #faf8f5;
    }

    .table-container th {
        text-align: left;
        padding: 0.8rem 1rem;
        font-weight: 600;
        color: #6b5d4f;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid #ede8e0;
        white-space: nowrap;
    }

    .table-container td {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #f5efe8;
        color: #2d2a24;
        vertical-align: middle;
    }

    .table-container tr:last-child td {
        border-bottom: none;
    }

    .table-container tr:hover td {
        background: #faf8f5;
    }

    /* ===== ESTADOS (extiende el .status-badge global del header con ancho/centrado) ===== */
    .status-badge {
        min-width: 80px;
        text-align: center;
    }

    .status-badge i {
        margin-right: 0.3rem;
    }

    .status-badge.pendiente {
        background: #f9ede0;
        color: #a87d58;
    }

    .status-badge.pagado {
        background: #e2f0e6;
        color: #4a7a5c;
    }

    .status-badge.rechazado {
        background: #fde8e4;
        color: #b05b4b;
    }

    /* ===== MÉTODOS DE PAGO ===== */
    .method-badge {
        padding: 0.2rem 0.6rem;
        border-radius: 60px;
        font-size: 0.65rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .method-badge.efectivo {
        background: #f0ece6;
        color: #6b5d4f;
    }

    .method-badge.tarjeta {
        background: #e0edf5;
        color: #4a7a8a;
    }

    .method-badge.transferencia {
        background: #ede6f0;
        color: #7a5a8a;
    }

    .method-badge.paypal {
        background: #e0edf5;
        color: #3a7a8a;
    }

    /* ===== BOTÓN FACTURA ===== */
    .btn-invoice {
        padding: 0.3rem 0.9rem;
        border-radius: 60px;
        border: none;
        background: #2d2a24;
        color: #f4f1eb;
        font-weight: 600;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.25s;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-family: 'Inter', sans-serif;
        white-space: nowrap;
        text-decoration: none;
    }

    .btn-invoice:hover {
        background: #1e1a16;
        transform: scale(1.05);
    }

    .btn-invoice i {
        color: #c87a5a;
        font-size: 0.7rem;
    }

    /* ===== PAGINACIÓN ===== */
    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1ebe4;
        flex-wrap: wrap;
        gap: 0.8rem;
    }

    .pagination .info {
        font-size: 0.8rem;
        color: #7f6e5d;
    }

    .pagination .pages {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .pagination .pages .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 0.5rem;
        border-radius: 36px;
        border: 1px solid #ede8e0;
        background: transparent;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
        color: #6b5d4f;
        text-decoration: none;
    }

    .pagination .pages .page-btn:hover {
        border-color: #c87a5a;
        color: #2d2a24;
    }

    .pagination .pages .page-btn.active {
        background: #2d2a24;
        border-color: #2d2a24;
        color: #f4f1eb;
    }

    .pagination .pages .page-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }

    .pagination .pages .page-dots {
        display: inline-flex;
        align-items: center;
        padding: 0 0.25rem;
        color: #bfae9c;
    }

    .empty-row td {
        text-align: center;
        padding: 2.5rem 1rem !important;
        color: #bfae9c;
        font-size: 0.85rem;
    }

    /* ===== AYUDANTES (reemplazan estilos en línea repetidos) ===== */
    .table-legend {
        font-size: 0.7rem;
        color: #7f6e5d;
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .table-legend .legend-dot {
        font-size: 0.4rem;
    }

    .table-legend .legend-dot.pendiente {
        color: #a87d58;
    }

    .table-legend .legend-dot.pagado {
        color: #4a7a5c;
    }

    .table-legend .legend-dot.rechazado {
        color: #b05b4b;
    }

    .cell-name {
        font-weight: 500;
    }

    .cell-email {
        font-size: 0.65rem;
        color: #7f6e5d;
    }

    .td-actions {
        text-align: center;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .admin-header .title-section h1 {
            font-size: 1.4rem;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-card .stat-info .stat-number {
            font-size: 1.3rem;
        }

        .filters-bar {
            padding: 0.8rem 1rem;
            flex-direction: column;
            align-items: stretch;
        }

        .filters-bar .filter-actions {
            justify-content: stretch;
        }

        .filters-bar .filter-actions .btn-filter {
            flex: 1;
            text-align: center;
        }

        .table-container td,
        .table-container th {
            padding: 0.6rem 0.6rem;
            font-size: 0.7rem;
        }

        .pagination {
            flex-direction: column;
            align-items: center;
        }

        .btn-invoice {
            padding: 0.2rem 0.6rem;
            font-size: 0.6rem;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .admin-header {
            flex-direction: column;
            align-items: stretch;
        }

        .admin-header .header-actions {
            justify-content: stretch;
        }

        .admin-header .header-actions .btn-outline {
            flex: 1;
            justify-content: center;
        }

        .table-container .table-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
    }
</style>
<div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
        <div class="title-section">
            <h1><i class="fas fa-credit-card"></i>Gestión de Pagos</h1>
            <span class="badge">Vendedor</span>
        </div>
        <div class="header-actions">
            <button class="btn-outline">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </header>

    <!-- ===== TARJETAS ESTADÍSTICAS ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pink">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="stat-info">
                <div class="stat-number">$<?php echo htmlspecialchars(number_format($total_recaudado, 2)) ?></div>
                <div class="stat-label">Total recaudado</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pagado) ?></div>
                <div class="stat-label">Pagos completados</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pendiente) ?></div>
                <div class="stat-label">Pagos pendientes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_rechazado) ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar por ID, cliente, producto..." id="searchInput">
        </div>
        <select id="statusFilter">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="rechazado">Rechazado</option>
        </select>
        <select id="methodFilter">
            <option value="">Todos los métodos</option>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
            <option value="paypal">PayPal</option>
        </select>
        <div class="filter-actions">
            <button class="btn-filter active" data-filter="all">Todos</button>
            <button class="btn-filter" data-filter="pagado">Pagados</button>
            <button class="btn-filter" data-filter="pendiente">Pendientes</button>
        </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">
                <i class="fas fa-list"></i>
                Lista de pagos <span>(mostrando 8 de 102)</span>
            </div>
            <div class="table-legend">
                <span><i class="fas fa-circle legend-dot pendiente"></i> Pendiente</span>
                <span><i class="fas fa-circle legend-dot pagado"></i> Pagado</span>
                <span><i class="fas fa-circle legend-dot rechazado"></i> Rechazado</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="min-width:120px;">Producto</th>
                    <th style="min-width:120px;">Cliente</th>
                    <th style="width:80px;">Monto</th>
                    <th style="min-width:120px;">Método de pago</th>
                    <th style="min-width:120px;">Fecha de pago</th>
                    <th style="min-width:100px;">Estado</th>
                    <th style="min-width:140px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="paymentsTableBody">
                <?php if (empty($pagos)): ?>
                    <tr class="empty-row">
                        <td colspan="8">No hay pagos registrados para mostrar.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pagos as $item): ?>
                        <tr data-status="<?php echo $item->estado; ?>" data-method="<?php echo $item->metodo_pago; ?>">
                            <td><strong>#<?php echo str_pad($item->id_pago, 3, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo htmlspecialchars($item->producto); ?></td>
                            <td>
                                <div class="cell-name"><?php echo htmlspecialchars($item->cliente); ?></div>
                                <div class="cell-email"><?php echo htmlspecialchars($item->email_cliente); ?></div>
                            </td>
                            <td><strong>$<?php echo number_format($item->monto, 2); ?></strong></td>
                            <td><span class="method-badge <?php echo $item->metodo_pago; ?>"><i class="fas <?php echo $iconos_metodo[$item->metodo_pago] ?? 'fa-credit-card'; ?>"></i> <?php echo ucfirst($item->metodo_pago); ?></span></td>
                            <td><?php echo date('d/m/Y H:i A', strtotime($item->fecha_pago)); ?></td>
                            <td><span class="status-badge <?php echo $item->estado; ?>"><i class="fas <?php echo $iconos_estado[$item->estado] ?? 'fa-circle'; ?>"></i> <?php echo ucfirst($item->estado); ?></span></td>
                            <td class="td-actions">
                                <a href="../app/facturas/generar_factura.php?id_pago=<?php echo Crypto::encrypt($item->id_pago); ?>" class="btn-invoice">
                                    <i class="fas fa-file-pdf"></i> Factura
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ===== PAGINACIÓN ===== -->
        <div class="pagination">
            <div class="info">
                Mostrando <strong><?php echo $registros_inicio; ?>-<?php echo $registros_fin; ?></strong> de <strong><?php echo $total_registros; ?></strong> pagos
            </div>
            <div class="pages">
                <a href="gestion_pagos.php?pagina=<?php echo $pagina_actual - 1; ?>" class="page-btn <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>

                <?php if ($total_paginas <= 7): ?>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="gestion_pagos.php?pagina=<?php echo $i; ?>" class="page-btn <?php echo $i == $pagina_actual ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    if ($pagina_actual <= 3) {
                        $fin = 5;
                    } elseif ($pagina_actual >= $total_paginas - 2) {
                        $inicio = $total_paginas - 4;
                    }
                    ?>
                    <?php if ($inicio > 1): ?>
                        <a href="gestion_pagos.php?pagina=1" class="page-btn">1</a>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <a href="gestion_pagos.php?pagina=<?php echo $i; ?>" class="page-btn <?php echo $i == $pagina_actual ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($fin < $total_paginas): ?>
                        <span class="page-dots">...</span>
                        <a href="gestion_pagos.php?pagina=<?php echo $total_paginas; ?>" class="page-btn"><?php echo $total_paginas; ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="gestion_pagos.php?pagina=<?php echo $pagina_actual + 1; ?>" class="page-btn <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>
</div>
<script>
    // ===== FILTROS =====
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const methodFilter = document.getElementById('methodFilter');
    const filterButtons = document.querySelectorAll('.btn-filter');
    const rows = document.querySelectorAll('#paymentsTableBody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase();
        const methodValue = methodFilter.value.toLowerCase();
        const activeFilter = document.querySelector('.btn-filter.active');
        const filterType = activeFilter ? activeFilter.dataset.filter : 'all';

        let visibleCount = 0;

        rows.forEach(row => {
            const id = row.querySelector('td strong')?.textContent.toLowerCase() || '';
            const producto = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
            const cliente = row.querySelector('td:nth-child(3) div:first-child')?.textContent?.toLowerCase() || '';
            const status = row.dataset.status || '';
            const method = row.dataset.method || '';

            const matchesSearch = id.includes(searchTerm) ||
                producto.includes(searchTerm) ||
                cliente.includes(searchTerm);

            const matchesStatus = statusValue === '' || status === statusValue;
            const matchesMethod = methodValue === '' || method === methodValue;

            let matchesFilter = true;
            if (filterType !== 'all') {
                matchesFilter = status === filterType;
            }

            if (matchesSearch && matchesStatus && matchesMethod && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Actualizar contador
        const tableTitle = document.querySelector('.table-header .table-title span');
        if (tableTitle) {
            tableTitle.textContent = `(mostrando ${visibleCount} de ${rows.length})`;
        }
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    methodFilter.addEventListener('change', filterTable);

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterTable();
        });
    });
</script>
<?php include_once '../templates/footer.php'; ?>