<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'inicio';
$tituloPagina = 'Inicio';

$noticias = $pdo->query('SELECT * FROM noticias WHERE publicado = 1 ORDER BY fecha DESC LIMIT 4')->fetchAll();
$destacada = $noticias[0] ?? null;
$restoNoticias = array_slice($noticias, 1);

$proxima = $pdo->query("SELECT * FROM competiciones WHERE disputada = 0 ORDER BY fecha ASC LIMIT 1")->fetch();
$fechaProximaISO = null;
if ($proxima) {
    $fechaProximaISO = $proxima['fecha'] . 'T' . ($proxima['hora'] ?: '00:00') . ':00';
}

$ajustes = obtenerAjustes($pdo);
$mostrarSplash = !empty($ajustes['splash_activo']) && !empty($ajustes['splash_imagen']);
$splashImagen = $ajustes['splash_imagen'] ?? null;

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="contenedor">
    <div>
      <div class="hero-eyebrow">Temporada 2026/27</div>
      <h1><?= t('hero_titulo') ?></h1>
      <p><?= t('hero_texto') ?></p>
      <div class="hero-cta">
        <a href="noticias.php" class="boton oro"><?= t('hero_boton_noticias') ?></a>
        <a href="competiciones.php" class="boton contorno"><?= t('hero_boton_competiciones') ?></a>
      </div>
    </div>

    <?php if ($proxima): ?>
    <div class="proximo-partido">
      <div class="etiqueta"><?= t('proxima_competicion') ?> · <?= e($proxima['categoria']) ?></div>
      <div class="enfrentamiento">
        <span><?= e($proxima['nombre']) ?></span>
      </div>
      <div class="detalle">
        <?= e(formatearFecha($proxima['fecha'])) ?> · <?= e($proxima['lugar']) ?>
      </div>
      <div class="cuenta-atras" data-fecha="<?= e($fechaProximaISO) ?>" data-dias="<?= e(t('dias')) ?>" data-horas="<?= e(t('horas')) ?>" data-min="<?= e(t('min')) ?>" data-seg="<?= e(t('seg')) ?>">
        <span class="cuenta-atras-num">–</span>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($ajustes['inicio_imagen'])): ?>
<section class="franja-portada animar-scroll">
  <div class="franja-portada-imagen" data-parallax style="background-image:url('img/<?= e($ajustes['inicio_imagen']) ?>');"></div>
  <div class="contenedor franja-portada-texto">
    <h2><?= e($ajustes['inicio_imagen_titulo'] ?: SITE_NAME) ?></h2>
    <p><?= e(SITE_CLAIM) ?></p>
  </div>
</section>
<?php endif; ?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_ultima_hora') ?></h2>
      <a href="noticias.php"><?= t('ver_todas') ?></a>
    </div>

    <?php if ($destacada): ?>
    <div class="grid-noticias">
      <a href="noticia.php?id=<?= (int)$destacada['id'] ?>" class="noticia-destacada animar-scroll">
        <img src="img/<?= e($destacada['imagen'] ?: 'competicion.svg') ?>" alt="">
        <div class="cuerpo-overlay">
          <div class="fecha"><?= e(formatearFecha($destacada['fecha'])) ?></div>
          <h3><?= e($destacada['titulo']) ?></h3>
          <p><?= e($destacada['resumen']) ?></p>
        </div>
      </a>

      <ul class="lista-noticias">
        <?php foreach ($restoNoticias as $n): ?>
        <li>
          <a href="noticia.php?id=<?= (int)$n['id'] ?>" class="animar-scroll" style="display:flex;gap:14px;">
            <img src="img/<?= e($n['imagen'] ?: 'cantera.svg') ?>" alt="">
            <span>
              <span class="fecha"><?= e(formatearFecha($n['fecha'])) ?></span>
              <h4><?= e($n['titulo']) ?></h4>
            </span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php else: ?>
      <p><?= t('sin_noticias') ?></p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
