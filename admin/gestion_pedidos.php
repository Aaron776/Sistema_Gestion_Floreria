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

// Paginación
$resultados_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

// Consultas SQL
try {
  // Total de registros (sin límite)
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM pedidos");
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer pedidos paginadas
  $sql = $conexion->prepare("SELECT p.id_pedido as id_pedido,cliente.nombre as cliente,cliente.email as email,vendedor.nombre as vendedor,p.fecha_pedido as fecha_pedido,p.estado as estado,p.total as total FROM pedidos p JOIN usuarios as cliente ON p.id_cliente = cliente.id_usuario JOIN usuarios as vendedor ON p.id_usuario = vendedor.id_usuario ORDER BY p.fecha_pedido DESC LIMIT :limite OFFSET :offset");
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $pedidos = $sql->fetchAll(PDO::FETCH_OBJ);

  // Obtener total de pedidos
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos");
  $sql->execute();
  $total_pedidos=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener total de pedidos en estado entregado
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='entregado'");
  $sql->execute();
  $total_pedidos_entregados=$sql->fetch(PDO::FETCH_OBJ);

  // Obtener total de pedidos en estado pendiente
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='pendiente'");
  $sql->execute();
  $total_pedidos_pendientes=$sql->fetch(PDO::FETCH_OBJ);


  // Obtener total de pedidos en estado preparando
  $sql=$conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado='preparando'");
  $sql->execute();
  $total_pedidos_preparando=$sql->fetch(PDO::FETCH_OBJ);


  // Obtener cantidad total de los pedidos en esatdo entregado
  $sql=$conexion->prepare("SELECT SUM(total) as total FROM pedidos WHERE estado='entregado'");
  $sql->execute();
  $total_ingresos=$sql->fetch(PDO::FETCH_OBJ);

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


    .admin-container {
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
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

    .stat-card .stat-icon.pink { background: #fde8e4; color: #c87a5a; }
    .stat-card .stat-icon.green { background: #e2f0e6; color: #4a7a5c; }
    .stat-card .stat-icon.orange { background: #f9ede0; color: #a87d58; }
    .stat-card .stat-icon.blue { background: #e0edf5; color: #4a7a8a; }
    .stat-card .stat-icon.purple { background: #ede6f0; color: #7a5a8a; }

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

    .stat-change.up { color: #4a7a5c; }
    .stat-change.down { color: #b05b4b; }

    /* ===== FILTROS Y BÚSQUEDA ===== */
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
      flex-wrap: wrap;
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

    .table-container table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
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
    /* .status-badge base viene de header.php — solo añadimos variantes de esta vista */

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
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .status-badge.en_camino {
      background: #e5f1f0;
      color: #3a7a72;
    }

    .status-badge.entregado {
      background: #ede6f0;
      color: #7a5a8a;
    }

    .status-badge.cancelado {
      background: #fde8e4;
      color: #b05b4b;
    }

    /* ===== CLIENTE ===== */
    .client-cell {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .client-cell .client-avatar {
      width: 34px;
      height: 34px;
      border-radius: 34px;
      background: #dccfc2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.7rem;
      color: #3b2e26;
      flex-shrink: 0;
    }

    .client-cell .client-info .client-name {
      font-weight: 500;
    }

    .client-cell .client-info .client-email {
      font-size: 0.7rem;
      color: #7f6e5d;
      display: block;
    }

    /* ===== TOTAL ===== */
    .order-total {
      font-weight: 600;
      color: #2d2a24;
    }

    /* ===== ACCIONES ===== */
    .action-buttons {
      display: flex;
      gap: 0.4rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .btn-action {
      padding: 0.4rem 1rem;
      border-radius: 60px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.75rem;
      font-weight: 500;
      font-family: 'Inter', sans-serif;
      text-decoration: none;
    }

    .btn-action.view {
      background: #e8f0fe;
      color: #4a6a8a;
    }

    .btn-action.view:hover {
      background: #d4e2f5;
      transform: scale(1.02);
    }

    .btn-action.view i {
      font-size: 0.8rem;
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

    .pagination .pages a,
    .pagination .pages span {
      width: 36px;
      height: 36px;
      border-radius: 36px;
      border: 1px solid #ede8e0;
      background: transparent;
      font-weight: 500;
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
      color: #6b5d4f;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .pagination .pages a:hover {
      border-color: #c87a5a;
      color: #2d2a24;
    }

    .pagination .pages span.active {
      background: #2d2a24;
      border-color: #2d2a24;
      color: #f4f1eb;
      cursor: default;
    }

    .pagination .pages a.disabled,
    .pagination .pages span.disabled {
      opacity: 0.4;
      cursor: not-allowed;
      pointer-events: none;
    }

    /* ===== ESTADO VACÍO ===== */
    .empty-state {
      text-align: center;
      padding: 3rem 1.5rem;
    }

    .empty-state .empty-icon {
      font-size: 3rem;
      color: #dccfc2;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      font-size: 1.1rem;
      color: #2d2a24;
      margin-bottom: 0.4rem;
    }

    .empty-state p {
      font-size: 0.85rem;
      color: #7f6e5d;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
        font-size: 0.75rem;
      }
      .pagination {
        flex-direction: column;
        align-items: center;
      }
      .client-cell .client-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.6rem;
      }
      .btn-action {
        padding: 0.3rem 0.7rem;
        font-size: 0.65rem;
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
    }
    /* ===== MODAL ===== */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      backdrop-filter: blur(4px);
    }

    .modal-overlay.show {
      display: flex;
    }

    .modal {
      background: #ffffff;
      border-radius: 24px;
      width: 100%;
      max-width: 650px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      animation: modalIn 0.25s ease;
    }

    @keyframes modalIn {
      from { opacity: 0; transform: translateY(20px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem 1.8rem 1rem;
      border-bottom: 1px solid #f1ebe4;
    }

    .modal-header .modal-title {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .modal-header .modal-title h2 {
      font-size: 1.15rem;
      font-weight: 700;
      color: #2d2a24;
    }

    .modal-header .modal-title .badge {
      background: #c87a5a;
      color: #fff;
      padding: 0.15rem 0.6rem;
      border-radius: 60px;
      font-size: 0.65rem;
      font-weight: 600;
    }

    .modal-close {
      width: 36px;
      height: 36px;
      border-radius: 36px;
      border: none;
      background: #f8f5f0;
      color: #6b5d4f;
      font-size: 1rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .modal-close:hover {
      background: #fde8e4;
      color: #b05b4b;
    }

    .modal-body {
      padding: 1.5rem 1.8rem;
    }

    .modal-section {
      margin-bottom: 1.2rem;
    }

    .modal-section-title {
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #7f6e5d;
      margin-bottom: 0.6rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .modal-section-title i {
      color: #c87a5a;
      font-size: 0.75rem;
    }

    .modal-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.6rem 1.2rem;
    }

    .modal-info-item {
      display: flex;
      flex-direction: column;
    }

    .modal-info-item .label {
      font-size: 0.7rem;
      color: #a28d7a;
      margin-bottom: 0.1rem;
    }

    .modal-info-item .value {
      font-size: 0.85rem;
      font-weight: 500;
      color: #2d2a24;
    }

    .modal-products-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8rem;
    }

    .modal-products-table thead {
      background: #faf8f5;
    }

    .modal-products-table th {
      text-align: left;
      padding: 0.5rem 0.6rem;
      font-weight: 600;
      color: #6b5d4f;
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      border-bottom: 1px solid #ede8e0;
    }

    .modal-products-table td {
      padding: 0.6rem 0.6rem;
      border-bottom: 1px solid #f5efe8;
      color: #2d2a24;
    }

    .modal-products-table tr:last-child td {
      border-bottom: none;
    }

    .modal-total {
      display: flex;
      justify-content: flex-end;
      padding: 0.8rem 1.8rem;
      border-top: 1px solid #f1ebe4;
      background: #faf8f5;
      border-radius: 0 0 24px 24px;
    }

    .modal-total .total-label {
      font-size: 0.85rem;
      color: #7f6e5d;
      margin-right: 0.6rem;
    }

    .modal-total .total-value {
      font-size: 1.1rem;
      font-weight: 700;
      color: #2d2a24;
    }

    .modal-loading {
      text-align: center;
      padding: 3rem 1.5rem;
      color: #7f6e5d;
      font-size: 0.9rem;
    }

    .modal-loading i {
      font-size: 1.5rem;
      color: #c87a5a;
      margin-bottom: 0.5rem;
      display: block;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @media (max-width: 480px) {
      .modal {
        max-height: 95vh;
        border-radius: 16px;
      }
      .modal-header {
        padding: 1.2rem 1.2rem 0.8rem;
      }
      .modal-body {
        padding: 1.2rem;
      }
      .modal-info-grid {
        grid-template-columns: 1fr;
      }
      .modal-total {
        padding: 0.8rem 1.2rem;
      }
    }

</style>
  <div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
      <div class="title-section">
        <h1><i class="fas fa-shopping-cart" style="color:#c87a5a; margin-right:0.5rem;"></i>Gestión de Pedidos</h1>
        <span class="badge">Admin</span>
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
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_pedidos->total;?></div>
          <div class="stat-label">Total pedidos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_pedidos_entregados->total;?></div>
          <div class="stat-label">Completados</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_pedidos_pendientes->total;?></div>
          <div class="stat-label">Pendientes</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fas fa-truck"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_pedidos_preparando->total;?></div>
          <div class="stat-label">En proceso</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number">$<?php echo number_format($total_ingresos->total, 2);?></div>
          <div class="stat-label">Ingresos totales</div>
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
      <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por cliente, vendedor, ID..." id="searchInput">
      </div>
      <select id="statusFilter">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="procesando">Procesando</option>
        <option value="enviado">Enviado</option>
        <option value="entregado">Entregado</option>
        <option value="cancelado">Cancelado</option>
      </select>
      <div class="filter-actions">
        <button class="btn-filter active" data-filter="all">Todos</button>
        <button class="btn-filter" data-filter="pendiente">Pendientes</button>
        <button class="btn-filter" data-filter="preparando">Preparando</button>
        <button class="btn-filter" data-filter="listo">Listos</button>
        <button class="btn-filter" data-filter="en_camino">En camino</button>
        <button class="btn-filter" data-filter="entregado">Entregados</button>
        <button class="btn-filter" data-filter="cancelado">Cancelados</button>
      </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
      <div class="table-header">
        <div class="table-title">
          <i class="fas fa-list" style="color:#c87a5a; margin-right:0.5rem;"></i>
          Lista de pedidos <span>(<?php echo $total_registros; ?> en total)</span>
        </div>
        <div style="font-size:0.7rem; color:#7f6e5d; display:flex; gap:0.6rem; flex-wrap:wrap;">
          <span><i class="fas fa-circle" style="color:#a87d58; font-size:0.5rem;"></i> Pendiente</span>
          <span><i class="fas fa-circle" style="color:#4a7a8a; font-size:0.5rem;"></i> Preparando</span>
          <span><i class="fas fa-circle" style="color:#4a7a5c; font-size:0.5rem;"></i> Listo</span>
          <span><i class="fas fa-circle" style="color:#3a7a72; font-size:0.5rem;"></i> En camino</span>
          <span><i class="fas fa-circle" style="color:#7a5a8a; font-size:0.5rem;"></i> Entregado</span>
          <span><i class="fas fa-circle" style="color:#b05b4b; font-size:0.5rem;"></i> Cancelado</span>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width:60px;">ID</th>
            <th style="min-width:160px;">Cliente</th>
            <th style="min-width:140px;">Vendedor</th>
            <th style="min-width:140px;">Fecha</th>
            <th style="min-width:120px; text-align:center;">Estado</th>
            <th style="width:100px; text-align:right;">Total</th>
            <th style="width:130px; text-align:center;">Acciones</th>
          </tr>
        </thead>
        <tbody id="ordersTableBody">
          <?php if(count($pedidos) > 0): ?>
          <?php foreach ($pedidos as $item): ?>
          <tr data-status="<?php echo htmlspecialchars($item->estado, ENT_QUOTES, 'UTF-8'); ?>">
            <td><strong>#<?php echo htmlspecialchars($item->id_pedido, ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td>
              <div class="client-cell">
                <div class="client-avatar"><?php echo htmlspecialchars(mb_substr($item->cliente, 0, 2), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="client-info">
                  <div class="client-name"><?php echo htmlspecialchars(ucfirst($item->cliente), ENT_QUOTES, 'UTF-8'); ?></div>
                  <span class="client-email"><?php echo htmlspecialchars($item->email, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars(ucfirst($item->vendedor), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(date('d/m/Y h:i A', strtotime($item->fecha_pedido)), ENT_QUOTES, 'UTF-8'); ?></td>
            <td style="text-align:center;">
              <?php 
              $clase = '';
              $icono = '';
              $texto = '';
              switch ($item->estado) {
                case 'pendiente':
                  $clase = 'pendiente';
                  $icono = 'fa-clock';
                  $texto = 'Pendiente';
                  break;
                case 'preparando':
                  $clase = 'preparando';
                  $icono = 'fa-box-open';
                  $texto = 'Preparando';
                  break;
                case 'listo':
                  $clase = 'listo';
                  $icono = 'fa-clipboard-check';
                  $texto = 'Listo';
                  break;
                case 'en_camino':
                  $clase = 'en_camino';
                  $icono = 'fa-truck';
                  $texto = 'En camino';
                  break;
                case 'entregado':
                  $clase = 'entregado';
                  $icono = 'fa-check-circle';
                  $texto = 'Entregado';
                  break;
                case 'cancelado':
                  $clase = 'cancelado';
                  $icono = 'fa-times-circle';
                  $texto = 'Cancelado';
                  break;
              }
              ?>
              <span class="status-badge <?php echo $clase; ?>"><i class="fas <?php echo $icono; ?>"></i> <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?></span>
            </td>
            <td style="text-align:right;" class="order-total">$<?php echo htmlspecialchars(number_format($item->total, 2), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
              <div class="action-buttons">
                <a href="detalle_pedido.php?id_pedido=<?php echo htmlspecialchars(Crypto::encrypt($item->id_pedido), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action view">
                  <i class="fas fa-eye"></i> Ver
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>No hay pedidos registrados</h3>
                <p>Cuando se realicen pedidos, aparecerán aquí.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if($total_registros > 0): ?>
      <!-- ===== PAGINACIÓN ===== -->
      <div class="pagination">
        <div class="info">
          Mostrando <strong><?php echo (($pagina_actual - 1) * $resultados_por_pagina) + 1; ?>-<?php echo min($pagina_actual * $resultados_por_pagina, $total_registros); ?></strong> de <strong><?php echo $total_registros; ?></strong> pedidos
        </div>
        <div class="pages">
          <?php if($pagina_actual > 1): ?>
            <a href="?pagina=<?php echo $pagina_actual - 1; ?>"><i class="fas fa-chevron-left"></i></a>
          <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
          <?php endif; ?>

          <?php
          $rango = 2;
          $inicio = max(1, $pagina_actual - $rango);
          $fin = min($total_paginas, $pagina_actual + $rango);

          if($inicio > 1): ?>
            <a href="?pagina=1">1</a>
            <?php if($inicio > 2): ?><span class="disabled">...</span><?php endif; ?>
          <?php endif; ?>

          <?php for($i = $inicio; $i <= $fin; $i++): ?>
            <?php if($i == $pagina_actual): ?>
              <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if($fin < $total_paginas): ?>
            <?php if($fin < $total_paginas - 1): ?><span class="disabled">...</span><?php endif; ?>
            <a href="?pagina=<?php echo $total_paginas; ?>"><?php echo $total_paginas; ?></a>
          <?php endif; ?>

          <?php if($pagina_actual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_actual + 1; ?>"><i class="fas fa-chevron-right"></i></a>
          <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>


    <!-- ===== MODAL DETALLE PEDIDO ===== -->
    <div class="modal-overlay" id="modalPedido">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">
            <h2 id="modalPedidoTitulo">Pedido #0000</h2>
            <span class="badge" id="modalPedidoBadge">—</span>
          </div>
          <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="modalPedidoBody">
          <div class="modal-loading">
            <i class="fas fa-spinner"></i>
            Cargando detalles...
          </div>
        </div>
        <div class="modal-total" id="modalPedidoTotal" style="display:none;">
          <span class="total-label">Total:</span>
          <span class="total-value" id="modalTotalValue">$0.00</span>
        </div>
      </div>
    </div>

  <script>
    // ===== FILTROS Y BÚSQUEDA =====
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const filterButtons = document.querySelectorAll('.btn-filter');
    const rows = document.querySelectorAll('#ordersTableBody tr');

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      const statusValue = statusFilter.value.toLowerCase();
      const activeFilter = document.querySelector('.btn-filter.active');
      const filterType = activeFilter ? activeFilter.dataset.filter : 'all';

      rows.forEach(row => {
        const clientName = row.querySelector('.client-name')?.textContent.toLowerCase() || '';
        const clientEmail = row.querySelector('.client-email')?.textContent.toLowerCase() || '';
        const seller = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        const orderId = row.querySelector('td strong')?.textContent.toLowerCase() || '';
        const status = row.dataset.status || '';

        const matchesSearch = clientName.includes(searchTerm) || 
                             clientEmail.includes(searchTerm) ||
                             seller.includes(searchTerm) ||
                             orderId.includes(searchTerm);
        
        let matchesStatus = true;
        if (statusValue !== '') {
          matchesStatus = status === statusValue;
        }
        
        let matchesFilter = true;
        if (filterType !== 'all') {
          matchesFilter = status === filterType;
        }

        if (matchesSearch && matchesStatus && matchesFilter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);

    filterButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterTable();
      });
    });

    // ===== MODAL DETALLE PEDIDO =====
    const modal = document.getElementById('modalPedido');
    const modalBody = document.getElementById('modalPedidoBody');
    const modalTitle = document.getElementById('modalPedidoTitulo');
    const modalBadge = document.getElementById('modalPedidoBadge');
    const modalTotal = document.getElementById('modalPedidoTotal');
    const modalTotalValue = document.getElementById('modalTotalValue');
    const modalClose = document.getElementById('modalClose');

    const estadoLabels = {
      pendiente: 'Pendiente',
      preparando: 'Preparando',
      listo: 'Listo',
      en_camino: 'En camino',
      entregado: 'Entregado',
      cancelado: 'Cancelado'
    };

    const pagoEstadoLabels = {
      pendiente: 'Pendiente',
      pagado: 'Pagado',
      rechazado: 'Rechazado'
    };

    function abrirModal(idPedido) {
      modal.classList.add('show');
      modalBody.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner"></i>Cargando detalles...</div>';
      modalTotal.style.display = 'none';
      document.body.style.overflow = 'hidden';

      fetch('../controladores/admin/obtener_pedido.php?id_pedido=' + encodeURIComponent(idPedido))
        .then(res => {
          if (!res.ok) throw new Error('Error al obtener datos');
          return res.json();
        })
        .then(data => {
          if (data.error) throw new Error(data.error);
          renderModal(data);
        })
        .catch(err => {
          modalBody.innerHTML = '<div class="modal-loading"><i class="fas fa-exclamation-triangle"></i>' + err.message + '</div>';
        });
    }

    function renderModal(data) {
      const p = data.pedido;
      const detalles = data.detalles || [];
      const pago = data.pago;

      modalTitle.textContent = 'Pedido #' + p.id_pedido;
      modalBadge.textContent = estadoLabels[p.estado] || p.estado;

      let html = '';

      // Info del pedido
      html += '<div class="modal-section">';
      html += '<div class="modal-section-title"><i class="fas fa-receipt"></i> Información del pedido</div>';
      html += '<div class="modal-info-grid">';
      html += '<div class="modal-info-item"><span class="label">Fecha</span><span class="value">' + formatDate(p.fecha_pedido) + '</span></div>';
      html += '<div class="modal-info-item"><span class="label">Estado</span><span class="value">' + (estadoLabels[p.estado] || p.estado) + '</span></div>';
      html += '</div></div>';

      // Cliente
      html += '<div class="modal-section">';
      html += '<div class="modal-section-title"><i class="fas fa-user"></i> Cliente</div>';
      html += '<div class="modal-info-grid">';
      html += '<div class="modal-info-item"><span class="label">Nombre</span><span class="value">' + escapeHtml(p.nombre_cliente) + '</span></div>';
      html += '<div class="modal-info-item"><span class="label">Email</span><span class="value">' + escapeHtml(p.email_cliente || '—') + '</span></div>';
      html += '<div class="modal-info-item"><span class="label">Teléfono</span><span class="value">' + escapeHtml(p.telefono_cliente || '—') + '</span></div>';
      html += '<div class="modal-info-item"><span class="label">Dirección</span><span class="value">' + escapeHtml(p.direccion_cliente || '—') + '</span></div>';
      html += '</div></div>';

      // Vendedor
      html += '<div class="modal-section">';
      html += '<div class="modal-section-title"><i class="fas fa-user-tie"></i> Vendedor</div>';
      html += '<div class="modal-info-grid">';
      html += '<div class="modal-info-item"><span class="label">Atendido por</span><span class="value">' + escapeHtml(p.nombre_vendedor || '—') + '</span></div>';
      html += '</div></div>';

      // Dirección de entrega y mensaje
      html += '<div class="modal-section">';
      html += '<div class="modal-section-title"><i class="fas fa-truck"></i> Entrega</div>';
      html += '<div class="modal-info-grid">';
      html += '<div class="modal-info-item" style="grid-column:1/-1;"><span class="label">Dirección de entrega</span><span class="value">' + escapeHtml(p.direccion_entrega) + '</span></div>';
      if (p.mensaje_tarjeta) {
        html += '<div class="modal-info-item" style="grid-column:1/-1;"><span class="label">Mensaje en tarjeta</span><span class="value" style="font-style:italic;">"' + escapeHtml(p.mensaje_tarjeta) + '"</span></div>';
      }
      html += '</div></div>';

      // Productos
      if (detalles.length > 0) {
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="fas fa-box"></i> Productos</div>';
        html += '<table class="modal-products-table">';
        html += '<thead><tr><th>Producto</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">P. Unit.</th><th style="text-align:right;">Subtotal</th></tr></thead>';
        html += '<tbody>';
        detalles.forEach(d => {
          html += '<tr>';
          html += '<td>' + escapeHtml(d.nombre_producto) + '</td>';
          html += '<td style="text-align:center;">' + d.cantidad + '</td>';
          html += '<td style="text-align:right;">$' + parseFloat(d.precio_unitario).toFixed(2) + '</td>';
          html += '<td style="text-align:right;">$' + parseFloat(d.subtotal).toFixed(2) + '</td>';
          html += '</tr>';
        });
        html += '</tbody></table>';
        html += '</div>';
      } else {
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="fas fa-box"></i> Productos</div>';
        html += '<p style="font-size:0.85rem; color:#7f6e5d;">No hay productos registrados en este pedido.</p>';
        html += '</div>';
      }

      // Pago
      if (pago) {
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="fas fa-credit-card"></i> Pago</div>';
        html += '<div class="modal-info-grid">';
        html += '<div class="modal-info-item"><span class="label">Método</span><span class="value">' + escapeHtml(pago.metodo_pago.charAt(0).toUpperCase() + pago.metodo_pago.slice(1)) + '</span></div>';
        html += '<div class="modal-info-item"><span class="label">Estado</span><span class="value">' + (pagoEstadoLabels[pago.estado] || pago.estado) + '</span></div>';
        if (pago.referencia) {
          html += '<div class="modal-info-item"><span class="label">Referencia</span><span class="value">' + escapeHtml(pago.referencia) + '</span></div>';
        }
        html += '<div class="modal-info-item"><span class="label">Fecha de pago</span><span class="value">' + formatDate(pago.fecha_pago) + '</span></div>';
        html += '</div></div>';
      }

      modalBody.innerHTML = html;
      modalTotal.style.display = 'flex';
      modalTotalValue.textContent = '$' + parseFloat(p.total).toFixed(2);
    }

    function closeModal() {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });

    // Enlaces "Ver" abren modal
    document.querySelectorAll('.btn-action.view').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        const params = new URLSearchParams(href.split('?')[1]);
        const idPedido = params.get('id_pedido');
        if (idPedido) abrirModal(idPedido);
      });
    });

    function formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr);
      if (isNaN(d)) return dateStr;
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      let hours = d.getHours();
      const minutes = String(d.getMinutes()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ' ' + ampm;
    }

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
<?php include_once '../templates/footer.php'; ?>