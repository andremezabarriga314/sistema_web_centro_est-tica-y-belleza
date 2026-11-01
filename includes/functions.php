
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function go(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sessionToken = $_SESSION['csrf'] ?? '';
        $formToken = $_POST['csrf'] ?? '';

        if (
            $sessionToken === '' ||
            !hash_equals($sessionToken, $formToken)
        ) {
            http_response_code(419);
            exit('Formulario vencido. Recarga la página.');
        }
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'm' => $message,
        't' => $type
    ];
}

function must_login(): void
{
    if (empty($_SESSION['user'])) {
        go('login.php');
    }
}

function admin_only(): void
{
    require_role(['Administrador']);
}

function require_role(array $roles): void
{
    must_login();

    if (!in_array($_SESSION['user']['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('No tienes permisos para acceder a este módulo.');
    }
}

function require_permission(string $permission): void
{
    $map = [
        'clients' => ['Administrador', 'Recepcionista'],
        'agenda' => ['Administrador', 'Recepcionista', 'Especialista'],
        'agenda_write' => ['Administrador', 'Recepcionista'],
        'attentions' => ['Administrador', 'Especialista'],
        'inventory' => ['Administrador'],
        'catalog' => ['Administrador'],
        'scheduling' => ['Administrador'],
        'treatments_access' => ['Administrador', 'Recepcionista', 'Especialista'],
        'treatments_assign' => ['Administrador', 'Recepcionista'],
        'treatments_session' => ['Administrador', 'Especialista'],
        'payments' => ['Administrador', 'Recepcionista'],
        'reports' => ['Administrador', 'Recepcionista'],
        'users' => ['Administrador'],
    ];

    require_role($map[$permission] ?? ['Administrador']);
}

function money($value): string
{
    return 'S/ ' . number_format((float)$value, 2);
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date && $date->format('Y-m-d') === $value;
}
