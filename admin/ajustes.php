<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'ajustes';
$tituloPagina = 'Ajustes del sitio';

$ajustes = obtenerAjustes($pdo);
$error = '';
$guardado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'La foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con una imagen más ligera (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $splashActivo = isset($_POST['splash_activo']) ? 1 : 0;
    $inicioImagenTitulo = trim($_POST['inicio_imagen_titulo'] ?? '');
    $sobreHistoria = trim($_POST['sobre_historia'] ?? '');
    $sobrePalmares = trim($_POST['sobre_palmares'] ?? '');
    $heroKicker = trim($_POST['hero_kicker'] ?? '');
    $heroTitulo = trim($_POST['hero_titulo'] ?? '');
    $heroTexto = trim($_POST['hero_texto'] ?? '');

    $errorSplash = null;
    $nuevoSplash = procesarImagenSubida('splash_imagen', $errorSplash);

    $errorInicio = null;
    $nuevaInicio = procesarImagenSubida('inicio_imagen', $errorInicio);

    if ($errorSplash) {
        $error = 'Foto de bienvenida: ' . $errorSplash;
    } elseif ($errorInicio) {
        $error = 'Foto de portada: ' . $errorInicio;
    } else {
        $splashImagenFinal = $nuevoSplash ?: $ajustes['splash_imagen'];

        if ($splashActivo && !$splashImagenFinal) {
            $error = 'Para activar la pantalla de bienvenida, sube antes una foto para ella.';
        } else {
            $inicioImagenFinal = $nuevaInicio ?: $ajustes['inicio_imagen'];

            try {
                $stmt = $pdo->prepare('UPDATE ajustes SET splash_activo=?, splash_imagen=?, inicio_imagen=?, inicio_imagen_titulo=?, sobre_historia=?, sobre_palmares=?, hero_kicker=?, hero_titulo=?, hero_texto=? WHERE id=1');
                $stmt->execute([$splashActivo, $splashImagenFinal, $inicioImagenFinal, $inicioImagenTitulo ?: null, $sobreHistoria ?: null, $sobrePalmares ?: null, $heroKicker ?: null, $heroTitulo ?: null, $heroTexto ?: null]);
                redirigir('ajustes.php?ok=1');
            } catch (PDOException $e) {
                $error = 'No se han podido guardar los ajustes (error de base de datos). Si acabas de actualizar el código del sitio, recarga esta página una vez más: la base de datos se actualiza sola en la primera visita tras cada cambio.';
            }
        }
    }

    // Si hubo error, mantener en pantalla lo que el usuario había marcado
    $ajustes['splash_activo'] = $splashActivo;
    $ajustes['inicio_imagen_titulo'] = $inicioImagenTitulo;
    $ajustes['sobre_historia'] = $sobreHistoria;
    $ajustes['sobre_palmares'] = $sobrePalmares;
    $ajustes['hero_kicker'] = $heroKicker;
    $ajustes['hero_titulo'] = $heroTitulo;
    $ajustes['hero_texto'] = $heroTexto;
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Ajustes del sitio</h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Ajustes guardados correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>

  <h3 style="margin-top:0;">Pantalla de bienvenida (splash)</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Se muestra a pantalla completa la primera vez que alguien entra a la web en cada
    visita (no se repite mientras siga navegando por las páginas del sitio).
  </p>

  <?php if (!empty($ajustes['splash_imagen'])): ?>
    <div style="margin-bottom:14px;">
      <img src="../img/<?= e($ajustes['splash_imagen']) ?>" alt="" style="max-width:280px;border-radius:8px;box-shadow:var(--sombra-chica);display:block;margin-bottom:6px;">
      <a href="ajustes_imagen_borrar.php?campo=splash_imagen&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:13px;" onclick="return confirm('¿Quitar la foto de bienvenida? Esto también desactivará el splash.');">Quitar foto</a>
    </div>
  <?php endif; ?>

  <div class="campo">
    <label for="splash_imagen">Foto de la pantalla de bienvenida (JPG, PNG o WEBP, máx. 20 MB)</label>
    <input type="file" id="splash_imagen" name="splash_imagen" accept="image/jpeg,image/png,image/webp">
  </div>

  <div class="campo">
    <label><input type="checkbox" name="splash_activo" <?= !empty($ajustes['splash_activo']) ? 'checked' : '' ?> style="width:auto;"> Activar la pantalla de bienvenida</label>
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Texto de la portada</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Deja cualquiera de estos campos en blanco para usar el texto por
    defecto de la web.
  </p>
  <div class="campo">
    <label for="hero_kicker">Entradilla (línea pequeña sobre el titular)</label>
    <input type="text" id="hero_kicker" name="hero_kicker" value="<?= e($ajustes['hero_kicker'] ?? '') ?>" placeholder="Ej: Equipo de referencia en gimnasia rítmica">
  </div>
  <div class="campo">
    <label for="hero_titulo">Titular grande</label>
    <input type="text" id="hero_titulo" name="hero_titulo" value="<?= e($ajustes['hero_titulo'] ?? '') ?>" placeholder="Ej: Cada cinta, cada aro, cada ejercicio: un paso más hacia el podio.">
  </div>
  <div class="campo">
    <label for="hero_texto">Texto de presentación</label>
    <textarea id="hero_texto" name="hero_texto" placeholder="Ej: Sigue la actualidad de la escuela, las gimnastas y los conjuntos del club..."><?= e($ajustes['hero_texto'] ?? '') ?></textarea>
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3>Foto de fondo de la portada</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Aparece como fondo detrás del titular de la página de inicio, con
    un efecto de parallax al hacer scroll.
  </p>

  <?php if (!empty($ajustes['inicio_imagen'])): ?>
    <div style="margin-bottom:14px;">
      <img src="../img/<?= e($ajustes['inicio_imagen']) ?>" alt="" style="max-width:280px;border-radius:8px;box-shadow:var(--sombra-chica);display:block;margin-bottom:6px;">
      <a href="ajustes_imagen_borrar.php?campo=inicio_imagen&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:13px;" onclick="return confirm('¿Quitar la foto de portada de inicio?');">Quitar foto</a>
    </div>
  <?php endif; ?>

  <div class="campo">
    <label for="inicio_imagen">Foto de portada (JPG, PNG o WEBP, máx. 20 MB)</label>
    <input type="file" id="inicio_imagen" name="inicio_imagen" accept="image/jpeg,image/png,image/webp">
  </div>

  <div class="campo">
    <label for="inicio_imagen_titulo">Pie de foto (opcional)</label>
    <input type="text" id="inicio_imagen_titulo" name="inicio_imagen_titulo" value="<?= e($ajustes['inicio_imagen_titulo'] ?? '') ?>" placeholder="Ej: El equipo tras el Campeonato de Euskadi">
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3>Sobre el club</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Este texto aparece en la página pública "Sobre el club". Separa los párrafos con una línea en blanco.
  </p>
  <div class="campo">
    <label for="sobre_historia">Historia del club</label>
    <textarea id="sobre_historia" name="sobre_historia"><?= e($ajustes['sobre_historia'] ?? '') ?></textarea>
  </div>
  <div class="campo">
    <label for="sobre_palmares">Palmarés (un logro por línea)</label>
    <textarea id="sobre_palmares" name="sobre_palmares" placeholder="Ej: Bronce por equipos, Campeonato de Euskadi 2025"><?= e($ajustes['sobre_palmares'] ?? '') ?></textarea>
  </div>

  <button type="submit" class="btn">Guardar ajustes</button>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
