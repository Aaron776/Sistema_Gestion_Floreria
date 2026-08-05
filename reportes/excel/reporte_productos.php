<?php
require_once '../../autorizacion/auth.php';
require_once '../../conexion/bd.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$resumen = $conexion->query("SELECT COUNT(*) AS total FROM productos")->fetch(PDO::FETCH_OBJ);
$stock_bajo = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE stock <= 5")->fetch(PDO::FETCH_OBJ);
$inventario = $conexion->query("SELECT COALESCE(SUM(precio * stock), 0) AS valor FROM productos")->fetch(PDO::FETCH_OBJ);
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Productos');

$fecha_generacion = date('d/m/Y H:i');

$sheet->setCellValue('A1', 'REPORTE DE PRODUCTOS');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:G2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:G4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$resumen_data = [
    ['Productos registrados', (int) $resumen->total],
    ['Stock bajo (≤ 5)', (int) $stock_bajo->total],
    ['Valor del inventario', '$' . number_format((float) $inventario->valor, 2)],
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

$sheet->setCellValue('A7', 'Inventario de productos');
$sheet->mergeCells('A7:G7');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);

$headers = ['ID', 'Producto', 'Categoría', 'Precio', 'Stock', 'Vendidos', 'Estado'];
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

foreach ($productos as $p) {
    $sheet->setCellValue('A' . $row, (int) $p->id_producto);
    $sheet->setCellValue('B' . $row, $p->nombre);
    $sheet->setCellValue('C' . $row, $p->categoria);
    $sheet->setCellValue('D' . $row, (float) $p->precio);
    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sheet->setCellValue('E' . $row, (int) $p->stock);
    $sheet->setCellValue('F' . $row, (int) $p->vendidos);
    $sheet->setCellValue('G' . $row, $etiquetas_estado[$p->estado] ?? ucfirst($p->estado));
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

foreach (['A' => 8, 'B' => 34, 'C' => 18, 'D' => 12, 'E' => 10, 'F' => 10, 'G' => 12] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_productos.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
