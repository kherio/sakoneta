<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'competiciones';
$tituloPagina = 'Próximas competiciones';

$competiciones = $pdo->query("
    SELECT c.*, (
        SELECT GROUP_CONCAT(categoria, ',') FROM (
            SELECT cc.categoria FROM competicion_categorias cc
            JOIN categorias cat ON cat.nombre = cc.categoria
            WHERE cc.competicion_id = c.id
            ORDER BY cat.orden ASC
        )
    ) AS categorias_lista,
    EXISTS (
        SELECT 1 FROM competicion_documentos cd
        WHERE cd.competicion_id = c.id AND cd.archivo LIKE '%.pdf'
    ) AS tiene_pdf
    FROM competiciones c
    WHERE c.disputada = 0
    ORDER BY c.fecha ASC
")->fetchAll();
$categoriasPresentes = [];
foreach ($competiciones as $c) {
    foreach (explode(',', $c['categorias_lista'] ?? '') as $cat) {
        if ($cat !== '' && !in_array($cat, $categoriasPresentes, true)) {
            $categoriasPresentes[] = $cat;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion seccion-competiciones">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_competiciones') ?></h2>
      <a href="resultados.php">Ver competiciones anteriores →</a>
    </div>

    <?php if (!$competiciones): ?>
      <p>No hay ninguna competición pendiente por ahora. <a href="resultados.php">Consulta los resultados de las ya disputadas</a>.</p>
    <?php else: ?>
    <div class="vista-cambio" role="tablist" aria-label="Vista de las competiciones">
      <span class="vista-cambio-pastilla" id="vista-cambio-pastilla" aria-hidden="true"></span>
      <button type="button" id="pestana-lista" role="tab" aria-selected="true" aria-controls="vista-lista-competiciones" tabindex="0" class="activo" data-vista="lista">☰ Lista</button>
      <button type="button" id="pestana-calendario" role="tab" aria-selected="false" aria-controls="vista-calendario-competiciones" tabindex="-1" data-vista="calendario">▦ Calendario</button>
    </div>

    <script type="application/json" id="datos-calendario-competiciones"><?= json_encode(array_map(fn($c) => [
        'id' => (int)$c['id'],
        'fecha' => $c['fecha'],
        'nombre' => campoIdioma($c, 'nombre'),
    ], $competiciones)) ?></script>
    <div id="vista-calendario-competiciones" role="tabpanel" aria-labelledby="pestana-calendario" style="display:none;"></div>

    <div id="vista-lista-competiciones" role="tabpanel" aria-labelledby="pestana-lista">
    <?php if (count($categoriasPresentes) > 1): ?>
    <div class="filtro-categorias" data-filtro-objetivo="grid-filtrable-competiciones">
      <button type="button" class="activo" data-categoria="todas"><?= t('filtro_todas') ?></button>
      <?php foreach ($categoriasPresentes as $cat): ?>
        <button type="button" data-categoria="<?= e($cat) ?>"><span class="punto-color-categoria" style="background:<?= e(colorCategoria($pdo, $cat)) ?>;"></span><?= e($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid-competiciones" id="grid-filtrable-competiciones">
      <?php foreach ($competiciones as $c): ?>
      <article class="tarjeta-competicion animar-scroll" data-categoria="<?= e($c['categorias_lista'] ?? '') ?>">
        <a href="competicion.php?id=<?= (int)$c['id'] ?>" class="tarjeta-competicion-enlace">
        <div class="tarjeta-competicion-foto">
          <img src="img/<?= e(rutaMiniatura($c['imagen_portada']) ?: 'competicion.svg') ?>" srcset="img/<?= e(rutaMiniatura($c['imagen_portada']) ?: 'competicion.svg') ?> 480w, img/<?= e($c['imagen_portada'] ?: 'competicion.svg') ?> 1600w" sizes="(max-width: 640px) 90vw, (max-width: 1024px) 45vw, 360px" alt="" data-parallax="0.05" data-parallax-limite="16" loading="lazy">
          <span class="tarjeta-competicion-categoria" style="background:<?= e(colorCategoria($pdo, explode(',', $c['categorias_lista'] ?? '')[0] ?? null)) ?>;"><?= e(str_replace(',', ' · ', $c['categorias_lista'] ?? '')) ?></span>
          <?php if ($c['tiene_pdf']): ?><span class="tarjeta-competicion-pdf">PDF</span><?php endif; ?>
        </div>
        <div class="tarjeta-competicion-cuerpo">
          <div class="fecha"><?= e(formatearFecha($c['fecha'])) ?></div>
          <h3><?= e(campoIdioma($c, 'nombre')) ?></h3>
          <p class="lugar"><?= e(campoIdioma($c, 'lugar')) ?></p>
          <?php if ($c['disputada']): ?>
            <div class="resultado"><?= e(campoIdioma($c, 'resultado') ?: t('disputada')) ?></div>
            <span class="pill jugado"><?= t('disputada') ?></span>
          <?php else: ?>
            <span class="pill pendiente"><?= t('pendiente') ?></span>
          <?php endif; ?>
          <span class="tarjeta-cta">Ver detalles <span class="tarjeta-flecha">→</span></span>
        </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
