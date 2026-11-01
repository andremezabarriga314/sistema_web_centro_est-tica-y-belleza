
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// Si ya inició sesión, ir al dashboard.
if (!empty($_SESSION['user'])) {
    go('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Ingresa tu usuario y contraseña.';
    } else {
        $sql = "
            SELECT
                u.id,
                u.nombre,
                u.usuario,
                u.password,
                u.estado,
                u.especialista_id,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.usuario = ? OR u.email = ?
            LIMIT 1
        ";

        $consulta = $pdo->prepare($sql);
        $consulta->execute([$usuario, $usuario]);
        $user = $consulta->fetch();

        if (
            $user &&
            $user['estado'] === 'Activo' &&
            password_verify($password, $user['password'])
        ) {
            session_regenerate_id(true);

            unset($user['password']);

            $_SESSION['user'] = $user;

            go('dashboard.php');
        }

        $error = 'Credenciales incorrectas o usuario inactivo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Bella Imagen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login">

    <form class="loginbox" method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf()) ?>"
        >

        <div class="brand">
            <span>BI</span>
        </div>

        <h1>Bella Imagen</h1>
        <p>Centro de Estética Integral</p>

        <?php if ($error !== ''): ?>
            <div class="alert error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="field">
            <label for="usuario">Usuario o correo</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                autocomplete="username"
                required
                autofocus
            >
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit" class="btn">
            Ingresar
        </button>

    </form>

</body>
</html>
