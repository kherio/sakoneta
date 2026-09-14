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

$numGimnastasStats = (int)$pdo->query('SELECT COUNT(*) FROM gimnastas')->fetchColumn();
$numCompeticionesStats = (int)$pdo->query('SELECT COUNT(*) FROM competiciones WHERE disputada = 1')->fetchColumn();
$numCategoriasStats = (int)$pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();

$estadisticasPortada = [
    ['valor' => $ajustes['est1_valor'] ?? $numGimnastasStats, 'texto' => $ajustes['est1_texto'] ?: 'Gimnastas en el club'],
    ['valor' => $ajustes['est2_valor'] ?? $numCompeticionesStats, 'texto' => $ajustes['est2_texto'] ?: 'Competiciones disputadas'],
    ['valor' => $ajustes['est3_valor'] ?? $numCategoriasStats, 'texto' => $ajustes['est3_texto'] ?: 'Categorías, de base a senior'],
    ['valor' => $ajustes['est4_valor'] ?? 5, 'texto' => $ajustes['est4_texto'] ?: 'Aparatos: aro, pelota, mazas, cinta y cuerda'],
];

require __DIR__ . '/includes/header.php';
?>

<section class="hero <?= !empty($ajustes['inicio_imagen']) ? 'hero-con-foto' : '' ?>">
  <?php if (!empty($ajustes['inicio_imagen'])): ?>
    <div class="hero-fondo-foto" data-parallax="0.06" data-parallax-limite="35" style="background-image:url('img/<?= e($ajustes['inicio_imagen']) ?>');"></div>
  <?php endif; ?>
  <div class="contenedor">
    <div>
      <div class="hero-eyebrow"><span class="hero-kicker">★</span> <?= e($ajustes['hero_kicker'] ?: 'Equipo de referencia en gimnasia rítmica') ?></div>
      <h1><?= e($ajustes['hero_titulo'] ?: t('hero_titulo')) ?></h1>
      <p><?= e($ajustes['hero_texto'] ?: t('hero_texto')) ?></p>
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

<section class="franja-stats animar-scroll">
  <div class="contenedor stats-grid">
    <?php foreach ($estadisticasPortada as $est): ?>
    <div class="stat-item">
      <span class="stat-numero" data-hasta="<?= (int)$est['valor'] ?>">0</span>
      <span class="stat-etiqueta"><?= e($est['texto']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

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
