<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirTecnico();

$pdo = getDb();
$seccionActual = 'analitica';
$tituloPagina = 'Analítica avanzada';

// --- Resumen general ---
$totalVisitas = (int)$pdo->query('SELECT COUNT(*) FROM visitas')->fetchColumn();
$ipsUnicas = (int)$pdo->query('SELECT COUNT(DISTINCT ip) FROM visitas')->fetchColumn();
$hoy = date('Y-m-d');
$hace7dias = date('Y-m-d', strtotime('-7 days'));
$hace30dias = date('Y-m-d', strtotime('-30 days'));

$stmt = $pdo->prepare('SELECT COUNT(*) FROM visitas WHERE fecha_hora >= ?');
$stmt->execute([$hoy . ' 00:00:00']);
$visitasHoy = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM visitas WHERE fecha_hora >= ?');
$stmt->execute([$hace7dias . ' 00:00:00']);
$visitas7dias = (int)$stmt->fetchColumn();

// --- Visitas por día, últimos 30 días (para el gráfico de barras) ---
$stmt = $pdo->prepare("SELECT substr(fecha_hora, 1, 10) AS dia, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY dia ORDER BY dia ASC");
$stmt->execute([$hace30dias . ' 00:00:00']);
$visitasPorDiaRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$visitasPorDia = [];
for ($i = 29; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $visitasPorDia[$dia] = (int)($visitasPorDiaRaw[$dia] ?? 0);
}
$maxVisitasDia = max(1, max($visitasPorDia));

// --- Páginas más visitadas (últimos 30 días) ---
$stmt = $pdo->prepare("SELECT pagina, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY pagina ORDER BY total DESC LIMIT 10");
$stmt->execute([$hace30dias . ' 00:00:00']);
$paginasTop = $stmt->fetchAll();
$maxPaginaTop = $paginasTop ? (int)$paginasTop[0]['total'] : 1;

// --- Referentes más frecuentes (de dónde viene la visita), últimos 30 días ---
$stmt = $pdo->prepare("SELECT referente, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? AND referente IS NOT NULL GROUP BY referente ORDER BY total DESC LIMIT 8");
$stmt->execute([$hace30dias . ' 00:00:00']);
$referentesTop = $stmt->fetchAll();

// --- Sesiones de navegación por IP (últimos 7 días, para no cargar demasiados datos) ---
// Se calculan en PHP a partir de las visitas en bruto: dentro de una
// misma IP, si pasan más de 30 minutos entre una página vista y la
// siguiente, se considera que empieza una sesión de navegación nueva.
$stmt = $pdo->prepare('SELECT ip, pagina, fecha_hora FROM visitas WHERE fecha_hora >= ? ORDER BY ip ASC, fecha_hora ASC');
$stmt->execute([$hace7dias . ' 00:00:00']);
$filasVisitas = $stmt->fetchAll();

$sesiones = [];
$ipActual = null;
$sesionActual = null;
$CORTE_SESION_SEGUNDOS = 30 * 60;

foreach ($filasVisitas as $fila) {
    $ts = strtotime($fila['fecha_hora']);
    if ($fila['ip'] !== $ipActual || $sesionActual === null || ($ts - $sesionActual['fin_ts']) > $CORTE_SESION_SEGUNDOS) {
        if ($sesionActual !== null) $sesiones[] = $sesionActual;
        $sesionActual = ['ip' => $fila['ip'], 'inicio_ts' => $ts, 'fin_ts' => $ts, 'paginas' => 1, 'entrada' => $fila['pagina'], 'salida' => $fila['pagina']];
        $ipActual = $fila['ip'];
    } else {
        $sesionActual['fin_ts'] = $ts;
        $sesionActual['paginas']++;
        $sesionActual['salida'] = $fila['pagina'];
    }
}
if ($sesionActual !== null) $sesiones[] = $sesionActual;

// --- Estadísticas derivadas de las sesiones (sobre TODAS, antes de recortar a las 100 más largas para la tabla) ---
$totalSesiones = count($sesiones);
$sesionesDeUnaPagina = 0;
$sumaDuraciones = 0;
$sumaPaginas = 0;
$entradas = [];
$salidas = [];
foreach ($sesiones as $s) {
    if ($s['paginas'] === 1) $sesionesDeUnaPagina++;
    $sumaDuraciones += ($s['fin_ts'] - $s['inicio_ts']);
    $sumaPaginas += $s['paginas'];
    $entradas[$s['entrada']] = ($entradas[$s['entrada']] ?? 0) + 1;
    $salidas[$s['salida']] = ($salidas[$s['salida']] ?? 0) + 1;
}
$tasaRebote = $totalSesiones ? round($sesionesDeUnaPagina / $totalSesiones * 100) : 0;
$duracionMediaSesion = $totalSesiones ? intdiv($sumaDuraciones, $totalSesiones) : 0;
$paginasMediaSesion = $totalSesiones ? round($sumaPaginas / $totalSesiones, 1) : 0;
arsort($entradas);
arsort($salidas);
$entradasTop = array_slice($entradas, 0, 8, true);
$salidasTop = array_slice($salidas, 0, 8, true);

// Las sesiones más largas (más interesantes) primero, con un límite
// razonable para no sobrecargar la página.
usort($sesiones, fn($a, $b) => ($b['fin_ts'] - $b['inicio_ts']) <=> ($a['fin_ts'] - $a['inicio_ts']));
$sesionesTabla = array_slice($sesiones, 0, 100);

// --- Visitantes nuevos vs. recurrentes (últimos 30 días): una IP es
// "recurrente" si tiene visitas en más de un día distinto dentro de
// ese periodo, "nueva" si solo aparece en un único día. ---
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT date(fecha_hora)) AS dias FROM visitas WHERE fecha_hora >= ? GROUP BY ip");
$stmt->execute([$hace30dias . ' 00:00:00']);
$diasPorIp = $stmt->fetchAll(PDO::FETCH_COLUMN);
$visitantesRecurrentes = count(array_filter($diasPorIp, fn($d) => $d > 1));
$visitantesNuevos = count($diasPorIp) - $visitantesRecurrentes;

// --- Franja horaria y día de la semana (últimos 30 días) ---
$stmt = $pdo->prepare("SELECT CAST(strftime('%H', fecha_hora) AS INTEGER) AS hora, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY hora");
$stmt->execute([$hace30dias . ' 00:00:00']);
$visitasPorHoraRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$visitasPorHora = [];
for ($h = 0; $h < 24; $h++) $visitasPorHora[$h] = (int)($visitasPorHoraRaw[$h] ?? 0);
$maxVisitasHora = max(1, max($visitasPorHora));

$diasSemanaNombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$stmt = $pdo->prepare("SELECT CAST(strftime('%w', fecha_hora) AS INTEGER) AS dia, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY dia");
$stmt->execute([$hace30dias . ' 00:00:00']);
$visitasPorDiaSemanaRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$visitasPorDiaSemana = [];
foreach ($diasSemanaNombres as $numDia => $nombreDia) {
    $visitasPorDiaSemana[$nombreDia] = (int)($visitasPorDiaSemanaRaw[$numDia] ?? 0);
}
$maxVisitasDiaSemana = max(1, max($visitasPorDiaSemana));

// --- Dispositivo e idioma (últimos 30 días) ---
$stmt = $pdo->prepare("SELECT COALESCE(dispositivo, 'desconocido') AS d, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY d");
$stmt->execute([$hace30dias . ' 00:00:00']);
$visitasPorDispositivo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalConDispositivo = array_sum($visitasPorDispositivo);

$stmt = $pdo->prepare("SELECT COALESCE(idioma, 'es') AS i, COUNT(*) AS total FROM visitas WHERE fecha_hora >= ? GROUP BY i");
$stmt->execute([$hace30dias . ' 00:00:00']);
$visitasPorIdioma = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalConIdioma = array_sum($visitasPorIdioma);

// --- Cruce de seguridad: intentos de login fallidos recientes ---
$intentosSospechosos = $pdo->query("SELECT ip, intentos, ultimo_intento, bloqueado_hasta FROM intentos_login WHERE intentos > 2 ORDER BY ultimo_intento DESC LIMIT 20")->fetchAll();

function formatearDuracion(int $segundos): string {
    if ($segundos < 60) return $segundos . ' seg';
    $minutos = intdiv($segundos, 60);
    if ($minutos < 60) return $minutos . ' min';
    $horas = intdiv($minutos, 60);
    $minRestantes = $minutos % 60;
    return $horas . ' h ' . $minRestantes . ' min';
}

require __DIR__ . '/includes/layout_header.php';
?>

<h2>Analítica avanzada</h2>
<p style="color:var(--gris);font-size:13.5px;max-width:70ch;">
  Página de acceso restringido: solo la ven las cuentas con el permiso
  especial de técnico activado (ver "Usuarios"). Los datos de IP se
  conservan un máximo de 90 días y se purgan solos pasado ese plazo.
</p>

<div class="analitica-resumen">
  <div class="analitica-tarjeta">
    <strong><?= number_format($totalVisitas, 0, ',', '.') ?></strong>
    <span>Visitas registradas (últimos 90 días)</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= number_format($ipsUnicas, 0, ',', '.') ?></strong>
    <span>IPs distintas</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= number_format($visitasHoy, 0, ',', '.') ?></strong>
    <span>Visitas de hoy</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= number_format($visitas7dias, 0, ',', '.') ?></strong>
    <span>Visitas últimos 7 días</span>
  </div>
</div>

<div class="analitica-resumen">
  <div class="analitica-tarjeta">
    <strong><?= $tasaRebote ?>%</strong>
    <span>Tasa de rebote (sesiones de 1 sola página, 7 días)</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= e(formatearDuracion($duracionMediaSesion)) ?></strong>
    <span>Duración media de sesión (7 días)</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= $paginasMediaSesion ?></strong>
    <span>Páginas vistas por sesión, de media (7 días)</span>
  </div>
  <div class="analitica-tarjeta">
    <strong><?= number_format($visitantesNuevos, 0, ',', '.') ?> / <?= number_format($visitantesRecurrentes, 0, ',', '.') ?></strong>
    <span>Visitantes nuevos / recurrentes (30 días)</span>
  </div>
</div>

<h3>Visitas por día (últimos 30 días)</h3>
<div class="analitica-grafico">
  <?php foreach ($visitasPorDia as $dia => $total): ?>
    <div class="analitica-barra-columna" title="<?= e(date('d/m/Y', strtotime($dia))) ?>: <?= $total ?> visitas">
      <div class="analitica-barra" style="height:<?= $total > 0 ? max(4, round($total / $maxVisitasDia * 100)) : 2 ?>%;"></div>
      <span class="analitica-barra-etiqueta"><?= date('d/m', strtotime($dia)) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="fila-2" style="align-items:flex-start;margin-top:32px;">
  <div>
    <h3>Páginas más visitadas <span style="font-weight:400;color:var(--gris);font-size:13px;">(30 días)</span></h3>
    <?php if (!$paginasTop): ?>
      <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
    <?php else: ?>
      <div class="analitica-lista-barras">
        <?php foreach ($paginasTop as $p): ?>
          <div class="analitica-lista-fila">
            <span class="analitica-lista-etiqueta"><?= e($p['pagina']) ?></span>
            <div class="analitica-lista-barra-fondo">
              <div class="analitica-lista-barra" style="width:<?= round($p['total'] / $maxPaginaTop * 100) ?>%;"></div>
            </div>
            <span class="analitica-lista-valor"><?= (int)$p['total'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div>
    <h3>De dónde vienen <span style="font-weight:400;color:var(--gris);font-size:13px;">(30 días)</span></h3>
    <?php if (!$referentesTop): ?>
      <p style="color:var(--gris);">No hay suficientes visitas con referente registrado (acceso directo o desde marcadores no cuenta aquí).</p>
    <?php else: ?>
      <table class="admin-tabla">
        <?php foreach ($referentesTop as $r): ?>
          <tr>
            <td style="word-break:break-all;font-size:13px;"><?= e(parse_url($r['referente'], PHP_URL_HOST) ?: $r['referente']) ?></td>
            <td style="text-align:right;width:60px;"><?= (int)$r['total'] ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="fila-2" style="align-items:flex-start;margin-top:32px;">
  <div>
    <h3>Páginas de entrada <span style="font-weight:400;color:var(--gris);font-size:13px;">(por dónde llega la gente, 7 días)</span></h3>
    <?php if (!$entradasTop): ?>
      <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
    <?php else: ?>
      <div class="analitica-lista-barras">
        <?php $maxEntrada = max($entradasTop); foreach ($entradasTop as $pagina => $total): ?>
          <div class="analitica-lista-fila">
            <span class="analitica-lista-etiqueta"><?= e($pagina) ?></span>
            <div class="analitica-lista-barra-fondo">
              <div class="analitica-lista-barra" style="width:<?= round($total / $maxEntrada * 100) ?>%;"></div>
            </div>
            <span class="analitica-lista-valor"><?= $total ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div>
    <h3>Páginas de salida <span style="font-weight:400;color:var(--gris);font-size:13px;">(por dónde se va la gente, 7 días)</span></h3>
    <?php if (!$salidasTop): ?>
      <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
    <?php else: ?>
      <div class="analitica-lista-barras">
        <?php $maxSalida = max($salidasTop); foreach ($salidasTop as $pagina => $total): ?>
          <div class="analitica-lista-fila">
            <span class="analitica-lista-etiqueta"><?= e($pagina) ?></span>
            <div class="analitica-lista-barra-fondo">
              <div class="analitica-lista-barra" style="width:<?= round($total / $maxSalida * 100) ?>%;"></div>
            </div>
            <span class="analitica-lista-valor"><?= $total ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<h3 style="margin-top:36px;">Sesiones de navegación por IP <span style="font-weight:400;color:var(--gris);font-size:13px;">(últimos 7 días, las más largas primero)</span></h3>
<p style="color:var(--gris);font-size:13px;max-width:70ch;margin-top:-8px;">
  Una "sesión" agrupa las páginas vistas seguidas desde la misma IP: si
  pasan más de 30 minutos sin actividad, se considera que empieza una
  sesión nueva.
</p>
<?php if (!$sesionesTabla): ?>
  <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
<?php else: ?>
<table class="admin-tabla">
  <tr><th>IP</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Páginas vistas</th></tr>
  <?php foreach ($sesionesTabla as $s): ?>
  <tr>
    <td><code><?= e($s['ip']) ?></code></td>
    <td><?= e(date('d/m/Y H:i', $s['inicio_ts'])) ?></td>
    <td><?= e(date('d/m/Y H:i', $s['fin_ts'])) ?></td>
    <td><?= e(formatearDuracion($s['fin_ts'] - $s['inicio_ts'])) ?></td>
    <td><?= (int)$s['paginas'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<h3 style="margin-top:36px;">¿Cuándo se visita la web? <span style="font-weight:400;color:var(--gris);font-size:13px;">(30 días)</span></h3>
<div class="fila-2" style="align-items:flex-start;">
  <div>
    <p style="color:var(--gris);font-size:13px;margin-top:-6px;">Por hora del día</p>
    <div class="analitica-grafico" style="height:110px;">
      <?php foreach ($visitasPorHora as $hora => $total): ?>
        <div class="analitica-barra-columna" title="<?= $hora ?>:00 — <?= $total ?> visitas">
          <div class="analitica-barra" style="height:<?= $total > 0 ? max(4, round($total / $maxVisitasHora * 100)) : 2 ?>%;"></div>
          <span class="analitica-barra-etiqueta" style="writing-mode:initial;transform:none;font-size:8px;"><?= $hora % 3 === 0 ? $hora : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div>
    <p style="color:var(--gris);font-size:13px;margin-top:-6px;">Por día de la semana</p>
    <div class="analitica-lista-barras">
      <?php foreach ($visitasPorDiaSemana as $nombreDia => $total): ?>
        <div class="analitica-lista-fila">
          <span class="analitica-lista-etiqueta"><?= e($nombreDia) ?></span>
          <div class="analitica-lista-barra-fondo">
            <div class="analitica-lista-barra" style="width:<?= round($total / $maxVisitasDiaSemana * 100) ?>%;"></div>
          </div>
          <span class="analitica-lista-valor"><?= $total ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<h3 style="margin-top:36px;">Dispositivo e idioma <span style="font-weight:400;color:var(--gris);font-size:13px;">(30 días)</span></h3>
<p style="color:var(--gris);font-size:12.5px;margin-top:-8px;">Estos dos datos solo se registran desde que se activó esta función: las visitas anteriores a esa fecha se cuentan aquí como castellano/desconocido por defecto.</p>
<div class="fila-2" style="align-items:flex-start;">
  <div>
    <p style="color:var(--gris);font-size:13px;margin-top:-6px;">Dispositivo</p>
    <?php if (!$totalConDispositivo): ?>
      <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
    <?php else: ?>
      <div class="analitica-lista-barras">
        <?php foreach ($visitasPorDispositivo as $tipo => $total):
          $etiquetas = ['movil' => '📱 Móvil', 'escritorio' => '🖥️ Escritorio', 'desconocido' => '❔ Desconocido'];
        ?>
          <div class="analitica-lista-fila">
            <span class="analitica-lista-etiqueta"><?= e($etiquetas[$tipo] ?? $tipo) ?></span>
            <div class="analitica-lista-barra-fondo">
              <div class="analitica-lista-barra" style="width:<?= round($total / $totalConDispositivo * 100) ?>%;"></div>
            </div>
            <span class="analitica-lista-valor"><?= round($total / $totalConDispositivo * 100) ?>%</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div>
    <p style="color:var(--gris);font-size:13px;margin-top:-6px;">Idioma de la visita</p>
    <?php if (!$totalConIdioma): ?>
      <p style="color:var(--gris);">Todavía no hay suficientes datos.</p>
    <?php else: ?>
      <div class="analitica-lista-barras">
        <?php foreach ($visitasPorIdioma as $idiomaVisita => $total):
          $etiquetasIdioma = ['es' => '🇪🇸 Castellano', 'eu' => 'Euskera'];
        ?>
          <div class="analitica-lista-fila">
            <span class="analitica-lista-etiqueta"><?= e($etiquetasIdioma[$idiomaVisita] ?? $idiomaVisita) ?></span>
            <div class="analitica-lista-barra-fondo">
              <div class="analitica-lista-barra" style="width:<?= round($total / $totalConIdioma * 100) ?>%;"></div>
            </div>
            <span class="analitica-lista-valor"><?= round($total / $totalConIdioma * 100) ?>%</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<h3 style="margin-top:36px;">⚠️ IPs con varios intentos de acceso fallidos</h3>
<?php if (!$intentosSospechosos): ?>
  <p style="color:var(--gris);">Ninguna IP destaca por intentos fallidos ahora mismo.</p>
<?php else: ?>
<table class="admin-tabla">
  <tr><th>IP</th><th>Intentos</th><th>Último intento</th><th>Bloqueada hasta</th></tr>
  <?php foreach ($intentosSospechosos as $i): ?>
  <tr>
    <td><code><?= e($i['ip']) ?></code></td>
    <td><?= (int)$i['intentos'] ?></td>
    <td><?= $i['ultimo_intento'] ? e(date('d/m/Y H:i', strtotime($i['ultimo_intento']))) : '—' ?></td>
    <td><?= ($i['bloqueado_hasta'] && strtotime($i['bloqueado_hasta']) > time()) ? e(date('d/m/Y H:i', strtotime($i['bloqueado_hasta']))) : '—' ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
