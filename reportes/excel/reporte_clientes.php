<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$resumen = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'cliente'")->fetch(PDO::FETCH_OBJ);
$recurrentes = $conexion->query("SELECT COUNT(*) AS total FROM (SELECT p.id_cliente FROM pedidos p GROUP BY p.id_cliente HAVING COUNT(*) >= 2) t")->fetch(PDO::FETCH_OBJ);
$ticket_promedio = $conexion->query("SELECT COALESCE(AVG(total), 0) AS promedio FROM pedidos WHERE estado <> 'cancelado'")->fetch(PDO::FETCH_OBJ);
$clientes = $conexion->query("
    SELECT u.id_usuario, u.nombre, u.email, u.telefono, u.direccion, u.created_at,
           COUNT(p.id_pedido) AS total_pedidos, COALESCE(SUM(p.total), 0) AS total_gastado
    FROM usuarios u
    LEFT JOIN pedidos p ON p.id_cliente = u.id_usuario
    WHERE u.rol = 'cliente'
    GROUP BY u.id_usuario
    ORDER BY total_gastado DESC, u.nombre ASC
")->fetchAll(PDO::FETCH_OBJ);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Clientes');

$fecha_generacion = date('d/m/Y H:i');

$sheet->setCellValue('A1', 'REPORTE DE CLIENTES');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:G2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:G4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$resumen_data = [
    ['Clientes registrados', (int) $resumen->total],
    ['Compras recurrentes (≥ 2)', (int) $recurrentes->total],
    ['Ticket promedio', '$' . number_format((float) $ticket_promedio->promedio, 2)],
];
$col = 1;
foreach ($resumen_data as $item) {
    $labelCell = Coordinate::stringFromColumnIndex($col) . '5';
    $valueCell = Coordinate::stringFromColumnIndex($col + 1) . '5';
    $sheet->setCellValue($labelCell, $item[0]);
    $sheet->getStyle($labelCell)->getFont()->setBold(true);
    $sheet->setCellValue($valueCell, $item[1]);
    $col += 2;
}

$sheet->setCellValue('A7', 'Listado de clientes');
$sheet->mergeCells('A7:G7');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);

$headers = ['ID', 'Cliente', 'Email', 'Teléfono', 'Pedidos', 'Total gastado', 'Registrado'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
$row = 8;
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $row, $h);
}
$sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');
$headerRow = $row;
$row++;

foreach ($clientes as $c) {
    $sheet->setCellValue('A' . $row, (int) $c->id_usuario);
    $sheet->setCellValue('B' . $row, $c->nombre);
    $sheet->setCellValue('C' . $row, $c->email);
    $sheet->setCellValue('D' . $row, $c->telefono ?: '—');
    $sheet->setCellValue('E' . $row, (int) $c->total_pedidos);
    $sheet->setCellValue('F' . $row, (float) $c->total_gastado);
    $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($c->created_at)));
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
    $sheet->getStyle('A' . $headerRow . ':G' . $lastRow)->applyFromArray($styleArray);
}

foreach (['A' => 8, 'B' => 28, 'C' => 30, 'D' => 14, 'E' => 10, 'F' => 14, 'G' => 12] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_clientes.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
