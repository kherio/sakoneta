<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tituloPagina ?? 'Administración') ?> · <?= htmlspecialchars(nombreSitio()) ?></title>
<link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-menu">
    <div class="marca">
      <img src="../img/logo-sakoneta.png" alt="">
      <strong><?= htmlspecialchars(SITE_SHORT) ?></strong>
    </div>
    <a href="dashboard.php" class="<?= ($seccionActual ?? '') === 'dashboard' ? 'activo' : '' ?>">Resumen</a>
    <a href="noticias.php" class="<?= ($seccionActual ?? '') === 'noticias' ? 'activo' : '' ?>">Noticias</a>
    <a href="gimnastas.php" class="<?= ($seccionActual ?? '') === 'gimnastas' ? 'activo' : '' ?>">Gimnastas</a>
    <a href="categorias.php" class="<?= ($seccionActual ?? '') === 'categorias' ? 'activo' : '' ?>">Categorías</a>
    <a href="competiciones.php" class="<?= ($seccionActual ?? '') === 'competiciones' ? 'activo' : '' ?>">Competiciones</a>
    <a href="mensajes.php" class="<?= ($seccionActual ?? '') === 'mensajes' ? 'activo' : '' ?>">Mensajes de contacto</a>
    <a href="medios.php" class="<?= ($seccionActual ?? '') === 'medios' ? 'activo' : '' ?>">Fotos subidas</a>
    <a href="patrocinadores.php" class="<?= ($seccionActual ?? '') === 'patrocinadores' ? 'activo' : '' ?>">Patrocinadores</a>
    <a href="suscriptores.php" class="<?= ($seccionActual ?? '') === 'suscriptores' ? 'activo' : '' ?>">Suscriptores</a>
    <a href="ajustes.php" class="<?= ($seccionActual ?? '') === 'ajustes' ? 'activo' : '' ?>">Ajustes del sitio</a>
    <a href="../index.php" target="_blank">Ver sitio web ↗</a>
    <a href="cambiar_clave.php" class="<?= ($seccionActual ?? '') === 'cambiar_clave' ? 'activo' : '' ?>">Cambiar contraseña</a>
    <a href="logout.php" class="salir">Cerrar sesión</a>
  </aside>
  <main class="admin-contenido">
