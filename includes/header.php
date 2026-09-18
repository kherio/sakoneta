<?php
require_once __DIR__ . '/i18n.php';
if (isset($pdo)) {
    registrarVisita($pdo, $paginaActual ?? ($_SERVER['REQUEST_URI'] ?? 'desconocida'));
}
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() === 'eu' ? 'eu' : 'es' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1450C4">
<link rel="icon" href="img/icono-192.png">
<link rel="apple-touch-icon" href="img/icono-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Sakoneta">
<script>
  // Se aplica aquí, lo antes posible (antes de que el body llegue a
  // pintarse), para que no haya un parpadeo del tema claro antes de
  // cambiar al oscuro guardado.
  try {
    if (localStorage.getItem('sakoneta_tema') === 'oscuro') {
      document.documentElement.setAttribute('data-tema', 'oscuro');
    }
  } catch (e) {}

  // Política global de "movimiento reducido": no solo cuando la
  // persona lo pide explícitamente (prefers-reduced-motion), sino
  // también cuando el propio dispositivo da señales de tener pocos
  // recursos (poca RAM, pocos núcleos) o una conexión limitada (modo
  // ahorro de datos, red lenta) — en esos casos, de nada sirve
  // preguntarle a la persona: cuantos menos efectos decorativos,
  // mejor irá la web independientemente de si los pidió o no. Se deja
  // como atributo en <html> para que tanto el CSS como el JS puedan
  // usarlo sin repetir esta misma comprobación en cada sitio.
  try {
    var prefiereMenos = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var pocaMemoria = 'deviceMemory' in navigator && navigator.deviceMemory <= 2;
    var pocosNucleos = 'hardwareConcurrency' in navigator && navigator.hardwareConcurrency <= 2;
    var conexionLimitada = 'connection' in navigator && navigator.connection &&
      (navigator.connection.saveData || /^(slow-2g|2g)$/.test(navigator.connection.effectiveType || ''));
    if (prefiereMenos || pocaMemoria || pocosNucleos || conexionLimitada) {
      document.documentElement.setAttribute('data-movimiento', 'reducido');
    }
  } catch (e) {}

  // Si se ha llegado deslizando desde otra competición (ver el swipe
  // en main.js), la página de origen ya sabe hacia qué lado se ha
  // deslizado, pero esta página es una carga nueva e independiente:
  // sin esto, su parte de la transición (la entrada) usaría el
  // fundido por defecto en vez de deslizarse en la misma dirección,
  // perdiendo la sensación de movimiento continuo entre una
  // competición y la siguiente. Se recoge aquí, antes de pintar, y se
  // borra al momento para que solo afecte a esta carga en concreto.
  try {
    var transicionSwipe = sessionStorage.getItem('sakoneta_transicion');
    if (transicionSwipe) {
      document.documentElement.setAttribute('data-transicion', transicionSwipe);
      sessionStorage.removeItem('sakoneta_transicion');
      // Solo debe afectar a la transición de ENTRADA de esta carga en
      // concreto: si se deja puesto, la siguiente navegación desde
      // esta misma página (un enlace cualquiera, sin relación con el
      // swipe) heredaría el mismo deslizamiento en vez del fundido
      // normal. El propio arranque de la transición ya ha capturado
      // el atributo para entonces, así que quitarlo enseguida no
      // afecta a la animación de esta carga.
      setTimeout(function () {
        document.documentElement.removeAttribute('data-transicion');
      }, 2000);
    }
  } catch (e) {}
</script>
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' · ' . nombreSitio() : nombreSitio() ?></title>
<meta name="description" content="<?= e($descripcionOG ?? claimSitio()) ?>">
<link rel="icon" href="img/logo-sakoneta.png" type="image/png">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(nombreSitio()) ?>">
<meta property="og:title" content="<?= isset($tituloPagina) ? e($tituloPagina) : e(nombreSitio()) ?>">
<meta property="og:description" content="<?= e($descripcionOG ?? claimSitio()) ?>">
<meta property="og:image" content="<?= e($imagenOG ?? SITE_URL . '/img/logo-sakoneta.png') ?>">
<?php
// Para la URL de esta página en concreto no se puede usar SITE_URL a
// secas seguida de REQUEST_URI: SITE_URL ya incluye la subcarpeta del
// proyecto (p. ej. /sakoneta-web) y REQUEST_URI también la trae de
// serie (viene de la URL real por la que se ha llegado hasta aquí),
// así que se duplicaría. En su lugar, se coge de SITE_URL solo el
// esquema y el dominio (la parte que NO se puede fiar de la petición)
// y se le añade la ruta real de la petición tal cual.
$origenSeguro = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST);
?>
<meta property="og:url" content="<?= e($origenSeguro . ($_SERVER['REQUEST_URI'] ?? '')) ?>">
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
    <button type="button" class="splash-saltar" id="splash-saltar">Saltar intro →</button>
  </div>
</div>
<?php endif; ?>

<div id="barra-progreso-lectura"></div>

<div class="cabecera-top">
  <div class="contenedor">
    <span>Temporada 2026 / 2027</span>
    <span class="cabecera-top-derecha">
      <button type="button" id="interruptor-tema" class="interruptor-tema" aria-label="Cambiar a modo oscuro" title="Cambiar a modo oscuro">🌙</button>
      <span class="selector-idioma">
        <a href="<?= e(urlConIdioma('es')) ?>" class="<?= idiomaActual() === 'es' ? 'activo' : '' ?>">ES</a> /
        <a href="<?= e(urlConIdioma('eu')) ?>" class="<?= idiomaActual() === 'eu' ? 'activo' : '' ?>">EU</a>
      </span>
    </span>
  </div>
</div>

<header class="cabecera" id="cabecera-principal">
  <div class="contenedor">
    <a href="index.php" class="marca" id="logo-cabecera">
      <img src="img/logo-sakoneta.png" alt="Emblema de <?= e(nombreSitio()) ?>" id="escudo-cabecera">
      <span class="marca-texto">
        <strong>Sakoneta GET</strong>
        <span><?= e(claimSitio()) ?></span>
      </span>
    </a>

    <?php
    // Se muestra solo cuando la cabecera se compacta al hacer scroll
    // (ver CSS), y solo si no estamos ya en la portada (ahí no hace
    // falta decir "estás en Inicio"), para que la persona sepa en
    // qué sección sigue estando aunque el logo se haya hecho pequeño.
    $etiquetasSeccion = [
        'noticias' => 'nav_noticias', 'gimnastas' => 'nav_gimnastas', 'competiciones' => 'nav_competiciones',
        'sobre' => 'nav_sobre', 'contacto' => 'nav_contacto', 'buscar' => 'nav_buscar',
    ];
    $seccionActualTexto = $etiquetasSeccion[$paginaActual ?? ''] ?? null;
    if ($seccionActualTexto):
    ?>
    <span class="cabecera-seccion-actual" aria-hidden="true"><?= t($seccionActualTexto) ?></span>
    <?php endif; ?>

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
      <a href="unete.php" class="nav-unete <?= ($paginaActual ?? '') === 'unete' ? 'activo' : '' ?>"><?= t('nav_unete') ?></a>
      <a href="buscar.php" class="<?= ($paginaActual ?? '') === 'buscar' ? 'activo' : '' ?>" title="Buscar">⌕</a>
      <a href="admin/index.php" class="nav-acceso"><?= t('nav_acceso') ?></a>
    </nav>
  </div>

  <div class="menu-movil" id="menu-movil">
    <div class="menu-movil-cabecera">
      <span class="menu-movil-acento" aria-hidden="true"></span>
      <img src="img/logo-sakoneta.png" alt="" class="menu-movil-logo">
      <div class="menu-movil-marca">
        <strong>Sakoneta GET</strong>
      </div>
      <button type="button" class="menu-movil-cerrar" id="menu-movil-cerrar" aria-label="Cerrar menú">×</button>
    </div>
    <div class="menu-movil-separador"></div>
    <div class="menu-movil-grupo">
      <a href="index.php" style="--i:0" <?= ($paginaActual ?? '') === 'inicio' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_inicio') ?></a>
      <a href="noticias.php" style="--i:1" <?= ($paginaActual ?? '') === 'noticias' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_noticias') ?></a>
      <a href="competiciones.php" style="--i:2" <?= ($paginaActual ?? '') === 'competiciones' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_competiciones') ?></a>
      <a href="sobre.php" style="--i:3" <?= ($paginaActual ?? '') === 'sobre' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_sobre') ?></a>
      <a href="historia.php" style="--i:4" <?= ($paginaActual ?? '') === 'historia' ? 'class="activo" aria-current="page"' : '' ?>>Historia y palmarés</a>
      <a href="unete.php" class="nav-unete" style="--i:5" <?= ($paginaActual ?? '') === 'unete' ? 'aria-current="page"' : '' ?>>Únete a Sakoneta</a>
      <a href="contacto.php" style="--i:6" <?= ($paginaActual ?? '') === 'contacto' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_contacto') ?></a>
      <a href="buscar.php" style="--i:7" <?= ($paginaActual ?? '') === 'buscar' ? 'class="activo" aria-current="page"' : '' ?>><?= t('nav_buscar') ?></a>
    </div>
  </div>
  <div class="menu-movil-fondo" id="menu-movil-fondo" aria-hidden="true"></div>
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
