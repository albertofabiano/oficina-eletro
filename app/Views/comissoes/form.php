<?php $comissao = $comissao ?? null; ?>
<div class="row justify-content-center"><div class="col-lg-7">
<form method="POST" action="<?= $comissao ? url('/comissoes/' . $comissao['id'] . '/atualizar') : url('/comissoes') ?>" id="formComissao">
  <?= csrf_field() ?>
  <input type="hidden" name="os_id" id="fOsId" value="<?= e($comissao['os_id'] ?? '') ?>">
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><?= e($titulo) ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label small fw-semibold">Técnico *</label>
          <select name="tecnico_id" id="fTecnicoId" class="form-select" required>
            <option value="">Selecione...</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" data-percentual="<?= $t['comissao_percentual'] !== null ? e($t['comissao_percentual']) : '' ?>" <?= ($comissao && (int) $comissao['tecnico_id'] === (int) $t['id']) ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label small fw-semibold">OS relacionada (opcional)</label>
          <div class="position-relative">
            <input type="text" id="buscaOs" class="form-control" placeholder="Busque por número ou cliente..." autocomplete="off"
              value="<?= $comissao && $comissao['os_numero'] ? 'OS: ' . e($comissao['os_numero']) : '' ?>"
              <?= ($comissao && $comissao['tecnico_id']) ? '' : 'disabled' ?>>
            <div id="resultadosOs" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000;display:none;max-height:220px;overflow-y:auto"></div>
          </div>
          <div id="osSelecionadaInfo" class="form-text text-success" style="<?= ($comissao && $comissao['os_numero']) ? '' : 'display:none' ?>"><?= $comissao && $comissao['os_numero'] ? 'OS selecionada: ' . e($comissao['os_numero']) : '' ?></div>
        </div>

        <div class="col-md-8">
          <label class="form-label small fw-semibold">Valor do serviço *</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_base" id="fValorBase" class="form-control" placeholder="0,00" required
              value="<?= $comissao ? number_format((float) $comissao['valor_base'], 2, ',', '.') : '' ?>">
            <button type="button" class="btn btn-outline-secondary" id="btnPuxarValor" <?= ($comissao && $comissao['os_id']) ? '' : 'disabled' ?>
              title="<?= $modoCalculo === 'total' ? 'Puxar o valor total da OS (peças + mão de obra)' : 'Somar só a mão de obra que o técnico fez nessa OS' ?>">
              <i class="bi bi-arrow-repeat me-1"></i>Puxar da OS
            </button>
          </div>
          <div class="form-text">
            Você pode digitar o valor à mão ou puxar automaticamente da OS selecionada acima.
            Modo atual: <strong><?= $modoCalculo === 'total' ? 'total da OS (peças + mão de obra)' : 'só mão de obra' ?></strong>
            <a href="<?= url('/empresa') ?>" class="text-decoration-none">(alterar)</a>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Percentual *</label>
          <div class="input-group">
            <input type="text" name="percentual" id="fPercentual" class="form-control" required
              value="<?= $comissao ? number_format((float) $comissao['percentual'], 2, ',', '.') : '' ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>

        <div class="col-12">
          <div class="alert alert-light border d-flex justify-content-between align-items-center mb-0">
            <span class="text-muted">Valor da comissão</span>
            <span class="fs-4 fw-bold text-success" id="previewComissao">R$ 0,00</span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/comissoes') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><?= $comissao ? 'Salvar Alterações' : 'Salvar Comissão' ?></button>
  </div>
</form>
</div></div>

<script>
(function(){
  var PERCENTUAL_PADRAO = <?= json_encode($percentualPadrao) ?>;
  var API_BASE = '<?= url('/api/comissoes') ?>';

  var tecnicoSel  = document.getElementById('fTecnicoId');
  var buscaOs     = document.getElementById('buscaOs');
  var resultadosOs= document.getElementById('resultadosOs');
  var osIdInput   = document.getElementById('fOsId');
  var osInfo      = document.getElementById('osSelecionadaInfo');
  var valorBase   = document.getElementById('fValorBase');
  var percentual  = document.getElementById('fPercentual');
  var btnPuxar    = document.getElementById('btnPuxarValor');
  var preview     = document.getElementById('previewComissao');

  function fmt(v){ return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}); }
  function parseValor(v){
    v = String(v || '0').replace(/[^\d,.-]/g,'');
    if (v.indexOf(',') !== -1) { v = v.replace(/\./g,'').replace(',','.'); }
    return parseFloat(v) || 0;
  }

  function atualizarPreview(){
    var vb = parseValor(valorBase.value);
    var pc = parseValor(percentual.value);
    preview.textContent = fmt(vb * pc / 100);
  }
  valorBase.addEventListener('input', atualizarPreview);
  percentual.addEventListener('input', atualizarPreview);

  tecnicoSel.addEventListener('change', function(){
    var ligado = !!this.value;
    buscaOs.disabled = !ligado;
    osIdInput.value = '';
    osInfo.style.display = 'none';
    btnPuxar.disabled = true;
    buscaOs.value = '';

    var opt = this.options[this.selectedIndex];
    var pPropria = opt ? opt.getAttribute('data-percentual') : '';
    percentual.value = (pPropria && pPropria !== '') ? pPropria : PERCENTUAL_PADRAO;
    atualizarPreview();
  });

  var buscaTimeout;
  buscaOs.addEventListener('input', function(){
    clearTimeout(buscaTimeout);
    var termo = this.value.trim();
    buscaTimeout = setTimeout(function(){
      fetch(API_BASE + '/buscar-os?tecnico_id=' + encodeURIComponent(tecnicoSel.value) + '&q=' + encodeURIComponent(termo))
        .then(function(r){ return r.json(); })
        .then(function(j){
          var lista = j.os || [];
          if (!lista.length) { resultadosOs.style.display='none'; resultadosOs.innerHTML=''; return; }
          resultadosOs.innerHTML = lista.map(function(os){
            return '<button type="button" class="list-group-item list-group-item-action" data-id="'+os.id+'" data-numero="'+os.numero+'">' +
              '<strong>OS: '+os.numero+'</strong> — '+os.cliente_nome+
              '</button>';
          }).join('');
          resultadosOs.style.display = 'block';
          Array.prototype.forEach.call(resultadosOs.querySelectorAll('button'), function(btn){
            btn.addEventListener('click', function(){
              osIdInput.value = this.dataset.id;
              osInfo.textContent = 'OS selecionada: ' + this.dataset.numero;
              osInfo.style.display = 'block';
              buscaOs.value = 'OS: ' + this.dataset.numero;
              resultadosOs.style.display = 'none';
              btnPuxar.disabled = false;
            });
          });
        }).catch(function(){});
    }, 300);
  });

  document.addEventListener('click', function(e){
    if (!resultadosOs.contains(e.target) && e.target !== buscaOs) resultadosOs.style.display = 'none';
  });

  btnPuxar.addEventListener('click', function(){
    if (!osIdInput.value || !tecnicoSel.value) return;
    var orig = this.innerHTML;
    this.disabled = true; this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch(API_BASE + '/valor-servicos?tecnico_id=' + tecnicoSel.value + '&os_id=' + osIdInput.value)
      .then(function(r){ return r.json(); })
      .then(function(j){
        valorBase.value = Number(j.valor || 0).toFixed(2).replace('.', ',');
        atualizarPreview();
      })
      .finally(function(){ btnPuxar.disabled = false; btnPuxar.innerHTML = orig; });
  });

  atualizarPreview();
})();
</script>
