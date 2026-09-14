<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
$categoriaId = (int)($_GET['categoria_id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categoria_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if ($foto) {
        $ruta = __DIR__ . '/../img/' . $foto['archivo'];
        if (is_file($ruta)) { @unlink($ruta); }
        $pdo->prepare('DELETE FROM categoria_fotos WHERE id = ?')->execute([$id]);

        $stmtCategoria = $pdo->prepare('SELECT imagen_portada FROM categorias WHERE id = ?');
        $stmtCategoria->execute([$foto['categoria_id']]);
        if ($stmtCategoria->fetchColumn() === $foto['archivo']) {
            $siguiente = $pdo->prepare("SELECT archivo FROM categoria_fotos WHERE categoria_id = ? AND tipo = 'imagen' ORDER BY orden ASC LIMIT 1");
            $siguiente->execute([$foto['categoria_id']]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare('UPDATE categorias SET imagen_portada = ? WHERE id = ?')->execute([$nuevaPortada, $foto['categoria_id']]);
        }
    }
}

header('Location: categoria_form.php?id=' . $categoriaId . '&ok=1');
exit;
