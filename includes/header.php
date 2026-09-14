<?php require_once __DIR__ . '/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() === 'eu' ? 'eu' : 'es' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' · ' . SITE_NAME : SITE_NAME ?></title>
<link rel="icon" href="img/escudo.svg" type="image/svg+xml">
<link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php if (!empty($mostrarSplash) && !empty($splashImagen)): ?>
<div class="splash" id="splash">
  <img src="img/<?= e($splashImagen) ?>" alt="" class="splash-foto">
  <div class="splash-capa"></div>
  <div class="splash-contenido">
    <img src="img/escudo.svg" alt="" class="splash-escudo" id="escudo-splash">
    <strong><?= e(SITE_NAME) ?></strong>
    <span><?= e(SITE_CLAIM) ?></span>
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

<header class="cabecera">
  <div class="contenedor">
    <a href="index.php" class="marca">
      <img src="img/escudo.svg" alt="Emblema de <?= e(SITE_NAME) ?>" id="escudo-cabecera">
      <span class="marca-texto">
        <strong><?= e(SITE_NAME) ?></strong>
        <span><?= e(SITE_CLAIM) ?></span>
      </span>
    </a>
    <nav class="principal">
      <a href="index.php" class="<?= ($paginaActual ?? '') === 'inicio' ? 'activo' : '' ?>"><?= t('nav_inicio') ?></a>
      <a href="noticias.php" class="<?= ($paginaActual ?? '') === 'noticias' ? 'activo' : '' ?>"><?= t('nav_noticias') ?></a>
      <a href="gimnastas.php" class="<?= ($paginaActual ?? '') === 'gimnastas' ? 'activo' : '' ?>"><?= t('nav_gimnastas') ?></a>
      <a href="competiciones.php" class="<?= ($paginaActual ?? '') === 'competiciones' ? 'activo' : '' ?>"><?= t('nav_competiciones') ?></a>
      <a href="sobre.php" class="<?= ($paginaActual ?? '') === 'sobre' ? 'activo' : '' ?>"><?= t('nav_sobre') ?></a>
      <a href="contacto.php" class="<?= ($paginaActual ?? '') === 'contacto' ? 'activo' : '' ?>"><?= t('nav_contacto') ?></a>
      <a href="admin/index.php" class="nav-acceso"><?= t('nav_acceso') ?></a>
    </nav>
  </div>
</header>
