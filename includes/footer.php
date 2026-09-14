<?php
$patrocinadoresFooter = [];
try {
    $patrocinadoresFooter = getDb()->query('SELECT * FROM patrocinadores ORDER BY orden ASC')->fetchAll();
} catch (Throwable $e) {
    $patrocinadoresFooter = [];
}
?>
<?php if ($patrocinadoresFooter): ?>
<div class="franja-patrocinadores">
  <div class="contenedor">
    <span class="patrocinadores-etiqueta">Con la colaboración de</span>
    <div class="patrocinadores-logos">
      <?php foreach ($patrocinadoresFooter as $p): ?>
        <?php if ($p['url']): ?>
          <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener" title="<?= e($p['nombre']) ?>">
        <?php endif; ?>
        <?php if ($p['logo']): ?>
          <img src="img/<?= e($p['logo']) ?>" alt="<?= e($p['nombre']) ?>">
        <?php else: ?>
          <span class="patrocinador-texto"><?= e($p['nombre']) ?></span>
        <?php endif; ?>
        <?php if ($p['url']): ?></a><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<footer>
  <div class="contenedor pie-grid">
    <div>
      <h4><?= e(nombreSitio()) ?></h4>
      <p style="max-width:32ch;font-size:14px;">Polideportivo de Sakoneta. Escuela y competición de gimnasia rítmica. Edita esta dirección y el resto de datos de contacto desde el panel o el código antes de publicar.</p>
    </div>
    <div>
      <h4><?= t('footer_secciones') ?></h4>
      <a href="noticias.php"><?= t('nav_noticias') ?></a>
      <a href="gimnastas.php"><?= t('nav_gimnastas') ?></a>
      <a href="competiciones.php"><?= t('nav_competiciones') ?></a>
      <a href="sobre.php"><?= t('nav_sobre') ?></a>
    </div>
    <div>
      <h4><?= t('footer_club') ?></h4>
      <a href="contacto.php"><?= t('nav_contacto') ?></a>
      <a href="admin/index.php"><?= t('nav_acceso') ?> administración</a>
    </div>
    <div>
      <h4>Recibe nuestras noticias</h4>
      <?php if (isset($_GET['suscrito']) && $_GET['suscrito'] === '1'): ?>
        <p class="suscripcion-aviso">¡Gracias! Ya estás suscrito.</p>
      <?php elseif (isset($_GET['suscrito']) && $_GET['suscrito'] === 'error'): ?>
        <p class="suscripcion-aviso suscripcion-aviso-error">Revisa el correo, no parece válido.</p>
      <?php else: ?>
        <p style="font-size:13.5px;color:#C9D3CE;margin:0 0 10px;">Un correo cuando publiquemos algo nuevo, nada más.</p>
      <?php endif; ?>
      <form method="post" action="suscribir.php" class="formulario-suscripcion">
        <input type="hidden" name="volver" value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
        <input type="email" name="email" placeholder="tu@email.com" required>
        <button type="submit">Suscribirme</button>
      </form>
    </div>
  </div>
  <div class="footer-nota">© <?= date('Y') ?> <?= e(nombreSitio()) ?> — sitio de ejemplo generado con fines de demostración.</div>
</footer>
<script src="js/main.js"></script>
</body>
</html>
