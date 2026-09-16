<?php
// Sistema de idioma ligero: traduce los textos fijos de la interfaz
// (menús, botones, títulos de sección...). El contenido que escribe el
// club (noticias, nombres de gimnastas, lugares...) se muestra tal cual
// se haya escrito, en el idioma en que se redactó.

function idiomaActual(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'eu'], true)) {
        $_SESSION['idioma'] = $_GET['lang'];
    }
    return $_SESSION['idioma'] ?? 'es';
}

/**
 * Enlace para cambiar de idioma SIN perder el resto de parámetros de
 * la URL actual (id de una noticia, filtros, la búsqueda escrita...).
 * Se genera de forma centralizada aquí, para que ninguna página tenga
 * que ocuparse de conservar sus propios parámetros al construir el
 * enlace de idioma.
 */
function urlConIdioma(string $idioma): string {
    $parametros = $_GET;
    $parametros['lang'] = $idioma;
    $ruta = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
    return $ruta . '?' . http_build_query($parametros);
}

function t(string $clave): string {
    static $textos = [
        'es' => [
            'nav_inicio' => 'Inicio', 'nav_noticias' => 'Noticias', 'nav_gimnastas' => 'Gimnastas',
            'nav_competiciones' => 'Competiciones', 'nav_sobre' => 'Sobre el club', 'nav_contacto' => 'Contacto',
            'nav_acceso' => 'Acceso', 'nav_buscar' => 'Buscar',
            'hero_titulo' => 'Cada cinta, cada aro, cada ejercicio: un paso más hacia el podio.',
            'hero_texto' => 'Sigue la actualidad de la escuela, las gimnastas y los conjuntos del club en un único sitio, hecho por y para la familia de Sakoneta.',
            'hero_boton_noticias' => 'Ver noticias', 'hero_boton_competiciones' => 'Ver competiciones',
            'proxima_competicion' => 'Próxima competición',
            'seccion_ultima_hora' => 'Última hora', 'ver_todas' => 'Todas las noticias →',
            'seccion_noticias' => 'Noticias', 'seccion_gimnastas' => 'Gimnastas 2026/27',
            'seccion_competiciones' => 'Competiciones y resultados', 'seccion_contacto' => 'Contacto',
            'seccion_sobre' => 'Sobre el club', 'seccion_palmares' => 'Palmarés', 'seccion_galeria' => 'Galería',
            'filtro_todas' => 'Todas',
            'disputada' => 'Disputada', 'pendiente' => 'Pendiente',
            'volver_noticias' => '← Volver a noticias',
            'volver_competiciones' => '← Todas las competiciones',
            'volver_gimnastas' => '← Todos los gimnastas',
            'compartir' => 'Compartir esta noticia', 'copiar_enlace' => 'Copiar enlace', 'enlace_copiado' => '¡Enlace copiado!',
            'contacto_nombre' => 'Nombre', 'contacto_email' => 'Correo electrónico', 'contacto_mensaje' => 'Mensaje',
            'contacto_enviar' => 'Enviar mensaje',
            'sin_noticias' => 'Todavía no hay noticias publicadas.',
            'sin_gimnastas' => 'El listado de gimnastas se publicará próximamente.',
            'sin_competiciones' => 'El calendario de competiciones se publicará próximamente.',
            'footer_secciones' => 'Secciones', 'footer_club' => 'Club',
            'dias' => 'días', 'horas' => 'horas', 'min' => 'min', 'seg' => 'seg',
        ],
        'eu' => [
            'nav_inicio' => 'Hasiera', 'nav_noticias' => 'Berriak', 'nav_gimnastas' => 'Gimnastak',
            'nav_competiciones' => 'Txapelketak', 'nav_sobre' => 'Klubari buruz', 'nav_contacto' => 'Kontaktua',
            'nav_acceso' => 'Sarbidea', 'nav_buscar' => 'Bilatu',
            'hero_titulo' => 'Zinta bakoitza, uztai bakoitza, ariketa bakoitza: urrats bat gehiago podiumerantz.',
            'hero_texto' => 'Jarraitu eskolaren, gimnasten eta taldeen berri leku bakar batean, Sakoneta familiarentzat egina.',
            'hero_boton_noticias' => 'Berriak ikusi', 'hero_boton_competiciones' => 'Txapelketak ikusi',
            'proxima_competicion' => 'Hurrengo txapelketa',
            'seccion_ultima_hora' => 'Azken berriak', 'ver_todas' => 'Berri guztiak →',
            'seccion_noticias' => 'Berriak', 'seccion_gimnastas' => 'Gimnastak 2026/27',
            'seccion_competiciones' => 'Txapelketak eta emaitzak', 'seccion_contacto' => 'Kontaktua',
            'seccion_sobre' => 'Klubari buruz', 'seccion_palmares' => 'Sariak', 'seccion_galeria' => 'Galeria',
            'filtro_todas' => 'Guztiak',
            'disputada' => 'Jokatuta', 'pendiente' => 'Zain',
            'volver_noticias' => '← Berrietara itzuli',
            'volver_competiciones' => '← Txapelketa guztiak',
            'volver_gimnastas' => '← Gimnasta guztiak',
            'compartir' => 'Berri hau partekatu', 'copiar_enlace' => 'Esteka kopiatu', 'enlace_copiado' => 'Esteka kopiatuta!',
            'contacto_nombre' => 'Izena', 'contacto_email' => 'Helbide elektronikoa', 'contacto_mensaje' => 'Mezua',
            'contacto_enviar' => 'Mezua bidali',
            'sin_noticias' => 'Oraindik ez dago berririk argitaratuta.',
            'sin_gimnastas' => 'Gimnasten zerrenda laster argitaratuko da.',
            'sin_competiciones' => 'Txapelketen egutegia laster argitaratuko da.',
            'footer_secciones' => 'Atalak', 'footer_club' => 'Kluba',
            'dias' => 'egun', 'horas' => 'ordu', 'min' => 'min', 'seg' => 'seg',
        ],
    ];
    $idioma = idiomaActual();
    return $textos[$idioma][$clave] ?? $textos['es'][$clave] ?? $clave;
}
