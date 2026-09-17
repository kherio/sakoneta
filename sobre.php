<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'sobre';
$tituloPagina = 'Sobre el club';

$ajustes = obtenerAjustes($pdo);
$historia = $ajustes['sobre_historia'] ?? '';
$historiaEnAjustes = trim($historia) !== '';

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_sobre') ?></h2>
    </div>

    <?php if ($historiaEnAjustes): ?>
    <div class="detalle-noticia" style="margin-bottom:40px;">
      <?php foreach (explode("\n\n", $historia) as $parrafo): ?>
        <?php if (trim($parrafo) !== ''): ?>
          <p><?= nl2br(e($parrafo)) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="historia-acceso">
      <div class="historia-acceso-emblema">🏆</div>
      <div class="historia-acceso-cuerpo">
        <div class="bloque-etiqueta bloque-etiqueta-oro">Historia y palmarés</div>
        <h3>Desde 1987, escribiendo la historia de la gimnasia rítmica vasca</h3>
        <p>Campeonas de España, conjuntos de Primera Categoría y gimnastas que
          han llegado a vestir el maillot de la selección española — todo
          empezó, y sigue empezando cada temporada, en la escuela del club.</p>
        <div class="historia-acceso-datos">
          <div><strong>1987</strong><span>Año de fundación</span></div>
          <div><strong>9+</strong><span>Podios en un solo Campeonato de España</span></div>
          <div><strong>3</strong><span>Gimnastas en la selección española</span></div>
        </div>
        <a href="historia.php" class="boton oro">Ver historia y palmarés completo →</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
