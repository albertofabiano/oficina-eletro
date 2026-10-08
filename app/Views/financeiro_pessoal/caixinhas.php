<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0 0 20px">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
  <div style="display:flex;flex-direction:column;gap:10px;max-width:280px;margin:0 auto">
    <a href="<?= url('/logout') ?>" class="fp-btn fp-btn-primary" style="text-decoration:none;display:inline-block">
      Entrar com outra conta
    </a>
    <a href="<?= url('/assistencias') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none;display:inline-block">
      Ver o Diretório
    </a>
  </div>
</div>

<?php else: ?>

<?php
// Ícones disponíveis pra caixinha — mesma whitelist curta de CaixinhasController::iconeValido().
$ICONES = ['piggy-bank-fill', 'wallet2', 'bar-chart-fill', 'arrow-up-circle-fill'];
?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Caixinhas</h1>
    <div class="fp-faint">Separe dinheiro pra guardar — o app lembra de transferir e não mexer</div>
  </div>
  <a href="<?= url('/financeiro-pessoal') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none">← Voltar</a>
</div>

<?php $flashOk = flash('success'); $flashErr = flash('error'); ?>
<?php if ($flashOk): ?><div class="fp-card" style="margin-bottom:16px;color:var(--inc);font-weight:700"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="fp-card" style="margin-bottom:16px;color:var(--exp);font-weight:700"><?= e($flashErr) ?></div><?php endif; ?>

<div style="margin-bottom:16px">
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovaCaixinha">+ Nova caixinha</button>
</div>

<div class="fp-caixinhas-grid">
  <?php if (!$caixinhas): ?>
  <div class="fp-card" style="text-align:center;padding:32px 24px;grid-column:1/-1">
    <div style="font-size:1.8rem;margin-bottom:8px"><?= fp_icone('piggy-bank-fill') ?></div>
    <div style="font-weight:700;margin-bottom:6px">Você ainda não tem nenhuma caixinha</div>
    <p class="fp-muted" style="font-size:.88rem;margin:0 0 18px">Crie uma pra começar a separar dinheiro — "Viagem", "Emergência", o que fizer sentido pra você.</p>
    <button type="button" class="fp-btn fp-btn-primary" id="btnNovaCaixinhaVazio">+ Criar minha primeira caixinha</button>
  </div>
  <?php endif; ?>
  <?php foreach ($caixinhas as $cx): ?>
  <?php
    $metaCx = $cx['meta_centavos'] ? (int) $cx['meta_centavos'] : null;
    $saldoCx = (int) $cx['saldo_centavos'];
    $pctMeta = $metaCx ? min(100, (int) round($saldoCx / $metaCx * 100)) : null;
    $faltaCx = $metaCx ? max(0, $metaCx - $saldoCx) : null;
  ?>
  <div class="fp-card<?= !empty($cx['arquivada']) ? ' fp-conta-arquivada' : '' ?>" style="display:flex;flex-direction:column;gap:10px;padding:16px">
    <div style="display:flex;align-items:center;gap:10px">
      <span style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:<?= e($cx['cor']) ?>22;color:<?= e($cx['cor']) ?>;flex:0 0 auto"><?= fp_icone($cx['icone']) ?></span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= e($cx['nome']) ?>
          <?php if (!empty($cx['arquivada'])): ?><span class="fp-chip fp-chip-muted" style="margin-left:6px">Arquivada</span><?php endif; ?>
        </div>
        <?php if (!empty($cx['falta_transferir'])): ?>
        <span class="fp-chip" style="margin-top:2px;background:#f5a62333;color:#c47f0a;font-size:.72rem">⏳ Falta transferir</span>
        <?php endif; ?>
      </div>
      <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-editar-caixinha"
        data-id="<?= (int) $cx['id'] ?>" data-nome="<?= e($cx['nome']) ?>" data-cor="<?= e($cx['cor']) ?>"
        data-icone="<?= e($cx['icone']) ?>" data-meta="<?= $metaCx ? number_format($metaCx / 100, 2, '.', '') : '' ?>"
        data-datameta="<?= e($cx['data_meta'] ?? '') ?>" title="Editar">✎</button>
    </div>

    <div class="fp-mono" style="font-weight:700;font-size:1.5rem">R$ <?= number_format($saldoCx / 100, 2, ',', '.') ?></div>

    <?php if ($metaCx): ?>
    <div>
      <div style="height:7px;border-radius:999px;background:var(--surf2);overflow:hidden">
        <div style="height:100%;width:<?= $pctMeta ?>%;background:<?= e($cx['cor']) ?>;border-radius:999px"></div>
      </div>
      <div class="fp-faint" style="font-size:.74rem;margin-top:4px">
        meta: R$ <?= number_format($metaCx / 100, 2, ',', '.') ?><?= !empty($cx['data_meta']) ? ' até ' . date_br($cx['data_meta']) : '' ?> (<?= $pctMeta ?>%)
      </div>
      <div style="font-size:.76rem;font-weight:600;margin-top:2px;color:<?= $faltaCx > 0 ? e($cx['cor']) : '#16a34a' ?>">
        <?= $faltaCx > 0 ? 'Falta R$ ' . number_format($faltaCx / 100, 2, ',', '.') . ' para cumprir sua meta' : '🎉 Meta atingida!' ?>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:8px;margin-top:4px">
      <button type="button" class="fp-btn fp-btn-primary fp-btn-sm btn-guardar" style="flex:1" data-id="<?= (int) $cx['id'] ?>" data-nome="<?= e($cx['nome']) ?>">Guardar</button>
      <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-retirar" style="flex:1" data-id="<?= (int) $cx['id'] ?>" data-nome="<?= e($cx['nome']) ?>" data-saldo="<?= $saldoCx ?>" data-meta="<?= $metaCx ?: '' ?>">Retirar</button>
    </div>

    <div style="display:flex;gap:6px;justify-content:flex-end">
      <form method="POST" action="<?= url('/financeiro-pessoal/caixinhas') ?>/<?= (int) $cx['id'] ?>/arquivar">
        <?= csrf_field() ?>
        <input type="hidden" name="arquivar" value="<?= !empty($cx['arquivada']) ? '0' : '1' ?>">
        <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm"><?= !empty($cx['arquivada']) ? 'Reativar' : 'Arquivar' ?></button>
      </form>
      <form method="POST" action="<?= url('/financeiro-pessoal/caixinhas') ?>/<?= (int) $cx['id'] ?>/excluir" class="form-excluir-caixinha">
        <?= csrf_field() ?>
        <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm" style="color:var(--exp)">Excluir</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Criar/editar caixinha -->
<div class="fp-modal-backdrop" id="modalCaixinha">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="modalCaixinhaTitulo">Nova caixinha</strong>
      <button type="button" class="fp-modal-close" id="btnFecharCaixinha" aria-label="Fechar">×</button>
    </div>
    <form id="formCaixinha" method="POST" action="<?= url('/financeiro-pessoal/caixinhas') ?>" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <input type="text" name="nome" id="cxNome" class="fp-input" placeholder="Nome (ex.: Viagem, Emergência)" maxlength="80" required>
      <input type="hidden" name="icone" id="cxIcone" value="piggy-bank-fill">
      <div style="display:flex;gap:8px" id="cxIconesWrap">
        <?php foreach ($ICONES as $ic): ?>
        <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-icone-caixinha" data-icone="<?= e($ic) ?>" style="font-size:1.1rem;padding:6px 10px"><?= fp_icone($ic) ?></button>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <label for="cxCor" class="fp-faint" style="font-size:.84rem">Cor</label>
        <input type="color" name="cor" id="cxCor" value="#8C7CFF" style="width:48px;height:38px;padding:2px;border-radius:10px;border:1.5px solid var(--line);background:var(--input)">
      </div>
      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="cxMeta" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Meta (opcional)</label>
          <input type="number" name="meta" id="cxMeta" class="fp-input" step="0.01" min="0" placeholder="R$">
        </div>
        <div style="flex:1">
          <label for="cxDataMeta" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Até (opcional)</label>
          <input type="date" name="data_meta" id="cxDataMeta" class="fp-input">
        </div>
      </div>
      <button type="submit" class="fp-btn fp-btn-primary">Salvar</button>
    </form>
  </div>
</div>

<!-- Guardar — valor sai da conta escolhida; ao salvar, mostra o aviso de transferência. -->
<div class="fp-modal-backdrop" id="modalGuardar">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="guardarTitulo">Guardar</strong>
      <button type="button" class="fp-modal-close" id="btnFecharGuardar" aria-label="Fechar">×</button>
    </div>
    <form id="formGuardar" style="display:flex;flex-direction:column;gap:10px">
      <input type="number" id="guardarValor" class="fp-input" placeholder="R$ Valor" step="0.01" min="0.01" required>
      <select id="guardarConta" class="fp-select">
        <?php foreach ($contas as $c): ?>
        <option value="<?= (int) $c['id'] ?>"><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" id="guardarData" class="fp-input">
      <div id="guardarMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary" id="btnGuardarSalvar">Guardar</button>
    </form>
    <div id="guardarAviso" style="display:none;flex-direction:column;gap:12px">
      <p style="margin:0;font-size:.92rem;line-height:1.5" id="guardarAvisoTexto"></p>
      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="btnJaTransferi" style="flex:1">Já transferi</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="btnLembrarDepois" style="flex:1">Lembrar depois</button>
      </div>
    </div>
  </div>
</div>

<!-- Retirar — pede confirmação (aviso de meta, se houver), nunca bloqueia além do saldo. -->
<div class="fp-modal-backdrop" id="modalRetirar">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="retirarTitulo">Retirar</strong>
      <button type="button" class="fp-modal-close" id="btnFecharRetirar" aria-label="Fechar">×</button>
    </div>
    <form id="formRetirar" style="display:flex;flex-direction:column;gap:10px">
      <div class="fp-faint" style="font-size:.8rem" id="retirarSaldoInfo"></div>
      <input type="number" id="retirarValor" class="fp-input" placeholder="R$ Valor" step="0.01" min="0.01" required>
      <select id="retirarConta" class="fp-select">
        <?php foreach ($contas as $c): ?>
        <option value="<?= (int) $c['id'] ?>"><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" id="retirarData" class="fp-input">
      <div id="retirarMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary">Retirar</button>
    </form>
  </div>
</div>

<style>
  .fp-caixinhas-grid{display:grid;grid-template-columns:1fr;gap:12px}
  @media (min-width:700px){ .fp-caixinhas-grid{grid-template-columns:repeat(2,1fr)} }
  @media (min-width:1100px){ .fp-caixinhas-grid{grid-template-columns:repeat(3,1fr)} }
</style>

<script>
(function () {
  var csrfToken = '<?= csrf_token() ?>';
  var URL_BASE = <?= json_encode(url('/financeiro-pessoal/caixinhas')) ?>;
  var hoje = new Date().toISOString().slice(0, 10);

  // ── Criar/editar caixinha ────────────────────────────────────────────────────────────────
  var modalCx = document.getElementById('modalCaixinha');
  var formCx = document.getElementById('formCaixinha');
  var tituloCx = document.getElementById('modalCaixinhaTitulo');
  var iconeHidden = document.getElementById('cxIcone');

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }

  function marcarIcone(icone) {
    iconeHidden.value = icone;
    document.querySelectorAll('.btn-icone-caixinha').forEach(function (b) {
      b.className = 'fp-btn fp-btn-sm btn-icone-caixinha ' + (b.dataset.icone === icone ? 'fp-btn-primary' : 'fp-btn-ghost');
      b.style.fontSize = '1.1rem'; b.style.padding = '6px 10px';
    });
  }
  document.querySelectorAll('.btn-icone-caixinha').forEach(function (b) {
    b.onclick = function () { marcarIcone(b.dataset.icone); };
  });

  function abrirNovaCaixinha() {
    tituloCx.textContent = 'Nova caixinha';
    formCx.reset();
    marcarIcone('piggy-bank-fill');
    document.getElementById('cxCor').value = '#8C7CFF';
    formCx.action = URL_BASE;
    abrirModal(modalCx);
  }
  document.getElementById('btnNovaCaixinha').onclick = abrirNovaCaixinha;
  var btnVazio = document.getElementById('btnNovaCaixinhaVazio');
  if (btnVazio) { btnVazio.onclick = abrirNovaCaixinha; }

  document.querySelectorAll('.btn-editar-caixinha').forEach(function (btn) {
    btn.onclick = function () {
      tituloCx.textContent = 'Editar caixinha';
      document.getElementById('cxNome').value = btn.dataset.nome;
      document.getElementById('cxCor').value = btn.dataset.cor;
      document.getElementById('cxMeta').value = btn.dataset.meta || '';
      document.getElementById('cxDataMeta').value = btn.dataset.datameta || '';
      marcarIcone(btn.dataset.icone || 'piggy-bank-fill');
      formCx.action = URL_BASE + '/' + btn.dataset.id + '/atualizar';
      abrirModal(modalCx);
    };
  });
  document.getElementById('btnFecharCaixinha').onclick = function () { fecharModal(modalCx); };

  document.querySelectorAll('.form-excluir-caixinha').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm('Excluir esta caixinha? Só é possível se o saldo estiver zerado.')) { ev.preventDefault(); }
    });
  });

  // ── Guardar ───────────────────────────────────────────────────────────────────────────────
  var modalG = document.getElementById('modalGuardar');
  var formG = document.getElementById('formGuardar');
  var avisoG = document.getElementById('guardarAviso');
  var msgG = document.getElementById('guardarMsg');
  var caixinhaIdAtual = null;

  document.querySelectorAll('.btn-guardar').forEach(function (btn) {
    btn.onclick = function () {
      caixinhaIdAtual = btn.dataset.id;
      document.getElementById('guardarTitulo').textContent = 'Guardar em "' + btn.dataset.nome + '"';
      formG.reset();
      formG.style.display = 'flex';
      avisoG.style.display = 'none';
      document.getElementById('guardarData').value = hoje;
      msgG.textContent = '';
      abrirModal(modalG);
    };
  });
  document.getElementById('btnFecharGuardar').onclick = function () { fecharModal(modalG); };

  formG.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var valor = document.getElementById('guardarValor').value;
    if (!valor || parseFloat(valor) <= 0) { msgG.innerHTML = '<span style="color:var(--exp)">Informe um valor válido.</span>'; return; }
    var btnSalvar = document.getElementById('btnGuardarSalvar');
    btnSalvar.disabled = true;

    fetch(URL_BASE + '/' + caixinhaIdAtual + '/guardar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        valor: valor,
        conta_id: document.getElementById('guardarConta').value,
        data: document.getElementById('guardarData').value
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        if (!j.ok) { msgG.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra guardar agora.') + '</span>'; return; }
        formG.style.display = 'none';
        avisoG.style.display = 'flex';
        document.getElementById('guardarAvisoTexto').textContent =
          'Separado! Agora transfira ' + j.valor_formatado + ' para a sua conta de investimento e deixe lá. Dinheiro guardado não é dinheiro disponível.';
        document.getElementById('btnJaTransferi').dataset.movimentoId = j.movimento_id;
      })
      .catch(function () { btnSalvar.disabled = false; msgG.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>'; });
  });

  function fecharEAtualizar() { fecharModal(modalG); location.reload(); }
  document.getElementById('btnLembrarDepois').onclick = fecharEAtualizar;
  document.getElementById('btnJaTransferi').onclick = function () {
    var movId = this.dataset.movimentoId;
    fetch(URL_BASE + '/movimento/' + movId + '/transferido', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).catch(function () {}).then(fecharEAtualizar);
  };

  // ── Retirar ───────────────────────────────────────────────────────────────────────────────
  var modalR = document.getElementById('modalRetirar');
  var formR = document.getElementById('formRetirar');
  var msgR = document.getElementById('retirarMsg');
  var caixinhaRetiradaId = null;
  var saldoAtualCentavos = 0;
  var metaCentavosAtual = 0;

  document.querySelectorAll('.btn-retirar').forEach(function (btn) {
    btn.onclick = function () {
      caixinhaRetiradaId = btn.dataset.id;
      saldoAtualCentavos = parseInt(btn.dataset.saldo, 10) || 0;
      metaCentavosAtual = parseInt(btn.dataset.meta, 10) || 0;
      document.getElementById('retirarTitulo').textContent = 'Retirar de "' + btn.dataset.nome + '"';
      document.getElementById('retirarSaldoInfo').textContent = 'Saldo guardado: R$ ' + (saldoAtualCentavos / 100).toFixed(2).replace('.', ',');
      formR.reset();
      document.getElementById('retirarData').value = hoje;
      msgR.textContent = '';
      abrirModal(modalR);
    };
  });
  document.getElementById('btnFecharRetirar').onclick = function () { fecharModal(modalR); };

  formR.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var valor = document.getElementById('retirarValor').value;
    var valorCentavos = Math.round(parseFloat(valor || '0') * 100);
    if (!valor || valorCentavos <= 0) { msgR.innerHTML = '<span style="color:var(--exp)">Informe um valor válido.</span>'; return; }
    // Mesma validação do servidor, replicada aqui só pra feedback imediato — quem decide de
    // verdade é CaixinhasController::retirar() (nunca confia só no JS).
    if (valorCentavos > saldoAtualCentavos) {
      msgR.innerHTML = '<span style="color:var(--exp)">O valor não pode ser maior que o saldo guardado (R$ ' + (saldoAtualCentavos / 100).toFixed(2).replace('.', ',') + ').</span>';
      return;
    }

    var avisoConfirma = 'Tem certeza?';
    if (metaCentavosAtual > 0) {
      var faltam = metaCentavosAtual - (saldoAtualCentavos - valorCentavos);
      if (faltam > 0) { avisoConfirma = 'Tem certeza? Faltam R$ ' + (faltam / 100).toFixed(2).replace('.', ',') + ' para a meta.'; }
    }
    if (!confirm(avisoConfirma)) { return; }

    fetch(URL_BASE + '/' + caixinhaRetiradaId + '/retirar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        valor: valor,
        conta_id: document.getElementById('retirarConta').value,
        data: document.getElementById('retirarData').value
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { msgR.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra retirar agora.') + '</span>'; return; }
        fecharModal(modalR);
        location.reload();
      })
      .catch(function () { msgR.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>'; });
  });
})();
</script>

<?php endif; ?>
