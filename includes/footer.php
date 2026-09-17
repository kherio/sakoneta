<?php
$patrocinadoresFooter = [];
$ajustesPie = [];
try {
    $pdoPie = getDb();
    $patrocinadoresFooter = $pdoPie->query('SELECT * FROM patrocinadores ORDER BY orden ASC')->fetchAll();
    $ajustesPie = obtenerAjustes($pdoPie);
} catch (Throwable $e) {
    $patrocinadoresFooter = [];
    $ajustesPie = [];
}
$pieTitulo = $ajustesPie['pie_titulo'] ?? '';
$pieTexto = $ajustesPie['pie_texto'] ?? '';
?>
<?php if ($patrocinadoresFooter): ?>
<div class="franja-patrocinadores">
  <div class="contenedor">
    <span class="patrocinadores-etiqueta">Con la colaboración de</span>
  </div>
  <div class="carrusel-patrocinadores">
    <div class="carrusel-patrocinadores-pista">
      <?php for ($vuelta = 0; $vuelta < 2; $vuelta++): ?>
        <?php foreach ($patrocinadoresFooter as $p): ?>
          <div class="carrusel-patrocinador-item">
            <?php $urlSegura = $p['url'] && esUrlPermitida($p['url']); ?>
            <?php if ($urlSegura): ?>
              <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener" title="<?= e($p['nombre']) ?> (se abre en una pestaña nueva)">
            <?php endif; ?>
            <?php if ($p['logo']): ?>
              <img src="img/<?= e($p['logo']) ?>" alt="<?= e($p['nombre']) ?>" <?= $vuelta === 1 ? 'aria-hidden="true"' : '' ?>>
            <?php else: ?>
              <span class="patrocinador-texto"><?= e($p['nombre']) ?></span>
            <?php endif; ?>
            <?php if ($urlSegura): ?><span class="oculto-visual"> (se abre en una pestaña nueva)</span></a><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>
</div>
<?php endif; ?>

</div>

<footer>
  <div class="contenedor pie-grid">
    <div>
      <h4><?= e($pieTitulo ?: nombreSitio()) ?></h4>
      <p style="max-width:32ch;font-size:14px;"><?= nl2br(e($pieTexto ?: 'Polideportivo de Sakoneta. Escuela y competición de gimnasia rítmica. Edita esta dirección y el resto de datos de contacto desde el panel o el código antes de publicar.')) ?></p>
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
    <div id="suscripcion">
      <h4>Recibe nuestras noticias</h4>
      <?php if (isset($_GET['suscrito']) && $_GET['suscrito'] === '1'): ?>
        <p class="suscripcion-aviso">¡Gracias! Ya estás suscrito.</p>
      <?php elseif (isset($_GET['suscrito']) && $_GET['suscrito'] === 'error'): ?>
        <p class="suscripcion-aviso suscripcion-aviso-error">Revisa el correo, no parece válido.</p>
      <?php else: ?>
        <p style="font-size:13.5px;color:#C9D3CE;margin:0 0 10px;">Un correo cuando publiquemos algo nuevo, nada más.</p>
      <?php endif; ?>
      <form method="post" action="suscribir.php" class="formulario-suscripcion">
        <?= campoCsrf() ?>
        <input type="hidden" name="volver" value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
        <input type="email" name="email" placeholder="tu@email.com" required>
        <button type="submit">Suscribirme</button>
      </form>
    </div>
  </div>
  <div class="footer-nota">© <?= date('Y') ?> <?= e(nombreSitio()) ?> — sitio de ejemplo generado con fines de demostración.</div>
</footer>

<?php if (($paginaActual ?? '') !== 'unete'): ?>
<a href="unete.php" class="unete-flotante">🤸 Únete a Sakoneta</a>
<?php endif; ?>

<div class="lightbox-galeria" id="lightbox-galeria" aria-hidden="true">
  <button type="button" class="lightbox-galeria-cerrar" id="lightbox-cerrar" aria-label="Cerrar">✕</button>
  <button type="button" class="lightbox-galeria-anterior" id="lightbox-anterior" aria-label="Foto anterior">‹</button>
  <img src="" alt="" class="lightbox-galeria-img" id="lightbox-img">
  <button type="button" class="lightbox-galeria-siguiente" id="lightbox-siguiente" aria-label="Foto siguiente">›</button>
  <div class="lightbox-galeria-contador" id="lightbox-contador"></div>
</div>

<script src="<?= versionArchivo('js/main.js') ?>"></script>
</body>
</html>
