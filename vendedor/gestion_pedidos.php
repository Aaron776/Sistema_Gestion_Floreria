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

$id_vendedor=$_SESSION['id_usuario'];

// Paginación
$resultados_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

// Consultas SQL
try {
  // Total de registros (sin límite)
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE id_usuario=:id_vendedor");
  $sql_total->bindParam(':id_vendedor', $id_vendedor);
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer pedidos que creo este vendedor paginados
  $sql = $conexion->prepare("SELECT p.id_pedido as id_pedido,cliente.nombre as cliente,cliente.email as email,p.fecha_pedido as fecha_pedido,p.direccion_entrega as direccion_entrega,p.estado as estado,p.total as total FROM pedidos p JOIN usuarios as cliente ON p.id_cliente = cliente.id_usuario WHERE p.id_usuario=:id_vendedor ORDER BY p.fecha_pedido DESC LIMIT :limite OFFSET :offset");
  $sql->bindValue(':id_vendedor', $id_vendedor, PDO::PARAM_INT);
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $pedidos = $sql->fetchAll(PDO::FETCH_OBJ);

  // Obtener total de pedidos
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener total de pedidos en estado entregado
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='entregado' AND id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos_entregados=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener total de pedidos en estado pendiente
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='pendiente' AND id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos_pendientes=$sql->fetch(PDO::FETCH_OBJ);


  // Obtener total de pedidos en estado preparando
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='preparando' AND id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos_preparando=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener cantidad de pedidos en estado listo
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='listo' AND id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos_listo=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener cantidad de pedidos en estado en_camino
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='en_camino' AND id_usuario=:id_vendedor");
  $sql->bindParam(':id_vendedor', $id_vendedor);
  $sql->execute();
  $total_pedidos_en_camino=$sql->fetch(PDO::FETCH_OBJ);



 

} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $pedidos = [];
  $total_categorias = 0;
  $total_registros = 0;
  $total_paginas = 1;
  $pagina_actual = 1;

}
include_once '../templates/header.php';
?>
<style>
    /* ===== CONTENEDOR ===== */
    /* Incorpora padding y background del body original */
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
        padding: 0.5rem 1.5rem;
        background-image:
            radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.06) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.04) 0%, transparent 50%);
    }

    /* ===== HEADER ===== */
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

    .btn-primary {
        padding: 0.7rem 1.6rem;
        border-radius: 60px;
        border: none;
        background: #2d2a24;
        color: #f4f1eb;
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

    .btn-primary:hover {
        background: #1e1a16;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(45, 42, 36, 0.15);
    }

    .btn-primary i {
        color: #c87a5a;
    }

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
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1.2rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #ffffff;
        padding: 1rem 1.2rem;
        border-radius: 20px;
        border: 1px solid #f1ebe4;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        transition: all 0.2s;
    }

    .stat-card:hover {
        border-color: #dbcbc0;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    }

    .stat-card .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stat-card .stat-icon.gray {
        background: #f0ece6;
        color: #6b5d4f;
    }

    .stat-card .stat-icon.orange {
        background: #f9ede0;
        color: #a87d58;
    }

    .stat-card .stat-icon.blue {
        background: #e0edf5;
        color: #4a7a8a;
    }

    .stat-card .stat-icon.green {
        background: #e2f0e6;
        color: #4a7a5c;
    }

    .stat-card .stat-icon.pink {
        background: #fde8e4;
        color: #c87a5a;
    }

    .stat-card .stat-icon.purple {
        background: #ede6f0;
        color: #7a5a8a;
    }

    .stat-card .stat-info {
        flex: 1;
    }

    .stat-card .stat-info .stat-number {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2d2a24;
        line-height: 1.2;
    }

    .stat-card .stat-info .stat-label {
        font-size: 0.7rem;
        color: #7f6e5d;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
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

    /* ===== ESTADOS ===== */
        .status-badge {
            padding: 0.25rem 0.8rem;
            border-radius: 60px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
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

    .status-badge.preparando {
        background: #e0edf5;
        color: #4a7a8a;
    }

    .status-badge.listo {
        background: #d4e8f0;
        color: #3a7a8a;
    }

    .status-badge.en-camino {
        background: #ede6f0;
        color: #7a5a8a;
    }

    .status-badge.entregado {
        background: #e2f0e6;
        color: #4a7a5c;
    }

    .status-badge.cancelado {
        background: #fde8e4;
        color: #b05b4b;
    }

    /* ===== ACCIONES ===== */
        .action-buttons {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            justify-content: center;
        }

    .btn-action {
        padding: 0.3rem 0.7rem;
        border-radius: 60px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
    }

    .btn-action.edit {
        background: #e8f0fe;
        color: #4a6a8a;
    }

    .btn-action.edit:hover {
        background: #d4e2f5;
        transform: scale(1.05);
    }

    .btn-action.delete {
        background: #fde8e4;
        color: #b05b4b;
    }

    .btn-action.delete:hover {
        background: #fcd4cc;
        transform: scale(1.05);
    }

    .btn-action.status {
        background: #f0ece6;
        color: #6b5d4f;
    }

    .btn-action.status:hover {
        background: #e0d8d0;
        transform: scale(1.05);
    }

    .btn-action.assign {
        background: #e2f0e6;
        color: #4a7a5c;
    }

    .btn-action.assign:hover {
        background: #d0e8d8;
        transform: scale(1.05);
    }

    .btn-action.cancel {
        background: #fde8e4;
        color: #b05b4b;
    }

    .btn-action.cancel:hover {
        background: #fcd4cc;
        transform: scale(1.05);
    }

    .btn-action i {
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

    .pagination .pages button {
        width: 36px;
        height: 36px;
        border-radius: 36px;
        border: 1px solid #ede8e0;
        background: transparent;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
        color: #6b5d4f;
    }

    .pagination .pages button:hover {
        border-color: #c87a5a;
        color: #2d2a24;
    }

    .pagination .pages button.active {
        background: #2d2a24;
        border-color: #2d2a24;
        color: #f4f1eb;
    }

    /* ===== MODAL ===== */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal {
        background: #ffffff;
        border-radius: 32px;
        max-width: 480px;
        width: 100%;
        padding: 2rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        animation: modalIn 0.3s ease;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #2d2a24;
    }

    .modal p {
        color: #6b5d4f;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .modal .form-group {
        margin-bottom: 1rem;
    }

    .modal .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.82rem;
        color: #2d2a24;
        margin-bottom: 0.3rem;
    }

    .modal .form-group select {
        width: 100%;
        padding: 0.7rem 1rem;
        border-radius: 60px;
        border: 2px solid #ede8e0;
        background: #faf8f5;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        transition: all 0.2s;
        color: #2d2a24;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        cursor: pointer;
    }

    .modal .form-group select:focus {
        outline: none;
        border-color: #c87a5a;
        background-color: #ffffff;
    }

    .modal .modal-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 1.5rem;
    }

    .modal .modal-actions button {
        flex: 1;
        padding: 0.8rem;
        border-radius: 60px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }

    .modal .modal-actions .btn-confirm {
        background: #2d2a24;
        color: #f4f1eb;
    }

    .modal .modal-actions .btn-confirm:hover {
        background: #1e1a16;
    }

    .modal .modal-actions .btn-cancel-modal {
        background: #f8f5f0;
        color: #6b5d4f;
    }

    .modal .modal-actions .btn-cancel-modal:hover {
        background: #ede8e0;
    }

    /* ===== CELDAS ===== */
    .cell-name {
        font-weight: 500;
    }

    .cell-email {
        font-size: 0.65rem;
        color: #7f6e5d;
    }

    .btn-filter-sm {
        padding: 0.6rem 1.2rem;
        font-size: 0.8rem;
    }

    .status-legend {
        font-size: 0.7rem;
        color: #7f6e5d;
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .admin-header .title-section h1 {
            font-size: 1.4rem;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .stat-card {
            padding: 0.8rem 1rem;
        }

        .stat-card .stat-info .stat-number {
            font-size: 1.2rem;
        }

        .filters-bar {
            padding: 0.8rem 1rem;
            flex-direction: column;
            align-items: stretch;
        }

        .table-container td,
        .table-container th {
            padding: 0.6rem 0.6rem;
            font-size: 0.7rem;
        }

        .action-buttons {
            gap: 0.2rem;
        }

        .btn-action {
            padding: 0.2rem 0.5rem;
            font-size: 0.65rem;
        }

        .pagination {
            flex-direction: column;
            align-items: center;
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

        .admin-header .header-actions .btn-primary,
        .admin-header .header-actions .btn-outline {
            flex: 1;
            justify-content: center;
        }

        .table-container .table-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .modal {
            padding: 1.5rem;
        }

        .modal .modal-actions {
            flex-direction: column;
        }
    .swal-popup {
        border-radius: 32px !important;
        padding: 2rem 1.5rem !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12) !important;
        font-family: 'Inter', sans-serif !important;
        border: 1px solid rgba(255, 245, 235, 0.5) !important;
    }
    .swal-title {
        font-size: 1.4rem !important;
        font-weight: 700 !important;
    }
    button.swal2-confirm {
        border-radius: 60px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        padding: 0.7rem 1.5rem !important;
        font-family: 'Inter', sans-serif !important;
        border: none !important;
        background: #c87a5a !important;
        color: #fff !important;
        box-shadow: none !important;
    }
    button.swal2-cancel {
        border-radius: 60px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        padding: 0.7rem 1.5rem !important;
        font-family: 'Inter', sans-serif !important;
        border: 2px solid #dccfc2 !important;
        background: transparent !important;
        color: #2d2a24 !important;
        box-shadow: none !important;
    }
</style>
<div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
        <div class="title-section">
            <h1><i class="fas fa-shopping-bag" style="color:#c87a5a; margin-right:0.5rem;"></i>Gestión de Pedidos</h1>
            <span class="badge">Vendedor</span>
        </div>
        <div class="header-actions">
            <a href="registrar_pedido.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Nuevo pedido
            </a>
            <button class="btn-outline">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </header>

    <!-- ===== TARJETAS ESTADÍSTICAS ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon gray"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-info">
                <div class="stat-number">156</div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pedidos_pendientes->total, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-cog"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pedidos_preparando->total, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="stat-label">Preparando</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pedidos_listo->total, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="stat-label">Listos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pink"><i class="fas fa-truck"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pedidos_en_camino->total, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="stat-label">En camino</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo htmlspecialchars($total_pedidos_entregados->total, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="stat-label">Entregados</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar por ID, cliente..." id="searchInput">
        </div>
        <select id="statusFilter">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="preparando">Preparando</option>
            <option value="listo">Listo</option>
            <option value="en-camino">En camino</option>
            <option value="entregado">Entregado</option>
            <option value="cancelado">Cancelado</option>
        </select>
        <button class="btn-outline btn-filter-sm" id="clearFilters">
            <i class="fas fa-times"></i> Limpiar filtros
        </button>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">
                <i class="fas fa-list" style="color:#c87a5a; margin-right:0.5rem;"></i>
                Lista de pedidos <span>(<?php echo $total_registros; ?> en total)</span>
            </div>
                <div class="status-legend">
                <span><i class="fas fa-circle" style="color:#a87d58; font-size:0.4rem;"></i> Pendiente</span>
                <span><i class="fas fa-circle" style="color:#4a7a8a; font-size:0.4rem;"></i> Preparando</span>
                <span><i class="fas fa-circle" style="color:#3a7a8a; font-size:0.4rem;"></i> Listo</span>
                <span><i class="fas fa-circle" style="color:#7a5a8a; font-size:0.4rem;"></i> En camino</span>
                <span><i class="fas fa-circle" style="color:#4a7a5c; font-size:0.4rem;"></i> Entregado</span>
                <span><i class="fas fa-circle" style="color:#b05b4b; font-size:0.4rem;"></i> Cancelado</span>
            </div>
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
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="min-width:130px;">Cliente</th>
                    <th style="min-width:120px;">Fecha pedido</th>
                    <th style="min-width:160px;">Dirección entrega</th>
                    <th style="width:80px;">Total</th>
                    <th style="min-width:120px;">Estado</th>
                    <th style="min-width:200px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <?php
                $iconos_estado = [
                    'pendiente'  => 'fa-clock',
                    'preparando' => 'fa-cog',
                    'listo'      => 'fa-check-circle',
                    'en_camino'  => 'fa-truck',
                    'entregado'  => 'fa-check-double',
                    'cancelado'  => 'fa-times-circle',
                ];
                ?>
                <?php foreach($pedidos as $item):?>
                <tr data-status="<?php echo str_replace('_', '-', htmlspecialchars($item->estado, ENT_QUOTES, 'UTF-8')); ?>">
                    <td><strong>#<?php echo $item->id_pedido ?></strong></td>
                    <td>
                        <div class="cell-name"><?php echo $item->cliente?></div>
                        <div class="cell-email"><?php echo $item->email?></div>
                    </td>
                    <td><?php echo date('d/m/Y H:i A', strtotime($item->fecha_pedido))?></td>
                    <td><?php echo $item->direccion_entrega?></td>
                    <td><strong>$<?php echo number_format($item->total, 2) ?></strong></td>
                    <td><span class="status-badge <?php echo str_replace('_', '-', $item->estado); ?>"><i class="fas <?php echo $iconos_estado[$item->estado] ?? 'fa-circle'; ?>"></i> <?php echo ucfirst(str_replace('_', ' ', $item->estado))?></span></td>
                    <td>
                        <div class="action-buttons">
                            <?php if($item->estado=='pendiente'):?>
                            <a href="editar_pedido.php?id_pedido=<?php echo Crypto::encrypt($item->id_pedido) ?>" class="btn-action edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif;?>
                            <?php if($item->estado=='pendiente' || $item->estado=='preparando' ):?>
                                <a href="cambiar_estado_pedido.php?id_pedido=<?php echo Crypto::encrypt($item->id_pedido) ?>" class="btn-action status">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                                <form action="../controladores/vendedor/cancelar_pedido.php" method="POST" style="display:inline;" class="cancel-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_pedido" value="<?= Crypto::encrypt($item->id_pedido) ?>">
                                    <button type="submit" class="btn-action cancel">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </form>
                            <?php endif;?>
                            <?php if($item->estado=='listo'):?>
                                <a href="asignar_repartidor.php?id_pedido=<?php echo Crypto::encrypt($item->id_pedido) ?>" class="btn-action assign">
                                    <i class="fas fa-user-tie"></i>
                                </a>
                            <?php endif;?>
                        </div>
                    </td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>

        <!-- ===== PAGINACIÓN ===== -->
        <div class="pagination">
            <div class="info">
                Mostrando <strong><?php echo (($pagina_actual - 1) * $resultados_por_pagina) + 1; ?>-<?php echo min($pagina_actual * $resultados_por_pagina, $total_registros); ?></strong> de <strong><?php echo $total_registros; ?></strong> pedidos
            </div>
            <div class="pages">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>"><button><i class="fas fa-chevron-left"></i></button></a>
                <?php else: ?>
                    <button disabled style="opacity:0.4; cursor:not-allowed;"><i class="fas fa-chevron-left"></i></button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina_actual): ?>
                        <button class="active"><?php echo $i; ?></button>
                    <?php else: ?>
                        <a href="?pagina=<?php echo $i; ?>"><button><?php echo $i; ?></button></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>"><button><i class="fas fa-chevron-right"></i></button></a>
                <?php else: ?>
                    <button disabled style="opacity:0.4; cursor:not-allowed;"><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== FILTROS =====
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase();

        const rows = document.querySelectorAll('#ordersTableBody tr');

        rows.forEach(row => {
            const id = row.querySelector('td strong')?.textContent.toLowerCase() || '';
            const cliente = row.querySelector('td:nth-child(2) div:first-child')?.textContent?.toLowerCase() || '';
            const status = row.dataset.status || '';

            const matchesSearch = id.includes(searchTerm) || cliente.includes(searchTerm);
            const matchesStatus = statusValue === '' || status === statusValue;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        updateStats();
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);

    // ===== LIMPIAR FILTROS =====
    document.getElementById('clearFilters').addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        filterTable();
    });

    // ===== CONFIRMAR CANCELACIÓN CON SWEETALERT2 =====
    document.querySelectorAll('.cancel-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cancelar pedido?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
                customClass: {
                    popup: 'swal-popup',
                    title: 'swal-title'
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
<?php include_once '../templates/footer.php'; ?>