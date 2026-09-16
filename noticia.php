<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'noticias';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM noticias WHERE id = ? AND publicado = 1');
$stmt->execute([$id]);
$noticia = $stmt->fetch();

if (!$noticia) {
    http_response_code(404);
    $tituloPagina = 'Noticia no encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<section class="seccion"><div class="contenedor"><p>Esta noticia no existe o ha sido retirada. <a href="noticias.php">Volver a noticias</a>.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$tituloPagina = $noticia['titulo'];
$descripcionOG = $noticia['resumen'];
$origenSeguro = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST);
$imagenOG = $origenSeguro . '/img/' . ($noticia['imagen'] ?: 'competicion.svg');
$migas = [['texto' => t('nav_noticias'), 'url' => 'noticias.php'], ['texto' => $noticia['titulo']]];

$urlActual = $origenSeguro . ($_SERVER['REQUEST_URI'] ?? '');
$tituloCodificado = rawurlencode($noticia['titulo']);
$urlCodificada = rawurlencode($urlActual);

$stmtFotos = $pdo->prepare('SELECT * FROM noticia_fotos WHERE noticia_id = ? ORDER BY orden ASC');
$stmtFotos->execute([$id]);
$fotosNoticia = $stmtFotos->fetchAll();
$otrasFotosNoticia = array_filter($fotosNoticia, function ($f) use ($noticia) { return $f['archivo'] !== $noticia['imagen']; });

$yaLeGustaEstaNoticia = isset($_COOKIE['like_noticia_' . $id]);

$comentarios = $pdo->prepare("SELECT * FROM comentarios WHERE noticia_id = ? AND estado = 'aprobado' ORDER BY fecha ASC");
$comentarios->execute([$id]);
$comentarios = $comentarios->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-noticia animar-scroll">
  <div class="franja-portada-imagen franja-noticia-imagen" data-parallax="0.1" data-parallax-limite="50" style="background-image:url('img/<?= e($noticia['imagen'] ?: 'competicion.svg') ?>');background-position:<?= e(posicionCss($noticia['imagen_posicion'] ?? null)) ?>;"></div>
  <div class="contenedor franja-portada-texto">
    <div class="fecha franja-noticia-fecha"><?= e(formatearFecha($noticia['fecha'])) ?></div>
    <h1 class="franja-noticia-titulo"><?= e($noticia['titulo']) ?></h1>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <article class="detalle-noticia detalle-noticia-sin-cabecera">
      <div class="cuerpo">
        <?php foreach (explode("\n\n", $noticia['contenido']) as $parrafo): ?>
          <?php if (trim($parrafo) !== ''): ?>
            <p><?= nl2br(e($parrafo)) ?></p>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($otrasFotosNoticia): ?>
      <div style="margin-top:32px;">
        <h3><?= t('seccion_galeria') ?></h3>
        <div class="galeria-parallax galeria-parallax-noticia">
          <?php foreach ($otrasFotosNoticia as $f): ?>
            <?php if ($f['tipo'] === 'video'): ?>
              <div class="galeria-video">
                <video controls preload="metadata"><source src="img/<?= e($f['archivo']) ?>"></video>
              </div>
            <?php else: ?>
              <div class="galeria-parallax-item marco-parallax animar-scroll">
                <img src="img/<?= e($f['archivo']) ?>" alt="" data-parallax="0.06" data-parallax-limite="18">
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="me-gusta-noticia">
        <button type="button" id="boton-me-gusta" class="boton-me-gusta <?= $yaLeGustaEstaNoticia ? 'activo' : '' ?>" data-id="<?= (int)$noticia['id'] ?>">
          <span class="corazon">♥</span>
          <span id="contador-me-gusta"><?= (int)$noticia['likes'] ?></span>
          <span class="me-gusta-etiqueta">Me gusta</span>
        </button>
      </div>

      <div class="compartir-noticia">
        <p><?= t('compartir') ?></p>
        <div class="compartir-botones">
          <a href="https://wa.me/?text=<?= $tituloCodificado ?>%20-%20<?= $urlCodificada ?>" target="_blank" rel="noopener">WhatsApp</a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $urlCodificada ?>" target="_blank" rel="noopener">Facebook</a>
          <a href="https://twitter.com/intent/tweet?text=<?= $tituloCodificado ?>&url=<?= $urlCodificada ?>" target="_blank" rel="noopener">X</a>
          <button type="button" class="boton-copiar-enlace" data-url="<?= e($urlActual) ?>" data-texto-copiar="<?= e(t('copiar_enlace')) ?>" data-texto-copiado="<?= e(t('enlace_copiado')) ?>"><?= t('copiar_enlace') ?></button>
        </div>
      </div>

      <div class="seccion-comentarios">
        <h3><?= count($comentarios) ?> comentario<?= count($comentarios) === 1 ? '' : 's' ?></h3>

        <?php if ($comentarios): ?>
        <ul class="lista-comentarios">
          <?php foreach ($comentarios as $c): ?>
          <li>
            <div class="comentario-cabecera">
              <strong><?= e($c['nombre']) ?></strong>
              <span class="comentario-fecha"><?= e(formatearFecha($c['fecha'])) ?></span>
            </div>
            <p><?= nl2br(e($c['mensaje'])) ?></p>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
          <p style="color:var(--gris-texto);font-size:14.5px;">Todavía no hay comentarios. ¡Sé el primero!</p>
        <?php endif; ?>

        <?php if (isset($_GET['comentario']) && $_GET['comentario'] === 'enviado'): ?>
          <div class="aviso-ok">Gracias por tu comentario. Se publicará en cuanto lo revisemos.</div>
        <?php elseif (isset($_GET['comentario']) && $_GET['comentario'] === 'error'): ?>
          <div class="aviso-ok" style="background:#FBE4E4;color:#9A2A2A;">Revisa los datos del formulario e inténtalo de nuevo.</div>
        <?php endif; ?>

        <form method="post" action="comentar.php" class="formulario formulario-comentario">
          <?= campoCsrf() ?>
          <input type="hidden" name="noticia_id" value="<?= (int)$noticia['id'] ?>">
          <input type="hidden" name="volver" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <!-- Campo señuelo anti-spam: invisible para personas, tentador para bots -->
          <div class="campo-senuelo" aria-hidden="true">
            <label for="web">Deja este campo vacío</label>
            <input type="text" id="web" name="web" tabindex="-1" autocomplete="off">
          </div>

          <label for="comentario_nombre">Nombre</label>
          <input type="text" id="comentario_nombre" name="nombre" maxlength="100" required>

          <label for="comentario_email">Correo electrónico (no se publica)</label>
          <input type="email" id="comentario_email" name="email" maxlength="190">

          <label for="comentario_mensaje">Comentario</label>
          <textarea id="comentario_mensaje" name="mensaje" maxlength="2000" required></textarea>

          <button type="submit" class="boton oro" style="margin-top:14px;">Enviar comentario</button>
        </form>
      </div>

      <p style="margin-top:24px;"><a href="noticias.php"><?= t('volver_noticias') ?></a></p>
    </article>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
