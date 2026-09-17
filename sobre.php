<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'sobre';
$tituloPagina = 'Conoce Sakoneta';
$descripcionOG = 'Quiénes somos, nuestro equipo técnico y nuestra forma de trabajar en Sakoneta Gimnasia Erritmiko Taldea, el club de gimnasia rítmica de Leioa.';

$ajustes = obtenerAjustes($pdo);
$historia = $ajustes['sobre_historia'] ?? '';
$historiaEnAjustes = trim($historia) !== '';

require __DIR__ . '/includes/header.php';
?>

<section class="historia-hero animar-scroll">
  <div class="contenedor">
    <div class="historia-hero-etiqueta">Leioa, Bizkaia</div>
    <h1>Conoce Sakoneta</h1>
    <p>Un club, una escuela y una familia que lleva desde 1987 formando
      gimnastas en el Polideportivo Sakoneta — hecho por y para quienes lo
      viven cada temporada.</p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="bloque-etiqueta">Quiénes somos</div>
    <div class="seccion-cabecera">
      <h2>Qué es Sakoneta</h2>
    </div>
    <div class="conoce-grid">
      <div class="conoce-item animar-scroll">
        <div class="conoce-item-icono">📍</div>
        <h3>Dónde estamos</h3>
        <p>En el Polideportivo Sakoneta de Leioa (Bizkaia), el mismo pabellón
          donde todo empezó y donde el club sigue entrenando cada semana.</p>
      </div>
      <div class="conoce-item animar-scroll">
        <div class="conoce-item-icono">🗓️</div>
        <h3>Desde cuándo existimos</h3>
        <p>La escuela nace en 1985, impulsada por la entrenadora María Cruz
          Cobelas. En 1987, con la implicación de las familias, se funda
          oficialmente el Club Gimnasia Rítmica Sakoneta de Leioa.</p>
      </div>
      <div class="conoce-item animar-scroll">
        <div class="conoce-item-icono">🎗️</div>
        <h3>Qué significa el club</h3>
        <p>Sakoneta es, ante todo, una escuela: un lugar donde cualquier niña
          o niño puede empezar a soñar con la rítmica, sin que eso esté reñido
          con formar, cuando llega el momento, a gimnastas capaces de competir
          al más alto nivel nacional.</p>
      </div>
      <div class="conoce-item animar-scroll">
        <div class="conoce-item-icono">💜</div>
        <h3>Nuestra filosofía</h3>
        <p>Formación antes que resultados, cantera antes que fichajes, y un
          club abierto a cualquiera que quiera vivir la rítmica — incluida,
          desde 2025, una escuela de gimnasia rítmica inclusiva junto a la
          asociación Haszten.</p>
      </div>
    </div>

    <?php if ($historiaEnAjustes): ?>
    <div class="detalle-noticia" style="margin-top:32px;">
      <?php foreach (explode("\n\n", $historia) as $parrafo): ?>
        <?php if (trim($parrafo) !== ''): ?>
          <p><?= nl2br(e($parrafo)) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="seccion" style="background:var(--papel);">
  <div class="contenedor">
    <div class="bloque-etiqueta">Nuestro equipo</div>
    <div class="seccion-cabecera">
      <h2>Quienes hacen posible Sakoneta</h2>
    </div>
    <p style="max-width:70ch;color:var(--gris-texto);margin-top:-8px;margin-bottom:32px;">
      Detrás de cada gimnasta hay un equipo técnico, y detrás del equipo
      técnico, décadas de entrenadoras que se han ido pasando el testigo.
    </p>
    <div class="atletas-grid">
      <article class="atleta-card animar-scroll">
        <h3>María Cruz Cobelas</h3>
        <p class="atleta-intro">La entrenadora fundadora. Puso en marcha la
          escuela en 1985 junto a Jesús Vázquez, entonces director del
          Polideportivo Sakoneta.</p>
      </article>
      <article class="atleta-card animar-scroll">
        <h3>Judith Torralba</h3>
        <p class="atleta-intro">Al frente del equipo desde el año 2000 — más
          de dos décadas ininterrumpidas formando a varias generaciones de
          gimnastas de Sakoneta.</p>
      </article>
      <article class="atleta-card animar-scroll">
        <h3>Alba Lambea</h3>
        <p class="atleta-intro">Parte del cuerpo técnico actual, junto a
          Judith Torralba, en los conjuntos de mayor nivel del club.</p>
      </article>
      <article class="atleta-card animar-scroll">
        <h3>Eva Santamariña e Igone Arribas</h3>
        <p class="atleta-intro">Dos nombres muy especiales: dos exgimnastas
          del club (puedes leer su palmarés completo en
          <a href="historia.php" style="color:var(--fucsia);font-weight:600;">Historia y palmarés</a>)
          que, terminada su etapa como competidoras, volvieron a Sakoneta
          para formar a la siguiente generación desde el banquillo.</p>
      </article>
    </div>
    <p style="font-size:13px;color:var(--gris-texto);margin-top:28px;">
      ¿Coordinación, junta directiva o algún otro perfil del club que quieras
      que aparezca aquí? Este apartado está pensado para ampliarse con esos
      nombres en cuanto los tengamos.
    </p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="bloque-etiqueta">Nuestra forma de trabajar</div>
    <div class="seccion-cabecera">
      <h2>De los primeros pasos a la alta competición</h2>
    </div>
    <div class="metodologia-linea">
      <div class="metodologia-paso animar-scroll">
        <div class="metodologia-numero">1</div>
        <div>
          <h3>Iniciación</h3>
          <p>Los primeros contactos con la rítmica, en un ambiente lúdico y
            sin presión, incluida nuestra escuela inclusiva junto a Haszten.
            Aquí no importa el resultado: importa que a la niña o al niño le
            guste lo que está descubriendo.</p>
        </div>
      </div>
      <div class="metodologia-paso animar-scroll">
        <div class="metodologia-numero">2</div>
        <div>
          <h3>Base</h3>
          <p>Categorías de base (prebenjamín, benjamín, alevín...): se
            empiezan a trabajar los cinco aparatos —cuerda, aro, pelota,
            mazas y cinta— y aparecen las primeras competiciones, siempre
            adaptadas a la edad.</p>
        </div>
      </div>
      <div class="metodologia-paso animar-scroll">
        <div class="metodologia-numero">3</div>
        <div>
          <h3>Competición</h3>
          <p>Categorías infantil, cadete y juvenil, con un calendario de
            competiciones ya regular: campeonatos de Euskadi, de Bizkaia y,
            para quien alcanza el nivel, campeonatos de España base.</p>
        </div>
      </div>
      <div class="metodologia-paso animar-scroll">
        <div class="metodologia-numero">4</div>
        <div>
          <h3>Alto nivel</h3>
          <p>Categorías junior y sénior de Primera Categoría, con gimnastas y
            conjuntos peleando por podios en Campeonatos de España — y, para
            unas pocas a lo largo de la historia del club, la propia
            selección española.</p>
        </div>
      </div>
    </div>

    <div class="modalidades-grid">
      <div class="modalidad-card animar-scroll">
        <h3>🤸 Individual</h3>
        <p>Cada gimnasta compite en solitario con uno o varios aparatos,
          buscando la máxima expresión de su propio nivel técnico y
          artístico. Es el camino que siguieron Eider Mendizabal, Saioa
          Agirre o Eneko Lambea.</p>
      </div>
      <div class="modalidad-card animar-scroll">
        <h3>🤝 Conjuntos</h3>
        <p>Cinco gimnastas ejecutando el mismo ejercicio, sincronizadas al
          milímetro. Sakoneta compite en esta modalidad desde 1990, y aquí
          ha llegado a algunos de sus mayores éxitos: el título por equipos
          de 2016, el de conjuntos sénior de 2023, o el histórico bronce
          nacional de 2024.</p>
      </div>
    </div>
  </div>
</section>

<section class="seccion familia-crece">
  <div class="contenedor">
    <div class="bloque-etiqueta bloque-etiqueta-oro">Nuestra gente</div>
    <div class="seccion-cabecera">
      <h2>Una familia que crece generación tras generación</h2>
    </div>
    <p style="max-width:70ch;color:var(--gris-texto);margin-top:-8px;margin-bottom:32px;">
      Gimnastas que empezaron con seis años y hoy entrenan a las que
      empiezan ahora. Familias que llevan décadas en las gradas. Esto es
      Sakoneta, contado en fotos.
    </p>
    <div class="familia-fotos">
      <div class="familia-foto-hueco">
        <span>📷</span>
        <p>Espacio reservado para una foto real del club<br><small>(añádela desde el panel de administración)</small></p>
      </div>
      <div class="familia-foto-hueco">
        <span>📷</span>
        <p>Espacio reservado para una foto real del club<br><small>(añádela desde el panel de administración)</small></p>
      </div>
      <div class="familia-foto-hueco">
        <span>📷</span>
        <p>Espacio reservado para una foto real del club<br><small>(añádela desde el panel de administración)</small></p>
      </div>
    </div>
  </div>
</section>

<section class="seccion" style="background:var(--papel);">
  <div class="contenedor">
    <div class="historia-acceso">
      <div class="historia-acceso-emblema">🏆</div>
      <div class="historia-acceso-cuerpo">
        <div class="bloque-etiqueta bloque-etiqueta-oro">Historia y palmarés</div>
        <h3>Desde 1987, escribiendo la historia de la gimnasia rítmica vasca</h3>
        <p>Campeonas de España, conjuntos de Primera Categoría y gimnastas que
          han llegado a vestir el maillot de la selección española — todo
          empezó, y sigue empezando cada temporada, en la escuela del club.</p>
        <div class="historia-acceso-datos">
          <div><strong>1987</strong><span>Año de fundación</span></div>
          <div><strong>9+</strong><span>Podios en un solo Campeonato de España</span></div>
          <div><strong>3</strong><span>Gimnastas en la selección española</span></div>
        </div>
        <a href="historia.php" class="boton oro">Ver historia y palmarés completo →</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
