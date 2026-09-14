<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
$noticiaId = (int)($_GET['noticia_id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM noticia_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if ($foto) {
        $ruta = __DIR__ . '/../img/' . $foto['archivo'];
        if (is_file($ruta)) { @unlink($ruta); }
        $pdo->prepare('DELETE FROM noticia_fotos WHERE id = ?')->execute([$id]);

        $stmtNoticia = $pdo->prepare('SELECT imagen FROM noticias WHERE id = ?');
        $stmtNoticia->execute([$foto['noticia_id']]);
        if ($stmtNoticia->fetchColumn() === $foto['archivo']) {
            $siguiente = $pdo->prepare('SELECT archivo FROM noticia_fotos WHERE noticia_id = ? ORDER BY orden ASC LIMIT 1');
            $siguiente->execute([$foto['noticia_id']]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare('UPDATE noticias SET imagen = ? WHERE id = ?')->execute([$nuevaPortada, $foto['noticia_id']]);
        }
    }
}

header('Location: noticia_form.php?id=' . $noticiaId . '&ok=1');
exit;
