<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

$pdo = getDb();
$seccionActual = 'patrocinadores';
$tituloPagina = 'Editar patrocinador';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM patrocinadores WHERE id = ?');
$stmt->execute([$id]);
$patrocinador = $stmt->fetch();

if (!$patrocinador) {
    redirigir('patrocinadores.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'El logo es demasiado grande para el límite de subida configurado en el servidor.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $nombre = trim($_POST['nombre'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $orden = (int)($_POST['orden'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre no puede estar vacío.';
    } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'El enlace no parece una URL válida (debe empezar por http:// o https://).';
    } else {
        $errorLogo = null;
        $nuevoLogo = procesarImagenSubida('logo', $errorLogo);

        if ($errorLogo) {
            $error = $errorLogo;
        } else {
            $logoFinal = $nuevoLogo ?: $patrocinador['logo'];
            $stmt = $pdo->prepare('UPDATE patrocinadores SET nombre=?, logo=?, url=?, orden=? WHERE id=?');
            $stmt->execute([$nombre, $logoFinal, $url ?: null, $orden, $id]);
            redirigir('patrocinadores.php?ok=1');
        }
    }
    $patrocinador['nombre'] = $nombre;
    $patrocinador['url'] = $url;
    $patrocinador['orden'] = $orden;
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Editar patrocinador</h1>

<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($patrocinador['nombre']) ?>" required>
  </div>

  <?php if (!empty($patrocinador['logo'])): ?>
    <div style="margin-bottom:14px;">
      <img src="../img/<?= e($patrocinador['logo']) ?>" alt="" style="max-width:200px;max-height:100px;object-fit:contain;background:#fff;border:1px solid var(--borde);border-radius:8px;padding:10px;">
    </div>
  <?php endif; ?>
  <div class="campo">
    <label for="logo">Logo (JPG, PNG o WEBP, máx. 20 MB; idealmente con fondo transparente o blanco)</label>
    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
  </div>

  <div class="campo">
    <label for="url">Enlace a su web (opcional)</label>
    <input type="url" id="url" name="url" value="<?= e($patrocinador['url'] ?? '') ?>" placeholder="https://...">
  </div>

  <div class="campo">
    <label for="orden">Orden</label>
    <input type="number" id="orden" name="orden" value="<?= e((string)$patrocinador['orden']) ?>">
  </div>

  <button type="submit" class="btn">Guardar</button>
  <a href="patrocinadores.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
