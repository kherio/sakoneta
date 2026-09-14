<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'dashboard';
$tituloPagina = 'Resumen';

$numNoticias = (int)$pdo->query('SELECT COUNT(*) c FROM noticias')->fetch()['c'];
$numGimnastas = (int)$pdo->query('SELECT COUNT(*) c FROM gimnastas')->fetch()['c'];
$numCompeticiones = (int)$pdo->query("SELECT COUNT(*) c FROM competiciones WHERE disputada = 0")->fetch()['c'];
$numMensajes = (int)$pdo->query('SELECT COUNT(*) c FROM mensajes_contacto WHERE leido = 0')->fetch()['c'];

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Hola, <?= htmlspecialchars($_SESSION['admin_usuario'] ?? 'admin') ?></h1>

<div class="tarjetas-resumen">
  <div class="tarjeta-resumen animar-scroll"><div class="num"><?= $numNoticias ?></div><div class="lbl">Noticias publicadas</div></div>
  <div class="tarjeta-resumen animar-scroll"><div class="num"><?= $numGimnastas ?></div><div class="lbl">Gimnastas dadas de alta</div></div>
  <div class="tarjeta-resumen animar-scroll"><div class="num"><?= $numCompeticiones ?></div><div class="lbl">Competiciones por disputar</div></div>
  <div class="tarjeta-resumen animar-scroll"><div class="num"><?= $numMensajes ?></div><div class="lbl">Mensajes sin leer</div></div>
</div>

<p style="color:#6B5A70;max-width:60ch;">
  Desde el menú de la izquierda puedes crear y editar noticias, gestionar el listado de gimnastas,
  actualizar el calendario de competiciones y consultar los mensajes recibidos desde el formulario de contacto.
</p>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
