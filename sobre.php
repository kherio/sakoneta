<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'sobre';
$tituloPagina = 'Sobre el club';

$ajustes = obtenerAjustes($pdo);
$historia = $ajustes['sobre_historia'] ?? '';
$palmares = array_filter(array_map('trim', explode("\n", $ajustes['sobre_palmares'] ?? '')));

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_sobre') ?></h2>
    </div>

    <div class="detalle-noticia" style="margin-bottom:40px;">
      <?php foreach (explode("\n\n", $historia) as $parrafo): ?>
        <?php if (trim($parrafo) !== ''): ?>
          <p><?= nl2br(e($parrafo)) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($palmares): ?>
    <h3><?= t('seccion_palmares') ?></h3>
    <ul class="lista-palmares">
      <?php foreach ($palmares as $logro): ?>
        <li><?= e($logro) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
