<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'repartidor') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Paginación
$resultados_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$id_repartidor = $_SESSION['id_usuario'];

// Consultas SQL
try {
  // Total de pedidos pendientes asignados a este repartidor
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor AND estado = 'pendiente'");
  $sql_total->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  // Total pedidos en camino asignados a este repartidor
  $sql_en_camino = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor AND estado = 'en_camino'");
  $sql_en_camino->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
  $sql_en_camino->execute();
  $total_en_camino = $sql_en_camino->fetchColumn();

  // Total pedidos entregados por este repartidor
  $sql_entregados = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor AND estado = 'entregado'");
  $sql_entregados->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
  $sql_entregados->execute();
  $total_entregados = $sql_entregados->fetchColumn();

  // Total general de pedidos asignados a este repartidor
  $sql_general = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor");
  $sql_general->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
  $sql_general->execute();
  $total_general = $sql_general->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer pedidos asignados pendientes de recoger
  $sql = $conexion->prepare("SELECT p.direccion_entrega as direccion,p.id_pedido as id_pedido,cliente.nombre as cliente,cliente.email as email,vendedor.nombre as vendedor,p.fecha_pedido as fecha_pedido,p.total as total FROM entregas e JOIN pedidos p ON e.id_pedido = p.id_pedido JOIN usuarios as cliente ON p.id_cliente = cliente.id_usuario JOIN usuarios as vendedor ON p.id_usuario = vendedor.id_usuario WHERE e.id_repartidor = :id_repartidor AND e.estado = 'pendiente' ORDER BY e.fecha_asignacion DESC LIMIT :limite OFFSET :offset");
  $sql->bindParam(":id_repartidor", $id_repartidor, PDO::PARAM_INT);
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $pedidos_listos = $sql->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $pedidos_listos = [];
  $total_registros = 0;
  $total_en_camino = 0;
  $total_entregados = 0;
  $total_general = 0;
  $total_paginas = 1;
  $pagina_actual = 1;
}

include_once '../templates/header.php';
?>
<style>
    /* ===== CONTENEDOR ===== */
    /* Incorpora padding y background decorativo del body original */
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
      margin-bottom: 1rem;
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

    /* ===== ESTADO ===== */
    .status-badge {
      padding: 0.25rem 0.8rem;
      border-radius: 60px;
      font-size: 0.65rem;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.pendiente {
      background: #f9ede0;
      color: #a87d58;
    }

    .status-badge.procesando {
      background: #e0edf5;
      color: #4a7a8a;
    }

    .status-badge.enviado {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .status-badge.entregado {
      background: #d4e8d8;
      color: #2d6a4a;
    }

    .status-badge.cancelado {
      background: #fde8e4;
      color: #b05b4b;
    }

    /* ===== BOTÓN CAMBIAR ESTADO ===== */
    .btn-change-status {
      padding: 0.45rem 1rem;
      border-radius: 60px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 0.75rem;
      cursor: pointer;
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-family: 'Inter', sans-serif;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-change-status:hover {
      background: #1e1a16;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(45, 42, 36, 0.15);
    }

    .btn-change-status i {
      color: #c87a5a;
    }

    .action-cell {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      flex-wrap: wrap;
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
      .action-cell {
        flex-direction: column;
        align-items: stretch;
      }
      .status-select {
        width: 100%;
      }
      .btn-apply-status {
        width: 100%;
        justify-content: center;
        padding: 0.35rem;
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
    .swal-confirm-btn {
      border-radius: 60px !important;
      font-weight: 600 !important;
      font-size: 0.9rem !important;
      padding: 0.7rem 1.5rem !important;
      font-family: 'Inter', sans-serif !important;
      border: none !important;
      background: #c87a5a !important;
      color: #fff !important;
    }
    .swal-cancel-btn {
      border-radius: 60px !important;
      font-weight: 600 !important;
      font-size: 0.9rem !important;
      padding: 0.7rem 1.5rem !important;
      font-family: 'Inter', sans-serif !important;
      border: 2px solid #dccfc2 !important;
      background: transparent !important;
      color: #2d2a24 !important;
    }
</style>
  <div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
      <div class="title-section">
        <h1><i class="fas fa-shopping-bag" style="color:#c87a5a; margin-right:0.5rem;"></i>Gestión de Pedidos Listos</h1>
        <span class="badge">Listos</span>
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
        <div class="stat-icon blue">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_registros; ?></div>
          <div class="stat-label">Total listos</div>
          <div class="stat-change up">
            <i class="fas fa-arrow-up"></i> Actualizado
          </div>
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
      <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por ID, cliente, vendedor..." id="searchInput">
      </div>
      <div class="filter-actions">
        <button class="btn-filter active" data-filter="all">Todos</button>
      </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
      <div class="table-header">
        <div class="table-title">
          <i class="fas fa-list" style="color:#c87a5a; margin-right:0.5rem;"></i>
          Lista de pedidos <span>(<?php echo $total_registros; ?> listos para entregar)</span>
        </div>
        <div style="font-size:0.75rem; color:#7f6e5d; display:flex; gap:0.6rem; flex-wrap:wrap;">
          <span><i class="fas fa-circle" style="color:#a87d58; font-size:0.5rem;"></i> Pendiente</span>
          <span><i class="fas fa-circle" style="color:#4a7a8a; font-size:0.5rem;"></i> Procesando</span>
          <span><i class="fas fa-circle" style="color:#4a7a5c; font-size:0.5rem;"></i> Enviado</span>
          <span><i class="fas fa-circle" style="color:#2d6a4a; font-size:0.5rem;"></i> Entregado</span>
          <span><i class="fas fa-circle" style="color:#b05b4b; font-size:0.5rem;"></i> Cancelado</span>
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
            <th style="min-width:150px;">Cliente</th>
            <th style="min-width:120px;">Vendedor</th>
            <th style="min-width:160px;">Dirección de entrega</th>
            <th style="min-width:120px;">Fecha del pedido</th>
            <th style="width:80px;">Total</th>
            <th style="min-width:140px; text-align:center;">Acciones</th>
          </tr>
        </thead>
        <tbody id="ordersTableBody">
          <?php if (!empty($pedidos_listos) && count($pedidos_listos) > 0): ?>
            <?php foreach ($pedidos_listos as $item): ?>
              <tr data-status="pendiente">
                <td><strong>#<?php echo htmlspecialchars($item->id_pedido, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td>
                  <div style="font-weight:500;"><?php echo htmlspecialchars(ucfirst($item->cliente), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div style="font-size:0.7rem; color:#7f6e5d;"><?php echo htmlspecialchars($item->email, ENT_QUOTES, 'UTF-8'); ?></div>
                </td>
                <td><?php echo htmlspecialchars(ucfirst($item->vendedor), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item->direccion, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y h:i A', strtotime($item->fecha_pedido)), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><strong>$<?php echo htmlspecialchars(number_format($item->total, 2), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td style="text-align:center;">
                  <form action="../controladores/repartidor/cambiar_estado.php" method="post" class="form-change-status">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="id_pedido" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_pedido), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="button" class="btn-change-status btn-change-confirm">
                      <i class="fas fa-truck"></i> Iniciar entrega
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align:center; padding:2rem; color:#7f6e5d;">
                <i class="fas fa-box-open" style="font-size:2rem; color:#dccfc2; margin-bottom:0.5rem; display:block;"></i>
                No tienes pedidos pendientes de recoger.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($total_registros > 0): ?>
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
      <?php endif; ?>
    </div>
    
  </div>

  <script>
 
    // ===== FILTROS =====
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#ordersTableBody tr');

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase().trim();


      rows.forEach(row => {
        const id = row.querySelector('td strong')?.textContent.toLowerCase() || '';
        const cliente = row.querySelector('td:nth-child(2) .fw-bold')?.textContent?.toLowerCase() || 
                       row.querySelector('td:nth-child(2) div')?.textContent?.toLowerCase() || '';
        const vendedor = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
        const direccion = row.querySelector('td:nth-child(4)')?.textContent?.toLowerCase() || '';
        const status = row.dataset.status || '';

        const matchesSearch = id.includes(searchTerm) || 
                             cliente.includes(searchTerm) || 
                             vendedor.includes(searchTerm) ||
                             direccion.includes(searchTerm);
        
        if (matchesSearch) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });

    }

    if (searchInput) {
      searchInput.addEventListener('input', filterTable);
    }

    // ===== INICIAR ENTREGA CON SWEETALERT2 =====
    document.querySelectorAll('.btn-change-confirm').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var form = this.closest('form');
        Swal.fire({
          title: '¿Iniciar entrega?',
          text: 'El pedido pasará a estado "en camino".',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, iniciar',
          cancelButtonText: 'Cancelar',
          customClass: {
            popup: 'swal-popup',
            title: 'swal-title',
            htmlContainer: 'swal-text',
            confirmButton: 'swal-confirm-btn',
            cancelButton: 'swal-cancel-btn'
          },
          buttonsStyling: false
        }).then(function(result) {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  </script>
<?php include '../templates/footer.php'; ?>