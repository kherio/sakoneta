<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'buscar';

$consulta = trim($_GET['q'] ?? '');
$resultadosNoticias = [];
$resultadosGimnastas = [];
$resultadosCompeticiones = [];

if ($consulta !== '') {
    $comodin = '%' . $consulta . '%';

    $stmt = $pdo->prepare('SELECT * FROM noticias WHERE publicado = 1 AND (titulo LIKE ? OR resumen LIKE ? OR contenido LIKE ?) ORDER BY fecha DESC');
    $stmt->execute([$comodin, $comodin, $comodin]);
    $resultadosNoticias = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM gimnastas WHERE nombre LIKE ? OR aparato LIKE ? ORDER BY orden ASC');
    $stmt->execute([$comodin, $comodin]);
    $resultadosGimnastas = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM competiciones WHERE nombre LIKE ? OR lugar LIKE ? ORDER BY fecha DESC');
    $stmt->execute([$comodin, $comodin]);
    $resultadosCompeticiones = $stmt->fetchAll();
}

$totalResultados = count($resultadosNoticias) + count($resultadosGimnastas) + count($resultadosCompeticiones);
$tituloPagina = $consulta !== '' ? 'Buscar: ' . $consulta : 'Buscar';
$migas = [['texto' => t('nav_buscar')]];

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('nav_buscar') ?></h2>
    </div>

    <form method="get" class="formulario-buscar">
      <input type="search" name="q" value="<?= e($consulta) ?>" placeholder="Busca noticias, gimnastas, competiciones..." autofocus>
      <button type="submit" class="boton oro" style="padding:11px 24px;">Buscar</button>
    </form>

    <?php if ($consulta === ''): ?>
      <p style="color:var(--gris-texto);margin-top:24px;">Escribe algo para buscar en todo el sitio.</p>
    <?php elseif ($totalResultados === 0): ?>
      <p style="color:var(--gris-texto);margin-top:24px;">No hemos encontrado nada para "<?= e($consulta) ?>".</p>
    <?php else: ?>

      <?php if ($resultadosNoticias): ?>
      <h3 class="resultados-titulo"><?= t('nav_noticias') ?></h3>
      <div class="pagina-noticias">
        <?php foreach ($resultadosNoticias as $n): ?>
        <a href="noticia.php?id=<?= (int)$n['id'] ?>" class="tarjeta-noticia">
          <div class="marco-img"><img src="img/<?= e($n['imagen'] ?: 'competicion.svg') ?>" alt=""></div>
          <div class="cuerpo-tarjeta">
            <div class="fecha"><?= e(formatearFecha($n['fecha'])) ?></div>
            <h3><?= e($n['titulo']) ?></h3>
            <p><?= e($n['resumen']) ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($resultadosGimnastas): ?>
      <h3 class="resultados-titulo"><?= t('nav_gimnastas') ?></h3>
      <div class="grid-plantilla">
        <?php foreach ($resultadosGimnastas as $g): ?>
        <a href="gimnasta.php?id=<?= (int)$g['id'] ?>" class="tarjeta-jugador" style="display:block;">
          <div class="marco-img"><img src="img/<?= e($g['foto'] ?: 'gimnasta-placeholder.svg') ?>" alt="<?= e($g['nombre']) ?>"></div>
          <div class="info">
            <div class="dorsal"><?= e($g['categoria']) ?></div>
            <h4><?= e($g['nombre']) ?></h4>
            <div class="posicion"><?= e($g['modalidad']) ?><?= $g['aparato'] ? ' · ' . e($g['aparato']) : '' ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($resultadosCompeticiones): ?>
      <h3 class="resultados-titulo"><?= t('nav_competiciones') ?></h3>
      <div class="grid-competiciones">
        <?php foreach ($resultadosCompeticiones as $c): ?>
        <article class="tarjeta-competicion">
          <a href="competicion.php?id=<?= (int)$c['id'] ?>" class="tarjeta-competicion-foto">
            <img src="img/<?= e($c['imagen_portada'] ?: 'competicion.svg') ?>" alt="">
          </a>
          <div class="tarjeta-competicion-cuerpo">
            <div class="fecha"><?= e(formatearFecha($c['fecha'])) ?></div>
            <h3><a href="competicion.php?id=<?= (int)$c['id'] ?>" style="color:inherit;"><?= e($c['nombre']) ?></a></h3>
            <p class="lugar"><?= e($c['lugar']) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
