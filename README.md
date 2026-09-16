# Sakoneta — web del club de gimnasia rítmica

Sitio web del club **Sakoneta Gimnasia Erritmiko Taldea**, escrito en
PHP con una base de datos SQLite (un único archivo, sin necesidad de
instalar ningún servidor de base de datos aparte). Incluye la web
pública del club y un panel de administración privado desde el que
gestionar todo el contenido: noticias, gimnastas, competiciones,
categorías, patrocinadores, comentarios, mensajes de contacto y los
ajustes generales del sitio.

> **Sobre el logo y los colores**: el logo (`img/logo-sakoneta.png`)
> es el logo real del club, recortado y con el fondo quitado a partir
> de una foto de baja resolución; si en algún momento hay un archivo
> original en más calidad (o una versión vectorizada), sustituir ese
> mismo archivo mejorará la nitidez en pantallas grandes sin tocar
> nada más. Los colores del sitio son azul eléctrico; para cambiar la
> paleta, las variables están centralizadas al principio de
> `css/styles.css` y `admin/css/admin.css` (variables `--acento`,
> `--morado`, etc.).

## Qué incluye la web pública

- **Portada** con foto de fondo a pantalla completa (parallax al
  hacer scroll), titular grande de tamaño configurable, cuatro
  estadísticas del club (gimnastas, competiciones disputadas,
  categorías...) y cuenta atrás hasta la próxima competición.
- **Noticias**, con galería de fotos y vídeos, "me gusta" (se puede
  quitar volviendo a pulsar), comentarios con moderación, barra de
  progreso de lectura, botones para compartir en WhatsApp/Facebook/X
  y foto de cabecera a modo de banner con efecto de zoom lento y
  parallax al hacer scroll.
- **Gimnastas**, con ficha individual (categoría, modalidad, aparato
  principal) y galería de fotos y vídeos propia. Filtro por categoría
  (una sola categoría a la vez) y buscador.
- **Competiciones**: la página "Competiciones" solo muestra las
  próximas (pendientes), y una página aparte, "Resultados", las ya
  disputadas con su resultado. Cada competición tiene su propia
  página con banner, galería y descripción libre; se puede asignar
  más de una categoría a una misma competición (por ejemplo, si
  compiten juntas Base y Alevín). En el móvil, se puede deslizar el
  dedo dentro de una competición para pasar a la anterior o
  siguiente, viendo entrar la otra con la misma composición exacta
  que su cabecera real (foto con degradado, categoría y título),
  dando sensación de una página deslizándose sobre otra en vez de un
  salto brusco al aterrizar. El filtro por categoría admite marcar varias
  a la vez.
- **Categorías**: cada categoría del club (Base, Alevín, Infantil...)
  tiene su propia página pública, con galería de fotos propia y el
  listado de gimnastas y competiciones que pertenecen a ella.
- **Sobre el club**: página con la historia y el palmarés, editables
  desde el panel.
- **Contacto**: formulario que guarda los mensajes en el panel.
- **Suscripción por email**: formulario en el pie de todas las
  páginas; los correos quedan guardados en el panel para usarlos con
  una herramienta de newsletter externa (la web no envía correos).
- **Patrocinadores**: carrusel de desplazamiento continuo en el pie
  de página (se pausa al pasar el ratón, y no se anima si el sistema
  tiene activado "reducir movimiento").
- **Buscador público** (`buscar.php`), que busca a la vez en
  noticias, gimnastas y competiciones.
- **Vista previa al compartir enlaces** (Open Graph): al pegar el
  enlace de una noticia, competición, gimnasta o categoría en
  WhatsApp, Facebook, etc., aparece con foto y descripción.
- **Pantalla de bienvenida (splash)** a pantalla completa, opcional,
  con foto propia y transición de zoom suave al cerrarse.
- **Selector de idioma ES/EU** en la cabecera, para los textos fijos
  del sitio (menús, botones, títulos de sección). El contenido que
  redacta el club (noticias, nombres, lugares...) se muestra tal cual
  se escribió, en un único idioma.
- Menú adaptado a móvil, cabecera que se compacta al hacer scroll,
  migas de pan en las páginas de detalle, y un pequeño guiño: 5 clics
  seguidos sobre el escudo del encabezado activan un efecto sorpresa.
- **Lightbox en las galerías**: al pulsar cualquier foto de una
  galería (noticias, gimnastas, competiciones, categorías) se abre a
  pantalla completa, con flechas para pasar a la siguiente/anterior y
  cierre con Esc, clic fuera o el botón de cerrar.
- **Esqueletos de carga** en las imágenes de tarjetas y galerías,
  mientras terminan de descargar.
- **Colores de acento por categoría**: cada categoría (Base,
  Alevín...) puede tener su propio color, configurable desde el
  panel, que se usa en sus píldoras y en los filtros para
  reconocerlas de un vistazo.
- **Iconos en las estadísticas de portada** (gimnastas, competiciones
  disputadas, categorías, aparatos).
- **Autocompletado en el buscador**: sugerencias mientras se escribe,
  sin tener que pulsar "Buscar".
- **Vista de calendario en Competiciones**, como alternativa a la
  lista: un mes navegable con el nombre de cada competición en su
  día, enlazando directamente a su página.
- **Documentos en las competiciones**: convocatorias, resultados
  oficiales... en PDF o Word (DOCX), descargables desde la propia
  página de la competición.
- **Se puede instalar como aplicación** ("Añadir a pantalla de
  inicio") en el móvil, con su propio icono y pantalla de carga.
- **Modo oscuro**, activable con el interruptor de la cabecera; se
  recuerda en el navegador de quien lo elija.
- **Confeti** al abrir una competición cuyo resultado sea de podio
  (oro, plata, bronce, campeón...).
- **Línea de tiempo visual** para el palmarés en "Sobre el club".

Todos los efectos visuales respetan la preferencia de "reducir
movimiento" del sistema operativo, y se degradan sin errores si el
navegador no soporta alguna característica.

## Qué se gestiona desde el panel

- **Noticias**: título, resumen, contenido, fecha, varias fotos o
  vídeos con una imagen marcada como principal (con selector visual
  de encuadre: se hace clic o se arrastra sobre la propia foto para
  elegir qué parte se ve en la cabecera), publicar/despublicar y
  borrar.
- **Gimnastas**: nombre, categoría, modalidad (individual o
  conjunto), aparato principal, galería de fotos y vídeos con una
  marcada como principal.
- **Competiciones**: nombre, una o varias categorías, lugar, fecha,
  hora de inicio (opcional), resultado una vez disputada, texto libre
  para contar cómo fue, galería de fotos y vídeos con selector visual
  de encuadre para la portada, y documentos adjuntos (PDF o Word)
  como convocatorias o resultados oficiales.
- **Categorías**: crear, renombrar, reordenar (arrastrando), elegir
  su color de acento y borrar las categorías que luego se asignan a
  gimnastas y competiciones; cada una admite su propia galería de
  fotos.
- **Elegir fotos ya subidas**: al crear o editar una noticia o una
  competición, se puede reutilizar cualquier foto o vídeo ya subido
  antes desde cualquier otra parte del panel, sin tener que volver a
  subirlo.
- **Fotos subidas**: biblioteca con todas las imágenes y vídeos
  subidos, indicando dónde se usa cada uno; se pueden seleccionar
  varios (o "Seleccionar todos") y borrarlos de golpe.
- **Patrocinadores**: nombre, logo y enlace opcional.
- **Comentarios**: moderación de los comentarios dejados en noticias
  (aprobar, rechazar o borrar); solo se muestran en la web los
  aprobados. Incluye un campo señuelo invisible para filtrar bots.
- **Mensajes de contacto** y **Suscriptores**: consulta de lo
  recibido desde los formularios públicos; los suscriptores se pueden
  descargar en CSV.
- **Usuarios**: alta, baja, cambio de rol y restablecimiento de
  contraseña de las personas con acceso al panel (ver roles más
  abajo).
- **Ajustes del sitio**: nombre del club y lema; foto y activación de
  la pantalla de bienvenida; foto de portada de inicio con pie de
  foto opcional; textos de la portada (entradilla, titular grande y
  párrafo de presentación) y su tamaño de letra; las 4 estadísticas
  bajo el titular; historia y palmarés de "Sobre el club"; título y
  texto del pie de página. Cualquier campo que se deje en blanco usa
  el valor por defecto de `config.php` o se calcula solo (número de
  gimnastas, competiciones disputadas, categorías...).
- **Cambiar contraseña**: cada persona cambia la suya propia desde el
  menú del panel.

### Roles de usuario

| Rol | Puede hacer |
|---|---|
| **Administrador** | Todo: contenido, ajustes del sitio, moderación y gestión de usuarios. |
| **Editor** | Gestionar y publicar todo el contenido público (noticias, gimnastas, competiciones, categorías, patrocinadores, fotos). No entra en Ajustes ni en Usuarios. |
| **Moderador** | Solo revisa comentarios y mensajes de contacto. No toca contenido ni ajustes. |
| **Colaborador** | Escribe y edita sus propias noticias mientras sigan sin publicar (quedan como borrador para que un editor o administrador las revise); no puede publicarlas, borrarlas, ni tocar noticias ajenas o ya publicadas. No accede a ninguna otra sección. |

Se gestionan desde "Usuarios" en el panel (solo visible para
administradores). No se puede desactivar, borrar ni quitarle el rol
de administrador al único administrador activo que quede, para no
perder nunca el acceso al panel.

## Estructura del proyecto

```
config.php                Configuración general del sitio
init_db.php                Script de creación de la base de datos y datos de ejemplo
includes/                  Conexión a la base de datos, funciones comunes, cabecera y pie públicos
css/, js/, img/             Estilos, scripts e imágenes de la web pública
index.php, noticias.php, noticia.php,
gimnastas.php, gimnasta.php,
competiciones.php, resultados.php, competicion.php,
categoria.php, sobre.php, contacto.php, buscar.php   Páginas públicas
admin/                      Panel de administración
```

La base de datos y otros archivos sensibles **no** se guardan dentro
de esta carpeta del proyecto: ver "Dónde se guardan los datos" más
abajo.

## Configuración del sitio

Los valores generales viven en `config.php`:

- `SITE_NAME`, `SITE_SHORT`, `SITE_CLAIM`: nombre completo, nombre
  corto y lema del club, usados como valor por defecto si no se
  rellenan desde Ajustes en el panel.
- `MODO_DEBUG`: debe estar en `false` en un servidor real; muestra
  los errores de PHP en pantalla solo cuando está en `true`, pensado
  únicamente para desarrollo en local.
- Zona horaria fijada a `Europe/Madrid`.

### Dónde se guardan los datos

La base de datos SQLite y la contraseña inicial de administrador se
guardan **fuera de la carpeta pública del sitio**, nunca dentro de
`data/` ni de ninguna carpeta que un servidor web pueda llegar a
servir:

- Si existe la variable de entorno `SAKONETA_PRIVATE_DIR`, se usa esa
  ruta tal cual. Es la forma recomendada, porque es la única que se
  puede garantizar de verdad que queda fuera de lo público:
  - Apache (dentro del `<VirtualHost>`): `SetEnv SAKONETA_PRIVATE_DIR /var/lib/sakoneta`
  - PHP-FPM (en el pool): `env[SAKONETA_PRIVATE_DIR] = /var/lib/sakoneta`
- Si no se ha definido, se prueba con una carpeta hermana del
  proyecto (un nivel por encima), pero solo se usa si se puede
  confirmar de verdad, contra el `DOCUMENT_ROOT` real que informa el
  propio servidor, que queda fuera de lo público.
- Si no hay ninguna ubicación que se pueda confirmar segura, el sitio
  se detiene con un error explicando qué hacer, en vez de arriesgarse
  a guardar contraseñas y datos del club en una carpeta pública.

### Límite de tamaño de fotos y vídeos

La aplicación admite fotos de hasta 20 MB y vídeos de hasta 80 MB por
archivo (se pueden subir varios a la vez). Para que el servidor no
recorte antes ese límite, `.htaccess` y `.user.ini` (en la raíz y
dentro de `admin/`) elevan `upload_max_filesize` y `post_max_size` a
90 MB / 200 MB, y `max_execution_time`/`max_input_time` a 300
segundos. Si el servidor no llega a aplicar esos valores (por
ejemplo, por no tener `AllowOverride All` en Apache, o por usar
Nginx, que no lee `.htaccess`), el límite real de PHP manda igual y
el formulario muestra un aviso explicando el motivo en vez de fallar
en silencio.

## Base de datos y actualizaciones

Las tablas y columnas de la base de datos se crean y actualizan
**solas**: la versión del esquema se guarda dentro de la propia base
de datos (`PRAGMA user_version`), así que en el caso normal
comprobarlo es una única consulta muy barata, y el trabajo de verdad
(crear tablas, añadir columnas, generar el primer usuario
administrador si hace falta) solo se hace la primera vez que se
recibe una petición con código nuevo. No hace falta ejecutar ningún
comando a mano tras actualizar el código — solo la primera vez que se
instala el sitio, para cargar los datos de ejemplo (`init_db.php`).

## Seguridad

- **La ubicación de los datos privados se comprueba pase lo que
  pase**: tanto si se detecta automáticamente como si se configura a
  mano con `SAKONETA_PRIVATE_DIR`, se verifica igual contra el
  `DOCUMENT_ROOT` real; una configuración accidentalmente insegura se
  rechaza igual que la automática, en vez de confiar en que "se habrá
  configurado bien".
- **Proteger al último administrador es realmente atómico**:
  cambiarle el rol, desactivarlo o borrarlo pasa por una transacción
  con bloqueo inmediato de SQLite, así que ninguna combinación de
  peticiones concurrentes puede dejar la web sin ningún administrador
  activo.
- **Los enlaces de patrocinadores solo admiten `http://` o
  `https://`**, comprobado explícitamente por esquema (no solo "tiene
  forma de URL"), tanto al guardarlo como al mostrarlo.

- **Bloqueo de fuerza bruta del login realmente atómico**: la
  comprobación, la verificación de credenciales y el registro del
  resultado ocurren dentro de una única transacción con bloqueo
  inmediato de SQLite, así que ninguna petición concurrente puede
  colarse viendo un estado ya desactualizado. Se bloquea tanto por IP
  como por la cuenta de usuario a la que se apunta (cambiar de IP no
  sirve de nada si se sigue atacando la misma cuenta).
- **La IP real no se puede falsificar por cabecera HTTP**: por
  defecto se usa siempre la IP de la conexión TCP; solo se mira
  `X-Forwarded-For` si la petición viene de un proxy configurado
  explícitamente como confiable (variable de entorno
  `SAKONETA_TRUSTED_PROXIES`).
- **Límites de conjunto en las subidas** por rol (más estrictos para
  colaboradores), límite de dimensiones de imagen (evita imágenes
  "bomba" de resolución absurda), límite de frecuencia de subidas por
  IP, y un fallo al redimensionar ya no interrumpe el resto del envío.
- **Ninguna foto sustituida (splash, portada de inicio, logo de
  patrocinador) se queda huérfana en el disco**: se limpia la
  anterior en cuanto se sube una nueva, solo si ya no la usa nada más.
- **Borrado de archivos de la biblioteca de medios también
  transaccional**: si algo falla a mitad al limpiar sus referencias
  en noticias, gimnastas, categorías o competiciones, no se borra
  nada de nada (rollback completo) y el archivo físico solo se
  elimina después de confirmar que todas las referencias se
  limpiaron bien.

- **La migración de la base de datos antigua se comprueba de
  verdad**: si por lo que sea (permisos de archivo) no se puede
  mover ni eliminar la copia que hubiera dentro de la carpeta
  pública, el sitio se detiene con un error explicando qué hacer, en
  vez de seguir funcionando con esa copia (con usuarios, hashes y
  datos privados) accesible dentro de la carpeta pública.
- **Contadores de intentos sin condición de carrera**: tanto el
  bloqueo de fuerza bruta del login como el límite de envíos de los
  formularios públicos incrementan su contador con una única
  operación SQL atómica, no con una lectura y una escritura por
  separado — así, aunque lleguen varios intentos a la vez, ninguno se
  pierde ni dos peticiones pueden pisarse entre ellas.
- **Buscador con límites**: longitud máxima y mínima de la búsqueda,
  límite de resultados por sección, y límite de búsquedas por IP en
  una ventana de tiempo.
- **Límites de conjunto en las subidas**: además del límite por
  archivo (20 MB foto / 80 MB vídeo), un máximo de archivos y de
  bytes combinados por envío, comprobación de espacio libre en disco
  antes de procesar nada, y un límite de tiempo que corta el envío
  con un aviso claro en vez de dejar que salte el límite de PHP a
  medias.
- **`init_db.php` solo se puede ejecutar por línea de comandos**
  (SSH), nunca desde el navegador.

- **Contraseñas por usuario**, guardadas con hash, nunca en el código
  fuente. Si no hay ninguna contraseña de administrador guardada
  todavía, se genera una al azar en el primer arranque y se escribe
  una única vez en la carpeta privada (ver arriba).
- **Bloqueo de fuerza bruta** en el login (tras varios intentos
  fallidos, la IP queda bloqueada un tiempo) y **límite de envíos por
  IP** en los formularios públicos (contacto, comentarios, "me
  gusta"), con límite de longitud aplicado siempre en el servidor.
- **Revalidación en cada petición** del rol y el estado de la persona
  conectada contra la base de datos, con regeneración del ID de
  sesión al elevar privilegios, y cierre inmediato de las demás
  sesiones abiertas al cambiar una contraseña o desactivar una
  cuenta.
- **Los colaboradores solo pueden editar sus propias noticias, y solo
  mientras sigan sin publicar.**
- **Token CSRF** en todos los formularios y acciones que crean,
  editan o borran datos, y todas esas acciones van siempre por POST,
  nunca por GET.
- **Borrado con limpieza completa**: al borrar una noticia, gimnasta,
  categoría o competición se borran también sus fotos, comentarios y
  categorías asociadas en una única operación; los archivos físicos
  solo se eliminan del disco cuando ya no los usa ninguna otra
  entidad.
- **Verificación real de las imágenes subidas** (no solo la extensión
  del archivo), redimensionado automático de las que superan 1600px,
  y `img/subidas/` no puede ejecutar scripts aunque alguien
  consiguiera subir un archivo con otra extensión.
- **Cookie de sesión reforzada** (`HttpOnly`, `SameSite=Lax`,
  `Secure` automático si detecta HTTPS).
- **Sin listado de carpetas, funcione o no `Options -Indexes` en el
  servidor**: además de esa directiva en `.htaccess` (que depende de
  que `AllowOverride` lo permita), cada carpeta sin nada público que
  mostrar (`img/`, `css/`, `js/`, `includes/`, `data/`...) tiene su
  propio `index.php` que redirige a la portada del sitio en vez de
  dejar que el servidor liste su contenido. `.git/` sigue bloqueado
  en `.htaccess`; aun así, lo correcto es que esa carpeta nunca llegue
  al servidor de producción.
- `robots.txt` bloquea `/admin/` y `/data/` para los buscadores.

Aun con todo esto, antes de servir el sitio en un dominio real
conviene hacerlo por HTTPS y revisar que `AllowOverride All` esté
activo en Apache (o trasladar las reglas equivalentes al
`VirtualHost` o a la configuración de Nginx).
