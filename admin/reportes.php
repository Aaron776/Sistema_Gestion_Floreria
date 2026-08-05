<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../acceso_denegado.php");
  exit();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Datos reales para tarjetas estadísticas
$total_reportes = 6;
$pedidos_mes = 0;
$pedidos_pendientes = 0;
$pedidos_entregados = 0;
$pedidos_hoy = 0;
$total_productos = 0;
$total_clientes = 0;
$total_usuarios = 0;
$total_categorias = 0;

try {
  $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())");
  $sql->execute();
  $pedidos_mes = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
  $sql->execute();
  $pedidos_pendientes = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado'");
  $sql->execute();
  $pedidos_entregados = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()");
  $sql->execute();
  $pedidos_hoy = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE estado = 'activo'");
  $sql->execute();
  $total_productos = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND estado = 'activo'");
  $sql->execute();
  $total_clientes = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
  $sql->execute();
  $total_usuarios = (int)$sql->fetchColumn();

  $sql = $conexion->prepare("SELECT COUNT(*) FROM categorias");
  $sql->execute();
  $total_categorias = (int)$sql->fetchColumn();
} catch (PDOException $e) {
  error_log("Error en reportes: " . $e->getMessage());
}

include_once '../templates/header.php';
?>
 <style>
    .admin-container {
      max-width: 1300px;
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
      margin-bottom: 2.5rem;
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

    /* ===== TARJETAS DE REPORTES ===== */
    .reports-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .report-card {
      background: #ffffff;
      border-radius: 24px;
      border: 1px solid #f1ebe4;
      padding: 1.8rem 1.8rem 1.5rem;
      transition: all 0.3s;
      display: flex;
      flex-direction: column;
    }

    .report-card:hover {
      border-color: #dbcbc0;
      transform: translateY(-4px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    }

    .report-card .report-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.8rem;
    }

    .report-card .report-icon {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    .report-card .report-icon.purple { background: #ede6f0; color: #7a5a8a; }
    .report-card .report-icon.green { background: #e2f0e6; color: #4a7a5c; }
    .report-card .report-icon.orange { background: #f9ede0; color: #a87d58; }
    .report-card .report-icon.blue { background: #e0edf5; color: #4a7a8a; }
    .report-card .report-icon.pink { background: #fde8e4; color: #c87a5a; }
    .report-card .report-icon.teal { background: #d4f0ed; color: #3a8a7a; }

    .report-card .report-info {
      flex: 1;
    }

    .report-card .report-info h3 {
      font-size: 1.1rem;
      font-weight: 600;
      color: #2d2a24;
      margin-bottom: 0.2rem;
    }

    .report-card .report-info p {
      font-size: 0.85rem;
      color: #7f6e5d;
      line-height: 1.5;
    }

    .report-card .report-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px solid #f5efe8;
      font-size: 0.75rem;
      color: #a28d7a;
    }

    .report-card .report-meta .badge-report {
      background: #f8f5f0;
      padding: 0.15rem 0.6rem;
      border-radius: 60px;
      font-size: 0.65rem;
      font-weight: 600;
      color: #6b5d4f;
    }

    .report-card .report-actions {
      display: flex;
      gap: 0.6rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }

    .btn-pdf {
      flex: 1;
      padding: 0.6rem 1rem;
      border-radius: 60px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      font-family: 'Inter', sans-serif;
      min-width: 100px;
    }

    .btn-pdf:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(45, 42, 36, 0.15);
    }

    .btn-pdf i {
      color: #c87a5a;
      font-size: 0.9rem;
    }

    .btn-excel {
      flex: 1;
      padding: 0.6rem 1rem;
      border-radius: 60px;
      border: 2px solid #4a7a5c;
      background: transparent;
      color: #4a7a5c;
      font-weight: 600;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      font-family: 'Inter', sans-serif;
      min-width: 100px;
    }

    .btn-excel:hover {
      background: #e2f0e6;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(74, 122, 92, 0.15);
    }

    .btn-excel i {
      color: #4a7a5c;
      font-size: 0.9rem;
    }

    .btn-pdf:disabled,
    .btn-excel:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
    }

    /* ===== SECCIÓN DE REPORTES RÁPIDOS ===== */
    .quick-reports {
      background: #ffffff;
      border-radius: 24px;
      border: 1px solid #f1ebe4;
      padding: 1.8rem 2rem;
      margin-top: 1rem;
    }

    .quick-reports .quick-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.2rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .quick-reports .quick-header h3 {
      font-size: 1.1rem;
      font-weight: 600;
      color: #2d2a24;
    }

    .quick-reports .quick-header h3 i {
      color: #c87a5a;
      margin-right: 0.5rem;
    }

    .quick-reports .quick-header .badge-time {
      font-size: 0.75rem;
      color: #7f6e5d;
      background: #f8f5f0;
      padding: 0.3rem 1rem;
      border-radius: 60px;
    }

    .quick-reports .quick-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    .quick-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.8rem 1rem;
      background: #faf8f5;
      border-radius: 16px;
      border: 1px solid #f1ebe4;
      transition: all 0.2s;
    }

    .quick-item:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
    }

    .quick-item .quick-info {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .quick-item .quick-info i {
      color: #c87a5a;
      font-size: 1.1rem;
    }

    .quick-item .quick-info span {
      font-size: 0.85rem;
      font-weight: 500;
      color: #2d2a24;
    }

    .quick-item .quick-btns {
      display: flex;
      gap: 0.3rem;
    }

    .quick-item .quick-btns button {
      width: 30px;
      height: 30px;
      border-radius: 30px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.7rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .quick-item .quick-btns .btn-pdf-sm {
      background: #2d2a24;
      color: #f4f1eb;
    }

    .quick-item .quick-btns .btn-pdf-sm:hover {
      background: #1e1a16;
      transform: scale(1.05);
    }

    .quick-item .quick-btns .btn-excel-sm {
      background: #e2f0e6;
      color: #4a7a5c;
    }

    .quick-item .quick-btns .btn-excel-sm:hover {
      background: #c8e0d4;
      transform: scale(1.05);
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
      .reports-grid {
        grid-template-columns: 1fr;
      }
      .report-card .report-actions {
        flex-direction: column;
      }
      .btn-pdf, .btn-excel {
        width: 100%;
      }
      .quick-reports .quick-grid {
        grid-template-columns: 1fr;
      }
      .quick-item {
        flex-wrap: wrap;
        gap: 0.5rem;
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
      .report-card {
        padding: 1.2rem;
      }
    }
</style>
  <div class="admin-container">

    <!-- ===== HEADER ===== -->
    <header class="admin-header">
      <div class="title-section">
        <h1><i class="fas fa-chart-bar" style="color:#c87a5a; margin-right:0.5rem;"></i>Reportes</h1>
        <span class="badge">Admin</span>
      </div>
      <div class="header-actions">
        <button class="btn-outline" id="refreshReports">
          <i class="fas fa-sync-alt"></i> Actualizar
        </button>
        <button class="btn-outline" id="exportAll">
          <i class="fas fa-download"></i> Exportar todo
        </button>
      </div>
    </header>

    <!-- ===== TARJETAS ESTADÍSTICAS ===== -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon pink">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $total_reportes; ?></div>
          <div class="stat-label">Reportes disponibles</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $pedidos_mes; ?></div>
          <div class="stat-label">Pedidos este mes</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $pedidos_pendientes; ?></div>
          <div class="stat-label">Pedidos pendientes</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fas fa-download"></i>
        </div>
        <div class="stat-info">
          <div class="stat-number"><?php echo $pedidos_entregados; ?></div>
          <div class="stat-label">Pedidos entregados</div>
        </div>
      </div>
    </div>

    <!-- ===== TARJETAS DE REPORTES ===== -->
    <div class="reports-grid">

      <!-- Reporte 1 - Ventas -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon purple">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Ventas</h3>
            <p>Ventas totales, tendencias y resumen por período</p>
          </div>
        </div>
        <div class="report-meta">
          <span>📊 Datos actualizados al día</span>
          <span class="badge-report">Diario</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_ventas.php" class="btn-pdf" >
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_ventas.php" class="btn-excel" >
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

      <!-- Reporte 2 - Productos -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon green">
            <i class="fas fa-box"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Productos</h3>
            <p>Inventario, stock, categorías y productos más vendidos</p>
          </div>
        </div>
        <div class="report-meta">
          <span>📦 <?php echo $total_productos; ?> productos registrados</span>
          <span class="badge-report">Semanal</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_productos.php" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_productos.php" class="btn-excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

      <!-- Reporte 3 - Clientes -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon orange">
            <i class="fas fa-users"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Clientes</h3>
            <p>Análisis de clientes, fidelización y compras recurrentes</p>
          </div>
        </div>
        <div class="report-meta">
          <span>👥 <?php echo $total_clientes; ?> clientes activos</span>
          <span class="badge-report">Mensual</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_clientes.php" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_clientes.php" class="btn-excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

      <!-- Reporte 4 - Usuarios -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon blue">
            <i class="fas fa-user-shield"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Usuarios</h3>
            <p>Usuarios del sistema, roles y actividad reciente</p>
          </div>
        </div>
        <div class="report-meta">
          <span>👤 <?php echo $total_usuarios; ?> administradores</span>
          <span class="badge-report">Mensual</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_usuarios.php" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_usuarios.php" class="btn-excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

      <!-- Reporte 5 - Categorías -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon pink">
            <i class="fas fa-tags"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Categorías</h3>
            <p>Análisis de categorías, productos por categoría y rendimiento</p>
          </div>
        </div>
        <div class="report-meta">
          <span>🏷️ <?php echo $total_categorias; ?> categorías activas</span>
          <span class="badge-report">Semanal</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_categorias.php" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_categorias.php" class="btn-excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

      <!-- Reporte 6 - Pedidos -->
      <div class="report-card">
        <div class="report-header">
          <div class="report-icon teal">
            <i class="fas fa-shopping-bag"></i>
          </div>
          <div class="report-info">
            <h3>Reporte de Pedidos</h3>
            <p>Pedidos completados, en proceso, entregas y tiempos</p>
          </div>
        </div>
        <div class="report-meta">
          <span>📦 <?php echo $pedidos_hoy; ?> pedidos hoy</span>
          <span class="badge-report">Diario</span>
        </div>
        <div class="report-actions">
          <a href="../reportes/pdf/reporte_pedidos.php" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
          <a href="../reportes/excel/reporte_pedidos.php" class="btn-excel">
            <i class="fas fa-file-excel"></i> Excel
          </a>
        </div>
      </div>

    </div>

    <!-- ===== REPORTES RÁPIDOS ===== -->
    <div class="quick-reports">
      <div class="quick-header">
        <h3><i class="fas fa-bolt"></i> Reportes rápidos</h3>
        <span class="badge-time">Última actualización: hoy 10:30 AM</span>
      </div>
      <div class="quick-grid">
        <div class="quick-item">
          <div class="quick-info">
            <i class="fas fa-chart-line"></i>
            <span>Ventas del día</span>
          </div>
          <div class="quick-btns">
            <a href="../reportes/pdf/reporte_ventas.php" class="btn-pdf-sm">
              <i class="fas fa-file-pdf"></i>
            </a>
            <a href="../reportes/excel/reporte_ventas.php" class="btn-excel-sm">
              <i class="fas fa-file-excel"></i>
            </a>
          </div>
        </div>

        <div class="quick-item">
          <div class="quick-info">
            <i class="fas fa-boxes"></i>
            <span>Stock bajo</span>
          </div>
          <div class="quick-btns">
            <a href="../reportes/pdf/reporte_productos.php" class="btn-pdf-sm">
              <i class="fas fa-file-pdf"></i>
            </a>
            <a href="../reportes/excel/reporte_productos.php" class="btn-excel-sm">
              <i class="fas fa-file-excel"></i>
            </a>
          </div>
        </div>

        <div class="quick-item">
          <div class="quick-info">
            <i class="fas fa-trophy"></i>
            <span>Top 10 productos</span>
          </div>
          <div class="quick-btns">
            <a href="../reportes/pdf/reporte_productos.php" class="btn-pdf-sm">
              <i class="fas fa-file-pdf"></i>
            </a>
            <a href="../reportes/excel/reporte_productos.php" class="btn-excel-sm">
              <i class="fas fa-file-excel"></i>
            </a>
          </div>
        </div>

        <div class="quick-item">
          <div class="quick-info">
            <i class="fas fa-users"></i>
            <span>Nuevos clientes</span>
          </div>
          <div class="quick-btns">
            <a href="../reportes/pdf/reporte_clientes.php" class="btn-pdf-sm">
              <i class="fas fa-file-pdf"></i>
            </a>
            <a href="../reportes/excel/reporte_clientes.php" class="btn-excel-sm">
              <i class="fas fa-file-excel"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>

    // ===== EFECTO DE CARGA INICIAL =====
    document.addEventListener('DOMContentLoaded', function() {
      // Simular carga inicial
      const cards = document.querySelectorAll('.report-card');
      cards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, 100);
        }, index * 100);
      });
    });

    // ===== POLYFILL PARA :contains EN QUERYSELECTOR =====
    // Función auxiliar para encontrar elementos por texto
    function findElementByText(selector, text) {
      const elements = document.querySelectorAll(selector);
      for (let el of elements) {
        if (el.textContent && el.textContent.includes(text)) {
          return el;
        }
      }
      return null;
    }

    console.log('📊 Gestión de reportes cargada exitosamente');
  </script>
<?php include_once "../templates/footer.php"; ?>