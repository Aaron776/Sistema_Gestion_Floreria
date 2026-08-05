<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Solo vendedores y administradores generan facturas
if (!in_array($_SESSION['rol'], ['vendedor', 'admin'])) {
    http_response_code(403);
    die('No tienes permiso para generar facturas.');
}

if (!isset($_GET['id_pago'])) {
    http_response_code(400);
    die('Falta el parámetro del pago.');
}

$id_pago = (int) Crypto::decrypt($_GET['id_pago']);
if ($id_pago <= 0) {
    http_response_code(400);
    die('Pago no válido.');
}

// Datos de la factura: pago + pedido + cliente + vendedor
$sql = $conexion->prepare("
    SELECT
        p.id_pago, p.id_pedido, p.metodo_pago, p.referencia, p.monto, p.fecha_pago, p.estado AS estado_pago,
        pe.id_cliente, pe.id_usuario, pe.fecha_pedido, pe.direccion_entrega, pe.mensaje_tarjeta, pe.total, pe.estado AS estado_pedido,
        c.nombre AS cliente_nombre, c.email AS cliente_email, c.telefono AS cliente_telefono, c.direccion AS cliente_direccion,
        v.nombre AS vendedor_nombre
    FROM pagos p
    JOIN pedidos pe ON pe.id_pedido = p.id_pedido
    JOIN usuarios c ON c.id_usuario = pe.id_cliente
    LEFT JOIN usuarios v ON v.id_usuario = pe.id_usuario
    WHERE p.id_pago = :id_pago
");
$sql->bindValue(':id_pago', $id_pago, PDO::PARAM_INT);
$sql->execute();
$factura = $sql->fetch(PDO::FETCH_OBJ);

if (!$factura) {
    http_response_code(404);
    die('Factura no encontrada.');
}

// El vendedor solo puede ver facturas de los pedidos que registró
if ($_SESSION['rol'] === 'vendedor' && (int) $factura->id_usuario !== (int) $_SESSION['id_usuario']) {
    http_response_code(403);
    die('No tienes permiso para ver esta factura.');
}

// Detalle del pedido
$sql_items = $conexion->prepare("
    SELECT pr.nombre AS nombre, dp.cantidad AS cantidad, dp.precio_unitario AS precio_unitario, dp.subtotal AS subtotal
    FROM detalle_pedido dp
    JOIN productos pr ON pr.id_producto = dp.id_producto
    WHERE dp.id_pedido = :id_pedido
    ORDER BY dp.id_detalle ASC
");
$sql_items->bindValue(':id_pedido', $factura->id_pedido, PDO::PARAM_INT);
$sql_items->execute();
$items = $sql_items->fetchAll(PDO::FETCH_OBJ);

// Totales
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += (float) $it->subtotal;
}
$total = $factura->total > 0 ? (float) $factura->total : (float) $factura->monto;

// Etiquetas y colores por estado del pago
$etiquetas_estado = [
    'pagado' => 'Pagado',
    'pendiente' => 'Pendiente',
    'rechazado' => 'Rechazado',
];
$colores_estado = [
    'pagado' => ['#e2f0e6', '#4a7a5c'],
    'pendiente' => ['#f9ede0', '#a87d58'],
    'rechazado' => ['#fde8e4', '#b05b4b'],
];
$estado_pago = $etiquetas_estado[$factura->estado_pago] ?? ucfirst($factura->estado_pago);
$colores = $colores_estado[$factura->estado_pago] ?? ['#f1ebe4', '#6b5d4f'];

// Etiqueta del método de pago
$etiquetas_metodo = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'paypal' => 'PayPal',
];
$metodo = $etiquetas_metodo[$factura->metodo_pago] ?? ucfirst($factura->metodo_pago);

$etiquetas_pedido = [
    'pendiente' => 'Pendiente',
    'preparando' => 'Preparando',
    'listo' => 'Listo',
    'en_camino' => 'En camino',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
];
$estado_pedido = $etiquetas_pedido[$factura->estado_pedido] ?? ucfirst($factura->estado_pedido);

$numero_factura = 'FAC-' . str_pad($factura->id_pago, 6, '0', STR_PAD_LEFT);
$fecha_emision = date('d/m/Y', strtotime($factura->fecha_pago));
$hora_emision = date('H:i', strtotime($factura->fecha_pago));
$fecha_pedido = date('d/m/Y H:i', strtotime($factura->fecha_pedido));
$referencia = $factura->referencia ? htmlspecialchars($factura->referencia) : '—';
$vendedor_nombre = $factura->vendedor_nombre ? htmlspecialchars($factura->vendedor_nombre) : '—';
$mensaje_tarjeta = $factura->mensaje_tarjeta ? htmlspecialchars($factura->mensaje_tarjeta) : '';

$subtotal_fmt = '$' . number_format($subtotal, 2);
$total_fmt = '$' . number_format($total, 2);

$items_html = '';
foreach ($items as $i => $it) {
    $items_html .= '
            <tr>
                <td class="td-num">' . ($i + 1) . '</td>
                <td>' . htmlspecialchars($it->nombre) . '</td>
                <td class="td-num">' . (int) $it->cantidad . '</td>
                <td class="td-num">$' . number_format((float) $it->precio_unitario, 2) . '</td>
                <td class="td-num">$' . number_format((float) $it->subtotal, 2) . '</td>
            </tr>';
}

if ($items_html === '') {
    $items_html = '
            <tr>
                <td colspan="5" class="td-empty">No se registraron productos para este pedido.</td>
            </tr>';
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura $numero_factura</title>
    <style>
        @page { margin: 16mm 16mm 18mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2d2a24; line-height: 1.5; }

        /* ===== BARRA SUPERIOR ===== */
        .topbar { position: fixed; top: 0; left: 0; right: 0; height: 6px; background: #c87a5a; }

        /* ===== HEADER ===== */
        .header { width: 100%; border-bottom: 2px solid #c87a5a; padding-bottom: 12px; margin-bottom: 18px; }
        .brand-mark { width: 42px; height: 42px; border-radius: 50%; background: #2d2a24; color: #f4f1eb; text-align: center; font-size: 20px; font-weight: 700; }
        .brand-name { font-size: 19px; font-weight: 700; color: #2d2a24; }
        .brand-slogan { font-size: 8.5px; color: #7f6e5d; margin-top: 2px; }
        .title-cell { text-align: right; }
        .title-factura { font-size: 24px; font-weight: 700; color: #c87a5a; letter-spacing: 3px; margin: 0; }
        .meta-factura { font-size: 9px; color: #7f6e5d; margin-top: 3px; }

        /* ===== CAJAS DE INFORMACIÓN ===== */
        .info-box { width: 100%; border: 1px solid #e8e1d7; border-radius: 8px; padding: 10px 12px; background: #faf8f5; }
        .info-box h3 { margin: 0 0 6px 0; font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: #c87a5a; }
        .info-row { font-size: 9.5px; margin-bottom: 3px; }
        .info-row .label { color: #7f6e5d; }

        /* ===== TABLA DE PRODUCTOS ===== */
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #2d2a24; margin: 18px 0 8px 0; }
        .items { width: 100%; border-collapse: collapse; }
        .items th { background: #2d2a24; color: #f4f1eb; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; padding: 8px 10px; text-align: left; }
        .items td { padding: 8px 10px; border-bottom: 1px solid #eee7dd; font-size: 9.5px; }
        .items tr.alt td { background: #f7f4ee; }
        .td-num { text-align: right; }
        .td-empty { text-align: center; color: #bfae9c; padding: 20px 10px !important; }

        /* ===== TOTALES ===== */
        .totals { width: 45%; margin-left: 55%; margin-top: 12px; }
        .totals td { padding: 4px 0; font-size: 10px; }
        .totals .total-row td { border-top: 2px solid #c87a5a; padding-top: 8px; font-weight: 700; font-size: 13px; color: #2d2a24; }
        .totals .label { color: #7f6e5d; }
        .totals .value { text-align: right; }

        /* ===== PAGO ===== */
        .payment-box { width: 100%; border: 1px solid #e8e1d7; border-radius: 8px; padding: 12px; margin-top: 16px; background: #faf8f5; }
        .payment-box h3 { margin: 0 0 8px 0; font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: #c87a5a; }
        .payment-grid { width: 100%; }
        .payment-grid td { font-size: 9.5px; padding: 3px 0; }
        .payment-grid .label { color: #7f6e5d; width: 45%; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 30px; font-size: 9px; font-weight: 700; }

        /* ===== MENSAJE DE TARJETA ===== */
        .note-box { border: 1px dashed #c87a5a; border-radius: 8px; padding: 10px 12px; margin-top: 14px; background: #fdf6f1; }
        .note-box h3 { margin: 0 0 4px 0; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #c87a5a; }
        .note-box p { margin: 0; font-size: 9.5px; font-style: italic; color: #6b5d4f; }

        /* ===== FOOTER ===== */
        .footer { margin-top: 26px; padding-top: 10px; border-top: 1px solid #e8e1d7; text-align: center; font-size: 8px; color: #7f6e5d; }
        .footer .thanks { font-size: 10px; color: #c87a5a; font-weight: 700; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <table class="header">
        <tr>
            <td style="width: 58%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 52px;">
                            <div class="brand-mark">P</div>
                        </td>
                        <td>
                            <div class="brand-name">Florería Pétalos</div>
                            <div class="brand-slogan">Arreglos florales &amp; detalles para cada ocasión</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="title-cell">
                <div class="title-factura">FACTURA</div>
                <div class="meta-factura">N° $numero_factura</div>
                <div class="meta-factura">Emitida el $fecha_emision a las $hora_emision</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="info-box">
                    <h3>Datos del cliente</h3>
                    <div class="info-row"><span class="label">Nombre:</span> {$factura->cliente_nombre}</div>
                    <div class="info-row"><span class="label">Correo:</span> {$factura->cliente_email}</div>
                    <div class="info-row"><span class="label">Teléfono:</span> {$factura->cliente_telefono}</div>
                    <div class="info-row"><span class="label">Dirección:</span> {$factura->cliente_direccion}</div>
                    <div class="info-row"><span class="label">Entregar en:</span> {$factura->direccion_entrega}</div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="info-box">
                    <h3>Datos del pedido</h3>
                    <div class="info-row"><span class="label">N° pedido:</span> #{$factura->id_pedido}</div>
                    <div class="info-row"><span class="label">Fecha pedido:</span> $fecha_pedido</div>
                    <div class="info-row"><span class="label">Estado pedido:</span> $estado_pedido</div>
                    <div class="info-row"><span class="label">Atendido por:</span> $vendedor_nombre</div>
                    <div class="info-row"><span class="label">Método de pago:</span> $metodo</div>
                    <div class="info-row"><span class="label">Referencia:</span> $referencia</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Detalle del pedido</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th>Producto</th>
                <th style="width: 12%; text-align: right;">Cant.</th>
                <th style="width: 16%; text-align: right;">P. Unitario</th>
                <th style="width: 16%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>$items_html
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">$subtotal_fmt</td>
        </tr>
        <tr class="total-row">
            <td class="label">TOTAL A PAGAR</td>
            <td class="value">$total_fmt</td>
        </tr>
    </table>
HTML;

$html .= <<<HTML

    <div class="payment-box">
        <h3>Información del pago</h3>
        <table class="payment-grid">
            <tr>
                <td class="label">N° pago</td>
                <td>#{$factura->id_pago}</td>
                <td class="label">Método de pago</td>
                <td>$metodo</td>
            </tr>
            <tr>
                <td class="label">Fecha del pago</td>
                <td>$fecha_emision $hora_emision</td>
                <td class="label">Referencia</td>
                <td>$referencia</td>
            </tr>
            <tr>
                <td class="label">Estado del pago</td>
                <td><span class="badge" style="background: {$colores[0]}; color: {$colores[1]};">$estado_pago</span></td>
                <td class="label">Monto</td>
                <td>$total_fmt</td>
            </tr>
        </table>
    </div>
HTML;

if ($mensaje_tarjeta !== '') {
    $html .= <<<HTML

    <div class="note-box">
        <h3>Mensaje incluido en la tarjeta</h3>
        <p>"$mensaje_tarjeta"</p>
    </div>
HTML;
}

$html .= <<<HTML

    <div class="footer">
        <div class="thanks">¡Gracias por su compra!</div>
        Florería Pétalos · Pedidos por teléfono o WhatsApp · Pago en efectivo, tarjeta, transferencia o PayPal<br>
        Esta factura fue generada electrónicamente por el sistema y no requiere firma.
    </div>
</body>
</html>
HTML;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('factura_' . $id_pago . '.pdf', ["Attachment" => true]);
