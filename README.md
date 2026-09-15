# Sakoneta — web del club (PHP + SQLite)

Sitio web de ejemplo para un club de gimnasia rítmica, con panel de
administración para gestionar noticias, gimnastas y competiciones.

> **Sobre el logo y los colores**: el logo (`img/logo-sakoneta.png`)
> es el logo real del club, a partir de una foto que nos pasasteis;
> lo hemos recortado, quitado el fondo y afilado un poco, pero la
> imagen original era de baja resolución, así que no hay milagros: si
> algún día tenéis el archivo original en alta calidad (o un diseñador
> os lo vectoriza), sustituir `img/logo-sakoneta.png` por esa versión
> mejorará bastante la nitidez en pantallas grandes. Los colores del
> sitio son azul eléctrico; si preferís otra paleta, las variables
> están todas centralizadas al principio de `css/styles.css` y
> `admin/css/admin.css` (busca `--acento`, `--morado`, etc.).

## Requisitos

- Los archivos CSS y JS (`css/styles.css`, `js/main.js`,
  `admin/css/admin.css`, `admin/js/admin.js`) se cargan con
  `?v=fecha_de_modificación` añadido automáticamente. Así, después de
  cada `git pull`, el navegador descarga la versión nueva en vez de
  quedarse con una copia antigua guardada en caché — no hace falta
  pedir a nadie que haga un refresco forzado (Ctrl+F5) tras cada
  actualización.

- PHP 8.x con las extensiones `pdo_sqlite` y `sqlite3` (vienen activadas
  por defecto en la mayoría de instalaciones de PHP).
- Extensión `gd` recomendada (para redimensionar automáticamente las
  fotos subidas). Si no está instalada, la web funciona igual, solo
  que las fotos se guardan a su tamaño original. En Debian/Ubuntu:
  `sudo apt install php-gd` y reinicia Apache o PHP-FPM.
- No necesitas instalar ningún servidor de base de datos: se usa un
  único archivo SQLite que se crea automáticamente.

## Puesta en marcha en local

1. Descomprime el proyecto y abre una terminal en esa carpeta.
2. Crea la base de datos y los datos de ejemplo (solo la primera vez):
   ```
   php init_db.php
   ```
   Debería mostrar: *Base de datos creada e inicializada correctamente...*
3. Arranca el servidor de desarrollo de PHP:
   ```
   php -S localhost:8000
   ```
4. Abre en el navegador:
   - Web pública: http://localhost:8000/
   - Panel de administración: http://localhost:8000/admin/

## Acceso al panel de administración

- Usuario: `admin`
- Contraseña: `sakoneta2026`

**Cambia la contraseña antes de publicar el sitio.** Genera un nuevo
hash con:
```
php -r "echo password_hash('tu_nueva_password', PASSWORD_DEFAULT);"
```
y sustituye el valor de `ADMIN_PASS_HASH` en `config.php`.

## Qué se puede gestionar desde el panel

- **Noticias**: crear, editar, publicar/despublicar y borrar. Se
  pueden subir varias fotos a la vez y elegir cuál se usa como
  principal; el resto forma una galería en la noticia.
- **Gimnastas**: nombre, categoría, modalidad (individual o conjunto)
  y aparato principal. Igual que en noticias, admite varias fotos con
  una marcada como principal; cada gimnasta tiene su propia página
  pública con galería.
- **Competiciones**: nombre, categoría, lugar, fecha, resultado una
  vez disputada, y un texto libre para contar cómo fue.
- **Mensajes de contacto**: los mensajes enviados desde el
  formulario público quedan guardados y visibles aquí.
- **Ajustes del sitio**: nombre del club y lema, editables (si se dejan
  en blanco, se usan los definidos en `config.php`); subir una foto
  para la pantalla de bienvenida (splash) a pantalla completa que se
  muestra al entrar a la web, y otra foto para la portada, con pie de
  foto opcional. Las imágenes subidas se guardan en `img/subidas/`.
  También se edita aquí el texto de la portada (entradilla, titular
  grande y párrafo de presentación) y **las 4 estadísticas** que
  aparecen bajo el titular (número y texto de cada una); si se dejan
  en blanco, se calculan solas (gimnastas, competiciones disputadas,
  categorías) o usan el valor por defecto.
- **Categorías**: crear, renombrar, reordenar y borrar las categorías
  (Base, Alevín, Infantil...) que luego se eligen al dar de alta
  gimnastas y competiciones. También admiten varias fotos propias y
  tienen su propia página pública, que además lista sus gimnastas y
  competiciones.
- **Fotos por competición**: al crear o editar una competición se
  pueden subir varias fotos a la vez y elegir cuál de ellas se usa
  como foto de portada/fondo en la tarjeta de esa competición en la
  web pública.
- **Información sobre el campeonato**: cada competición tiene un
  campo de texto libre (párrafos separados por una línea en blanco)
  que aparece en su página de detalle pública, junto con la galería
  de fotos.
- **Patrocinadores**: gestión desde el panel (nombre, logo y enlace
  opcional). Aparecen en el pie de página de toda la web como un
  **carrusel** de desplazamiento continuo (se pausa al pasar el
  ratón por encima, y no se anima si el sistema tiene activado
  "reducir movimiento").
- **Suscripción por email**: formulario en el pie de todas las
  páginas para recibir avisos de noticias nuevas. Los correos
  quedan guardados en el panel (sección "Suscriptores"), con opción
  de descargarlos en CSV — la web no los envía por sí sola, es una
  lista para usar con tu propio correo o herramienta de newsletter.
- **Splash con transición de zoom**: al cerrarse, la pantalla de
  bienvenida se desvanece con un efecto de zoom hacia dentro, más
  lento y vistoso que un simple fundido.
- **"Me gusta" en las noticias**: cada visitante puede darle a me
  gusta, y quitarlo si vuelve a pulsar (se recuerda con una cookie,
  sin necesidad de cuenta). El contador se actualiza al momento, sin
  recargar la página.
- **Comentarios con moderación**: cualquiera puede dejar un comentario
  en una noticia; queda pendiente hasta que alguien con permiso lo
  aprueba desde "Comentarios" en el panel (aprobar, rechazar o
  borrar). Solo se muestran en la web los aprobados. Incluye un campo
  señuelo invisible para filtrar bots.
- **Usuarios con roles**: en vez de una única contraseña compartida,
  ahora se pueden crear varias personas con acceso, cada una con su
  usuario y contraseña, y un rol que delimita lo que puede hacer:

  | Rol | Puede hacer |
  |---|---|
  | **Administrador** | Todo: contenido, ajustes del sitio, moderación y gestión de usuarios. |
  | **Editor** | Gestionar todo el contenido público (noticias, gimnastas, competiciones, categorías, patrocinadores, fotos) y publicarlo. No entra en Ajustes ni en Usuarios. |
  | **Moderador** | Solo revisa comentarios y mensajes de contacto. No toca contenido ni ajustes. |
  | **Colaborador** | Escribe y edita noticias, pero no puede publicarlas (quedan como borrador para que un editor o administrador las revise) ni borrarlas. No accede a ninguna otra sección. |

  Se gestionan desde "Usuarios" en el panel (solo visible para
  administradores). La cuenta `admin` de siempre se ha convertido
  automáticamente en el primer usuario, con rol administrador y la
  misma contraseña que ya tenía.
- **Filtro de categorías con selección múltiple**: en noticias,
  gimnastas y competiciones se pueden marcar varias categorías a la
  vez (antes solo una).
- **Deslizar entre competiciones en el móvil**: al abrir una
  competición desde un teléfono o tablet, se puede pasar a la
  anterior o siguiente (por fecha) deslizando el dedo, como en una
  galería. La primera vez aparece un aviso animado abajo indicándolo.
- **Tamaño del titular de la portada configurable**: en Ajustes, se
  puede elegir entre varios tamaños para el titular grande de la
  portada. Sigue ajustándose solo para caber en una línea.
- **Encuadre visual de la foto principal**: en noticias y
  competiciones, se puede hacer clic o arrastrar directamente sobre
  la foto (en el propio panel) para marcar el punto exacto que se
  quiere ver en la cabecera — útil para no cortar caras cuando la
  cabecera es alta o la foto no tiene la proporción ideal.
- **Próximas competiciones y resultados por separado**: la página
  "Competiciones" solo muestra las pendientes, ordenadas de la más
  próxima a la más lejana. Hay una página nueva, "Resultados"
  (`resultados.php`), con las ya disputadas y su resultado, ordenadas
  de la más reciente a la más antigua. Cada una enlaza a la otra.
- **Elegir fotos ya subidas**: al crear o editar una noticia o una
  competición, además de subir fotos/vídeos nuevos se puede abrir "O
  elige entre las fotos y vídeos ya subidos antes" y marcar cualquier
  archivo que ya esté en la biblioteca de medios, sin tener que
  volver a subirlo.
- **Fotos subidas**: biblioteca con todas las imágenes subidas desde
  cualquier parte del panel, indicando dónde se usa cada una. Se
  pueden seleccionar varias a la vez (o "Seleccionar todos") y
  borrarlas todas de golpe, además de poder borrar una suelta.
- **Cambiar contraseña**: desde el menú del panel, sin tocar ningún
  archivo ni ejecutar comandos. El usuario sigue siendo `admin`.
- **Pie de página**: título y texto de la primera columna del pie
  (visible en todas las páginas), editables desde Ajustes.
- **Vídeos**: además de fotos, el mismo selector de noticias,
  gimnastas, categorías y competiciones admite vídeo (MP4, WEBM o
  MOV, hasta 80 MB). Los vídeos se muestran con su propio reproductor
  en la galería pública, pero no se pueden usar como foto principal
  o portada (esa siempre tiene que ser una imagen).
- **Buscador y filtros**: en Noticias, Gimnastas y Competiciones se
  puede buscar por texto, y en Gimnastas/Competiciones también filtrar
  por categoría.
- **Reordenar arrastrando**: en Gimnastas y Categorías se puede
  arrastrar cada fila (icono ⠿) para cambiar el orden en que aparecen
  en la web; el nuevo orden se guarda solo, sin botón adicional.
- **Sobre el club**: nueva página pública con historia y palmarés,
  editables desde Ajustes del sitio.
- **Idiomas**: selector ES/EU en la cabecera para los textos fijos
  (menús, botones, títulos de sección). El contenido que escribe el
  club (noticias, nombres de gimnastas, lugares...) se muestra tal
  cual se redactó, en un único idioma — traducirlo no está automatizado.

## Experiencia de navegación

- **Foto de portada fundida con el texto**: la foto de portada de
  inicio ahora ocupa todo el ancho con un degradado que la funde con
  el título, en vez de mostrarse como una tarjeta aparte.
- **Página de cada competición** con su propia URL (antes las fotos
  extra subidas a una competición no se veían en ningún sitio, solo
  servían para elegir la portada): banner grande con parallax usando
  la foto de portada, y debajo una galería con el resto de fotos, cada
  una con su propio parallax al hacer scroll. Se accede haciendo clic
  en cualquier tarjeta de Competiciones.
- Las miniaturas de Competiciones combinan el parallax con el zoom al
  pasar el ratón que ya tenían.
- **Foto de la noticia como fondo espectacular**: en vez de una
  imagen pequeña bajo el título, ahora es un banner a todo lo ancho
  con la foto de fondo, un zoom lento al entrar (efecto "Ken Burns")
  y el mismo parallax al hacer scroll, con el título y la fecha
  superpuestos y un degradado que la funde con el resto de la página.
- **Countdown** a la próxima competición en la portada.
- **Parallax suave** en la foto de portada al hacer scroll.
- **Pestañas por categoría** en Gimnastas y Competiciones (filtran sin
  recargar la página).
- **Compartir** cada noticia en WhatsApp, Facebook o X, o copiar el
  enlace.
- **Barra de progreso de lectura** en las noticias.
- Un pequeño guiño: haz **5 clics seguidos sobre el escudo** del
  encabezado (o el de la pantalla de bienvenida) para un efecto
  sorpresa.
- **Menú en móvil**: botón de hamburguesa con el mismo menú desplegado.
- **Cabecera que se compacta** al hacer scroll (menos alto, sin el lema).
- **Foto de fondo del hero** de la portada, con parallax, configurable
  desde Ajustes (antes era una sección aparte debajo del hero; ahora
  es el propio fondo del titular). El titular ajusta su propio tamaño
  de letra en el navegador para caber siempre en una sola línea, sea
  cual sea el texto que pongas.
- **Vista previa al compartir enlaces** (Open Graph): si pegas el
  enlace de una noticia, competición, gimnasta o categoría en
  WhatsApp/Facebook/etc., sale con foto y descripción.
- **Fotos más ligeras**: cualquier foto subida que supere 1600px se
  redimensiona sola (requiere la extensión PHP `gd`; si el servidor no
  la tiene, simplemente no redimensiona, sin dar error).
- **Migas de pan** en noticia, competición, gimnasta y categoría.
- **Buscador público** (`buscar.php`), busca en noticias, gimnastas y
  competiciones a la vez.

Todos estos efectos respetan la preferencia de "reducir movimiento"
del sistema operativo, y se degradan sin errores en navegadores que
no soporten alguna característica (por ejemplo, las transiciones de
página con View Transitions).

## Seguridad

El panel se ha reforzado con:

- **La base de datos vive fuera de la carpeta pública del sitio**: en
  vez de `data/club.sqlite`, ahora se guarda un nivel por encima del
  proyecto (en una carpeta hermana, `sakoneta-datos-privados/`, fuera
  de lo que cualquier servidor web pueda llegar a servir). Esto
  importa sobre todo si el sitio se sirviera algún día con Nginx: el
  `.htaccess` de `data/` es una protección que **solo funciona en
  Apache** — con Nginx, sin una regla específica en su configuración,
  ese `.htaccess` se ignora y el archivo de la base de datos podría
  llegar a descargarse directamente. Sacarla de la carpeta pública
  elimina el problema de raíz, sea cual sea el servidor.
  **La migración es automática**: si ya tenías `data/club.sqlite` de
  antes, se traslada solo la primera vez que cargues cualquier página
  tras esta actualización — no hace falta mover nada a mano por SSH.
  Si por lo que sea el servidor no puede crear esa carpeta externa
  (permisos), se sigue usando `data/` como antes y queda avisado en
  el registro de errores de PHP.
- **La contraseña inicial también se genera ahí fuera**, en
  `sakoneta-datos-privados/contrasena-inicial-admin.txt`.
- **Los colaboradores solo pueden editar sus propias noticias, y solo
  mientras sigan sin publicar**: antes, un colaborador podía abrir y
  modificar (o despublicar) una noticia de otra persona, o una ya
  publicada, aunque el panel dijera que "no puede publicar ni
  borrar". Ahora se comprueba de verdad quién es el autor antes de
  dejar editar o borrar fotos de una noticia — un colaborador que lo
  intente con una noticia ajena o ya publicada recibe un aviso claro
  de que no tiene permiso, en vez de poder modificarla igualmente.
- **Límite de envíos en los formularios públicos**: contacto,
  comentarios y "me gusta" tienen ahora un límite de longitud
  aplicado en el servidor (no solo el `maxlength` del HTML, que
  cualquiera puede saltarse) y un límite de envíos por IP en una
  ventana de tiempo (5 mensajes/comentarios cada 10 minutos, 30
  likes cada 5 minutos). Evita que alguien pueda hinchar la base de
  datos automatizando envíos.
- **Las migraciones ya no se repiten en cada petición**: antes,
  cada página comprobaba de nuevo todas las tablas y columnas del
  esquema; ahora se guarda la versión del esquema dentro de la propia
  base de datos (`PRAGMA user_version`), así que en el caso normal es
  solo una consulta muy barata, y el trabajo de verdad (crear tablas,
  añadir columnas) solo se hace una vez, justo después de cada
  actualización de código. También se ha blindado ante el caso raro
  de que dos peticiones lleguen a la vez justo en ese momento.
- **El ID de sesión se regenera al cambiar de rol**: si un
  administrador asciende a otra persona (por ejemplo, de colaborador
  a editor) mientras esa persona tiene la sesión abierta, su
  identificador de sesión se renueva en su siguiente petición, como
  recomienda la buena práctica de seguridad al elevar privilegios.
- **Sin ningún secreto permanente en el código fuente**: ya no hay
  ningún hash de contraseña fijo en `config.php`. Si no hay ninguna
  contraseña de administrador guardada todavía, se genera una al azar
  en el primer arranque. Bórrala en cuanto la hayas anotado y
  cambiado desde el panel.
- **`.git/` bloqueado correctamente**: el `.htaccess` usa
  `RewriteRule` (válido ahí) en vez de `<DirectoryMatch>` (que NO es
  válido dentro de un `.htaccess` y podía provocar un error 500 en
  toda la web). Aun así, lo correcto es que la carpeta `.git/` nunca
  llegue al servidor de producción.
- **Revalidación en cada petición**: el rol y el estado (activo o no)
  de quien está conectado se comprueban contra la base de datos en
  cada página del panel, no solo al iniciar sesión. Si otro
  administrador cambia tu rol o te desactiva, se nota en la siguiente
  página que cargues, no hace falta esperar a que cierres sesión.
- **Cambiar la contraseña (la tuya o la de otro usuario) cierra las
  demás sesiones abiertas con la contraseña antigua al instante**
  (menos la tuya propia, si eres tú quien la cambia).
- **No te puedes quedar sin administradores**: no se puede desactivar,
  borrar ni quitarle el rol de administrador al único administrador
  activo que quede.
- **Token CSRF** en todos los formularios y acciones que crean, editan
  o borran datos.
- **Todas las acciones que crean, editan o borran algo van por POST**,
  nunca por GET.
- **Bloqueo real de fuerza bruta en el login**: tras 6 intentos
  fallidos, esa IP queda bloqueada 15 minutos.
- **La foto de portada de una galería solo puede ser una foto que
  pertenezca de verdad a esa noticia/gimnasta/categoría/competición**.
- **Borrado de archivos unificado**: da igual desde dónde se borre una
  foto, siempre se limpia de la misma forma y se reasigna la portada
  a otra foto disponible si la había.
- **Verificación real de las imágenes subidas** (no solo la extensión
  del nombre de archivo) con `getimagesize()`.
- **Cookie de sesión reforzada** (`HttpOnly`, `SameSite=Lax`, `Secure`
  automático si detecta HTTPS) y regeneración del ID de sesión al
  iniciar sesión.
- **`img/subidas/` no puede ejecutar scripts** aunque alguien
  consiguiera subir un archivo con otra extensión.
- **`MODO_DEBUG`** en `config.php` (por defecto `false`): mantenlo así
  en un servidor real para que los errores de PHP no se muestren a
  los visitantes.
- `robots.txt` con `/admin/` y `/data/` bloqueados para buscadores.
- **Sin listado de carpetas**: `Options -Indexes` en la raíz impide
  que el navegador muestre el contenido de ninguna carpeta del
  proyecto (`admin/css/`, `includes/`, `js/`, `img/`, `data/`...) al
  visitarla directamente sin un archivo `index`.

Aun así, antes de publicar el sitio en un dominio real:
- Sirve el sitio por HTTPS.
- Revisa que `AllowOverride All` esté activo en Apache para que los
  `.htaccess` de `data/`, `img/subidas/` y la raíz funcionen (o
  traslada esas reglas al `VirtualHost` si usas Nginx u otro servidor).
- Idealmente, despliega el sitio sin la carpeta `.git/` (copiando solo
  los archivos del proyecto, o con `git archive`), en vez de clonar el
  repositorio directamente dentro de la carpeta pública del servidor.

## Estructura del proyecto

```
config.php              Datos del sitio y credenciales de admin
init_db.php              Script de creación de la base de datos
includes/                Conexión a BD, funciones, cabecera y pie públicos
css/, js/, img/           Estilos, scripts e imágenes del sitio público
index.php, noticias.php, noticia.php,
gimnastas.php, competiciones.php, contacto.php   Páginas públicas
admin/                    Panel de administración (requiere login)
data/club.sqlite          Base de datos (se genera con init_db.php)
```

## Límite de tamaño de fotos y vídeos subidos

Por defecto, PHP suele traer `upload_max_filesize` en solo 2 MB, algo
muy fácil de superar con una foto de móvil normal (las cámaras de
gama alta pueden dar JPEG de 10-20 MB) y, sobre todo, con un vídeo.
Para evitarlo, el proyecto incluye dos ficheros en la raíz que elevan
ese límite a 90 MB por archivo (200 MB por envío, para cuando se
suben varias fotos o vídeos a la vez):

- **`.htaccess`**: funciona si PHP corre como módulo de Apache (mod_php).
  También sube `max_execution_time` y `max_input_time` a 300 segundos,
  necesario para que dé tiempo a subir un vídeo con buena conexión.
- **`.user.ini`**: funciona con PHP-FPM (no lee `.htaccess`). Puede
  tardar unos minutos en aplicarse, o necesitar recargar el servicio:
  `sudo systemctl reload php8.2-fpm` (ajusta la versión de PHP instalada;
  averigua el nombre exacto del servicio con
  `systemctl list-units --type=service --all | grep -i php`).

La propia aplicación limita las fotos a 20 MB y los vídeos a 80 MB
cada uno; si el servidor no llega a aplicar los 90 MB de arriba (por
ejemplo, por no tener `AllowOverride All`), esos límites de PHP
mandan igualmente y el formulario mostrará un aviso explicando el
motivo en vez de fallar en silencio.

Si usas Nginx con PHP-FPM en lugar de Apache, `.htaccess` no sirve de
nada (Nginx no lo lee): en ese caso el `.user.ini` sigue funcionando,
pero además puede que tengas que subir el límite de tamaño de subida
en la propia configuración de Nginx (`client_max_body_size`) y, si el
PHP-FPM está detrás de un proxy, el `proxy_read_timeout`.

**Para comprobar qué límite está aplicando tu servidor de verdad**
(no lo que digan los ficheros, sino el valor real que usa PHP en ese
directorio), sube `comprobar-limite.php` a la raíz del sitio, ábrelo
en el navegador y bórralo en cuanto lo hayas comprobado — no debe
quedar publicado de forma permanente.

## Base de datos y actualizaciones (importante)

Las tablas y columnas de la base de datos se crean y se actualizan
**solas**, en la primera petición tras cada `git pull` (desde
`includes/db.php`). Ya no hace falta ejecutar `init_db.php` a mano
después de cada actualización de código — solo la primera vez que
instalas el sitio, para cargar los datos de ejemplo.

Esto solucionó un error real: si el código de una página esperaba una
columna que la base de datos todavía no tenía (por ejemplo, tras
añadir el texto editable de la portada), se producía un
**Error 500** al guardar. Ahora es imposible que eso vuelva a pasar
por ese motivo, porque la migración se aplica sola en cuanto se
recibe la primera visita con el código nuevo. Además, la página de
Ajustes tiene ahora un `try/catch` general: cualquier fallo al
guardar (sea cual sea la causa) se muestra como un aviso legible en
vez de una pantalla en blanco de error 500, y el motivo exacto queda
anotado en el registro de errores de PHP del servidor.

## Antes de publicarlo en un servidor real

- Cambia la contraseña de administración (ver arriba).
- Protege la carpeta `data/` para que no sea accesible desde el
  navegador (con Apache, añade un `.htaccess` con `Deny from all`;
  con Nginx, bloquea esa ruta en la configuración del servidor).
- Sustituye el logo, los colores, las direcciones de contacto y los
  textos de ejemplo por los datos reales del club.
- Sirve el sitio por HTTPS.
