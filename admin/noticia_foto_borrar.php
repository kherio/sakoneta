<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor','colaborador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: noticias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
$noticiaId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT nf.*, n.autor_id, n.publicado FROM noticia_fotos nf JOIN noticias n ON n.id = nf.noticia_id WHERE nf.id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();
    if ($foto) {
        $noticiaId = (int)$foto['noticia_id'];
        if (puedeEditarNoticia($foto)) {
            borrarFotoDeEntidad($pdo, 'noticias', 'noticia_id', 'imagen', 'noticia_fotos', $id);
        } else {
            http_response_code(403);
            exit('No tienes permiso para borrar fotos de esta noticia.');
        }
    }
}

header('Location: noticia_form.php?id=' . $noticiaId . '&ok=1');
exit;
