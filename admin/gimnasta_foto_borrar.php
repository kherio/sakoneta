<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
$gimnastaId = (int)($_GET['gimnasta_id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gimnasta_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if ($foto) {
        $ruta = __DIR__ . '/../img/' . $foto['archivo'];
        if (is_file($ruta)) { @unlink($ruta); }
        $pdo->prepare('DELETE FROM gimnasta_fotos WHERE id = ?')->execute([$id]);

        $stmtGimnasta = $pdo->prepare('SELECT foto FROM gimnastas WHERE id = ?');
        $stmtGimnasta->execute([$foto['gimnasta_id']]);
        if ($stmtGimnasta->fetchColumn() === $foto['archivo']) {
            $siguiente = $pdo->prepare('SELECT archivo FROM gimnasta_fotos WHERE gimnasta_id = ? ORDER BY orden ASC LIMIT 1');
            $siguiente->execute([$foto['gimnasta_id']]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare('UPDATE gimnastas SET foto = ? WHERE id = ?')->execute([$nuevaPortada, $foto['gimnasta_id']]);
        }
    }
}

header('Location: gimnasta_form.php?id=' . $gimnastaId . '&ok=1');
exit;
