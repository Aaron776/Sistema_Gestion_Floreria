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
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM productos ");
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer usuarios paginados
  $sql = $conexion->prepare("SELECT id_producto,productos.nombre as producto,categorias.nombre as categoria,productos.descripcion as descripcion,productos.precio as precio,productos.stock as stock,productos.imagen as imagen,productos.estado as estado FROM productos JOIN categorias ON productos.id_categoria = categorias.id_categoria ORDER BY id_producto DESC LIMIT :limite OFFSET :offset");
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $productos = $sql->fetchAll(PDO::FETCH_OBJ);

  // Obtener total de productos
  $sql_total_productos = $conexion->prepare("SELECT COUNT(*) FROM productos");
  $sql_total_productos->execute();
  $total_productos = $sql_total_productos->fetchColumn();

  // Obtener cantidad de productos ene stado activo
  $sql_total_productos_activos = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE estado = 'activo'");
  $sql_total_productos_activos->execute();
  $total_productos_activos = $sql_total_productos_activos->fetchColumn();

  // Obtener cantidad de productos ene stado inactivo
  $sql_total_productos_inactivos = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE estado = 'inactivo'");
  $sql_total_productos_inactivos->execute();
  $total_productos_inactivos = $sql_total_productos_inactivos->fetchColumn();

  // Obtener cantidad de productos ene stado agotado
  $sql_total_productos_agotados = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE estado = 'agotado'");
  $sql_total_productos_agotados->execute();
  $total_productos_agotados = $sql_total_productos_agotados->fetchColumn();
} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $productos = [];
  $total_productos = 0;
  $total_registros = 0;
  $total_paginas = 1;
  $pagina_actual = 1;
  $total_productos_activos = 0;
  $total_productos_inactivos = 0;
  $total_productos_agotados = 0;
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
    .stat-card .stat-icon.red { background: #fde8e4; color: #b05b4b; }

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

    /* ===== PRODUCTO ===== */
    .product-cell {
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .product-cell .product-image {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #f0ece6;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: #c87a5a;
      flex-shrink: 0;
      overflow: hidden;
    }

    .product-thumb {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    .product-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .product-cell .product-info .product-name {
      font-weight: 500;
    }

    .product-cell .product-info .product-id {
      font-size: 0.7rem;
      color: #a28d7a;
    }

    .product-category {
      display: inline-block;
      padding: 0.15rem 0.6rem;
      border-radius: 60px;
      font-size: 0.7rem;
      font-weight: 500;
      background: #f0ece6;
      color: #6b5d4f;
    }

    .product-description {
      color: #6b5d4f;
      font-size: 0.8rem;
      max-width: 200px;
    }

    .product-price {
      font-weight: 600;
      color: #2d2a24;
    }

    .product-stock {
      font-weight: 500;
    }

    .product-stock.low {
      color: #b05b4b;
    }

    .product-stock.medium {
      color: #a87d58;
    }

    .product-stock.high {
      color: #4a7a5c;
    }

    /* ===== ESTADO ===== */
    .status-badge {
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      font-size: 0.65rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      text-transform: capitalize;
    }

    .status-badge.active {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .status-badge.inactive {
      background: #fde8e4;
      color: #b05b4b;
    }

    .status-badge.agotado {
      background: #f9ede0;
      color: #a87d58;
    }

    /* ===== ACCIONES ===== */
    .action-buttons {
      display: flex;
      gap: 0.3rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .btn-action {
      width: 32px;
      height: 32px;
      border-radius: 32px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
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

    .btn-action.activate {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .btn-action.activate:hover {
      background: #c8e0d4;
      transform: scale(1.05);
    }

    .btn-action.deactivate {
      background: #f9ede0;
      color: #a87d58;
    }

    .btn-action.deactivate:hover {
      background: #f5e0cc;
      transform: scale(1.05);
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
    }

    .pagination .pages .page-btn {
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
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-size: 0.85rem;
    }

    .pagination .pages .page-btn:hover {
      border-color: #c87a5a;
      color: #2d2a24;
    }

    .pagination .pages .page-btn.active {
      background: #2d2a24;
      border-color: #2d2a24;
      color: #f4f1eb;
      cursor: default;
    }

    .pagination .pages .page-btn.disabled {
      opacity: 0.4;
      cursor: not-allowed;
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
      .action-buttons {
        gap: 0.2rem;
      }
      .btn-action {
        width: 28px;
        height: 28px;
        font-size: 0.7rem;
      }
      .pagination {
        flex-direction: column;
        align-items: center;
      }
      .product-cell .product-image {
        width: 36px;
        height: 36px;
        font-size: 1rem;
      }
      .product-thumb {
        width: 36px;
        height: 36px;
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
      .product-description {
        max-width: 120px;
      }
    }

    /* ===== SWEETALERT2 PERSONALIZADO ===== */
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
    }
    .swal-cancel-btn {
      border-radius: 60px !important;
      font-weight: 600 !important;
      font-size: 0.9rem !important;
      padding: 0.7rem 1.5rem !important;
      font-family: 'Inter', sans-serif !important;
      border: 2px solid #dccfc2 !important;
    }
</style>

  <div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
      <div class="title-section">
        <h1><i class="fas fa-box" style="color:#c87a5a; margin-right:0.5rem;"></i>Gestión de Productos</h1>
        <span class="badge">Admin</span>
      </div>
      <div class="header-actions">
        <a href="agregar_producto.php" class="btn-primary" id="btnNewProduct">
          <i class="fas fa-plus-circle"></i> Nuevo producto
        </a>
        <button class="btn-outline">
          <i class="fas fa-download"></i> Exportar
        </button>
      </div>
    </header>

    <!-- ===== TARJETAS ESTADÍSTICAS ===== -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon pink">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_productos, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-label">Total productos</div>
          
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_productos_activos, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-label">Activos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_productos_inactivos, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-label">Inactivos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_productos_agotados, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-label">Agotados</div>
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
      <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por nombre, categoría..." id="searchInput">
      </div>
      <select id="categoryFilter">
        <option value="">Todas las categorías</option>
        <option value="Rosas">Rosas</option>
        <option value="Follaje">Follaje</option>
        <option value="Orquídeas">Orquídeas</option>
        <option value="Arreglos">Arreglos</option>
        <option value="Tulipanes">Tulipanes</option>
        <option value="Girasoles">Girasoles</option>
      </select>
      <select id="statusFilter">
        <option value="">Todos los estados</option>
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
        <option value="agotado">Agotado</option>
      </select>
      <div class="filter-actions">
        <button class="btn-filter active" data-filter="all">Todos</button>
        <button class="btn-filter" data-filter="activo">Activos</button>
        <button class="btn-filter" data-filter="inactivo">Inactivos</button>
        <button class="btn-filter" data-filter="agotado">Agotados</button>
      </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
      <div class="table-header">
        <div class="table-title">
          <i class="fas fa-list" style="color:#c87a5a; margin-right:0.5rem;"></i>
          Lista de productos <span>(<?php echo $total_registros; ?> en total)</span>
        </div>
        <div style="font-size:0.75rem; color:#7f6e5d; display:flex; gap:0.8rem; flex-wrap:wrap;">
          <span><i class="fas fa-circle" style="color:#4a7a5c; font-size:0.5rem;"></i> Activo</span>
          <span><i class="fas fa-circle" style="color:#b05b4b; font-size:0.5rem;"></i> Inactivo</span>
          <span><i class="fas fa-circle" style="color:#a87d58; font-size:0.5rem;"></i> Agotado</span>
        </div>
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
    
      <table>
        <thead>
          <tr>
            <th style="width:60px;">ID</th>
            <th style="min-width:180px;">Producto</th>
            <th style="min-width:120px;">Categoría</th>
            <th style="min-width:150px;">Descripción</th>
            <th style="width:80px;">Precio</th>
            <th style="width:80px;">Stock</th>
            <th style="width:80px;">Imagen</th>
            <th style="width:100px; text-align:center;">Estado</th>
            <th style="width:140px; text-align:center;">Acciones</th>
          </tr>
        </thead>
        <tbody id="productsTableBody">
          <?php if(count($productos) > 0): ?>
          <?php foreach ($productos as $item): ?>
          <tr data-status="<?php echo $item->estado; ?>" data-category="<?php echo htmlspecialchars($item->categoria, ENT_QUOTES, 'UTF-8'); ?>">
            <td><strong>#<?php echo htmlspecialchars($item->id_producto, ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td>
              <div class="product-cell">
                <div class="product-image">
                  <i class="fas fa-seedling"></i>
                </div>
                <div class="product-info">
                  <div class="product-name"><?php echo htmlspecialchars($item->producto, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </td>
            <td><span class="product-category">🌹 <?php echo htmlspecialchars($item->categoria, ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td>
              <div class="product-description">
                <?php echo $item->descripcion ? htmlspecialchars($item->descripcion, ENT_QUOTES, 'UTF-8') : '<em style="color:#a28d7a;">Sin descripción</em>'; ?>
              </div>
            </td>
            <td class="product-price">$<?php echo htmlspecialchars(number_format($item->precio, 2), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="product-stock high"><?php echo htmlspecialchars($item->stock, ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td style="text-align:center;">
              <div class="product-thumb" style="background:#fde8e4; margin:0 auto;">
                <?php if($item->imagen): ?>
                <img src="../app/fotos_productos/<?php echo htmlspecialchars($item->imagen, ENT_QUOTES, 'UTF-8'); ?>" alt="Producto">
                <?php else: ?>
                <i class="fas fa-seedling" style="font-size:1rem;"></i>
                <span style="font-size:0.55rem; color:#a28d7a; display:block;">Sin imagen</span>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:center;">
              <?php 
              $clase = '';
              $icono = '';
              $texto = '';
              switch ($item->estado) {
                case 'activo':
                  $clase = 'active';
                  $icono = 'fa-check-circle';
                  $texto = 'Activo';
                  break;
                case 'inactivo':
                  $clase = 'inactive';
                  $icono = 'fa-pause-circle';
                  $texto = 'Inactivo';
                  break;
                case 'agotado':
                  $clase = 'agotado';
                  $icono = 'fa-exclamation-triangle';
                  $texto = 'Agotado';
                  break;
              }
              ?>
              <span class="status-badge <?php echo $clase; ?>"><i class="fas <?php echo $icono; ?>"></i> <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?></span>
            </td>
            <td>
              <div class="action-buttons">
                <a href="editar_producto.php?id_producto=<?php echo htmlspecialchars(Crypto::encrypt($item->id_producto), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action edit" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <?php if ($item->estado != 'activo'): ?>
                  <form action="../controladores/admin/activar_producto.php" method="POST" class="activate-form">
                    <input type="hidden" name="id_producto" value="<?= Crypto::encrypt($item->id_producto) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="button" class="btn-action activate" title="Activar" onclick="confirmarActivacion(this)"><i class="fas fa-toggle-on"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($item->estado == 'activo'): ?>
                  <form action="../controladores/admin/eliminar_producto.php" method="POST" class="delete-form">
                    <input type="hidden" name="id_producto" value="<?= Crypto::encrypt($item->id_producto) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="button" class="btn-action delete" title="Eliminar" onclick="confirmarEliminacion(this)"><i class="fas fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          <?php else: ?>
          <tr>
            <td colspan="9" style="text-align:center; padding:3rem 1rem; color:#a28d7a;">
              <i class="fas fa-box" style="font-size:2rem; display:block; margin-bottom:0.8rem; opacity:0.4;"></i>
              No hay productos registrados todavía
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if($total_registros > 0): ?>
      <div class="pagination">
        <div class="info">
          Mostrando
          <strong><?php echo (($pagina_actual - 1) * $resultados_por_pagina) + 1; ?>
          -<?php echo min($pagina_actual * $resultados_por_pagina, $total_registros); ?></strong>
          de <strong><?php echo $total_registros; ?></strong> productos
        </div>
        <div class="pages">
          <?php if($pagina_actual > 1): ?>
            <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
          <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
          <?php endif; ?>

          <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <?php if($i == $pagina_actual): ?>
              <span class="page-btn active"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="?pagina=<?php echo $i; ?>" class="page-btn"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if($pagina_actual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
          <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // ===== CONFIRMACIONES SWEETALERT2 =====
    function confirmarActivacion(btn) {
      const form = btn.closest('.activate-form');
      Swal.fire({
        title: '¿Activar producto?',
        text: 'El producto será activado y estará disponible para la venta.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4a7a5c',
        cancelButtonColor: '#2d2a24',
        confirmButtonText: '<i class="fas fa-toggle-on"></i> Sí, activar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        background: '#f4f1eb',
        color: '#2d2a24',
        reverseButtons: true,
        customClass: {
          popup: 'swal-popup',
          title: 'swal-title',
          htmlContainer: 'swal-text',
          confirmButton: 'swal-confirm-btn',
          cancelButton: 'swal-cancel-btn'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    }

    function confirmarEliminacion(btn) {
      const form = btn.closest('.delete-form');
      Swal.fire({
        title: '¿Eliminar producto?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b05b4b',
        cancelButtonColor: '#2d2a24',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        background: '#f4f1eb',
        color: '#2d2a24',
        reverseButtons: true,
        customClass: {
          popup: 'swal-popup',
          title: 'swal-title',
          htmlContainer: 'swal-text',
          confirmButton: 'swal-confirm-btn',
          cancelButton: 'swal-cancel-btn'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    }

    // ===== FILTROS Y BÚSQUEDA =====
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const filterButtons = document.querySelectorAll('.btn-filter');
    const rows = document.querySelectorAll('#productsTableBody tr');

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      const categoryValue = categoryFilter.value.toLowerCase();
      const statusValue = statusFilter.value.toLowerCase();
      const activeFilter = document.querySelector('.btn-filter.active');
      const filterType = activeFilter ? activeFilter.dataset.filter : 'all';

      rows.forEach(row => {
        const name = row.querySelector('.product-name')?.textContent.toLowerCase() || '';
        const category = row.dataset.category?.toLowerCase() || '';
        const status = row.dataset.status || '';
        const description = row.querySelector('.product-description')?.textContent.toLowerCase() || '';

        const matchesSearch = name.includes(searchTerm) || 
                             description.includes(searchTerm) ||
                             category.includes(searchTerm);
        
        const matchesCategory = categoryValue === '' || category.includes(categoryValue);
        
        let matchesStatus = true;
        if (statusValue !== '') {
          matchesStatus = status === statusValue;
        }
        
        let matchesFilter = true;
        if (filterType === 'activo') {
          matchesFilter = status === 'activo';
        } else if (filterType === 'inactivo') {
          matchesFilter = status === 'inactivo';
        } else if (filterType === 'agotado') {
          matchesFilter = status === 'agotado';
        }

        if (matchesSearch && matchesCategory && matchesStatus && matchesFilter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);

    filterButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterTable();
      });
    });
  </script>
<?php include_once '../templates/footer.php'; ?>