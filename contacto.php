<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'contacto';
$tituloPagina = 'Contacto';

$enviado = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($nombre === '' || $email === '' || $mensaje === '') {
        $error = 'Por favor, rellena todos los campos.';
    } elseif (strlen($nombre) > 100 || strlen($email) > 190 || strlen($mensaje) > 4000) {
        $error = 'Alguno de los campos es demasiado largo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Revisa el correo electrónico, no parece válido.';
    } elseif (superaLimiteEnvios($pdo, 'contacto', 5, 10)) {
        $error = 'Se han enviado demasiados mensajes desde aquí en poco tiempo. Vuelve a intentarlo en unos minutos.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO mensajes_contacto (nombre, email, mensaje, fecha) VALUES (?,?,?,?)');
        $stmt->execute([$nombre, $email, $mensaje, date('Y-m-d H:i:s')]);
        $enviado = true;
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_contacto') ?></h2>
    </div>

    <div class="grid-contacto">
      <div>
        <?php if ($enviado): ?>
          <div class="aviso-ok">Gracias, hemos recibido tu mensaje. Te responderemos lo antes posible.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="aviso-ok" style="background:#FBE4E4;color:#9A2A2A;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="formulario">
          <label for="nombre"><?= t('contacto_nombre') ?></label>
          <input type="text" id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" maxlength="100" required>

          <label for="email"><?= t('contacto_email') ?></label>
          <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" maxlength="190" required>

          <label for="mensaje"><?= t('contacto_mensaje') ?></label>
          <textarea id="mensaje" name="mensaje" maxlength="4000" required><?= e($_POST['mensaje'] ?? '') ?></textarea>

          <button type="submit" class="boton oro"><?= t('contacto_enviar') ?></button>
        </form>
      </div>

      <div>
        <dl class="datos-contacto">
          <dt>Dirección</dt>
          <dd>Polideportivo de Sakoneta, Leioa</dd>
          <dt>Teléfono</dt>
          <dd>944 000 000</dd>
          <dt>Correo</dt>
          <dd>info@sakoneta-grt.eus</dd>
          <dt>Horario de oficinas</dt>
          <dd>Lunes a viernes, de 9:00 a 14:00 y de 16:00 a 19:00</dd>
        </dl>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
