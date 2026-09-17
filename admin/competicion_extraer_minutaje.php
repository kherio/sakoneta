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
$documentoId = (int)($_POST['documento_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM competicion_documentos WHERE id = ?');
$stmt->execute([$documentoId]);
$documento = $stmt->fetch();

if (!$documento) {
    header('Location: competiciones.php');
    exit;
}

$competicionId = (int)$documento['competicion_id'];
$rutaAbsoluta = __DIR__ . '/../img/' . $documento['archivo'];

if (strtolower(pathinfo($documento['archivo'], PATHINFO_EXTENSION)) !== 'pdf') {
    establecerFlash('minutaje_error', 'Solo se puede extraer el horario de un documento en PDF.');
} else {
    $texto = extraerTextoPdf($rutaAbsoluta);
    if ($texto === null) {
        establecerFlash('minutaje_error', 'No se ha podido leer texto de este PDF. Puede que el servidor no tenga instalada la herramienta necesaria, o que el PDF sea una hoja escaneada (una imagen) en vez de texto real.');
    } else {
        $borrador = extraerMinutajeDeTexto($pdo, $texto);
        if (!$borrador) {
            establecerFlash('minutaje_error', 'No se ha encontrado a ninguna gimnasta del club en este documento. Revisa que el PDF tenga el listado completo de participantes, o añade el horario a mano.');
        } else {
            // El borrador se guarda en la sesión, no en la base de
            // datos: hasta que alguien lo revise y confirme desde el
            // panel, no es un dato real, es solo una propuesta.
            $_SESSION['minutaje_borrador'][$competicionId] = $borrador;
        }
    }
}

header('Location: competicion_form.php?id=' . $competicionId, true, 303);
exit;
