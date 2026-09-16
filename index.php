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

<section class="hero <?= !empty($ajustes['inicio_imagen']) ? 'hero-con-foto' : '' ?>">
  <?php if (!empty($ajustes['inicio_imagen'])): ?>
    <div class="hero-fondo-foto" data-parallax="0.06" data-parallax-limite="35" style="background-image:url('img/<?= e($ajustes['inicio_imagen']) ?>');"></div>
  <?php endif; ?>
  <div class="contenedor">
    <div>
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
        <?= e(formatearFecha($proxima['fecha'])) ?> · <?= e($proxima['lugar']) ?>
      </div>
      <div class="cuenta-atras" data-fecha="<?= e($fechaProximaISO) ?>" data-dias="<?= e(t('dias')) ?>" data-horas="<?= e(t('horas')) ?>" data-min="<?= e(t('min')) ?>" data-seg="<?= e(t('seg')) ?>">
        <span class="cuenta-atras-num">–</span>
      </div>
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
        <img src="img/<?= e($destacada['imagen'] ?: 'competicion.svg') ?>" alt="">
        <div class="cuerpo-overlay">
          <div class="fecha"><?= e(formatearFecha($destacada['fecha'])) ?></div>
          <h3><?= e($destacada['titulo']) ?></h3>
          <p><?= e($destacada['resumen']) ?></p>
        </div>
      </a>

      <ul class="lista-noticias">
        <?php foreach ($restoNoticias as $n): ?>
        <li>
          <a href="noticia.php?id=<?= (int)$n['id'] ?>" class="animar-scroll" style="display:flex;gap:14px;">
            <img src="img/<?= e($n['imagen'] ?: 'cantera.svg') ?>" alt="">
            <span>
              <span class="fecha"><?= e(formatearFecha($n['fecha'])) ?></span>
              <h4><?= e($n['titulo']) ?></h4>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
