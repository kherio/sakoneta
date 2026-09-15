// Reordenar filas de tabla arrastrando (Gimnastas, Categorías)
document.addEventListener('DOMContentLoaded', function () {
  var tabla = document.querySelector('table[data-endpoint-orden]');
  if (!tabla) return;

  var cuerpo = document.getElementById('cuerpo-tabla-arrastrable');
  var endpoint = tabla.getAttribute('data-endpoint-orden');
  var csrf = tabla.getAttribute('data-csrf');
  var avisoGuardado = document.getElementById('aviso-orden-guardado');
  var filaArrastrada = null;

  cuerpo.querySelectorAll('tr[draggable="true"]').forEach(function (fila) {
    fila.addEventListener('dragstart', function () {
      filaArrastrada = fila;
      fila.style.opacity = '0.4';
    });
    fila.addEventListener('dragend', function () {
      fila.style.opacity = '';
      guardarOrden();
    });
    fila.addEventListener('dragover', function (e) {
      e.preventDefault();
      var filaSobre = e.target.closest('tr');
      if (!filaSobre || filaSobre === filaArrastrada) return;
      var rect = filaSobre.getBoundingClientRect();
      var mitad = rect.top + rect.height / 2;
      if (e.clientY < mitad) {
        cuerpo.insertBefore(filaArrastrada, filaSobre);
      } else {
        cuerpo.insertBefore(filaArrastrada, filaSobre.nextSibling);
      }
    });
  });

  function guardarOrden() {
    var ids = Array.from(cuerpo.querySelectorAll('tr[data-id]')).map(function (f) {
      return f.getAttribute('data-id');
    });
    var datos = new URLSearchParams();
    ids.forEach(function (id) { datos.append('ids[]', id); });
    datos.append('csrf_token', csrf);

    fetch(endpoint, { method: 'POST', body: datos })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (r.ok && avisoGuardado) {
          avisoGuardado.style.display = 'block';
          setTimeout(function () { avisoGuardado.style.display = 'none'; }, 2000);
        }
      })
      .catch(function () {});
  }
});

// Selección múltiple en la biblioteca de medios (Fotos y vídeos subidos)
document.addEventListener('DOMContentLoaded', function () {
  var checks = document.querySelectorAll('.check-medio');
  if (!checks.length) return;

  var seleccionarTodos = document.getElementById('seleccionar-todos');
  var boton = document.getElementById('btn-borrar-seleccion');
  var contador = document.getElementById('contador-seleccion');

  function actualizarContador() {
    var marcados = document.querySelectorAll('.check-medio:checked').length;
    contador.textContent = marcados;
    boton.disabled = marcados === 0;
  }

  checks.forEach(function (c) { c.addEventListener('change', actualizarContador); });

  if (seleccionarTodos) {
    seleccionarTodos.addEventListener('change', function () {
      checks.forEach(function (c) { c.checked = seleccionarTodos.checked; });
      actualizarContador();
    });
  }

  actualizarContador();
});

// Selector visual de encuadre de la foto principal (noticia_form.php,
// competicion_form.php): clic o arrastre sobre la foto para marcar el
// punto que se quiere ver en la cabecera.
document.addEventListener('DOMContentLoaded', function () {
  var selector = document.getElementById('selector-encuadre');
  if (!selector) return;

  var marca = document.getElementById('marca-encuadre');
  var input = document.getElementById('imagen_posicion_input');
  var arrastrando = false;

  function actualizar(clientX, clientY) {
    var rect = selector.getBoundingClientRect();
    var x = Math.round(Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100)));
    var y = Math.round(Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100)));
    marca.style.left = x + '%';
    marca.style.top = y + '%';
    input.value = x + ' ' + y;
  }

  selector.addEventListener('mousedown', function (e) {
    arrastrando = true;
    actualizar(e.clientX, e.clientY);
  });
  document.addEventListener('mousemove', function (e) {
    if (arrastrando) actualizar(e.clientX, e.clientY);
  });
  document.addEventListener('mouseup', function () { arrastrando = false; });

  selector.addEventListener('touchstart', function (e) {
    arrastrando = true;
    var t = e.touches[0];
    actualizar(t.clientX, t.clientY);
  }, { passive: true });
  selector.addEventListener('touchmove', function (e) {
    if (!arrastrando) return;
    var t = e.touches[0];
    actualizar(t.clientX, t.clientY);
    e.preventDefault();
  }, { passive: false });
  document.addEventListener('touchend', function () { arrastrando = false; });
});
