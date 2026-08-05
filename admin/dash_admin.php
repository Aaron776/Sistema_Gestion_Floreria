<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

$ventas_hoy = 0;
$ventas_ayer = 0;
$pedidos_hoy = 0;
$pedidos_semana = 0;
$pedidos_semana_anterior = 0;
$total_clientes = 0;
$clientes_nuevos = 0;
$ingresos_mes = 0;
$ingresos_mes_anterior = 0;
$pedidos_mes = 0;
$pedidos_entregados = 0;
$ventas_semana = array_fill(0, 7, 0);
$ultimos_pedidos = [];
$categorias_top = [];

try {
    // Ventas de hoy y de ayer (pedidos no cancelados)
    $sql = $conexion->prepare("SELECT COALESCE(SUM(total),0) AS total FROM pedidos WHERE estado <> 'cancelado' AND DATE(fecha_pedido) = CURDATE()");
    $sql->execute();
    $ventas_hoy = (float)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COALESCE(SUM(total),0) AS total FROM pedidos WHERE estado <> 'cancelado' AND DATE(fecha_pedido) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $sql->execute();
    $ventas_ayer = (float)$sql->fetchColumn();

    // Pedidos de hoy y de la semana (actual vs anterior)
    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado' AND DATE(fecha_pedido) = CURDATE()");
    $sql->execute();
    $pedidos_hoy = (int)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado' AND YEARWEEK(fecha_pedido, 1) = YEARWEEK(CURDATE(), 1)");
    $sql->execute();
    $pedidos_semana = (int)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado' AND YEARWEEK(fecha_pedido, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 7 DAY), 1)");
    $sql->execute();
    $pedidos_semana_anterior = (int)$sql->fetchColumn();

    // Clientes activos y nuevos del mes
    $sql = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND estado = 'activo'");
    $sql->execute();
    $total_clientes = (int)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $sql->execute();
    $clientes_nuevos = (int)$sql->fetchColumn();

    // Ingresos del mes (actual vs anterior)
    $sql = $conexion->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado <> 'cancelado' AND YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())");
    $sql->execute();
    $ingresos_mes = (float)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado <> 'cancelado' AND YEAR(fecha_pedido) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(fecha_pedido) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $sql->execute();
    $ingresos_mes_anterior = (float)$sql->fetchColumn();

    // Pedidos del mes y entregados (para metas)
    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado <> 'cancelado' AND YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())");
    $sql->execute();
    $pedidos_mes = (int)$sql->fetchColumn();

    $sql = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado' AND YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())");
    $sql->execute();
    $pedidos_entregados = (int)$sql->fetchColumn();

    // Ventas por día de la semana actual (Lun=1 ... Dom=7)
    $sql = $conexion->prepare("SELECT DAYOFWEEK(fecha_pedido) AS dia, COALESCE(SUM(total),0) AS total FROM pedidos WHERE estado <> 'cancelado' AND YEARWEEK(fecha_pedido, 1) = YEARWEEK(CURDATE(), 1) GROUP BY dia");
    $sql->execute();
    $ventas_semana_rows = $sql->fetchAll(PDO::FETCH_OBJ);
    $ventas_semana = array_fill(0, 7, 0);
    foreach ($ventas_semana_rows as $row) {
        $dia_semana = (int)$row->dia; // 1=Dom ... 7=Sáb
        $idx = $dia_semana === 1 ? 6 : $dia_semana - 2; // convertir a Lun=0
        if ($idx >= 0 && $idx < 7) {
            $ventas_semana[$idx] = (float)$row->total;
        }
    }

    // Últimos 4 pedidos con producto y cliente
    $sql = $conexion->prepare("SELECT p.id_pedido, p.estado, c.nombre AS cliente, dp.nombre_producto FROM pedidos p JOIN usuarios c ON p.id_cliente = c.id_usuario JOIN (SELECT id_pedido, MAX(producto.nombre) AS nombre_producto FROM detalle_pedido dp JOIN productos producto ON dp.id_producto = producto.id_producto GROUP BY id_pedido) dp ON p.id_pedido = dp.id_pedido ORDER BY p.fecha_pedido DESC LIMIT 4");
    $sql->execute();
    $ultimos_pedidos = $sql->fetchAll(PDO::FETCH_OBJ);

    // Categorías top por ingresos
    $sql = $conexion->prepare("SELECT cat.nombre AS categoria, COUNT(det.id_detalle) AS ventas, COALESCE(SUM(det.subtotal),0) AS total FROM detalle_pedido det JOIN productos prod ON det.id_producto = prod.id_producto JOIN categorias cat ON prod.id_categoria = cat.id_categoria GROUP BY cat.id_categoria, cat.nombre ORDER BY total DESC LIMIT 4");
    $sql->execute();
    $categorias_top = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error en dashboard admin: " . $e->getMessage());
}

// Helper: variación porcentual
function variacion_pct($actual, $anterior) {
    if ($anterior > 0) {
        return round(($actual - $anterior) / $anterior * 100);
    }
    return $actual > 0 ? 100 : 0;
}

// Helper: altura de barra semanal (máx 130px)
$max_venta_semana = max($ventas_semana);
function altura_barra($valor, $maximo) {
    if ($maximo <= 0) return 8;
    return max(8, (int)round(($valor / $maximo) * 130));
}

// Helper: estado a clase de badge
function clase_badge($estado) {
    switch ($estado) {
        case 'entregado': return '';
        case 'pendiente': return 'secondary';
        case 'cancelado': return 'secondary';
        default: return 'warning';
    }
}

include_once '../templates/header.php';
?>
  <!-- ===== KPI ===== -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Ventas hoy</span>
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="kpi-number">$<?php echo number_format($ventas_hoy, 0); ?></div>
                <?php $var_ventas = variacion_pct($ventas_hoy, $ventas_ayer); ?>
                <div class="kpi-trend <?php echo $var_ventas >= 0 ? 'trend-up' : 'trend-down'; ?>"><i class="fas fa-arrow-<?php echo $var_ventas >= 0 ? 'up' : 'down'; ?>"></i> <?php echo $var_ventas >= 0 ? '+' : ''; ?><?php echo $var_ventas; ?>% vs ayer</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Pedidos</span>
                    <i class="fas fa-box"></i>
                </div>
                <div class="kpi-number"><?php echo $pedidos_hoy; ?></div>
                <?php $var_pedidos = variacion_pct($pedidos_semana, $pedidos_semana_anterior); ?>
                <div class="kpi-trend <?php echo $var_pedidos >= 0 ? 'trend-up' : 'trend-down'; ?>"><i class="fas fa-arrow-<?php echo $var_pedidos >= 0 ? 'up' : 'down'; ?>"></i> <?php echo $var_pedidos >= 0 ? '+' : ''; ?><?php echo $var_pedidos; ?>% esta semana</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Clientes</span>
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="kpi-number"><?php echo $total_clientes; ?></div>
                <div class="kpi-trend trend-up"><i class="fas fa-arrow-up"></i> +<?php echo $clientes_nuevos; ?> nuevos</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Ingresos (mes)</span>
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="kpi-number">$<?php echo number_format($ingresos_mes, 0); ?></div>
                <?php $var_ingresos = variacion_pct($ingresos_mes, $ingresos_mes_anterior); ?>
                <div class="kpi-trend <?php echo $var_ingresos >= 0 ? 'trend-up' : 'trend-down'; ?>"><i class="fas fa-arrow-<?php echo $var_ingresos >= 0 ? 'up' : 'down'; ?>"></i> <?php echo $var_ingresos >= 0 ? '+' : ''; ?><?php echo $var_ingresos; ?>% vs mes pasado</div>
            </div>
        </div>

        <!-- ===== ROW DOBLE ===== -->
        <div class="row-doble">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar" style="color:#c87a5a; margin-right:6px;"></i>Ventas semanales</h3>
                    <a href="#">Ver más <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="chart-bars">
                    <div class="bar-item">
                        <div class="bar bar-1" style="height:<?php echo altura_barra($ventas_semana[0], $max_venta_semana); ?>px"></div><span class="bar-label">Lun</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-2" style="height:<?php echo altura_barra($ventas_semana[1], $max_venta_semana); ?>px"></div><span class="bar-label">Mar</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-3" style="height:<?php echo altura_barra($ventas_semana[2], $max_venta_semana); ?>px"></div><span class="bar-label">Mié</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-4" style="height:<?php echo altura_barra($ventas_semana[3], $max_venta_semana); ?>px"></div><span class="bar-label">Jue</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-5" style="height:<?php echo altura_barra($ventas_semana[4], $max_venta_semana); ?>px"></div><span class="bar-label">Vie</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-6" style="height:<?php echo altura_barra($ventas_semana[5], $max_venta_semana); ?>px"></div><span class="bar-label">Sáb</span>
                    </div>
                    <div class="bar-item">
                        <div class="bar bar-7" style="height:<?php echo altura_barra($ventas_semana[6], $max_venta_semana); ?>px"></div><span class="bar-label">Dom</span>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:0.6rem; font-size:0.7rem; color:#6b5d4f;">
                    <span><span style="display:inline-block; width:10px; height:10px; background:#c87a5a; border-radius:4px;"></span> Flores</span>
                    <span><span style="display:inline-block; width:10px; height:10px; background:#dbb7a4; border-radius:4px;"></span> Arreglos</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock" style="color:#c87a5a; margin-right:6px;"></i>Últimos pedidos</h3>
                    <a href="#">Todos</a>
                </div>
                <div class="table-wrap">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ultimos_pedidos) > 0): ?>
                                <?php foreach ($ultimos_pedidos as $pedido): ?>
                                    <tr>
                                        <td>
                                            <div class="product-thumb"><i class="fas fa-seedling"></i> <?php echo htmlspecialchars($pedido->nombre_producto ?? '—'); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($pedido->cliente); ?></td>
                                        <td><span class="status-badge <?php echo clase_badge($pedido->estado); ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($pedido->estado))); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; color:#7f6e5d;">No hay pedidos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== ROW TRIPLE ===== -->
        <div class="row-triple">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tags" style="color:#c87a5a; margin-right:6px;"></i>Categorías top</h3>
                </div>
                <?php if (count($categorias_top) > 0): ?>
                    <?php foreach ($categorias_top as $i => $categoria): ?>
                        <div class="category-item">
                            <div class="category-icon"><i class="fas <?php echo $i === 1 ? 'fa-leaf' : ($i === 3 ? 'fa-tree' : 'fa-seedling'); ?>"></i></div>
                            <div class="category-info">
                                <h4><?php echo htmlspecialchars($categoria->categoria); ?></h4><small><?php echo (int)$categoria->ventas; ?> ventas</small>
                            </div>
                            <div class="category-total">$<?php echo number_format((float)$categoria->total, 0); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="category-item">
                        <div class="category-info">
                            <h4>Sin ventas</h4><small>Aún no hay ventas registradas</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bullseye" style="color:#c87a5a; margin-right:6px;"></i>Metas mensuales</h3>
                </div>
                <?php
                $meta_ventas = 22000;
                $pct_ventas = $meta_ventas > 0 ? min(100, round($ingresos_mes / $meta_ventas * 100)) : 0;
                $meta_clientes = 20;
                $pct_clientes = $meta_clientes > 0 ? min(100, round($clientes_nuevos / $meta_clientes * 100)) : 0;
                $meta_pedidos = 60;
                $pct_pedidos = $meta_pedidos > 0 ? min(100, round($pedidos_mes / $meta_pedidos * 100)) : 0;
                $pct_entregados = $pedidos_mes > 0 ? min(100, round($pedidos_entregados / $pedidos_mes * 100)) : 0;
                ?>
                <div style="margin-bottom:0.8rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:500;"><span>Ventas</span><span><?php echo $pct_ventas; ?>%</span></div>
                    <div class="progress-mini">
                        <div class="fill" style="width:<?php echo $pct_ventas; ?>%"></div>
                    </div>
                </div>
                <div style="margin-bottom:0.8rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:500;"><span>Clientes nuevos</span><span><?php echo $pct_clientes; ?>%</span></div>
                    <div class="progress-mini">
                        <div class="fill" style="width:<?php echo $pct_clientes; ?>%"></div>
                    </div>
                </div>
                <div style="margin-bottom:0.8rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:500;"><span>Pedidos del mes</span><span><?php echo $pct_pedidos; ?>%</span></div>
                    <div class="progress-mini">
                        <div class="fill" style="width:<?php echo $pct_pedidos; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; font-weight:500;"><span>Pedidos entregados</span><span><?php echo $pct_entregados; ?>%</span></div>
                    <div class="progress-mini">
                        <div class="fill" style="width:<?php echo $pct_entregados; ?>%"></div>
                    </div>
                </div>
                <div style="margin-top:0.8rem; font-size:0.75rem; color:#6b5d4f;"><i class="far fa-calendar-alt"></i> Meta mensual: $<?php echo number_format($meta_ventas, 0); ?></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt" style="color:#c87a5a; margin-right:6px;"></i>Acciones rápidas</h3>
                </div>
                <div class="quick-action">
                    <div class="quick-btn"><i class="fas fa-plus-circle"></i> Nuevo pedido</div>
                    <div class="quick-btn"><i class="fas fa-user-plus"></i> Agregar cliente</div>
                    <div class="quick-btn"><i class="fas fa-file-invoice"></i> Generar factura</div>
                    <div class="quick-btn"><i class="fas fa-calendar-plus"></i> Programar entrega</div>
                </div>
            </div>
        </div>
<?php include_once '../templates/footer.php';?>