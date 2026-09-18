// Animación de aparición al hacer scroll para tarjetas y bloques marcados
// con la clase "animar-scroll". Usa IntersectionObserver y escalona la
// entrada de los elementos que comparten un mismo contenedor.
document.addEventListener('DOMContentLoaded', function () {
  // Política global de movimiento reducido: la preferencia explícita
  // de la persona (prefers-reduced-motion) O una señal de que el
  // propio dispositivo va justo de recursos (calculado ya en el
  // <head>, ver includes/header.php). Un único punto de verdad para
  // todo lo decorativo: parallax, contador, confeti, swipe...
  function movimientoReducidoGlobal() {
    return document.documentElement.getAttribute('data-movimiento') === 'reducido';
  }

  // Bloquea el scroll del body (para el menú móvil, el lightbox y la
  // pantalla de bienvenida) sin que la página dé un pequeño salto al
  // desaparecer la barra de scroll: se compensa con un padding-right
  // del mismo ancho que ocupaba esa barra.
  function bloquearScrollBody() {
    var anchoBarra = window.innerWidth - document.documentElement.clientWidth;
    if (anchoBarra > 0) document.body.style.paddingRight = anchoBarra + 'px';
    document.body.style.overflow = 'hidden';
  }
  function desbloquearScrollBody() {
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  }

  // --- Pantalla de bienvenida (splash) ---
  var splash = document.getElementById('splash');
  if (splash) {
    var yaVisto = sessionStorage.getItem('sakoneta_splash_visto');
    var movimientoReducido = movimientoReducidoGlobal();
    if (yaVisto || movimientoReducido) {
      // Con movimiento reducido, nadie debería tener que esperar ni
      // ver una animación de entrada/salida: se retira directamente.
      splash.remove();
      if (!yaVisto) sessionStorage.setItem('sakoneta_splash_visto', '1');
    } else {
      bloquearScrollBody();
      var cerrarSplash = function () {
        splash.classList.add('splash-oculto');
        desbloquearScrollBody();
        sessionStorage.setItem('sakoneta_splash_visto', '1');
        // Debe coincidir con la duración de la transición en CSS
        // (.splash { transition: ... 1.4s ... }); si se retira antes,
        // se corta la animación de golpe a medio camino.
        setTimeout(function () { splash.remove(); }, 1150);
      };
      splash.addEventListener('click', cerrarSplash);
      var botonSaltarSplash = document.getElementById('splash-saltar');
      if (botonSaltarSplash) {
        botonSaltarSplash.addEventListener('click', function (e) {
          e.stopPropagation(); // que no dispare también el clic del fondo
          cerrarSplash();
        });
      }
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
        if (movimientoReducidoGlobal()) {
          elemento.textContent = meta;
          obs.unobserve(elemento);
          return;
        }
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
      datos.append('csrf_token', botonMeGusta.getAttribute('data-csrf'));
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
  var fondoMenuMovil = document.getElementById('menu-movil-fondo');
  var botonCerrarMenuMovil = document.getElementById('menu-movil-cerrar');
  var cabeceraParaMenu = document.getElementById('cabecera-principal');
  var contenidoTrasMenu = document.getElementById('contenido-pagina');
  var pieTrasMenu = document.querySelector('footer');
  var logoTrasMenu = document.getElementById('logo-cabecera');
  if (botonMenu && menuMovil) {
    function fijarInertTrasMenu(bloqueado) {
      [contenidoTrasMenu, pieTrasMenu, logoTrasMenu].forEach(function (el) {
        if (!el) return;
        if (bloqueado) el.setAttribute('inert', ''); else el.removeAttribute('inert');
      });
    }
    function abrirMenuMovil() {
      menuMovil.classList.add('abierto');
      botonMenu.classList.add('activo');
      botonMenu.setAttribute('aria-expanded', 'true');
      if (fondoMenuMovil) fondoMenuMovil.classList.add('visible');
      // El logo y el nombre de la cabecera del sitio se ocultan
      // mientras el menú está abierto: ya se repiten en la propia
      // cabecera del menú, y verlos dos veces a la vez quedaba raro.
      if (cabeceraParaMenu) cabeceraParaMenu.classList.add('oculta-marca');
      // El contenido de detrás del menú no debe poder recibir el foco
      // por teclado (Tab) mientras el menú está abierto y lo tapa.
      fijarInertTrasMenu(true);
      bloquearScrollBody();
    }
    function cerrarMenuMovil(devolverFoco) {
      menuMovil.classList.remove('abierto');
      botonMenu.classList.remove('activo');
      botonMenu.setAttribute('aria-expanded', 'false');
      if (fondoMenuMovil) fondoMenuMovil.classList.remove('visible');
      if (cabeceraParaMenu) cabeceraParaMenu.classList.remove('oculta-marca');
      fijarInertTrasMenu(false);
      desbloquearScrollBody();
      if (devolverFoco) botonMenu.focus();
    }
    botonMenu.addEventListener('click', function () {
      if (menuMovil.classList.contains('abierto')) cerrarMenuMovil(false);
      else abrirMenuMovil();
    });
    // Pulsar fuera del menú (sobre el fondo oscurecido) lo cierra. El
    // contenido de la página queda con "inert" mientras el menú está
    // abierto, que también bloquea sus propios clics, así que hace
    // falta este fondo aparte —no inert— para poder recibir ese clic.
    if (fondoMenuMovil) {
      fondoMenuMovil.addEventListener('click', function () { cerrarMenuMovil(false); });
    }
    if (botonCerrarMenuMovil) {
      botonCerrarMenuMovil.addEventListener('click', function () { cerrarMenuMovil(true); });
    }
    // Escape cierra el menú y devuelve el foco al botón que lo abrió,
    // esté el foco donde esté en ese momento (no solo dentro del menú).
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menuMovil.classList.contains('abierto')) cerrarMenuMovil(true);
    });
    // Elegir cualquier opción del menú lo cierra (antes de seguir el
    // enlace; como es un enlace normal, el cambio de página ya hace
    // el resto).
    menuMovil.querySelectorAll('a').forEach(function (enlace) {
      enlace.addEventListener('click', function () { cerrarMenuMovil(false); });
    });

    // Deslizar hacia la izquierda sobre el propio menú también lo
    // cierra: un gesto extra, nunca la única forma de hacerlo — el
    // botón × y tocar fuera siguen funcionando igual. Se engancha
    // directamente en el menú (no en el documento entero) y para la
    // propagación, para no interferir con el swipe entre
    // competiciones de competicion.php.
    var swipeMenuInicioX = null, swipeMenuInicioY = null, swipeMenuEsHorizontal = null;
    menuMovil.addEventListener('touchstart', function (e) {
      swipeMenuInicioX = e.touches[0].clientX;
      swipeMenuInicioY = e.touches[0].clientY;
      swipeMenuEsHorizontal = null;
      e.stopPropagation();
    }, { passive: true });
    menuMovil.addEventListener('touchmove', function (e) {
      if (swipeMenuInicioX === null) return;
      var deltaX = e.touches[0].clientX - swipeMenuInicioX;
      var deltaY = e.touches[0].clientY - swipeMenuInicioY;
      if (swipeMenuEsHorizontal === null && (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10)) {
        swipeMenuEsHorizontal = Math.abs(deltaX) > Math.abs(deltaY) * 1.3;
      }
      e.stopPropagation();
    }, { passive: true });
    menuMovil.addEventListener('touchend', function (e) {
      if (swipeMenuInicioX === null) return;
      var deltaX = e.changedTouches[0].clientX - swipeMenuInicioX;
      swipeMenuInicioX = null;
      // Umbral de 60px hacia la izquierda, con el gesto confirmado
      // como horizontal, para no cerrar el menú por un roce vertical
      // (por ejemplo, al hacer scroll dentro del propio menú).
      if (swipeMenuEsHorizontal && deltaX < -60) cerrarMenuMovil(false);
      e.stopPropagation();
    }, { passive: true });
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
  var cuentasAtras = document.querySelectorAll('.cuenta-atras[data-fecha], .cuenta-atras-grande[data-fecha]');
  if (cuentasAtras.length) {
    var actualizarCuentas = function () {
      cuentasAtras.forEach(function (caja) {
        var objetivo = new Date(caja.getAttribute('data-fecha')).getTime();
        var restante = objetivo - Date.now();
        var cajasUnidad = caja.querySelectorAll('[data-unidad]');

        if (isNaN(objetivo) || restante <= 0) {
          var num = caja.querySelector('.cuenta-atras-num:not([data-unidad])');
          if (num) num.textContent = '';
          cajasUnidad.forEach(function (el) { el.textContent = '0'; });
          return;
        }

        var dias = Math.floor(restante / 86400000);
        var horas = Math.floor((restante % 86400000) / 3600000);
        var min = Math.floor((restante % 3600000) / 60000);
        var seg = Math.floor((restante % 60000) / 1000);

        if (cajasUnidad.length) {
          // Cuenta atrás "en cajas" (modo evento de la portada): cada
          // unidad tiene su propio hueco, con los números siempre a 2
          // cifras para que las cajas no cambien de ancho cada segundo.
          var valores = { dias: dias, horas: horas, min: min, seg: seg };
          cajasUnidad.forEach(function (el) {
            var valor = valores[el.getAttribute('data-unidad')];
            el.textContent = (valor < 10 ? '0' : '') + valor;
          });
        } else {
          var numTexto = caja.querySelector('.cuenta-atras-num');
          if (numTexto) {
            numTexto.textContent = dias + ' ' + caja.getAttribute('data-dias') + ' · ' +
              horas + ' ' + caja.getAttribute('data-horas') + ' · ' +
              min + ' ' + caja.getAttribute('data-min') + ' · ' +
              seg + ' ' + caja.getAttribute('data-seg');
          }
        }
      });
    };
    actualizarCuentas();
    setInterval(actualizarCuentas, 1000);
  }

  // --- Parallax suave en fotos de fondo ---
  var capasParallax = document.querySelectorAll('[data-parallax]');
  if (capasParallax.length && !movimientoReducidoGlobal()) {
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
    if (movimientoReducidoGlobal()) return;
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
        '<h3>' + nombresMes[mesMostrado] + ' ' + anioMostrado + '</h3>' +
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

    var pastilla = document.getElementById('vista-cambio-pastilla');
    function moverPastilla(boton, conAnimacion) {
      if (!pastilla) return;
      if (!conAnimacion) pastilla.style.transition = 'none';
      pastilla.style.width = boton.offsetWidth + 'px';
      pastilla.style.transform = 'translateX(' + boton.offsetLeft + 'px)';
      if (!conAnimacion) {
        // Fuerza a aplicar la posición ya, sin animación, y solo
        // entonces se reactiva la transición para los próximos clics.
        pastilla.offsetHeight;
        pastilla.style.transition = '';
      }
    }
    var botonActivoInicial = document.querySelector('.vista-cambio button.activo');
    if (botonActivoInicial) moverPastilla(botonActivoInicial, false);
    window.addEventListener('resize', function () {
      var actual = document.querySelector('.vista-cambio button.activo');
      if (actual) moverPastilla(actual, false);
    });

    var botonesPestana = Array.from(document.querySelectorAll('.vista-cambio button'));

    function activarPestana(boton) {
      botonesPestana.forEach(function (b) {
        var esEsta = b === boton;
        b.classList.toggle('activo', esEsta);
        b.setAttribute('aria-selected', esEsta ? 'true' : 'false');
        b.setAttribute('tabindex', esEsta ? '0' : '-1');
      });
      moverPastilla(boton, true);
      var esCalendario = boton.getAttribute('data-vista') === 'calendario';
      vistaLista.style.display = esCalendario ? 'none' : '';
      contenedorCalendario.style.display = esCalendario ? '' : 'none';
      if (esCalendario && !contenedorCalendario.innerHTML) pintarCalendario();
    }

    botonesPestana.forEach(function (boton, indice) {
      boton.addEventListener('click', function () { activarPestana(boton); });
      // Flechas izquierda/derecha para moverse entre pestañas, como
      // se espera de un role="tablist" (Inicio/Fin también van al
      // primer/último). La pestaña a la que se llega se activa
      // directamente, no solo recibe el foco.
      boton.addEventListener('keydown', function (e) {
        var siguiente = null;
        if (e.key === 'ArrowRight') siguiente = botonesPestana[(indice + 1) % botonesPestana.length];
        else if (e.key === 'ArrowLeft') siguiente = botonesPestana[(indice - 1 + botonesPestana.length) % botonesPestana.length];
        else if (e.key === 'Home') siguiente = botonesPestana[0];
        else if (e.key === 'End') siguiente = botonesPestana[botonesPestana.length - 1];
        if (siguiente) {
          e.preventDefault();
          siguiente.focus();
          activarPestana(siguiente);
        }
      });
    });
  }

  // --- Autocompletado del buscador ---
  var campoBusqueda = document.getElementById('campo-busqueda');
  if (campoBusqueda) {
    var cajaSugerencias = document.getElementById('sugerencias-busqueda');
    var temporizadorBusqueda = null;
    var indiceActivo = -1;
    var opcionesActuales = [];

    function ocultarSugerencias() {
      cajaSugerencias.classList.remove('visible');
      cajaSugerencias.innerHTML = '';
      opcionesActuales = [];
      indiceActivo = -1;
      campoBusqueda.setAttribute('aria-expanded', 'false');
      campoBusqueda.removeAttribute('aria-activedescendant');
    }

    function marcarActiva(indice) {
      opcionesActuales.forEach(function (opcion, i) {
        var activa = i === indice;
        opcion.classList.toggle('activa', activa);
        opcion.setAttribute('aria-selected', activa ? 'true' : 'false');
      });
      indiceActivo = indice;
      if (indice >= 0) {
        campoBusqueda.setAttribute('aria-activedescendant', opcionesActuales[indice].id);
        opcionesActuales[indice].scrollIntoView({ block: 'nearest' });
      } else {
        campoBusqueda.removeAttribute('aria-activedescendant');
      }
    }

    function elegirSugerencia(item) {
      campoBusqueda.value = item.texto;
      ocultarSugerencias();
      campoBusqueda.closest('form').submit();
    }

    function pintarSugerencias(lista, texto) {
      cajaSugerencias.innerHTML = '';
      opcionesActuales = [];
      indiceActivo = -1;

      if (!lista.length) {
        var vacio = document.createElement('div');
        vacio.className = 'sugerencia-vacia';
        vacio.textContent = 'Sin sugerencias para "' + texto + '". Pulsa Buscar para ver todos los resultados.';
        cajaSugerencias.appendChild(vacio);
        cajaSugerencias.classList.add('visible');
        campoBusqueda.setAttribute('aria-expanded', 'true');
        return;
      }

      lista.forEach(function (item, i) {
        var fila = document.createElement('div');
        fila.className = 'sugerencia-item';
        fila.id = 'sugerencia-opcion-' + i;
        fila.setAttribute('role', 'option');
        fila.setAttribute('aria-selected', 'false');
        var texto2 = document.createElement('span');
        texto2.textContent = item.texto;
        var tipo = document.createElement('span');
        tipo.className = 'sugerencia-tipo';
        tipo.textContent = item.tipo;
        fila.appendChild(texto2);
        fila.appendChild(tipo);
        // El ratón también selecciona, para que el resaltado por
        // teclado y por ratón sean siempre coherentes entre sí.
        fila.addEventListener('mouseenter', function () { marcarActiva(i); });
        fila.addEventListener('mousedown', function (e) {
          e.preventDefault(); // que no le quite el foco al campo antes del click
          elegirSugerencia(item);
        });
        cajaSugerencias.appendChild(fila);
        opcionesActuales.push(fila);
      });
      cajaSugerencias.classList.add('visible');
      campoBusqueda.setAttribute('aria-expanded', 'true');
    }

    campoBusqueda.addEventListener('input', function () {
      var texto = campoBusqueda.value.trim();
      clearTimeout(temporizadorBusqueda);
      if (texto.length < 2) { ocultarSugerencias(); return; }

      temporizadorBusqueda = setTimeout(function () {
        fetch('buscar.php?sugerencias=1&q=' + encodeURIComponent(texto))
          .then(function (r) { return r.json(); })
          .then(function (lista) {
            if (campoBusqueda.value.trim() !== texto) return; // ya no aplica, ha seguido escribiendo
            pintarSugerencias(lista, texto);
          })
          .catch(function () { ocultarSugerencias(); });
      }, 250);
    });

    // Navegación por teclado: flechas para moverse entre sugerencias,
    // Enter para elegir la resaltada (o enviar la búsqueda tal cual si
    // ninguna está resaltada), Escape para cerrar sin elegir nada.
    campoBusqueda.addEventListener('keydown', function (e) {
      var hayOpciones = opcionesActuales.length > 0;
      if (e.key === 'ArrowDown') {
        if (!hayOpciones) return;
        e.preventDefault();
        marcarActiva(indiceActivo < opcionesActuales.length - 1 ? indiceActivo + 1 : 0);
      } else if (e.key === 'ArrowUp') {
        if (!hayOpciones) return;
        e.preventDefault();
        marcarActiva(indiceActivo > 0 ? indiceActivo - 1 : opcionesActuales.length - 1);
      } else if (e.key === 'Enter') {
        if (indiceActivo >= 0 && hayOpciones) {
          e.preventDefault();
          var lista = cajaSugerencias.querySelectorAll('.sugerencia-item');
          var elegida = lista[indiceActivo];
          elegirSugerencia({ texto: elegida.firstChild.textContent });
        }
        // si no hay ninguna resaltada, se deja que el formulario se
        // envíe de forma normal con lo que haya escrito
      } else if (e.key === 'Escape') {
        ocultarSugerencias();
      }
    });

    document.addEventListener('click', function (e) {
      if (!campoBusqueda.closest('form').contains(e.target)) ocultarSugerencias();
    });

    var botonLimpiarBusqueda = document.getElementById('boton-limpiar-busqueda');
    if (botonLimpiarBusqueda) {
      botonLimpiarBusqueda.addEventListener('click', function () {
        campoBusqueda.value = '';
        ocultarSugerencias();
        campoBusqueda.focus();
        // Al enviar el formulario ya vacío, la página recarga sin
        // "q" y vuelve al estado inicial (contador, agrupados y
        // botón de limpiar desaparecen, tal como si no se hubiera
        // buscado nada todavía).
        campoBusqueda.closest('form').submit();
      });
    }
  }

  // --- Botones "Volver a...": si se ha llegado aquí desde el propio
  // listado (con un filtro de categoría aplicado, por ejemplo), el
  // botón vuelve exactamente a esa URL con su filtro, en vez de al
  // listado sin filtrar. Si se ha llegado de cualquier otro sitio
  // (buscador, enlace directo, otra página), se queda con el enlace
  // sencillo de toda la vida.
  document.querySelectorAll('.boton-volver[data-volver-listado]').forEach(function (boton) {
    var referencia = document.referrer;
    if (!referencia) return;
    try {
      var urlReferencia = new URL(referencia);
      if (urlReferencia.origin !== window.location.origin) return;
      var paginasValidas = boton.getAttribute('data-volver-listado').split(',');
      var nombreArchivo = urlReferencia.pathname.split('/').pop();
      if (paginasValidas.indexOf(nombreArchivo) !== -1) {
        boton.href = urlReferencia.pathname + urlReferencia.search;
      }
    } catch (e) {}
  });

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
      lightboxImg.src = foto.full || foto.src;
      lightboxImg.alt = foto.alt || '';
      lightboxContador.textContent = (indiceActual + 1) + ' / ' + fotosGaleria.length;
      var variasFotos = fotosGaleria.length > 1;
      lightboxAnterior.style.display = variasFotos ? '' : 'none';
      lightboxSiguiente.style.display = variasFotos ? '' : 'none';
      lightboxContador.style.display = variasFotos ? '' : 'none';
      lightbox.classList.add('visible');
      bloquearScrollBody();
    }

    function cerrarLightbox() {
      lightbox.classList.remove('visible');
      desbloquearScrollBody();
    }

    document.querySelectorAll('.galeria-parallax').forEach(function (galeria) {
      var imagenes = Array.from(galeria.querySelectorAll('.galeria-parallax-item img'));
      if (!imagenes.length) return;
      var listaDeEstaGaleria = imagenes.map(function (img) { return { src: img.src, full: img.getAttribute('data-full'), alt: img.alt }; });

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

  // --- Huevo de pascua: "Modo Sakoneta" (↑ ↓ ← →, en cualquier página) ---
  var mskOverlay = document.getElementById('modo-sakoneta');
  if (mskOverlay) {
    var mskSecuencia = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
    var mskProgreso = 0;
    var mskActivo = false;

    document.addEventListener('keydown', function (e) {
      // No interferir si la persona está escribiendo en un campo
      var elActivo = document.activeElement;
      var enCampoTexto = elActivo && (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(elActivo.tagName) !== -1 || elActivo.isContentEditable);
      if (enCampoTexto) { mskProgreso = 0; return; }

      if (e.key === mskSecuencia[mskProgreso]) {
        mskProgreso++;
        if (mskProgreso === mskSecuencia.length) {
          mskProgreso = 0;
          activarModoSakoneta();
        }
      } else {
        // Si la tecla fallida es a la vez el inicio de la secuencia,
        // no se pierde del todo: cuenta como si fuera el primer paso.
        mskProgreso = (e.key === mskSecuencia[0]) ? 1 : 0;
      }
    });

    function activarModoSakoneta() {
      if (mskActivo) return; // ya está activo, no se relanza encima
      mskActivo = true;
      mskOverlay.classList.add('visible');
      mskOverlay.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modo-sakoneta-activo');
      var duracion = movimientoReducidoGlobal() ? 3200 : 5500;
      setTimeout(function () {
        mskOverlay.classList.remove('visible');
        mskOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modo-sakoneta-activo');
        mskActivo = false;
      }, duracion);
    }
  }
});
