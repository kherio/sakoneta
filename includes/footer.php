<footer>
  <div class="contenedor">
    <div>
      <h4><?= e(SITE_NAME) ?></h4>
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
  </div>
  <div class="footer-nota">© <?= date('Y') ?> <?= e(SITE_NAME) ?> — sitio de ejemplo generado con fines de demostración.</div>
</footer>
<script src="js/main.js"></script>
</body>
</html>
