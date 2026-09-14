<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'medios';
$tituloPagina = 'Fotos y vídeos subidos';

$carpeta = __DIR__ . '/../img/subidas';
$archivos = is_dir($carpeta) ? glob($carpeta . '/*.{jpg,jpeg,png,webp,mp4,webm,mov}', GLOB_BRACE) : [];
usort($archivos, fn($a, $b) => filemtime($b) <=> filemtime($a));

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Fotos y vídeos subidos</h1>
<p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-14px;">
  Todo lo que se ha subido desde noticias, gimnastas, categorías, competiciones y
  ajustes del sitio. Puedes borrar aquí lo que ya no necesites; si un archivo
  está en uso en algún sitio, al borrarlo se quitará también de allí.
</p>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Foto eliminada correctamente.</div>
<?php endif; ?>

<?php if (!$archivos): ?>
  <p>Todavía no se ha subido ninguna foto.</p>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;">
  <?php foreach ($archivos as $rutaCompleta):
      $nombreArchivo = basename($rutaCompleta);
      $rutaRelativa = 'subidas/' . $nombreArchivo;
      $usos = descripcionUsosArchivo($pdo, $rutaRelativa);
      $pesoKb = round(filesize($rutaCompleta) / 1024);
  ?>
    <div style="border:1.5px solid var(--borde);border-radius:8px;padding:10px;background:#fff;">
      <img src="../img/<?= e($rutaRelativa) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:8px;">
      <div style="font-size:12px;color:var(--gris);margin-bottom:6px;"><?= $pesoKb ?> KB</div>
      <?php if ($usos): ?>
        <div style="font-size:11.5px;color:var(--morado);margin-bottom:8px;line-height:1.4;">
          <?= e(implode(' · ', $usos)) ?>
        </div>
      <?php else: ?>
        <div style="font-size:11.5px;color:var(--gris);margin-bottom:8px;">Sin usar</div>
      <?php endif; ?>
      <a href="medio_borrar.php?archivo=<?= urlencode($rutaRelativa) ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:12.5px;"
         onclick="return confirm('<?= $usos ? '¡Esta foto está en uso! Si la borras, se quitará también de donde se está usando. ¿Continuar?' : '¿Borrar esta foto?' ?>');">
        Borrar
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
