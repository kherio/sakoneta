<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        // Si dos peticiones coinciden justo cuando una tiene la base
        // de datos bloqueada en escritura, SQLite espera hasta 5
        // segundos antes de rendirse, en vez de fallar al momento.
        $pdo->exec('PRAGMA busy_timeout = 5000');
        ejecutarMigracionesEsquema($pdo);
    }
    return $pdo;
}
