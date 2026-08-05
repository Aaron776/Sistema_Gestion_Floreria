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
  $sql_total = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol!='cliente'");
  $sql_total->execute();
  $total_registros = $sql_total->fetchColumn();

  $total_paginas = max(1, ceil($total_registros / $resultados_por_pagina));
  if ($pagina_actual > $total_paginas) $pagina_actual = 1;
  $offset = ($pagina_actual - 1) * $resultados_por_pagina;

  // Traer usuarios paginados
  $sql = $conexion->prepare("SELECT id_usuario,nombre,telefono,direccion,email,rol,estado,ultimo_acceso FROM usuarios WHERE rol!='cliente' ORDER BY id_usuario DESC LIMIT :limite OFFSET :offset");
  $sql->bindValue(':limite', $resultados_por_pagina, PDO::PARAM_INT);
  $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
  $sql->execute();
  $usuarios = $sql->fetchAll(PDO::FETCH_OBJ);

  // Consultas para estadísticas
  $total_usuarios = $total_registros;

  // Traer cantidad de usaurio con rol admin
  $sql_usuarios_admin = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol='admin'");
  $sql_usuarios_admin->execute();
  $usuarios_admin = $sql_usuarios_admin->fetch(PDO::FETCH_OBJ)->total;

  // Traer cantidad de usaurio con rol vendedor
  $sql_usuarios_vendedor = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol='vendedor'");
  $sql_usuarios_vendedor->execute();
  $usuarios_vendedor = $sql_usuarios_vendedor->fetch(PDO::FETCH_OBJ)->total;

  // Traer cantidad de usaurio con rol repartidor
  $sql_usuarios_repartidor = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol='repartidor'");
  $sql_usuarios_repartidor->execute();
  $usuarios_repartidor = $sql_usuarios_repartidor->fetch(PDO::FETCH_OBJ)->total;
} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $usuarios = [];
  $total_usuarios = 0;
  $total_registros = 0;
  $total_paginas = 1;
  $pagina_actual = 1;
}

include_once '../templates/header.php';
?>
<style>
  .admin-container {
    max-width: 1300px;
    margin: 0 auto;
    width: 100%;
    padding: 0 1.5rem;
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

  .stat-card .stat-icon.purple {
    background: #ede6f0;
    color: #7a5a8a;
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
    font-size: 0.85rem;
  }

  .table-container thead {
    background: #faf8f5;
  }

  .table-container th {
    text-align: left;
    padding: 0.9rem 1.2rem;
    font-weight: 600;
    color: #6b5d4f;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 1px solid #ede8e0;
    white-space: nowrap;
  }

  .table-container td {
    padding: 0.9rem 1.2rem;
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

  .table-container .alert {
    margin: 1rem 1.5rem;
  }

  /* Roles */
  .role-badge {
    padding: 0.25rem 0.9rem;
    border-radius: 60px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-transform: capitalize;
    white-space: nowrap;
  }

  .role-badge i {
    font-size: 0.75rem;
  }

  .role-badge.admin {
    background: #ede6f0;
    color: #7a5a8a;
  }

  .role-badge.vendedor {
    background: #e0edf5;
    color: #4a7a8a;
  }

  .role-badge.repartidor {
    background: #f9ede0;
    color: #a87d58;
  }

  /* Acciones */
  .action-buttons {
    display: flex;
    gap: 0.4rem;
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

  .btn-action.password {
    background: #f9ede0;
    color: #a87d58;
  }

  .btn-action.password:hover {
    background: #f5e0cc;
    transform: scale(1.05);
  }

  .btn-action.activate {
    background: #e2f0e6;
    color: #4a7a5c;
  }

  .btn-action.activate:hover {
    background: #c8dfd0;
    transform: scale(1.05);
  }

  /* Avatar en tabla */
  .user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 36px;
    background: #dccfc2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    color: #3b2e26;
    flex-shrink: 0;
  }

  .user-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .user-cell .user-name {
    font-weight: 500;
  }

  .user-cell .user-email {
    font-size: 0.75rem;
    color: #7f6e5d;
    display: block;
  }

  .icon-accent {
    color: #c87a5a;
    margin-right: 0.5rem;
  }

  .table-legend {
    font-size: 0.8rem;
    color: #7f6e5d;
  }

  .table-legend .dot {
    font-size: 0.5rem;
    vertical-align: middle;
  }

  .table-legend .dot.active {
    color: #4a7a5c;
  }

  .table-legend .dot.inactive {
    color: #b05b4b;
  }

  .table-legend .sep {
    margin: 0 0.5rem;
    color: #7f6e5d;
  }

  .last-access {
    font-size: 0.8rem;
    color: #6b5d4f;
  }

  .last-access i {
    color: #a28d7a;
    margin-right: 4px;
  }

  /* ===== ESTADO VACÍO ===== */
  .empty-state {
    padding: 3rem 1.5rem;
    text-align: center;
    color: #7f6e5d;
  }

  .empty-state i {
    font-size: 3rem;
    color: #dccfc2;
    margin-bottom: 1rem;
  }

  .empty-state p {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d2a24;
    margin-bottom: 0.3rem;
  }

  .empty-state span {
    font-size: 0.85rem;
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
      width: 28px;
      height: 28px;
      font-size: 0.7rem;
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

    .user-cell {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.2rem;
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
      <h1><i class="fas fa-users icon-accent"></i>Gestión de Usuarios</h1>
      <span class="badge">Admin</span>
    </div>
    <div class="header-actions">
      <a href="agregar_usuario.php" class="btn-primary">
        <i class="fas fa-user-plus"></i> Nuevo usuario
      </a>
      <a href="exportar_usuarios.php" class="btn-outline">
        <i class="fas fa-download"></i> Exportar
      </a>
    </div>
  </header>

  <!-- ===== TARJETAS ESTADÍSTICAS ===== -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon purple">
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-info">
        <div class="stat-number"><?= htmlspecialchars($total_usuarios) ?></div>
        <div class="stat-label">Total usuarios</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon green">
        <i class="fas fa-user-check"></i>
      </div>
      <div class="stat-info">
        <div class="stat-number"><?= htmlspecialchars($usuarios_admin) ?></div>
        <div class="stat-label">Administradores</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon orange">
        <i class="fas fa-user-tie"></i>
      </div>
      <div class="stat-info">
        <div class="stat-number"><?= htmlspecialchars($usuarios_vendedor) ?></div>
        <div class="stat-label">Vendedores</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon blue">
        <i class="fas fa-truck"></i>
      </div>
      <div class="stat-info">
        <div class="stat-number"><?= htmlspecialchars($usuarios_repartidor) ?></div>
        <div class="stat-label">Repartidores</div>
      </div>
    </div>
  </div>

  <!-- ===== FILTROS ===== -->
  <div class="filters-bar">
    <div class="search-wrapper">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Buscar por nombre, email, teléfono..." id="searchInput">
    </div>
    <select id="roleFilter">
      <option value="">Todos los roles</option>
      <option value="admin">Administrador</option>
      <option value="vendedor">Vendedor</option>
      <option value="repartidor">Repartidor</option>
    </select>
    <div class="filter-actions">
      <button class="btn-filter active">Todos</button>
      <button class="btn-filter">Activos</button>
      <button class="btn-filter">Inactivos</button>
    </div>
  </div>

  <!-- ===== TABLA ===== -->
  <div class="table-container">
    <div class="table-header">
      <div class="table-title">
        <i class="fas fa-list icon-accent"></i>
        Lista de usuarios <span>(<?= $total_registros ?> en total)</span>
      </div>
      <div class="table-legend">
        <i class="fas fa-circle dot active"></i> Activo
        <span class="sep">|</span>
        <i class="fas fa-circle dot inactive"></i> Inactivo
      </div>
    </div>

    <?php if (empty($usuarios)): ?>
      <div class="empty-state">
        <i class="fas fa-users-slash"></i>
        <p>No se encontraron usuarios</p>
        <span>No hay usuarios registrados que coincidan con los criterios.</span>
      </div>
    <?php else: ?>

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
            <th>ID</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Último acceso</th>
            <th style="text-align:center;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php $desde = ($pagina_actual - 1) * $resultados_por_pagina + 1; ?>
          <?php foreach ($usuarios as $item): ?>
            <tr>
              <td><strong>#<?= htmlspecialchars($item->id_usuario) ?></strong></td>
              <td>
                <div class="user-cell">
                  <span class="user-avatar"><?= mb_substr(htmlspecialchars($item->nombre), 0, 2) ?></span>
                  <div>
                    <div class="user-name"><?= ucfirst(htmlspecialchars($item->nombre)) ?></div>
                    <span class="user-email"><?= htmlspecialchars($item->email) ?></span>
                  </div>
                </div>
              </td>
              <td><?= empty(trim($item->direccion)) ? '<span class="text-muted" style="font-style:italic;">Sin dirección</span>' : ucfirst(htmlspecialchars($item->direccion)) ?></td>
              <td><?= htmlspecialchars($item->telefono) ?></td>
              <td>
                <?php
                $icono_rol = match ($item->rol) {
                  'admin'     => 'fa-user-shield',
                  'vendedor'  => 'fa-user-tie',
                  'repartidor' => 'fa-truck',
                  default     => 'fa-user'
                };
                ?>
                <span class="role-badge <?= $item->rol ?>"><i class="fas <?= $icono_rol ?>"></i> <?= ucfirst(htmlspecialchars($item->rol)) ?></span>
              </td>
              <td class="last-access"><i class="fas fa-clock"></i> <?= Formatos::tiempoAgo(htmlspecialchars($item->ultimo_acceso)) ?></td>
              <td>
                <div class="action-buttons">
                  <a href="editar_usuario.php?id_usuario=<?php echo Crypto::encrypt($item->id_usuario); ?>" class="btn-action edit" title="Editar"><i class="fas fa-edit"></i></a>
                  <form action="../controladores/admin/editar_password_usuario.php" method="POST" class="password-form">
                    <input type="hidden" name="id_usuario" value="<?= Crypto::encrypt($item->id_usuario) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="button" class="btn-action password" title="Cambiar contraseña" onclick="confirmarPassword(this)"><i class="fas fa-key"></i></button>
                  </form>
                  <?php if ($item->estado == 'inactivo'): ?>
                    <form action="../controladores/admin/activar_usuario.php" method="POST" class="activate-form">
                      <input type="hidden" name="id_usuario" value="<?= Crypto::encrypt($item->id_usuario) ?>">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <button type="button" class="btn-action activate" title="Activar" onclick="confirmarActivacion(this)"><i class="fas fa-toggle-on"></i></button>
                    </form>
                  <?php endif; ?>
                  <?php if ($item->id_usuario != $id_admin): ?>
                    <form action="../controladores/admin/eliminar_usuario.php" method="POST" class="delete-form">
                      <input type="hidden" name="id_usuario" value="<?= Crypto::encrypt($item->id_usuario) ?>">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                      <button type="button" class="btn-action delete" title="Eliminar" onclick="confirmarEliminacion(this)"><i class="fas fa-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- ===== PAGINACIÓN ===== -->
      <div class="pagination">
        <div class="info">
          Mostrando
          <strong><?= (($pagina_actual - 1) * $resultados_por_pagina) + 1 ?>
          -<?= min($pagina_actual * $resultados_por_pagina, $total_registros) ?></strong>
          de <strong><?= $total_registros ?></strong> resultado<?= $total_registros != 1 ? 's' : '' ?>
        </div>
        <div class="pages">
          <?php if($pagina_actual > 1): ?>
            <a href="?pagina=<?= $pagina_actual - 1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
          <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
          <?php endif; ?>

          <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <?php if($i == $pagina_actual): ?>
              <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
              <a href="?pagina=<?= $i ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if($pagina_actual < $total_paginas): ?>
            <a href="?pagina=<?= $pagina_actual + 1 ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
          <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <script>
    function confirmarActivacion(btn) {
      const form = btn.closest('.activate-form');
      Swal.fire({
        title: '¿Activar usuario?',
        text: 'El usuario será reactivado y podrá acceder al sistema nuevamente.',
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
        title: '¿Desactivar usuario?',
        text: 'El usuario será desactivado y no podrá acceder al sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b05b4b',
        cancelButtonColor: '#2d2a24',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, desactivar',
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

    function confirmarPassword(btn) {
      const form = btn.closest('.password-form');
      Swal.fire({
        title: '¿Restablecer contraseña?',
        text: 'Se generará una nueva contraseña y se enviará al correo del usuario.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#c87a5a',
        cancelButtonColor: '#2d2a24',
        confirmButtonText: '<i class="fas fa-key"></i> Sí, restablecer',
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
    const roleFilter = document.getElementById('roleFilter');
    const rows = document.querySelectorAll('tbody tr');

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      const roleValue = roleFilter.value.toLowerCase();

      rows.forEach(row => {
        const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
        const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
        const phone = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
        const role = row.querySelector('.role-badge')?.textContent.toLowerCase() || '';

        const matchesSearch = name.includes(searchTerm) ||
          email.includes(searchTerm) ||
          phone.includes(searchTerm);

        const matchesRole = roleValue === '' || role.includes(roleValue);

        if (matchesSearch && matchesRole) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);

    // ===== FILTROS DE ESTADO =====
    document.querySelectorAll('.filter-actions .btn-filter').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-actions .btn-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Simulación de filtro por estado
      });
    });

    // ===== ACCIONES DE BOTONES =====
  </script>
  <?php include_once '../templates/footer.php'; ?>