<?php
require_once __DIR__ . '/../../config.php';

function estaAutenticado(): bool {
    return !empty($_SESSION['admin_autenticado']);
}

function exigirAutenticacion(): void {
    if (!estaAutenticado()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Token CSRF de la sesión actual (se genera una vez y se reutiliza).
 */
function tokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Campo oculto listo para insertar dentro de un <form method="post">.
 */
function campoCsrf(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Corta la ejecución con un 403 si el token CSRF (recibido por POST o GET)
 * no coincide con el de la sesión. Debe llamarse tras exigirAutenticacion().
 */
function exigirCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Token de seguridad no válido o caducado. Vuelve a la página anterior e inténtalo de nuevo.');
    }
}
