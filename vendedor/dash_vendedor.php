<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'vendedor') {
    header("Location: ../acceso_denegado.php");
    exit();
}

$ventas_totales = 0;
$ingresos = 0;
$clientes_atendidos = 0;
$pedidos_mes = 0;
$pedidos_en_curso = 0;
$ventas_semana = array_fill(0, 7, 0);
$pedidos_recientes = [];
$top_productos = [];
$clientes_recientes = [];
$notificaciones = [];

try {
    // Ventas totales (pedidos no cancelados)
    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado'");
    $sql->execute();
    $ventas_totales = (int)$sql->fetchColumn();

    // Ingresos totales
    $sql = $conexion->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado <> 'cancelado'");
    $sql->execute();
    $ingresos = (float)$sql->fetchColumn();

    // Clientes activos
    $sql = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND estado = 'activo'");
    $sql->execute();
    $clientes_atendidos = (int)$sql->fetchColumn();

    // Pedidos del mes
    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado' AND YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())");
    $sql->execute();
    $pedidos_mes = (int)$sql->fetchColumn();

    // Pedidos en curso
    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente','preparando','listo','en_camino')");
    $sql->execute();
    $pedidos_en_curso = (int)$sql->fetchColumn();

    // Ventas por día de la semana actual
    $sql = $conexion->prepare("SELECT DAYOFWEEK(fecha_pedido) AS dia, COALESCE(SUM(total),0) AS total FROM pedidos WHERE estado <> 'cancelado' AND YEARWEEK(fecha_pedido, 1) = YEARWEEK(CURDATE(), 1) GROUP BY dia");
    $sql->execute();
    foreach ($sql->fetchAll(PDO::FETCH_OBJ) as $row) {
        $dia_semana = (int)$row->dia;
        $idx = $dia_semana === 1 ? 6 : $dia_semana - 2;
        if ($idx >= 0 && $idx < 7) {
            $ventas_semana[$idx] = (float)$row->total;
        }
    }

    // Últimos 5 pedidos
    $sql = $conexion->prepare("SELECT p.id_pedido, p.estado, p.total, c.nombre AS cliente, dp.nombre_producto FROM pedidos p JOIN usuarios c ON p.id_cliente = c.id_usuario JOIN (SELECT id_pedido, MAX(producto.nombre) AS nombre_producto FROM detalle_pedido dp JOIN productos producto ON dp.id_producto = producto.id_producto GROUP BY id_pedido) dp ON p.id_pedido = dp.id_pedido ORDER BY p.fecha_pedido DESC LIMIT 5");
    $sql->execute();
    $pedidos_recientes = $sql->fetchAll(PDO::FETCH_OBJ);

    // Top 4 productos más vendidos
    $sql = $conexion->prepare("SELECT prod.nombre AS nombre, COUNT(det.id_detalle) AS ventas, COALESCE(SUM(det.subtotal),0) AS total FROM detalle_pedido det JOIN productos prod ON det.id_producto = prod.id_producto JOIN pedidos p ON det.id_pedido = p.id_pedido AND p.estado <> 'cancelado' GROUP BY prod.id_producto, prod.nombre ORDER BY total DESC LIMIT 4");
    $sql->execute();
    $top_productos = $sql->fetchAll(PDO::FETCH_OBJ);

    // Últimos 4 clientes
    $sql = $conexion->prepare("SELECT u.nombre, u.email, (SELECT COUNT(*) FROM pedidos WHERE id_cliente = u.id_usuario AND estado <> 'cancelado') AS pedidos FROM usuarios u WHERE u.rol = 'cliente' ORDER BY u.id_usuario DESC LIMIT 4");
    $sql->execute();
    $clientes_recientes = $sql->fetchAll(PDO::FETCH_OBJ);

    // Notificaciones no leídas del vendedor
    $id_vendedor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
    $sql = $conexion->prepare("SELECT titulo, mensaje FROM notificaciones WHERE id_usuario = :id_usuario AND leida = 'no' ORDER BY fecha DESC LIMIT 3");
    $sql->bindValue(':id_usuario', $id_vendedor, PDO::PARAM_INT);
    $sql->execute();
    $notificaciones = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en dashboard vendedor: " . $e->getMessage());
}

function altura_barra_vend($valor, $maximo) {
    if ($maximo <= 0) return 8;
    return max(8, (int)round(($valor / $maximo) * 130));
}

function clase_badge_vend($estado) {
    switch ($estado) {
        case 'entregado': return 'completado';
        case 'cancelado': return 'cancelado';
        case 'pendiente': return 'pendiente';
        default: return 'procesando';
    }
}

function iniciales($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        if ($p !== '') $ini .= strtoupper(mb_substr($p, 0, 1));
    }
    return $ini !== '' ? $ini : '?';
}

include_once '../templates/header.php';
?>
<style>
    /* ===== CONTENEDOR ===== */
    /* Incorpora padding y background del body original */
    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
      padding: 0.5rem 1.5rem;
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
      background: #e0edf5;
      color: #4a7a8a;
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

    .dashboard-header .user-info .user-name {
      font-weight: 600;
      font-size: 0.95rem;
    }

    .dashboard-header .user-info .user-role {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    .dashboard-header .header-actions {
      display: flex;
      gap: 0.6rem;
    }

    .btn-icon {
      width: 40px;
      height: 40px;
      border-radius: 40px;
      border: 1px solid #ede8e0;
      background: #faf8f5;
      color: #6b5d4f;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    .btn-icon:hover {
      border-color: #c87a5a;
      color: #c87a5a;
      background: #f8f5f0;
    }

    .btn-icon .badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #b05b4b;
      color: #fff;
      font-size: 0.55rem;
      font-weight: 600;
      padding: 0.15rem 0.4rem;
      border-radius: 60px;
    }

    .btn-icon-wrapper {
      position: relative;
    }

    /* ===== TARJETAS KPI ===== */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .kpi-card .kpi-icon.pink { background: #fde8e4; color: #c87a5a; }
    .kpi-card .kpi-icon.green { background: #e2f0e6; color: #4a7a5c; }
    .kpi-card .kpi-icon.orange { background: #f9ede0; color: #a87d58; }
    .kpi-card .kpi-icon.blue { background: #e0edf5; color: #4a7a8a; }
    .kpi-card .kpi-icon.purple { background: #ede6f0; color: #7a5a8a; }

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

    .kpi-card .kpi-info .kpi-change {
      font-size: 0.7rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.2rem;
      margin-top: 2px;
    }

    .kpi-change.up { color: #4a7a5c; }
    .kpi-change.down { color: #b05b4b; }

    /* ===== MAIN GRID ===== */
    .main-grid {
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
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

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.2rem;
    }

    .card-header .badge-count {
      background: #f8f5f0;
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      font-size: 0.7rem;
      font-weight: 600;
      color: #6b5d4f;
    }

    .card-header a {
      color: #b07d64;
      font-size: 0.8rem;
      font-weight: 500;
      text-decoration: none;
    }

    .card-header a:hover {
      text-decoration: underline;
    }

    /* ===== GRÁFICO DE VENTAS ===== */
    .sales-chart {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      height: 140px;
      margin-top: 0.5rem;
      gap: 0.4rem;
    }

    .chart-bar {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
    }

    .chart-bar .bar {
      width: 100%;
      max-width: 36px;
      background: #dccfc2;
      border-radius: 20px 20px 6px 6px;
      min-height: 8px;
      transition: height 0.3s;
    }

    .chart-bar .bar.bar-1 { height: 65px; background: #c87a5a; }
    .chart-bar .bar.bar-2 { height: 42px; background: #dbb7a4; }
    .chart-bar .bar.bar-3 { height: 85px; background: #c87a5a; }
    .chart-bar .bar.bar-4 { height: 55px; background: #dbb7a4; }
    .chart-bar .bar.bar-5 { height: 105px; background: #c87a5a; }
    .chart-bar .bar.bar-6 { height: 38px; background: #dbb7a4; }
    .chart-bar .bar.bar-7 { height: 72px; background: #c87a5a; }

    .chart-bar .bar-label {
      font-size: 0.6rem;
      margin-top: 0.4rem;
      color: #6b5d4f;
      font-weight: 500;
      text-transform: uppercase;
    }

    .chart-legend {
      display: flex;
      gap: 1.2rem;
      justify-content: center;
      margin-top: 0.8rem;
      font-size: 0.7rem;
      color: #6b5d4f;
    }

    .chart-legend span {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .chart-legend .dot {
      width: 10px;
      height: 10px;
      border-radius: 4px;
      display: inline-block;
    }

    .chart-legend .dot.pink { background: #c87a5a; }
    .chart-legend .dot.beige { background: #dbb7a4; }

    /* ===== LISTA DE PEDIDOS RECIENTES ===== */
    .order-list {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }

    .order-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.7rem 0.8rem;
      background: #faf8f5;
      border-radius: 14px;
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
      font-size: 0.8rem;
      color: #2d2a24;
      min-width: 55px;
    }

    .order-item .order-info .order-details .order-client {
      font-weight: 500;
      font-size: 0.82rem;
    }

    .order-item .order-info .order-details .order-product {
      font-size: 0.7rem;
      color: #7f6e5d;
    }

    .order-item .order-total {
      font-weight: 600;
      font-size: 0.85rem;
      color: #2d2a24;
    }

    .order-item .order-status {
      margin-left: 0.5rem;
    }

    .status-badge {
      padding: 0.15rem 0.7rem;
      border-radius: 60px;
      font-size: 0.6rem;
      font-weight: 600;
    }

    .status-badge.pendiente { background: #f9ede0; color: #a87d58; }
    .status-badge.procesando { background: #e0edf5; color: #4a7a8a; }
    .status-badge.completado { background: #e2f0e6; color: #4a7a5c; }
    .status-badge.cancelado { background: #fde8e4; color: #b05b4b; }

    /* ===== CLIENTES RECIENTES ===== */
    .client-list {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }

    .client-item {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      padding: 0.6rem 0.8rem;
      background: #faf8f5;
      border-radius: 14px;
      border: 1px solid #f1ebe4;
      transition: all 0.2s;
    }

    .client-item:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
    }

    .client-item .client-avatar {
      width: 36px;
      height: 36px;
      border-radius: 36px;
      background: #dccfc2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.7rem;
      color: #3b2e26;
      flex-shrink: 0;
    }

    .client-item .client-info {
      flex: 1;
    }

    .client-item .client-info .client-name {
      font-weight: 500;
      font-size: 0.85rem;
    }

    .client-item .client-info .client-email {
      font-size: 0.7rem;
      color: #7f6e5d;
    }

    .client-item .client-orders {
      font-size: 0.7rem;
      color: #6b5d4f;
      font-weight: 500;
    }

    /* ===== BOTTOM GRID ===== */
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 1.5rem;
    }

    /* ===== PRODUCTOS MÁS VENDIDOS ===== */
    .product-item {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid #f5efe8;
    }

    .product-item:last-child {
      border-bottom: none;
    }

    .product-item .product-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: #f0ece6;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #c87a5a;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .product-item .product-info {
      flex: 1;
    }

    .product-item .product-info .product-name {
      font-weight: 500;
      font-size: 0.85rem;
    }

    .product-item .product-info .product-sales {
      font-size: 0.7rem;
      color: #7f6e5d;
    }

    .product-item .product-total {
      font-weight: 600;
      font-size: 0.85rem;
      color: #2d2a24;
    }

    /* ===== ACCIONES RÁPIDAS ===== */
    .quick-actions {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }

    .quick-btn {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      padding: 0.7rem 1rem;
      background: #faf8f5;
      border-radius: 14px;
      border: 1px solid #f1ebe4;
      transition: all 0.2s;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      font-size: 0.85rem;
      color: #2d2a24;
      text-decoration: none;
    }

    .quick-btn:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
      transform: translateX(4px);
    }

    .quick-btn i {
      color: #c87a5a;
      width: 20px;
      font-size: 1rem;
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
      .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.8rem;
        border-radius: 30px;
        padding: 1rem 1.2rem;
      }
      .dashboard-header .user-info {
        justify-content: space-between;
        flex-wrap: wrap;
      }
      .dashboard-header .header-actions {
        justify-content: center;
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
      .order-item {
        flex-wrap: wrap;
        gap: 0.3rem;
      }
      .order-item .order-total {
        margin-left: auto;
      }
    }

    @media (max-width: 480px) {
      .kpi-grid {
        grid-template-columns: 1fr;
      }
      .dashboard-header .user-info {
        flex-direction: column;
        align-items: stretch;
        gap: 0.3rem;
      }
      .dashboard-header .user-info .status-online {
        justify-content: center;
      }
      .dashboard-header .user-info .user-name,
      .dashboard-header .user-info .user-role {
        text-align: center;
      }
      .dashboard-header .user-info .avatar {
        align-self: center;
      }
      .chart-legend {
        flex-direction: column;
        align-items: center;
        gap: 0.3rem;
      }
    }
</style>
  <div class="dashboard-container">

    <!-- ===== KPI CARDS ===== -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon pink">
          <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $ventas_totales; ?></div>
          <div class="kpi-label">Ventas totales</div>
          <div class="kpi-change up">
            <i class="fas fa-arrow-up"></i> <?php echo $pedidos_mes; ?> este mes
          </div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon green">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number">$<?php echo number_format($ingresos, 0); ?></div>
          <div class="kpi-label">Ingresos</div>
          <div class="kpi-change up">
            <i class="fas fa-dollar-sign"></i> total acumulado
          </div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon orange">
          <i class="fas fa-users"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $clientes_atendidos; ?></div>
          <div class="kpi-label">Clientes atendidos</div>
          <div class="kpi-change up">
            <i class="fas fa-arrow-up"></i> activos
          </div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon blue">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $pedidos_mes; ?></div>
          <div class="kpi-label">Pedidos del mes</div>
          <div class="kpi-change up">
            <i class="fas fa-arrow-up"></i> este mes
          </div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon purple">
          <i class="fas fa-sync-alt"></i>
        </div>
        <div class="kpi-info">
          <div class="kpi-number"><?php echo $pedidos_en_curso; ?></div>
          <div class="kpi-label">Pedidos en curso</div>
          <div class="kpi-change down">
            <i class="fas fa-sync-alt"></i> activos ahora
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MAIN GRID ===== -->
    <div class="main-grid">

      <!-- ===== GRÁFICO DE VENTAS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-chart-bar"></i> Ventas semanales</h3>
          <a href="#">Ver más <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="sales-chart">
          <?php $max_venta_vend = max($ventas_semana); ?>
          <div class="chart-bar">
            <div class="bar bar-1" style="height:<?php echo altura_barra_vend($ventas_semana[0], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Lun</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-2" style="height:<?php echo altura_barra_vend($ventas_semana[1], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Mar</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-3" style="height:<?php echo altura_barra_vend($ventas_semana[2], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Mié</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-4" style="height:<?php echo altura_barra_vend($ventas_semana[3], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Jue</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-5" style="height:<?php echo altura_barra_vend($ventas_semana[4], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Vie</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-6" style="height:<?php echo altura_barra_vend($ventas_semana[5], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Sáb</span>
          </div>
          <div class="chart-bar">
            <div class="bar bar-7" style="height:<?php echo altura_barra_vend($ventas_semana[6], $max_venta_vend); ?>px"></div>
            <span class="bar-label">Dom</span>
          </div>
        </div>
        <div class="chart-legend">
          <span><span class="dot pink"></span> Ventas</span>
          <span><span class="dot beige"></span> Objetivo</span>
        </div>
      </div>

      <!-- ===== PEDIDOS RECIENTES ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-clock"></i> Pedidos recientes</h3>
          <a href="#">Ver todos</a>
        </div>
        <div class="order-list">
          <?php if (count($pedidos_recientes) > 0): ?>
            <?php foreach ($pedidos_recientes as $pedido): ?>
              <div class="order-item">
                <div class="order-info">
                  <span class="order-number">#<?php echo str_pad($pedido->id_pedido, 4, '0', STR_PAD_LEFT); ?></span>
                  <div class="order-details">
                    <div class="order-client"><?php echo htmlspecialchars($pedido->cliente); ?></div>
                    <div class="order-product"><?php echo htmlspecialchars($pedido->nombre_producto ?? '—'); ?></div>
                  </div>
                </div>
                <div class="order-total">$<?php echo number_format($pedido->total, 2); ?></div>
                <div class="order-status">
                  <span class="status-badge <?php echo clase_badge_vend($pedido->estado); ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($pedido->estado))); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="order-item" style="justify-content:center;">
              <span style="color:#7f6e5d;">No hay pedidos registrados</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- ===== BOTTOM GRID ===== -->
    <div class="bottom-grid">

      <!-- ===== PRODUCTOS MÁS VENDIDOS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-crown"></i> Top productos</h3>
          <span class="badge-count">Más vendidos</span>
        </div>
        <div class="product-list">
          <?php $iconos_prod = ['fa-rose', 'fa-tree', 'fa-seedling', 'fa-flower']; ?>
          <?php if (count($top_productos) > 0): ?>
            <?php foreach ($top_productos as $i => $producto): ?>
              <div class="product-item">
                <div class="product-icon"><i class="fas <?php echo $iconos_prod[$i % 4]; ?>"></i></div>
                <div class="product-info">
                  <div class="product-name"><?php echo htmlspecialchars($producto->nombre); ?></div>
                  <div class="product-sales"><?php echo (int)$producto->ventas; ?> ventas · $<?php echo number_format((float)$producto->total, 0); ?></div>
                </div>
                <div class="product-total">$<?php echo number_format((float)$producto->total, 0); ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="product-item" style="justify-content:center;">
              <span style="color:#7f6e5d;">Sin ventas registradas</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===== CLIENTES RECIENTES ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-user-friends"></i> Clientes recientes</h3>
          <a href="#">Ver más</a>
        </div>
        <div class="client-list">
          <?php if (count($clientes_recientes) > 0): ?>
            <?php foreach ($clientes_recientes as $cliente): ?>
              <div class="client-item">
                <div class="client-avatar"><?php echo iniciales($cliente->nombre); ?></div>
                <div class="client-info">
                  <div class="client-name"><?php echo htmlspecialchars($cliente->nombre); ?></div>
                  <div class="client-email"><?php echo htmlspecialchars($cliente->email); ?></div>
                </div>
                <div class="client-orders"><?php echo (int)$cliente->pedidos; ?> pedidos</div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="client-item" style="justify-content:center;">
              <span style="color:#7f6e5d;">Sin clientes registrados</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===== ACCIONES RÁPIDAS ===== -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-bolt"></i> Acciones rápidas</h3>
          <span class="badge-count">Accesos directos</span>
        </div>
        <div class="quick-actions">
          <a href="#" class="quick-btn">
            <i class="fas fa-plus-circle"></i>
            Nuevo pedido
          </a>
          <a href="#" class="quick-btn">
            <i class="fas fa-user-plus"></i>
            Registrar cliente
          </a>
          <a href="#" class="quick-btn">
            <i class="fas fa-file-invoice"></i>
            Generar factura
          </a>
          <a href="#" class="quick-btn">
            <i class="fas fa-chart-line"></i>
            Ver reportes
          </a>
          <a href="#" class="quick-btn">
            <i class="fas fa-envelope"></i>
            Contactar cliente
          </a>
        </div>
      </div>

    </div>
  </div>

  <script>
    // ===== NOTIFICACIONES =====
    document.querySelector('.btn-icon-wrapper .btn-icon')?.addEventListener('click', function() {
      <?php if (count($notificaciones) > 0): ?>
        var notif = <?php echo json_encode($notificaciones, JSON_UNESCAPED_UNICODE); ?>;
        var msg = '📬 Tienes ' + notif.length + ' notificaciones:\n\n';
        notif.forEach(function(n, i) {
          msg += (i + 1) + '️⃣ ' + n.titulo + '\n   ' + n.mensaje + '\n';
        });
        alert(msg);
      <?php else: ?>
        alert('📬 No tienes notificaciones pendientes');
      <?php endif; ?>
    });

    // ===== MENSAJES =====
    document.querySelector('.btn-icon-wrapper + .btn-icon')?.addEventListener('click', function() {
      alert('💬 Mensajes recientes:\n\n• Ana Rodríguez: ¿Cuándo llegará mi pedido?\n• Javier Ramírez: Excelente atención\n• Laura Sánchez: Quiero hacer un pedido especial');
    });

    // ===== CONFIGURACIÓN =====
    document.querySelectorAll('.btn-icon')[2]?.addEventListener('click', function() {
      alert('⚙️ Configuración del vendedor\n\n• Notificaciones: Activadas\n• Zona de entrega: Norte\n• Horario: 9:00 - 18:00');
    });

    // ===== VER MÁS EN GRÁFICO =====
    document.querySelector('.card-header a')?.addEventListener('click', function(e) {
      e.preventDefault();
      alert('📊 Reporte detallado de ventas semanales (simulación)');
    });

    // ===== VER TODOS LOS PEDIDOS =====
    document.querySelectorAll('.card-header a')[1]?.addEventListener('click', function(e) {
      e.preventDefault();
      alert('📋 Lista completa de pedidos (simulación)');
    });

    // ===== VER MÁS CLIENTES =====
    document.querySelectorAll('.card-header a')[2]?.addEventListener('click', function(e) {
      e.preventDefault();
      alert('👥 Lista completa de clientes (simulación)');
    });

    // ===== ACCIONES RÁPIDAS =====
    document.querySelectorAll('.quick-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const text = this.textContent.trim();
        alert(`✅ Acción "${text}" seleccionada (simulación)`);
      });
    });

    // ===== VER DETALLES DE PEDIDO =====
    document.querySelectorAll('.order-item').forEach(item => {
      item.addEventListener('click', function(e) {
        if (e.target.closest('.order-status')) return;
        const number = this.querySelector('.order-number')?.textContent || '';
        const client = this.querySelector('.order-client')?.textContent || '';
        const product = this.querySelector('.order-product')?.textContent || '';
        const total = this.querySelector('.order-total')?.textContent || '';
        alert(`📋 Detalles del pedido ${number}\n\n👤 Cliente: ${client}\n📦 Producto: ${product}\n💰 Total: ${total}\n📝 Notas: Entregar en portería`);
      });
    });

    // ===== VER PERFIL DE CLIENTE =====
    document.querySelectorAll('.client-item').forEach(item => {
      item.addEventListener('click', function() {
        const name = this.querySelector('.client-name')?.textContent || '';
        const email = this.querySelector('.client-email')?.textContent || '';
        const orders = this.querySelector('.client-orders')?.textContent || '';
        alert(`👤 Perfil del cliente\n\nNombre: ${name}\nEmail: ${email}\n${orders}\n\n📊 Compras totales: $2,340\n⭐ Calificación: 5.0`);
      });
    });

    // ===== VER PRODUCTO =====
    document.querySelectorAll('.product-item').forEach(item => {
      item.addEventListener('click', function() {
        const name = this.querySelector('.product-name')?.textContent || '';
        const sales = this.querySelector('.product-sales')?.textContent || '';
        const total = this.querySelector('.product-total')?.textContent || '';
        alert(`📦 Detalles del producto\n\nNombre: ${name}\n${sales}\nTotal: ${total}\n\n📊 Stock: 24 unidades\n⭐ Calificación: 4.8`);
      });
    });

    console.log('👤 Dashboard de vendedor cargado exitosamente');
  </script>
<?php include_once '../templates/footer.php'; ?>