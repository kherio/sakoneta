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
    <div class="bloque-etiqueta">Historia</div>
    <div class="seccion-cabecera">
      <h2>De 1987 a hoy</h2>
    </div>
    <div class="hitos-linea">

      <article class="hito animar-scroll">
        <div class="hito-anio hito-anio-fundacion">1985</div>
        <div class="hito-cuerpo">
          <h3>🌱 Los comienzos</h3>
          <p>En 1985 se funda la Escuela de Gimnasia Rítmica en el Polideportivo
            Sakoneta de Leioa, promovida por la entrenadora María Cruz Cobelas
            y por Jesús Vázquez, entonces director del polideportivo. A la
            vista del potencial de algunas de las niñas que acudían a la
            escuela, y con la aprobación y participación de las familias, en
            <strong>1987</strong> se funda oficialmente el Club Gimnasia Rítmica
            Sakoneta de Leioa.</p>
          <p>Aitziber Iriondo, Eva Susana Bernardo y Eider Mendizabal están
            entre las primeras gimnastas que destacan cuando el club empieza a
            competir a nivel provincial, autonómico y estatal. Ese mismo 1987,
            Eider es 5.ª en el Torneo Internacional de Pamplona, y poco después
            llegarían su título de campeona de España en cinta, un bronce
            nacional en pelota y el subcampeonato de España de 2.ª
            Categoría — la primera gran gimnasta de la historia del club.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio hito-anio-seleccion">1989</div>
        <div class="hito-cuerpo">
          <h3>🇪🇸 Eider Mendizabal, primera gimnasta de Sakoneta en la selección española</h3>
          <p>Eider se convierte en la primera gimnasta del club en vestir el
            maillot de la selección española. Participa en Campeonatos de
            Europa, donde consigue una medalla de bronce en la modalidad de
            conjuntos, y abre un camino que otras dos compañeras de Sakoneta
            seguirían en la década siguiente.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio hito-anio-seleccion">1993</div>
        <div class="hito-cuerpo">
          <h3>🇪🇸 Lorena Barbadillo, segunda gimnasta en la selección</h3>
          <p>Lorena Barbadillo se convierte en la segunda gimnasta de Sakoneta
            en formar parte de la selección española. Con el conjunto nacional
            conseguiría después medallas en los Campeonatos del Mundo
            celebrados en París.</p>
          <p>Antes de que acabara el siglo, Sakoneta ya había puesto a <strong>dos
            gimnastas en la selección española</strong> — un dato que pocos
            clubes de su tamaño pueden decir.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio hito-anio-seleccion">1996-2001</div>
        <div class="hito-cuerpo">
          <h3>⭐ Igone Arribas</h3>
          <p>Igone empieza en Sakoneta con 9 años. En categoría junior encadena
            una racha de resultados que la convierten en una de las grandes
            gimnastas de la historia del club:</p>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Oro en cuerda, Campeonato de España Individual B (1996)</li>
            <li><span class="logro-medalla">🥇</span> Oro en pelota, Campeonato de España Individual B (1997)</li>
            <li><span class="logro-medalla">🥈</span> Plata por autonomías (1999)</li>
            <li><span class="logro-medalla">🥉</span> Bronce por clubes (1999)</li>
            <li><span class="logro-medalla">🥉</span> Bronce en pelota (1999)</li>
            <li><span class="logro-medalla">🥉</span> Bronce en mazas (1999)</li>
            <li><span class="logro-medalla">🥇🥇</span> Dos oros con Sakoneta, Campeonato de España de Conjuntos (1999)</li>
          </ul>
          <p>En noviembre de 1999 entra en el conjunto sénior de la selección
            española, siendo la <strong>tercera gimnasta de Sakoneta</strong> en
            llegar al equipo nacional. En el año 2000 participa con España en
            los <strong>Juegos Olímpicos de Sídney</strong>, donde el conjunto
            español termina 10.º en la fase de clasificación — uno de los
            hitos más grandes de la historia del club.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio hito-anio-rango">2000-2012</div>
        <div class="hito-cuerpo">
          <h3>🌱 Generación de transición y consolidación</h3>
          <p>Una etapa de trabajo silencioso en la que Sakoneta sigue formando
            gimnastas y afianzando su lugar en la gimnasia rítmica vasca. En
            2013, Eva Santamariña ya es una gimnasta sénior consolidada —campeona
            de Euskadi sénior ese año, y participante con la selección vasca en
            el Campeonato de España de la Juventud— junto a otros nombres de su
            generación como Nerea Linares, Silvia Fuente, Sol Moreno, Marina
            Ortiz de Zárate y Naroa Pérez, que ya sumaban resultados
            autonómicos y nacionales antes del gran salto que vendría después.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2013</div>
        <div class="hito-cuerpo">
          <h3>🏆 La generación Saioa Agirre: primer gran salto nacional</h3>
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
        <div class="hito-anio">2021</div>
        <div class="hito-cuerpo">
          <h3>El adiós de Saioa Agirre</h3>
          <p>Tras años como la referencia indiscutible del club, Saioa Agirre
            se despide de la competición. Elige disputar su última temporada
            junto a su amiga y también campeona Maddi Otaola, entrenadas por
            Judith Torralba e <strong>Igone Arribas</strong> — la misma gimnasta
            que, dos décadas antes, había llevado a Sakoneta hasta los Juegos
            Olímpicos de Sídney, y que ahora forma a la siguiente generación
            desde el banquillo.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2022</div>
        <div class="hito-cuerpo">
          <h3>🚀 Nueva generación: Eneko Lambea, campeón de España</h3>
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
        <div class="hito-anio">2023</div>
        <div class="hito-cuerpo">
          <h3>🥇 Campeonas de España, categoría sénior</h3>
          <p>El conjunto sénior de Sakoneta se proclama <strong>campeón de
            España</strong> en el Campeonato de España de Conjuntos celebrado en
            Valladolid, liderando la categoría sénior con 56,550 puntos por
            delante del Ritmo (Castilla y León, plata) y el Arenas Corza's
            Maspalomas (Canarias, bronce). El punto de partida de la temporada
            que un año después culminaría con el histórico podio de 2024.</p>
        </div>
      </article>

      <article class="hito animar-scroll">
        <div class="hito-anio">2024</div>
        <div class="hito-cuerpo">
          <h3>Podio histórico en conjuntos</h3>
          <p>Tras revalidar el buen momento con una medalla de plata en la
            Copa de España de Primera Categoría en Zaragoza, Sakoneta alcanza
            por primera vez el podio en el Campeonato de España de Primera
            Categoría de conjuntos, consiguiendo una histórica <strong>medalla
            de bronce</strong> en Pamplona.</p>
          <p class="hito-equipo">Naroa Ribeiro · June Orza · Araia Salazar · Eneko Lambea · Julene Eraña</p>
          <p>Su ejercicio de 3 pelotas y 2 aros, con música de Los Cazafantasmas,
            les llevó hasta la tercera posición entre los conjuntos de mayor
            nivel del Estado.</p>
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

      <article class="hito animar-scroll">
        <div class="hito-anio">2025</div>
        <div class="hito-cuerpo">
          <h3>🤝 Una escuela para todas</h3>
          <p>Sakoneta se une a la asociación Haszten para poner en marcha una
            escuela de gimnasia rítmica inclusiva en el polideportivo del
            club, un proyecto pionero que apuesta por la inclusión real dentro
            de la estructura deportiva convencional de este deporte. Cuarenta
            años después de que naciera la primera escuela en este mismo
            pabellón, Sakoneta sigue abriendo sus puertas a quien quiera
            soñar con la rítmica.</p>
        </div>
      </article>

    </div>
  </div>
</section>

<section class="seccion">

  <div class="contenedor">
    <div class="bloque-etiqueta">Palmarés</div>
    <div class="seccion-cabecera">
      <h2>Nuestras grandes campeonas</h2>
    </div>
    <div class="atletas-grid">

      <article class="atleta-card animar-scroll">
        <h3>Eider Mendizabal</h3>
        <p class="atleta-intro">La primera gran gimnasta de la historia de Sakoneta, y la primera en llegar a la selección española.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">Finales de los 80</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Campeona de España en cinta</li>
            <li><span class="logro-medalla">🥈</span> Subcampeona de España de 2.ª Categoría</li>
            <li><span class="logro-medalla">🥉</span> Bronce nacional en pelota</li>
            <li><span class="logro-medalla">—</span> 5.ª en el Torneo Internacional de Pamplona (1987)</li>
          </ul>
        </div>
      </article>

      <article class="atleta-card animar-scroll">
        <h3>Igone Arribas</h3>
        <p class="atleta-intro">Empezó en Sakoneta con 9 años y llegó a los Juegos Olímpicos con la selección española.</p>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">1996-1997 · Junior</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥇</span> Oro en cuerda, Campeonato de España Individual B (1996)</li>
            <li><span class="logro-medalla">🥇</span> Oro en pelota, Campeonato de España Individual B (1997)</li>
          </ul>
        </div>
        <div class="atleta-anio">
          <div class="atleta-anio-etiqueta">1999</div>
          <ul class="hito-logros">
            <li><span class="logro-medalla">🥈</span> Plata por autonomías</li>
            <li><span class="logro-medalla">🥉</span> Bronce por clubes</li>
            <li><span class="logro-medalla">🥉</span> Bronce en pelota</li>
            <li><span class="logro-medalla">🥉</span> Bronce en mazas</li>
            <li><span class="logro-medalla">🥇🥇</span> Dos oros con Sakoneta, Campeonato de España de Conjuntos</li>
          </ul>
        </div>
      </article>

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

<section class="seleccion-espanola animar-scroll">
  <div class="contenedor">
    <div class="bloque-etiqueta bloque-etiqueta-oro">Cuadro de honor</div>
    <div class="seccion-cabecera">
      <h2>Sakoneta en la selección española</h2>
    </div>
    <p class="seleccion-espanola-intro">
      Tres gimnastas formadas en Sakoneta han llegado a vestir el maillot de
      la selección española, llevando el nombre del club hasta los grandes
      campeonatos internacionales — algo que pocos clubes de su tamaño pueden
      decir.
    </p>
    <div class="seleccion-grid">

      <article class="seleccion-card animar-scroll">
        <div class="seleccion-anio">1989</div>
        <h3>Eider Mendizabal</h3>
        <p class="seleccion-card-orden">1.ª gimnasta de Sakoneta en la selección española</p>
        <p>Participó en Campeonatos de Europa, donde consiguió una medalla de
          bronce en modalidad de conjuntos.</p>
        <p class="seleccion-card-nota">Su trayectoria abrió el camino para las dos gimnastas del club que llegarían a la selección en la década siguiente.</p>
      </article>

      <article class="seleccion-card animar-scroll">
        <div class="seleccion-anio">1993</div>
        <h3>Lorena Barbadillo</h3>
        <p class="seleccion-card-orden">2.ª gimnasta de Sakoneta en la selección española</p>
        <p>Con el conjunto español consiguió posteriormente medallas en los
          Campeonatos del Mundo celebrados en París.</p>
      </article>

      <article class="seleccion-card animar-scroll">
        <div class="seleccion-anio">1999-2001</div>
        <h3>Igone Arribas</h3>
        <p class="seleccion-card-orden">3.ª gimnasta de Sakoneta en la selección española</p>
        <p>Entró en el conjunto sénior en noviembre de 1999, tras una racha de
          medallas en categoría junior. En el año 2000 participó con España en
          los <strong>Juegos Olímpicos de Sídney</strong>, donde el conjunto
          español terminó 10.º en la fase de clasificación.</p>
        <p class="seleccion-card-nota">Antes de llegar a la selección absoluta, había conseguido títulos nacionales en categoría junior — ver su palmarés completo más arriba.</p>
      </article>

    </div>
  </div>
</section>

<section class="historia-cierre animar-scroll">
  <div class="contenedor">
    <h2>🌟 Una historia que sigue creciendo</h2>
    <p class="historia-cierre-cantera">Nada de esto habría sido posible sin la escuela. Detrás de
      cada podio, cada convocatoria y cada nombre propio de este palmarés hay
      cientos de horas de entrenamiento silencioso de las gimnastas de base:
      las que empiezan hoy con la cuerda o el aro, las que suben de categoría
      cada septiembre, las que puede que nunca salgan en un titular pero
      sostienen, temporada tras temporada, la cantera que hace posible que
      Sakoneta siga escribiendo historia.</p>
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
      Eva Santamariña · Sol Moreno · Eneko Lambea ·
      Asier Carrasco · Daria Rubtsova · y todas las gimnastas y gimnastas
      que han formado parte de Sakoneta.
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
