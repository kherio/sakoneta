<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'buscar';

$consulta = trim($_GET['q'] ?? '');
// Límite de longitud (una búsqueda no necesita más de esto) y un
// mínimo de caracteres, para no lanzar un LIKE '%%' casi vacío que
// recorra prácticamente toda la tabla. Además, un límite de
// peticiones por IP: el buscador hace varias consultas por cada
// búsqueda, así que es más costoso que un formulario normal.
if (strlen($consulta) > 80) {
    $consulta = substr($consulta, 0, 80);
}
$consultaDemasiadoCorta = $consulta !== '' && strlen($consulta) < 2;
$demasiadasBusquedas = $consulta !== '' && superaLimiteEnvios($pdo, 'buscar', 30, 5);

// Modo ligero para el autocompletado del propio cuadro de búsqueda:
// solo unas pocas sugerencias, en JSON, sin renderizar toda la página.
if (isset($_GET['sugerencias'])) {
    header('Content-Type: application/json');
    $sugerencias = [];
    if ($consulta !== '' && !$consultaDemasiadoCorta && !$demasiadasBusquedas) {
        $comodin = '%' . $consulta . '%';

        $stmt = $pdo->prepare('SELECT titulo FROM noticias WHERE publicado = 1 AND titulo LIKE ? ORDER BY fecha DESC LIMIT 3');
        $stmt->execute([$comodin]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
            $sugerencias[] = ['texto' => $t, 'tipo' => 'Noticia'];
        }

        $stmt = $pdo->prepare('SELECT nombre FROM gimnastas WHERE nombre LIKE ? ORDER BY orden ASC LIMIT 3');
        $stmt->execute([$comodin]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) {
            $sugerencias[] = ['texto' => $n, 'tipo' => 'Gimnasta'];
        }

        $stmt = $pdo->prepare('SELECT nombre FROM competiciones WHERE nombre LIKE ? ORDER BY fecha DESC LIMIT 3');
        $stmt->execute([$comodin]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) {
            $sugerencias[] = ['texto' => $n, 'tipo' => 'Competición'];
        }
    }
    echo json_encode(array_slice($sugerencias, 0, 6));
    exit;
}

$resultadosNoticias = [];
$resultadosGimnastas = [];
$resultadosCompeticiones = [];
$maxResultadosPorTabla = 20;

if ($consulta !== '' && !$consultaDemasiadoCorta && !$demasiadasBusquedas) {
    $comodin = '%' . $consulta . '%';

    $stmt = $pdo->prepare('SELECT * FROM noticias WHERE publicado = 1 AND (titulo LIKE ? OR resumen LIKE ? OR contenido LIKE ?) ORDER BY fecha DESC LIMIT ' . $maxResultadosPorTabla);
    $stmt->execute([$comodin, $comodin, $comodin]);
    $resultadosNoticias = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM gimnastas WHERE nombre LIKE ? OR aparato LIKE ? ORDER BY orden ASC LIMIT ' . $maxResultadosPorTabla);
    $stmt->execute([$comodin, $comodin]);
    $resultadosGimnastas = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM competiciones WHERE nombre LIKE ? OR lugar LIKE ? ORDER BY fecha DESC LIMIT ' . $maxResultadosPorTabla);
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

    <form method="get" class="formulario-buscar" style="position:relative;" autocomplete="off">
      <input type="search" name="q" id="campo-busqueda" value="<?= e($consulta) ?>" placeholder="Busca noticias, gimnastas, competiciones..." autofocus>
      <?php if ($consulta !== ''): ?>
        <button type="button" id="boton-limpiar-busqueda" class="boton-limpiar-busqueda" aria-label="Borrar la búsqueda">✕</button>
      <?php endif; ?>
      <button type="submit" class="boton oro" style="padding:11px 24px;">Buscar</button>
      <div id="sugerencias-busqueda" class="sugerencias-busqueda"></div>
    </form>

    <?php if ($consulta === ''): ?>
      <p style="color:var(--gris-texto);margin-top:24px;">Escribe algo para buscar en todo el sitio.</p>
    <?php elseif ($consultaDemasiadoCorta): ?>
      <p style="color:var(--gris-texto);margin-top:24px;">Escribe al menos 2 caracteres para buscar.</p>
    <?php elseif ($demasiadasBusquedas): ?>
      <p style="color:var(--gris-texto);margin-top:24px;">Se han hecho demasiadas búsquedas seguidas desde aquí. Espera un momento y vuelve a intentarlo.</p>
    <?php elseif ($totalResultados === 0): ?>
      <div class="busqueda-vacia">
        <p style="font-size:17px;margin-bottom:6px;">No hemos encontrado nada para "<strong><?= e($consulta) ?></strong>".</p>
        <p style="color:var(--gris-texto);">Prueba con otra palabra, o revisa que esté bien escrita.</p>
      </div>
    <?php else: ?>

      <p class="contador-resultados"><?= $totalResultados ?> resultado<?= $totalResultados === 1 ? '' : 's' ?> para "<strong><?= e($consulta) ?></strong>"</p>

      <?php if ($resultadosNoticias): ?>
      <h3 class="resultados-titulo"><?= t('nav_noticias') ?> <span class="resultados-titulo-contador">(<?= count($resultadosNoticias) ?>)</span></h3>
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
      <h3 class="resultados-titulo"><?= t('nav_gimnastas') ?> <span class="resultados-titulo-contador">(<?= count($resultadosGimnastas) ?>)</span></h3>
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
      <h3 class="resultados-titulo"><?= t('nav_competiciones') ?> <span class="resultados-titulo-contador">(<?= count($resultadosCompeticiones) ?>)</span></h3>
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
