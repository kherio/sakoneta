<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador']);

$pdo = getDb();
$seccionActual = 'usuarios';
$tituloPagina = 'Usuarios';

$roles = [
    'administrador' => 'Acceso total: contenido, ajustes del sitio, usuarios y moderación. Es el único rol que puede gestionar otros usuarios.',
    'editor' => 'Gestiona todo el contenido público (noticias, gimnastas, competiciones, categorías, patrocinadores y fotos), incluida su publicación. No accede a Ajustes del sitio ni a Usuarios.',
    'moderador' => 'Solo revisa comentarios y mensajes de contacto: aprobar, rechazar o borrar. No puede tocar contenido ni ajustes.',
    'colaborador' => 'Puede escribir y editar noticias, pero no publicarlas ni borrarlas: quedan como borrador hasta que un editor o administrador las revise y publique. No accede a ninguna otra sección.',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();

    if (isset($_POST['crear'])) {
        $usuario = trim($_POST['usuario'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $rol = $_POST['rol'] ?? '';
        $clave = $_POST['clave'] ?? '';

        if ($usuario === '' || $nombre === '') {
            $error = 'El usuario y el nombre no pueden estar vacíos.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $usuario)) {
            $error = 'El usuario solo puede tener letras, números, puntos, guiones y guiones bajos (sin espacios).';
        } elseif (!array_key_exists($rol, $roles)) {
            $error = 'Elige un rol válido.';
        } elseif (strlen($clave) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, nombre, password_hash, rol, activo, creado) VALUES (?,?,?,?,1,?)');
                $stmt->execute([$usuario, $nombre, password_hash($clave, PASSWORD_DEFAULT), $rol, date('Y-m-d H:i:s')]);
                redirigir('usuarios.php?ok=1');
            } catch (PDOException $e) {
                $error = 'Ya existe un usuario con ese nombre de acceso.';
            }
        }
    } elseif (isset($_POST['cambiar_rol'])) {
        $id = (int)$_POST['id'];
        $rol = $_POST['rol'] ?? '';
        if (array_key_exists($rol, $roles) && $id !== (int)($_SESSION['admin_usuario_id'] ?? 0)) {
            $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?')->execute([$rol, $id]);
        }
        redirigir('usuarios.php?ok=1');
    } elseif (isset($_POST['alternar_activo'])) {
        $id = (int)$_POST['id'];
        if ($id !== (int)($_SESSION['admin_usuario_id'] ?? 0)) {
            $pdo->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id = ?')->execute([$id]);
        }
        redirigir('usuarios.php?ok=1');
    } elseif (isset($_POST['borrar'])) {
        $id = (int)$_POST['id'];
        if ($id !== (int)($_SESSION['admin_usuario_id'] ?? 0)) {
            $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        }
        redirigir('usuarios.php?ok=1');
    } elseif (isset($_POST['resetear_clave'])) {
        $id = (int)$_POST['id'];
        $nueva = $_POST['nueva_clave'] ?? '';
        if (strlen($nueva) < 8) {
            $error = 'La contraseña nueva debe tener al menos 8 caracteres.';
        } else {
            $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $id]);
            redirigir('usuarios.php?ok=1');
        }
    }
}

$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY creado ASC')->fetchAll();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Usuarios</h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<h3 style="margin-top:0;">Los cuatro roles</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:30px;">
  <?php foreach ($roles as $nombreRol => $descripcion): ?>
  <div style="background:#fff;border:1px solid var(--borde);border-radius:8px;padding:14px 16px;">
    <strong style="text-transform:capitalize;"><?= e($nombreRol) ?></strong>
    <p style="font-size:13px;color:var(--gris);margin:6px 0 0;line-height:1.5;"><?= e($descripcion) ?></p>
  </div>
  <?php endforeach; ?>
</div>

<h3>Usuarios actuales</h3>
<table class="admin-tabla">
  <thead>
    <tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= e($u['usuario']) ?></td>
      <td><?= e($u['nombre']) ?></td>
      <td>
        <?php if ((int)$u['id'] === (int)($_SESSION['admin_usuario_id'] ?? 0)): ?>
          <?= e($u['rol']) ?> <span style="color:var(--gris);font-size:12px;">(tú)</span>
        <?php else: ?>
        <form method="post" style="display:inline;">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <select name="rol" onchange="this.form.submit()" style="width:auto;padding:4px 8px;font-size:13px;">
            <?php foreach ($roles as $nombreRol => $d): ?>
              <option value="<?= e($nombreRol) ?>" <?= $u['rol'] === $nombreRol ? 'selected' : '' ?>><?= e($nombreRol) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="cambiar_rol" value="1">
        </form>
        <?php endif; ?>
      </td>
      <td><?= $u['activo'] ? 'Activo' : 'Desactivado' ?></td>
      <td class="acciones">
        <?php if ((int)$u['id'] !== (int)($_SESSION['admin_usuario_id'] ?? 0)): ?>
        <form method="post">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button type="submit" name="alternar_activo" value="1" class="editar"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button>
        </form>
        <form method="post" onsubmit="var c=prompt('Escribe la nueva contraseña para ' + <?= json_encode($u['usuario']) ?> + ' (mínimo 8 caracteres):'); if(!c) return false; this.nueva_clave.value=c; return true;">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="nueva_clave">
          <button type="submit" name="resetear_clave" value="1" class="editar">Restablecer clave</button>
        </form>
        <form method="post" onsubmit="return confirm('¿Borrar este usuario?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button type="submit" name="borrar" value="1" class="borrar">Borrar</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Nuevo usuario</h3>
<form method="post" style="max-width:420px;">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="usuario">Usuario de acceso</label>
    <input type="text" id="usuario" name="usuario" placeholder="ej: iciar" required>
  </div>
  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" placeholder="ej: Iciar Etxaniz" required>
  </div>
  <div class="campo">
    <label for="rol">Rol</label>
    <select id="rol" name="rol" required>
      <?php foreach ($roles as $nombreRol => $descripcion): ?>
        <option value="<?= e($nombreRol) ?>"><?= e($nombreRol) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="clave">Contraseña (mínimo 8 caracteres)</label>
    <input type="password" id="clave" name="clave" minlength="8" required>
  </div>
  <button type="submit" name="crear" value="1" class="btn">Crear usuario</button>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
