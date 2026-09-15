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

- **Token CSRF** en todos los formularios y acciones que crean, editan
  o borran datos.
- **Todas las acciones que crean, editan o borran algo van por POST**,
  nunca por GET (antes los enlaces "Borrar" eran GET, lo que además
  del riesgo de CSRF es mala práctica: un enlace nunca debería borrar
  nada solo con visitarlo — por ejemplo, si un antivirus, un
  previsualizador de enlaces o el propio navegador precargan la URL).
- **Bloqueo real de fuerza bruta en el login**: tras 6 intentos
  fallidos, esa IP queda bloqueada 15 minutos (antes solo había un
  pequeño retardo, insuficiente por sí solo).
- **Contraseña de administrador cambiable desde el panel** ("Cambiar
  contraseña" en el menú), guardada en la base de datos en vez de en
  un archivo de código.
- **La foto de portada de una galería solo puede ser una foto que
  pertenezca de verdad a esa noticia/gimnasta/categoría/competición**
  (antes se aceptaba cualquier ruta enviada en el formulario sin
  comprobar que fuera realmente suya).
- **Borrado de archivos unificado**: da igual desde dónde se borre una
  foto (su propia galería, la biblioteca de medios, o un borrado en
  bloque), siempre se limpia de la misma forma y se reasigna la
  portada a otra foto disponible si la había.
- **Verificación real de las imágenes subidas** (no solo la extensión
  del nombre de archivo) con `getimagesize()`.
- **Cookie de sesión reforzada** (`HttpOnly`, `SameSite=Lax`, `Secure`
  automático si detecta HTTPS) y regeneración del ID de sesión al
  iniciar sesión.
- **`img/subidas/` no puede ejecutar scripts** aunque alguien
  consiguiera subir un archivo con otra extensión.
- **La carpeta `.git/` (y cualquier archivo oculto) no se puede
  acceder desde el navegador**, bloqueado en `.htaccess`. Sin esto,
  cualquiera podría descargarse todo el código y el historial del
  repositorio conociendo la URL.
- **`MODO_DEBUG`** en `config.php` (por defecto `false`): mantenlo así
  en un servidor real para que los errores de PHP no se muestren a
  los visitantes.
- `robots.txt` con `/admin/` y `/data/` bloqueados para buscadores.
- Ya no se incluye ningún script de diagnóstico en el proyecto
  publicado (revelaba configuración interna del servidor).

Aun así, antes de publicar el sitio en un dominio real:
- Sirve el sitio por HTTPS.
- Revisa que `AllowOverride All` esté activo en Apache para que los
  `.htaccess` de `data/`, `img/subidas/` y la raíz funcionen (o
  traslada esas reglas al `VirtualHost` si usas Nginx u otro servidor).
- Considera sacar `config.php` del control de versiones (añadirlo a
  `.gitignore` y sustituirlo por una plantilla `config.example.php`)
  para que ningún dato sensible quede nunca en el historial de git.
  No lo hemos hecho automáticamente porque, si tu servidor ya está
  desplegado como un clon de git, quitar `config.php` del repositorio
  haría que el próximo `git pull` **borre ese archivo también del
  servidor** y rompa el sitio — habría que hacerlo con cuidado,
  avísame si quieres que lo preparemos paso a paso.

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
