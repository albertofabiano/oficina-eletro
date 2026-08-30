<div class="card">
  <h1>📷 Fotos do estado de entrada</h1>
  <p class="hint">
    Tire até 4 fotos do estado de entrada (arranhões, trincas, etc). Elas ficam
    anexadas à OS no computador — não são enviadas por WhatsApp nem e-mail.
  </p>

  <div class="btn-row" style="margin-top:6px">
    <label class="btn btn-cam" for="fotoCam">📸 Tirar foto</label>
    <label class="btn btn-galeria" for="fotoGaleria">🖼️ Galeria</label>
  </div>
  <!-- Sem "multiple" na câmera de propósito: capture+multiple junto costuma fazer o Android
       ignorar o capture e cair no seletor genérico — cada toque tira 1 foto, repetível pra
       fotografar em sequência. A galeria (sem capture) suporta multiple normalmente. -->
  <input id="fotoCam" type="file" accept="image/*" capture="environment" style="display:none">
  <input id="fotoGaleria" type="file" accept="image/*" multiple style="display:none">

  <div id="miniaturas" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px"></div>
  <div class="hint" style="text-align:right;margin-top:4px"><span id="contagem">0</span>/4</div>

  <button type="button" class="btn btn-send" id="btnEnviar" disabled>✅ Enviar pro computador</button>
</div>

<div class="card ok-box" id="okBox" style="display:none">
  <div class="big">✅</div>
  <h1 style="margin-top:.4rem">Enviado!</h1>
  <p class="hint">As fotos já chegaram no computador. Pode voltar e continuar por lá.</p>
</div>

<script>
(function () {
  var fotos = [];
  var fotoCam     = document.getElementById('fotoCam');
  var fotoGaleria = document.getElementById('fotoGaleria');
  var box   = document.getElementById('miniaturas');
  var btn   = document.getElementById('btnEnviar');

  var suportaWebp = (function () {
    var c = document.createElement('canvas'); c.width = c.height = 1;
    return c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
  })();

  function comprimir(file) {
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
          var max = 1280, w = img.width, h = img.height;
          if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
          else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
          var c = document.createElement('canvas');
          c.width = w; c.height = h;
          var ctx = c.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
          ctx.drawImage(img, 0, 0, w, h);
          resolve(suportaWebp ? c.toDataURL('image/webp', 0.72) : c.toDataURL('image/jpeg', 0.6));
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  function render() {
    box.innerHTML = fotos.map(function (d, i) {
      return '<div style="position:relative">' +
        '<img src="' + d + '" style="width:74px;height:74px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0">' +
        '<button type="button" data-i="' + i + '" class="rm" style="position:absolute;top:-6px;right:-6px;background:#dc2626;color:#fff;border:none;border-radius:50%;width:22px;height:22px;font-size:14px;padding:0;line-height:20px">&times;</button>' +
        '</div>';
    }).join('');
    document.getElementById('contagem').textContent = fotos.length;
    btn.disabled = fotos.length === 0;
    box.querySelectorAll('.rm').forEach(function (b) {
      b.addEventListener('click', function () { fotos.splice(+b.dataset.i, 1); render(); });
    });
  }

  async function processarArquivos(fileList) {
    var files = [].slice.call(fileList);
    for (var i = 0; i < files.length; i++) {
      if (fotos.length >= 4) { alert('Máximo de 4 fotos.'); break; }
      if (!files[i].type.startsWith('image/')) continue;
      fotos.push(await comprimir(files[i]));
    }
    render();
  }
  fotoCam.addEventListener('change', function () { processarArquivos(fotoCam.files); fotoCam.value = ''; });
  fotoGaleria.addEventListener('change', function () { processarArquivos(fotoGaleria.files); fotoGaleria.value = ''; });

  btn.addEventListener('click', async function () {
    btn.disabled = true;
    btn.innerHTML = '<span class="spin"></span> Enviando...';
    try {
      var r = await fetch('<?= url('/scan/' . $token . '/fotos-entrada') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fotos: fotos })
      });
      var j = await r.json();
      if (j.ok) {
        document.querySelector('.card').style.display = 'none';
        document.getElementById('okBox').style.display = 'block';
        window.scrollTo(0, 0);
      } else {
        alert(j.erro || 'Não foi possível enviar. Tente de novo.');
        btn.disabled = false;
        btn.textContent = '✅ Enviar pro computador';
      }
    } catch (err) {
      alert('Falha de conexão. Tente de novo.');
      btn.disabled = false;
      btn.textContent = '✅ Enviar pro computador';
    }
  });
})();
</script>
