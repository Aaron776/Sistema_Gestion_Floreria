<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'repartidor') {
    header("Location: ../acceso_denegado.php");
    exit();
}

$id_repartidor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

$pedidos_asignados = 0;
$entregas_hoy = 0;
$pendientes = 0;
$en_camino = 0;
$entregados = 0;
$pedidos_mios = [];
$paradas_restantes = 0;
$entregas_completadas = 0;

try {
    // Total de entregas asignadas al repartidor
    $sql = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor");
    $sql->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
    $sql->execute();
    $pedidos_asignados = (int)$sql->fetchColumn();

    // Entregas completadas hoy
    $sql = $conexion->prepare("SELECT COUNT(*) FROM entregas WHERE id_repartidor = :id_repartidor AND estado = 'entregado' AND DATE(fecha_entrega) = CURDATE()");
    $sql->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
    $sql->execute();
    $entregas_hoy = (int)$sql->fetchColumn();

    // Entregas por estado
    $sql = $conexion->prepare("SELECT estado, COUNT(*) AS total FROM entregas WHERE id_repartidor = :id_repartidor GROUP BY estado");
    $sql->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
    $sql->execute();
    foreach ($sql->fetchAll(PDO::FETCH_OBJ) as $row) {
        if ($row->estado === 'pendiente') $pendientes = (int)$row->total;
        if ($row->estado === 'en_camino') $en_camino = (int)$row->total;
        if ($row->estado === 'entregado') $entregados = (int)$row->total;
    }

    // Pedidos activos del repartidor (pendiente o en camino)
    $sql = $conexion->prepare("SELECT p.id_pedido, p.direccion_entrega AS direccion, p.estado, cliente.nombre AS cliente FROM entregas e JOIN pedidos p ON e.id_pedido = p.id_pedido JOIN usuarios cliente ON p.id_cliente = cliente.id_usuario WHERE e.id_repartidor = :id_repartidor AND e.estado <> 'entregado' ORDER BY e.fecha_asignacion DESC LIMIT 5");
    $sql->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
    $sql->execute();
    $pedidos_mios = $sql->fetchAll(PDO::FETCH_OBJ);

    // Paradas restantes (pendientes + en camino) y completadas
    $paradas_restantes = $pendientes + $en_camino;
    $entregas_completadas = $entregados;
} catch (PDOException $e) {
    error_log("Error en dashboard repartidor: " . $e->getMessage());
}

function clase_badge_rep($estado) {
    switch ($estado) {
        case 'en_camino': return 'in-progress';
        case 'entregado': return 'delivered';
        default: return 'pending';
    }
}

include_once '../templates/header.php';
?>
<style>
    /* ===== CONTENEDOR ===== */
    /* Incorpora padding y background decorativo del body original */
    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
      padding: 1.5rem;
      background-image: 
        radial-gradient(ellipse at 10% 20%, rgba(200, 122, 90, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 90% 80%, rgba(200, 122, 90, 0.04) 0%, transparent 50%);
    }

    /* ===== HEADER ===== */
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 2rem;
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(8px);
      padding: 1rem 1.8rem;
      border-radius: 60px;
      border: 1px solid rgba(255, 245, 235, 0.5);
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.04);
    }

    .dashboard-header .brand {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.4rem;
      font-weight: 700;
      color: #2d2a24;
      text-decoration: none;
    }

    .dashboard-header .brand i {
      color: #c87a5a;
      font-size: 1.8rem;
    }

    .dashboard-header .brand .role-badge {
      font-size: 0.6rem;
      font-weight: 600;
      background: #f9ede0;
      color: #a87d58;
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .dashboard-header .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .dashboard-header .user-info .status-online {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.8rem;
      color: #4a7a5c;
      font-weight: 500;
    }

    .dashboard-header .user-info .status-online i {
      font-size: 0.5rem;
    }

    .dashboard-header .user-info .avatar {
      width: 44px;
      height: 44px;
      border-radius: 44px;
      background: #dccfc2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #3b2e26;
      border: 2px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .dashboard-header .user-info .user-name {
      font-weight: 600;
      font-size: 0.95rem;
    }

    .dashboard-header .user-info .user-role {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    /* ===== TARJETAS KPI ===== */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }

    .kpi-card {
      background: #ffffff;
      padding: 1.2rem 1.5rem;
      border-radius: 20px;
      border: 1px solid #f1ebe4;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s;
    }

    .kpi-card:hover {
      border-color: #dbcbc0;
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    }

    .kpi-card .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }

    .kpi-card .kpi-icon.blue { background: #e0edf5; color: #4a7a8a; }
    .kpi-card .kpi-icon.green { background: #e2f0e6; color: #4a7a5c; }
    .kpi-card .kpi-icon.orange { background: #f9ede0; color: #a87d58; }
    .kpi-card .kpi-icon.pink { background: #fde8e4; color: #c87a5a; }

    .kpi-card .kpi-info {
      flex: 1;
    }

    .kpi-card .kpi-info .kpi-number {
      font-size: 1.6rem;
      font-weight: 700;
      color: #2d2a24;
      line-height: 1.2;
    }

    .kpi-card .kpi-info .kpi-label {
      font-size: 0.75rem;
      color: #7f6e5d;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    /* ===== FILA PRINCIPAL ===== */
    .main-grid {
      display: grid;
      grid-template-columns: 1.4fr 0.6fr;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    /* ===== TARJETAS ===== */
    .card {
      background: #ffffff;
      border-radius: 24px;
      border: 1px solid #f1ebe4;
      padding: 1.5rem;
    }

    .card-header h3 i {
      color: #c87a5a;
      margin-right: 0.5rem;
    }

    .card-header .badge-count {
      background: #f8f5f0;
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      font-size: 0.7rem;
      font-weight: 600;
      color: #6b5d4f;
    }

    /* ===== LISTA DE PEDIDOS ===== */
    .order-list {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .order-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.8rem 1rem;
      background: #faf8f5;
      border-radius: 16px;
      border: 1px solid #f1ebe4;
      transition: all 0.2s;
    }

    .order-item:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
    }

    .order-item .order-info {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      flex: 1;
    }

    .order-item .order-info .order-number {
      font-weight: 600;
      font-size: 0.85rem;
      color: #2d2a24;
      min-width: 60px;
    }

    .order-item .order-info .order-details {
      flex: 1;
    }

    .order-item .order-info .order-details .order-client {
      font-weight: 500;
      font-size: 0.85rem;
    }

    .order-item .order-info .order-details .order-address {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    .order-item .order-status {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .status-badge.pending {
      background: #f9ede0;
      color: #a87d58;
    }

    .status-badge.in-progress {
      background: #e0edf5;
      color: #4a7a8a;
    }

    .status-badge.delivered {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .status-badge.cancelled {
      background: #fde8e4;
      color: #b05b4b;
    }

    .order-item .order-actions {
      display: flex;
      gap: 0.3rem;
    }

    .btn-action-small {
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
    }

    .btn-action-small.start {
      background: #e0edf5;
      color: #4a7a8a;
    }

    .btn-action-small.start:hover {
      background: #c8dce8;
      transform: scale(1.05);
    }

    .btn-action-small.complete {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .btn-action-small.complete:hover {
      background: #c8e0d4;
      transform: scale(1.05);
    }

    .btn-action-small.view {
      background: #f0ece6;
      color: #6b5d4f;
    }

    .btn-action-small.view:hover {
      background: #e0d8d0;
      transform: scale(1.05);
    }

    /* ===== RUTA / MAPA ===== */
    .route-info {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .route-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.8rem;
    }

    .route-stat-item {
      background: #faf8f5;
      padding: 0.8rem;
      border-radius: 14px;
      text-align: center;
    }

    .route-stat-item .route-stat-number {
      font-size: 1.3rem;
      font-weight: 700;
      color: #2d2a24;
    }

    .route-stat-item .route-stat-label {
      font-size: 0.7rem;
      color: #7f6e5d;
    }

    .route-map-placeholder {
      background: #f0ece6;
      border-radius: 16px;
      height: 160px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      color: #7f6e5d;
      border: 1px dashed #dccfc2;
    }

    .route-map-placeholder i {
      font-size: 2.5rem;
      color: #c87a5a;
      opacity: 0.5;
    }

    .route-map-placeholder p {
      font-size: 0.85rem;
    }

    .route-progress {
      margin-top: 0.5rem;
    }

    .route-progress .progress-bar {
      height: 6px;
      background: #ede8e0;
      border-radius: 10px;
      overflow: hidden;
    }

    .route-progress .progress-bar .progress-fill {
      height: 100%;
      background: #c87a5a;
      border-radius: 10px;
      width: 40%;
      transition: width 0.5s;
    }

    .route-progress .progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      color: #7f6e5d;
      margin-top: 0.3rem;
    }

    /* ===== FILA INFERIOR ===== */
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    /* ===== ESTADÍSTICAS DE ENTREGAS ===== */
    .delivery-stats {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .delivery-stat-row {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .delivery-stat-row .stat-bar {
      flex: 1;
      height: 8px;
      background: #ede8e0;
      border-radius: 10px;
      overflow: hidden;
    }

    .delivery-stat-row .stat-bar .stat-fill {
      height: 100%;
      border-radius: 10px;
      transition: width 0.5s;
    }

    .delivery-stat-row .stat-bar .stat-fill.green { background: #4a7a5c; }
    .delivery-stat-row .stat-bar .stat-fill.orange { background: #a87d58; }
    .delivery-stat-row .stat-bar .stat-fill.blue { background: #4a7a8a; }
    .delivery-stat-row .stat-bar .stat-fill.red { background: #b05b4b; }

    .delivery-stat-row .stat-label {
      font-size: 0.85rem;
      font-weight: 500;
      min-width: 100px;
    }

    .delivery-stat-row .stat-percent {
      font-size: 0.85rem;
      font-weight: 600;
      min-width: 40px;
      text-align: right;
    }

    /* ===== BOTÓN DE ESTADO ===== */
    .btn-toggle-status {
      padding: 0.7rem 1.5rem;
      border-radius: 60px;
      border: none;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
      width: 100%;
      justify-content: center;
      margin-top: 0.5rem;
    }

    .btn-toggle-status.online {
      background: #2d2a24;
      color: #f4f1eb;
    }

    .btn-toggle-status.online:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(45, 42, 36, 0.15);
    }

    .btn-toggle-status.online i {
      color: #4a7a5c;
    }

    .btn-toggle-status.offline {
      background: #fde8e4;
      color: #b05b4b;
      border: 2px solid #b05b4b;
    }

    .btn-toggle-status.offline:hover {
      background: #fcd4cc;
      transform: translateY(-2px);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
      .bottom-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
      .dashboard-container {
        padding: 1rem;
      }
      .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.8rem;
        border-radius: 30px;
        padding: 1rem 1.2rem;
      }
      .dashboard-header .user-info {
        justify-content: space-between;
      }
      .kpi-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.8rem;
      }
      .kpi-card {
        padding: 1rem;
      }
      .kpi-card .kpi-info .kpi-number {
        font-size: 1.3rem;
      }
      .bottom-grid {
        grid-template-columns: 1fr;
        gap: 1.2rem;
      }
      .route-stats {
        grid-template-columns: 1fr 1fr;
      }
      .order-item {
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      .order-item .order-status {
        width: 100%;
        justify-content: flex-start;
      }
      .order-item .order-actions {
        width: 100%;
        justify-content: flex-start;
      }
    }

    @media (max-width: 480px) {
      .kpi-grid {
        grid-template-columns: 1fr;
      }
      .dashboard-header .user-info {
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      .route-stats {
        grid-template-columns: 1fr;
      }
      .delivery-stat-row {
        flex-wrap: wrap;
        gap: 0.3rem;
      }
      .delivery-stat-row .stat-label {
        min-width: 80px;
      }
    }
    /* ===== RESUMEN DE ESTADÍSTICAS ===== */
    .delivery-summary {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #f1ebe4;
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      color: #7f6e5d;
    }

    /* ===== ÚLTIMAS ENTREGAS ===== */
    .recent-list {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }

    .recent-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.6rem 0.8rem;
      background: #faf8f5;
      border-radius: 12px;
      border-left: 4px solid #4a7a5c;
    }

    .recent-item.in-route {
      border-left-color: #4a7a8a;
    }

    .recent-item-detail {
      font-weight: 500;
      font-size: 0.9rem;
    }

    .recent-item-subdetail {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    .recent-item-status {
      font-size: 0.7rem;
      font-weight: 600;
    }

    .recent-item-status.delivered {
      color: #4a7a5c;
    }

    .recent-item-status.in-route {
      color: #4a7a8a;
    }

    /* ===== FOOTER ===== */
    .dashboard-footer {
      margin-top: 2rem;
      font-size: 0.7rem;
      color: #a28d7a;
      text-align: center;
      border-top: 1px solid #ede8e0;
      padding-top: 1.2rem;
    }

    .dashboard-footer i {
      color: #c87a5a;
      margin-right: 4px;
    }
</style>

  <div class="dashboard-container">


    <!-- ===== KPI CARDS ===== -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon blue">
          <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $pedidos_asignados; ?></div>
          <div class="kpi-label">Pedidos asignados</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $entregas_hoy; ?></div>
          <div class="kpi-label">Entregas hoy</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon orange">
          <i class="fas fa-clock"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $pendientes; ?></div>
          <div class="kpi-label">Pendientes</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon pink">
          <i class="fas fa-truck"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $en_camino; ?></div>
          <div class="kpi-label">En camino</div>
        </div>
      </div>
    </div>

    <!-- ===== MAIN GRID ===== -->
    <div class="main-grid">

      <!-- ===== LISTA DE PEDIDOS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-list"></i> Mis pedidos</h3>
          <span class="badge-count"><?php echo $paradas_restantes; ?> activos</span>
        </div>
        <div class="order-list">

          <?php if (count($pedidos_mios) > 0): ?>
            <?php foreach ($pedidos_mios as $pedido): ?>
              <div class="order-item">
                <div class="order-info">
                  <span class="order-number">#<?php echo str_pad($pedido->id_pedido, 4, '0', STR_PAD_LEFT); ?></span>
                  <div class="order-details">
                    <div class="order-client"><?php echo htmlspecialchars($pedido->cliente); ?></div>
                    <div class="order-address"><i class="fas fa-map-marker-alt" style="font-size:0.6rem;"></i> <?php echo htmlspecialchars($pedido->direccion); ?></div>
                  </div>
                </div>
                <div class="order-status">
                  <span class="status-badge <?php echo clase_badge_rep($pedido->estado); ?>"><?php echo $pedido->estado === 'en_camino' ? 'En ruta' : 'Pendiente'; ?></span>
                </div>
                <div class="order-actions">
                  <?php if ($pedido->estado !== 'entregado'): ?>
                    <button class="btn-action-small view" title="Ver detalles">
                      <i class="fas fa-eye"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="order-item" style="justify-content:center;">
              <span style="color:#7f6e5d;">No tienes pedidos activos</span>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- ===== RUTA Y ESTADO ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-route"></i> Mi ruta</h3>
          <span class="badge-count"><?php echo $paradas_restantes; ?> paradas</span>
        </div>

        <div class="route-info">
          <div class="route-stats">
            <div class="route-stat-item">
              <div class="route-stat-number"><?php echo $paradas_restantes; ?></div>
              <div class="route-stat-label">Paradas restantes</div>
            </div>
            <div class="route-stat-item">
              <div class="route-stat-number"><?php echo $entregas_completadas; ?></div>
              <div class="route-stat-label">Entregas completadas</div>
            </div>
            <div class="route-stat-item">
              <div class="route-stat-number"><?php echo $pedidos_asignados; ?></div>
              <div class="route-stat-label">Total asignadas</div>
            </div>
            <div class="route-stat-item">
              <div class="route-stat-number"><?php echo $en_camino; ?></div>
              <div class="route-stat-label">En camino</div>
            </div>
          </div>

          <div class="route-map-placeholder">
            <i class="fas fa-map"></i>
            <p><strong>Mapa de ruta</strong><br>Visualización de tu recorrido</p>
            <span style="font-size:0.7rem;">🟢 Tu ubicación · <?php echo $paradas_restantes > 0 ? '📍 ' . ($pedidos_mios[0]->cliente ?? 'Siguiente parada') : 'Sin entregas pendientes'; ?></span>
          </div>

          <div class="route-progress">
            <div class="progress-bar">
              <?php $pct_ruta = $pedidos_asignados > 0 ? min(100, (int)round($entregas_completadas / $pedidos_asignados * 100)) : 0; ?>
              <div class="progress-fill" style="width:<?php echo $pct_ruta; ?>%;"></div>
            </div>
            <div class="progress-label">
              <span>Inicio</span>
              <span><?php echo $pct_ruta; ?>% completado</span>
              <span>Fin</span>
            </div>
          </div>

          <button class="btn-toggle-status online" id="toggleStatusBtn">
            <i class="fas fa-circle"></i> Estoy en línea
          </button>
        </div>
      </div>

    </div>

    <!-- ===== BOTTOM GRID ===== -->
    <div class="bottom-grid">

      <!-- ===== ESTADÍSTICAS DE ENTREGAS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-chart-bar"></i> Estadísticas de entregas</h3>
          <span class="badge-count">Esta semana</span>
        </div>
        <div class="delivery-stats">
          <?php
          $total_entregas = $pedidos_asignados;
          $pct_done = $total_entregas > 0 ? min(100, (int)round($entregados / $total_entregas * 100)) : 0;
          $pct_ruta = $total_entregas > 0 ? min(100, (int)round($en_camino / $total_entregas * 100)) : 0;
          $pct_pend = $total_entregas > 0 ? min(100, (int)round($pendientes / $total_entregas * 100)) : 0;
          ?>
          <div class="delivery-stat-row">
            <span class="stat-label">Completadas</span>
            <div class="stat-bar">
              <div class="stat-fill green" style="width:<?php echo $pct_done; ?>%;"></div>
            </div>
            <span class="stat-percent"><?php echo $pct_done; ?>%</span>
          </div>
          <div class="delivery-stat-row">
            <span class="stat-label">En ruta</span>
            <div class="stat-bar">
              <div class="stat-fill blue" style="width:<?php echo $pct_ruta; ?>%;"></div>
            </div>
            <span class="stat-percent"><?php echo $pct_ruta; ?>%</span>
          </div>
          <div class="delivery-stat-row">
            <span class="stat-label">Pendientes</span>
            <div class="stat-bar">
              <div class="stat-fill orange" style="width:<?php echo $pct_pend; ?>%;"></div>
            </div>
            <span class="stat-percent"><?php echo $pct_pend; ?>%</span>
          </div>
        </div>
        <div class="delivery-summary">
          <span><i class="fas fa-check-circle" style="color:#4a7a5c;"></i> <?php echo $entregados; ?> entregas completadas</span>
          <span><i class="fas fa-truck" style="color:#a87d58;"></i> <?php echo $paradas_restantes; ?> por entregar</span>
        </div>
      </div>

      <!-- ===== ÚLTIMAS ENTREGAS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-clock"></i> Últimas entregas</h3>
          <span class="badge-count">Hoy</span>
        </div>
        <div class="recent-list">
          <?php
          try {
            $sql = $conexion->prepare("SELECT p.id_pedido, cliente.nombre AS cliente, p.direccion_entrega AS direccion, e.estado, e.fecha_entrega FROM entregas e JOIN pedidos p ON e.id_pedido = p.id_pedido JOIN usuarios cliente ON p.id_cliente = cliente.id_usuario WHERE e.id_repartidor = :id_repartidor ORDER BY e.fecha_asignacion DESC LIMIT 4");
            $sql->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
            $sql->execute();
            $ultimas_entregas = $sql->fetchAll(PDO::FETCH_OBJ);
          } catch (PDOException $e) {
            $ultimas_entregas = [];
          }
          ?>
          <?php if (count($ultimas_entregas) > 0): ?>
            <?php foreach ($ultimas_entregas as $entrega): ?>
              <?php $entregada = $entrega->estado === 'entregado'; ?>
              <div class="recent-item <?php echo $entregada ? '' : 'in-route'; ?>">
                <div>
                  <div class="recent-item-detail">#<?php echo str_pad($entrega->id_pedido, 4, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($entrega->cliente); ?></div>
                  <div class="recent-item-subdetail"><?php echo htmlspecialchars($entrega->direccion); ?> · <?php echo $entregada && $entrega->fecha_entrega ? date('H:i', strtotime($entrega->fecha_entrega)) : 'En curso'; ?></div>
                </div>
                <span class="recent-item-status <?php echo $entregada ? 'delivered' : 'in-route'; ?>"><i class="fas <?php echo $entregada ? 'fa-check-circle' : 'fa-spinner fa-spin'; ?>"></i> <?php echo $entregada ? 'Entregado' : 'En ruta'; ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="recent-item" style="justify-content:center;">
              <span style="color:#7f6e5d;">Sin entregas registradas</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <script>
    // ===== TOGGLE ESTADO EN LÍNEA / DESCONECTADO =====
    const statusToggleBtn = document.getElementById('toggleStatusBtn');
    let isOnline = true;

    statusToggleBtn.addEventListener('click', function() {
      isOnline = !isOnline;
      
      if (isOnline) {
        this.className = 'btn-toggle-status online';
        this.innerHTML = '<i class="fas fa-circle"></i> Estoy en línea';
        
        // Actualizar estado en header
        document.querySelector('.status-online').innerHTML = '<i class="fas fa-circle"></i> En línea';
        document.querySelector('.status-online').style.color = '#4a7a5c';
        
        alert('✅ Has cambiado tu estado a "En línea"');
      } else {
        this.className = 'btn-toggle-status offline';
        this.innerHTML = '<i class="fas fa-circle"></i> Estoy desconectado';
        
        // Actualizar estado en header
        document.querySelector('.status-online').innerHTML = '<i class="fas fa-circle"></i> Desconectado';
        document.querySelector('.status-online').style.color = '#b05b4b';
        
        alert('⏸️ Has cambiado tu estado a "Desconectado"');
      }
    });

    // ===== ACCIONES DE PEDIDOS =====
    document.querySelectorAll('.btn-action-small.start').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const orderItem = this.closest('.order-item');
        const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
        
        if (confirm(`¿Iniciar entrega para el pedido ${orderNumber}?`)) {
          // Cambiar estado
          const statusBadge = orderItem.querySelector('.status-badge');
          statusBadge.className = 'status-badge in-progress';
          statusBadge.textContent = 'En ruta';
          
          // Cambiar botones
          const actions = orderItem.querySelector('.order-actions');
          actions.innerHTML = `
            <button class="btn-action-small complete" title="Marcar como entregado">
              <i class="fas fa-check"></i>
            </button>
            <button class="btn-action-small view" title="Ver detalles">
              <i class="fas fa-eye"></i>
            </button>
          `;
          
          // Reasignar eventos
          reassignEvents(orderItem);
          
          alert(`🚚 Pedido ${orderNumber} iniciado. Dirígete al destino.`);
        }
      });
    });

    document.querySelectorAll('.btn-action-small.complete').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const orderItem = this.closest('.order-item');
        const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
        
        if (confirm(`¿Marcar el pedido ${orderNumber} como entregado?`)) {
          // Cambiar estado
          const statusBadge = orderItem.querySelector('.status-badge');
          statusBadge.className = 'status-badge delivered';
          statusBadge.textContent = 'Entregado';
          
          // Cambiar botones
          const actions = orderItem.querySelector('.order-actions');
          actions.innerHTML = `
            <button class="btn-action-small view" title="Ver detalles">
              <i class="fas fa-eye"></i>
            </button>
          `;
          
          alert(`✅ Pedido ${orderNumber} entregado con éxito.`);
        }
      });
    });

    // ===== VER DETALLES =====
    document.querySelectorAll('.btn-action-small.view').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const orderItem = this.closest('.order-item');
        const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
        const client = orderItem.querySelector('.order-client')?.textContent || '';
        const address = orderItem.querySelector('.order-address')?.textContent?.trim() || '';
        
        alert(`📋 Detalles del pedido ${orderNumber}\n\n👤 Cliente: ${client}\n📍 Dirección: ${address}`);
      });
    });

    // ===== REASIGNAR EVENTOS DESPUÉS DE ACTUALIZAR BOTONES =====
    function reassignEvents(orderItem) {
      orderItem.querySelectorAll('.btn-action-small.start').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
          if (confirm(`¿Iniciar entrega para el pedido ${orderNumber}?`)) {
            const statusBadge = orderItem.querySelector('.status-badge');
            statusBadge.className = 'status-badge in-progress';
            statusBadge.textContent = 'En ruta';
            const actions = orderItem.querySelector('.order-actions');
            actions.innerHTML = `
              <button class="btn-action-small complete" title="Marcar como entregado">
                <i class="fas fa-check"></i>
              </button>
              <button class="btn-action-small view" title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
            `;
            reassignEvents(orderItem);
            alert(`🚚 Pedido ${orderNumber} iniciado.`);
          }
        });
      });

      orderItem.querySelectorAll('.btn-action-small.complete').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
          if (confirm(`¿Marcar el pedido ${orderNumber} como entregado?`)) {
            const statusBadge = orderItem.querySelector('.status-badge');
            statusBadge.className = 'status-badge delivered';
            statusBadge.textContent = 'Entregado';
            const actions = orderItem.querySelector('.order-actions');
            actions.innerHTML = `
              <button class="btn-action-small view" title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
            `;
            reassignEvents(orderItem);
            alert(`✅ Pedido ${orderNumber} entregado con éxito.`);
          }
        });
      });

      orderItem.querySelectorAll('.btn-action-small.view').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const orderNumber = orderItem.querySelector('.order-number')?.textContent || '';
          const client = orderItem.querySelector('.order-client')?.textContent || '';
          const address = orderItem.querySelector('.order-address')?.textContent?.trim() || '';
          alert(`📋 Detalles del pedido ${orderNumber}\n\n👤 Cliente: ${client}\n📍 Dirección: ${address}`);
        });
      });
    }

    // ===== ACTUALIZAR PROGRESO DE RUTA =====
    let progress = 40;
    setInterval(() => {
      if (progress < 100) {
        progress += 1;
        document.querySelector('.progress-fill').style.width = progress + '%';
        document.querySelector('.progress-label span:last-child').textContent = progress + '% completado';
      }
    }, 30000);

    console.log('🚚 Dashboard de repartidor cargado exitosamente');
  </script>
<?php include_once '../templates/footer.php';