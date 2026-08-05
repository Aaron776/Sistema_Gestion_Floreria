<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$resumen = $conexion->query("SELECT COUNT(*) AS total_pedidos, COALESCE(SUM(total), 0) AS ingresos FROM pedidos WHERE estado <> 'cancelado'")->fetch(PDO::FETCH_OBJ);
$resumen_pagos = $conexion->query("SELECT COUNT(*) AS total_pagos, COALESCE(SUM(monto), 0) AS recaudado FROM pagos WHERE estado = 'pagado'")->fetch(PDO::FETCH_OBJ);
$por_metodo = $conexion->query("SELECT metodo_pago, COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total FROM pagos WHERE estado = 'pagado' GROUP BY metodo_pago ORDER BY cantidad DESC")->fetchAll(PDO::FETCH_OBJ);
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ventas');

$fecha_generacion = date('d/m/Y H:i');

// ===== TÍTULO =====
$sheet->setCellValue('A1', 'REPORTE DE VENTAS');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

// ===== RESUMEN =====
$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:H4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$resumen_data = [
    ['Pedidos registrados', (int) $resumen->total_pedidos],
    ['Ingresos (pedidos)', '$' . number_format((float) $resumen->ingresos, 2)],
    ['Pagos confirmados', (int) $resumen_pagos->total_pagos],
    ['Recaudado confirmado', '$' . number_format((float) $resumen_pagos->recaudado, 2)],
];
$col = 1;
foreach ($resumen_data as $item) {
    $labelCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '5';
    $valueCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '5';
    $sheet->setCellValue($labelCell, $item[0]);
    $sheet->getStyle($labelCell)->getFont()->setBold(true);
    $sheet->setCellValue($valueCell, $item[1]);
    $sheet->getStyle($valueCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $col += 2;
}

// ===== VENTAS POR MÉTODO =====
$sheet->setCellValue('A7', 'Ventas por método de pago');
$sheet->mergeCells('A7:H7');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);

$sheet->setCellValue('A8', 'Método');
$sheet->setCellValue('B8', 'Cantidad');
$sheet->setCellValue('C8', 'Total');
$sheet->getStyle('A8:C8')->getFont()->setBold(true);
$sheet->getStyle('A8:C8')->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A8:C8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');

$row = 9;
foreach ($por_metodo as $m) {
    $sheet->setCellValue('A' . $row, $etiquetas_metodo[$m->metodo_pago] ?? ucfirst($m->metodo_pago));
    $sheet->setCellValue('B' . $row, (int) $m->cantidad);
    $sheet->setCellValue('C' . $row, (float) $m->total);
    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $row++;
}

// ===== DETALLE DE VENTAS =====
$row += 1;
$sheet->setCellValue('A' . $row, 'Detalle de ventas');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
$row++;

$headers = ['Pedido', 'Cliente', 'Fecha', 'Total', 'Estado', 'Método', 'Pago'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $row, $h);
}
$sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');
$headerRow = $row;
$row++;

foreach ($ventas as $v) {
    $sheet->setCellValue('A' . $row, '#' . $v->id_pedido);
    $sheet->setCellValue('B' . $row, $v->cliente);
    $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($v->fecha_pedido)));
    $sheet->setCellValue('D' . $row, (float) $v->total);
    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sheet->setCellValue('E' . $row, $etiquetas_estado[$v->estado] ?? $v->estado);
    $sheet->setCellValue('F' . $row, $v->metodo_pago ? ($etiquetas_metodo[$v->metodo_pago] ?? ucfirst($v->metodo_pago)) : '—');
    $sheet->setCellValue('G' . $row, $v->estado_pago ? ucfirst($v->estado_pago) : '—');
    $row++;
}
$lastRow = $row - 1;

// ===== ESTILOS DE TABLA =====
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FFE8E1D7'],
        ],
    ],
];
if ($lastRow >= $headerRow) {
    $sheet->getStyle('A' . $headerRow . ':G' . $lastRow)->applyFromArray($styleArray);
}

// ===== ANCHO DE COLUMNAS =====
foreach (['A' => 12, 'B' => 28, 'C' => 20, 'D' => 14, 'E' => 14, 'F' => 16, 'G' => 14] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_ventas.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
