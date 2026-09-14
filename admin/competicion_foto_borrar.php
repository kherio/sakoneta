<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
$competicionId = (int)($_GET['competicion_id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM competicion_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if ($foto) {
        $ruta = __DIR__ . '/../img/' . $foto['archivo'];
        if (is_file($ruta)) {
            @unlink($ruta);
        }
        $pdo->prepare('DELETE FROM competicion_fotos WHERE id = ?')->execute([$id]);

        // Si era la foto de portada, asignar otra existente o dejarlo en blanco
        $stmtComp = $pdo->prepare('SELECT imagen_portada FROM competiciones WHERE id = ?');
        $stmtComp->execute([$foto['competicion_id']]);
        if ($stmtComp->fetchColumn() === $foto['archivo']) {
            $siguiente = $pdo->prepare('SELECT archivo FROM competicion_fotos WHERE competicion_id = ? ORDER BY orden ASC LIMIT 1');
            $siguiente->execute([$foto['competicion_id']]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare('UPDATE competiciones SET imagen_portada = ? WHERE id = ?')->execute([$nuevaPortada, $foto['competicion_id']]);
        }
    }
}

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1');
exit;
