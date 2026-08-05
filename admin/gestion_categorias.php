<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';
require_once '../helpers/Formatos.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../acceso_denegado.php");
  exit();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_admin = $_SESSION['id_usuario']; // id del admin logueado

// Paginación
$resultados_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

// Consultas SQL
try {
  // Total de registros (sin límite)
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM categorias");
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer categorias paginadas
  $sql = $conexion->prepare("SELECT * FROM categorias ORDER BY id_categoria DESC LIMIT :limite OFFSET :offset");
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $categorias = $sql->fetchAll(PDO::FETCH_OBJ);

  $sql_total_categorias = $conexion->prepare("SELECT COUNT(*) FROM categorias");
  $sql_total_categorias->execute();
  $total_categorias = $sql_total_categorias->fetchColumn();

  // Categorias con al menos un producto
  $sql_con_productos = $conexion->prepare("SELECT COUNT(DISTINCT c.id_categoria) FROM categorias c INNER JOIN productos p ON c.id_categoria = p.id_categoria");
  $sql_con_productos->execute();
  $categorias_con_productos = $sql_con_productos->fetchColumn();

  // Categorias sin productos
  $categorias_sin_productos = $total_categorias - $categorias_con_productos;

  // Total de productos registrados
  $sql_total_productos = $conexion->prepare("SELECT COUNT(*) FROM productos");
  $sql_total_productos->execute();
  $total_productos = $sql_total_productos->fetchColumn();
} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $categorias = [];
  $total_categorias = 0;
  $total_registros = 0;
  $total_paginas = 1;
  $pagina_actual = 1;
  $categorias_con_productos = 0;
  $categorias_sin_productos = 0;
  $total_productos = 0;
}

include_once '../templates/header.php';
?>
<style>

    .admin-container {
      max-width: 1200px;
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
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
      font-size: 0.85rem;
    }

    .table-container thead {
      background: #faf8f5;
    }

    .table-container th {
      text-align: left;
      padding: 0.7rem 0.9rem;
      font-weight: 600;
      color: #6b5d4f;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      border-bottom: 1px solid #ede8e0;
      white-space: nowrap;
    }

    .table-container td {
      padding: 0.7rem 0.9rem;
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

    /* ===== ICONOS DE CATEGORÍAS ===== */
    .category-icon {
      width: 36px;
      height: 36px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .category-icon.rose { background: #fde8e4; color: #c87a5a; }
    .category-icon.leaf { background: #e2f0e6; color: #4a7a5c; }
    .category-icon.orchid { background: #ede6f0; color: #7a5a8a; }
    .category-icon.tree { background: #f9ede0; color: #a87d58; }
    .category-icon.tulip { background: #fce4ec; color: #c87a8a; }
    .category-icon.sunflower { background: #fdf0d5; color: #c89a3a; }
    .category-icon.daisy { background: #e8f0e0; color: #5a8a6a; }
    .category-icon.default { background: #f0ece6; color: #8a7a6a; }

    .category-cell {
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .category-cell .category-name {
      font-weight: 500;
    }

    .category-cell .category-id {
      font-size: 0.7rem;
      color: #a28d7a;
    }

    .category-description {
      color: #6b5d4f;
      font-size: 0.85rem;
      max-width: 250px;
    }

    /* ===== ESTADO ===== */
    .status-badge {
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      font-size: 0.7rem;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.active {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .status-badge.inactive {
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
      width: 34px;
      height: 34px;
      border-radius: 34px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
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
        padding: 0.6rem 0.8rem;
        font-size: 0.8rem;
      }
      .action-buttons {
        gap: 0.3rem;
      }
      .btn-action {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
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
      .category-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
      }
      .category-description {
        max-width: 150px;
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
      padding: 0.7rem 1.5rem !important;
      font-size: 0.9rem !important;
    }
    .swal-cancel-btn {
      border-radius: 60px !important;
      font-weight: 600 !important;
      padding: 0.7rem 1.5rem !important;
      font-size: 0.9rem !important;
    }
</style>  <div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
      <div class="title-section">
        <h1><i class="fas fa-tags" style="color:#c87a5a; margin-right:0.5rem;"></i>Gestión de Categorías</h1>
        <span class="badge">Admin</span>
      </div>
      <div class="header-actions">
        <a class="btn-primary" href="agregar_categoria.php">
          <i class="fas fa-plus-circle"></i> Nueva categoría
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
          <i class="fas fa-tags"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_categorias); ?></div>
          <div class="stat-label">Total categorías</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($categorias_con_productos); ?></div>
          <div class="stat-label">Con productos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($categorias_sin_productos); ?></div>
          <div class="stat-label">Sin productos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo htmlspecialchars($total_productos); ?></div>
          <div class="stat-label">Productos registrados</div>
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="filters-bar">
      <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por nombre, descripción..." id="searchInput">
      </div>
      <div class="filter-actions">
        <span style="font-size:0.8rem; color:#7f6e5d; align-self:center;">
          <i class="fas fa-tag"></i> <?php echo $total_categorias; ?> categorías
        </span>
      </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="table-container">
      <div class="table-header">
        <div class="table-title">
          <i class="fas fa-list" style="color:#c87a5a; margin-right:0.5rem;"></i>
          Lista de categorías
        </div>
        <div style="font-size:0.8rem; color:#7f6e5d;">
          Total: <strong><?php echo $total_registros; ?></strong> categorías
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
            <th style="width:80px;">ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th style="text-align:center; width:140px;">Acciones</th>
          </tr>
        </thead>
        <tbody id="categoriesTableBody">
          <?php if(count($categorias) > 0): ?>
          <?php foreach($categorias as $item): ?>
          <tr>
            <td><strong>#<?php echo htmlspecialchars($item->id_categoria); ?></strong></td>
            <td>
              <div class="category-cell">
                <span class="category-icon default"><i class="fas fa-tag"></i></span>
                <div>
                  <div class="category-name"><?php echo htmlspecialchars(ucfirst($item->nombre), ENT_QUOTES, 'UTF-8'); ?></div>
                  <span class="category-id">ID: CAT-<?php echo htmlspecialchars($item->id_categoria); ?></span>
                </div>
              </div>
            </td>
            <td>
              <div class="category-description">
                <?php echo $item->descripcion ? htmlspecialchars(ucfirst($item->descripcion), ENT_QUOTES, 'UTF-8') : '<em style="color:#a28d7a;">Sin descripción</em>'; ?>
              </div>
            </td>
            <td>
              <div class="action-buttons">
                <a href="editar_categoria.php?id_categoria=<?php echo htmlspecialchars(Crypto::encrypt($item->id_categoria), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action edit" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <form action="../controladores/admin/eliminar_categoria.php" method="POST" class="delete-form">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="id_categoria" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_categoria), ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="button" class="btn-action delete" title="Eliminar" onclick="confirmarEliminacion(this)">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr>
            <td colspan="4" style="text-align:center; padding:3rem 1rem; color:#a28d7a;">
              <i class="fas fa-tags" style="font-size:2rem; display:block; margin-bottom:0.8rem; opacity:0.4;"></i>
              No hay categorías registradas todavía
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
          de <strong><?php echo $total_registros; ?></strong> categorías
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
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#categoriesTableBody tr');

    searchInput.addEventListener('input', function() {
      const term = this.value.toLowerCase().trim();
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });

    function confirmarEliminacion(btn) {
      const form = btn.closest('.delete-form');
      Swal.fire({
        title: '¿Eliminar categoría?',
        text: 'Esta acción no se puede deshacer. Los productos asociados perderán su categoría.',
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
  </script>
<?php include_once '../templates/footer.php'; ?>