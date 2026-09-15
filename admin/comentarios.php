<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','moderador']);

$pdo = getDb();
$seccionActual = 'comentarios';
$tituloPagina = 'Comentarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id && isset($_POST['aprobar'])) {
        $pdo->prepare("UPDATE comentarios SET estado = 'aprobado' WHERE id = ?")->execute([$id]);
    } elseif ($id && isset($_POST['rechazar'])) {
        $pdo->prepare("UPDATE comentarios SET estado = 'rechazado' WHERE id = ?")->execute([$id]);
    } elseif ($id && isset($_POST['borrar'])) {
        $pdo->prepare('DELETE FROM comentarios WHERE id = ?')->execute([$id]);
    }
    redirigir('comentarios.php' . (isset($_GET['estado']) ? '?estado=' . urlencode($_GET['estado']) : ''));
}

$filtro = $_GET['estado'] ?? 'pendiente';
if (!in_array($filtro, ['pendiente', 'aprobado', 'rechazado', 'todos'], true)) {
    $filtro = 'pendiente';
}

if ($filtro === 'todos') {
    $comentarios = $pdo->query('
        SELECT comentarios.*, noticias.titulo AS noticia_titulo
        FROM comentarios JOIN noticias ON noticias.id = comentarios.noticia_id
        ORDER BY comentarios.fecha DESC
    ')->fetchAll();
} else {
    $stmt = $pdo->prepare('
        SELECT comentarios.*, noticias.titulo AS noticia_titulo
        FROM comentarios JOIN noticias ON noticias.id = comentarios.noticia_id
        WHERE comentarios.estado = ?
        ORDER BY comentarios.fecha DESC
    ');
    $stmt->execute([$filtro]);
    $comentarios = $stmt->fetchAll();
}

$numPendientes = (int)$pdo->query("SELECT COUNT(*) FROM comentarios WHERE estado = 'pendiente'")->fetchColumn();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Comentarios<?= $numPendientes ? ' <span style="color:var(--rojo);font-size:16px;">(' . $numPendientes . ' pendientes)</span>' : '' ?></h1>

<div class="filtro-categorias" style="margin-bottom:22px;">
  <?php foreach (['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados', 'todos' => 'Todos'] as $valor => $etiqueta): ?>
    <a href="comentarios.php?estado=<?= $valor ?>" class="btn <?= $filtro === $valor ? '' : 'secundario' ?>" style="text-decoration:none;"><?= $etiqueta ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$comentarios): ?>
  <p>No hay comentarios en esta categoría.</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px;">
  <?php foreach ($comentarios as $c): ?>
  <div style="background:#fff;border:1px solid var(--borde);border-radius:8px;padding:16px 20px;">
    <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
      <div>
        <strong><?= e($c['nombre']) ?></strong>
        <?php if ($c['email']): ?><span style="color:var(--gris);font-size:13px;"> · <?= e($c['email']) ?></span><?php endif; ?>
        <span style="color:var(--gris);font-size:13px;"> · <?= e($c['fecha']) ?></span>
      </div>
      <a href="../noticia.php?id=<?= (int)$c['noticia_id'] ?>" target="_blank" style="font-size:13px;color:var(--morado);">En: <?= e($c['noticia_titulo']) ?> ↗</a>
    </div>
    <p style="margin:0 0 12px;font-size:14.5px;"><?= nl2br(e($c['mensaje'])) ?></p>
    <div class="acciones">
      <?php if ($c['estado'] !== 'aprobado'): ?>
      <form method="post">
        <?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" name="aprobar" value="1" class="editar">Aprobar</button>
      </form>
      <?php endif; ?>
      <?php if ($c['estado'] !== 'rechazado'): ?>
      <form method="post">
        <?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" name="rechazar" value="1" class="borrar">Rechazar</button>
      </form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('¿Borrar este comentario definitivamente?');">
        <?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" name="borrar" value="1" class="borrar">Borrar</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
