<?php $placa = ($modo ?? '') === 'placa'; ?>
<div class="card">
  <h1><?= $placa ? 'Aponte para a placa' : 'Aponte para a etiqueta do aparelho' ?></h1>
  <p class="hint"><?= $placa
    ? '📸 Bata a foto do código / part number da placa. A IA identifica a peça e os modelos compatíveis e preenche o anúncio no computador. Se preferir, digite o código.'
    : '📸 Bata a foto da etiqueta — o sistema lê marca, modelo e série sozinho. Se preferir, digite. Ao enviar, aparece no computador na hora.' ?></p>

  <form id="fScan" enctype="multipart/form-data">

    <label class="btn btn-cam" for="foto" style="margin-top:6px"><?= $placa ? '📸 Foto da placa / part number' : '📸 Foto da etiqueta (tirar ou escolher)' ?></label>
    <input id="foto" name="foto" type="file" accept="image/*" style="display:none">
    <img id="preview" class="preview" alt="prévia">

    <div id="camposManuais">
    <?php if ($placa): ?>
      <label>Part number / código (opcional)</label>
      <input type="text" name="codigo" id="s_codigo" placeholder="Ex: BN94-12695R, EAX69532304" autocomplete="off">
      <p class="hint" style="margin-top:6px">Dica: se o código estiver legível na foto, nem precisa digitar.</p>
    <?php else: ?>
      <label>Tipo de equipamento</label>
      <input type="text" name="tipo" id="s_tipo" placeholder="Ex: TV de LED 32" autocomplete="off">
      <label>Marca</label>
      <input type="text" name="marca" id="s_marca" placeholder="Ex: Samsung" autocomplete="off">
      <label>Modelo</label>
      <input type="text" name="modelo" id="s_modelo" placeholder="Ex: UN32J4200" autocomplete="off">
      <label>Nº de série</label>
      <input type="text" name="serie" id="s_serie" placeholder="Nº de série / S/N" autocomplete="off">
    <?php endif; ?>
    </div>
    <a href="#" id="linkManual" class="hint" style="display:none;text-align:center;text-decoration:underline;margin-top:8px">✏️ Preencher os campos à mão</a>

    <button type="submit" class="btn btn-send" id="btnEnviar">✅ Enviar para o computador</button>
  </form>
</div>

<div class="card ok-box" id="okBox" style="display:none">
  <div class="big">✅</div>
  <h1 style="margin-top:.4rem">Enviado!</h1>
  <p class="hint"><?= $placa ? 'A peça já apareceu na tela do computador. Pode voltar e continuar por lá.' : 'Os dados já apareceram na tela do computador. Pode voltar e continuar por lá.' ?></p>
  <button class="btn btn-cam" onclick="location.reload()" style="margin-top:12px">Escanear outro</button>
</div>

<script>
(function(){
  var placa = <?= $placa ? 'true' : 'false' ?>;
  var foto = document.getElementById('foto');
  foto.addEventListener('change', function(){
    if (foto.files && foto.files[0]) {
      var img = document.getElementById('preview');
      img.src = URL.createObjectURL(foto.files[0]);
      img.style.display = 'block';
      // com foto, a IA preenche sozinha: esconde os campos manuais pra deixar o botão à mão
      var cm = document.getElementById('camposManuais'); if (cm) cm.style.display = 'none';
      var lk = document.getElementById('linkManual'); if (lk) lk.style.display = 'block';
    }
  });
  var _lk = document.getElementById('linkManual');
  if (_lk) _lk.addEventListener('click', function(e){
    e.preventDefault();
    document.getElementById('camposManuais').style.display = '';
    this.style.display = 'none';
  });

  document.getElementById('fScan').addEventListener('submit', async function(e){
    e.preventDefault();
    var temFoto = foto.files && foto.files.length;
    var ids = placa ? ['s_codigo'] : ['s_tipo','s_marca','s_modelo','s_serie'];
    var algumCampo = ids.some(function(id){ var el=document.getElementById(id); return el && el.value.trim() !== ''; });
    if (!temFoto && !algumCampo) { alert(placa ? 'Fotografe o código da placa ou digite o part number.' : 'Tire a foto da etiqueta ou preencha ao menos um campo.'); return; }

    var btn = document.getElementById('btnEnviar');
    btn.disabled = true; btn.innerHTML = '<span class="spin"></span> ' + (placa ? 'Identificando a peça…' : 'Enviando…');
    try {
      var fd = new FormData(this);
      var r = await fetch('<?= url('/scan/' . $token . '/foto') ?>', { method:'POST', body: fd });
      var j = await r.json();
      if (j.ok) {
        document.getElementById('fScan').closest('.card').style.display = 'none';
        document.getElementById('okBox').style.display = 'block';
        window.scrollTo(0,0);
      } else {
        alert(j.erro || 'Não foi possível enviar. Tente de novo.');
        btn.disabled = false; btn.textContent = '✅ Enviar para o computador';
      }
    } catch(err) {
      alert('Falha de conexão. Tente de novo.');
      btn.disabled = false; btn.textContent = '✅ Enviar para o computador';
    }
  });
})();
</script>
