<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tituloPagina ?? 'Administración') ?> · <?= htmlspecialchars(nombreSitio()) ?></title>
<link rel="stylesheet" href="<?= versionArchivo('css/admin.css', 'admin/css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-menu">
    <div class="marca">
      <img src="../img/logo-sakoneta.png" alt="">
      <strong><?= htmlspecialchars(SITE_SHORT) ?></strong>
    </div>
    <a href="dashboard.php" class="<?= ($seccionActual ?? '') === 'dashboard' ? 'activo' : '' ?>">Resumen</a>

    <?php // --- Contenido del club: de lo más "de base" (categorías) a lo más editorial (noticias) --- ?>
    <?php if (in_array(rolActual(), ['administrador', 'editor'], true)): ?>
    <a href="categorias.php" class="admin-menu-separador <?= ($seccionActual ?? '') === 'categorias' ? 'activo' : '' ?>">Categorías</a>
    <a href="gimnastas.php" class="<?= ($seccionActual ?? '') === 'gimnastas' ? 'activo' : '' ?>">Gimnastas</a>
    <a href="competiciones.php" class="<?= ($seccionActual ?? '') === 'competiciones' ? 'activo' : '' ?>">Competiciones</a>
    <?php endif; ?>
    <?php if (in_array(rolActual(), ['administrador', 'editor', 'colaborador'], true)): ?>
    <a href="noticias.php" class="<?= (in_array(rolActual(), ['administrador', 'editor'], true) ? '' : 'admin-menu-separador ') . (($seccionActual ?? '') === 'noticias' ? 'activo' : '') ?>">Noticias</a>
    <?php endif; ?>

    <?php // --- Comunidad: lo que llega de fuera (mensajes, comentarios, suscripciones) --- ?>
    <?php if (in_array(rolActual(), ['administrador', 'moderador'], true)): ?>
    <a href="comentarios.php" class="admin-menu-separador <?= ($seccionActual ?? '') === 'comentarios' ? 'activo' : '' ?>">Comentarios</a>
    <a href="mensajes.php" class="<?= ($seccionActual ?? '') === 'mensajes' ? 'activo' : '' ?>">Mensajes de contacto</a>
    <?php endif; ?>
    <?php if (in_array(rolActual(), ['administrador', 'editor'], true)): ?>
    <a href="suscriptores.php" class="<?= in_array(rolActual(), ['administrador', 'moderador'], true) ? '' : 'admin-menu-separador ' ?><?= ($seccionActual ?? '') === 'suscriptores' ? 'activo' : '' ?>">Suscriptores</a>

    <?php // --- Medios y patrocinio --- ?>
    <a href="medios.php" class="admin-menu-separador <?= ($seccionActual ?? '') === 'medios' ? 'activo' : '' ?>">Fotos subidas</a>
    <a href="patrocinadores.php" class="<?= ($seccionActual ?? '') === 'patrocinadores' ? 'activo' : '' ?>">Patrocinadores</a>
    <?php endif; ?>

    <?php // --- Administración del sitio --- ?>
    <?php if (esAdministrador()): ?>
    <a href="usuarios.php" class="admin-menu-separador <?= ($seccionActual ?? '') === 'usuarios' ? 'activo' : '' ?>">Usuarios</a>
    <a href="ajustes.php" class="<?= ($seccionActual ?? '') === 'ajustes' ? 'activo' : '' ?>">Ajustes del sitio</a>
    <?php endif; ?>

    <?php // --- Cuenta --- ?>
    <a href="../index.php" target="_blank" class="admin-menu-separador">Ver sitio web ↗</a>
    <a href="cambiar_clave.php" class="<?= ($seccionActual ?? '') === 'cambiar_clave' ? 'activo' : '' ?>">Cambiar contraseña</a>
    <a href="logout.php" class="salir">Cerrar sesión (<?= e($_SESSION['admin_nombre'] ?? $_SESSION['admin_usuario'] ?? '') ?>)</a>
  </aside>
  <main class="admin-contenido">
