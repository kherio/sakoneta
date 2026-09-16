<?php
require_once __DIR__ . '/../../config.php';

function estaAutenticado(): bool {
    return !empty($_SESSION['admin_autenticado']);
}

/**
 * Comprueba la sesión Y la vuelve a validar contra la base de datos en
 * cada petición: que el usuario siga existiendo y activo, que su rol
 * esté al día (por si otro administrador lo acaba de cambiar) y que
 * su "session_version" coincida (se incrementa al cambiar la
 * contraseña o desactivar la cuenta, para cerrar de golpe cualquier
 * sesión abierta con las credenciales antiguas).
 */
function exigirAutenticacion(): void {
    if (!estaAutenticado()) {
        header('Location: index.php');
        exit;
    }

    $id = (int)($_SESSION['admin_usuario_id'] ?? 0);
    $stmt = getDb()->prepare('SELECT rol, activo, session_version FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $fila = $stmt->fetch();

    $versionSesion = $_SESSION['admin_session_version'] ?? null;

    if (!$fila || !$fila['activo'] || $versionSesion === null || (int)$fila['session_version'] !== (int)$versionSesion) {
        session_unset();
        session_destroy();
        header('Location: index.php?cerrada=1');
        exit;
    }

    // Si el rol ha cambiado desde la última petición (por ejemplo, un
    // administrador acaba de ascender a esta persona), se regenera el
    // ID de sesión: es la recomendación habitual al elevar privilegios,
    // para no arrastrar un identificador de sesión de antes del cambio.
    if (isset($_SESSION['admin_rol']) && $_SESSION['admin_rol'] !== $fila['rol']) {
        session_regenerate_id(true);
    }

    // Mantener el rol de la sesión siempre al día con el de la base de datos
    $_SESSION['admin_rol'] = $fila['rol'];
}

/**
 * Rol de la persona conectada ('administrador', 'editor', 'moderador'
 * o 'colaborador'), guardado en la sesión al iniciar sesión.
 */
function rolActual(): string {
    return $_SESSION['admin_rol'] ?? 'colaborador';
}

function esAdministrador(): bool {
    return rolActual() === 'administrador';
}

/**
 * Un colaborador solo puede editar (o borrar fotos de) sus propias
 * noticias, y solo mientras sigan sin publicar. Una vez publicada, o
 * si es de otra persona, ya no puede tocarla — la seguirá viendo en
 * el listado, pero sin opción de editar. Administradores y editores
 * no tienen esta limitación.
 */
function puedeEditarNoticia(array $noticia): bool {
    if (rolActual() !== 'colaborador') {
        return true;
    }
    $miId = (int)($_SESSION['admin_usuario_id'] ?? 0);
    return (int)($noticia['autor_id'] ?? 0) === $miId && !$noticia['publicado'];
}

/**
 * Corta la ejecución con un 403 si la persona conectada no puede
 * editar esta noticia (ver puedeEditarNoticia).
 */
function exigirPuedeEditarNoticia(array $noticia): void {
    if (puedeEditarNoticia($noticia)) {
        return;
    }
    http_response_code(403);
    require __DIR__ . '/layout_header.php';
    echo '<h1>Sin permiso</h1><p>Como colaborador, solo puedes editar tus propias noticias mientras sigan sin publicar.</p>';
    require __DIR__ . '/layout_footer.php';
    exit;
}

/**
 * Corta la ejecución (con un mensaje claro) si la persona conectada no
 * tiene ninguno de los roles indicados. Debe llamarse justo después de
 * exigirAutenticacion().
 */
function exigirRol(array $rolesPermitidos): void {
    if (in_array(rolActual(), $rolesPermitidos, true)) {
        return;
    }
    http_response_code(403);
    require __DIR__ . '/layout_header.php';
    echo '<h1>Sin permiso</h1><p>Tu usuario (rol: <strong>' . htmlspecialchars(rolActual(), ENT_QUOTES, 'UTF-8') . '</strong>) no tiene acceso a esta sección.</p>';
    require __DIR__ . '/layout_footer.php';
    exit;
}

// tokenCsrf(), campoCsrf() y exigirCsrf() viven ahora en
// includes/functions.php: son igual de necesarias en los formularios
// públicos (contacto, comentarios) como en el panel, y ese archivo lo
// cargan ambos.
