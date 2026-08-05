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
$resumen = $conexion->query("SELECT COUNT(*) AS total FROM productos")->fetch(PDO::FETCH_OBJ);
$stock_bajo = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE stock <= 5")->fetch(PDO::FETCH_OBJ);
$inventario = $conexion->query("SELECT COALESCE(SUM(precio * stock), 0) AS valor FROM productos")->fetch(PDO::FETCH_OBJ);

// ===== DETALLE =====
$productos = $conexion->query("
    SELECT pr.id_producto, pr.nombre, c.nombre AS categoria, pr.precio, pr.stock, pr.estado,
           COALESCE(SUM(dp.cantidad), 0) AS vendidos
    FROM productos pr
    JOIN categorias c ON c.id_categoria = pr.id_categoria
    LEFT JOIN detalle_pedido dp ON dp.id_producto = pr.id_producto
    GROUP BY pr.id_producto
    ORDER BY vendidos DESC, pr.nombre ASC
")->fetchAll(PDO::FETCH_OBJ);

$etiquetas_estado = ['activo' => 'Activo', 'inactivo' => 'Inactivo', 'agotado' => 'Agotado'];

$filas = '';
foreach ($productos as $p) {
    $filas .= '
            <tr>
                <td class="td-num">' . $p->id_producto . '</td>
                <td>' . htmlspecialchars($p->nombre) . '</td>
                <td>' . htmlspecialchars($p->categoria) . '</td>
                <td class="td-num">$' . number_format((float) $p->precio, 2) . '</td>
                <td class="td-num">' . (int) $p->stock . '</td>
                <td class="td-num">' . (int) $p->vendidos . '</td>
                <td>' . ($etiquetas_estado[$p->estado] ?? ucfirst($p->estado)) . '</td>
            </tr>';
}
if ($filas === '') {
    $filas = '
            <tr><td colspan="7" class="td-empty">No hay productos registrados.</td></tr>';
}

$fecha_generacion = date('d/m/Y H:i');
$inventario_fmt = '$' . number_format((float) $inventario->valor, 2);

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Productos</title>
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
                <div class="title-report">REPORTE DE PRODUCTOS</div>
                <div class="meta-report">Generado el $fecha_generacion</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="box-label">Productos registrados</div><div class="box-value">{$resumen->total}</div></div></td>
            <td><div class="box"><div class="box-label">Stock bajo (≤ 5)</div><div class="box-value">{$stock_bajo->total}</div></div></td>
            <td><div class="box"><div class="box-label">Valor del inventario</div><div class="box-value">$inventario_fmt</div></div></td>
        </tr>
    </table>

    <div class="section-title">Inventario de productos</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 8%;" class="td-num">ID</th>
                <th>Producto</th>
                <th style="width: 16%;">Categoría</th>
                <th style="width: 12%;" class="td-num">Precio</th>
                <th style="width: 9%;" class="td-num">Stock</th>
                <th style="width: 9%;" class="td-num">Vendidos</th>
                <th style="width: 11%;">Estado</th>
            </tr>
        </thead>
        <tbody>$filas
        </tbody>
    </table>

    <div class="footer" style="margin-top: 24px; padding-top: 10px; border-top: 1px solid #e8e1d7; text-align: center; font-size: 8px; color: #7f6e5d;">
        Reporte de productos generado electrónicamente por el sistema · $fecha_generacion
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
$dompdf->stream('reporte_productos.pdf', ['Attachment' => true]);
