
<?php
require_once __DIR__ . '/includes/functions.php';
must_login();
check_csrf();
require_once __DIR__ . '/config/database.php';

$m = $_GET['m'] ?? 'clientes';

if ($m === 'clientes') {
    require_permission('clients');
} elseif ($m === 'usuarios') {
    require_permission('users');
} else {
    require_permission('catalog');
}

$defs = [
    'clientes' => [
        'table' => 'clientes',
        'title' => 'Clientes',
        'subtitle' => 'Conoce y administra a las personas que confían en Bella Imagen.',
        'image' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=1600&q=85',
        'fields' => [
            'nombres' => ['Nombres', 'text'],
            'apellidos' => ['Apellidos', 'text'],
            'documento' => ['Documento', 'text'],
            'telefono' => ['Teléfono', 'text'],
            'email' => ['Correo electrónico', 'email'],
            'direccion' => ['Dirección', 'text']
        ],
        'cols' => [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'documento' => 'Documento',
            'telefono' => 'Teléfono',
            'email' => 'Correo'
        ]
    ],

    'especialistas' => [
        'table' => 'especialistas',
        'title' => 'Especialistas',
        'subtitle' => 'Gestiona al equipo profesional de nuestro centro estético.',
        'image' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=1600&q=85',
        'fields' => [
            'nombres' => ['Nombres', 'text'],
            'apellidos' => ['Apellidos', 'text'],
            'telefono' => ['Teléfono', 'text'],
            'email' => ['Correo electrónico', 'email'],
            'descripcion' => ['Descripción', 'textarea'],
            'estado' => ['Estado', 'enum:Activo,Inactivo']
        ],
        'cols' => [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'telefono' => 'Teléfono',
            'email' => 'Correo',
            'estado' => 'Estado'
        ]
    ],

    'especialidades' => [
        'table' => 'especialidades',
        'title' => 'Especialidades',
        'subtitle' => 'Organiza las áreas profesionales y especialidades disponibles.',
        'image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=1600&q=85',
        'fields' => [
            'nombre' => ['Nombre', 'text'],
            'descripcion' => ['Descripción', 'textarea']
        ],
        'cols' => [
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción'
        ]
    ],

    'servicios' => [
        'table' => 'servicios',
        'title' => 'Servicios',
        'subtitle' => 'Administra los tratamientos y experiencias de belleza.',
        'image' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1600&q=85',
        'fields' => [
            'nombre' => ['Nombre del servicio', 'text'],
            'descripcion' => ['Descripción', 'textarea'],
            'precio' => ['Precio (S/)', 'number'],
            'duracion_minutos' => ['Duración (minutos)', 'number'],
            'estado' => ['Estado', 'enum:Activo,Inactivo']
        ],
        'cols' => [
            'nombre' => 'Servicio',
            'precio' => 'Precio',
            'duracion_minutos' => 'Duración',
            'estado' => 'Estado'
        ]
    ],

    'productos' => [
        'table' => 'productos',
        'title' => 'Productos',
        'subtitle' => 'Controla los productos y recursos utilizados en los tratamientos.',
        'image' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?w=1600&q=85',
        'fields' => [
            'nombre' => ['Nombre', 'text'],
            'descripcion' => ['Descripción', 'textarea'],
            'stock_minimo' => ['Stock mínimo', 'number'],
            'unidad' => ['Unidad', 'text'],
            'precio_compra' => ['Precio de compra', 'number'],
            'estado' => ['Estado', 'enum:Activo,Inactivo']
        ],
        'cols' => [
            'nombre' => 'Producto',
            'stock' => 'Stock',
            'stock_minimo' => 'Mínimo',
            'unidad' => 'Unidad',
            'estado' => 'Estado'
        ]
    ],

    'tratamientos' => [
        'table' => 'tratamientos',
        'title' => 'Tratamientos',
        'subtitle' => 'Administra los planes y protocolos de cuidado estético.',
        'image' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=1600&q=85',
        'fields' => [
            'nombre' => ['Nombre', 'text'],
            'descripcion' => ['Descripción', 'textarea'],
            'precio' => ['Precio (S/)', 'number'],
            'numero_sesiones' => ['Número de sesiones', 'number'],
            'estado' => ['Estado', 'enum:Activo,Inactivo']
        ],
        'cols' => [
            'nombre' => 'Tratamiento',
            'precio' => 'Precio',
            'numero_sesiones' => 'Sesiones',
            'estado' => 'Estado'
        ]
    ],

    'usuarios' => [
        'table' => 'usuarios',
        'title' => 'Usuarios',
        'subtitle' => 'Administra los accesos y perfiles del sistema.',
        'image' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1600&q=85',
        'fields' => [
            'nombre' => ['Nombre completo', 'text'],
            'usuario' => ['Usuario', 'text'],
            'email' => ['Correo electrónico', 'email'],
            'rol_id' => ['Rol', 'role'],
            'especialista_id' => ['Especialista vinculado', 'specialist'],
            'password' => ['Contraseña', 'password'],
            'estado' => ['Estado', 'enum:Activo,Inactivo']
        ],
        'cols' => [
            'nombre' => 'Nombre',
            'usuario' => 'Usuario',
            'email' => 'Correo',
            'rol_id' => 'Rol',
            'especialista_id' => 'Especialista',
            'estado' => 'Estado'
        ]
    ]
];

if (!isset($defs[$m])) {
    http_response_code(404);
    $title = 'Módulo no encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="bi-empty-module"><h2>Módulo no disponible</h2><p>Selecciona una sección disponible.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$d = $defs[$m];
$table = $d['table'];
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException('Registro no válido.');
            }

            $st = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
            $st->execute([$id]);

            flash('Registro eliminado correctamente.');
            go('modulo.php?m=' . urlencode($m));
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $vals = [];

            foreach ($d['fields'] as $k => $meta) {
                $v = trim((string)($_POST[$k] ?? ''));

                if (
                    in_array($k, ['nombres', 'apellidos', 'nombre', 'usuario'], true)
                    && $v === ''
                ) {
                    throw new RuntimeException('Completa los campos obligatorios.');
                }

                if (
                    $k === 'email'
                    && $v !== ''
                    && !filter_var($v, FILTER_VALIDATE_EMAIL)
                ) {
                    throw new RuntimeException('Ingresa un correo electrónico válido.');
                }

                if (
                    in_array($k, ['precio', 'stock_minimo', 'precio_compra'], true)
                    && $v !== ''
                    && (!is_numeric($v) || (float)$v < 0)
                ) {
                    throw new RuntimeException('Los importes y cantidades no pueden ser negativos.');
                }

                if ($k === 'password') {
                    if ($id && $v === '') {
                        continue;
                    }

                    if ($v === '') {
                        throw new RuntimeException('La contraseña es obligatoria.');
                    }

                    $v = password_hash($v, PASSWORD_DEFAULT);
                }

                if ($k === 'rol_id') {
                    $v = (int)$v;
                }

                if ($k === 'especialista_id') {
                    $v = $v !== '' ? (int)$v : null;
                }

                if (
                    in_array($k, ['precio', 'stock_minimo', 'precio_compra'], true)
                    && $v === ''
                ) {
                    $v = '0';
                }

                if ($k === 'duracion_minutos' || $k === 'numero_sesiones') {
                    $v = max(1, (int)$v);
                }

                if ($k === 'email' && $v === '') {
                    $v = null;
                }

                $vals[$k] = $v;
            }

            if ($m === 'usuarios') {
                $roleQ = $pdo->prepare('SELECT nombre FROM roles WHERE id = ?');
                $roleQ->execute([$vals['rol_id'] ?? 0]);
                $roleName = $roleQ->fetchColumn();

                if (!$roleName) {
                    throw new RuntimeException('Selecciona un rol válido.');
                }

                if ($roleName === 'Especialista' && empty($vals['especialista_id'])) {
                    throw new RuntimeException('Vincula la cuenta con un perfil de especialista.');
                }

                if ($roleName !== 'Especialista') {
                    $vals['especialista_id'] = null;
                }
            }

            if ($id > 0) {
                if (!$vals) {
                    throw new RuntimeException('No hay cambios para guardar.');
                }

                $set = implode(',', array_map(
                    fn($k) => "`$k` = ?",
                    array_keys($vals)
                ));

                $sql = "UPDATE `$table` SET $set WHERE id = ?";
                $pdo->prepare($sql)->execute([...array_values($vals), $id]);
            } else {
                $keys = array_keys($vals);

                $sql = "INSERT INTO `$table` (`"
                    . implode('`,`', $keys)
                    . "`) VALUES ("
                    . implode(',', array_fill(0, count($keys), '?'))
                    . ")";

                $pdo->prepare($sql)->execute(array_values($vals));
            }

            flash('Registro guardado correctamente.');
            go('modulo.php?m=' . urlencode($m));
        }
    }
} catch (Throwable $e) {
    $error = $e instanceof PDOException
        ? 'No se pudo completar la operación. Verifica si el registro está relacionado con otros datos o si existe información duplicada.'
        : $e->getMessage();
}

$edit = null;

if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();

    if (!$edit) {
        $error = 'No se encontró el registro solicitado.';
    }
}

$rows = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 300")->fetchAll();

$roles = $m === 'usuarios'
    ? $pdo->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll()
    : [];

$specialists = $m === 'usuarios'
    ? $pdo->query("SELECT id, nombres, apellidos FROM especialistas WHERE estado = 'Activo' ORDER BY nombres")->fetchAll()
    : [];

$title = $d['title'];
require __DIR__ . '/includes/header.php';
?>

<div class="bi-module-page">

    <section class="bi-module-hero">
        <div class="bi-module-hero-photo"
             style="background-image:url('<?= e($d['image']) ?>')"></div>
        <div class="bi-module-hero-shade"></div>

        <div class="bi-module-hero-content">
            <span class="bi-module-eyebrow">BELLA IMAGEN · CENTRO DE ESTÉTICA</span>
            <h1><?= e($d['title']) ?></h1>
            <p><?= e($d['subtitle']) ?></p>

            <span class="bi-module-pill">
                <i></i> MÓDULO DE GESTIÓN
            </span>
        </div>

        <div class="bi-module-hero-counter">
            <small>REGISTROS</small>
            <strong><?= count($rows) ?></strong>
            <span>Elementos registrados</span>
        </div>
    </section>

    <section class="bi-module-stats">
        <article class="bi-module-stat">
            <span class="bi-stat-icon">✧</span>
            <div>
                <small>TOTAL DE REGISTROS</small>
                <strong><?= count($rows) ?></strong>
            </div>
        </article>

        <article class="bi-module-stat">
            <span class="bi-stat-icon">◇</span>
            <div>
                <small>SECCIÓN ACTUAL</small>
                <strong><?= e($d['title']) ?></strong>
            </div>
        </article>

        <article class="bi-module-stat">
            <span class="bi-stat-icon">♡</span>
            <div>
                <small>ESTADO DEL MÓDULO</small>
                <strong>Disponible</strong>
            </div>
        </article>
    </section>

    <?php if ($error): ?>
        <div class="bi-module-alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="bi-module-grid">

        <section class="bi-module-panel bi-module-form-panel">
            <div class="bi-module-panel-heading">
                <div>
                    <span class="bi-module-eyebrow">GESTIÓN DE INFORMACIÓN</span>
                    <h2><?= $edit ? 'Editar registro' : 'Nuevo registro' ?></h2>
                    <p><?= $edit ? 'Actualiza los datos del registro seleccionado.' : 'Completa los datos para agregar un nuevo registro.' ?></p>
                </div>
                <span class="bi-panel-decoration">✦</span>
            </div>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

                <div class="bi-form-grid">
                    <?php foreach ($d['fields'] as $k => $meta): ?>
                        <?php $val = $edit[$k] ?? ''; ?>

                        <label class="bi-field">
                            <span><?= e($meta[0]) ?></span>

                            <?php if ($meta[1] === 'textarea'): ?>
                                <textarea name="<?= e($k) ?>" rows="3"><?= e($val) ?></textarea>

                            <?php elseif ($meta[1] === 'role'): ?>
                                <select name="rol_id" required>
                                    <option value="">Seleccionar rol</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= (int)$r['id'] ?>"
                                            <?= ((int)$val === (int)$r['id']) ? 'selected' : '' ?>>
                                            <?= e($r['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($meta[1] === 'specialist'): ?>
                                <select name="especialista_id">
                                    <option value="">No aplica</option>
                                    <?php foreach ($specialists as $sp): ?>
                                        <option value="<?= (int)$sp['id'] ?>"
                                            <?= ((int)$val === (int)$sp['id']) ? 'selected' : '' ?>>
                                            <?= e($sp['nombres'] . ' ' . $sp['apellidos']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif (str_starts_with($meta[1], 'enum:')): ?>
                                <select name="<?= e($k) ?>">
                                    <?php foreach (explode(',', substr($meta[1], 5)) as $o): ?>
                                        <option value="<?= e($o) ?>" <?= ($val === $o) ? 'selected' : '' ?>>
                                            <?= e($o) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php else: ?>
                                <input
                                    type="<?= e($meta[1]) ?>"
                                    name="<?= e($k) ?>"
                                    value="<?= $meta[1] === 'password' ? '' : e($val) ?>"
                                    <?= $meta[1] === 'number' ? 'step="0.01" min="0"' : '' ?>
                                    <?= ($meta[1] === 'password' && !$edit) ? 'required' : '' ?>
                                    <?= in_array($k, ['nombre', 'nombres', 'apellidos', 'usuario'], true) ? 'required' : '' ?>
                                    placeholder="<?= ($meta[1] === 'password' && $edit) ? 'Dejar vacío para conservar la contraseña' : '' ?>"
                                >
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="bi-module-form-actions">
                    <button type="submit" class="bi-module-primary-button">
                        <?= $edit ? 'Guardar cambios' : '＋ Registrar información' ?>
                    </button>

                    <?php if ($edit): ?>
                        <a href="modulo.php?m=<?= e($m) ?>" class="bi-module-secondary-button">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="bi-form-photo">
                <img
                    src="https://images.unsplash.com/photo-1600334129128-685c5582fd35?w=900&q=80"
                    alt="Ambiente de spa y bienestar"
                    loading="lazy"
                >
                <div>
                    <span>EXPERIENCIA BELLA IMAGEN</span>
                    <strong>Un espacio dedicado a tu bienestar.</strong>
                </div>
            </div>
        </section>

        <section class="bi-module-panel bi-module-list-panel">
            <div class="bi-module-panel-heading">
                <div>
                    <span class="bi-module-eyebrow">REGISTROS DEL SISTEMA</span>
                    <h2>Listado de <?= e($d['title']) ?></h2>
                    <p>Consulta, modifica o elimina información registrada.</p>
                </div>

                <span class="bi-module-list-count"><?= count($rows) ?></span>
            </div>

            <div class="bi-module-table-wrap">
                <table class="bi-module-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php foreach ($d['cols'] as $k => $label): ?>
                                <th><?= e($label) ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><span class="bi-module-id">#<?= (int)$r['id'] ?></span></td>

                                <?php foreach ($d['cols'] as $k => $label): ?>
                                    <td>
                                        <?php if ($k === 'rol_id'): ?>
                                            <?php
                                            $roleName = '—';
                                            foreach ($roles as $rr) {
                                                if ((int)$rr['id'] === (int)$r[$k]) {
                                                    $roleName = $rr['nombre'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?= e($roleName) ?>

                                        <?php elseif ($k === 'especialista_id'): ?>
                                            <?php
                                            $spName = '—';
                                            foreach ($specialists as $sp) {
                                                if ((int)$sp['id'] === (int)$r[$k]) {
                                                    $spName = $sp['nombres'] . ' ' . $sp['apellidos'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?= e($spName) ?>

                                        <?php elseif ($k === 'precio' || $k === 'precio_compra'): ?>
                                            S/ <?= number_format((float)$r[$k], 2) ?>

                                        <?php elseif ($k === 'estado'): ?>
                                            <span class="bi-module-status <?= ($r[$k] === 'Activo') ? 'active' : 'inactive' ?>">
                                                <?= e($r[$k]) ?>
                                            </span>

                                        <?php elseif ($k === 'duracion_minutos'): ?>
                                            <?= (int)$r[$k] ?> min

                                        <?php else: ?>
                                            <?= e($r[$k] ?? '') ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <div class="bi-module-actions">
                                        <a
                                            href="modulo.php?m=<?= e($m) ?>&edit=<?= (int)$r['id'] ?>"
                                            class="bi-module-edit"
                                        >Editar</a>

                                        <form method="post"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este registro?')">
                                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="bi-module-delete">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="<?= count($d['cols']) + 2 ?>">
                                    <div class="bi-module-empty">
                                        <span>✧</span>
                                        <strong>Aún no hay registros</strong>
                                        <p>Agrega el primer registro desde el formulario.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
