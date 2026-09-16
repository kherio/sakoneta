<?php require_once __DIR__ . '/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() === 'eu' ? 'eu' : 'es' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' · ' . nombreSitio() : nombreSitio() ?></title>
<meta name="description" content="<?= e($descripcionOG ?? claimSitio()) ?>">
<link rel="icon" href="img/logo-sakoneta.png" type="image/png">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(nombreSitio()) ?>">
<meta property="og:title" content="<?= isset($tituloPagina) ? e($tituloPagina) : e(nombreSitio()) ?>">
<meta property="og:description" content="<?= e($descripcionOG ?? claimSitio()) ?>">
<meta property="og:image" content="<?= e($imagenOG ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/img/logo-sakoneta.png') ?>">
<meta property="og:url" content="<?= e(((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')) ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="stylesheet" href="<?= versionArchivo('css/styles.css') ?>">
</head>
<body>

<?php if (!empty($mostrarSplash) && !empty($splashImagen)): ?>
<div class="splash" id="splash">
  <img src="img/<?= e($splashImagen) ?>" alt="" class="splash-foto">
  <div class="splash-capa"></div>
  <div class="splash-contenido">
    <img src="img/logo-sakoneta.png" alt="" class="splash-escudo" id="escudo-splash">
    <strong><?= e(nombreSitio()) ?></strong>
    <span><?= e(claimSitio()) ?></span>
    <div class="splash-cargando" aria-hidden="true"></div>
  </div>
</div>
<?php endif; ?>

<div id="barra-progreso-lectura"></div>

<div class="cabecera-top">
  <div class="contenedor">
    <span>Temporada 2026 / 2027</span>
    <span class="selector-idioma">
      <a href="?lang=es" class="<?= idiomaActual() === 'es' ? 'activo' : '' ?>">ES</a> /
      <a href="?lang=eu" class="<?= idiomaActual() === 'eu' ? 'activo' : '' ?>">EU</a>
    </span>
  </div>
</div>

<header class="cabecera" id="cabecera-principal">
  <div class="contenedor">
    <a href="index.php" class="marca">
      <img src="img/logo-sakoneta.png" alt="Emblema de <?= e(nombreSitio()) ?>" id="escudo-cabecera">
      <span class="marca-texto">
        <strong><?= e(nombreSitio()) ?></strong>
        <span><?= e(claimSitio()) ?></span>
      </span>
    </a>

    <button type="button" class="btn-menu-movil" id="btn-menu-movil" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-movil">
      <span></span><span></span><span></span>
    </button>

    <nav class="principal">
      <a href="index.php" class="<?= ($paginaActual ?? '') === 'inicio' ? 'activo' : '' ?>"><?= t('nav_inicio') ?></a>
      <a href="noticias.php" class="<?= ($paginaActual ?? '') === 'noticias' ? 'activo' : '' ?>"><?= t('nav_noticias') ?></a>
      <a href="gimnastas.php" class="<?= ($paginaActual ?? '') === 'gimnastas' ? 'activo' : '' ?>"><?= t('nav_gimnastas') ?></a>
      <a href="competiciones.php" class="<?= ($paginaActual ?? '') === 'competiciones' ? 'activo' : '' ?>"><?= t('nav_competiciones') ?></a>
      <a href="sobre.php" class="<?= ($paginaActual ?? '') === 'sobre' ? 'activo' : '' ?>"><?= t('nav_sobre') ?></a>
      <a href="contacto.php" class="<?= ($paginaActual ?? '') === 'contacto' ? 'activo' : '' ?>"><?= t('nav_contacto') ?></a>
      <a href="buscar.php" class="<?= ($paginaActual ?? '') === 'buscar' ? 'activo' : '' ?>" title="Buscar">⌕</a>
      <a href="admin/index.php" class="nav-acceso"><?= t('nav_acceso') ?></a>
    </nav>
  </div>

  <div class="menu-movil" id="menu-movil">
    <a href="index.php" class="<?= ($paginaActual ?? '') === 'inicio' ? 'activo' : '' ?>"><?= t('nav_inicio') ?></a>
    <a href="noticias.php" class="<?= ($paginaActual ?? '') === 'noticias' ? 'activo' : '' ?>"><?= t('nav_noticias') ?></a>
    <a href="gimnastas.php" class="<?= ($paginaActual ?? '') === 'gimnastas' ? 'activo' : '' ?>"><?= t('nav_gimnastas') ?></a>
    <a href="competiciones.php" class="<?= ($paginaActual ?? '') === 'competiciones' ? 'activo' : '' ?>"><?= t('nav_competiciones') ?></a>
    <a href="sobre.php" class="<?= ($paginaActual ?? '') === 'sobre' ? 'activo' : '' ?>"><?= t('nav_sobre') ?></a>
    <a href="contacto.php" class="<?= ($paginaActual ?? '') === 'contacto' ? 'activo' : '' ?>"><?= t('nav_contacto') ?></a>
    <a href="buscar.php" class="<?= ($paginaActual ?? '') === 'buscar' ? 'activo' : '' ?>"><?= t('nav_buscar') ?></a>
    <a href="admin/index.php" class="nav-acceso"><?= t('nav_acceso') ?></a>
  </div>
</header>

<div id="contenido-pagina">

<?php if (!empty($migas)): ?>
<nav class="migas-pan" aria-label="Ruta de navegación">
  <div class="contenedor">
    <a href="index.php"><?= t('nav_inicio') ?></a>
    <?php foreach ($migas as $miga): ?>
      <span class="migas-separador">›</span>
      <?php if (!empty($miga['url'])): ?>
        <a href="<?= e($miga['url']) ?>"><?= e($miga['texto']) ?></a>
      <?php else: ?>
        <span class="migas-actual"><?= e($miga['texto']) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
