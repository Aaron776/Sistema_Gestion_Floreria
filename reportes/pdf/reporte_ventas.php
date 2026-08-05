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
$resumen = $conexion->query("SELECT COUNT(*) AS total_pedidos, COALESCE(SUM(total), 0) AS ingresos FROM pedidos WHERE estado <> 'cancelado'")->fetch(PDO::FETCH_OBJ);
$resumen_pagos = $conexion->query("SELECT COUNT(*) AS total_pagos, COALESCE(SUM(monto), 0) AS recaudado FROM pagos WHERE estado = 'pagado'")->fetch(PDO::FETCH_OBJ);
$por_metodo = $conexion->query("SELECT metodo_pago, COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total FROM pagos WHERE estado = 'pagado' GROUP BY metodo_pago ORDER BY cantidad DESC")->fetchAll(PDO::FETCH_OBJ);

// ===== DETALLE =====
$ventas = $conexion->query("
    SELECT p.id_pedido, c.nombre AS cliente, p.fecha_pedido, p.total, p.estado,
           pg.metodo_pago, pg.estado AS estado_pago
    FROM pedidos p
    JOIN usuarios c ON c.id_usuario = p.id_cliente
    LEFT JOIN pagos pg ON pg.id_pedido = p.id_pedido
    ORDER BY p.fecha_pedido DESC
")->fetchAll(PDO::FETCH_OBJ);

$etiquetas_estado = ['pendiente' => 'Pendiente', 'preparando' => 'Preparando', 'listo' => 'Listo', 'en_camino' => 'En camino', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
$etiquetas_metodo = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'paypal' => 'PayPal'];

$filas_metodo = '';
foreach ($por_metodo as $m) {
    $filas_metodo .= '
            <tr>
                <td>' . ($etiquetas_metodo[$m->metodo_pago] ?? ucfirst($m->metodo_pago)) . '</td>
                <td class="td-num">' . (int) $m->cantidad . '</td>
                <td class="td-num">$' . number_format((float) $m->total, 2) . '</td>
            </tr>';
}
if ($filas_metodo === '') {
    $filas_metodo = '
            <tr><td colspan="3" class="td-empty">Sin pagos registrados.</td></tr>';
}

$filas_ventas = '';
foreach ($ventas as $v) {
    $filas_ventas .= '
            <tr>
                <td class="td-num">#' . $v->id_pedido . '</td>
                <td>' . htmlspecialchars($v->cliente) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($v->fecha_pedido)) . '</td>
                <td class="td-num">$' . number_format((float) $v->total, 2) . '</td>
                <td>' . ($etiquetas_estado[$v->estado] ?? $v->estado) . '</td>
                <td>' . ($v->metodo_pago ? ($etiquetas_metodo[$v->metodo_pago] ?? ucfirst($v->metodo_pago)) : '—') . '</td>
                <td>' . ($v->estado_pago ? ucfirst($v->estado_pago) : '—') . '</td>
            </tr>';
}
if ($filas_ventas === '') {
    $filas_ventas = '
            <tr><td colspan="7" class="td-empty">No hay ventas registradas.</td></tr>';
}

$fecha_generacion = date('d/m/Y H:i');
$ingresos_fmt = '$' . number_format((float) $resumen->ingresos, 2);
$recaudado_fmt = '$' . number_format((float) $resumen_pagos->recaudado, 2);

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
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
        .footer .thanks { font-size: 9px; color: #c87a5a; font-weight: 700; margin-bottom: 4px; }
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
                <div class="title-report">REPORTE DE VENTAS</div>
                <div class="meta-report">Generado el $fecha_generacion</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="box-label">Pedidos registrados</div><div class="box-value">{$resumen->total_pedidos}</div></div></td>
            <td><div class="box"><div class="box-label">Ingresos (pedidos)</div><div class="box-value">$ingresos_fmt</div></div></td>
            <td><div class="box"><div class="box-label">Pagos confirmados</div><div class="box-value">{$resumen_pagos->total_pagos} / $recaudado_fmt</div></div></td>
        </tr>
    </table>

    <div class="section-title">Ventas por método de pago</div>
    <table class="data" style="width: 60%;">
        <thead>
            <tr>
                <th>Método</th>
                <th class="td-num">Cantidad</th>
                <th class="td-num">Total</th>
            </tr>
        </thead>
        <tbody>$filas_metodo
        </tbody>
    </table>

    <div class="section-title">Detalle de ventas</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 9%;">Pedido</th>
                <th>Cliente</th>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 12%;" class="td-num">Total</th>
                <th style="width: 12%;">Estado</th>
                <th style="width: 14%;">Método</th>
                <th style="width: 11%;">Pago</th>
            </tr>
        </thead>
        <tbody>$filas_ventas
        </tbody>
    </table>

    <div class="footer">
        <div class="thanks">Florería Pétalos</div>
        Reporte de ventas generado electrónicamente por el sistema · $fecha_generacion
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
$dompdf->stream('reporte_ventas.pdf', ['Attachment' => true]);
