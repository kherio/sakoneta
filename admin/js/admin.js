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
