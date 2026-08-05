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
$resumen = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'cliente'")->fetch(PDO::FETCH_OBJ);
$recurrentes = $conexion->query("SELECT COUNT(*) AS total FROM (SELECT p.id_cliente FROM pedidos p GROUP BY p.id_cliente HAVING COUNT(*) >= 2) t")->fetch(PDO::FETCH_OBJ);
$ticket_promedio = $conexion->query("SELECT COALESCE(AVG(total), 0) AS promedio FROM pedidos WHERE estado <> 'cancelado'")->fetch(PDO::FETCH_OBJ);

// ===== DETALLE =====
$clientes = $conexion->query("
    SELECT u.id_usuario, u.nombre, u.email, u.telefono, u.direccion, u.created_at,
           COUNT(p.id_pedido) AS total_pedidos, COALESCE(SUM(p.total), 0) AS total_gastado
    FROM usuarios u
    LEFT JOIN pedidos p ON p.id_cliente = u.id_usuario
    WHERE u.rol = 'cliente'
    GROUP BY u.id_usuario
    ORDER BY total_gastado DESC, u.nombre ASC
")->fetchAll(PDO::FETCH_OBJ);

$filas = '';
foreach ($clientes as $c) {
    $filas .= '
            <tr>
                <td class="td-num">' . $c->id_usuario . '</td>
                <td>' . htmlspecialchars($c->nombre) . '</td>
                <td>' . htmlspecialchars($c->email) . '</td>
                <td>' . htmlspecialchars($c->telefono ?: '—') . '</td>
                <td class="td-num">' . (int) $c->total_pedidos . '</td>
                <td class="td-num">$' . number_format((float) $c->total_gastado, 2) . '</td>
                <td>' . date('d/m/Y', strtotime($c->created_at)) . '</td>
            </tr>';
}
if ($filas === '') {
    $filas = '
            <tr><td colspan="7" class="td-empty">No hay clientes registrados.</td></tr>';
}

$fecha_generacion = date('d/m/Y H:i');
$ticket_fmt = '$' . number_format((float) $ticket_promedio->promedio, 2);

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Clientes</title>
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
                <div class="title-report">REPORTE DE CLIENTES</div>
                <div class="meta-report">Generado el $fecha_generacion</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="box-label">Clientes registrados</div><div class="box-value">{$resumen->total}</div></div></td>
            <td><div class="box"><div class="box-label">Compras recurrentes (≥ 2)</div><div class="box-value">{$recurrentes->total}</div></div></td>
            <td><div class="box"><div class="box-label">Ticket promedio</div><div class="box-value">$ticket_fmt</div></div></td>
        </tr>
    </table>

    <div class="section-title">Listado de clientes</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 8%;" class="td-num">ID</th>
                <th>Cliente</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 13%;">Teléfono</th>
                <th style="width: 10%;" class="td-num">Pedidos</th>
                <th style="width: 14%;" class="td-num">Total gastado</th>
                <th style="width: 11%;">Registrado</th>
            </tr>
        </thead>
        <tbody>$filas
        </tbody>
    </table>

    <div class="footer">
        Reporte de clientes generado electrónicamente por el sistema · $fecha_generacion
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
$dompdf->stream('reporte_clientes.pdf', ['Attachment' => true]);
