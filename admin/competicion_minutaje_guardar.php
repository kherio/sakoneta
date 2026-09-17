<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: competiciones.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$competicionId = (int)($_POST['competicion_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM competiciones WHERE id = ?');
$stmt->execute([$competicionId]);
if (!$stmt->fetch()) {
    header('Location: competiciones.php');
    exit;
}

$nombres = $_POST['fila_nombre'] ?? [];
$horas = $_POST['fila_hora'] ?? [];
$gimnastaIds = $_POST['fila_gimnasta_id'] ?? [];
$incluidas = $_POST['fila_incluir'] ?? [];

$pdo->beginTransaction();
try {
    // Se sustituye entero el horario de esta competición por el que
    // se acaba de confirmar, para no acumular versiones sueltas de
    // ediciones anteriores.
    $pdo->prepare('DELETE FROM competicion_minutaje WHERE competicion_id = ?')->execute([$competicionId]);

    $stmtInsertar = $pdo->prepare('INSERT INTO competicion_minutaje (competicion_id, gimnasta_id, nombre, hora, orden) VALUES (?,?,?,?,?)');
    $orden = 0;
    foreach ($nombres as $i => $nombre) {
        if (empty($incluidas[$i])) continue; // se ha desmarcado desde el panel: no se guarda
        $nombre = trim((string)$nombre);
        if ($nombre === '') continue;
        $hora = trim((string)($horas[$i] ?? ''));
        $hora = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : null;
        $gimnastaId = (int)($gimnastaIds[$i] ?? 0) ?: null;
        $stmtInsertar->execute([$competicionId, $gimnastaId, $nombre, $hora, $orden++]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

// El borrador ya se ha confirmado (o descartado): no debe seguir
// apareciendo la próxima vez que se abra esta competición.
unset($_SESSION['minutaje_borrador'][$competicionId]);

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1', true, 303);
exit;
