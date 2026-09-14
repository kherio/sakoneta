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
        setTimeout(function () { splash.remove(); }, 700);
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
        var rect = capa.parentElement.getBoundingClientRect();
        var centro = rect.top + rect.height / 2 - window.innerHeight / 2;
        var desplazamiento = Math.max(-30, Math.min(30, centro * -0.06));
        capa.style.transform = 'translateY(' + desplazamiento + 'px)';
      });
    };
    document.addEventListener('scroll', function () {
      window.requestAnimationFrame(actualizarParallax);
    }, { passive: true });
    actualizarParallax();
  }

  // --- Filtro de categorías (pestañas) ---
  document.querySelectorAll('.filtro-categorias').forEach(function (grupo) {
    var contenedor = document.getElementById(grupo.getAttribute('data-filtro-objetivo'));
    if (!contenedor) return;
    var tarjetas = contenedor.children;
    grupo.querySelectorAll('button').forEach(function (boton) {
      boton.addEventListener('click', function () {
        grupo.querySelectorAll('button').forEach(function (b) { b.classList.remove('activo'); });
        boton.classList.add('activo');
        var categoria = boton.getAttribute('data-categoria');
        Array.from(tarjetas).forEach(function (tarjeta) {
          var coincide = categoria === 'todas' || tarjeta.getAttribute('data-categoria') === categoria;
          tarjeta.style.display = coincide ? '' : 'none';
        });
      });
    });
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
});
