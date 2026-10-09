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
$TIPOS = [
    'corrente'       => 'Conta corrente',
    'poupanca'       => 'Poupança',
    'dinheiro'       => 'Dinheiro',
    'cartao_credito' => 'Cartão de crédito',
    'investimento'   => 'Investimento',
];
?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Contas</h1>
    <div class="fp-faint">Contas do perfil "<?= e($perfil['nome']) ?>" — saldo inicial e saldo atual de cada uma</div>
  </div>
  <a href="<?= url('/financeiro-pessoal') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none">← Voltar</a>
</div>

<?php $flashOk = flash('success'); $flashErr = flash('error'); ?>
<?php if ($flashOk): ?><div class="fp-card" style="margin-bottom:16px;color:var(--inc);font-weight:700"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="fp-card" style="margin-bottom:16px;color:var(--exp);font-weight:700"><?= e($flashErr) ?></div><?php endif; ?>

<div style="margin-bottom:16px">
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovaConta">+ Nova conta</button>
</div>

<div style="display:flex;flex-direction:column;gap:10px">
  <?php if (!$contas): ?>
  <div class="fp-card" style="text-align:center;padding:32px 24px">
    <div style="font-size:1.8rem;margin-bottom:8px"><?= fp_icone('wallet2') ?></div>
    <div style="font-weight:700;margin-bottom:6px">Você ainda não tem nenhuma conta</div>
    <p class="fp-muted" style="font-size:.88rem;margin:0 0 18px">Crie a primeira — dá até pra deixar "Carteira" mesmo, só em dinheiro.</p>
    <button type="button" class="fp-btn fp-btn-primary" id="btnNovaContaVazio">+ Criar minha primeira conta</button>
  </div>
  <?php endif; ?>
  <?php foreach ($contas as $c): ?>
  <?php $neg = $c['saldo_atual'] < 0; ?>
  <div class="fp-card<?= !empty($c['arquivada']) ? ' fp-conta-arquivada' : '' ?>" style="display:flex;align-items:center;gap:12px;padding:14px 16px;flex-wrap:wrap">
    <span style="width:14px;height:14px;border-radius:50%;background:<?= e($c['cor']) ?>;flex:0 0 auto"></span>
    <div style="flex:1;min-width:160px">
      <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= e($c['nome']) ?>
        <?php if (!empty($c['arquivada'])): ?><span class="fp-chip fp-chip-muted" style="margin-left:6px">Arquivada</span><?php endif; ?>
        <?php if (!empty($c['padrao'])): ?><span class="fp-chip fp-chip-muted" style="margin-left:6px" title="Conta criada automaticamente com o perfil — não pode ser arquivada nem excluída">Padrão</span><?php endif; ?>
      </div>
      <div class="fp-faint" style="font-size:.78rem;margin-top:2px"><?= e($TIPOS[$c['tipo']] ?? $c['tipo']) ?> · saldo inicial R$ <?= number_format((float) $c['saldo_inicial'], 2, ',', '.') ?> em <?= date_br($c['data_saldo_inicial']) ?></div>
    </div>
    <div class="fp-mono" style="font-weight:700;font-size:1.05rem;color:<?= $neg ? 'var(--exp)' : 'var(--inc)' ?>;min-width:120px;text-align:right">
      <?= $neg ? '−' : '' ?>R$ <?= number_format(abs($c['saldo_atual']), 2, ',', '.') ?>
    </div>
    <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-editar-conta"
      data-id="<?= (int) $c['id'] ?>" data-nome="<?= e($c['nome']) ?>" data-tipo="<?= e($c['tipo']) ?>"
      data-saldo="<?= (float) $c['saldo_inicial'] ?>" data-data="<?= e($c['data_saldo_inicial']) ?>" data-cor="<?= e($c['cor']) ?>">Editar</button>
    <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-ajustar-saldo"
      data-id="<?= (int) $c['id'] ?>" data-nome="<?= e($c['nome']) ?>" data-saldo-atual="<?= (float) $c['saldo_atual'] ?>">Corrigir saldo</button>
    <?php if (empty($c['padrao']) || !empty($c['arquivada'])): ?>
    <form method="POST" action="<?= url('/financeiro-pessoal/contas') ?>/<?= (int) $c['id'] ?>/arquivar">
      <?= csrf_field() ?>
      <input type="hidden" name="arquivar" value="<?= !empty($c['arquivada']) ? '0' : '1' ?>">
      <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm"><?= !empty($c['arquivada']) ? 'Reativar' : 'Arquivar' ?></button>
    </form>
    <?php endif; ?>
    <?php if (empty($c['padrao'])): ?>
    <form method="POST" action="<?= url('/financeiro-pessoal/contas') ?>/<?= (int) $c['id'] ?>/excluir" class="form-excluir-conta">
      <?= csrf_field() ?>
      <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm" style="color:var(--exp)">Excluir</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<div class="fp-modal-backdrop" id="modalConta">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="modalContaTitulo">Nova conta</strong>
      <button type="button" class="fp-modal-close" id="btnFecharConta" aria-label="Fechar">×</button>
    </div>
    <form id="formConta" method="POST" action="<?= url('/financeiro-pessoal/contas') ?>" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <input type="text" name="nome" id="contaNome" class="fp-input" placeholder="Nome da conta" maxlength="80" required>
      <select name="tipo" id="contaTipo" class="fp-select">
        <?php foreach ($TIPOS as $chave => $nome): ?>
        <option value="<?= e($chave) ?>"><?= e($nome) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="contaSaldo" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Saldo inicial</label>
          <input type="number" name="saldo_inicial" id="contaSaldo" class="fp-input" step="0.01" value="0">
        </div>
        <div style="flex:1">
          <label for="contaData" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Data do saldo inicial</label>
          <input type="date" name="data_saldo_inicial" id="contaData" class="fp-input">
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <label for="contaCor" class="fp-faint" style="font-size:.84rem">Cor</label>
        <input type="color" name="cor" id="contaCor" value="#3CC9C0" style="width:48px;height:38px;padding:2px;border-radius:10px;border:1.5px solid var(--line);background:var(--input)">
      </div>
      <button type="submit" class="fp-btn fp-btn-primary">Salvar</button>
    </form>
  </div>
</div>

<!-- Corrigir saldo: diferente de "Editar" (que mexe no saldo_inicial, o ponto de partida
     histórico da conta), este ajusta o saldo ATUAL — a diferença vira um lançamento de ajuste
     já pago na conta, ver FixaContasController::ajustarSaldo(). -->
<div class="fp-modal-backdrop" id="modalAjustarSaldo">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Corrigir saldo — <span id="ajusteSaldoNomeConta"></span></strong>
      <button type="button" class="fp-modal-close" id="btnFecharAjusteSaldo" aria-label="Fechar">×</button>
    </div>
    <form id="formAjustarSaldo" method="POST" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div class="fp-faint" style="font-size:.85rem">
        Saldo atual: <strong class="fp-mono" id="ajusteSaldoAtual"></strong>
      </div>
      <div>
        <label for="ajusteSaldoNovo" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Novo saldo</label>
        <input type="number" name="novo_saldo" id="ajusteSaldoNovo" class="fp-input" step="0.01" required>
      </div>
      <p class="fp-faint" style="font-size:.78rem;margin:0">
        A diferença vira um lançamento de "Ajuste de saldo" (já pago, hoje) nesta conta — o
        saldo inicial não é alterado.
      </p>
      <button type="submit" class="fp-btn fp-btn-primary">Corrigir saldo</button>
    </form>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('modalConta');
  var form = document.getElementById('formConta');
  var titulo = document.getElementById('modalContaTitulo');
  var URL_CRIAR = <?= json_encode(url('/financeiro-pessoal/contas')) ?>;

  function abrirModal() { modal.classList.add('show'); }
  function fecharModal() { modal.classList.remove('show'); }

  function abrirModalNovaConta() {
    titulo.textContent = 'Nova conta';
    form.reset();
    document.getElementById('contaData').value = new Date().toISOString().slice(0, 10);
    document.getElementById('contaCor').value = '#3CC9C0';
    form.action = URL_CRIAR;
    abrirModal();
  }
  document.getElementById('btnNovaConta').onclick = abrirModalNovaConta;
  var btnVazio = document.getElementById('btnNovaContaVazio');
  if (btnVazio) { btnVazio.onclick = abrirModalNovaConta; }

  document.querySelectorAll('.btn-editar-conta').forEach(function (btn) {
    btn.onclick = function () {
      titulo.textContent = 'Editar conta';
      document.getElementById('contaNome').value = btn.dataset.nome;
      document.getElementById('contaTipo').value = btn.dataset.tipo;
      document.getElementById('contaSaldo').value = btn.dataset.saldo;
      document.getElementById('contaData').value = btn.dataset.data;
      document.getElementById('contaCor').value = btn.dataset.cor;
      form.action = URL_CRIAR + '/' + btn.dataset.id + '/atualizar';
      abrirModal();
    };
  });

  document.getElementById('btnFecharConta').onclick = fecharModal;

  // Corrigir saldo — modal próprio, independente do de criar/editar conta.
  var modalAjuste = document.getElementById('modalAjustarSaldo');
  var formAjuste = document.getElementById('formAjustarSaldo');
  var ajusteNomeConta = document.getElementById('ajusteSaldoNomeConta');
  var ajusteSaldoAtual = document.getElementById('ajusteSaldoAtual');
  var ajusteSaldoNovo = document.getElementById('ajusteSaldoNovo');

  function fmtMoeda(v) {
    var n = Number(v);
    var neg = n < 0;
    return (neg ? '−' : '') + 'R$ ' + Math.abs(n).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  document.querySelectorAll('.btn-ajustar-saldo').forEach(function (btn) {
    btn.onclick = function () {
      ajusteNomeConta.textContent = btn.dataset.nome;
      ajusteSaldoAtual.textContent = fmtMoeda(btn.dataset.saldoAtual);
      ajusteSaldoNovo.value = btn.dataset.saldoAtual;
      formAjuste.action = URL_CRIAR + '/' + btn.dataset.id + '/ajustar-saldo';
      modalAjuste.classList.add('show');
    };
  });
  document.getElementById('btnFecharAjusteSaldo').onclick = function () { modalAjuste.classList.remove('show'); };

  // Excluir é de verdade (DELETE, não arquivar) — confirmação explícita, até porque é a única
  // ação desta tela que não dá pra desfazer sozinho pela própria UI.
  document.querySelectorAll('.form-excluir-conta').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm('Excluir esta conta? Lançamentos já lançados nela não são apagados, só ficam sem conta vinculada. Essa ação não pode ser desfeita.')) {
        ev.preventDefault();
      }
    });
  });
})();
</script>

<?php endif; ?>
