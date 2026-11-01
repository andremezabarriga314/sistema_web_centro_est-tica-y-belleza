
<?php
require_once __DIR__ . '/includes/functions.php';

must_login();
require_permission('clients');

require_once __DIR__ . '/config/database.php';

/* Obtener servicios activos, incluyendo su fotografía */
$services = $pdo->query("
    SELECT id, nombre, descripcion, precio, duracion_minutos, imagen
    FROM servicios
    WHERE estado = 'Activo'
    ORDER BY nombre
")->fetchAll();

/* Identificar la categoría del servicio */
function service_image(string $name): string {
    $n = mb_strtolower($name, 'UTF-8');

    if (preg_match('/pelu|cabello|corte|peinad|tinte|alisad/', $n)) {
        return 'hair';
    }

    if (preg_match('/manicur|uñas|unas|gel|acril/', $n)) {
        return 'nails';
    }

    if (preg_match('/facial|limpieza|hidrat|piel|rejuven/', $n)) {
        return 'facial';
    }

    if (preg_match('/maquill/', $n)) {
        return 'makeup';
    }

    if (preg_match('/pedicur|pies/', $n)) {
        return 'pedicure';
    }

    if (preg_match('/masaje|relaj|corporal|spa/', $n)) {
        return 'spa';
    }

    return 'procedure';
}

/* Fotografías de respaldo por categoría */
function service_photo(string $category): string {
    $photos = [
        'hair' => 'photo-1562322140-8baeececf3df',
        'nails' => 'photo-1610992015732-2449b76344bc',
        'facial' => 'photo-1570172619644-dfd03ed5d881',
        'makeup' => 'photo-1522335789203-aabd1fc54bc9',
        'pedicure' => 'photo-1519014816548-bf5fe059798b',
        'spa' => 'photo-1540555700478-4be289fbecef',
        'procedure' => 'photo-1560066984-138dadb4c035'
    ];

    $photo = $photos[$category] ?? $photos['procedure'];

    return 'https://images.unsplash.com/' . $photo .
        '?auto=format&fit=crop&w=900&q=85';
}

/* Priorizar la fotografía guardada en la base de datos */
function service_image_url(array $service): string {
    $savedImage = trim((string)($service['imagen'] ?? ''));

    if ($savedImage !== '') {
        return $savedImage;
    }

    return service_photo(service_image((string)$service['nombre']));
}

$title = 'Catálogo de servicios';

require __DIR__ . '/includes/header.php';
?>

<section class="catalog-page">

    <div class="catalog-hero">
        <div class="catalog-hero-content">
            <span class="catalog-kicker">BELLA IMAGEN · CENTRO DE ESTÉTICA</span>

            <h1>Tu belleza merece<br>un momento especial.</h1>

            <p>
                Descubre nuestros tratamientos y servicios diseñados
                para cuidar tu imagen, resaltar tu belleza y regalarte
                una experiencia de bienestar.
            </p>

            <a href="agenda.php" class="catalog-hero-btn">
                <span>Reservar una cita</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="catalog-hero-decoration">
            <span>BI</span>
            <small>BELLEZA · CUIDADO · BIENESTAR</small>
        </div>
    </div>

    <div class="catalog-heading">
        <div>
            <span class="eyebrow">NUESTROS TRATAMIENTOS</span>
            <h2>Servicios para ti</h2>
            <p>
                Explora nuestras opciones y encuentra el tratamiento
                ideal para ti.
            </p>
        </div>

        <a class="btn ghost" href="modulo.php?m=servicios">
            Administrar servicios
        </a>
    </div>

    <div class="catalog-count">
        <span class="catalog-count-icon">✦</span>
        <span>
            <strong><?= count($services) ?></strong>
            servicios disponibles
        </span>
    </div>

    <?php if ($services): ?>

        <div class="service-gallery catalog-gallery">

            <?php foreach ($services as $service): ?>

                <?php
                    $category = service_image((string)$service['nombre']);
                    $photo = service_image_url($service);
                ?>

                <article class="service-card catalog-card">

                    <div class="catalog-card-image">
                        <img
                            src="<?= e($photo) ?>"
                            alt="<?= e($service['nombre']) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?= e(service_photo('procedure')) ?>';"
                        >

                        <span class="catalog-card-category">
                            <?= e(match ($category) {
                                'hair' => 'Cuidado capilar',
                                'nails' => 'Manicure y uñas',
                                'facial' => 'Cuidado facial',
                                'makeup' => 'Maquillaje',
                                'pedicure' => 'Cuidado de pies',
                                'spa' => 'Spa y bienestar',
                                default => 'Tratamiento estético'
                            }) ?>
                        </span>

                        <div class="catalog-image-shade"></div>
                    </div>

                    <div class="service-card-body catalog-card-body">

                        <h3><?= e($service['nombre']) ?></h3>

                        <p>
                            <?= e(
                                $service['descripcion']
                                ?: 'Un servicio especializado para cuidar tu belleza y bienestar.'
                            ) ?>
                        </p>

                        <div class="service-meta catalog-meta">
                            <div>
                                <small>PRECIO DEL SERVICIO</small>
                                <strong><?= money($service['precio']) ?></strong>
                            </div>

                            <div class="catalog-duration">
                                <span>◷</span>
                                <?= (int)$service['duracion_minutos'] ?> min
                            </div>
                        </div>

                        <a href="agenda.php" class="catalog-card-button">
                            Agendar servicio
                            <span aria-hidden="true">→</span>
                        </a>

                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="catalog-empty">
            <span>✦</span>
            <h3>Próximamente nuevos servicios</h3>
            <p>
                Todavía no hay servicios activos en el catálogo.
                Puedes registrarlos desde la administración.
            </p>

            <a href="modulo.php?m=servicios" class="btn">
                Registrar servicios
            </a>
        </div>

    <?php endif; ?>

    <section class="catalog-bottom">
        <div>
            <span class="eyebrow">BELLA IMAGEN</span>
            <h2>Un espacio para sentirte bien contigo.</h2>
            <p>
                Nuestro objetivo es ofrecerte atención personalizada
                y una experiencia de belleza y bienestar.
            </p>
        </div>

        <a href="agenda.php" class="catalog-bottom-button">
            Agenda tu próxima visita →
        </a>
    </section>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
