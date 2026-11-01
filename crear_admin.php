
<?php
require_once __DIR__ . '/config/database.php';

$rol = $pdo->query("
    SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1
")->fetch();

if (!$rol) {
    exit('No existe el rol Administrador. Revisa la tabla roles.');
}

$password = password_hash('BellaImagen2026!', PASSWORD_DEFAULT);

$consulta = $pdo->prepare("
    SELECT id FROM usuarios WHERE usuario = 'admin' LIMIT 1
");
$consulta->execute();
$usuario = $consulta->fetch();

if ($usuario) {
    $actualizar = $pdo->prepare("
        UPDATE usuarios
        SET rol_id = ?, nombre = ?, email = ?, password = ?, estado = 'Activo'
        WHERE id = ?
    ");

    $actualizar->execute([
        $rol['id'],
        'Administrador Bella Imagen',
        'admin@bellaimagen.com',
        $password,
        $usuario['id']
    ]);

    echo 'Administrador actualizado correctamente.';
} else {
    $insertar = $pdo->prepare("
        INSERT INTO usuarios
        (rol_id, nombre, usuario, email, password, estado)
        VALUES (?, ?, ?, ?, ?, 'Activo')
    ");

    $insertar->execute([
        $rol['id'],
        'Administrador Bella Imagen',
        'admin',
        'admin@bellaimagen.com',
        $password
    ]);

    echo 'Administrador creado correctamente.';
}
