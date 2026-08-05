<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$resumen = $conexion->query("SELECT COUNT(*) AS total FROM pedidos")->fetch(PDO::FETCH_OBJ);
$por_estado = $conexion->query("SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado ORDER BY total DESC")->fetchAll(PDO::FETCH_OBJ);
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Pedidos');

$fecha_generacion = date('d/m/Y H:i');

$sheet->setCellValue('A1', 'REPORTE DE PEDIDOS');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:H4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$sheet->setCellValue('A5', 'Pedidos totales');
$sheet->getStyle('A5')->getFont()->setBold(true);
$sheet->setCellValue('B5', (int) $resumen->total);
$sheet->setCellValue('C5', 'Pedidos por estado');
$sheet->getStyle('C5')->getFont()->setBold(true);

$row = 5;
foreach ($por_estado as $e) {
    $row++;
    $sheet->setCellValue('C' . $row, $etiquetas_estado[$e->estado] ?? ucfirst($e->estado));
    $sheet->setCellValue('D' . $row, (int) $e->total);
}

$row += 1;
$sheet->setCellValue('A' . $row, 'Detalle de pedidos');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
$row++;

$headers = ['Pedido', 'Cliente', 'Vendedor', 'Fecha', 'Total', 'Estado', 'Repartidor', 'Entrega'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $row, $h);
}
$sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':H' . $row)->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');
$headerRow = $row;
$row++;

foreach ($pedidos as $p) {
    $sheet->setCellValue('A' . $row, '#' . $p->id_pedido);
    $sheet->setCellValue('B' . $row, $p->cliente);
    $sheet->setCellValue('C' . $row, $p->vendedor ?: '—');
    $sheet->setCellValue('D' . $row, date('d/m/Y H:i', strtotime($p->fecha_pedido)));
    $sheet->setCellValue('E' . $row, (float) $p->total);
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sheet->setCellValue('F' . $row, $etiquetas_estado[$p->estado] ?? ucfirst($p->estado));
    $sheet->setCellValue('G' . $row, $p->repartidor ?: '—');
    $sheet->setCellValue('H' . $row, $p->estado_entrega ? ($etiquetas_entrega[$p->estado_entrega] ?? ucfirst($p->estado_entrega)) : '—');
    $row++;
}
$lastRow = $row - 1;

$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FFE8E1D7'],
        ],
    ],
];
if ($lastRow >= $headerRow) {
    $sheet->getStyle('A' . $headerRow . ':H' . $lastRow)->applyFromArray($styleArray);
}

foreach (['A' => 10, 'B' => 26, 'C' => 20, 'D' => 18, 'E' => 12, 'F' => 14, 'G' => 20, 'H' => 12] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_pedidos.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
