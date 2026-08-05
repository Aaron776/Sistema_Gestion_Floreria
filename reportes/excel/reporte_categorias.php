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

$resumen = $conexion->query("SELECT COUNT(*) AS total_categorias FROM categorias")->fetch(PDO::FETCH_OBJ);
$total_productos = $conexion->query("SELECT COUNT(*) AS total FROM productos")->fetch(PDO::FETCH_OBJ);
$categorias = $conexion->query("
    SELECT c.id_categoria, c.nombre, c.descripcion,
           COUNT(pr.id_producto) AS total_productos,
           COALESCE(SUM(pr.stock), 0) AS stock_total,
           COALESCE(SUM(pr.precio * pr.stock), 0) AS valor_inventario
    FROM categorias c
    LEFT JOIN productos pr ON pr.id_categoria = c.id_categoria
    GROUP BY c.id_categoria
    ORDER BY total_productos DESC, c.nombre ASC
")->fetchAll(PDO::FETCH_OBJ);

$promedio = $resumen->total_categorias > 0 ? $total_productos->total / $resumen->total_categorias : 0;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Categorías');

$fecha_generacion = date('d/m/Y H:i');

$sheet->setCellValue('A1', 'REPORTE DE CATEGORÍAS');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:F4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$resumen_data = [
    ['Categorías', (int) $resumen->total_categorias],
    ['Productos totales', (int) $total_productos->total],
    ['Promedio productos/categoría', number_format((float) $promedio, 1)],
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

$sheet->setCellValue('A7', 'Categorías y su rendimiento');
$sheet->mergeCells('A7:F7');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);

$headers = ['ID', 'Categoría', 'Descripción', 'Productos', 'Stock total', 'Valor inventario'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F'];
$row = 8;
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $row, $h);
}
$sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');
$headerRow = $row;
$row++;

foreach ($categorias as $cat) {
    $sheet->setCellValue('A' . $row, (int) $cat->id_categoria);
    $sheet->setCellValue('B' . $row, $cat->nombre);
    $sheet->setCellValue('C' . $row, $cat->descripcion ?: '—');
    $sheet->setCellValue('D' . $row, (int) $cat->total_productos);
    $sheet->setCellValue('E' . $row, (int) $cat->stock_total);
    $sheet->setCellValue('F' . $row, (float) $cat->valor_inventario);
    $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
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
    $sheet->getStyle('A' . $headerRow . ':F' . $lastRow)->applyFromArray($styleArray);
}

foreach (['A' => 8, 'B' => 26, 'C' => 40, 'D' => 12, 'E' => 12, 'F' => 16] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_categorias.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
