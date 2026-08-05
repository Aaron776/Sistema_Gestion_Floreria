<?php
// Comprobar el estado actual de la sesión
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../conexion/session.php';
}

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit;
}

// Calcular la ruta base relativa hacia la raíz del proyecto
// Obtenemos el archivo que incluye este header
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
$including_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : __FILE__;
$including_dir = dirname($including_file);
$root_dir = dirname(__DIR__); // Directorio raíz del proyecto

// Normalizar las rutas para que funcionen en Windows y Linux
$including_dir = str_replace('\\', '/', $including_dir);
$root_dir = str_replace('\\', '/', $root_dir);

// Calcular la ruta relativa desde el directorio del archivo que incluye el header hacia la raíz
$relative_path = str_replace($root_dir, '', $including_dir);
$relative_path = trim($relative_path, '/');
$depth = !empty($relative_path) ? substr_count($relative_path, '/') + 1 : 0;

// Construir la ruta base: si está en bodeguero/ o cajero/ o admin/, necesitamos "../", si está en la raíz, ""
$base_url = $depth > 0 ? str_repeat('../', $depth) : '';

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si el enlace está activo
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}

// Función mejorada para verificar si una página está activa (soporta múltiples páginas por sección)
function isMenuActive($pages, $current_page)
{
    foreach ($pages as $page) {
        if (stripos($current_page, $page) !== false) {
            return 'active';
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florería Pétalos · Dashboard</title>
    <!-- Google Fonts & Font Awesome 6 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f1eb;
            color: #2d2a24;
            min-height: 100vh;
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1e1a16;
            color: #d4cdc4;
            padding: 1.5rem 0 2rem;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
            flex-shrink: 0;
            border-right: 1px solid #2d2822;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.5rem;
            color: #f0e8df;
            border-bottom: 1px solid #2d2822;
            margin-bottom: 1.5rem;
        }

        .sidebar-brand i {
            color: #c87a5a;
            font-size: 1.8rem;
        }

        .sidebar-brand span {
            letter-spacing: -0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 0.8rem;
            flex: 1;
        }

        .sidebar-menu li {
            margin-bottom: 0.2rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.7rem 1rem;
            border-radius: 12px;
            color: #b5aca2;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.15s;
        }

        .sidebar-menu a i {
            width: 22px;
            font-size: 1.1rem;
            text-align: center;
            color: #8a7b6b;
            transition: 0.15s;
        }

        .sidebar-menu a:hover {
            background: #2d2822;
            color: #f0e8df;
        }

        .sidebar-menu a:hover i {
            color: #c87a5a;
        }

        .sidebar-menu a.active {
            background: #2d2822;
            color: #f0e8df;
        }

        .sidebar-menu a.active i {
            color: #c87a5a;
        }

        .sidebar-menu .menu-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b5d4f;
            padding: 1rem 1rem 0.4rem;
            font-weight: 600;
        }

        /* ===== SUBMENÚ DESPLEGABLE ===== */
        .sidebar-menu .has-submenu > a {
            justify-content: space-between;
            cursor: pointer;
        }

        .sidebar-menu .submenu-arrow {
            font-size: 0.55rem;
            transition: transform 0.25s;
            opacity: 0.5;
        }

        .sidebar-menu .has-submenu.open > a .submenu-arrow {
            transform: rotate(180deg);
        }

        .sidebar-menu .submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .sidebar-menu .has-submenu.open > .submenu {
            max-height: 200px;
        }

        .sidebar-menu .submenu li a {
            padding-left: 2.8rem;
            font-size: 0.82rem;
            gap: 0.6rem;
        }

        .sidebar-menu .submenu li a i {
            font-size: 0.85rem;
            width: 18px;
        }

        .sidebar-menu .submenu li a.active {
            background: #2d2822;
            color: #f0e8df;
        }

        .sidebar-menu .submenu li a.active i {
            color: #c87a5a;
        }

        .sidebar-footer {
            padding: 1.2rem 1.5rem 0;
            border-top: 1px solid #2d2822;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #6b5d4f;
        }

        .sidebar-footer .user-card {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.5rem 0;
        }

        .sidebar-footer .avatar-small {
            width: 38px;
            height: 38px;
            border-radius: 38px;
            background: #3b322a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #d4cdc4;
            font-size: 0.8rem;
        }

        .sidebar-footer .user-info {
            flex: 1;
        }

        .sidebar-footer .user-info .name {
            font-weight: 600;
            color: #e8dfd6;
            font-size: 0.85rem;
        }

        .sidebar-footer .user-info .role {
            font-size: 0.7rem;
            color: #8a7b6b;
        }

        /* ===== CONTENIDO PRINCIPAL ===== */
        .main-content {
            flex: 1;
            padding: 1.2rem 1.8rem 2rem;
            min-width: 0;
            max-width: calc(100% - 260px);
        }

        /* ===== TOGGLE (móvil) ===== */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #2d2a24;
            cursor: pointer;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            background: #f8f5f0;
            border: 1px solid #ede8e0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 998;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            background: #ffffffdd;
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.85);
            padding: 0.7rem 1.8rem;
            border-radius: 60px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.04);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 245, 235, 0.6);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            color: #3b2e26;
        }

        .navbar-brand i {
            color: #c87a5a;
            font-size: 1.8rem;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
        }

        .navbar-actions .search-box {
            background: #f8f5f0;
            border-radius: 40px;
            padding: 0.4rem 1rem 0.4rem 1.4rem;
            display: flex;
            align-items: center;
            border: 1px solid #ede8e0;
            transition: 0.2s;
        }

        .navbar-actions .search-box:focus-within {
            border-color: #c87a5a;
            box-shadow: 0 0 0 3px rgba(200, 122, 90, 0.15);
        }

        .search-box input {
            border: none;
            background: transparent;
            padding: 0.4rem 0;
            font-size: 0.9rem;
            outline: none;
            min-width: 140px;
            font-family: 'Inter', sans-serif;
            color: #2d2a24;
        }

        .search-box i {
            color: #a28d7a;
            font-size: 0.9rem;
            margin-left: 0.3rem;
        }

        .navbar-icons {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .navbar-icons .icon-badge {
            background: #f8f5f0;
            width: 40px;
            height: 40px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b3d33;
            font-size: 1.2rem;
            position: relative;
            border: 1px solid #e8e0d6;
            cursor: default;
        }

        .navbar-icons .icon-badge .badge-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 10px;
            height: 10px;
            background: #d46a4a;
            border-radius: 20px;
            border: 2px solid #fff;
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 44px;
            background: #dccfc2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #3b2e26;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            cursor: default;
        }

        /* ===== KPI ===== */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .kpi-card {
            background: #ffffff;
            padding: 1.4rem 1rem 1.2rem 1.5rem;
            border-radius: 28px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1ebe4;
            transition: all 0.15s;
        }

        .kpi-card:hover {
            border-color: #dbcbc0;
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .kpi-header span {
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #7f6e5d;
        }

        .kpi-header i {
            font-size: 1.8rem;
            color: #c87a5a;
            opacity: 0.6;
        }

        .kpi-number {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #2d2a24;
        }

        .kpi-trend {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.2rem;
            color: #5a6b4b;
        }

        .trend-up {
            color: #4a7a5c;
        }

        .trend-down {
            color: #b05b4b;
        }

        /* ===== ROWS ===== */
        .row-doble {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1.8rem;
            margin-bottom: 2rem;
        }

        .card {
            background: #ffffff;
            border-radius: 28px;
            padding: 1.5rem 1.5rem 1.8rem;
            border: 1px solid #f1ebe4;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
        }

        /* ===== ALERTAS DEL SISTEMA ===== */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 16px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            border: 1px solid transparent;
            animation: alertFadeIn 0.3s ease;
        }

        .alert i {
            font-size: 1.1rem;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .alert-success {
            background: #e2f0e6;
            border-color: #c8dfd0;
            color: #3f6a4f;
        }

        .alert-success i {
            color: #3f6a4f;
        }

        .alert-danger {
            background: #fde8e4;
            border-color: #f5d0c8;
            color: #8f3d2f;
        }

        .alert-danger i {
            color: #b05b4b;
        }

        .alert ul {
            margin: 0;
            padding: 0;
            list-style: none;
            flex: 1;
        }

        .alert ul li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
        }

        .alert ul li:last-child {
            margin-bottom: 0;
        }

        @keyframes alertFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .card-header h3 {
            font-weight: 600;
            font-size: 1.05rem;
            color: #2d2a24;
        }

        .card-header a {
            color: #b07d64;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
        }

        .card-header a i {
            margin-left: 0.2rem;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 140px;
            margin-top: 0.8rem;
            gap: 0.4rem;
        }

        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .bar {
            width: 100%;
            max-width: 32px;
            background: #dccfc2;
            border-radius: 20px 20px 6px 6px;
            min-height: 8px;
        }

        .bar-1 {
            height: 68px;
            background: #c87a5a;
        }

        .bar-2 {
            height: 42px;
            background: #dbb7a4;
        }

        .bar-3 {
            height: 90px;
            background: #c87a5a;
        }

        .bar-4 {
            height: 58px;
            background: #dbb7a4;
        }

        .bar-5 {
            height: 110px;
            background: #c87a5a;
        }

        .bar-6 {
            height: 34px;
            background: #dbb7a4;
        }

        .bar-7 {
            height: 76px;
            background: #c87a5a;
        }

        .bar-label {
            font-size: 0.6rem;
            margin-top: 0.4rem;
            color: #6b5d4f;
            font-weight: 500;
            text-transform: uppercase;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .recent-table th {
            text-align: left;
            padding: 0.6rem 0.2rem 0.8rem 0;
            font-weight: 600;
            color: #6b5d4f;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #efe8e0;
        }

        .recent-table td {
            padding: 0.7rem 0.2rem 0.7rem 0;
            border-bottom: 1px solid #f5efe8;
            color: #2d2a24;
        }

        .recent-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            background: #e2f0e6;
            color: #3f6a4f;
            padding: 0.2rem 0.8rem;
            border-radius: 60px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.warning {
            background: #f9ede0;
            color: #a87d58;
        }

        .status-badge.secondary {
            background: #ede8e0;
            color: #6b5d4f;
        }

        .product-thumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-thumb i {
            font-size: 1.1rem;
            color: #c87a5a;
            width: 24px;
            text-align: center;
        }

        .row-triple {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.8rem;
            margin-bottom: 1rem;
        }

        .category-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f3ede6;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-icon {
            background: #f5f0ea;
            width: 40px;
            height: 40px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8b6f5a;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .category-info {
            flex: 1;
        }

        .category-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .category-info small {
            color: #7f6e5d;
            font-size: 0.7rem;
        }

        .category-total {
            font-weight: 600;
            color: #2d2a24;
        }

        .progress-mini {
            height: 4px;
            background: #ede8e0;
            border-radius: 8px;
            margin-top: 0.2rem;
            width: 100%;
        }

        .progress-mini .fill {
            height: 4px;
            background: #c87a5a;
            border-radius: 8px;
        }

        .fill-60 {
            width: 60%;
        }

        .fill-40 {
            width: 40%;
        }

        .fill-90 {
            width: 90%;
        }

        .fill-30 {
            width: 30%;
        }

        .fill-70 {
            width: 70%;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .quick-btn {
            background: #f8f5f0;
            padding: 0.8rem 1rem;
            border-radius: 60px;
            border: 1px solid #ede8e0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            color: #2d2a24;
            transition: 0.15s;
            cursor: default;
        }

        .quick-btn i {
            color: #c87a5a;
            width: 22px;
            font-size: 1rem;
        }

        .quick-btn:hover {
            background: #f1ebe4;
            border-color: #dccfc2;
        }

        .text-muted {
            color: #7f6e5d;
        }

        .mt-1 {
            margin-top: 0.6rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                width: 280px;
                height: 100vh;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open {
                display: block;
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .main-content {
                max-width: 100%;
                padding: 1rem 1.2rem 1.5rem;
            }

            .row-doble {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .row-triple {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.8rem;
                border-radius: 36px;
                padding: 0.9rem 1.2rem;
            }

            .navbar-actions {
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .search-box input {
                min-width: 100px;
                width: 100%;
            }

            .kpi-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
            }

            .row-triple {
                grid-template-columns: 1fr;
                gap: 1.2rem;
            }

            .card {
                padding: 1.2rem;
            }

            .main-content {
                padding: 0.8rem 0.8rem 1.2rem;
            }

            .sidebar {
                width: 260px;
            }
        }

        @media (max-width: 450px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .navbar-icons .icon-badge {
                width: 36px;
                height: 36px;
            }

            .avatar {
                width: 38px;
                height: 38px;
                font-size: 0.9rem;
            }
        }

        /* ===== OBJETIVO TÁCTIL (móvil) ===== */
        @media (max-width: 768px) {
            .btn-action {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</head>

<body>

    <!-- ===== OVERLAY (móvil) ===== -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-seedling"></i>
            <span>Pétalos</span>
        </div>
        <ul class="sidebar-menu">
            <?php if($_SESSION['rol'] == 'admin'):?>
                <li><a href="<?php echo $base_url; ?>admin/dash_admin.php" class="<?= isActive('dash_admin.php', $current_page) ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_url; ?>admin/gestion_usuarios.php" class="<?= isMenuActive(['gestion_usuarios.php','agregar_usuario.php','editar_usuario.php'], $current_page) ?>"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="<?php echo $base_url; ?>admin/gestion_categorias.php" class="<?= isMenuActive(['gestion_categorias.php','agregar_categoria.php','editar_categoria.php'], $current_page) ?>"><i class="fas fa-tags"></i> Categorías</a></li>
                <li><a href="<?php echo $base_url; ?>admin/gestion_productos.php" class="<?= isMenuActive(['gestion_productos.php','agregar_producto.php','editar_producto.php'], $current_page) ?>"><i class="fas fa-shopping-bag"></i> Productos</a></li>
                <li><a href="<?php echo $base_url; ?>admin/gestion_pedidos.php" class="<?= isMenuActive(['gestion_pedidos.php','detalle_pedido.php'], $current_page) ?>"><i class="fas fa-truck"></i> Pedidos</a></li>
                <li><a href="<?php echo $base_url; ?>admin/reportes.php" class="<?= isMenuActive(['reportes.php'], $current_page) ?>"><i class="fas fa-file-invoice"></i> Reportes</a></li>
            <?php endif;?>
            <?php if($_SESSION['rol'] == 'vendedor'):?>
                <li><a href="<?php echo $base_url; ?>vendedor/dash_vendedor.php" class="<?= isActive('dash_vendedor.php', $current_page) ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_url; ?>vendedor/gestion_pedidos.php" class="<?= isMenuActive(['gestion_pedidos.php','agregar_pedido.php','editar_pedido.php','asignar_repartidor.php'], $current_page) ?>"><i class="fas fa-truck"></i> Pedidos</a></li>
                <li><a href="<?php echo $base_url; ?>vendedor/gestion_pagos.php" class="<?= isMenuActive(['gestion_pagos.php','detalle_pedido.php'], $current_page) ?>"><i class="fas fa-file-invoice"></i> Pagos</a></li>
            <?php endif;?>
            <?php if($_SESSION['rol'] == 'repartidor'):?>
                <li><a href="<?php echo $base_url; ?>repartidor/dash_repartidor.php" class="<?= isActive('dash_repartidor.php', $current_page) ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li class="has-submenu <?= isMenuActive(['gestion_pedidos_listos.php','gestion_pedidos_en_camino.php','gestion_pedidos_entregados.php'], $current_page) ? 'active open' : '' ?>">
                    <a href="javascript:void(0)" class="submenu-toggle" onclick="toggleSubmenu(this.parentElement)">
                        <span><i class="fas fa-truck"></i> Pedidos</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="<?php echo $base_url; ?>repartidor/gestion_pedidos_listos.php" class="<?= isActive('gestion_pedidos_listos.php', $current_page) ?>"><i class="fas fa-clipboard-check"></i> Pedidos Listos</a></li>
                        <li><a href="<?php echo $base_url; ?>repartidor/gestion_pedidos_en_camino.php" class="<?= isActive('gestion_pedidos_en_camino.php', $current_page) ?>"><i class="fas fa-truck"></i> En Camino</a></li>
                        <li><a href="<?php echo $base_url; ?>repartidor/gestion_pedidos_entregados.php" class="<?= isActive('gestion_pedidos_entregados.php', $current_page) ?>"><i class="fas fa-check-double"></i> Pedidos Entregados</a></li>
                    </ul>
                </li>
            <?php endif;?>
            <li><a href="<?php echo $base_url; ?>controladores/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="avatar-small"><?php echo htmlspecialchars(substr($_SESSION['nombre'], 0, 2)); ?></div>
                <div class="user-info">
                    <div class="name"><?php echo htmlspecialchars(ucfirst($_SESSION['nombre'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($_SESSION['rol'])); ?></div>
                </div>
                <i class="fas fa-ellipsis-v" style="color:#6b5d4f; font-size:0.9rem;"></i>
            </div>
        </div>
    </aside>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="main-content">

        <?php include_once 'sidebar.php'; ?>