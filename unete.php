<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'unete';
$tituloPagina = 'Únete a Sakoneta';
$descripcionOG = '¿Quieres probar la gimnasia rítmica? Descubre tu grupo por edad y nivel, horarios, qué necesitas y cómo empezar en Sakoneta Gimnasia Erritmiko Taldea.';

$ajustes = obtenerAjustes($pdo);
$horarios = trim($ajustes['unete_horarios'] ?? '');
$precio = trim($ajustes['unete_precio'] ?? '');
$requisitos = trim($ajustes['unete_requisitos'] ?? '');
$prueba = trim($ajustes['unete_prueba'] ?? '');

$mensajePrellenado = rawurlencode('Hola, me gustaría recibir información para apuntarme (o apuntar a mi hija/hijo) a Sakoneta.');

require __DIR__ . '/includes/header.php';
?>

<section class="historia-hero animar-scroll unete-hero">
  <div class="contenedor">
    <div class="historia-hero-etiqueta">Escuela de gimnasia rítmica en Leioa</div>
    <h1>¿Quieres hacer gimnasia rítmica?</h1>
    <p>Da igual si nunca has pisado un tapiz o si llevas años soñando con
      ello: en Sakoneta hay un grupo pensado para ti. Te contamos todo lo
      que necesitas saber para dar el primer paso.</p>
    <a href="contacto.php?mensaje=<?= $mensajePrellenado ?>" class="boton oro unete-cta-grande">Quiero información →</a>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="bloque-etiqueta">Encuentra tu grupo</div>
    <div class="seccion-cabecera">
      <h2>Por edad y por nivel</h2>
    </div>
    <div class="grupos-grid">
      <article class="grupo-card animar-scroll">
        <div class="grupo-card-icono">🌱</div>
        <h3>Iniciación</h3>
        <p class="grupo-card-edad">Los primeros pasos</p>
        <p>Toma de contacto con la rítmica de forma lúdica, sin presión y sin
          necesidad de experiencia previa. Incluye nuestra escuela inclusiva
          junto a Haszten.</p>
      </article>
      <article class="grupo-card animar-scroll">
        <div class="grupo-card-icono">🤸</div>
        <h3>Base</h3>
        <p class="grupo-card-edad">Prebenjamín · Benjamín · Alevín</p>
        <p>Se empiezan a trabajar los cinco aparatos —cuerda, aro, pelota,
          mazas y cinta— y llegan las primeras competiciones, siempre
          adaptadas a la edad.</p>
      </article>
      <article class="grupo-card animar-scroll">
        <div class="grupo-card-icono">🏆</div>
        <h3>Competición</h3>
        <p class="grupo-card-edad">Infantil · Cadete · Juvenil · Junior · Sénior</p>
        <p>Para quienes quieren dar el salto a la competición federada, con
          un calendario regular de campeonatos de Euskadi, Bizkaia y España.</p>
      </article>
    </div>
    <p style="text-align:center;color:var(--gris-texto);font-size:14.5px;margin-top:24px;">
      ¿No tienes claro en qué grupo encajarías? No pasa nada — cuéntanoslo y te orientamos nosotras.
    </p>

    <div class="unete-aviso-inscripcion animar-scroll">
      <div class="unete-aviso-icono">📅</div>
      <div>
        <h3>¿Cuándo se abre la inscripción?</h3>
        <p>Las inscripciones se abren normalmente a principio de curso, no
          durante todo el año (este 2026 el plazo se abrió en mayo). Si nos
          escribes fuera de esas fechas, apuntamos tu interés, pero la forma
          más segura de no perderte la apertura del plazo es
          <strong>suscribirte a nuestras noticias</strong>: te avisamos por
          correo en cuanto se abra.</p>
        <a href="#suscripcion" class="boton contorno">Suscribirme a las noticias →</a>
      </div>
    </div>
  </div>
</section>

<section class="seccion" style="background:var(--papel);">
  <div class="contenedor">
    <div class="preguntas-grid">
      <div class="pregunta-card animar-scroll">
        <div class="pregunta-icono">📍</div>
        <h3>¿Dónde entrenamos?</h3>
        <p>En el Polideportivo Sakoneta, en Leioa (Bizkaia) — el mismo
          pabellón donde nació el club en 1987 y donde sigue entrenando hoy.</p>
      </div>
      <div class="pregunta-card animar-scroll">
        <div class="pregunta-icono">🗓️</div>
        <h3>¿Cuándo?</h3>
        <?php if ($horarios !== ''): ?>
          <p><?= nl2br(e($horarios)) ?></p>
        <?php else: ?>
          <p class="pregunta-pendiente">Los horarios varían según el grupo y la
            categoría. Escríbenos y te decimos exactamente cuándo entrena el
            grupo que te interesa.</p>
        <?php endif; ?>
      </div>
      <div class="pregunta-card animar-scroll">
        <div class="pregunta-icono">🎒</div>
        <h3>¿Qué necesitas?</h3>
        <?php if ($requisitos !== ''): ?>
          <p><?= nl2br(e($requisitos)) ?></p>
        <?php else: ?>
          <p class="pregunta-pendiente">Para empezar, solo ropa cómoda y
            ganas de moverte — nada de material específico. Si sigues
            adelante, más adelante te contamos qué hace falta para entrenar
            y competir.</p>
        <?php endif; ?>
      </div>
      <div class="pregunta-card animar-scroll">
        <div class="pregunta-icono">✨</div>
        <h3>¿Cómo puedo probar?</h3>
        <?php if ($prueba !== ''): ?>
          <p><?= nl2br(e($prueba)) ?></p>
        <?php else: ?>
          <p class="pregunta-pendiente">Escríbenos con la edad de la niña o
            el niño interesado y te proponemos un día para venir a probar una
            clase, sin ningún compromiso.</p>
        <?php endif; ?>
      </div>
      <div class="pregunta-card animar-scroll">
        <div class="pregunta-icono">💶</div>
        <h3>¿Cuánto cuesta?</h3>
        <?php if ($precio !== ''): ?>
          <p><?= nl2br(e($precio)) ?></p>
        <?php else: ?>
          <p class="pregunta-pendiente">La cuota depende del grupo y de si
            hay más de una gimnasta por familia inscrita. Pídenos información
            y te pasamos las tarifas actuales.</p>
        <?php endif; ?>
      </div>
      <div class="pregunta-card pregunta-card-cta animar-scroll">
        <h3>¿Lista o listo para dar el paso?</h3>
        <p>No hace falta decidir nada todavía. Escríbenos, cuéntanos tu caso,
          y te acompañamos desde el primer mensaje.</p>
        <a href="contacto.php?mensaje=<?= $mensajePrellenado ?>" class="boton oro" style="width:100%;text-align:center;">Quiero información →</a>
      </div>
    </div>
  </div>
</section>

<section class="unete-cierre animar-scroll">
  <div class="contenedor">
    <h2>El primer paso es el más sencillo</h2>
    <p>Ni siquiera hace falta que sepas todavía si esto es lo tuyo. Escríbenos
      y hablamos.</p>
    <a href="contacto.php?mensaje=<?= $mensajePrellenado ?>" class="boton oro unete-cta-grande">Quiero información</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
