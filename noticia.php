<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

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

$esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$urlActual = $esquema . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
$tituloCodificado = rawurlencode($noticia['titulo']);
$urlCodificada = rawurlencode($urlActual);

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <article class="detalle-noticia">
      <div class="fecha"><?= e(formatearFecha($noticia['fecha'])) ?></div>
      <h1><?= e($noticia['titulo']) ?></h1>
      <div class="marco-parallax marco-parallax-noticia">
        <img src="img/<?= e($noticia['imagen'] ?: 'competicion.svg') ?>" alt="" data-parallax="0.06" data-parallax-limite="26">
      </div>
      <div class="cuerpo">
        <?php foreach (explode("\n\n", $noticia['contenido']) as $parrafo): ?>
          <?php if (trim($parrafo) !== ''): ?>
            <p><?= nl2br(e($parrafo)) ?></p>
          <?php endif; ?>
        <?php endforeach; ?>
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

      <p style="margin-top:24px;"><a href="noticias.php"><?= t('volver_noticias') ?></a></p>
    </article>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
