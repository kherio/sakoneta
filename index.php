<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'inicio';
$tituloPagina = 'Inicio';

$noticias = $pdo->query('SELECT * FROM noticias WHERE publicado = 1 ORDER BY fecha DESC LIMIT 4')->fetchAll();
$destacada = $noticias[0] ?? null;
$restoNoticias = array_slice($noticias, 1);

$proxima = $pdo->query("SELECT * FROM competiciones WHERE disputada = 0 ORDER BY fecha ASC LIMIT 1")->fetch();
$fechaProximaISO = null;
if ($proxima) {
    $fechaProximaISO = $proxima['fecha'] . 'T' . ($proxima['hora'] ?: '00:00') . ':00';
}

$ajustes = obtenerAjustes($pdo);

// "Modo evento": cuando falta poco para la próxima competición
// pendiente (y esa competición tiene foto propia), la portada entera
// cambia para darle todo el protagonismo, en vez de mostrar el
// titular y las estadísticas de siempre.
$diasModoEvento = isset($ajustes['modo_evento_dias']) && $ajustes['modo_evento_dias'] !== null ? (int)$ajustes['modo_evento_dias'] : 2;
$modoEvento = false;
$fotosModoEvento = [];
if ($proxima && !empty($proxima['imagen_portada']) && $diasModoEvento > 0) {
    $diasHastaProxima = (strtotime($proxima['fecha']) - strtotime(date('Y-m-d'))) / 86400;
    $modoEvento = $diasHastaProxima >= 0 && $diasHastaProxima <= $diasModoEvento;

    if ($modoEvento) {
        // La portada siempre va primero, y detrás las fotos de la
        // galería de esa competición (sin repetir la portada si
        // también estuviera ahí, y sin vídeos), hasta un máximo de 5.
        $fotosModoEvento[] = $proxima['imagen_portada'];
        $stmtFotosEvento = $pdo->prepare("SELECT archivo FROM competicion_fotos WHERE competicion_id = ? AND tipo = 'imagen' ORDER BY orden ASC");
        $stmtFotosEvento->execute([$proxima['id']]);
        foreach ($stmtFotosEvento->fetchAll(PDO::FETCH_COLUMN) as $archivoGaleria) {
            if (count($fotosModoEvento) >= 5) break;
            if (!in_array($archivoGaleria, $fotosModoEvento, true)) {
                $fotosModoEvento[] = $archivoGaleria;
            }
        }
    }
}

$mostrarSplash = !empty($ajustes['splash_activo']) && !empty($ajustes['splash_imagen']);
$splashImagen = $ajustes['splash_imagen'] ?? null;

$numGimnastasStats = (int)$pdo->query('SELECT COUNT(*) FROM gimnastas')->fetchColumn();
$numCompeticionesStats = (int)$pdo->query('SELECT COUNT(*) FROM competiciones WHERE disputada = 1')->fetchColumn();
$numCategoriasStats = (int)$pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();

$estadisticasPortada = [
    ['valor' => $ajustes['est1_valor'] ?? $numGimnastasStats, 'texto' => $ajustes['est1_texto'] ?: 'Gimnastas en el club', 'icono' => 'gimnasta'],
    ['valor' => $ajustes['est2_valor'] ?? $numCompeticionesStats, 'texto' => $ajustes['est2_texto'] ?: 'Competiciones disputadas', 'icono' => 'medalla'],
    ['valor' => $ajustes['est3_valor'] ?? $numCategoriasStats, 'texto' => $ajustes['est3_texto'] ?: 'Categorías, de base a senior', 'icono' => 'categorias'],
    ['valor' => $ajustes['est4_valor'] ?? 5, 'texto' => $ajustes['est4_texto'] ?: 'Aparatos: aro, pelota, mazas, cinta y cuerda', 'icono' => 'aro'],
];

/**
 * Icono SVG sencillo (trazo, sin relleno) para las estadísticas de
 * la portada. Un puñado de formas hechas a mano relacionadas con la
 * gimnasia rítmica, sin depender de ninguna librería de iconos externa.
 */
function iconoStat(string $nombre): string {
    $iconos = [
        'gimnasta' => '<circle cx="12" cy="5" r="2.3"/><path d="M12 7.5v6M12 13.5l-4 6M12 13.5l4 6M8 10l-3 2M16 10l3 2"/>',
        'medalla' => '<circle cx="12" cy="15" r="5.5"/><path d="M9.5 10 7 3h3l2 5M14.5 10 17 3h-3l-2 5"/><path d="M10.3 15.7l1.2 1.2 2.2-2.5"/>',
        'categorias' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'aro' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="5"/>',
    ];
    $trazos = $iconos[$nombre] ?? $iconos['aro'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="stat-icono" aria-hidden="true">' . $trazos . '</svg>';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($modoEvento): ?>
<section class="hero-evento">
  <?php
  $numFotosEvento = count($fotosModoEvento);
  $unaSolaFoto = $numFotosEvento <= 1;
  $duracionPorFoto = 6; // segundos que cada foto está "en primer plano"
  $duracionTotal = $numFotosEvento * $duracionPorFoto;
  // La lógica de "cuánto dura el fundido según cuántas fotos haya" ya
  // no se genera aquí: vive como CSS estático en styles.css
  // (.hero-evento-pase-2 a .hero-evento-pase-5), esta clase solo dice
  // cuál de esos cálculos ya hechos le corresponde a esta carga.
  $claseNumFotos = $unaSolaFoto ? '' : ' hero-evento-pase-' . $numFotosEvento;
  ?>
  <?php foreach ($fotosModoEvento as $i => $fotoEvento): ?>
    <div class="hero-evento-foto<?= $unaSolaFoto ? ' hero-evento-foto-fija' : $claseNumFotos ?>"
         style="background-image:url('img/<?= e($fotoEvento) ?>');
                <?= $unaSolaFoto ? '' : '--dir:' . ($i % 2 === 0 ? '1' : '-1') . '; animation-duration:' . $duracionTotal . 's; animation-delay:-' . ($i * $duracionPorFoto) . 's;' ?>"></div>
  <?php endforeach; ?>
  <div class="hero-evento-capa"></div>
  <div class="contenedor hero-evento-contenido">
    <div class="hero-evento-etiqueta"><?= e($ajustes['evento_etiqueta'] ?: '¡Ya casi está aquí!') ?></div>
    <h1><?= e($proxima['nombre']) ?></h1>
    <p class="hero-evento-detalle"><?= e($proxima['categoria']) ?> · <?= e(formatearFecha($proxima['fecha'])) ?><?= !empty($proxima['hora']) ? ' a las ' . e($proxima['hora']) : '' ?> · <?= e($proxima['lugar']) ?></p>

    <div class="cuenta-atras-grande" data-fecha="<?= e($fechaProximaISO) ?>">
      <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="dias">–</span><span class="cuenta-atras-etiqueta"><?= e(t('dias')) ?></span></div>
      <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="horas">–</span><span class="cuenta-atras-etiqueta"><?= e(t('horas')) ?></span></div>
      <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="min">–</span><span class="cuenta-atras-etiqueta"><?= e(t('min')) ?></span></div>
      <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="seg">–</span><span class="cuenta-atras-etiqueta"><?= e(t('seg')) ?></span></div>
    </div>

    <a href="competicion.php?id=<?= (int)$proxima['id'] ?>" class="boton oro hero-evento-boton">Ver todos los detalles del torneo →</a>
  </div>
</section>
<?php else: ?>

<section class="hero <?= !empty($ajustes['inicio_imagen']) ? 'hero-con-foto' : '' ?>">
  <?php if (!empty($ajustes['inicio_imagen'])): ?>
    <div class="hero-fondo-foto" data-parallax="0.06" data-parallax-limite="35" style="background-image:url('img/<?= e($ajustes['inicio_imagen']) ?>');"></div>
  <?php endif; ?>
  <div class="contenedor">
    <div class="hero-texto-centrado">
      <div class="hero-eyebrow"><span class="hero-kicker">★</span> <?= e($ajustes['hero_kicker'] ?: 'Equipo de referencia en gimnasia rítmica') ?></div>
      <h1 style="--tam-titulo:<?= e((string)(($ajustes['hero_titulo_tamano'] ?: 100) / 100)) ?>;"><?= e($ajustes['hero_titulo'] ?: t('hero_titulo')) ?></h1>
      <p><?= e($ajustes['hero_texto'] ?: t('hero_texto')) ?></p>
      <div class="hero-cta">
        <a href="noticias.php" class="boton oro"><?= t('hero_boton_noticias') ?></a>
        <a href="competiciones.php" class="boton contorno"><?= t('hero_boton_competiciones') ?></a>
      </div>
    </div>

    <?php if ($proxima): ?>
    <div class="proximo-partido">
      <div class="etiqueta"><?= t('proxima_competicion') ?> · <?= e($proxima['categoria']) ?></div>
      <div class="enfrentamiento">
        <span><?= e($proxima['nombre']) ?></span>
      </div>
      <div class="detalle">
        <span class="proximo-partido-linea">📅 <?= e(formatearFecha($proxima['fecha'])) ?></span>
        <span class="proximo-partido-linea">📍 <?= e($proxima['lugar']) ?></span>
      </div>
      <div class="cuenta-atras-grande cuenta-atras-grande-chica" data-fecha="<?= e($fechaProximaISO) ?>">
        <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="dias">–</span><span class="cuenta-atras-etiqueta"><?= e(t('dias')) ?></span></div>
        <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="horas">–</span><span class="cuenta-atras-etiqueta"><?= e(t('horas')) ?></span></div>
        <div class="cuenta-atras-caja"><span class="cuenta-atras-num" data-unidad="min">–</span><span class="cuenta-atras-etiqueta"><?= e(t('min')) ?></span></div>
      </div>
      <a href="competicion.php?id=<?= (int)$proxima['id'] ?>" class="proximo-partido-enlace"><?= t('ver_competicion') ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="franja-stats animar-scroll">
  <div class="contenedor stats-grid">
    <?php foreach ($estadisticasPortada as $est): ?>
    <div class="stat-item">
      <?= iconoStat($est['icono']) ?>
      <span class="stat-numero" data-hasta="<?= (int)$est['valor'] ?>">0</span>
      <span class="stat-etiqueta"><?= e($est['texto']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_ultima_hora') ?></h2>
      <a href="noticias.php"><?= t('ver_todas') ?></a>
    </div>

    <?php if ($destacada): ?>
    <div class="grid-noticias">
      <a href="noticia.php?id=<?= (int)$destacada['id'] ?>" class="noticia-destacada animar-scroll">
        <img src="img/<?= e(rutaMiniatura($destacada['imagen']) ?: 'competicion.svg') ?>" srcset="img/<?= e(rutaMiniatura($destacada['imagen']) ?: 'competicion.svg') ?> 480w, img/<?= e($destacada['imagen'] ?: 'competicion.svg') ?> 1600w" sizes="(max-width: 860px) 90vw, 55vw" alt="" loading="lazy">
        <div class="cuerpo-overlay">
          <div class="fecha"><?= e(formatearFecha($destacada['fecha'])) ?></div>
          <h3><?= e(campoIdioma($destacada, 'titulo')) ?></h3>
          <p><?= e(campoIdioma($destacada, 'resumen')) ?></p>
        </div>
      </a>

      <ul class="lista-noticias">
        <?php foreach ($restoNoticias as $n): ?>
        <li>
          <a href="noticia.php?id=<?= (int)$n['id'] ?>" class="animar-scroll" style="display:flex;gap:14px;">
            <img src="img/<?= e(rutaMiniatura($n['imagen']) ?: 'cantera.svg') ?>" alt="" loading="lazy">
            <span>
              <span class="fecha"><?= e(formatearFecha($n['fecha'])) ?></span>
              <h4><?= e(campoIdioma($n, 'titulo')) ?></h4>
            </span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php else: ?>
      <p><?= t('sin_noticias') ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="seccion franja-club-teaser">
  <div class="contenedor franja-club-teaser-caja">
    <div>
      <h2 style="margin-bottom:6px;"><?= e($ajustes['club_teaser_titulo'] ?: 'Conoce la historia del club') ?></h2>
      <p style="color:var(--gris-texto);margin:0;"><?= e($ajustes['club_teaser_texto'] ?: 'Fundado en 1987, con equipos en todas las categorías. Descubre nuestra trayectoria y palmarés.') ?></p>
    </div>
    <a href="sobre.php" class="boton oro" style="flex-shrink:0;"><?= e($ajustes['club_teaser_boton'] ?: 'Sobre el club →') ?></a>
  </div>
</section>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
