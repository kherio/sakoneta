// Animación de aparición al hacer scroll para tarjetas y bloques marcados
// con la clase "animar-scroll". Usa IntersectionObserver y escalona la
// entrada de los elementos que comparten un mismo contenedor.
document.addEventListener('DOMContentLoaded', function () {
  // --- Pantalla de bienvenida (splash) ---
  var splash = document.getElementById('splash');
  if (splash) {
    var yaVisto = sessionStorage.getItem('sakoneta_splash_visto');
    if (yaVisto) {
      splash.remove();
    } else {
      document.body.style.overflow = 'hidden';
      var cerrarSplash = function () {
        splash.classList.add('splash-oculto');
        document.body.style.overflow = '';
        sessionStorage.setItem('sakoneta_splash_visto', '1');
        // Debe coincidir con la duración de la transición en CSS
        // (.splash { transition: ... 1.4s ... }); si se retira antes,
        // se corta la animación de golpe a medio camino.
        setTimeout(function () { splash.remove(); }, 1150);
      };
      splash.addEventListener('click', cerrarSplash);
      setTimeout(cerrarSplash, 2800);
    }
  }

  // --- Animación al hacer scroll ---
  var elementos = document.querySelectorAll('.animar-scroll');
  if (!elementos.length) {
    // No hay tarjetas que animar en esta página, pero seguimos con el resto
    // de funciones (countdown, parallax, compartir, guiño del escudo...).
  } else if (!('IntersectionObserver' in window)) {
    elementos.forEach(function (el) { el.classList.add('en-vista'); });
  } else {
    // Escalonar el retraso de animación entre hermanos del mismo contenedor
    var porContenedor = new Map();
    elementos.forEach(function (el) {
      var padre = el.parentElement;
      if (!porContenedor.has(padre)) porContenedor.set(padre, []);
      porContenedor.get(padre).push(el);
    });
    porContenedor.forEach(function (hijos) {
      hijos.forEach(function (el, indice) {
        el.style.transitionDelay = Math.min(indice * 80, 400) + 'ms';
      });
    });

    var observador = new IntersectionObserver(function (entradas, obs) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) {
          entrada.target.classList.add('en-vista');
          obs.unobserve(entrada.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    elementos.forEach(function (el) { observador.observe(el); });
  }

  // --- Contador ascendente en la barra de estadísticas ---
  var numeros = document.querySelectorAll('.stat-numero[data-hasta]');
  if (numeros.length) {
    var animado = new WeakSet();
    var observadorNumeros = new IntersectionObserver(function (entradas, obs) {
      entradas.forEach(function (entrada) {
        if (!entrada.isIntersecting || animado.has(entrada.target)) return;
        animado.add(entrada.target);
        var elemento = entrada.target;
        var meta = parseInt(elemento.getAttribute('data-hasta'), 10) || 0;
        var duracion = 1200;
        var inicio = null;
        var paso = function (marca) {
          if (!inicio) inicio = marca;
          var progreso = Math.min((marca - inicio) / duracion, 1);
          elemento.textContent = Math.round(progreso * meta);
          if (progreso < 1) window.requestAnimationFrame(paso);
        };
        window.requestAnimationFrame(paso);
        obs.unobserve(elemento);
      });
    }, { threshold: 0.4 });
    numeros.forEach(function (n) { observadorNumeros.observe(n); });
  }

  // --- Ajustar el titular del hero para que quepa en una sola línea ---
  var ajustarTituloHero = function () {
    var titulo = document.querySelector('.hero h1');
    if (!titulo) return;
    titulo.style.fontSize = '';
    var tamano = parseFloat(window.getComputedStyle(titulo).fontSize);
    var intentos = 0;
    while (intentos < 40 && tamano > 16) {
      var lineHeight = parseFloat(window.getComputedStyle(titulo).lineHeight);
      if (titulo.scrollHeight <= lineHeight + 3) break;
      tamano -= 1;
      titulo.style.fontSize = tamano + 'px';
      intentos++;
    }
  };
  ajustarTituloHero();
  window.addEventListener('resize', function () {
    window.requestAnimationFrame(ajustarTituloHero);
  });
  // Reajustar también cuando termine la animación de entrada del hero,
  // por si el navegador todavía no tenía las medidas finales listas.
  window.addEventListener('load', ajustarTituloHero);

  // --- Botón de me gusta en la noticia ---
  var botonMeGusta = document.getElementById('boton-me-gusta');
  if (botonMeGusta) {
    botonMeGusta.addEventListener('click', function () {
      if (botonMeGusta.disabled) return;
      botonMeGusta.disabled = true;
      var datos = new URLSearchParams();
      datos.append('id', botonMeGusta.getAttribute('data-id'));
      fetch('dar_like.php', { method: 'POST', body: datos })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          botonMeGusta.disabled = false;
          if (r.ok) {
            document.getElementById('contador-me-gusta').textContent = r.likes;
            botonMeGusta.classList.toggle('activo', r.yaLeGusta);
            botonMeGusta.classList.add('animando');
            setTimeout(function () { botonMeGusta.classList.remove('animando'); }, 350);
          }
        })
        .catch(function () { botonMeGusta.disabled = false; });
    });
  }

  // --- Swipe entre competiciones (solo móvil/táctil), con la otra
  // competición entrando de verdad en tiempo real ---
  var datosSwipe = document.getElementById('swipe-competicion');
  if (datosSwipe && window.matchMedia('(max-width: 860px) and (pointer: coarse)').matches) {
    var urlAnterior = datosSwipe.getAttribute('data-anterior');
    var urlSiguiente = datosSwipe.getAttribute('data-siguiente');
    var capaAnterior = document.getElementById('vista-previa-anterior');
    var capaSiguiente = document.getElementById('vista-previa-siguiente');
    // Solo se desplaza el contenido propio de la página (noticia,
    // galería, etc.), nunca la cabecera del sitio: la cabecera es
    // igual en todas las páginas, así que se queda fija arriba en
    // todo momento, como en la página real.
    var contenido = document.getElementById('contenido-pagina');
    var cabeceraSitio = document.getElementById('cabecera-principal');
    // Importante: si se mueven o quedan como hijas del contenedor que
    // recibe el transform, en cuanto ese contenedor lo reciba pasaría
    // a ser el punto de referencia de sus position:fixed, y dejarían
    // de estar anclados de verdad a la pantalla — se verían mal,
    // movidos y solapados de forma incorrecta. Se sacan a <html> para
    // evitarlo, y su posición (justo debajo de la cabecera) se
    // calcula por JavaScript en cada gesto.
    if (capaAnterior) document.documentElement.appendChild(capaAnterior);
    if (capaSiguiente) document.documentElement.appendChild(capaSiguiente);

    // El servidor ya ha rellenado estas capas con la foto y el texto
    // reales de la anterior/siguiente competición directamente en el
    // HTML de la página (ver competicion.php); no hace falta pedir
    // nada más por red al vuelo. "Hay vista previa" se sabe solo con
    // mirar si el servidor llegó a poner contenido dentro de la capa.
    var hayPreviewAnterior = !!(capaAnterior && capaAnterior.querySelector('.vista-previa-swipe-foto'));
    var hayPreviewSiguiente = !!(capaSiguiente && capaSiguiente.querySelector('.vista-previa-swipe-foto'));

    var inicioX = null, inicioY = null, arrastrando = false, esHorizontal = null, navegando = false, vaASiguiente = null;
    var anchoPantalla = window.innerWidth;

    function posicionarCapasBajoCabecera() {
      // .bottom da directamente dónde termina la cabecera en la
      // pantalla ahora mismo; es más fiable que reconstruirlo a
      // partir de su altura, porque no depende de que su "top" sea
      // exactamente 0 (barra de estado, notch, etc.).
      var finCabecera = cabeceraSitio ? cabeceraSitio.getBoundingClientRect().bottom : 0;
      [capaAnterior, capaSiguiente].forEach(function (capa) {
        if (!capa) return;
        capa.style.top = finCabecera + 'px';
        capa.style.height = 'calc(100% - ' + finCabecera + 'px)';
      });
    }

    function ocultarCapas() {
      [capaAnterior, capaSiguiente].forEach(function (capa) {
        capa.classList.remove('visible');
        capa.style.transform = '';
      });
    }

    document.addEventListener('touchstart', function (e) {
      if (navegando) return;
      inicioX = e.touches[0].clientX;
      inicioY = e.touches[0].clientY;
      arrastrando = true;
      esHorizontal = null;
      vaASiguiente = null;
      posicionarCapasBajoCabecera();
      contenido.style.transition = 'none';
      capaAnterior.style.transition = 'none';
      capaSiguiente.style.transition = 'none';
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
      if (!arrastrando || inicioX === null) return;
      var deltaX = e.touches[0].clientX - inicioX;
      var deltaY = e.touches[0].clientY - inicioY;

      if (esHorizontal === null && (Math.abs(deltaX) > 12 || Math.abs(deltaY) > 12)) {
        esHorizontal = Math.abs(deltaX) > Math.abs(deltaY) * 1.3;
        // La dirección se decide UNA sola vez, al confirmarse el gesto
        // horizontal, y ya no cambia durante el resto del arrastre
        // (si no, un pequeño temblor cerca del centro hacía parpadear
        // la vista previa de un lado a otro).
        if (esHorizontal) vaASiguiente = deltaX < 0;
      }
      if (!esHorizontal) return;
      posicionarCapasBajoCabecera();

      var tieneDestino = (vaASiguiente && hayPreviewSiguiente) || (!vaASiguiente && hayPreviewAnterior);
      var desplazamiento = tieneDestino ? deltaX : deltaX / 4;

      contenido.style.transform = 'translateX(' + desplazamiento + 'px)';

      var capaActiva = vaASiguiente ? capaSiguiente : capaAnterior;
      if (tieneDestino) {
        capaActiva.classList.add('visible');
        var base = vaASiguiente ? anchoPantalla : -anchoPantalla;
        capaActiva.style.transform = 'translateX(' + (base + desplazamiento) + 'px)';
      }
      e.preventDefault();
    }, { passive: false });

    document.addEventListener('touchend', function (e) {
      if (!arrastrando || inicioX === null) { arrastrando = false; return; }
      arrastrando = false;
      var deltaX = e.changedTouches[0].clientX - inicioX;
      inicioX = null;
      if (!esHorizontal || vaASiguiente === null) { ocultarCapas(); return; }

      var tieneDestino = (vaASiguiente && hayPreviewSiguiente) || (!vaASiguiente && hayPreviewAnterior);
      var umbralSuperado = tieneDestino && Math.abs(deltaX) > anchoPantalla * 0.22;
      var destino = vaASiguiente ? urlSiguiente : urlAnterior;
      var capaActiva = vaASiguiente ? capaSiguiente : capaAnterior;

      var transicion = 'transform .28s cubic-bezier(.32,.72,0,1)';
      contenido.style.transition = transicion;
      capaActiva.style.transition = transicion;

      if (umbralSuperado) {
        navegando = true;
        var destinoX = vaASiguiente ? -anchoPantalla : anchoPantalla;
        contenido.style.transform = 'translateX(' + destinoX + 'px)';
        capaActiva.style.transform = 'translateX(0)';
        setTimeout(function () { window.location.href = destino; }, 260);
      } else {
        contenido.style.transform = 'translateX(0)';
        var base = vaASiguiente ? anchoPantalla : -anchoPantalla;
        capaActiva.style.transform = 'translateX(' + base + 'px)';
        setTimeout(ocultarCapas, 290);
      }
    }, { passive: true });

    var avisoSwipe = document.getElementById('aviso-swipe');
    if (avisoSwipe) {
      setTimeout(function () { avisoSwipe.remove(); }, 4200);
    }
  }

  // --- Menú móvil ---
  var botonMenu = document.getElementById('btn-menu-movil');
  var menuMovil = document.getElementById('menu-movil');
  if (botonMenu && menuMovil) {
    botonMenu.addEventListener('click', function () {
      var abierto = menuMovil.classList.toggle('abierto');
      botonMenu.classList.toggle('activo', abierto);
      botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  // --- Cabecera compacta al hacer scroll ---
  var cabecera = document.getElementById('cabecera-principal');
  if (cabecera) {
    var actualizarCabecera = function () {
      cabecera.classList.toggle('compacta', window.scrollY > 60);
    };
    document.addEventListener('scroll', function () {
      window.requestAnimationFrame(actualizarCabecera);
    }, { passive: true });
    actualizarCabecera();
  }

  // --- Countdown a la próxima competición ---
  var cuentasAtras = document.querySelectorAll('.cuenta-atras[data-fecha]');
  if (cuentasAtras.length) {
    var actualizarCuentas = function () {
      cuentasAtras.forEach(function (caja) {
        var num = caja.querySelector('.cuenta-atras-num');
        var objetivo = new Date(caja.getAttribute('data-fecha')).getTime();
        var restante = objetivo - Date.now();
        if (isNaN(objetivo) || restante <= 0) {
          if (num) num.textContent = '';
          return;
        }
        var dias = Math.floor(restante / 86400000);
        var horas = Math.floor((restante % 86400000) / 3600000);
        var min = Math.floor((restante % 3600000) / 60000);
        var seg = Math.floor((restante % 60000) / 1000);
        if (num) {
          num.textContent = dias + ' ' + caja.getAttribute('data-dias') + ' · ' +
            horas + ' ' + caja.getAttribute('data-horas') + ' · ' +
            min + ' ' + caja.getAttribute('data-min') + ' · ' +
            seg + ' ' + caja.getAttribute('data-seg');
        }
      });
    };
    actualizarCuentas();
    setInterval(actualizarCuentas, 1000);
  }

  // --- Parallax suave en fotos de fondo ---
  var capasParallax = document.querySelectorAll('[data-parallax]');
  if (capasParallax.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var actualizarParallax = function () {
      capasParallax.forEach(function (capa) {
        var contenedor = capa.closest('.marco-parallax') || capa.parentElement;
        var rect = contenedor.getBoundingClientRect();
        var centro = rect.top + rect.height / 2 - window.innerHeight / 2;
        var intensidad = parseFloat(capa.getAttribute('data-parallax')) || 0.06;
        var limite = parseFloat(capa.getAttribute('data-parallax-limite')) || 30;
        var desplazamiento = Math.max(-limite, Math.min(limite, centro * -intensidad));
        capa.style.setProperty('--py', desplazamiento + 'px');
      });
    };
    document.addEventListener('scroll', function () {
      window.requestAnimationFrame(actualizarParallax);
    }, { passive: true });
    window.addEventListener('resize', actualizarParallax);
    actualizarParallax();
  }

  // --- Filtro de categorías: selección múltiple por defecto, o única
  // si el grupo lleva data-modo="unico" (así en Gimnastas) ---
  document.querySelectorAll('.filtro-categorias').forEach(function (grupo) {
    var contenedor = document.getElementById(grupo.getAttribute('data-filtro-objetivo'));
    if (!contenedor) return;
    var tarjetas = contenedor.children;
    var esUnico = grupo.getAttribute('data-modo') === 'unico';
    var botonTodas = grupo.querySelector('button[data-categoria="todas"]');
    var botonesCategoria = grupo.querySelectorAll('button:not([data-categoria="todas"])');

    function aplicarFiltro() {
      var activas = Array.from(botonesCategoria)
        .filter(function (b) { return b.classList.contains('activo'); })
        .map(function (b) { return b.getAttribute('data-categoria'); });

      var mostrarTodas = activas.length === 0;
      if (botonTodas) botonTodas.classList.toggle('activo', mostrarTodas);

      Array.from(tarjetas).forEach(function (tarjeta) {
        var categoriasTarjeta = (tarjeta.getAttribute('data-categoria') || '').split(',');
        var coincide = mostrarTodas || activas.some(function (activa) { return categoriasTarjeta.indexOf(activa) !== -1; });
        tarjeta.style.display = coincide ? '' : 'none';
      });
    }

    if (botonTodas) {
      botonTodas.addEventListener('click', function () {
        botonesCategoria.forEach(function (b) { b.classList.remove('activo'); });
        aplicarFiltro();
      });
    }
    botonesCategoria.forEach(function (boton) {
      boton.addEventListener('click', function () {
        if (esUnico) {
          var yaEstabaActivo = boton.classList.contains('activo');
          botonesCategoria.forEach(function (b) { b.classList.remove('activo'); });
          if (!yaEstabaActivo) boton.classList.add('activo');
        } else {
          boton.classList.toggle('activo');
        }
        aplicarFiltro();
      });
    });

    aplicarFiltro();
  });

  // --- Copiar enlace al compartir una noticia ---
  var botonCopiar = document.querySelector('.boton-copiar-enlace');
  if (botonCopiar) {
    botonCopiar.addEventListener('click', function () {
      var url = botonCopiar.getAttribute('data-url');
      var textoCopiado = botonCopiar.getAttribute('data-texto-copiado');
      var textoOriginal = botonCopiar.getAttribute('data-texto-copiar');
      var marcarCopiado = function () {
        botonCopiar.textContent = textoCopiado;
        setTimeout(function () { botonCopiar.textContent = textoOriginal; }, 2000);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(marcarCopiado).catch(function () {});
      } else {
        var campoTemporal = document.createElement('input');
        campoTemporal.value = url;
        document.body.appendChild(campoTemporal);
        campoTemporal.select();
        try { document.execCommand('copy'); marcarCopiado(); } catch (e) {}
        campoTemporal.remove();
      }
    });
  }

  // --- Barra de progreso de lectura (solo en páginas con artículo) ---
  var articulo = document.querySelector('.detalle-noticia');
  var barraProgreso = document.getElementById('barra-progreso-lectura');
  if (articulo && barraProgreso) {
    var actualizarProgreso = function () {
      var inicio = articulo.offsetTop;
      var total = articulo.offsetHeight - window.innerHeight * 0.5;
      var avance = (window.scrollY - inicio + 200) / Math.max(total, 1);
      barraProgreso.style.width = Math.max(0, Math.min(100, avance * 100)) + '%';
    };
    document.addEventListener('scroll', function () {
      window.requestAnimationFrame(actualizarProgreso);
    }, { passive: true });
    actualizarProgreso();
  }

  // --- Guiño divertido: varios clics en el escudo lanzan confeti ---
  var escudos = document.querySelectorAll('#escudo-cabecera, #escudo-splash');
  var clicsEscudo = 0;
  var temporizadorClics = null;
  escudos.forEach(function (escudo) {
    escudo.addEventListener('click', function (evento) {
      evento.preventDefault();
      clicsEscudo++;
      clearTimeout(temporizadorClics);
      temporizadorClics = setTimeout(function () { clicsEscudo = 0; }, 1500);
      if (clicsEscudo >= 5) {
        clicsEscudo = 0;
        lanzarConfeti();
        escudo.classList.add('escudo-girando');
        setTimeout(function () { escudo.classList.remove('escudo-girando'); }, 800);
        mostrarMensajeGuino('🎀 ¡Aupa Sakoneta!');
      }
    });
  });

  function lanzarConfeti() {
    var colores = ['#D6187A', '#5B2A86', '#F5A9CE', '#FBF5F9'];
    for (var i = 0; i < 26; i++) {
      var pieza = document.createElement('div');
      pieza.className = 'confeti-pieza';
      pieza.style.left = Math.random() * 100 + 'vw';
      pieza.style.background = colores[Math.floor(Math.random() * colores.length)];
      pieza.style.animationDuration = (1.6 + Math.random() * 1.2) + 's';
      pieza.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
      document.body.appendChild(pieza);
      (function (el) { setTimeout(function () { el.remove(); }, 3200); })(pieza);
    }
  }

  function mostrarMensajeGuino(texto) {
    var aviso = document.createElement('div');
    aviso.className = 'mensaje-guino';
    aviso.textContent = texto;
    document.body.appendChild(aviso);
    requestAnimationFrame(function () { aviso.classList.add('visible'); });
    setTimeout(function () {
      aviso.classList.remove('visible');
      setTimeout(function () { aviso.remove(); }, 400);
    }, 2200);
  }

  // --- Confeti al aterrizar en una competición con resultado de podio ---
  var marcadorPodio = document.getElementById('swipe-competicion');
  if (marcadorPodio && marcadorPodio.getAttribute('data-podio') === '1') {
    setTimeout(lanzarConfeti, 500);
  }

  // --- Interruptor de modo oscuro ---
  var interruptorTema = document.getElementById('interruptor-tema');
  if (interruptorTema) {
    function actualizarIconoTema() {
      var esOscuro = document.documentElement.getAttribute('data-tema') === 'oscuro';
      interruptorTema.textContent = esOscuro ? '☀️' : '🌙';
      interruptorTema.setAttribute('aria-label', esOscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
      interruptorTema.title = interruptorTema.getAttribute('aria-label');
    }
    actualizarIconoTema();

    interruptorTema.addEventListener('click', function () {
      var esOscuroAhora = document.documentElement.getAttribute('data-tema') === 'oscuro';
      if (esOscuroAhora) {
        document.documentElement.removeAttribute('data-tema');
      } else {
        document.documentElement.setAttribute('data-tema', 'oscuro');
      }
      try { localStorage.setItem('sakoneta_tema', esOscuroAhora ? 'claro' : 'oscuro'); } catch (e) {}
      actualizarIconoTema();
    });
  }

  // --- Vista de calendario de competiciones (alternativa a la lista) ---
  var datosCalendarioEl = document.getElementById('datos-calendario-competiciones');
  if (datosCalendarioEl) {
    var competicionesCalendario = JSON.parse(datosCalendarioEl.textContent || '[]');
    var contenedorCalendario = document.getElementById('vista-calendario-competiciones');
    var vistaLista = document.getElementById('vista-lista-competiciones');
    var hoy = new Date();
    var mesMostrado = hoy.getMonth();
    var anioMostrado = hoy.getFullYear();

    var nombresMes = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    var nombresDiaCorto = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    function escaparHtml(texto) {
      var div = document.createElement('div');
      div.textContent = texto;
      return div.innerHTML;
    }

    function pintarCalendario() {
      var porDia = {};
      competicionesCalendario.forEach(function (c) {
        var partes = c.fecha.split('-');
        if (parseInt(partes[0], 10) === anioMostrado && parseInt(partes[1], 10) - 1 === mesMostrado) {
          var dia = parseInt(partes[2], 10);
          (porDia[dia] = porDia[dia] || []).push(c);
        }
      });

      var primerDiaSemana = (new Date(anioMostrado, mesMostrado, 1).getDay() + 6) % 7; // que empiece en lunes
      var diasEnMes = new Date(anioMostrado, mesMostrado + 1, 0).getDate();

      var html = '<div class="calendario-cabecera">' +
        '<button type="button" id="calendario-mes-anterior" aria-label="Mes anterior">‹</button>' +
        '<h3>' + nombresMes[mesMostrado] + ' de ' + anioMostrado + '</h3>' +
        '<button type="button" id="calendario-mes-siguiente" aria-label="Mes siguiente">›</button>' +
        '</div><div class="calendario-grid">';

      nombresDiaCorto.forEach(function (n) { html += '<div class="calendario-dia-nombre">' + n + '</div>'; });
      for (var v = 0; v < primerDiaSemana; v++) html += '<div class="calendario-dia vacio"></div>';

      for (var dia = 1; dia <= diasEnMes; dia++) {
        var esHoy = hoy.getDate() === dia && hoy.getMonth() === mesMostrado && hoy.getFullYear() === anioMostrado;
        var deEsteDia = porDia[dia];
        var clases = 'calendario-dia' + (esHoy ? ' hoy' : '') + (deEsteDia ? ' con-competicion' : '');
        if (deEsteDia) {
          var nombresTexto = deEsteDia.map(function (c) { return c.nombre; }).join(' · ');
          var primeraLinea = deEsteDia.length > 1 ? deEsteDia.length + ' competiciones' : deEsteDia[0].nombre;
          html += '<div class="' + clases + '" title="' + escaparHtml(nombresTexto) + '">' +
            '<a href="competicion.php?id=' + deEsteDia[0].id + '">' +
            '<span class="calendario-dia-numero">' + dia + '</span>' +
            '<span class="calendario-dia-nombre-torneo">' + escaparHtml(primeraLinea) + '</span>' +
            '</a></div>';
        } else {
          html += '<div class="' + clases + '"><span class="calendario-dia-numero">' + dia + '</span></div>';
        }
      }
      html += '</div>';
      contenedorCalendario.innerHTML = html;

      document.getElementById('calendario-mes-anterior').addEventListener('click', function () {
        mesMostrado--; if (mesMostrado < 0) { mesMostrado = 11; anioMostrado--; }
        pintarCalendario();
      });
      document.getElementById('calendario-mes-siguiente').addEventListener('click', function () {
        mesMostrado++; if (mesMostrado > 11) { mesMostrado = 0; anioMostrado++; }
        pintarCalendario();
      });
    }

    document.querySelectorAll('.vista-cambio button').forEach(function (boton) {
      boton.addEventListener('click', function () {
        document.querySelectorAll('.vista-cambio button').forEach(function (b) { b.classList.remove('activo'); });
        boton.classList.add('activo');
        var esCalendario = boton.getAttribute('data-vista') === 'calendario';
        vistaLista.style.display = esCalendario ? 'none' : '';
        contenedorCalendario.style.display = esCalendario ? '' : 'none';
        if (esCalendario && !contenedorCalendario.innerHTML) pintarCalendario();
      });
    });
  }

  // --- Autocompletado del buscador ---
  var campoBusqueda = document.getElementById('campo-busqueda');
  if (campoBusqueda) {
    var cajaSugerencias = document.getElementById('sugerencias-busqueda');
    var temporizadorBusqueda = null;

    function ocultarSugerencias() {
      cajaSugerencias.classList.remove('visible');
      cajaSugerencias.innerHTML = '';
    }

    campoBusqueda.addEventListener('input', function () {
      var texto = campoBusqueda.value.trim();
      clearTimeout(temporizadorBusqueda);
      if (texto.length < 2) { ocultarSugerencias(); return; }

      temporizadorBusqueda = setTimeout(function () {
        fetch('buscar.php?sugerencias=1&q=' + encodeURIComponent(texto))
          .then(function (r) { return r.json(); })
          .then(function (lista) {
            if (!lista.length || campoBusqueda.value.trim() !== texto) { ocultarSugerencias(); return; }
            cajaSugerencias.innerHTML = '';
            lista.forEach(function (item) {
              var fila = document.createElement('div');
              fila.className = 'sugerencia-item';
              var texto2 = document.createElement('span');
              texto2.textContent = item.texto;
              var tipo = document.createElement('span');
              tipo.className = 'sugerencia-tipo';
              tipo.textContent = item.tipo;
              fila.appendChild(texto2);
              fila.appendChild(tipo);
              fila.addEventListener('click', function () {
                campoBusqueda.value = item.texto;
                ocultarSugerencias();
                campoBusqueda.closest('form').submit();
              });
              cajaSugerencias.appendChild(fila);
            });
            cajaSugerencias.classList.add('visible');
          })
          .catch(function () { ocultarSugerencias(); });
      }, 250);
    });

    document.addEventListener('click', function (e) {
      if (!campoBusqueda.closest('form').contains(e.target)) ocultarSugerencias();
    });
    campoBusqueda.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') ocultarSugerencias();
    });
  }

  // --- Esqueleto de carga en imágenes de contenido (mientras descargan) ---
  document.querySelectorAll('.galeria-parallax-item img, .tarjeta-noticia img, .tarjeta-jugador img, .tarjeta-competicion-foto img, .franja-tarjeta img').forEach(function (img) {
    if (img.complete && img.naturalWidth > 0) return; // ya estaba en caché, no hace falta esqueleto
    img.classList.add('cargando-esqueleto');
    var quitar = function () { img.classList.remove('cargando-esqueleto'); };
    img.addEventListener('load', quitar, { once: true });
    img.addEventListener('error', quitar, { once: true });
  });

  // --- Lightbox de galería (noticias, gimnastas, competiciones, categorías) ---
  var lightbox = document.getElementById('lightbox-galeria');
  if (lightbox) {
    var lightboxImg = document.getElementById('lightbox-img');
    var lightboxContador = document.getElementById('lightbox-contador');
    var lightboxAnterior = document.getElementById('lightbox-anterior');
    var lightboxSiguiente = document.getElementById('lightbox-siguiente');
    var fotosGaleria = [];
    var indiceActual = 0;

    function abrirLightbox(indice) {
      if (!fotosGaleria.length) return;
      indiceActual = (indice + fotosGaleria.length) % fotosGaleria.length;
      var foto = fotosGaleria[indiceActual];
      lightboxImg.src = foto.src;
      lightboxImg.alt = foto.alt || '';
      lightboxContador.textContent = (indiceActual + 1) + ' / ' + fotosGaleria.length;
      var variasFotos = fotosGaleria.length > 1;
      lightboxAnterior.style.display = variasFotos ? '' : 'none';
      lightboxSiguiente.style.display = variasFotos ? '' : 'none';
      lightboxContador.style.display = variasFotos ? '' : 'none';
      lightbox.classList.add('visible');
      document.body.style.overflow = 'hidden';
    }

    function cerrarLightbox() {
      lightbox.classList.remove('visible');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('.galeria-parallax').forEach(function (galeria) {
      var imagenes = Array.from(galeria.querySelectorAll('.galeria-parallax-item img'));
      if (!imagenes.length) return;
      var listaDeEstaGaleria = imagenes.map(function (img) { return { src: img.src, alt: img.alt }; });

      imagenes.forEach(function (img, i) {
        img.addEventListener('click', function () {
          fotosGaleria = listaDeEstaGaleria;
          abrirLightbox(i);
        });
      });
    });

    document.getElementById('lightbox-cerrar').addEventListener('click', cerrarLightbox);
    lightboxAnterior.addEventListener('click', function () { abrirLightbox(indiceActual - 1); });
    lightboxSiguiente.addEventListener('click', function () { abrirLightbox(indiceActual + 1); });
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) cerrarLightbox();
    });
    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('visible')) return;
      if (e.key === 'Escape') cerrarLightbox();
      else if (e.key === 'ArrowLeft') abrirLightbox(indiceActual - 1);
      else if (e.key === 'ArrowRight') abrirLightbox(indiceActual + 1);
    });
  }
});
