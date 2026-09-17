<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'historia';
$tituloPagina = 'Historia y palmarés';
$descripcionOG = 'Desde 1987, Sakoneta ha formado a generaciones de gimnastas y ha alcanzado algunos de los resultados más destacados de la gimnasia rítmica vasca.';
$migas = [['texto' => t('nav_sobre'), 'url' => 'sobre.php'], ['texto' => 'Historia y palmarés']];

require __DIR__ . '/includes/header.php';
?>

<section class="historia-hero animar-scroll">
  <div class="contenedor">
    <div class="historia-hero-etiqueta">Una historia construida sobre el tapiz</div>
    <h1>Logros y palmarés</h1>
    <p>Desde 1987, Sakoneta Gimnasia Erritmiko Taldea ha acompañado a generaciones de
      gimnastas en su camino hacia la competición, formando deportistas y alcanzando
      algunos de los resultados más destacados de la gimnasia rítmica vasca.</p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="detalle-noticia" style="margin-bottom:0;">
      <p>Nuestro palmarés es el reflejo de muchos años de trabajo, constancia, ilusión
        y compromiso. Una historia en la que los éxitos individuales conviven con
        grandes resultados de equipos y conjuntos, y en la que varias gimnastas
        formadas en Sakoneta han llegado a representar a Euskadi y a España.</p>
    </div>
  </div>
</section>

<section class="seccion" style="background:var(--papel);">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2>🏆 Grandes hitos</h2>
    </div>
    <div class="hitos-linea">

      <article class="hito animar-scroll">
        <div class="hito-anio">2013</div>
        <div class="hito-cuerpo">
          <h3>Primer gran salto nacional</h3>
          <p>Saioa Agirre se proclama campeona de España Infantil Absoluta,
            convirtiéndose en una de las grandes protagonistas de la gimnasia
            rítmica nacional y abriendo una etapa especialmente brillante para
            Sakoneta.</p>
          <p>Ese mismo año, los conjuntos del club consiguen:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇🥇</span> 2 oros en el Campeonato de Euskadi</li>
            <li><span class="logro-medalla">🥉</span> 1 bronce en el Campeonato de Euskadi</li>
            <li><span class="logro-medalla">—</span> 4.º puesto en el Campeonato de España Base</li>
            <li><span class="logro-medalla">🥉🥉</span> 2 bronces en la Copa de España</li>
            <li><span class="logro-medalla">—</span> Dos 10.º puestos en el Campeonato de España de Conjuntos</li>
          </ul>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2014</div>
        <div class="hito-cuerpo">
          <h3>Un año histórico</h3>
          <p>Sakoneta firma uno de los mejores campeonatos nacionales de su historia
            hasta ese momento. En el Campeonato de España Individual, el club
            consigue 9 podios, incluyendo:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Campeonato de España Junior Absoluto para Saioa Agirre</li>
            <li><span class="logro-medalla">🥇</span> Saioa Agirre en aro</li>
            <li><span class="logro-medalla">🥇</span> Saioa Agirre en pelota</li>
            <li><span class="logro-medalla">🥇</span> Saioa Agirre en cinta</li>
            <li><span class="logro-medalla">🥈</span> Saioa Agirre en mazas</li>
            <li><span class="logro-medalla">🥉</span> Eva Santamariña en categoría senior</li>
            <li><span class="logro-medalla">🥇</span> Campeonato por clubes en categoría junior</li>
            <li><span class="logro-medalla">🥈</span> Campeonato de España por Autonomías Junior</li>
            <li><span class="logro-medalla">🥉</span> Campeonato de España por Autonomías Infantil</li>
          </ul>
          <p>Además, Sakoneta se proclama campeón de la Liga Vasca de Gimnasia 2014.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2016</div>
        <div class="hito-cuerpo">
          <h3>Campeones de España por equipos</h3>
          <p>Uno de los grandes momentos de la historia del club llega en
            Guadalajara. Sakoneta se proclama <strong>campeón de España de Primera
            Categoría por equipos</strong>, en la primera edición de este formato.</p>
          <p class="hito-equipo">Saioa Agirre · Eva Santamariña · Sol Moreno · Saioa García</p>
          <p>Saioa Agirre, además, consiguió dos oros en las finales individuales.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2022</div>
        <div class="hito-cuerpo">
          <h3>Eneko Lambea, campeón de España</h3>
          <p>Eneko Lambea se proclama por tercer año consecutivo campeón de España
            de Primera Categoría masculina. En las finales por aparatos suma
            además:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Aro</li>
            <li><span class="logro-medalla">🥇</span> Cuerda</li>
            <li><span class="logro-medalla">🥇</span> Mazas</li>
          </ul>
          <p>Un nuevo capítulo en la trayectoria de uno de los gimnastas más
            representativos de Sakoneta.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2024</div>
        <div class="hito-cuerpo">
          <h3>Podio histórico en conjuntos</h3>
          <p>Sakoneta alcanza por primera vez el podio en el Campeonato de España
            de Primera Categoría de conjuntos, consiguiendo una histórica
            <strong>medalla de bronce</strong> en Pamplona.</p>
          <p class="hito-equipo">Naroa Ribeiro · June Orza · Araia Salazar · Eneko Lambea · Julene Eraña</p>
          <p>Su ejercicio de 3 pelotas y 2 aros les llevó hasta la tercera posición
            entre los conjuntos de mayor nivel del Estado.</p>
          <p>Ese mismo año, Eneko Lambea consigue la medalla de plata en Primera
            Categoría masculina en el Campeonato de España. Asier Carrasco logra
            el bronce en categoría junior y el ascenso a la máxima categoría.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2025</div>
        <div class="hito-cuerpo">
          <h3>Nuevos podios nacionales</h3>
          <p>Eneko Lambea vuelve a proclamarse subcampeón de España de Primera
            Categoría, por tercer año consecutivo. En las finales por aparatos
            consigue:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Cinta</li>
            <li><span class="logro-medalla">🥉</span> Mazas</li>
          </ul>
          <p>Asier Carrasco suma además:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥈</span> Aro</li>
            <li><span class="logro-medalla">🥈</span> Mazas</li>
          </ul>
          <p>Y Daria Rubtsova consigue la mejor puntuación de su rotación en pelota
            en categoría infantil.</p>
        </div>
      </article>

    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2>🥇 Palmarés individual</h2>
    </div>
    <div class="atletas-grid">

      <article class="atleta-card animar-scroll">
        <h3>Saioa Agirre</h3>
        <p class="atleta-intro">Una de las grandes gimnastas de la historia de Sakoneta.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2013 · Infantil</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Campeona de España Infantil Absoluta</li>
          </ul>
        </div>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2014 · Junior</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Campeona de España Junior Absoluta</li>
            <li><span class="logro-medalla">🥇</span> Aro</li>
            <li><span class="logro-medalla">🥇</span> Pelota</li>
            <li><span class="logro-medalla">🥇</span> Cinta</li>
            <li><span class="logro-medalla">🥈</span> Mazas</li>
          </ul>
          <p>En 2014, Saioa volvió a conquistar el título nacional tras haberlo
            conseguido el año anterior en categoría infantil.</p>
        </div>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2016</div>
          <p>En su debut en Primera Categoría, Saioa consigue resultados
            destacados a nivel nacional y forma parte del equipo de Sakoneta que
            se proclama campeón de España de Primera Categoría por equipos.</p>
        </div>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Eneko Lambea</h3>
        <p class="atleta-intro">Uno de los nombres propios de la historia reciente de
          Sakoneta. Comenzó su trayectoria en el club desde muy pequeño y se
          convirtió en uno de los referentes de la gimnasia rítmica masculina.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">Campeonato de España</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Campeón de España de Primera Categoría</li>
            <li><span class="logro-medalla">🥇</span> Campeón de España en 2020</li>
            <li><span class="logro-medalla">🥇</span> Campeón de España en 2021</li>
            <li><span class="logro-medalla">🥇</span> Campeón de España en 2022</li>
            <li><span class="logro-medalla">🥈</span> Subcampeón de España en 2024</li>
            <li><span class="logro-medalla">🥈</span> Subcampeón de España en 2025</li>
          </ul>
          <p>Además, ha conseguido numerosas medallas en finales por aparatos. En
            2022 consiguió tres oros: aro, cuerda y mazas.</p>
        </div>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Asier Carrasco</h3>
        <p class="atleta-intro">La nueva generación masculina de Sakoneta también
          está dejando su huella.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2024</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥉</span> Bronce en el Campeonato de España Junior</li>
            <li><span class="logro-medalla">🥉</span> Bronce en la final de mazas</li>
            <li><span class="logro-medalla">—</span> Ascenso a Primera Categoría</li>
          </ul>
        </div>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2025</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥈</span> Plata en aro</li>
            <li><span class="logro-medalla">🥈</span> Plata en mazas</li>
          </ul>
        </div>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Daria Rubtsova</h3>
        <p class="atleta-intro">Entre las nuevas generaciones destaca también Daria
          Rubtsova, que en 2025 consiguió la mejor puntuación de su rotación de
          pelota en el Campeonato de España Infantil.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">2026</div>
          <p>Alcanzó además el bronce nacional infantil, consolidándose como una
            de las jóvenes promesas del club.</p>
        </div>
      </article>

    </div>
  </div>
</section>

<section class="seccion" style="background:var(--papel);">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2>🏆 Palmarés de conjuntos y equipos</h2>
    </div>
    <div class="conjuntos-grid">

      <article class="conjunto-card animar-scroll">
        <div class="conjunto-anio">2013</div>
        <ul class="hito-logros">
          <li><span class="logro-medalla">🥇🥇</span> 2 oros en el Campeonato de Euskadi</li>
          <li><span class="logro-medalla">🥉</span> 1 bronce en el Campeonato de Euskadi</li>
          <li><span class="logro-medalla">—</span> 4.º puesto en el Campeonato de España Base</li>
          <li><span class="logro-medalla">🥉🥉</span> 2 bronces en la Copa de España</li>
          <li><span class="logro-medalla">—</span> Dos 10.º puestos en el Campeonato de España de Conjuntos</li>
        </ul>
      </article>

      <article class="conjunto-card animar-scroll">
        <div class="conjunto-anio">2014</div>
        <ul class="hito-logros">
          <li><span class="logro-medalla">🥇</span> Campeonato de España por clubes — categoría junior</li>
          <li><span class="logro-medalla">🥇</span> Campeonato de la Liga Vasca</li>
          <li><span class="logro-medalla">🥉</span> Campeonato de España por Autonomías Infantil</li>
          <li><span class="logro-medalla">🥈</span> Campeonato de España por Autonomías Junior</li>
        </ul>
      </article>

      <article class="conjunto-card animar-scroll">
        <div class="conjunto-anio">2016</div>
        <p><span class="logro-medalla">🥇</span> <strong>Campeones de España de Primera Categoría por equipos</strong></p>
        <p class="hito-equipo">Saioa Agirre · Eva Santamariña · Sol Moreno · Saioa García</p>
      </article>

      <article class="conjunto-card animar-scroll">
        <div class="conjunto-anio">2024</div>
        <p><span class="logro-medalla">🥉</span> <strong>Campeonato de España de Primera Categoría — Conjuntos</strong></p>
        <p class="hito-equipo">Naroa Ribeiro · June Orza · Araia Salazar · Eneko Lambea · Julene Eraña</p>
        <p>Primera medalla de Sakoneta en esta competición y uno de los grandes
          hitos de la historia del club.</p>
      </article>

    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2>🌍 Sakoneta y la gimnasia rítmica internacional</h2>
    </div>
    <p style="max-width:70ch;color:var(--gris-texto);margin-top:-8px;margin-bottom:32px;">
      La historia de Sakoneta también está vinculada a la gimnasia rítmica
      internacional a través de gimnastas formadas en el club que llegaron a
      representar a Euskadi y a España.
    </p>
    <div class="atletas-grid">

      <article class="atleta-card animar-scroll">
        <h3>Eider Mendizabal</h3>
        <p>Fue la primera gimnasta de Sakoneta en formar parte de la selección
          española, en 1989. Participó en competiciones internacionales y
          consiguió una medalla de bronce en modalidad de conjuntos en un
          Campeonato de Europa.</p>
        <p>Su trayectoria abrió el camino para futuras generaciones de gimnastas
          del club.</p>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Lorena Barbadillo</h3>
        <p>En 1993, se convirtió en la segunda gimnasta de Sakoneta en
          incorporarse a la selección española. Con el conjunto español
          consiguió posteriormente resultados destacados en Campeonatos del
          Mundo, incluyendo una medalla de plata y una de bronce.</p>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Igone Arribas</h3>
        <p>Formada en Sakoneta, llegó a formar parte de la selección española de
          conjuntos entre 1999 y 2001. Su trayectoria internacional incluye la
          participación en los Juegos Olímpicos de Sídney 2000, donde el
          conjunto español terminó en 10.ª posición.</p>
        <p>Antes de llegar a la selección absoluta, Arribas había conseguido
          títulos nacionales en categoría junior.</p>
      </article>

    </div>
  </div>
</section>

<section class="historia-cierre animar-scroll">
  <div class="contenedor">
    <h2>🌟 Una historia que sigue creciendo</h2>
    <p>Desde las primeras generaciones de Sakoneta hasta las actuales, el club
      ha construido una trayectoria marcada por la formación y por la
      presencia constante en las grandes competiciones nacionales.</p>
    <p>Campeonas de España, medallistas nacionales, equipos campeones,
      conjuntos de Primera Categoría y gimnastas que han llegado a vestir el
      maillot de la selección española forman parte de una misma historia.
      Una historia que continúa escribiéndose cada temporada.</p>
    <p class="historia-cierre-lema">Desde 1987, formando gimnastas.<br>Creando equipo.<br>Viviendo la rítmica.</p>
    <div class="historia-cierre-nombres">
      Eider Mendizabal · Lorena Barbadillo · Igone Arribas · Saioa Agirre ·
      Eva Santamariña · Sol Moreno · Izaro Martín · Eneko Lambea ·
      Asier Carrasco · Daria Rubtsova · y todas las gimnastas y gimnastas
      que han formado parte de Sakoneta.
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
