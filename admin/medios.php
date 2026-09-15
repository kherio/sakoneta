<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

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
  ajustes del sitio. Puedes borrar aquí lo que ya no necesites (una a una, o
  seleccionando varias a la vez); si un archivo está en uso en algún sitio, al
  borrarlo se quitará también de allí.
</p>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>

<?php if (!$archivos): ?>
  <p>Todavía no se ha subido ninguna foto.</p>
<?php else: ?>
<form method="post" action="medios_borrar_lote.php" id="form-medios">
  <?= campoCsrf() ?>
  <div class="barra-superior">
    <label style="font-size:13.5px;display:flex;align-items:center;gap:6px;">
      <input type="checkbox" id="seleccionar-todos" style="width:auto;"> Seleccionar todos
    </label>
    <button type="submit" class="btn peligro" id="btn-borrar-seleccion" disabled
            onclick="return confirm('¿Borrar todos los archivos seleccionados? Si alguno está en uso, se quitará también de donde se esté usando.');">
      Borrar seleccionados (<span id="contador-seleccion">0</span>)
    </button>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;">
    <?php foreach ($archivos as $rutaCompleta):
        $nombreArchivo = basename($rutaCompleta);
        $rutaRelativa = 'subidas/' . $nombreArchivo;
        $usos = descripcionUsosArchivo($pdo, $rutaRelativa);
        $pesoKb = round(filesize($rutaCompleta) / 1024);
        $esVideo = in_array(strtolower(pathinfo($rutaRelativa, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov'], true);
    ?>
      <div class="tarjeta-medio" style="border:1.5px solid var(--borde);border-radius:8px;padding:10px;background:#fff;position:relative;">
        <label style="position:absolute;top:16px;left:16px;background:rgba(255,255,255,.9);border-radius:6px;padding:2px;z-index:1;">
          <input type="checkbox" name="archivos[]" value="<?= e($rutaRelativa) ?>" class="check-medio" style="width:18px;height:18px;">
        </label>
        <?php if ($esVideo): ?>
          <video src="../img/<?= e($rutaRelativa) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:8px;background:#000;" muted></video>
        <?php else: ?>
          <img src="../img/<?= e($rutaRelativa) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:8px;">
        <?php endif; ?>
        <div style="font-size:12px;color:var(--gris);margin-bottom:6px;"><?= $pesoKb ?> KB</div>
        <?php if ($usos): ?>
          <div style="font-size:11.5px;color:var(--morado);margin-bottom:8px;line-height:1.4;">
            <?= e(implode(' · ', $usos)) ?>
          </div>
        <?php else: ?>
          <div style="font-size:11.5px;color:var(--gris);margin-bottom:8px;">Sin usar</div>
        <?php endif; ?>
        <button type="submit" formaction="medio_borrar.php" name="archivo" value="<?= e($rutaRelativa) ?>" class="borrar" style="font-size:12.5px;"
           onclick="return confirm('<?= $usos ? '¡Esta foto está en uso! Si la borras, se quitará también de donde se está usando. ¿Continuar?' : '¿Borrar esta foto?' ?>');">
          Borrar
        </button>
      </div>
    <?php endforeach; ?>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
