<?php
// Ejecuta este script UNA VEZ (desde el navegador o con "php init_db.php")
// para crear la base de datos SQLite y cargar contenido de ejemplo.

require_once __DIR__ . '/includes/db.php';

$pdo = getDb();

$pdo->exec("CREATE TABLE IF NOT EXISTS noticias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    resumen TEXT NOT NULL,
    contenido TEXT NOT NULL,
    imagen TEXT,
    fecha TEXT NOT NULL,
    publicado INTEGER NOT NULL DEFAULT 1
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS gimnastas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    categoria TEXT NOT NULL,
    modalidad TEXT NOT NULL,
    aparato TEXT,
    foto TEXT,
    orden INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE,
    orden INTEGER DEFAULT 0,
    imagen_portada TEXT
)");
$columnasCategorias = $pdo->query("PRAGMA table_info(categorias)")->fetchAll();
if (!in_array('imagen_portada', array_column($columnasCategorias, 'name'), true)) {
    $pdo->exec("ALTER TABLE categorias ADD COLUMN imagen_portada TEXT");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS competiciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    categoria TEXT NOT NULL,
    lugar TEXT NOT NULL,
    fecha TEXT NOT NULL,
    resultado TEXT,
    disputada INTEGER NOT NULL DEFAULT 0,
    imagen_portada TEXT,
    descripcion TEXT
)");

// Migración: añade la columna imagen_portada si la base de datos ya existía sin ella
$columnasCompeticiones = $pdo->query("PRAGMA table_info(competiciones)")->fetchAll();
$tienePortada = false;
$tieneDescripcion = false;
foreach ($columnasCompeticiones as $columna) {
    if ($columna['name'] === 'imagen_portada') { $tienePortada = true; }
    if ($columna['name'] === 'descripcion') { $tieneDescripcion = true; }
}
if (!$tienePortada) {
    $pdo->exec("ALTER TABLE competiciones ADD COLUMN imagen_portada TEXT");
}
if (!$tieneDescripcion) {
    $pdo->exec("ALTER TABLE competiciones ADD COLUMN descripcion TEXT");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS competicion_fotos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    competicion_id INTEGER NOT NULL,
    archivo TEXT NOT NULL,
    orden INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS noticia_fotos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    noticia_id INTEGER NOT NULL,
    archivo TEXT NOT NULL,
    orden INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS gimnasta_fotos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gimnasta_id INTEGER NOT NULL,
    archivo TEXT NOT NULL,
    orden INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS categoria_fotos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    categoria_id INTEGER NOT NULL,
    archivo TEXT NOT NULL,
    orden INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS ajustes (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    splash_activo INTEGER NOT NULL DEFAULT 0,
    splash_imagen TEXT,
    inicio_imagen TEXT,
    inicio_imagen_titulo TEXT
)");
$pdo->exec("INSERT OR IGNORE INTO ajustes (id, splash_activo, splash_imagen, inicio_imagen, inicio_imagen_titulo)
            VALUES (1, 0, NULL, NULL, NULL)");

// Migración: columnas de "Sobre el club" si la base de datos ya existía sin ellas
$columnasAjustes = $pdo->query("PRAGMA table_info(ajustes)")->fetchAll();
$nombresColumnas = array_column($columnasAjustes, 'name');
if (!in_array('sobre_historia', $nombresColumnas, true)) {
    $pdo->exec("ALTER TABLE ajustes ADD COLUMN sobre_historia TEXT");
}
if (!in_array('sobre_palmares', $nombresColumnas, true)) {
    $pdo->exec("ALTER TABLE ajustes ADD COLUMN sobre_palmares TEXT");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    email TEXT NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TEXT NOT NULL,
    leido INTEGER NOT NULL DEFAULT 0
)");

// --- Datos de ejemplo, solo si las tablas están vacías ---

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM noticias')->fetch()['c'];
if ($count === 0) {
    $noticias = [
        ['Doblete de podios en el Campeonato de Euskadi', 'El conjunto junior y dos individuales suben al cajón en la primera gran cita de la temporada.', "El pabellón vivió una jornada de gimnasia de altísimo nivel. El conjunto junior de aro y pelota logró la medalla de plata tras un ejercicio limpio y muy bien sincronizado.\n\nEn individual, dos gimnastas del club completaron el podio en categoría infantil, confirmando el buen momento de la cantera. La dirección técnica destacó \"el trabajo constante de todo el curso\" como clave de estos resultados.", 'competicion.svg', date('Y-m-d', strtotime('-3 days'))],
        ['Nueva promoción de gimnastas se incorpora a la escuela', 'Arranca el curso con más de veinte nuevas alumnas en las categorías de iniciación.', "La escuela del club sigue creciendo temporada tras temporada. Este año se han incorporado nuevas gimnastas a los grupos de base, que empezarán a familiarizarse con los cinco aparatos: cuerda, aro, pelota, mazas y cinta.\n\nDesde el club se recuerda que las puertas de la escuela permanecen abiertas durante todo el curso para quienes quieran probar esta disciplina.", 'cantera.svg', date('Y-m-d', strtotime('-9 days'))],
        ['Abierto el plazo de renovación de socias y socios', 'Ya se puede formalizar la cuota de la nueva temporada a través de secretaría.', "El club ha abierto el periodo de renovación para la temporada 2026/27, con condiciones especiales para familias con más de una gimnasta inscrita.\n\nSer socia o socio del club ayuda a sostener becas de material y desplazamientos a competiciones para el equipo de base.", 'socios.svg', date('Y-m-d', strtotime('-15 days'))],
    ];
    $stmt = $pdo->prepare('INSERT INTO noticias (titulo, resumen, contenido, imagen, fecha, publicado) VALUES (?,?,?,?,?,1)');
    foreach ($noticias as $n) $stmt->execute($n);
}

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM categorias')->fetch()['c'];
if ($count === 0) {
    $categorias = ['Base', 'Alevín', 'Infantil', 'Cadete', 'Junior', 'Senior'];
    $stmt = $pdo->prepare('INSERT INTO categorias (nombre, orden) VALUES (?, ?)');
    foreach ($categorias as $i => $nombreCategoria) $stmt->execute([$nombreCategoria, $i + 1]);
}

$sobreActual = $pdo->query('SELECT sobre_historia FROM ajustes WHERE id = 1')->fetchColumn();
if (!$sobreActual) {
    $historiaEjemplo = "Sakoneta nació de la ilusión de un grupo de familias por tener un club de gimnasia rítmica propio en la zona.\n\nDesde entonces, generaciones de gimnastas han pasado por sus grupos de base, muchas de ellas hoy formando parte del equipo que compite en las categorías superiores.";
    $palmaresEjemplo = "Bronce por equipos, Campeonato de Euskadi por Clubes 2025\nPlata en conjunto, Copa de Bizkaia 2026\nVarias medallas individuales en categorías de base";
    $pdo->prepare('UPDATE ajustes SET sobre_historia = ?, sobre_palmares = ? WHERE id = 1')->execute([$historiaEjemplo, $palmaresEjemplo]);
}

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM gimnastas')->fetch()['c'];
if ($count === 0) {
    $gimnastas = [
        ['Nagore Iturbe', 'Senior', 'Individual', 'Pelota', 'gimnasta-placeholder.svg', 1],
        ['Amaia Zubeldia', 'Junior', 'Individual', 'Aro', 'gimnasta-placeholder.svg', 2],
        ['June Larrinaga', 'Junior', 'Individual', 'Mazas', 'gimnasta-placeholder.svg', 3],
        ['Nerea Uranga', 'Infantil', 'Individual', 'Cinta', 'gimnasta-placeholder.svg', 4],
        ['Maddi Etxebarria', 'Infantil', 'Individual', 'Cuerda', 'gimnasta-placeholder.svg', 5],
        ['Conjunto Junior', 'Junior', 'Conjunto', 'Aro y pelota', 'gimnasta-placeholder.svg', 6],
        ['Conjunto Infantil', 'Infantil', 'Conjunto', 'Cuerdas', 'gimnasta-placeholder.svg', 7],
    ];
    $stmt = $pdo->prepare('INSERT INTO gimnastas (nombre, categoria, modalidad, aparato, foto, orden) VALUES (?,?,?,?,?,?)');
    foreach ($gimnastas as $g) $stmt->execute($g);
}

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM competiciones')->fetch()['c'];
if ($count === 0) {
    $competiciones = [
        ['Campeonato de Euskadi por Clubes', 'Junior', 'Vitoria-Gasteiz', date('Y-m-d', strtotime('-24 days')), 'Bronce por equipos', 1, "El conjunto junior viajó hasta Vitoria-Gasteiz con el objetivo de subir al podio, y lo consiguió con un ejercicio de aro y pelota muy limpio.\n\nLa entrenadora destacó la madurez del grupo ante un pabellón exigente y con rivales de mucho nivel."],
        ['Copa de Bizkaia', 'Infantil', 'Bilbao', date('Y-m-d', strtotime('-3 days')), 'Plata en conjunto, bronce individual', 1, "Una de las citas más esperadas del calendario territorial. El conjunto infantil logró la plata con su ejercicio de cuerdas, y en individual se sumó un bronce más para la vitrina del club.\n\nGracias a todas las familias que acompañaron a las gimnastas durante toda la jornada."],
        ['Campeonato de Euskadi Individual', 'Senior', 'Donostia-San Sebastián', date('Y-m-d', strtotime('+11 days')), null, 0, "Cita individual para la categoría senior. Se disputará en el Illumbe de Donostia-San Sebastián, con clasificación directa para el Campeonato de España por Autonomías."],
        ['Torneo interclubes de primavera', 'Base', 'Leioa', date('Y-m-d', strtotime('+25 days')), null, 0, "Torneo amistoso pensado para las categorías de base, con la participación de varios clubes de la zona. Ideal para que las más pequeñas vivan su primera experiencia de competición."],
        ['Campeonato de España por Autonomías', 'Junior', 'Madrid', date('Y-m-d', strtotime('+40 days')), null, 0, null],
    ];
    $stmt = $pdo->prepare('INSERT INTO competiciones (nombre, categoria, lugar, fecha, resultado, disputada, descripcion) VALUES (?,?,?,?,?,?,?)');
    foreach ($competiciones as $c) $stmt->execute($c);
}

echo "Base de datos creada e inicializada correctamente en data/club.sqlite";
