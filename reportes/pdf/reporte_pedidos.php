<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

// ===== RESUMEN =====
$resumen = $conexion->query("SELECT COUNT(*) AS total FROM pedidos")->fetch(PDO::FETCH_OBJ);
$por_estado = $conexion->query("SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado ORDER BY total DESC")->fetchAll(PDO::FETCH_OBJ);

// ===== DETALLE =====
$pedidos = $conexion->query("
    SELECT p.id_pedido, c.nombre AS cliente, v.nombre AS vendedor, p.fecha_pedido, p.total, p.estado,
           r.nombre AS repartidor, e.estado AS estado_entrega, e.fecha_entrega
    FROM pedidos p
    JOIN usuarios c ON c.id_usuario = p.id_cliente
    LEFT JOIN usuarios v ON v.id_usuario = p.id_usuario
    LEFT JOIN entregas e ON e.id_pedido = p.id_pedido
    LEFT JOIN usuarios r ON r.id_usuario = e.id_repartidor
    ORDER BY p.fecha_pedido DESC
")->fetchAll(PDO::FETCH_OBJ);

$etiquetas_estado = ['pendiente' => 'Pendiente', 'preparando' => 'Preparando', 'listo' => 'Listo', 'en_camino' => 'En camino', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
$etiquetas_entrega = ['pendiente' => 'Pendiente', 'en_camino' => 'En camino', 'entregado' => 'Entregado'];

$filas = '';
foreach ($pedidos as $p) {
    $filas .= '
            <tr>
                <td class="td-num">#' . $p->id_pedido . '</td>
                <td>' . htmlspecialchars($p->cliente) . '</td>
                <td>' . htmlspecialchars($p->vendedor ?: '—') . '</td>
                <td>' . date('d/m/Y H:i', strtotime($p->fecha_pedido)) . '</td>
                <td class="td-num">$' . number_format((float) $p->total, 2) . '</td>
                <td>' . ($etiquetas_estado[$p->estado] ?? ucfirst($p->estado)) . '</td>
                <td>' . htmlspecialchars($p->repartidor ?: '—') . '</td>
                <td>' . ($p->estado_entrega ? ($etiquetas_entrega[$p->estado_entrega] ?? ucfirst($p->estado_entrega)) : '—') . '</td>
            </tr>';
}
if ($filas === '') {
    $filas = '
            <tr><td colspan="8" class="td-empty">No hay pedidos registrados.</td></tr>';
}

$filas_estado = '';
foreach ($por_estado as $e) {
    $filas_estado .= '
            <tr>
                <td>' . ($etiquetas_estado[$e->estado] ?? ucfirst($e->estado)) . '</td>
                <td class="td-num">' . (int) $e->total . '</td>
            </tr>';
}
if ($filas_estado === '') {
    $filas_estado = '
            <tr><td colspan="2" class="td-empty">Sin datos.</td></tr>';
}

$fecha_generacion = date('d/m/Y H:i');

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pedidos</title>
    <style>
        @page { margin: 16mm 15mm 18mm 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2d2a24; line-height: 1.5; }
        .topbar { position: fixed; top: 0; left: 0; right: 0; height: 6px; background: #c87a5a; }
        .header { width: 100%; border-bottom: 2px solid #c87a5a; padding-bottom: 12px; margin-bottom: 16px; }
        .brand-mark { width: 40px; height: 40px; border-radius: 50%; background: #2d2a24; color: #f4f1eb; text-align: center; font-size: 19px; font-weight: 700; }
        .brand-name { font-size: 18px; font-weight: 700; }
        .title-cell { text-align: right; }
        .title-report { font-size: 20px; font-weight: 700; color: #c87a5a; letter-spacing: 2px; margin: 0; }
        .meta-report { font-size: 9px; color: #7f6e5d; margin-top: 3px; }
        .summary { width: 100%; margin-bottom: 14px; }
        .summary td { width: 33%; }
        .box { border: 1px solid #e8e1d7; border-radius: 8px; padding: 8px 12px; background: #faf8f5; margin-right: 8px; }
        .box .box-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #7f6e5d; }
        .box .box-value { font-size: 15px; font-weight: 700; color: #2d2a24; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 14px 0 8px 0; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #2d2a24; color: #f4f1eb; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; padding: 7px 9px; text-align: left; }
        table.data td { padding: 7px 9px; border-bottom: 1px solid #eee7dd; font-size: 9.5px; }
        table.data tr.alt td { background: #f7f4ee; }
        .td-num { text-align: right; }
        .td-empty { text-align: center; color: #bfae9c; padding: 18px 9px !important; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e8e1d7; text-align: center; font-size: 8px; color: #7f6e5d; }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <table class="header">
        <tr>
            <td style="width: 55%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50px;"><div class="brand-mark">P</div></td>
                        <td>
                            <div class="brand-name">Florería Pétalos</div>
                            <div style="font-size: 8.5px; color: #7f6e5d;">Sistema de gestión de pedidos y ventas</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="title-cell">
                <div class="title-report">REPORTE DE PEDIDOS</div>
                <div class="meta-report">Generado el $fecha_generacion</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="box-label">Pedidos totales</div><div class="box-value">{$resumen->total}</div></div></td>
            <td>
                <table class="data" style="width: 100%;">
                    <thead><tr><th>Estado</th><th class="td-num">Total</th></tr></thead>
                    <tbody>$filas_estado</tbody>
                </table>
            </td>
            <td><div class="box"><div class="box-label">Nota</div><div class="box-value" style="font-size: 10px; font-weight: 500;">Resumen de pedidos por estado y detalle completo abajo.</div></div></td>
        </tr>
    </table>

    <div class="section-title">Detalle de pedidos</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 8%;" class="td-num">Pedido</th>
                <th>Cliente</th>
                <th style="width: 14%;">Vendedor</th>
                <th style="width: 14%;">Fecha</th>
                <th style="width: 11%;" class="td-num">Total</th>
                <th style="width: 12%;">Estado</th>
                <th style="width: 14%;">Repartidor</th>
                <th style="width: 11%;">Entrega</th>
            </tr>
        </thead>
        <tbody>$filas
        </tbody>
    </table>

    <div class="footer">
        Reporte de pedidos generado electrónicamente por el sistema · $fecha_generacion
    </div>
</body>
</html>
HTML;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('reporte_pedidos.pdf', ['Attachment' => true]);
