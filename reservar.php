
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = '';

if (empty($_SESSION['public_booking_token'])) {
    $_SESSION['public_booking_token'] = bin2hex(random_bytes(32));
}

$datos = [
    'nombres' => '',
    'apellidos' => '',
    'telefono' => '',
    'email' => '',
    'servicio_id' => '',
    'especialista_id' => '',
    'fecha' => '',
    'hora_inicio' => '',
    'observaciones' => ''
];

// Cargar servicios y especialistas activos.
$servicios = $pdo->query("
    SELECT id, nombre, precio, duracion_minutos
    FROM servicios
    WHERE estado = 'Activo'
    ORDER BY nombre
")->fetchAll();

$especialistas = $pdo->query("
    SELECT id, nombres, apellidos
    FROM especialistas
    WHERE estado = 'Activo'
    ORDER BY nombres, apellidos
")->fetchAll();

// Procesar reserva.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($datos as $campo => $valor) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }

    try {
        if (
            empty($_POST['token']) ||
            !hash_equals($_SESSION['public_booking_token'], $_POST['token'])
        ) {
            throw new Exception('La sesión del formulario expiró. Recarga la página.');
        }

        if (
            $datos['nombres'] === '' ||
            $datos['apellidos'] === '' ||
            $datos['telefono'] === '' ||
            $datos['servicio_id'] === '' ||
            $datos['especialista_id'] === '' ||
            $datos['fecha'] === '' ||
            $datos['hora_inicio'] === ''
        ) {
            throw new Exception('Completa todos los campos obligatorios.');
        }

        if (!preg_match('/^[0-9+\s()-]{7,20}$/', $datos['telefono'])) {
            throw new Exception('Ingresa un número de teléfono válido.');
        }

        if (
            $datos['email'] !== '' &&
            !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)
        ) {
            throw new Exception('Ingresa un correo electrónico válido.');
        }

        $fecha = DateTime::createFromFormat('!Y-m-d', $datos['fecha']);

        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha']) {
            throw new Exception('La fecha seleccionada no es válida.');
        }

        if ($datos['fecha'] < date('Y-m-d')) {
            throw new Exception('No puedes reservar una fecha pasada.');
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $datos['hora_inicio'])) {
            throw new Exception('Selecciona una hora válida.');
        }

        $horaInicio = $datos['hora_inicio'] . ':00';

        // Verificar servicio.
        $stmt = $pdo->prepare("
            SELECT id, nombre, duracion_minutos
            FROM servicios
            WHERE id = ? AND estado = 'Activo'
        ");
        $stmt->execute([(int)$datos['servicio_id']]);
        $servicio = $stmt->fetch();

        if (!$servicio) {
            throw new Exception('El servicio seleccionado no está disponible.');
        }

        // Verificar especialista.
        $stmt = $pdo->prepare("
            SELECT id
            FROM especialistas
            WHERE id = ? AND estado = 'Activo'
        ");
        $stmt->execute([(int)$datos['especialista_id']]);
        $especialista = $stmt->fetch();

        if (!$especialista) {
            throw new Exception('El especialista seleccionado no está disponible.');
        }

        // Verificar si tiene servicios asignados.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM especialista_servicio
            WHERE especialista_id = ?
        ");
        $stmt->execute([(int)$datos['especialista_id']]);
        $tieneServicios = (int)$stmt->fetchColumn();

        if ($tieneServicios > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM especialista_servicio
                WHERE especialista_id = ? AND servicio_id = ?
            ");

            $stmt->execute([
                (int)$datos['especialista_id'],
                (int)$datos['servicio_id']
            ]);

            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception('Este especialista no realiza el servicio seleccionado.');
            }
        }

        // Calcular hora final según duración.
        $duracion = max(1, (int)$servicio['duracion_minutos']);

        $inicio = new DateTime($horaInicio);
        $fin = clone $inicio;
        $fin->modify("+{$duracion} minutes");

        $horaFin = $fin->format('H:i:s');

        if (
            $horaFin <= $horaInicio ||
            $fin->format('Y-m-d') !== $fecha->format('Y-m-d')
        ) {
            throw new Exception('El horario seleccionado no es válido.');
        }

        if ($horaInicio < '07:00:00' || $horaFin > '21:00:00') {
            throw new Exception('El horario de atención es de 7:00 a. m. a 9:00 p. m.');
        }

        if (
            $datos['fecha'] === date('Y-m-d') &&
            $horaInicio <= date('H:i:s')
        ) {
            throw new Exception('Selecciona una hora posterior a la hora actual.');
        }

        $pdo->beginTransaction();

        // Bloquear al especialista mientras se verifica la disponibilidad.
        $stmt = $pdo->prepare("
            SELECT id
            FROM especialistas
            WHERE id = ? AND estado = 'Activo'
            FOR UPDATE
        ");

        $stmt->execute([(int)$datos['especialista_id']]);

        if (!$stmt->fetch()) {
            throw new Exception('El especialista ya no está disponible.');
        }

        // Comprobar cruces de horarios.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM citas
            WHERE especialista_id = ?
              AND fecha = ?
              AND estado NOT IN ('Cancelada', 'No asistió')
              AND hora_inicio < ?
              AND hora_fin > ?
        ");

        $stmt->execute([
            (int)$datos['especialista_id'],
            $datos['fecha'],
            $horaFin,
            $horaInicio
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception('Ese horario ya está ocupado. Selecciona otra hora.');
        }

        // Buscar cliente por teléfono.
        $stmt = $pdo->prepare("
            SELECT id
            FROM clientes
            WHERE telefono = ?
            LIMIT 1
        ");

        $stmt->execute([$datos['telefono']]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            $clienteId = (int)$cliente['id'];

            $stmt = $pdo->prepare("
                UPDATE clientes
                SET nombres = ?, apellidos = ?, email = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $datos['nombres'],
                $datos['apellidos'],
                $datos['email'] !== '' ? $datos['email'] : null,
                $clienteId
            ]);

        } else {
            $stmt = $pdo->prepare("
                INSERT INTO clientes (nombres, apellidos, telefono, email)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $datos['nombres'],
                $datos['apellidos'],
                $datos['telefono'],
                $datos['email'] !== '' ? $datos['email'] : null
            ]);

            $clienteId = (int)$pdo->lastInsertId();
        }

        // Crear cita pendiente.
        $stmt = $pdo->prepare("
            INSERT INTO citas (
                cliente_id,
                especialista_id,
                servicio_id,
                fecha,
                hora_inicio,
                hora_fin,
                estado,
                observaciones
            )
            VALUES (?, ?, ?, ?, ?, ?, 'Pendiente', ?)
        ");

        $stmt->execute([
            $clienteId,
            (int)$datos['especialista_id'],
            (int)$datos['servicio_id'],
            $datos['fecha'],
            $horaInicio,
            $horaFin,
            $datos['observaciones'] !== '' ? $datos['observaciones'] : null
        ]);

        $citaId = (int)$pdo->lastInsertId();

        $pdo->commit();

        $_SESSION['public_booking_token'] = bin2hex(random_bytes(32));

        $success = '¡Tu solicitud de reserva fue registrada! Tu código de reserva es BI-' .
            str_pad((string)$citaId, 5, '0', STR_PAD_LEFT) .
            '. Nuestro equipo confirmará tu cita.';

        $datos = array_fill_keys(array_keys($datos), '');

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e instanceof PDOException
            ? 'No se pudo registrar la reserva. Verifica los datos e inténtalo nuevamente.'
            : $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reservar | Bella Imagen</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg: #120b11;
            --panel: #21151f;
            --rose: #d9a8b8;
            --gold: #d5b36b;
            --text: #f8edf1;
            --muted: #ad98a4;
            --border: rgba(224,184,198,.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 15% 10%, rgba(120,60,88,.16), transparent 40%),
                var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            height: 82px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: rgba(18,11,17,.94);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 29px;
            color: var(--rose);
        }

        .logo span {
            color: var(--gold);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
            color: #d5c1ca;
            font-size: 13px;
        }

        .nav-right a:hover {
            color: var(--gold);
        }

        .nav-login {
            border: 1px solid rgba(213,179,107,.5);
            padding: 11px 19px;
            border-radius: 30px;
        }

        .hero {
            min-height: 360px;
            display: flex;
            align-items: center;
            padding: 65px 8%;
            background:
                linear-gradient(90deg, rgba(18,11,17,.97) 5%, rgba(18,11,17,.75) 48%, rgba(18,11,17,.2)),
                url('https://images.unsplash.com/photo-1560750588-73207b1ef5b8?auto=format&fit=crop&w=2000&q=90') center 45%/cover;
            border-bottom: 1px solid var(--border);
        }

        .hero-content {
            max-width: 650px;
        }

        .eyebrow {
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(43px, 6vw, 70px);
            font-weight: 500;
            line-height: 1.08;
            margin: 20px 0;
        }

        .hero h1 span {
            color: var(--rose);
        }

        .hero p {
            color: #e2cfd7;
            font-size: 15px;
            line-height: 1.8;
            max-width: 470px;
        }

        .hero-line {
            width: 65px;
            height: 2px;
            background: var(--gold);
            margin-top: 25px;
        }

        .booking-wrapper {
            width: min(1250px, 92%);
            margin: 65px auto 85px;
        }

        .booking-heading {
            text-align: center;
            margin-bottom: 38px;
        }

        .booking-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: 37px;
            font-weight: 500;
            margin: 12px 0;
        }

        .booking-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .booking-layout {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 25px;
            align-items: start;
        }

        .visual-panel {
            position: relative;
            min-height: 650px;
            overflow: hidden;
            border-radius: 18px;
            background:
                linear-gradient(180deg, rgba(25,12,22,.05) 15%, rgba(25,12,22,.95) 90%),
                url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=90') center/cover;
            border: 1px solid var(--border);
        }

        .visual-content {
            position: absolute;
            bottom: 0;
            left: 0;
            padding: 32px;
            width: 100%;
        }

        .visual-content .small-title {
            color: var(--gold);
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .visual-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            line-height: 1.2;
            font-weight: 500;
            margin: 14px 0;
        }

        .visual-content p {
            font-size: 13px;
            line-height: 1.8;
            color: #d7c4ce;
        }

        .visual-features {
            border-top: 1px solid rgba(255,255,255,.2);
            padding-top: 20px;
            margin-top: 25px;
            display: grid;
            gap: 17px;
        }

        .feature {
            display: flex;
            gap: 12px;
            align-items: center;
            font-size: 13px;
            color: #eadce2;
        }

        .feature i {
            color: var(--gold);
            width: 18px;
        }

        .form-panel {
            padding: 35px;
            background: linear-gradient(145deg, #291c29, #1d131e);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 25px 65px rgba(0,0,0,.25);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .form-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 27px;
            font-weight: 500;
            margin: 0 0 7px;
        }

        .form-header p {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }

        .form-number {
            font-family: 'Playfair Display', serif;
            color: var(--gold);
            font-size: 35px;
            opacity: .8;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 21px;
        }

        .section-number {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(213,179,107,.5);
            border-radius: 50%;
            color: var(--gold);
            font-size: 12px;
        }

        .section-heading h4 {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .5px;
            margin: 0;
        }

        .section-heading span {
            color: var(--muted);
            font-size: 11px;
            display: block;
            margin-top: 4px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 12px;
            color: #e7d6de;
            font-weight: 600;
        }

        label .required {
            color: var(--gold);
        }

        input, select, textarea {
            width: 100%;
            min-height: 49px;
            padding: 13px 15px;
            border: 1px solid #513a4d;
            border-radius: 9px;
            background: rgba(17,10,17,.7);
            color: #fff1f5;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: .2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(213,179,107,.09);
        }

        input::placeholder, textarea::placeholder {
            color: #8f7a88;
        }

        select option {
            background: #251725;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .field-hint {
            font-size: 11px;
            color: #a9919e;
            line-height: 1.6;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert.error {
            background: rgba(180,55,75,.13);
            border: 1px solid #a94b60;
            color: #ffc1cc;
        }

        .alert.success {
            background: rgba(70,150,110,.13);
            border: 1px solid #4d9875;
            color: #b9f0d1;
        }

        .submit-area {
            border-top: 1px solid var(--border);
            padding-top: 25px;
        }

        .submit {
            width: 100%;
            min-height: 55px;
            border: 0;
            border-radius: 9px;
            color: #211321;
            background: linear-gradient(110deg, #c49a52, #f0d9a5);
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: .25s;
        }

        .submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(213,179,107,.2);
            filter: brightness(1.08);
        }

        .submit i {
            margin-left: 9px;
        }

        .terms {
            text-align: center;
            font-size: 11px;
            color: var(--muted);
            margin: 15px 0 0;
            line-height: 1.7;
        }

        .footer {
            border-top: 1px solid var(--border);
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer strong {
            color: var(--rose);
            font-family: 'Playfair Display', serif;
            font-size: 18px;
        }

        @media(max-width:950px) {
            .booking-layout {
                grid-template-columns: 280px minmax(0,1fr);
            }

            .form-panel {
                padding: 25px;
            }

            .visual-content {
                padding: 23px;
            }
        }

        @media(max-width:720px) {
            .navbar {
                padding: 0 5%;
                height: 70px;
            }

            .nav-right {
                gap: 12px;
            }

            .nav-right a:first-child {
                display: none;
            }

            .hero {
                min-height: 300px;
                padding: 55px 7%;
            }

            .booking-wrapper {
                margin-top: 45px;
            }

            .booking-layout {
                grid-template-columns: 1fr;
            }

            .visual-panel {
                min-height: 360px;
            }

            .form-panel {
                padding: 23px 18px;
            }

            .booking-heading h2 {
                font-size: 31px;
            }
        }

        @media(max-width:480px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .form-header h3 {
                font-size: 23px;
            }

            .form-number {
                font-size: 27px;
            }

            .footer {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">Bella <span>Imagen.</span></a>

    <nav class="nav-right">
        <a href="index.php">Inicio</a>
        <a href="index.php#servicios">Servicios</a>
        <a href="login.php" class="nav-login">Acceso administrativo</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <div class="eyebrow">Belleza · Cuidado · Bienestar</div>

        <h1>Un momento para ti.<br><span>Una experiencia única.</span></h1>

        <p>
            Regálate el cuidado que mereces. Reserva tu próxima experiencia
            de belleza con nuestro equipo de especialistas.
        </p>

        <div class="hero-line"></div>
    </div>
</section>

<main class="booking-wrapper">

    <div class="booking-heading">
        <div class="eyebrow">Agenda tu experiencia</div>
        <h2>Tu próxima cita comienza aquí</h2>
        <p>Completa tus datos y selecciona el servicio que deseas disfrutar.</p>
    </div>

    <div class="booking-layout">

        <aside class="visual-panel">
            <div class="visual-content">
                <div class="small-title">Bella Imagen Experience</div>

                <h3>La belleza está en dedicarte tiempo.</h3>

                <p>
                    Creamos experiencias de cuidado personal pensadas para
                    resaltar tu belleza y hacerte sentir especial.
                </p>

                <div class="visual-features">
                    <div class="feature">
                        <i class="fa-solid fa-spa"></i>
                        Servicios especializados
                    </div>

                    <div class="feature">
                        <i class="fa-solid fa-gem"></i>
                        Atención personalizada
                    </div>

                    <div class="feature">
                        <i class="fa-regular fa-calendar-check"></i>
                        Reserva de manera sencilla
                    </div>
                </div>
            </div>
        </aside>

        <section class="form-panel">

            <div class="form-header">
                <div>
                    <h3>Formulario de reserva</h3>
                    <p>Los campos marcados con * son obligatorios.</p>
                </div>

                <div class="form-number">01—03</div>
            </div>

            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= h($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reservar.php">

                <input
                    type="hidden"
                    name="token"
                    value="<?= h($_SESSION['public_booking_token']) ?>"
                >

                <div class="form-section">

                    <div class="section-heading">
                        <div class="section-number">01</div>
                        <div>
                            <h4>Información personal</h4>
                            <span>Cuéntanos cómo podemos contactarte.</span>
                        </div>
                    </div>

                    <div class="form-grid">

                        <div class="field">
                            <label for="nombres">Nombres <span class="required">*</span></label>
                            <input
                                id="nombres"
                                name="nombres"
                                required
                                maxlength="100"
                                placeholder="Ingresa tus nombres"
                                value="<?= h($datos['nombres']) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="apellidos">Apellidos <span class="required">*</span></label>
                            <input
                                id="apellidos"
                                name="apellidos"
                                required
                                maxlength="100"
                                placeholder="Ingresa tus apellidos"
                                value="<?= h($datos['apellidos']) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="telefono">Teléfono <span class="required">*</span></label>
                            <input
                                id="telefono"
                                name="telefono"
                                type="tel"
                                required
                                maxlength="20"
                                placeholder="Ej. 987654321"
                                value="<?= h($datos['telefono']) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="email">Correo electrónico</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                maxlength="120"
                                placeholder="correo@ejemplo.com"
                                value="<?= h($datos['email']) ?>"
                            >
                        </div>

                    </div>
                </div>

                <div class="form-section">

                    <div class="section-heading">
                        <div class="section-number">02</div>
                        <div>
                            <h4>Selecciona tu experiencia</h4>
                            <span>Elige el servicio y la especialista.</span>
                        </div>
                    </div>

                    <div class="form-grid">

                        <div class="field">
                            <label for="servicio_id">Servicio <span class="required">*</span></label>

                            <select id="servicio_id" name="servicio_id" required>
                                <option value="">Seleccionar servicio</option>

                                <?php foreach ($servicios as $servicio): ?>
                                    <option
                                        value="<?= (int)$servicio['id'] ?>"
                                        <?= (string)$datos['servicio_id'] === (string)$servicio['id'] ? 'selected' : '' ?>
                                    >
                                        <?= h($servicio['nombre']) ?>
                                        · S/ <?= number_format((float)$servicio['precio'], 2) ?>
                                        · <?= (int)$servicio['duracion_minutos'] ?> min
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="field">
                            <label for="especialista_id">Especialista <span class="required">*</span></label>

                            <select id="especialista_id" name="especialista_id" required>
                                <option value="">Seleccionar especialista</option>

                                <?php foreach ($especialistas as $especialista): ?>
                                    <option
                                        value="<?= (int)$especialista['id'] ?>"
                                        <?= (string)$datos['especialista_id'] === (string)$especialista['id'] ? 'selected' : '' ?>
                                    >
                                        <?= h($especialista['nombres'] . ' ' . $especialista['apellidos']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                    </div>
                </div>

                <div class="form-section">

                    <div class="section-heading">
                        <div class="section-number">03</div>
                        <div>
                            <h4>Fecha y horario</h4>
                            <span>Selecciona cuándo deseas visitarnos.</span>
                        </div>
                    </div>

                    <div class="form-grid">

                        <div class="field">
                            <label for="fecha">Fecha de atención <span class="required">*</span></label>

                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                required
                                min="<?= date('Y-m-d') ?>"
                                value="<?= h($datos['fecha']) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="hora_inicio">Hora de atención <span class="required">*</span></label>

                            <select id="hora_inicio" name="hora_inicio" required>
                                <option value="">Seleccionar horario</option>

                                <?php
                                for ($hora = 7; $hora <= 20; $hora++) {
                                    foreach ([0, 30] as $minuto) {
                                        if ($hora === 20 && $minuto === 30) {
                                            continue;
                                        }

                                        $valor = sprintf('%02d:%02d', $hora, $minuto);
                                        $texto = date('h:i A', strtotime($valor));
                                        ?>

                                        <option
                                            value="<?= $valor ?>"
                                            <?= $datos['hora_inicio'] === $valor ? 'selected' : '' ?>
                                        >
                                            <?= $texto ?>
                                        </option>

                                        <?php
                                    }
                                }
                                ?>

                            </select>

                            <span class="field-hint">
                                Atención disponible de 7:00 a. m. a 9:00 p. m.
                            </span>
                        </div>

                        <div class="field full">
                            <label for="observaciones">Indicaciones adicionales</label>

                            <textarea
                                id="observaciones"
                                name="observaciones"
                                placeholder="¿Hay algo que debamos saber antes de tu visita?"
                            ><?= h($datos['observaciones']) ?></textarea>
                        </div>

                    </div>
                </div>

                <div class="submit-area">
                    <button type="submit" class="submit">
                        SOLICITAR RESERVA
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    <p class="terms">
                        <i class="fa-solid fa-shield-halved"></i>
                        Tus datos serán utilizados únicamente para gestionar tu reserva.
                        La cita quedará pendiente de confirmación.
                    </p>
                </div>

            </form>
        </section>
    </div>
</main>

<footer class="footer">
    <strong>Bella Imagen.</strong>
    <span>© <?= date('Y') ?> Bella Imagen · Todos los derechos reservados.</span>
    <a href="index.php">Volver al inicio ↑</a>
</footer>

</body>
</html>
