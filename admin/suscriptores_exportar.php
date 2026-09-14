<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$suscriptores = $pdo->query('SELECT email, fecha FROM suscriptores ORDER BY fecha DESC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="suscriptores-sakoneta.csv"');

$salida = fopen('php://output', 'w');
fputcsv($salida, ['email', 'fecha_suscripcion']);
foreach ($suscriptores as $s) {
    fputcsv($salida, [$s['email'], $s['fecha']]);
}
fclose($salida);
