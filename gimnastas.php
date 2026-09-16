<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'gimnastas';
$tituloPagina = 'Gimnastas';

$gimnastas = $pdo->query('SELECT * FROM gimnastas ORDER BY orden ASC')->fetchAll();
$categoriasPresentes = [];
foreach ($gimnastas as $g) {
    if ($g['categoria'] && !in_array($g['categoria'], $categoriasPresentes, true)) {
        $categoriasPresentes[] = $g['categoria'];
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_gimnastas') ?></h2>
    </div>

    <?php if (!$gimnastas): ?>
      <p><?= t('sin_gimnastas') ?></p>
    <?php else: ?>
    <?php if (count($categoriasPresentes) > 1): ?>
    <div class="filtro-categorias" data-filtro-objetivo="grid-filtrable-gimnastas" data-modo="unico">
      <button type="button" class="activo" data-categoria="todas"><?= t('filtro_todas') ?></button>
      <?php foreach ($categoriasPresentes as $cat): ?>
        <button type="button" data-categoria="<?= e($cat) ?>"><?= e($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid-plantilla" id="grid-filtrable-gimnastas">
      <?php foreach ($gimnastas as $g): ?>
      <a href="gimnasta.php?id=<?= (int)$g['id'] ?>" class="tarjeta-jugador animar-scroll" style="display:block;" data-categoria="<?= e($g['categoria']) ?>">
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
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
