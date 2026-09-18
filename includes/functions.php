<?php

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Valida que una fecha enviada desde un formulario sea una fecha de
 * verdad en formato AAAA-MM-DD (el que envía <input type="date">),
 * y no solo algo con esa forma (rechaza "9999-99-99", "2026-02-30",
 * etc.). El navegador ya limita lo que se puede escribir en el
 * campo, pero eso no es ninguna garantía: hace falta comprobarlo
 * también en el servidor.
 */
function fechaValida(?string $fecha): bool {
    if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return false;
    }
    [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
    return checkdate($mes, $dia, $anio);
}

function formatearFecha(?string $fecha): string {
    if (!$fecha) return '';
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($fecha);
    return (int)date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/**
 * Añade "?v=fecha_de_modificación" a la URL de un archivo estático
 * (CSS, JS...) para que el navegador descargue la versión nueva justo
 * después de cada actualización del sitio, en vez de quedarse con una
 * copia antigua guardada en caché.
 *
 * $hrefRelativo es lo que se pone en el href/src tal cual (relativo a
 * la página actual). $rutaFisica es dónde está el archivo de verdad
 * en el disco, relativa a la raíz del proyecto; si no se indica, se
 * asume que coincide con $hrefRelativo (válido en las páginas
 * públicas, no en las del panel, donde el enlace es relativo a /admin/).
 */
function versionArchivo(string $hrefRelativo, ?string $rutaFisica = null): string {
    $rutaFisica ??= $hrefRelativo;
    $rutaCompleta = __DIR__ . '/../' . ltrim($rutaFisica, '/');
    $version = is_file($rutaCompleta) ? filemtime($rutaCompleta) : time();
    return $hrefRelativo . '?v=' . $version;
}

function redirigir(string $ruta): void {
    header('Location: ' . $ruta, true, 303);
    exit;
}

const LOGIN_MAX_INTENTOS = 6;
const LOGIN_BLOQUEO_MINUTOS = 15;

/**
 * IP real de quien visita, resistente a falsificación por cabecera
 * HTTP. Por defecto se usa SIEMPRE la IP de la conexión TCP
 * (REMOTE_ADDR), que un atacante no puede falsificar. Solo si esta
 * petición viene de una IP configurada explícitamente como proxy de
 * confianza (variable de entorno SAKONETA_TRUSTED_PROXIES, o
 * definida en config.local.php con putenv()) se mira la cabecera
 * X-Forwarded-For — y aun así, recorriéndola de derecha a izquierda
 * y quedándose con el primer salto que YA NO sea uno de esos
 * proxies, porque todo lo que hay a la izquierda de eso lo pudo
 * escribir libremente quien hizo la petición.
 */
function ipVisitante(): string {
    $remoto = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $proxiesConfiables = array_filter(array_map('trim', explode(',', (string)(getenv('SAKONETA_TRUSTED_PROXIES') ?: ''))));
    if (!$proxiesConfiables || !in_array($remoto, $proxiesConfiables, true)) {
        return $remoto;
    }

    $cabecera = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $saltos = array_reverse(array_filter(array_map('trim', explode(',', $cabecera))));
    foreach ($saltos as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $proxiesConfiables, true)) {
            return $ip;
        }
    }
    return $remoto;
}

/**
 * Minutos de bloqueo restantes para esta IP, solo para poder avisar
 * al cargar el formulario de login (GET). La comprobación real y
 * decisiva ocurre dentro de intentarLogin(), de forma atómica.
 */
function minutosBloqueoRestantesIp(PDO $pdo, string $ip): int {
    $stmt = $pdo->prepare('SELECT bloqueado_hasta FROM intentos_login WHERE ip = ?');
    $stmt->execute([$ip]);
    return minutosDesdeFecha($stmt->fetchColumn());
}

function minutosDesdeFecha($hasta): int {
    if (!$hasta) return 0;
    $restante = strtotime($hasta) - time();
    return $restante > 0 ? (int)ceil($restante / 60) : 0;
}

/**
 * Intenta un login de forma completamente atómica frente a
 * concurrencia: la comprobación de si ya está bloqueado, la
 * verificación de credenciales (a través de $verificarCredenciales,
 * que debe devolver la fila del usuario si son correctas o null si
 * no) y el registro del resultado ocurren TODOS dentro de una única
 * transacción con bloqueo inmediato de SQLite (BEGIN IMMEDIATE).
 * Mientras dura, ninguna otra petición puede leer ni escribir estas
 * tablas, así que no existe ninguna ventana en la que dos intentos
 * concurrentes (misma IP, u otra IP apuntando a la misma cuenta)
 * puedan colarse viendo a la vez el estado "todavía sin bloquear".
 *
 * Se bloquea tanto por IP como por la cuenta de usuario a la que se
 * apunta: cambiar de IP no sirve de nada si se sigue atacando la
 * misma cuenta, y viceversa.
 *
 * Devuelve ['bloqueado' => bool, 'minutos' => int, 'usuario' => array|null].
 */
function intentarLogin(PDO $pdo, string $ip, string $usuario, callable $verificarCredenciales): array {
    // Si SQLite estuviera ocupada por otra petición en este mismo
    // instante, se reintenta el BEGIN unas cuantas veces en vez de
    // fallar directamente.
    for ($intento = 0; ; $intento++) {
        try {
            $pdo->exec('BEGIN IMMEDIATE');
            break;
        } catch (PDOException $e) {
            if ($intento >= 8) throw $e;
            usleep(150000);
        }
    }

    try {
        $stmtIp = $pdo->prepare('SELECT bloqueado_hasta FROM intentos_login WHERE ip = ?');
        $stmtIp->execute([$ip]);
        $minutos = minutosDesdeFecha($stmtIp->fetchColumn());

        if ($usuario !== '') {
            $stmtCuenta = $pdo->prepare('SELECT bloqueado_hasta FROM intentos_login_usuario WHERE usuario = ?');
            $stmtCuenta->execute([$usuario]);
            $minutos = max($minutos, minutosDesdeFecha($stmtCuenta->fetchColumn()));
        }

        if ($minutos > 0) {
            $pdo->exec('COMMIT');
            return ['bloqueado' => true, 'minutos' => $minutos, 'usuario' => null];
        }

        $filaUsuario = $verificarCredenciales();

        if ($filaUsuario) {
            $pdo->prepare('DELETE FROM intentos_login WHERE ip = ?')->execute([$ip]);
            if ($usuario !== '') $pdo->prepare('DELETE FROM intentos_login_usuario WHERE usuario = ?')->execute([$usuario]);
            $pdo->exec('COMMIT');
            return ['bloqueado' => false, 'minutos' => 0, 'usuario' => $filaUsuario];
        }

        $pdo->prepare('INSERT INTO intentos_login (ip, intentos, ultimo_intento) VALUES (?, 1, ?)
                        ON CONFLICT(ip) DO UPDATE SET intentos = intentos + 1, ultimo_intento = excluded.ultimo_intento')
            ->execute([$ip, date('Y-m-d H:i:s')]);
        $stmtIp2 = $pdo->prepare('SELECT intentos FROM intentos_login WHERE ip = ?');
        $stmtIp2->execute([$ip]);
        $intentosIp = (int)$stmtIp2->fetchColumn();
        if ($intentosIp >= LOGIN_MAX_INTENTOS) {
            $pdo->prepare('UPDATE intentos_login SET bloqueado_hasta = ? WHERE ip = ?')
                ->execute([date('Y-m-d H:i:s', time() + LOGIN_BLOQUEO_MINUTOS * 60), $ip]);
        }

        $intentosCuenta = 0;
        if ($usuario !== '') {
            $pdo->prepare('INSERT INTO intentos_login_usuario (usuario, intentos, ultimo_intento) VALUES (?, 1, ?)
                            ON CONFLICT(usuario) DO UPDATE SET intentos = intentos + 1, ultimo_intento = excluded.ultimo_intento')
                ->execute([$usuario, date('Y-m-d H:i:s')]);
            $stmtCuenta2 = $pdo->prepare('SELECT intentos FROM intentos_login_usuario WHERE usuario = ?');
            $stmtCuenta2->execute([$usuario]);
            $intentosCuenta = (int)$stmtCuenta2->fetchColumn();
            if ($intentosCuenta >= LOGIN_MAX_INTENTOS) {
                $pdo->prepare('UPDATE intentos_login_usuario SET bloqueado_hasta = ? WHERE usuario = ?')
                    ->execute([date('Y-m-d H:i:s', time() + LOGIN_BLOQUEO_MINUTOS * 60), $usuario]);
            }
        }

        $minutosTras = max(
            $intentosIp >= LOGIN_MAX_INTENTOS ? LOGIN_BLOQUEO_MINUTOS : 0,
            $intentosCuenta >= LOGIN_MAX_INTENTOS ? LOGIN_BLOQUEO_MINUTOS : 0
        );
        $pdo->exec('COMMIT');
        return ['bloqueado' => $minutosTras > 0, 'minutos' => $minutosTras, 'usuario' => null];
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

/**
 * Crea las tablas si no existen y añade cualquier columna nueva que
 * falte (migraciones). Se llama automáticamente en cada petición
 * desde getDb(), así que la base de datos se pone al día sola en
 * cuanto se sube código nuevo, sin depender de que alguien recuerde
 * ejecutar init_db.php a mano.
 */
// Se incrementa cada vez que se añade una tabla o columna nueva al
// esquema. Gracias a esto, ejecutarMigracionesEsquema() solo hace el
// trabajo de verdad (CREATE TABLE / ALTER TABLE) la primera vez que
// se ejecuta con código nuevo, y no en cada petición: en el caso
// normal, se limita a una única consulta muy barata (PRAGMA
// user_version) y sale enseguida.
const VERSION_ESQUEMA_SAKONETA = 13;

function ejecutarMigracionesEsquema(PDO $pdo): void {
    $versionActual = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($versionActual >= VERSION_ESQUEMA_SAKONETA) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS noticias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        resumen TEXT NOT NULL,
        contenido TEXT NOT NULL,
        imagen TEXT,
        fecha TEXT NOT NULL,
        publicado INTEGER NOT NULL DEFAULT 1,
        likes INTEGER NOT NULL DEFAULT 0,
        imagen_posicion TEXT NOT NULL DEFAULT 'arriba'
    )");
    agregarColumnaSiFalta($pdo, 'noticias', 'likes', 'INTEGER NOT NULL DEFAULT 0');
    agregarColumnaSiFalta($pdo, 'noticias', 'imagen_posicion', "TEXT NOT NULL DEFAULT 'arriba'");
    agregarColumnaSiFalta($pdo, 'noticias', 'autor_id', 'INTEGER');
    // Versión en euskera, opcional: si se deja en blanco, la web
    // pública muestra automáticamente el texto en castellano en su
    // lugar (así el contenido antiguo, sin traducir, sigue viéndose
    // bien y no hay que traducirlo todo de golpe).
    agregarColumnaSiFalta($pdo, 'noticias', 'titulo_eu', 'TEXT');
    agregarColumnaSiFalta($pdo, 'noticias', 'resumen_eu', 'TEXT');
    agregarColumnaSiFalta($pdo, 'noticias', 'contenido_eu', 'TEXT');

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
    agregarColumnaSiFalta($pdo, 'categorias', 'imagen_portada', 'TEXT');
    agregarColumnaSiFalta($pdo, 'categorias', 'color', 'TEXT');

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
    agregarColumnaSiFalta($pdo, 'competiciones', 'imagen_portada', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'descripcion', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'imagen_posicion', "TEXT NOT NULL DEFAULT 'arriba'");
    agregarColumnaSiFalta($pdo, 'competiciones', 'hora', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'nombre_eu', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'lugar_eu', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'resultado_eu', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'descripcion_eu', 'TEXT');

    $pdo->exec("CREATE TABLE IF NOT EXISTS competicion_categorias (
        competicion_id INTEGER NOT NULL,
        categoria TEXT NOT NULL,
        PRIMARY KEY (competicion_id, categoria)
    )");

    // Documentos (PDF, DOCX) adjuntos a una competición: convocatoria,
    // hoja de resultados oficial, etc. Separados de las fotos porque
    // se muestran como una lista de descargas, no como una galería.
    $pdo->exec("CREATE TABLE IF NOT EXISTS competicion_documentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        competicion_id INTEGER NOT NULL,
        archivo TEXT NOT NULL,
        nombre_original TEXT NOT NULL,
        orden INTEGER NOT NULL DEFAULT 0
    )");

    // Horario de las gimnastas del club dentro de una competición,
    // extraído (con revisión humana obligatoria) del PDF de minutaje
    // que suba el club, o escrito a mano directamente.
    $pdo->exec("CREATE TABLE IF NOT EXISTS competicion_minutaje (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        competicion_id INTEGER NOT NULL,
        gimnasta_id INTEGER,
        nombre TEXT NOT NULL,
        hora TEXT,
        dato_extra TEXT,
        orden INTEGER NOT NULL DEFAULT 0
    )");
    // Migración: cada competición que ya tuviera una categoría en la
    // columna antigua pasa a tener también esa misma categoría aquí,
    // sin perder nada. A partir de ahora una competición puede tener
    // varias a la vez.
    $pdo->exec("INSERT OR IGNORE INTO competicion_categorias (competicion_id, categoria)
                SELECT id, categoria FROM competiciones WHERE categoria IS NOT NULL AND categoria != ''");

    foreach (['competicion_fotos' => 'competicion_id', 'noticia_fotos' => 'noticia_id', 'gimnasta_fotos' => 'gimnasta_id', 'categoria_fotos' => 'categoria_id'] as $tabla => $columnaId) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS $tabla (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            $columnaId INTEGER NOT NULL,
            archivo TEXT NOT NULL,
            orden INTEGER DEFAULT 0,
            tipo TEXT NOT NULL DEFAULT 'imagen'
        )");
        agregarColumnaSiFalta($pdo, $tabla, 'tipo', "TEXT NOT NULL DEFAULT 'imagen'");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS ajustes (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        splash_activo INTEGER NOT NULL DEFAULT 0,
        splash_imagen TEXT,
        inicio_imagen TEXT,
        inicio_imagen_titulo TEXT
    )");
    $pdo->exec("INSERT OR IGNORE INTO ajustes (id, splash_activo, splash_imagen, inicio_imagen, inicio_imagen_titulo)
                VALUES (1, 0, NULL, NULL, NULL)");
    foreach (['sobre_historia', 'sobre_palmares', 'hero_kicker', 'hero_titulo', 'hero_texto', 'nombre_sitio', 'eslogan_sitio', 'admin_password_hash', 'pie_titulo', 'pie_texto', 'hero_titulo_tamano', 'unete_horarios', 'unete_precio', 'unete_requisitos', 'unete_prueba'] as $columnaAjuste) {
        agregarColumnaSiFalta($pdo, 'ajustes', $columnaAjuste, 'TEXT');
    }
    foreach ([1, 2, 3, 4] as $n) {
        agregarColumnaSiFalta($pdo, 'ajustes', "est{$n}_valor", 'INTEGER');
        agregarColumnaSiFalta($pdo, 'ajustes', "est{$n}_texto", 'TEXT');
    }
    agregarColumnaSiFalta($pdo, 'ajustes', 'modo_evento_dias', 'INTEGER');

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_contacto (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        email TEXT NOT NULL,
        mensaje TEXT NOT NULL,
        fecha TEXT NOT NULL,
        leido INTEGER NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS patrocinadores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        logo TEXT,
        url TEXT,
        orden INTEGER DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS suscriptores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        fecha TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS intentos_login (
        ip TEXT PRIMARY KEY,
        intentos INTEGER NOT NULL DEFAULT 0,
        ultimo_intento TEXT,
        bloqueado_hasta TEXT
    )");

    // Bloqueo por cuenta de usuario, además del de por IP: así,
    // atacar la misma cuenta desde muchas IP distintas no sirve de
    // nada para saltarse el límite.
    $pdo->exec("CREATE TABLE IF NOT EXISTS intentos_login_usuario (
        usuario TEXT PRIMARY KEY,
        intentos INTEGER NOT NULL DEFAULT 0,
        ultimo_intento TEXT,
        bloqueado_hasta TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS limite_envios (
        ip TEXT NOT NULL,
        tipo TEXT NOT NULL,
        ventana_inicio TEXT NOT NULL,
        intentos INTEGER NOT NULL DEFAULT 1,
        PRIMARY KEY (ip, tipo)
    )");

    // Registro de visitas a la web pública (no al panel), solo para
    // estadísticas: de qué IP, a qué página y cuándo. Con retención
    // limitada (se purgan solas las más antiguas de 90 días, ver
    // registrarVisita()) para no acumular datos personales sin límite.
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        pagina TEXT NOT NULL,
        referente TEXT,
        fecha_hora TEXT NOT NULL
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visitas_ip ON visitas(ip)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visitas_fecha ON visitas(fecha_hora)");
    agregarColumnaSiFalta($pdo, 'visitas', 'dispositivo', 'TEXT');
    agregarColumnaSiFalta($pdo, 'visitas', 'idioma', 'TEXT');

    $pdo->exec("CREATE TABLE IF NOT EXISTS comentarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        noticia_id INTEGER NOT NULL,
        nombre TEXT NOT NULL,
        email TEXT,
        mensaje TEXT NOT NULL,
        fecha TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'pendiente'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT NOT NULL UNIQUE,
        nombre TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        rol TEXT NOT NULL DEFAULT 'colaborador',
        activo INTEGER NOT NULL DEFAULT 1,
        creado TEXT NOT NULL,
        session_version INTEGER NOT NULL DEFAULT 1
    )");
    agregarColumnaSiFalta($pdo, 'usuarios', 'session_version', 'INTEGER NOT NULL DEFAULT 1');
    // Permiso especial, aparte de los roles habituales: da acceso a
    // la página de estadísticas avanzadas (IPs, tiempo de navegación).
    // Se activa a mano, cuenta por cuenta, desde el propio listado de
    // usuarios — no es un rol más, es un interruptor extra sobre una
    // cuenta ya existente.
    agregarColumnaSiFalta($pdo, 'usuarios', 'es_tecnico', 'INTEGER NOT NULL DEFAULT 0');

    // Igual que las tablas y columnas, el primer usuario administrador
    // también se crea solo, sin depender de que alguien ejecute
    // init_db.php a mano tras esta actualización. Se respeta la
    // contraseña que ya hubiera (cambiada desde el panel de Ajustes en
    // versiones anteriores); si no había ninguna, se genera una nueva
    // al azar en este mismo momento — nunca se usa un hash fijo
    // guardado en el código fuente, porque un repositorio puede llegar
    // a ser público y ese hash quedaría ahí para siempre.
    $numUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($numUsuarios === 0) {
        $columnasAjustes = $pdo->query('PRAGMA table_info(ajustes)')->fetchAll();
        $hashPrevio = null;
        if (in_array('admin_password_hash', array_column($columnasAjustes, 'name'), true)) {
            $hashPrevio = $pdo->query('SELECT admin_password_hash FROM ajustes WHERE id = 1')->fetchColumn();
        }

        if ($hashPrevio) {
            $hashInicial = $hashPrevio;
        } else {
            $claveGenerada = bin2hex(random_bytes(9)); // 18 caracteres hexadecimales
            $hashInicial = password_hash($claveGenerada, PASSWORD_DEFAULT);
            $rutaAviso = CARPETA_PRIVADA . '/contrasena-inicial-admin.txt';
            @file_put_contents($rutaAviso, "Usuario: " . ADMIN_USER . "\nContraseña inicial: $claveGenerada\n\n" .
                "Este archivo se generó automáticamente porque no había ninguna contraseña de\n" .
                "administrador guardada todavía. Entra con estos datos y cámbiala cuanto antes\n" .
                "desde \"Cambiar contraseña\" en el panel. Después, borra este archivo del\n" .
                "servidor (ya está fuera de la carpeta pública del sitio, pero no hace falta\n" .
                "dejarlo ahí): rm " . $rutaAviso . "\n");
            error_log('Sakoneta: se ha generado una contraseña de administrador inicial. Consulta ' . $rutaAviso . ' en el servidor.');
        }

        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, nombre, password_hash, rol, activo, creado, session_version) VALUES (?,?,?,?,1,?,1)');
        $stmt->execute([ADMIN_USER, 'Administrador', $hashInicial, 'administrador', date('Y-m-d H:i:s')]);
    }

    $pdo->exec('PRAGMA user_version = ' . VERSION_ESQUEMA_SAKONETA);
}

/**
 * Añade una columna a una tabla si todavía no existe. Usado por
 * ejecutarMigracionesEsquema() para no repetir la misma comprobación
 * una y otra vez.
 */
function agregarColumnaSiFalta(PDO $pdo, string $tabla, string $columna, string $definicionSql): void {
    $columnas = $pdo->query("PRAGMA table_info($tabla)")->fetchAll();
    if (!in_array($columna, array_column($columnas, 'name'), true)) {
        try {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN $columna $definicionSql");
        } catch (PDOException $e) {
            // Si dos peticiones llegan a la vez justo después de un
            // despliegue, ambas pueden ver la columna como "no
            // existe todavía" y las dos intentar añadirla. La primera
            // gana; a la segunda solo le toca ignorar el error de
            // "ya existe" en vez de romper la petición.
            if (stripos($e->getMessage(), 'duplicate column') === false) {
                throw $e;
            }
        }
    }
}

/**
 * Recorta un texto a una longitud máxima sin depender de la extensión
 * mbstring (no siempre está instalada). Corta por espacio para no
 * partir una palabra a la mitad.
 */
function recortarTexto(string $texto, int $longitud): string {
    if (strlen($texto) <= $longitud) return $texto;
    $recortado = substr($texto, 0, $longitud);
    $ultimoEspacio = strrpos($recortado, ' ');
    if ($ultimoEspacio !== false) {
        $recortado = substr($recortado, 0, $ultimoEspacio);
    }
    return $recortado . '…';
}

/**
 * Detecta el caso en que PHP ha descartado toda la petición POST (incluidos
 * $_POST y $_FILES) por superar "post_max_size" en el servidor. En ese caso
 * $_POST y $_FILES llegan vacíos sin ningún otro aviso, así que hay que
 * comprobarlo explícitamente para poder mostrar un error claro en vez de
 * que el formulario parezca no hacer nada.
 */
function subidaDemasiadoGrande(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

/**
 * Comprueba que un archivo temporal es realmente una imagen del tipo esperado
 * (no solo que su nombre termine en .jpg/.png/.webp). Usa getimagesize(),
 * que lee la cabecera real del archivo.
 */
function esImagenValida(string $rutaTemporal, string $extensionEsperada): bool {
    $info = @getimagesize($rutaTemporal);
    if ($info === false) return false;

    $tiposValidos = [
        'jpg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'webp' => IMAGETYPE_WEBP,
    ];
    if (!isset($tiposValidos[$extensionEsperada]) || $info[2] !== $tiposValidos[$extensionEsperada]) {
        return false;
    }

    // Límite de dimensiones: una imagen con una resolución absurda
    // (por ejemplo, un PNG de 40000x40000 píxeles que en bytes pesa
    // poco pero al descomprimirla en memoria para procesarla ocupa
    // muchísima RAM) podría usarse para forzar un consumo de memoria
    // desproporcionado, aunque pase el límite de tamaño en bytes.
    $megapixeles = ($info[0] * $info[1]) / 1_000_000;
    if ($info[0] > 8000 || $info[1] > 8000 || $megapixeles > 40) {
        return false;
    }

    return true;
}

/**
 * Comprueba que un archivo temporal es realmente un vídeo del tipo esperado,
 * a partir de su tipo MIME real (no solo la extensión del nombre).
 */
function esVideoValido(string $rutaTemporal, array $mimesValidos): bool {
    if (!function_exists('finfo_open')) {
        // Sin la extensión fileinfo no podemos comprobar el contenido real;
        // en ese caso confiamos en la validación de extensión ya hecha antes.
        return true;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $rutaTemporal);
    finfo_close($finfo);
    return in_array($mime, $mimesValidos, true);
}

/**
 * Procesa varias fotos y/o vídeos subidos desde un campo
 * <input type="file" multiple>. Devuelve un array de arrays
 * ['archivo' => 'subidas/xxx.jpg', 'tipo' => 'imagen'|'video']
 * (puede estar vacío). Los archivos no válidos se ignoran salvo que
 * $errores se pase por referencia, en cuyo caso se añaden los mensajes.
 */
function procesarImagenesMultiples(string $campo, array &$errores = []): array {
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return [];
    }

    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $total = count($_FILES[$campo]['name']);

    // Límites de CONJUNTO para toda la petición, además de los límites
    // por archivo (20 MB foto / 80 MB vídeo) que ya había: sin esto,
    // alguien con acceso al panel podría forzar un consumo excesivo de
    // CPU, RAM, disco y tiempo subiendo muchos archivos grandes a la
    // vez en un único envío. Se comprueban ANTES de tocar ningún
    // archivo, para poder rechazar el envío entero de golpe en vez de
    // dejar una subida a medias.
    //
    // Los colaboradores (el rol menos fiable con acceso a subir
    // archivos) tienen un límite bastante más estricto que
    // editores/administradores.
    $esColaborador = function_exists('rolActual') && rolActual() === 'colaborador';
    $maxArchivosPorPeticion = $esColaborador ? 5 : 15;
    $maxBytesTotalPeticion = ($esColaborador ? 40 : 150) * 1024 * 1024;
    $margenEspacioLibre = 200 * 1024 * 1024; // no dejar el disco a menos de esto

    // Límite de frecuencia: como máximo un número razonable de
    // envíos de subida por IP en una ventana de tiempo, para que no
    // se pueda automatizar un bombardeo de subidas aunque cada una
    // cumpla el resto de límites por separado.
    if (function_exists('getDb') && superaLimiteEnvios(getDb(), 'subida_admin', $esColaborador ? 8 : 20, 10)) {
        $errores[] = 'Se han hecho demasiadas subidas seguidas desde aquí en poco tiempo. Espera unos minutos y vuelve a intentarlo.';
        return [];
    }

    if ($total > $maxArchivosPorPeticion) {
        $errores[] = 'Se pueden subir como máximo ' . $maxArchivosPorPeticion . ' archivos en un mismo envío.';
        return [];
    }

    $bytesTotales = 0;
    foreach ($_FILES[$campo]['size'] as $tamano) {
        $bytesTotales += (int)$tamano;
    }
    if ($bytesTotales > $maxBytesTotalPeticion) {
        $errores[] = 'El conjunto de archivos pesa demasiado (máximo ' . round($maxBytesTotalPeticion / (1024 * 1024)) . ' MB en total por envío, aunque cada uno esté dentro de su propio límite).';
        return [];
    }

    $espacioLibre = @disk_free_space($carpetaDestino);
    if ($espacioLibre !== false && $espacioLibre < ($bytesTotales + $margenEspacioLibre)) {
        $errores[] = 'No hay espacio suficiente en el servidor para esta subida. Avisa a quien administre el hosting.';
        return [];
    }

    $tiempoLimite = time() + 240; // deja margen antes de que salte el límite de PHP (300s)

    $extensionesImagen = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $extensionesVideo = ['mp4' => 'mp4', 'webm' => 'webm', 'mov' => 'mov'];
    $mimesVideo = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime', 'video/mp4'],
    ];

    $guardados = [];

    for ($i = 0; $i < $total; $i++) {
        if (time() > $tiempoLimite) {
            $errores[] = 'Se ha tardado demasiado procesando los archivos anteriores; el resto de este envío no se ha subido. Súbelos en un envío aparte, con menos archivos o más pequeños.';
            break;
        }

        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_INI_SIZE || $_FILES[$campo]['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" supera el límite de subida configurado en el servidor (revisa "upload_max_filesize" y "post_max_size" en PHP).';
            continue;
        }
        if ($_FILES[$campo]['error'][$i] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se ha podido subir "' . $_FILES[$campo]['name'][$i] . '".';
            continue;
        }

        $extension = strtolower(pathinfo($_FILES[$campo]['name'][$i], PATHINFO_EXTENSION));
        $esImagen = isset($extensionesImagen[$extension]);
        $esVideo = isset($extensionesVideo[$extension]);

        if (!$esImagen && !$esVideo) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es un formato admitido (JPG, PNG, WEBP para fotos; MP4, WEBM o MOV para vídeo).';
            continue;
        }

        $limiteMb = $esVideo ? 80 : 20;
        if ($_FILES[$campo]['size'][$i] > $limiteMb * 1024 * 1024) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" pesa demasiado (máximo ' . $limiteMb . ' MB).';
            continue;
        }

        if ($esImagen && !esImagenValida($_FILES[$campo]['tmp_name'][$i], $extensionesImagen[$extension])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es una imagen válida.';
            continue;
        }
        if ($esVideo && !esVideoValido($_FILES[$campo]['tmp_name'][$i], $mimesVideo[$extension])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es un archivo de vídeo válido.';
            continue;
        }

        $extensionFinal = $esImagen ? $extensionesImagen[$extension] : $extensionesVideo[$extension];
        $nombreFinal = 'subida-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extensionFinal;
        $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'][$i], $rutaFinal)) {
            if ($esImagen) {
                try {
                    redimensionarImagenSiHaceFalta($rutaFinal, $extensionFinal);
                } catch (Throwable $e) {
                    // Un fallo al redimensionar no es motivo para
                    // perder el resto del envío: se queda con el
                    // archivo original (ya validado y dentro de
                    // límites), sin redimensionar.
                    error_log('Sakoneta: no se pudo redimensionar ' . $rutaFinal . ': ' . $e->getMessage());
                }
            }
            $guardados[] = ['archivo' => 'subidas/' . $nombreFinal, 'tipo' => $esImagen ? 'imagen' : 'video'];
        } else {
            $errores[] = 'No se ha podido guardar "' . $_FILES[$campo]['name'][$i] . '".';
        }
    }

    return $guardados;
}

/**
 * Procesa la subida de documentos (PDF, DOCX) adjuntos a una
 * competición: convocatoria, resultados oficiales, etc. Con las
 * mismas comprobaciones de seguridad que procesarImagenesMultiples()
 * (tipo real del archivo, no solo la extensión; límites de tamaño,
 * de conjunto y de frecuencia por rol), adaptadas a documentos.
 */
function procesarDocumentosMultiples(string $campo, array &$errores = []): array {
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return [];
    }

    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $total = count($_FILES[$campo]['name']);
    $esColaborador = function_exists('rolActual') && rolActual() === 'colaborador';
    $maxArchivosPorPeticion = $esColaborador ? 3 : 10;
    $maxBytesTotalPeticion = ($esColaborador ? 20 : 80) * 1024 * 1024;
    $limiteMb = 20;

    if (function_exists('getDb') && superaLimiteEnvios(getDb(), 'subida_admin', $esColaborador ? 8 : 20, 10)) {
        $errores[] = 'Se han hecho demasiadas subidas seguidas desde aquí en poco tiempo. Espera unos minutos y vuelve a intentarlo.';
        return [];
    }
    if ($total > $maxArchivosPorPeticion) {
        $errores[] = 'Se pueden subir como máximo ' . $maxArchivosPorPeticion . ' documentos en un mismo envío.';
        return [];
    }

    $bytesTotales = 0;
    foreach ($_FILES[$campo]['size'] as $tamano) {
        $bytesTotales += (int)$tamano;
    }
    if ($bytesTotales > $maxBytesTotalPeticion) {
        $errores[] = 'El conjunto de documentos pesa demasiado (máximo ' . round($maxBytesTotalPeticion / (1024 * 1024)) . ' MB en total por envío).';
        return [];
    }

    $espacioLibre = @disk_free_space($carpetaDestino);
    if ($espacioLibre !== false && $espacioLibre < ($bytesTotales + 200 * 1024 * 1024)) {
        $errores[] = 'No hay espacio suficiente en el servidor para esta subida. Avisa a quien administre el hosting.';
        return [];
    }

    $tiposValidos = [
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];

    $guardados = [];
    for ($i = 0; $i < $total; $i++) {
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_INI_SIZE || $_FILES[$campo]['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" supera el límite de subida configurado en el servidor.';
            continue;
        }
        if ($_FILES[$campo]['error'][$i] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se ha podido subir "' . $_FILES[$campo]['name'][$i] . '".';
            continue;
        }

        $nombreOriginal = $_FILES[$campo]['name'][$i];
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (!isset($tiposValidos[$extension])) {
            $errores[] = '"' . $nombreOriginal . '" no es un formato admitido (solo PDF o DOCX).';
            continue;
        }
        if ($_FILES[$campo]['size'][$i] > $limiteMb * 1024 * 1024) {
            $errores[] = '"' . $nombreOriginal . '" pesa demasiado (máximo ' . $limiteMb . ' MB).';
            continue;
        }

        // Comprobar el tipo real del archivo, no solo su extensión.
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeReal = finfo_file($finfo, $_FILES[$campo]['tmp_name'][$i]);
            finfo_close($finfo);
            if (!in_array($mimeReal, $tiposValidos[$extension], true)) {
                $errores[] = '"' . $nombreOriginal . '" no es un archivo ' . strtoupper($extension) . ' válido.';
                continue;
            }
        }

        $nombreFinal = 'documento-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extension;
        $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'][$i], $rutaFinal)) {
            $guardados[] = ['archivo' => 'subidas/' . $nombreFinal, 'nombre_original' => $nombreOriginal];
        } else {
            $errores[] = 'No se ha podido guardar "' . $nombreOriginal . '".';
        }
    }

    return $guardados;
}

/**
 * Borra una foto de la galería de UNA entidad concreta (noticia,
 * gimnasta, categoría o competición), sin afectar a ninguna otra
 * entidad que pudiera compartir ese mismo archivo (elegido de la
 * biblioteca de medios ya subidos). A diferencia de
 * limpiarReferenciasArchivo() —pensada para el borrado deliberado y
 * global de un archivo desde la biblioteca de medios—, esta función
 * solo toca la relación de galería que se le pide, y el archivo
 * físico solo se elimina si, tras quitar esa relación, ya no lo usa
 * ninguna otra fila en ningún sitio.
 *
 * Devuelve el id de la entidad (para poder redirigir de vuelta a su
 * formulario), o 0 si no se encontró la foto.
 */
function borrarFotoDeEntidad(PDO $pdo, string $tabla, string $columnaId, string $columnaPortada, string $tablaFotos, int $fotoId): int {
    $stmt = $pdo->prepare("SELECT * FROM $tablaFotos WHERE id = ?");
    $stmt->execute([$fotoId]);
    $foto = $stmt->fetch();
    if (!$foto) return 0;

    $entidadId = (int)$foto[$columnaId];
    $archivo = $foto['archivo'];

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM $tablaFotos WHERE id = ?")->execute([$fotoId]);

        $stmtPortada = $pdo->prepare("SELECT $columnaPortada FROM $tabla WHERE id = ?");
        $stmtPortada->execute([$entidadId]);
        if ($stmtPortada->fetchColumn() === $archivo) {
            $siguiente = $pdo->prepare("SELECT archivo FROM $tablaFotos WHERE $columnaId = ? AND tipo = 'imagen' ORDER BY orden ASC LIMIT 1");
            $siguiente->execute([$entidadId]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare("UPDATE $tabla SET $columnaPortada = ? WHERE id = ?")->execute([$nuevaPortada, $entidadId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Solo ahora, con la relación de ESTA entidad ya quitada, se
    // comprueba si el archivo sigue haciendo falta en algún otro
    // sitio antes de borrarlo físicamente.
    eliminarArchivoSiNoSeUsa($pdo, $archivo);

    return $entidadId;
}

/**
 * Cuenta en cuántos sitios de la base de datos se usa un archivo de img/subidas/.
 */
function contarUsosArchivo(PDO $pdo, string $ruta): int {
    $total = 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM noticia_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gimnasta_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE imagen_portada = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categoria_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competiciones WHERE imagen_portada = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competicion_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ajustes WHERE splash_imagen = ? OR inicio_imagen = ?'); $stmt->execute([$ruta, $ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM patrocinadores WHERE logo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    return $total;
}

/**
 * Devuelve una lista de textos legibles indicando dónde se usa un archivo.
 */
function descripcionUsosArchivo(PDO $pdo, string $ruta): array {
    $usos = [];

    $stmt = $pdo->prepare('SELECT titulo FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $usos[] = 'Noticia: ' . $t;

    $stmt = $pdo->prepare('SELECT n.titulo FROM noticia_fotos f JOIN noticias n ON n.id = f.noticia_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $usos[] = 'Foto de noticia: ' . $t;

    $stmt = $pdo->prepare('SELECT nombre FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Gimnasta: ' . $n;

    $stmt = $pdo->prepare('SELECT g.nombre FROM gimnasta_fotos f JOIN gimnastas g ON g.id = f.gimnasta_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de gimnasta: ' . $n;

    $stmt = $pdo->prepare('SELECT nombre FROM categorias WHERE imagen_portada = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Portada de categoría: ' . $n;

    $stmt = $pdo->prepare('SELECT c.nombre FROM categoria_fotos f JOIN categorias c ON c.id = f.categoria_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de categoría: ' . $n;

    $stmt = $pdo->prepare('SELECT nombre FROM competiciones WHERE imagen_portada = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Portada de competición: ' . $n;

    $stmt = $pdo->prepare('SELECT c.nombre FROM competicion_fotos f JOIN competiciones c ON c.id = f.competicion_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de competición: ' . $n;

    $ajustes = $pdo->query('SELECT splash_imagen, inicio_imagen FROM ajustes WHERE id = 1')->fetch();
    if ($ajustes) {
        if ($ajustes['splash_imagen'] === $ruta) $usos[] = 'Pantalla de bienvenida (splash)';
        if ($ajustes['inicio_imagen'] === $ruta) $usos[] = 'Foto de portada de inicio';
    }

    $stmt = $pdo->prepare('SELECT nombre FROM patrocinadores WHERE logo = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Logo de patrocinador: ' . $n;

    return $usos;
}

/**
 * Borra físicamente un archivo de img/subidas/ solo si ya no lo usa nada en la base de datos.
 */
function eliminarArchivoSiNoSeUsa(PDO $pdo, ?string $ruta): void {
    if (!$ruta) return;
    if (contarUsosArchivo($pdo, $ruta) > 0) return;
    $rutaCompleta = __DIR__ . '/../img/' . $ruta;
    if (is_file($rutaCompleta)) {
        @unlink($rutaCompleta);
    }
}

/**
 * Redimensiona una imagen ya guardada si supera el ancho/alto máximo,
 * para que las fotos de móvil (a veces de más de 4000px) no viajen a
 * tamaño completo. Si la extensión GD no está disponible en el
 * servidor, no hace nada (la imagen se queda en su tamaño original).
 */
function redimensionarImagenSiHaceFalta(string $rutaCompleta, string $extension, int $maximoPx = 1600): void {
    if (!function_exists('imagecreatefromjpeg')) return;

    $cargar = ['jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp'];
    $guardar = ['jpg' => 'imagejpeg', 'png' => 'imagepng', 'webp' => 'imagewebp'];
    if (!isset($cargar[$extension]) || !function_exists($cargar[$extension])) return;

    $origen = @$cargar[$extension]($rutaCompleta);
    if (!$origen) return;

    $anchoOriginal = imagesx($origen);
    $altoOriginal = imagesy($origen);

    if (max($anchoOriginal, $altoOriginal) <= $maximoPx) {
        imagedestroy($origen);
        return;
    }

    $ratio = $maximoPx / max($anchoOriginal, $altoOriginal);
    $anchoNuevo = (int)round($anchoOriginal * $ratio);
    $altoNuevo = (int)round($altoOriginal * $ratio);

    $destino = imagecreatetruecolor($anchoNuevo, $altoNuevo);
    if ($extension === 'png' || $extension === 'webp') {
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
    }
    imagecopyresampled($destino, $origen, 0, 0, 0, 0, $anchoNuevo, $altoNuevo, $anchoOriginal, $altoOriginal);

    if ($extension === 'jpg') {
        $guardar[$extension]($destino, $rutaCompleta, 85);
    } elseif ($extension === 'webp') {
        $guardar[$extension]($destino, $rutaCompleta, 82);
    } else {
        $guardar[$extension]($destino, $rutaCompleta, 6);
    }

    imagedestroy($origen);
    imagedestroy($destino);
}

/**
 * Nombre del sitio: el que se haya guardado en Ajustes, o si no hay
 * ninguno, el nombre por defecto definido en config.php.
 */
function nombreSitio(): string {
    static $nombre = null;
    if ($nombre === null) {
        $ajustes = obtenerAjustes(getDb());
        $nombre = $ajustes['nombre_sitio'] ?: SITE_NAME;
    }
    return $nombre;
}

/**
 * Lema del sitio: el que se haya guardado en Ajustes, o si no hay
 * ninguno, el lema por defecto definido en config.php.
 */
function claimSitio(): string {
    static $claim = null;
    if ($claim === null) {
        $ajustes = obtenerAjustes(getDb());
        $claim = $ajustes['eslogan_sitio'] ?: SITE_CLAIM;
    }
    return $claim;
}

/**
 * Limpia TODAS las referencias posibles a un archivo (portada/foto
 * principal, filas de galería en cualquier entidad, ajustes,
 * patrocinadores) y borra el archivo físico al final. Es el único
 * punto donde se hace esta limpieza, para que el resultado sea
 * siempre el mismo se borre desde donde se borre (galería de una
 * noticia, biblioteca de medios, borrado en bloque...).
 */
function limpiarReferenciasArchivo(PDO $pdo, string $archivo): void {
    $entidades = [
        'noticias' => ['fotos' => 'noticia_fotos', 'columna_id' => 'noticia_id', 'columna_portada' => 'imagen'],
        'gimnastas' => ['fotos' => 'gimnasta_fotos', 'columna_id' => 'gimnasta_id', 'columna_portada' => 'foto'],
        'categorias' => ['fotos' => 'categoria_fotos', 'columna_id' => 'categoria_id', 'columna_portada' => 'imagen_portada'],
        'competiciones' => ['fotos' => 'competicion_fotos', 'columna_id' => 'competicion_id', 'columna_portada' => 'imagen_portada'],
    ];

    $pdo->beginTransaction();
    try {
        foreach ($entidades as $tabla => $info) {
            $columnaPortada = $info['columna_portada'];
            $tablaFotos = $info['fotos'];
            $columnaId = $info['columna_id'];

            $stmt = $pdo->prepare("SELECT id FROM $tabla WHERE $columnaPortada = ?");
            $stmt->execute([$archivo]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $pdo->prepare("DELETE FROM $tablaFotos WHERE archivo = ? AND $columnaId = ?")->execute([$archivo, $id]);
                $siguiente = $pdo->prepare("SELECT archivo FROM $tablaFotos WHERE $columnaId = ? AND tipo = 'imagen' ORDER BY orden ASC LIMIT 1");
                $siguiente->execute([$id]);
                $nuevaPortada = $siguiente->fetchColumn() ?: null;
                $pdo->prepare("UPDATE $tabla SET $columnaPortada = ? WHERE id = ?")->execute([$nuevaPortada, $id]);
            }
            // Por si el archivo era una foto de galería que no era la portada de nadie
            $pdo->prepare("DELETE FROM $tablaFotos WHERE archivo = ?")->execute([$archivo]);
        }

        $pdo->prepare('UPDATE ajustes SET splash_imagen = NULL, splash_activo = 0 WHERE splash_imagen = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE ajustes SET inicio_imagen = NULL, inicio_imagen_titulo = NULL WHERE inicio_imagen = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE patrocinadores SET logo = NULL WHERE logo = ?')->execute([$archivo]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // El archivo físico se borra después de confirmar la transacción,
    // nunca antes: si algo hubiera fallado a mitad, con el rollback
    // ya hecho, no queremos habernos cargado el archivo de todas formas.
    $rutaCompleta = __DIR__ . '/../img/' . $archivo;
    if (is_file($rutaCompleta)) {
        @unlink($rutaCompleta);
    }
}

/**
 * Lista los archivos (fotos y vídeos) ya subidos a img/subidas/, del
 * más reciente al más antiguo. Se usa para poder elegir una foto ya
 * subida en vez de tener que volver a subirla.
 */
function listarMediaSubida(): array {
    $carpeta = __DIR__ . '/../img/subidas';
    if (!is_dir($carpeta)) return [];
    $archivos = glob($carpeta . '/*.{jpg,jpeg,png,webp,mp4,webm,mov}', GLOB_BRACE);
    usort($archivos, fn($a, $b) => filemtime($b) <=> filemtime($a));

    $resultado = [];
    foreach ($archivos as $rutaCompleta) {
        $relativa = 'subidas/' . basename($rutaCompleta);
        $extension = strtolower(pathinfo($relativa, PATHINFO_EXTENSION));
        $resultado[] = ['archivo' => $relativa, 'tipo' => in_array($extension, ['mp4', 'webm', 'mov'], true) ? 'video' : 'imagen'];
    }
    return $resultado;
}

/**
 * Convierte lo que haya guardado en imagen_posicion (nuevo formato
 * "X Y" en porcentajes del selector visual, o el antiguo
 * 'arriba'/'centro'/'abajo') en una pareja [x, y] de porcentajes.
 */
function posicionXY(?string $posicion): array {
    if ($posicion && preg_match('/^(\d{1,3})\s+(\d{1,3})$/', trim($posicion), $m)) {
        return [max(0, min(100, (int)$m[1])), max(0, min(100, (int)$m[2]))];
    }
    return match ($posicion) {
        'centro' => [50, 50],
        'abajo' => [50, 90],
        default => [50, 12], // 'arriba', vacío o cualquier valor no reconocido
    };
}

/**
 * Traduce el encuadre elegido para una foto de portada al valor CSS
 * background-position correspondiente.
 */
function posicionCss(?string $posicion): string {
    [$x, $y] = posicionXY($posicion);
    return "{$x}% {$y}%";
}

/**
 * Límite de envíos por IP para formularios públicos (contacto,
 * comentarios, likes...), independiente del de intentos de login.
 * Cada $tipo lleva su propia cuenta. Devuelve true si esta IP ya ha
 * superado el máximo permitido en la ventana de tiempo indicada (en
 * ese caso, el que llama debe rechazar el envío).
 */
function superaLimiteEnvios(PDO $pdo, string $tipo, int $maxIntentos, int $minutosVentana): bool {
    $ip = ipVisitante();
    $ahora = time();

    $stmt = $pdo->prepare('SELECT ventana_inicio FROM limite_envios WHERE ip = ? AND tipo = ?');
    $stmt->execute([$ip, $tipo]);
    $ventanaActual = $stmt->fetchColumn();

    $ventanaCaducada = $ventanaActual === false || strtotime($ventanaActual) < ($ahora - $minutosVentana * 60);

    // El incremento (o el reinicio de la ventana) se hace en una única
    // operación SQL atómica, y solo DESPUÉS se comprueba el resultado.
    // Antes se leía "intentos" en PHP y, según ese valor ya desfasado,
    // se decidía si incrementar o no — con varias peticiones a la vez,
    // todas podían leer el mismo valor por debajo del límite y colarse
    // a la vez. Así, cada petición suma su propio +1 de verdad.
    if ($ventanaCaducada) {
        $pdo->prepare('INSERT INTO limite_envios (ip, tipo, ventana_inicio, intentos) VALUES (?, ?, ?, 1)
                        ON CONFLICT(ip, tipo) DO UPDATE SET ventana_inicio = excluded.ventana_inicio, intentos = 1')
            ->execute([$ip, $tipo, date('Y-m-d H:i:s', $ahora)]);
    } else {
        $pdo->prepare('UPDATE limite_envios SET intentos = intentos + 1 WHERE ip = ? AND tipo = ?')->execute([$ip, $tipo]);
    }

    $stmt = $pdo->prepare('SELECT intentos FROM limite_envios WHERE ip = ? AND tipo = ?');
    $stmt->execute([$ip, $tipo]);
    return (int)$stmt->fetchColumn() > $maxIntentos;
}

/**
 * Registra una visita a la web pública, con fines puramente
 * estadísticos, para la página de analítica avanzada (solo accesible
 * a quien tenga el permiso especial "técnico"). Se llama una vez por
 * página vista, desde includes/header.php — nunca desde el panel de
 * administración, para no mezclar la actividad del propio equipo con
 * la de las visitas reales.
 *
 * Retención limitada a propósito: en cada llamada hay una pequeña
 * probabilidad de purgar los registros de más de 90 días, para no
 * acumular datos personales (la IP lo es, bajo el RGPD) sin límite
 * de tiempo.
 */
function registrarVisita(PDO $pdo, string $pagina): void {
    $ip = ipVisitante();
    if ($ip === '') return;

    $referente = $_SERVER['HTTP_REFERER'] ?? null;
    if ($referente !== null) {
        if (stripos($referente, SITE_URL) === 0) {
            $referente = null;
        } elseif (strlen($referente) > 300) {
            $referente = substr($referente, 0, 300);
        }
    }

    $pdo->prepare('INSERT INTO visitas (ip, pagina, referente, fecha_hora, dispositivo, idioma) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$ip, $pagina, $referente, date('Y-m-d H:i:s'), dispositivoVisitante(), idiomaActual()]);

    if (mt_rand(1, 200) === 1) {
        $limite = date('Y-m-d H:i:s', strtotime('-90 days'));
        $pdo->prepare('DELETE FROM visitas WHERE fecha_hora < ?')->execute([$limite]);
    }
}

/**
 * Detección simple de dispositivo a partir del user-agent, sin
 * ninguna librería externa: suficiente para saber a grandes rasgos
 * si una visita fue desde el móvil o desde un ordenador de mesa.
 */
function dispositivoVisitante(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') return 'desconocido';
    return preg_match('/Mobi|Android|iPhone|iPad|iPod/i', $ua) ? 'movil' : 'escritorio';
}

/**
 * Exige el permiso especial de "técnico" (ver columna es_tecnico en
 * usuarios): no es un rol más, es un interruptor aparte que un
 * administrador activa cuenta a cuenta, pensado para dar acceso a la
 * página de estadísticas avanzadas sin mezclarlo con los roles
 * habituales del panel.
 */
function exigirTecnico(): void {
    exigirAutenticacion();
    if (empty($_SESSION['es_tecnico'])) {
        http_response_code(403);
        die('Acceso restringido.');
    }
}

/**
 * Borra una noticia por completo: sus fotos de galería, sus
 * comentarios y la propia noticia, todo en una transacción (o no se
 * borra nada, si algo falla a medias). Los archivos físicos solo se
 * eliminan después, y solo si ya no los usa ninguna otra entidad.
 */
function borrarNoticiaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM noticia_fotos WHERE noticia_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM noticia_fotos WHERE noticia_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM comentarios WHERE noticia_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una gimnasta por completo: sus fotos de galería y la propia
 * ficha, en una transacción. Ver borrarNoticiaCompleta().
 */
function borrarGimnastaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT foto FROM gimnastas WHERE id = ?');
    $stmt->execute([$id]);
    if ($foto = $stmt->fetchColumn()) $archivos[] = $foto;

    $stmt = $pdo->prepare('SELECT archivo FROM gimnasta_fotos WHERE gimnasta_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM gimnasta_fotos WHERE gimnasta_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM gimnastas WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una categoría por completo: sus fotos de galería y la propia
 * categoría, en una transacción. Ver borrarNoticiaCompleta(). No
 * toca a las gimnastas/competiciones que ya tuvieran asignado su
 * nombre (eso no cambia respecto a como funcionaba antes).
 */
function borrarCategoriaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen_portada FROM categorias WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM categoria_fotos WHERE categoria_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM categoria_fotos WHERE categoria_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una competición por completo: sus categorías asignadas, sus
 * fotos de galería y la propia competición, en una transacción. Ver
 * borrarNoticiaCompleta().
 */
function borrarCompeticionCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen_portada FROM competiciones WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM competicion_fotos WHERE competicion_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = $pdo->prepare('SELECT archivo FROM competicion_documentos WHERE competicion_id = ?');
    $stmt->execute([$id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM competicion_fotos WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competicion_documentos WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competicion_minutaje WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competicion_categorias WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competiciones WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
    // Los documentos no se comparten nunca entre varias competiciones
    // (a diferencia de las fotos, no hay un selector de "elegir uno ya
    // subido"), así que su archivo físico se borra directamente.
    foreach (array_unique(array_filter($documentos)) as $documento) {
        $rutaDocumento = __DIR__ . '/../img/' . $documento;
        if (is_file($rutaDocumento)) @unlink($rutaDocumento);
    }
}

/**
 * Datos ligeros de una competición (foto, categorías, título, fecha y
 * lugar) para la vista previa del swipe entre competiciones. Se usan
 * tanto para incrustarlos directamente en la página (sin depender de
 * ninguna petición de red aparte) como en el endpoint ?preview=1.
 */
function datosVistaPreviaCompeticion(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM competiciones WHERE id = ?');
    $stmt->execute([$id]);
    $competicion = $stmt->fetch();
    if (!$competicion) return null;

    $stmtCats = $pdo->prepare('
        SELECT cc.categoria FROM competicion_categorias cc
        JOIN categorias cat ON cat.nombre = cc.categoria
        WHERE cc.competicion_id = ?
        ORDER BY cat.orden ASC
    ');
    $stmtCats->execute([$id]);
    $categorias = $stmtCats->fetchAll(PDO::FETCH_COLUMN) ?: [$competicion['categoria']];

    $stmtFoto = $pdo->prepare('SELECT archivo FROM competicion_fotos WHERE competicion_id = ? ORDER BY orden ASC LIMIT 1');
    $stmtFoto->execute([$id]);
    $foto = $competicion['imagen_portada'] ?: ($stmtFoto->fetchColumn() ?: 'competicion.svg');

    return [
        'nombre' => campoIdioma($competicion, 'nombre'),
        'categorias' => $categorias,
        'imagen' => 'img/' . $foto,
        'posicion' => posicionCss($competicion['imagen_posicion'] ?? null),
        'fecha' => $competicion['fecha'],
        'hora' => $competicion['hora'],
        'lugar' => campoIdioma($competicion, 'lugar'),
        'disputada' => (bool)$competicion['disputada'],
        'resultado' => campoIdioma($competicion, 'resultado'),
    ];
}

/**
 * Ejecuta $accion() dentro de una transacción con bloqueo inmediato
 * de SQLite (BEGIN IMMEDIATE), reintentando unas cuantas veces si la
 * base de datos estuviera ocupada por otra petición en ese instante.
 * Así, comprobar una condición (por ejemplo, "¿queda algún otro
 * administrador?") y la escritura que depende de ese resultado
 * ocurren de forma realmente atómica frente a peticiones
 * concurrentes: nadie más puede leer ni escribir estas tablas hasta
 * que termine, así que no hay ninguna ventana en la que dos
 * peticiones vean a la vez la misma condición todavía sin aplicar.
 */
function ejecutarConBloqueo(PDO $pdo, callable $accion) {
    for ($intento = 0; ; $intento++) {
        try {
            $pdo->exec('BEGIN IMMEDIATE');
            break;
        } catch (PDOException $e) {
            if ($intento >= 8) throw $e;
            usleep(150000);
        }
    }
    try {
        $resultado = $accion();
        $pdo->exec('COMMIT');
        return $resultado;
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

/**
 * Comprueba que una URL es realmente http:// o https://, no solo que
 * "parece una URL" (FILTER_VALIDATE_URL también da por buenos otros
 * esquemas como javascript:, data: o file:, que no deberían acabar
 * nunca en un href de la web). Se usa en cualquier campo del panel
 * que pueda acabar en un href/src/action.
 */
function esUrlPermitida(string $url): bool {
    if ($url === '') return true; // el campo es opcional en los sitios donde se usa
    $partes = parse_url($url);
    return $partes !== false
        && isset($partes['scheme'], $partes['host'])
        && in_array(strtolower($partes['scheme']), ['http', 'https'], true)
        && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Color de acento de cada categoría, indexado por nombre. Las que no
 * tengan uno propio asignado usan el azul del sitio como color por
 * defecto, para que las píldoras de categoría se distingan de un
 * vistazo en los listados sin tener que leer el texto.
 */
function coloresCategorias(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    $stmt = $pdo->query('SELECT nombre, color FROM categorias');
    foreach ($stmt->fetchAll() as $fila) {
        $cache[$fila['nombre']] = $fila['color'] ?: '#1450C4';
    }
    return $cache;
}

function colorCategoria(PDO $pdo, ?string $nombre): string {
    if (!$nombre) return '#1450C4';
    $colores = coloresCategorias($pdo);
    return $colores[$nombre] ?? '#1450C4';
}

/**
 * Token CSRF de la sesión actual (se genera una vez y se reutiliza).
 * Sirve tanto para el panel como para los formularios públicos
 * (contacto, comentarios): no hace falta haber iniciado sesión como
 * administrador para tener uno, cualquier visitante ya tiene su
 * propia sesión de PHP desde que entra a la web.
 */
function tokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Campo oculto listo para insertar dentro de un <form method="post">.
 */
function campoCsrf(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Corta la ejecución con un 403 si el token CSRF (recibido por POST o GET)
 * no coincide con el de la sesión.
 */
function exigirCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Token de seguridad no válido o caducado. Vuelve a la página anterior e inténtalo de nuevo.');
    }
}

/**
 * Mensaje "flash": se guarda antes de una redirección y se lee (y
 * borra) una sola vez en la petición GET que llega después. Así el
 * patrón POST → redirección 303 → GET puede seguir mostrando un
 * mensaje de éxito sin tener que reenviar el formulario si se
 * refresca la página.
 */
function establecerFlash(string $clave, $valor): void {
    $_SESSION['flash'][$clave] = $valor;
}

function leerFlash(string $clave) {
    $valor = $_SESSION['flash'][$clave] ?? null;
    unset($_SESSION['flash'][$clave]);
    return $valor;
}

/**
 * Extrae el texto de un PDF con pdftotext (paquete poppler-utils),
 * si está instalado en el servidor. Devuelve null si no se puede
 * (no está instalado, shell_exec deshabilitado, o el PDF no tiene
 * texto real dentro —por ejemplo, una hoja escaneada como imagen—).
 * -layout intenta conservar la disposición en columnas, que suele
 * ayudar a que la hora y el nombre queden en la misma línea.
 */
function extraerTextoPdf(string $rutaAbsoluta): ?string {
    if (!is_file($rutaAbsoluta) || !function_exists('shell_exec')) {
        return null;
    }
    $ruta = trim((string)@shell_exec('which pdftotext 2>/dev/null'));
    if ($ruta === '' || !is_file($ruta)) {
        return null;
    }
    $comando = escapeshellarg($ruta) . ' -layout ' . escapeshellarg($rutaAbsoluta) . ' - 2>/dev/null';
    $salida = @shell_exec($comando);
    return $salida !== null && trim($salida) !== '' ? $salida : null;
}

/**
 * Busca, dentro del texto de un PDF de minutaje, las líneas que
 * mencionen a alguna gimnasta del club, y si hay una hora cerca en
 * esa misma línea, la extrae también. Esto es solo un BORRADOR: se
 * revisa y confirma a mano desde el panel antes de guardarse de
 * verdad, nunca se publica solo.
 */
function extraerMinutajeDeTexto(PDO $pdo, string $textoPdf): array {
    $gimnastas = $pdo->query('SELECT id, nombre FROM gimnastas ORDER BY orden ASC')->fetchAll();

    // Se evita a propósito la extensión mbstring (no se usa en
    // ningún otro sitio de este proyecto, así que no se puede dar
    // por hecho que esté instalada en el servidor): strtolower() ya
    // pasa a minúsculas el texto ASCII correctamente, y el mapa
    // siguiente cubre las mayúsculas acentuadas propias del
    // castellano/euskera que strtolower() no reconoce por ir en
    // varios bytes.
    $normalizar = function (string $texto): string {
        $texto = strtolower(trim(preg_replace('/\s+/', ' ', $texto)));
        return strtr($texto, ['Á'=>'á','É'=>'é','Í'=>'í','Ó'=>'ó','Ú'=>'ú','Ñ'=>'ñ','Ü'=>'ü']);
    };

    // Palabras del propio nombre del club, y términos habituales de
    // aparato/categoría, que no forman parte del nombre de ninguna
    // gimnasta: se usan para descartar candidatos que en realidad son
    // el nombre del club, y para recortar del final de un nombre
    // adivinado si se ha colado alguna (los minutajes suelen poner el
    // aparato justo después del nombre).
    $palabrasClub = ['sakoneta', 'cd', 'club', 'rg', 'ge', 'gimnasia', 'erritmiko', 'taldea', 'deportivo'];
    $palabrasNoNombre = array_merge($palabrasClub, [
        'aro', 'pelota', 'mazas', 'cinta', 'cuerda', 'conjunto', 'individual',
        'base', 'alevin', 'alevín', 'infantil', 'cadete', 'junior', 'júnior', 'senior', 'sénior',
        'prebenjamin', 'prebenjamín', 'benjamin', 'benjamín', 'juvenil', 'promesas', 'absoluto',
    ]);

    $lineas = preg_split('/\r\n|\r|\n/', $textoPdf);
    $resultados = [];
    $yaEncontrados = [];
    $nombresYaExtraidos = [];

    foreach ($lineas as $linea) {
        // Se guarda también la línea SIN colapsar los espacios: hace
        // falta tal cual para poder separarla por columnas más
        // abajo (pdftotext -layout separa cada columna de la tabla
        // con dos o más espacios seguidos, así que esos espacios de
        // más son la pista que dice dónde empieza y acaba cada dato,
        // independientemente de si el texto está en MAYÚSCULAS,
        // Mayúscula Inicial o minúsculas).
        $lineaOriginal = rtrim($linea, "\r\n");
        $lineaLimpia = trim(preg_replace('/\s+/', ' ', $linea));
        if ($lineaLimpia === '') continue;

        // Primer filtro, el que pedías: la fila tiene que mencionar
        // al club (Sakoneta o SAKONETA, sin importar mayúsculas) para
        // considerarse siquiera. "Sakoneta" no lleva ninguna letra
        // acentuada, así que basta con stripos() de toda la vida.
        if (stripos($lineaLimpia, 'sakoneta') === false) {
            continue;
        }

        $lineaNormalizada = $normalizar($lineaLimpia);
        preg_match('/\b([01]?\d|2[0-3])[:.hH]([0-5]\d)\b/', $lineaLimpia, $coincidenciaHora);
        $hora = $coincidenciaHora ? sprintf('%02d:%02d', (int)$coincidenciaHora[1], (int)$coincidenciaHora[2]) : null;

        // Dentro de una fila que ya sabemos que es del club, se
        // intenta reconocer a una gimnasta concreta del plantel...
        $gimnastaReconocida = null;
        foreach ($gimnastas as $g) {
            if (isset($yaEncontrados[$g['id']])) continue; // una aparición por gimnasta es suficiente
            $nombreNormalizado = $normalizar($g['nombre']);
            // Búsqueda de subcadena a nivel de bytes: sigue
            // funcionando bien en UTF-8 para comprobar "¿aparece
            // este texto dentro de este otro?", sin necesitar
            // mbstring para ello.
            if ($nombreNormalizado !== '' && strpos($lineaNormalizada, $nombreNormalizado) !== false) {
                $gimnastaReconocida = $g;
                break;
            }
        }

        if ($gimnastaReconocida) {
            $resultados[] = [
                'gimnasta_id' => (int)$gimnastaReconocida['id'],
                'nombre' => $gimnastaReconocida['nombre'],
                'hora' => $hora,
                'dato_extra' => $lineaLimpia,
            ];
            $yaEncontrados[$gimnastaReconocida['id']] = true;
            continue;
        }

        // ...y si no se reconoce a nadie del plantel actual (puede
        // ser una gimnasta nueva que todavía no esté dada de alta),
        // se intenta adivinar el nombre igualmente, para no perder
        // esa fila del todo.
        $candidato = null;

        // Método principal: columnas reales de la tabla. Se separa
        // la línea original (sin colapsar) por cada tramo de 2 o más
        // espacios seguidos, se localiza la columna que contiene el
        // nombre del club, y se coge la columna justo anterior como
        // nombre — así es como suelen venir estos listados (Orden,
        // Nombre, Club, Categoría, Aparato, Hora), y funciona igual
        // de bien en mayúsculas que en Mayúscula Inicial.
        $columnas = array_values(array_filter(
            array_map('trim', preg_split('/\s{2,}/', $lineaOriginal)),
            fn($c) => $c !== ''
        ));
        foreach ($columnas as $i => $col) {
            if ($i === 0 || stripos($col, 'sakoneta') === false) continue;
            $posibleNombre = $columnas[$i - 1];
            // Tiene que parecer un nombre de verdad: solo letras y
            // espacios, un par de palabras (no un número de orden ni
            // una categoría suelta de una sola palabra), y que no sea
            // a su vez el nombre de un club (p. ej. "Club Laredo",
            // que puede aparecer en otras partes del PDF —como el
            // listado de clubes participantes— junto al nombre del
            // club de Sakoneta sin ser una fila real de minutaje).
            if (!preg_match('/^[a-zA-ZÁÉÍÓÚÑÜáéíóúñü]+(?:\s+[a-zA-ZÁÉÍÓÚÑÜáéíóúñü]+){1,3}$/u', $posibleNombre)) {
                continue;
            }
            $posibleNormalizado = $normalizar($posibleNombre);
            $esNombreDeClub = false;
            foreach ($palabrasClub as $palabra) {
                if (strpos($posibleNormalizado, $palabra) !== false) { $esNombreDeClub = true; break; }
            }
            if (!$esNombreDeClub) {
                $candidato = $posibleNombre;
                break;
            }
        }

        // Último recurso, por si la fila no tuviera columnas bien
        // separadas por espacios (por ejemplo, un PDF sin tabla real,
        // solo texto corrido): se buscan tramos de 2 a 4 palabras que
        // empiecen en mayúscula, recortando del final aparatos o
        // categorías que se hayan colado.
        if ($candidato === null && preg_match_all('/\b(?:[A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]+(?:\s+[A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]+){1,3})\b/u', $lineaLimpia, $coincidenciasNombre)) {
            foreach ($coincidenciasNombre[0] as $posible) {
                $palabrasCandidato = explode(' ', $posible);
                while (count($palabrasCandidato) > 2 && in_array($normalizar(end($palabrasCandidato)), $palabrasNoNombre, true)) {
                    array_pop($palabrasCandidato);
                }
                $posible = implode(' ', $palabrasCandidato);
                $posibleNormalizado = $normalizar($posible);
                $esNombreDelClub = false;
                foreach ($palabrasClub as $palabra) {
                    if (strpos($posibleNormalizado, $palabra) !== false) { $esNombreDelClub = true; break; }
                }
                if (!$esNombreDelClub) { $candidato = $posible; break; }
            }
        }

        if ($candidato === null) continue;
        $candidatoNormalizado = $normalizar($candidato);
        if (isset($nombresYaExtraidos[$candidatoNormalizado])) continue;

        $resultados[] = [
            'gimnasta_id' => null,
            'nombre' => $candidato,
            'hora' => $hora,
            'dato_extra' => $lineaLimpia,
        ];
        $nombresYaExtraidos[$candidatoNormalizado] = true;
    }

    return $resultados;
}


function obtenerAjustes(PDO $pdo): array {
    $ajustes = $pdo->query('SELECT * FROM ajustes WHERE id = 1')->fetch();
    if (!$ajustes) {
        $pdo->exec("INSERT OR IGNORE INTO ajustes (id, splash_activo, splash_imagen, inicio_imagen, inicio_imagen_titulo) VALUES (1,0,NULL,NULL,NULL)");
        $ajustes = $pdo->query('SELECT * FROM ajustes WHERE id = 1')->fetch();
    }
    return $ajustes ?: ['splash_activo' => 0, 'splash_imagen' => null, 'inicio_imagen' => null, 'inicio_imagen_titulo' => null];
}

/**
 * Procesa una imagen subida por un formulario y la guarda en img/subidas/.
 * Devuelve el nombre de archivo generado, o null si no se subió nada válido.
 * $error se rellena con un mensaje si el archivo no es válido.
 */
function procesarImagenSubida(string $campo, ?string &$error = null): ?string {
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $archivo = $_FILES[$campo];
    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'La imagen supera el límite de subida configurado en el servidor (revisa "upload_max_filesize" en PHP).';
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se ha podido subir el archivo (código ' . $archivo['error'] . ').';
        return null;
    }
    if ($archivo['size'] > 20 * 1024 * 1024) {
        $error = 'La imagen pesa demasiado (máximo 20 MB).';
        return null;
    }
    $extensionesValidas = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $extensionOriginal = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!isset($extensionesValidas[$extensionOriginal])) {
        $error = 'Formato no soportado. Usa JPG, PNG o WEBP.';
        return null;
    }
    if (!esImagenValida($archivo['tmp_name'], $extensionesValidas[$extensionOriginal])) {
        $error = 'El archivo no es una imagen válida.';
        return null;
    }

    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $nombreFinal = 'subida-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extensionesValidas[$extensionOriginal];
    $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
        $error = 'No se ha podido guardar el archivo en el servidor.';
        return null;
    }
    redimensionarImagenSiHaceFalta($rutaFinal, $extensionesValidas[$extensionOriginal]);

    return 'subidas/' . $nombreFinal;
}
