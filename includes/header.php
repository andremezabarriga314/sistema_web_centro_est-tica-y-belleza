
<?php
require_once __DIR__ . '/functions.php';

must_login();

$title = $title ?? 'Panel';
$msg = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$rol = $_SESSION['user']['rol'] ?? '';
$esAdmin = $rol === 'Administrador';
$esRecepcion = $rol === 'Recepcionista';
$esEspecialista = $rol === 'Especialista';

$puedeGestionarClientes = $esAdmin || $esRecepcion;
$puedeVerAtenciones = $esAdmin || $esEspecialista;
$puedeVerPagos = $esAdmin || $esRecepcion;
$puedeVerTratamientos = $esAdmin || $esRecepcion || $esEspecialista;

$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');
$moduloActual = $_GET['m'] ?? '';

$nombreUsuario = $_SESSION['user']['nombre'] ?? 'Usuario';
$iniciales = mb_strtoupper(
    mb_substr(trim($nombreUsuario), 0, 1, 'UTF-8'),
    'UTF-8'
);

$directorioBase = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$directorioBase = rtrim($directorioBase, '/');

if ($directorioBase === '/' || $directorioBase === '.') {
    $directorioBase = '';
}

$rutaCSS = $directorioBase . '/assets/css/style.css?v=20261101';
$rutaLuxuryCSS = $directorioBase . '/assets/css/bella-imagen.css?v=1';

if (!function_exists('menuActivo')) {
    function menuActivo(string $archivo, string $actual): string {
        return $archivo === $actual ? 'active' : '';
    }
}

if (!function_exists('menuModuloActivo')) {
    function menuModuloActivo(string $modulo, string $pagina, string $actual): string {
        return ($pagina === 'modulo.php' && $actual === $modulo) ? 'active' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10090f">

    <title><?= e($title) ?> | Bella Imagen</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e($rutaCSS) ?>">
    <link rel="stylesheet" href="<?= e($rutaLuxuryCSS) ?>">
</head>

<body class="app">

<header class="bi-topbar">
    <a class="bi-brand" href="dashboard.php">
        <span class="bi-brand-mark">BI</span>
        <span class="bi-brand-copy">
            <strong>BELLA IMAGEN</strong>
            <small>CENTRO DE ESTÉTICA INTEGRAL</small>
        </span>
    </a>

    <div class="bi-topbar-center">
        <span class="bi-status-dot"></span>
        <span>SISTEMA DE GESTIÓN</span>
    </div>

    <div class="bi-account">
        <div class="bi-account-avatar"><?= e($iniciales) ?></div>

        <div class="bi-account-data">
            <strong><?= e($nombreUsuario) ?></strong>
            <small><?= e($rol) ?></small>
        </div>

        <form method="POST" action="logout.php" class="logout-form">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <button type="submit" class="bi-logout" title="Cerrar sesión">
                <span>↗</span>
                <span class="bi-logout-text">Salir</span>
            </button>
        </form>
    </div>
</header>

<div class="bi-brand-line"></div>

<nav class="bi-quicknav">
    <a class="<?= menuActivo('dashboard.php', $paginaActual) ?>" href="dashboard.php">Inicio</a>

    <?php if ($puedeGestionarClientes): ?>
        <a class="<?= menuModuloActivo('clientes', $paginaActual, $moduloActual) ?>" href="modulo.php?m=clientes">Clientes</a>
        <a class="<?= menuActivo('catalogo_servicios.php', $paginaActual) ?>" href="catalogo_servicios.php">Catálogo</a>
    <?php endif; ?>

    <a class="<?= menuActivo('agenda.php', $paginaActual) ?>" href="agenda.php">Agenda</a>

    <?php if ($puedeVerAtenciones): ?>
        <a class="<?= menuActivo('atenciones.php', $paginaActual) ?>" href="atenciones.php">Atenciones</a>
    <?php endif; ?>

    <?php if ($puedeVerPagos): ?>
        <a class="<?= menuActivo('pagos.php', $paginaActual) ?>" href="pagos.php">Pagos</a>
        <a class="<?= menuActivo('reportes.php', $paginaActual) ?>" href="reportes.php">Reportes</a>
    <?php endif; ?>
</nav>

<div class="layout bi-layout">

    <aside class="side bi-sidebar">
        <div class="bi-sidebar-label">MENÚ PRINCIPAL</div>

        <a class="<?= menuActivo('dashboard.php', $paginaActual) ?>" href="dashboard.php">
            <span class="bi-menu-icon">⌂</span><span>Inicio</span>
        </a>

        <?php if ($puedeGestionarClientes): ?>
            <a class="<?= menuModuloActivo('clientes', $paginaActual, $moduloActual) ?>" href="modulo.php?m=clientes">
                <span class="bi-menu-icon">♙</span><span>Clientes</span>
            </a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <div class="bi-sidebar-label">GESTIÓN DEL CENTRO</div>

            <a class="<?= menuModuloActivo('especialistas', $paginaActual, $moduloActual) ?>" href="modulo.php?m=especialistas">
                <span class="bi-menu-icon">✧</span><span>Especialistas</span>
            </a>

            <a class="<?= menuModuloActivo('especialidades', $paginaActual, $moduloActual) ?>" href="modulo.php?m=especialidades">
                <span class="bi-menu-icon">✦</span><span>Especialidades</span>
            </a>

            <a class="<?= menuModuloActivo('servicios', $paginaActual, $moduloActual) ?>" href="modulo.php?m=servicios">
                <span class="bi-menu-icon">◇</span><span>Servicios</span>
            </a>

            <a class="<?= menuActivo('asignaciones.php', $paginaActual) ?>" href="asignaciones.php">
                <span class="bi-menu-icon">⇄</span><span>Asignar servicios</span>
            </a>

            <a class="<?= menuActivo('horarios.php', $paginaActual) ?>" href="horarios.php">
                <span class="bi-menu-icon">◷</span><span>Horarios</span>
            </a>
        <?php endif; ?>

        <div class="bi-sidebar-label">ATENCIÓN</div>

        <a class="<?= menuActivo('agenda.php', $paginaActual) ?>" href="agenda.php">
            <span class="bi-menu-icon">▦</span><span>Agenda de citas</span>
        </a>

        <?php if ($puedeVerAtenciones): ?>
            <a class="<?= menuActivo('atenciones.php', $paginaActual) ?>" href="atenciones.php">
                <span class="bi-menu-icon">♡</span><span>Atenciones</span>
            </a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <div class="bi-sidebar-label">OPERACIONES</div>

            <a class="<?= menuModuloActivo('productos', $paginaActual, $moduloActual) ?>" href="modulo.php?m=productos">
                <span class="bi-menu-icon">▣</span><span>Productos</span>
            </a>

            <a class="<?= menuActivo('inventario.php', $paginaActual) ?>" href="inventario.php">
                <span class="bi-menu-icon">▤</span><span>Inventario</span>
            </a>

            <a class="<?= menuModuloActivo('tratamientos', $paginaActual, $moduloActual) ?>" href="modulo.php?m=tratamientos">
                <span class="bi-menu-icon">✧</span><span>Tratamientos</span>
            </a>
        <?php endif; ?>

        <?php if ($puedeVerTratamientos): ?>
            <a class="<?= menuActivo('tratamientos_cliente.php', $paginaActual) ?>" href="tratamientos_cliente.php">
                <span class="bi-menu-icon">♡</span><span>Tratamientos de clientes</span>
            </a>
        <?php endif; ?>

        <?php if ($puedeVerPagos): ?>
            <div class="bi-sidebar-label">FINANZAS</div>

            <a class="<?= menuActivo('pagos.php', $paginaActual) ?>" href="pagos.php">
                <span class="bi-menu-icon">＄</span><span>Pagos y caja</span>
            </a>

            <a class="<?= menuActivo('reportes.php', $paginaActual) ?>" href="reportes.php">
                <span class="bi-menu-icon">▥</span><span>Reportes</span>
            </a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <div class="bi-sidebar-label">CONFIGURACIÓN</div>

            <a class="<?= menuModuloActivo('usuarios', $paginaActual, $moduloActual) ?>" href="modulo.php?m=usuarios">
                <span class="bi-menu-icon">♙</span><span>Usuarios y roles</span>
            </a>
        <?php endif; ?>

        <div class="bi-sidebar-promo">
            <div class="bi-promo-symbol">BI</div>
            <span>BELLA IMAGEN</span>
            <p>Tu belleza, nuestro compromiso.</p>
        </div>
    </aside>

    <main class="main bi-main">
        <?php if ($msg): ?>
            <div class="alert <?= e($msg['t']) ?>">
                <?= e($msg['m']) ?>
            </div>
        <?php endif; ?>
