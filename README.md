# Sakoneta — web del club (PHP + SQLite)

Sitio web de ejemplo para un club de gimnasia rítmica, con panel de
administración para gestionar noticias, gimnastas y competiciones.

> **Importante sobre la identidad visual**: el logo, los colores y los
> textos de este sitio son un diseño **original**, creado para este
> proyecto. No reproducen el logo ni el diseño real de ningún club.
> Si tienes el logo oficial del club, sustituye `img/escudo.svg` por
> tu propio archivo (por ejemplo `escudo.png`) y actualiza la ruta en
> `includes/header.php` y `admin/index.php`.

## Requisitos

- PHP 8.x con las extensiones `pdo_sqlite` y `sqlite3` (vienen activadas
  por defecto en la mayoría de instalaciones de PHP).
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

- **Noticias**: crear, editar, publicar/despublicar y borrar. La
  imagen se sube directamente desde tu ordenador.
- **Gimnastas**: nombre, categoría, modalidad (individual o conjunto),
  aparato principal y foto (subida directa desde tu ordenador).
- **Competiciones**: nombre, categoría, lugar, fecha y resultado
  una vez disputada.
- **Mensajes de contacto**: los mensajes enviados desde el
  formulario público quedan guardados y visibles aquí.
- **Ajustes del sitio**: subir una foto para la pantalla de bienvenida
  (splash) a pantalla completa que se muestra al entrar a la web, y
  otra foto para la portada, con pie de foto opcional. Las imágenes
  subidas se guardan en `img/subidas/`.
- **Categorías**: crear, renombrar, reordenar y borrar las categorías
  (Base, Alevín, Infantil...) que luego se eligen al dar de alta
  gimnastas y competiciones.
- **Fotos por competición**: al crear o editar una competición se
  pueden subir varias fotos a la vez y elegir cuál de ellas se usa
  como foto de portada/fondo en la tarjeta de esa competición en la
  web pública.
- **Fotos subidas**: biblioteca con todas las imágenes subidas desde
  cualquier parte del panel, indicando dónde se usa cada una y con
  opción de borrarlas (también se puede quitar una foto puntual desde
  el propio formulario de la noticia, gimnasta o ajuste donde se subió).
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

Todos estos efectos respetan la preferencia de "reducir movimiento"
del sistema operativo, y se degradan sin errores en navegadores que
no soporten alguna característica (por ejemplo, las transiciones de
página con View Transitions).

## Límite de tamaño de las fotos

La web admite fotos de hasta 6 MB, pero **PHP tiene su propio límite
por debajo de eso en muchas instalaciones por defecto** (a menudo
`upload_max_filesize = 2M`). Si una foto de un tamaño normal "no se
sube" sin más explicación, es casi seguro que es esto.

Para solucionarlo de raíz, el proyecto incluye dentro de `admin/`:
- `.htaccess` (efecto si el servidor usa **mod_php** en Apache)
- `.user.ini` (efecto si el servidor usa **PHP-FPM**)

Ambos suben el límite a 8-10 MB automáticamente; solo hace falta que
`AllowOverride All` esté activo para que el `.htaccess` funcione (ver
el apartado de despliegue en Debian más abajo). Si aun así seguís
viendo el problema, comprobad directamente los valores de
`upload_max_filesize` y `post_max_size` en el `php.ini` del servidor.

Cuando una foto supera el límite, ahora se muestra un aviso claro
indicando el problema, en vez de que el formulario parezca no hacer
nada.

## Seguridad

Antes de esta versión el panel no tenía protección contra CSRF ni
verificaba el contenido real de las imágenes subidas. Se ha reforzado con:

- **Token CSRF** en todos los formularios y enlaces que crean, editan
  o borran datos.
- **Verificación real de las imágenes subidas** (no solo la extensión
  del nombre de archivo) con `getimagesize()`.
- **Cookie de sesión reforzada** (`HttpOnly`, `SameSite=Lax`, `Secure`
  automático si detecta HTTPS) y regeneración del ID de sesión al
  iniciar sesión.
- **Pequeño retardo tras un login fallido** para dificultar ataques
  de fuerza bruta automatizados.
- **`img/subidas/` no puede ejecutar scripts** aunque alguien
  consiguiera subir un archivo con otra extensión.
- **`MODO_DEBUG`** en `config.php` (por defecto `false`): mantenlo así
  en un servidor real para que los errores de PHP no se muestren a
  los visitantes.
- `robots.txt` con `/admin/` y `/data/` bloqueados para buscadores.

Aun así, antes de publicar el sitio en un dominio real:
- Cambia la contraseña de administración (ver más abajo).
- Sirve el sitio por HTTPS.
- Revisa que `AllowOverride All` esté activo en Apache para que los
  `.htaccess` de `data/` e `img/subidas/` funcionen (o traslada esas
  reglas al `VirtualHost` si usas Nginx u otro servidor).

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

## Antes de publicarlo en un servidor real

- Cambia la contraseña de administración (ver arriba).
- Protege la carpeta `data/` para que no sea accesible desde el
  navegador (con Apache, añade un `.htaccess` con `Deny from all`;
  con Nginx, bloquea esa ruta en la configuración del servidor).
- Sustituye el logo, los colores, las direcciones de contacto y los
  textos de ejemplo por los datos reales del club.
- Sirve el sitio por HTTPS.
