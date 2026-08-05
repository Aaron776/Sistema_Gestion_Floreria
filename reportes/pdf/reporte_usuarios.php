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
$resumen = $conexion->query("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol ORDER BY total DESC")->fetchAll(PDO::FETCH_OBJ);
$activos = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE estado = 'activo'")->fetch(PDO::FETCH_OBJ);
$total_usuarios = $conexion->query("SELECT COUNT(*) AS total FROM usuarios")->fetch(PDO::FETCH_OBJ);

// ===== DETALLE =====
$usuarios = $conexion->query("
    SELECT u.id_usuario, u.nombre, u.email, u.telefono, u.rol, u.estado, u.ultimo_acceso, u.created_at
    FROM usuarios u
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_OBJ);

$etiquetas_rol = ['admin' => 'Administrador', 'vendedor' => 'Vendedor', 'repartidor' => 'Repartidor', 'cliente' => 'Cliente'];
$etiquetas_estado = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];

$filas = '';
foreach ($usuarios as $u) {
    $filas .= '
            <tr>
                <td class="td-num">' . $u->id_usuario . '</td>
                <td>' . htmlspecialchars($u->nombre) . '</td>
                <td>' . htmlspecialchars($u->email) . '</td>
                <td>' . htmlspecialchars($u->telefono ?: '—') . '</td>
                <td>' . ($etiquetas_rol[$u->rol] ?? ucfirst($u->rol)) . '</td>
                <td>' . ($etiquetas_estado[$u->estado] ?? ucfirst($u->estado)) . '</td>
                <td>' . ($u->ultimo_acceso ? date('d/m/Y H:i', strtotime($u->ultimo_acceso)) : 'Nunca') . '</td>
                <td>' . date('d/m/Y', strtotime($u->created_at)) . '</td>
            </tr>';
}
if ($filas === '') {
    $filas = '
            <tr><td colspan="8" class="td-empty">No hay usuarios registrados.</td></tr>';
}

$filas_resumen = '';
foreach ($resumen as $r) {
    $filas_resumen .= '
            <tr>
                <td>' . ($etiquetas_rol[$r->rol] ?? ucfirst($r->rol)) . '</td>
                <td class="td-num">' . (int) $r->total . '</td>
            </tr>';
}

$fecha_generacion = date('d/m/Y H:i');

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Usuarios</title>
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
                <div class="title-report">REPORTE DE USUARIOS</div>
                <div class="meta-report">Generado el $fecha_generacion</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="box-label">Usuarios totales</div><div class="box-value">{$total_usuarios->total}</div></div></td>
            <td><div class="box"><div class="box-label">Usuarios activos</div><div class="box-value">{$activos->total}</div></div></td>
            <td>
                <table class="data" style="width: 100%;">
                    <thead><tr><th>Rol</th><th class="td-num">Total</th></tr></thead>
                    <tbody>$filas_resumen</tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Listado de usuarios</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 7%;" class="td-num">ID</th>
                <th>Nombre</th>
                <th style="width: 18%;">Email</th>
                <th style="width: 12%;">Teléfono</th>
                <th style="width: 13%;">Rol</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 14%;">Último acceso</th>
                <th style="width: 11%;">Registrado</th>
            </tr>
        </thead>
        <tbody>$filas
        </tbody>
    </table>

    <div class="footer">
        Reporte de usuarios generado electrónicamente por el sistema · $fecha_generacion
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
$dompdf->stream('reporte_usuarios.pdf', ['Attachment' => true]);
