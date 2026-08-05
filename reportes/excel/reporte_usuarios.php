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

$resumen = $conexion->query("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol ORDER BY total DESC")->fetchAll(PDO::FETCH_OBJ);
$activos = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE estado = 'activo'")->fetch(PDO::FETCH_OBJ);
$total_usuarios = $conexion->query("SELECT COUNT(*) AS total FROM usuarios")->fetch(PDO::FETCH_OBJ);
$usuarios = $conexion->query("
    SELECT u.id_usuario, u.nombre, u.email, u.telefono, u.rol, u.estado, u.ultimo_acceso, u.created_at
    FROM usuarios u
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_OBJ);

$etiquetas_rol = ['admin' => 'Administrador', 'vendedor' => 'Vendedor', 'repartidor' => 'Repartidor', 'cliente' => 'Cliente'];
$etiquetas_estado = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Usuarios');

$fecha_generacion = date('d/m/Y H:i');

$sheet->setCellValue('A1', 'REPORTE DE USUARIOS');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFC87A5A');
$sheet->setCellValue('A2', 'Florería Pétalos · Generado el ' . $fecha_generacion);
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF7F6E5D');

$sheet->setCellValue('A4', 'Resumen general');
$sheet->mergeCells('A4:H4');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$sheet->setCellValue('A5', 'Usuarios totales');
$sheet->getStyle('A5')->getFont()->setBold(true);
$sheet->setCellValue('B5', (int) $total_usuarios->total);
$sheet->setCellValue('C5', 'Usuarios activos');
$sheet->getStyle('C5')->getFont()->setBold(true);
$sheet->setCellValue('D5', (int) $activos->total);

$sheet->setCellValue('A6', 'Usuarios por rol');
$sheet->getStyle('A6')->getFont()->setBold(true);
$row = 6;
foreach ($resumen as $r) {
    $row++;
    $sheet->setCellValue('A' . $row, $etiquetas_rol[$r->rol] ?? ucfirst($r->rol));
    $sheet->setCellValue('B' . $row, (int) $r->total);
}

$row += 1;
$sheet->setCellValue('A' . $row, 'Listado de usuarios');
$sheet->mergeCells('A' . $row . ':H' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
$row++;

$headers = ['ID', 'Nombre', 'Email', 'Teléfono', 'Rol', 'Estado', 'Último acceso', 'Registrado'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $row, $h);
}
$sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':H' . $row)->getFont()->getColor()->setARGB('FFF4F1EB');
$sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D2A24');
$headerRow = $row;
$row++;

foreach ($usuarios as $u) {
    $sheet->setCellValue('A' . $row, (int) $u->id_usuario);
    $sheet->setCellValue('B' . $row, $u->nombre);
    $sheet->setCellValue('C' . $row, $u->email);
    $sheet->setCellValue('D' . $row, $u->telefono ?: '—');
    $sheet->setCellValue('E' . $row, $etiquetas_rol[$u->rol] ?? ucfirst($u->rol));
    $sheet->setCellValue('F' . $row, $etiquetas_estado[$u->estado] ?? ucfirst($u->estado));
    $sheet->setCellValue('G' . $row, $u->ultimo_acceso ? date('d/m/Y H:i', strtotime($u->ultimo_acceso)) : 'Nunca');
    $sheet->setCellValue('H' . $row, date('d/m/Y', strtotime($u->created_at)));
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

foreach (['A' => 8, 'B' => 28, 'C' => 30, 'D' => 14, 'E' => 16, 'F' => 12, 'G' => 18, 'H' => 12] as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_usuarios.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
