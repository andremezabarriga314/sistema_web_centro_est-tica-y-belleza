
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

must_login();

$usuario = $_SESSION['user'];
$rol = $usuario['rol'] ?? '';
$esEspecialista = $rol === 'Especialista';
$esAdministrador = $rol === 'Administrador';
$esRecepcionista = $rol === 'Recepcionista';

function dashboardCount(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function dashboardImage(string $image, array $fallbacks, int $index): string
{
    $image = trim($image);

    if ($image !== '') {
        if (preg_match('~^https?://~i', $image)) {
            return $image;
        }

        // Si la base de datos guarda solamente el nombre del archivo.
        if (!str_contains($image, '/') && !str_contains($image, '\\')) {
            return 'assets/img/servicios/' . rawurlencode($image);
        }

        return $image;
    }

    return $fallbacks[$index % count($fallbacks)];
}

$nombreUsuario = $usuario['nombre'] ?? $usuario['usuario'] ?? 'Usuario';
$inicial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombreUsuario, 0, 1));

$fechaActual = date('d/m/Y');
$especialistaId = (int) ($usuario['especialista_id'] ?? 0);

$imagenes = [
    'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=900&q=85'
];

$clientes = 0;
$especialistas = 0;
$citasHoy = 0;
$ingresos = 0;
$citas = [];
$servicios = [];

try {
    $clientes = dashboardCount($pdo, "SELECT COUNT(*) FROM clientes");

    $especialistas = dashboardCount(
        $pdo,
        "SELECT COUNT(*) FROM especialistas WHERE estado = 'Activo'"
    );

    if ($esEspecialista) {
        $citasHoy = dashboardCount(
            $pdo,
            "SELECT COUNT(*) FROM citas
             WHERE fecha = CURDATE()
             AND especialista_id = ?",
            [$especialistaId]
        );

        $sqlCitas = "
            SELECT
                c.fecha,
                c.hora_inicio,
                c.estado,
                CONCAT(cl.nombres, ' ', cl.apellidos) AS cliente,
                s.nombre AS servicio,
                CONCAT(es.nombres, ' ', es.apellidos) AS especialista
            FROM citas c
            INNER JOIN clientes cl ON cl.id = c.cliente_id
            INNER JOIN servicios s ON s.id = c.servicio_id
            INNER JOIN especialistas es ON es.id = c.especialista_id
            WHERE c.fecha >= CURDATE()
              AND c.especialista_id = ?
              AND c.estado NOT IN ('Cancelada', 'No asistió')
            ORDER BY c.fecha ASC, c.hora_inicio ASC
            LIMIT 8
        ";

        $stmt = $pdo->prepare($sqlCitas);
        $stmt->execute([$especialistaId]);
        $citas = $stmt->fetchAll();
    } else {
        $citasHoy = dashboardCount(
            $pdo,
            "SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()"
        );

        $sqlCitas = "
            SELECT
                c.fecha,
                c.hora_inicio,
                c.estado,
                CONCAT(cl.nombres, ' ', cl.apellidos) AS cliente,
                s.nombre AS servicio,
                CONCAT(es.nombres, ' ', es.apellidos) AS especialista
            FROM citas c
            INNER JOIN clientes cl ON cl.id = c.cliente_id
            INNER JOIN servicios s ON s.id = c.servicio_id
            INNER JOIN especialistas es ON es.id = c.especialista_id
            WHERE c.fecha >= CURDATE()
              AND c.estado NOT IN ('Cancelada', 'No asistió')
            ORDER BY c.fecha ASC, c.hora_inicio ASC
            LIMIT 8
        ";

        $stmt = $pdo->query($sqlCitas);
        $citas = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    error_log('Dashboard: ' . $e->getMessage());
}

if (!$esEspecialista) {
    try {
        $ingresos = (float) $pdo->query("
            SELECT COALESCE(SUM(monto), 0)
            FROM pagos
            WHERE estado = 'Pagado'
              AND DATE(fecha_pago) = CURDATE()
        ")->fetchColumn();
    } catch (Throwable $e) {
        error_log('Dashboard ingresos: ' . $e->getMessage());
        $ingresos = 0;
    }
}

try {
    $servicios = $pdo->query("
        SELECT id, nombre, descripcion, precio, duracion_minutos, imagen
        FROM servicios
        WHERE estado = 'Activo'
        ORDER BY id DESC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    error_log('Dashboard servicios: ' . $e->getMessage());
}

$saludo = ((int) date('H') < 12)
    ? 'Buenos días'
    : (((int) date('H') < 19) ? 'Buenas tardes' : 'Buenas noches');

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#160f19">

<title>Bella Imagen | Panel principal</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #100b12;
    --sidebar: #160f19;
    --panel: #1d1520;
    --panel-light: #271c29;
    --wine: #713b56;
    --wine-light: #945b75;
    --rose: #dda6b9;
    --gold: #d8b77c;
    --text: #f8edf1;
    --muted: #a995a1;
    --line: rgba(255,255,255,.085);
    --green: #9bd1ad;
    --red: #ed9aa6;
}

* {
    box-sizing: border-box;
}

html {
    min-height: 100%;
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 80% 0%, rgba(113,59,86,.13), transparent 35%),
        var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
}

a {
    color: inherit;
    text-decoration: none;
}

button {
    font: inherit;
}

.app {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 255px;
    min-width: 255px;
    background: var(--sidebar);
    border-right: 1px solid var(--line);
    padding: 27px 17px 20px;
    display: flex;
    flex-direction: column;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 29px;
}

.brand-logo {
    width: 45px;
    height: 45px;
    flex-shrink: 0;
    border: 1px solid var(--gold);
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    background: linear-gradient(145deg, rgba(216,183,124,.12), transparent);
}

.brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 21px;
}

.brand-name small {
    display: block;
    color: var(--muted);
    font-family: 'DM Sans', sans-serif;
    font-size: 9px;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    margin-top: 4px;
}

.menu-label {
    padding: 15px 11px 9px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.7px;
    color: #806d7b;
    text-transform: uppercase;
}

.sidebar nav {
    display: flex;
    flex-direction: column;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 13px;
    margin: 3px 0;
    color: #bbaab6;
    font-size: 12px;
    border-radius: 10px;
    transition: .2s ease;
}

.nav-link:hover,
.nav-link.active {
    color: white;
    background: linear-gradient(100deg, #713b56, #48283e);
}

.nav-link.active {
    box-shadow: inset 3px 0 var(--gold);
}

.nav-icon {
    width: 22px;
    text-align: center;
    color: var(--gold);
    font-size: 17px;
}

.sidebar-bottom {
    margin-top: auto;
    border-top: 1px solid var(--line);
    padding-top: 17px;
}

.user-mini {
    padding: 11px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 9px;
    min-width: 0;
}

.avatar {
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: linear-gradient(135deg, #b77a96, #513044);
    color: white;
    font-weight: 700;
}

.user-mini strong {
    display: block;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.user-mini small {
    display: block;
    color: var(--muted);
    font-size: 10px;
    margin-top: 3px;
}

.logout {
    width: 100%;
    background: transparent;
    border: 1px solid var(--line);
    border-radius: 9px;
    color: #d4b5c3;
    padding: 11px;
    cursor: pointer;
    transition: .2s;
}

.logout:hover {
    background: #38202f;
    border-color: rgba(217,154,174,.3);
}

.main {
    flex: 1;
    min-width: 0;
    width: 100%;
    max-width: 1700px;
    margin: 0 auto;
    padding: 28px 35px 25px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
}

.breadcrumb {
    color: var(--muted);
    font-size: 12px;
}

.breadcrumb strong {
    color: var(--rose);
}

.top-right {
    display: flex;
    align-items: center;
    gap: 15px;
    color: var(--muted);
    font-size: 12px;
}

.date-chip {
    padding: 9px 14px;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: rgba(255,255,255,.025);
}

.hero {
    position: relative;
    min-height: 315px;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    align-items: center;
    padding: 42px;
    margin-bottom: 23px;
    background:
        linear-gradient(90deg, rgba(23,11,20,.97) 0%, rgba(48,22,39,.86) 47%, rgba(40,20,32,.2) 100%),
        url('https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1800&q=90') center 43%/cover;
    border: 1px solid rgba(216,183,124,.22);
    box-shadow: 0 18px 55px rgba(0,0,0,.18);
}

.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, rgba(16,11,18,.2), transparent 60%);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 590px;
}

.eyebrow {
    color: var(--gold);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(34px, 4vw, 49px);
    font-weight: 500;
    line-height: 1.15;
    margin: 15px 0;
}

.hero h1 span {
    color: #e5b4c5;
}

.hero p {
    max-width: 450px;
    color: #dfcbd5;
    font-size: 13px;
    line-height: 1.8;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 11px;
    margin-top: 23px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 9px;
    padding: 12px 18px;
    font-size: 12px;
    font-weight: 700;
    transition: .2s ease;
}

.btn-primary {
    color: #281522;
    background: linear-gradient(110deg, #e7c18a, #d9a9b9);
    box-shadow: 0 7px 20px rgba(217,169,185,.15);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(217,169,185,.25);
}

.btn-outline {
    color: white;
    border: 1px solid rgba(255,255,255,.35);
    background: rgba(255,255,255,.06);
}

.btn-outline:hover {
    background: rgba(255,255,255,.13);
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
}

.stat {
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, #281c2b, #1a121d);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 19px;
    min-height: 116px;
    transition: .2s;
}

.stat:hover {
    transform: translateY(-3px);
    border-color: rgba(216,183,124,.25);
}

.stat::after {
    content: "";
    position: absolute;
    width: 85px;
    height: 85px;
    right: -35px;
    bottom: -40px;
    border-radius: 50%;
    background: rgba(217,154,174,.07);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 11px;
}

.stat-icon {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: rgba(217,154,174,.12);
    color: var(--rose);
    font-size: 16px;
}

.stat strong {
    display: block;
    font-size: 27px;
    margin-top: 9px;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin: 31px 0 17px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 500;
    margin: 0;
}

.section-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 11px;
}

.text-link {
    color: var(--gold);
    font-size: 12px;
    white-space: nowrap;
}

.text-link:hover {
    color: var(--rose);
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 17px;
}

.service-card {
    overflow: hidden;
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 14px;
    transition: .25s ease;
}

.service-card:hover {
    transform: translateY(-4px);
    border-color: rgba(216,183,124,.4);
    box-shadow: 0 12px 28px rgba(0,0,0,.18);
}

.service-image-wrap {
    position: relative;
    height: 180px;
    overflow: hidden;
    background: #2b1d2c;
}

.service-image {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: transform .45s ease;
}

.service-card:hover .service-image {
    transform: scale(1.05);
}

.service-info {
    padding: 15px;
}

.service-info h3 {
    margin: 0 0 7px;
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 500;
}

.service-info p {
    min-height: 35px;
    margin: 0;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.6;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    padding-top: 12px;
    border-top: 1px solid var(--line);
}

.price {
    color: var(--gold);
    font-size: 16px;
    font-weight: 700;
}

.duration {
    color: var(--muted);
    font-size: 10px;
    white-space: nowrap;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(240px, .8fr);
    gap: 17px;
    margin-top: 29px;
}

.panel {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 20px;
    min-width: 0;
}

.panel h3 {
    margin: 0;
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    font-weight: 500;
}

.panel-subtitle {
    color: var(--muted);
    font-size: 11px;
    margin: 6px 0 17px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 520px;
}

th {
    text-align: left;
    color: #a58c9b;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
}

td {
    padding: 13px 10px;
    font-size: 11px;
    color: #e4d5de;
    border-bottom: 1px solid var(--line);
}

tbody tr:hover {
    background: rgba(255,255,255,.025);
}

tr:last-child td {
    border-bottom: 0;
}

.status {
    display: inline-block;
    border-radius: 20px;
    padding: 5px 9px;
    font-size: 9px;
    background: rgba(216,183,124,.12);
    color: var(--gold);
    white-space: nowrap;
}

.status.confirmed,
.status.attended {
    background: rgba(112,174,139,.13);
    color: var(--green);
}

.status.cancelled {
    background: rgba(210,100,115,.13);
    color: var(--red);
}

.status.pending {
    background: rgba(216,183,124,.12);
    color: var(--gold);
}

.empty {
    padding: 25px 10px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

.promo {
    position: relative;
    min-height: 260px;
    overflow: hidden;
    border-radius: 14px;
    padding: 23px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background:
        linear-gradient(0deg, rgba(24,12,21,.97), rgba(24,12,21,.08)),
        url('https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=900&q=85') center/cover;
    border: 1px solid rgba(216,183,124,.15);
}

.promo span {
    color: var(--gold);
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.promo h3 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 500;
    margin: 9px 0;
}

.promo p {
    color: #d9c6d1;
    font-size: 11px;
    line-height: 1.6;
    margin: 0 0 15px;
}

.promo .btn {
    align-self: flex-start;
}

.footer {
    color: #776473;
    text-align: center;
    padding: 30px 10px 5px;
    font-size: 10px;
    line-height: 1.8;
}

@media(max-width:1100px) {
    .sidebar {
        width: 220px;
        min-width: 220px;
    }

    .main {
        padding: 24px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .service-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width:760px) {
    .app {
        display: block;
    }

    .sidebar {
        width: 100%;
        min-width: 0;
        padding: 12px;
        border-right: 0;
        border-bottom: 1px solid var(--line);
    }

    .brand {
        padding: 0 5px 12px;
    }

    .menu-label {
        display: none;
    }

    .sidebar nav {
        flex-direction: row;
        overflow-x: auto;
        gap: 5px;
        padding-bottom: 5px;
    }

    .nav-link {
        white-space: nowrap;
        flex-shrink: 0;
        margin: 0;
        padding: 10px;
        font-size: 11px;
    }

    .sidebar-bottom {
        display: none;
    }

    .main {
        padding: 18px 14px;
    }

    .topbar {
        margin-bottom: 17px;
    }

    .hero {
        padding: 28px 22px;
        min-height: 310px;
    }

    .hero h1 {
        font-size: 35px;
    }

    .stats {
        gap: 9px;
    }

    .stat {
        padding: 13px;
        min-height: 100px;
    }

    .stat strong {
        font-size: 23px;
    }

    .service-grid {
        gap: 10px;
    }

    .service-image-wrap {
        height: 140px;
    }

    .service-info {
        padding: 11px;
    }

    .service-info h3 {
        font-size: 15px;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .date-chip {
        display: none;
    }
}

@media(max-width:420px) {
    .service-grid {
        grid-template-columns: 1fr;
    }

    .service-image-wrap {
        height: 190px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .top-right {
        font-size: 10px;
    }

    .section-heading h2 {
        font-size: 21px;
    }
}

@media print {
    .sidebar,
    .hero-actions,
    .promo {
        display: none !important;
    }

    .app {
        display: block;
    }

    .main {
        max-width: none;
        padding: 0;
    }

    body {
        background: white;
        color: black;
    }

    .panel,
    .stat,
    .service-card {
        background: white;
        color: black;
        border: 1px solid #ddd;
    }
}
</style>
</head>

<body>

<div class="app">

<aside class="sidebar">

    <div class="brand">
        <div class="brand-logo">BI</div>
        <div class="brand-name">
            Bella Imagen
            <small>Centro de estética integral</small>
        </div>
    </div>

    <div class="menu-label">Principal</div>

    <nav>
        <a href="dashboard.php" class="nav-link active">
            <span class="nav-icon">⌂</span>
            Panel principal
        </a>

        <?php if ($esAdministrador || $esRecepcionista): ?>
        <a href="modulo.php?m=clientes" class="nav-link">
            <span class="nav-icon">♙</span>
            Clientes
        </a>
        <?php endif; ?>

        <a href="agenda.php" class="nav-link">
            <span class="nav-icon">▦</span>
            Agenda de citas
        </a>

        <?php if ($esAdministrador || $esEspecialista): ?>
        <a href="atenciones.php" class="nav-link">
            <span class="nav-icon">✧</span>
            Atenciones
        </a>
        <?php endif; ?>

        <div class="menu-label">Gestión</div>

        <?php if ($esAdministrador): ?>
        <a href="catalogo_servicios.php" class="nav-link">
            <span class="nav-icon">◇</span>
            Servicios
        </a>

        <a href="modulo.php?m=especialistas" class="nav-link">
            <span class="nav-icon">♧</span>
            Especialistas
        </a>

        <a href="inventario.php" class="nav-link">
            <span class="nav-icon">▤</span>
            Inventario
        </a>

        <a href="tratamientos_cliente.php" class="nav-link">
            <span class="nav-icon">♡</span>
            Tratamientos
        </a>
        <?php endif; ?>

        <?php if ($esAdministrador || $esRecepcionista): ?>
        <a href="pagos.php" class="nav-link">
            <span class="nav-icon">S/</span>
            Pagos
        </a>

        <a href="reportes.php" class="nav-link">
            <span class="nav-icon">▥</span>
            Reportes
        </a>
        <?php endif; ?>

        <?php if ($esAdministrador): ?>
        <a href="horarios.php" class="nav-link">
            <span class="nav-icon">◷</span>
            Horarios
        </a>

        <a href="asignaciones.php" class="nav-link">
            <span class="nav-icon">⚙</span>
            Asignaciones
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="user-mini">
            <div class="avatar"><?= e($inicial) ?></div>
            <div>
                <strong><?= e($nombreUsuario) ?></strong>
                <small><?= e($rol) ?></small>
            </div>
        </div>

        <form action="logout.php" method="POST">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <button type="submit" class="logout">↪ Cerrar sesión</button>
        </form>
    </div>

</aside>

<main class="main">

    <header class="topbar">
        <div class="breadcrumb">
            Bella Imagen / <strong>Panel principal</strong>
        </div>

        <div class="top-right">
            <span><?= e($saludo) ?>, <?= e($nombreUsuario) ?></span>
            <span class="date-chip"><?= e($fechaActual) ?></span>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <div class="eyebrow">Belleza · Bienestar · Confianza</div>

            <h1>
                La belleza comienza<br>
                cuando te <span>cuidas.</span>
            </h1>

            <p>
                Administra tu centro de estética desde un solo lugar.
                Organiza tus citas, servicios y especialistas, y brinda
                una experiencia excepcional a cada cliente.
            </p>

            <div class="hero-actions">
                <a href="agenda.php" class="btn btn-primary">
                    + Gestionar citas
                </a>

                <a href="catalogo_servicios.php" class="btn btn-outline">
                    Explorar servicios →
                </a>
            </div>
        </div>
    </section>

    <section class="stats">

        <article class="stat">
            <div class="stat-top">
                Clientes registrados
                <span class="stat-icon">♙</span>
            </div>
            <strong><?= number_format($clientes) ?></strong>
        </article>

        <article class="stat">
            <div class="stat-top">
                Especialistas activos
                <span class="stat-icon">✧</span>
            </div>
            <strong><?= number_format($especialistas) ?></strong>
        </article>

        <article class="stat">
            <div class="stat-top">
                Citas de hoy
                <span class="stat-icon">▦</span>
            </div>
            <strong><?= number_format($citasHoy) ?></strong>
        </article>

        <?php if (!$esEspecialista): ?>
        <article class="stat">
            <div class="stat-top">
                Ingresos de hoy
                <span class="stat-icon">S/</span>
            </div>
            <strong style="font-size:22px">
                <?= e(money($ingresos)) ?>
            </strong>
        </article>
        <?php endif; ?>

    </section>

    <div class="section-heading">
        <div>
            <h2>Nuestros servicios</h2>
            <p>Descubre los tratamientos y cuidados disponibles.</p>
        </div>

        <?php if ($esAdministrador): ?>
        <a href="catalogo_servicios.php" class="text-link">
            Administrar servicios →
        </a>
        <?php endif; ?>
    </div>

    <section class="service-grid">

        <?php if (count($servicios) > 0): ?>

            <?php foreach ($servicios as $i => $servicio): ?>

                <?php
                $imagenServicio = dashboardImage(
                    (string) ($servicio['imagen'] ?? ''),
                    $imagenes,
                    $i
                );

                $descripcion = trim((string) ($servicio['descripcion'] ?? ''));

                if ($descripcion === '') {
                    $descripcion = 'Servicio especializado para tu cuidado y bienestar.';
                }
                ?>

                <article class="service-card">

                    <div class="service-image-wrap">
                        <img
                            class="service-image"
                            src="<?= e($imagenServicio) ?>"
                            alt="<?= e($servicio['nombre']) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?= e($imagenes[$i % count($imagenes)]) ?>';"
                        >
                    </div>

                    <div class="service-info">
                        <h3><?= e($servicio['nombre']) ?></h3>

                        <p><?= e($descripcion) ?></p>

                        <div class="service-meta">
                            <span class="price">
                                <?= e(money($servicio['precio'])) ?>
                            </span>

                            <span class="duration">
                                ◷ <?= (int) $servicio['duracion_minutos'] ?> min
                            </span>
                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="panel">
                <h3>Catálogo de servicios</h3>
                <p class="panel-subtitle">
                    Todavía no hay servicios activos registrados.
                </p>

                <?php if ($esAdministrador): ?>
                <a href="catalogo_servicios.php" class="btn btn-primary">
                    Registrar servicios
                </a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </section>

    <section class="content-grid">

        <div class="panel">

            <h3>Próximas citas</h3>

            <p class="panel-subtitle">
                Seguimiento de las próximas atenciones programadas.
            </p>

            <div class="table-wrap">

                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($citas) > 0): ?>

                        <?php foreach ($citas as $cita): ?>

                            <?php
                            $estadoClass = match ($cita['estado']) {
                                'Confirmada' => 'confirmed',
                                'Atendida' => 'attended',
                                'Cancelada', 'No asistió' => 'cancelled',
                                'Pendiente' => 'pending',
                                default => ''
                            };
                            ?>

                            <tr>
                                <td>
                                    <?= e(date('d/m/Y', strtotime($cita['fecha']))) ?>
                                </td>

                                <td>
                                    <?= e(substr((string) $cita['hora_inicio'], 0, 5)) ?>
                                </td>

                                <td><?= e($cita['cliente']) ?></td>
                                <td><?= e($cita['servicio']) ?></td>

                                <td>
                                    <span class="status <?= e($estadoClass) ?>">
                                        <?= e($cita['estado']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                No hay próximas citas programadas.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </div>

        <div class="promo">
            <span>Bella Imagen</span>

            <h3>Un momento<br>para ti.</h3>

            <p>
                Tu bienestar también merece un espacio.
                Organiza tu próxima experiencia de belleza.
            </p>

            <a href="agenda.php" class="btn btn-primary">
                Ir a la agenda →
            </a>
        </div>

    </section>

    <footer class="footer">
        © <?= date('Y') ?> Bella Imagen · Centro de Estética Integral
        <br>
        Atención profesional y personalizada.
    </footer>

</main>

</div>

</body>
</html>
