<?php
// Ejecuta este script UNA VEZ, la primera vez que instales el sitio
// (solo por SSH, con "php init_db.php" — ya NO se puede ejecutar
// desde el navegador, ver más abajo), para cargar los datos de
// ejemplo. La creación de tablas y las migraciones de columnas nuevas
// ya NO dependen de este script: se ejecutan solas en cada petición
// desde includes/db.php, así que un simple "git pull" con código
// nuevo ya deja la base de datos al día.

// Solo por línea de comandos: un script de instalación accesible
// desde el navegador es superficie de ataque innecesaria, aunque hoy
// no permita tomar el control de una instalación ya inicializada.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se puede ejecutar por línea de comandos (SSH), no desde el navegador.\n");
}

require_once __DIR__ . '/includes/db.php';

$pdo = getDb(); // getDb() ya crea las tablas y aplica migraciones al abrir la conexión

// --- Datos de ejemplo, solo si las tablas están vacías ---

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM noticias')->fetch()['c'];
if ($count === 0) {
    // Las versiones en euskera son una primera traducción automática:
    // antes de confiar en ellas del todo para un sitio real, conviene
    // que las revise alguien euskaldun.
    $noticias = [
        [
            'Doblete de podios en el Campeonato de Euskadi', 'El conjunto junior y dos individuales suben al cajón en la primera gran cita de la temporada.', "El pabellón vivió una jornada de gimnasia de altísimo nivel. El conjunto junior de aro y pelota logró la medalla de plata tras un ejercicio limpio y muy bien sincronizado.\n\nEn individual, dos gimnastas del club completaron el podio en categoría infantil, confirmando el buen momento de la cantera. La dirección técnica destacó \"el trabajo constante de todo el curso\" como clave de estos resultados.",
            'Podium bikoitza Euskadiko Txapelketan', 'Junior taldea eta bi banakako gimnasta podiumera igo dira denboraldiko lehen hitzordu handian.', "Kiroldegiak maila oso altuko gimnasia jardunaldia bizi izan zuen. Junior taldeak, uztaia eta pilota erabiliz, zilarrezko domina lortu zuen, ariketa garbi eta oso ondo sinkronizatu baten ondoren.\n\nBanakakoetan, klubeko bi gimnastak podiuma osatu zuten infantil kategorian, harrobiaren une ona berretsiz. Zuzendaritza teknikoak \"ikasturte osoko lan etengabea\" nabarmendu zuen emaitza hauen giltzarri gisa.",
            'competicion.svg', date('Y-m-d', strtotime('-3 days')),
        ],
        [
            'Nueva promoción de gimnastas se incorpora a la escuela', 'Arranca el curso con más de veinte nuevas alumnas en las categorías de iniciación.', "La escuela del club sigue creciendo temporada tras temporada. Este año se han incorporado nuevas gimnastas a los grupos de base, que empezarán a familiarizarse con los cinco aparatos: cuerda, aro, pelota, mazas y cinta.\n\nDesde el club se recuerda que las puertas de la escuela permanecen abiertas durante todo el curso para quienes quieran probar esta disciplina.",
            'Gimnasta talde berria eskolan sartu da', 'Ikasturteari ekin zaio hasiera mailako kategorietan hogei ikasle berri baino gehiagorekin.', "Klubeko eskola denboraldiz denboraldi hazten ari da. Aurten gimnasta berriak sartu dira oinarrizko taldeetan, eta bost tresnak ezagutzen hasiko dira: soka, uztaia, pilota, mazak eta zinta.\n\nKlubetik gogorarazi nahi da eskolako ateak ikasturte osoan zabalik daudela diziplina hau probatu nahi duen edonorentzat.",
            'cantera.svg', date('Y-m-d', strtotime('-9 days')),
        ],
        [
            'Abierto el plazo de renovación de socias y socios', 'Ya se puede formalizar la cuota de la nueva temporada a través de secretaría.', "El club ha abierto el periodo de renovación para la temporada 2026/27, con condiciones especiales para familias con más de una gimnasta inscrita.\n\nSer socia o socio del club ayuda a sostener becas de material y desplazamientos a competiciones para el equipo de base.",
            'Bazkideen berritze-epea zabalik dago', 'Denboraldi berriko kuota idazkaritzaren bidez ordaindu daiteke jada.', "Klubak 2026/27 denboraldirako berritze-epea zabaldu du, gimnasta bat baino gehiago izena emanda duten familientzako baldintza bereziekin.\n\nKlubeko bazkide izateak lagundu egiten du oinarrizko taldearentzako materiale-bekak eta txapelketetarako joan-etorriak eusten.",
            'socios.svg', date('Y-m-d', strtotime('-15 days')),
        ],
    ];
    $stmt = $pdo->prepare('INSERT INTO noticias (titulo, resumen, contenido, titulo_eu, resumen_eu, contenido_eu, imagen, fecha, publicado) VALUES (?,?,?,?,?,?,?,?,1)');
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
    // Las versiones en euskera son una primera traducción automática:
    // antes de confiar en ellas del todo para un sitio real, conviene
    // que las revise alguien euskaldun.
    $competiciones = [
        [
            'Campeonato de Euskadi por Clubes', 'Junior', 'Vitoria-Gasteiz', date('Y-m-d', strtotime('-24 days')), 'Bronce por equipos', 1, "El conjunto junior viajó hasta Vitoria-Gasteiz con el objetivo de subir al podio, y lo consiguió con un ejercicio de aro y pelota muy limpio.\n\nLa entrenadora destacó la madurez del grupo ante un pabellón exigente y con rivales de mucho nivel.",
            'Euskadiko Klubarteko Txapelketa', 'Gasteiz', 'Brontzea taldeka', "Junior taldea Gasteizera joan zen podiumera igotzeko helburuarekin, eta lortu zuen uztai eta pilotako ariketa oso garbi batekin.\n\nEntrenatzaileak taldearen heldutasuna nabarmendu zuen kiroldegi zorrotz eta maila handiko aurkarien aurrean.",
        ],
        [
            'Copa de Bizkaia', 'Infantil', 'Bilbao', date('Y-m-d', strtotime('-3 days')), 'Plata en conjunto, bronce individual', 1, "Una de las citas más esperadas del calendario territorial. El conjunto infantil logró la plata con su ejercicio de cuerdas, y en individual se sumó un bronce más para la vitrina del club.\n\nGracias a todas las familias que acompañaron a las gimnastas durante toda la jornada.",
            'Bizkaiko Kopa', 'Bilbo', 'Zilarra taldeka, brontzea bakarka', "Lurralde egutegiko itxarondako hitzorduetako bat. Infantil taldeak zilarra lortu zuen soken ariketarekin, eta bakarkakoan brontze bat gehiago gehitu zitzaion klubaren bitrinari.\n\nEskerrik asko jardunaldi osoan gimnastak lagundu dituzten familia guztiei.",
        ],
        [
            'Campeonato de Euskadi Individual', 'Senior', 'Donostia-San Sebastián', date('Y-m-d', strtotime('+11 days')), null, 0, "Cita individual para la categoría senior. Se disputará en el Illumbe de Donostia-San Sebastián, con clasificación directa para el Campeonato de España por Autonomías.",
            'Euskadiko Banakako Txapelketa', 'Donostia', null, "Senior kategoriako banakako hitzordua. Donostiako Illunben jokatuko da, eta Espainiako Autonomien arteko Txapelketarako sailkapen zuzena emango du.",
        ],
        [
            'Torneo interclubes de primavera', 'Base', 'Leioa', date('Y-m-d', strtotime('+25 days')), null, 0, "Torneo amistoso pensado para las categorías de base, con la participación de varios clubes de la zona. Ideal para que las más pequeñas vivan su primera experiencia de competición.",
            'Udaberriko klubarteko txapelketa', 'Leioa', null, "Oinarrizko kategorientzat pentsatutako txapelketa lagunartekoa, eskualdeko hainbat kluben parte-hartzearekin. Aproposa txikienek euren lehen txapelketa-esperientzia bizi dezaten.",
        ],
        [
            'Campeonato de España por Autonomías', 'Junior', 'Madrid', date('Y-m-d', strtotime('+40 days')), null, 0, null,
            'Espainiako Autonomien arteko Txapelketa', 'Madril', null, null,
        ],
    ];
    $stmt = $pdo->prepare('INSERT INTO competiciones (nombre, categoria, lugar, fecha, resultado, disputada, descripcion, nombre_eu, lugar_eu, resultado_eu, descripcion_eu) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($competiciones as $c) $stmt->execute($c);
}

// Por si acaso alguna competición (de ejemplo o real) tiene categoría
// en la columna antigua pero todavía no tiene fila en la tabla de
// categorías múltiples (por ejemplo, en una instalación nueva, donde
// esto se inserta DESPUÉS de que se hayan creado las migraciones).
$pdo->exec("INSERT OR IGNORE INTO competicion_categorias (competicion_id, categoria)
            SELECT id, categoria FROM competiciones WHERE categoria IS NOT NULL AND categoria != ''");

echo "Base de datos creada e inicializada correctamente en data/club.sqlite";
