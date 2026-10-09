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

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Categorias</h1>
    <div class="fp-faint">Do perfil "<?= e($perfil['nome']) ?>" — organize seus lançamentos do jeito que fizer sentido pra você</div>
  </div>
  <a href="<?= url('/financeiro-pessoal') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none">← Voltar</a>
</div>

<?php $flashOk = flash('success'); $flashErr = flash('error'); ?>
<?php if ($flashOk): ?><div class="fp-card" style="margin-bottom:16px;color:var(--inc);font-weight:700"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="fp-card" style="margin-bottom:16px;color:var(--exp);font-weight:700"><?= e($flashErr) ?></div><?php endif; ?>

<div style="margin-bottom:16px">
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovaCategoria">+ Nova categoria</button>
</div>

<div style="display:flex;flex-direction:column;gap:10px">
  <?php if (!$categorias): ?>
  <div class="fp-card" style="text-align:center;padding:32px 24px">
    <div style="font-size:1.8rem;margin-bottom:8px">🏷️</div>
    <div style="font-weight:700;margin-bottom:6px">Você ainda não tem nenhuma categoria</div>
    <p class="fp-muted" style="font-size:.88rem;margin:0 0 18px">Crie a primeira pra começar a organizar seus lançamentos.</p>
    <button type="button" class="fp-btn fp-btn-primary" id="btnNovaCategoriaVazio">+ Criar minha primeira categoria</button>
  </div>
  <?php endif; ?>
  <?php foreach ($categorias as $chave => $c): ?>
  <div class="fp-card" style="display:flex;align-items:center;gap:12px;padding:14px 16px">
    <span style="width:14px;height:14px;border-radius:50%;background:<?= e($c['cor']) ?>;flex:0 0 auto"></span>
    <div style="flex:1;min-width:0;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($c['nome']) ?></div>
    <span class="fp-chip <?= $c['tipo'] === 'receita' ? 'fp-chip-inc' : 'fp-chip-exp' ?>"><?= $c['tipo'] === 'receita' ? 'Entrada' : 'Gasto' ?></span>
    <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-editar-categoria"
      data-id="<?= (int) $c['id'] ?>" data-nome="<?= e($c['nome']) ?>" data-cor="<?= e($c['cor']) ?>">Editar</button>
    <form method="POST" action="<?= url('/financeiro-pessoal/categorias') ?>/<?= (int) $c['id'] ?>/excluir"
      onsubmit="return confirm('Excluir a categoria &quot;<?= e(addslashes($c['nome'])) ?>&quot;? Lançamentos que já usam ela continuam guardando o nome antigo.');">
      <?= csrf_field() ?>
      <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm" style="color:var(--exp)">Excluir</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<!-- Regras aprendidas — beneficiário/descrição -> categoria (e conta, se houver), aprendidas
     sozinhas conforme você lança (manual, escaneado ou por voz, ver CLAUDE.md). Só leitura +
     excluir; não dá pra criar uma regra na mão aqui, ela nasce do uso real. -->
<?php if ($regras): ?>
<div class="fp-page-header" style="margin-top:28px">
  <div>
    <h2 class="fp-greeting" style="font-size:1.05rem">Regras aprendidas</h2>
    <div class="fp-faint">O sistema já aprendeu a categorizar sozinho estes beneficiários/descrições</div>
  </div>
</div>
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
  <?php foreach ($regras as $r): ?>
  <div class="fp-card" style="display:flex;align-items:center;gap:12px;padding:12px 16px">
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-transform:capitalize"><?= e($r['termo']) ?></div>
      <div class="fp-faint" style="font-size:.8rem">
        → <?= e($r['categoria']) ?><?= $r['conta'] ? ' · ' . e($r['conta']) : '' ?>
        · usado <?= (int) $r['usos'] ?>x<?= $r['confirmada'] ? ' · confirmada' : '' ?>
      </div>
    </div>
    <form method="POST" action="<?= url('/financeiro-pessoal/categorias/regras') ?>/<?= (int) $r['id'] ?>/excluir"
      onsubmit="return confirm('Esquecer esta regra? Da próxima vez, a categoria volta a ser sugerida pela IA (ou fica em branco).');">
      <?= csrf_field() ?>
      <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm" style="color:var(--exp)">Excluir</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Mesmo modal reaproveitado pra criar E editar — só muda o action/título via JS, mesmo
     padrão já usado no modal de Lançamento (ver financeiro_pessoal/index.php). -->
<div class="fp-modal-backdrop" id="modalCategoria">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="modalCategoriaTitulo">Nova categoria</strong>
      <button type="button" class="fp-modal-close" id="btnFecharCategoria" aria-label="Fechar">×</button>
    </div>
    <form id="formCategoria" method="POST" action="<?= url('/financeiro-pessoal/categorias') ?>" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <input type="text" name="nome" id="catNome" class="fp-input" placeholder="Nome da categoria" maxlength="60" required>
      <!-- Tipo só aparece ao CRIAR — não muda depois (mesmo princípio da chave: lançamento já
           feito com essa categoria não deveria "virar" de gasto pra entrada por baixo). -->
      <div id="catTipoWrap" style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-despesa" id="catTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
        <button type="button" class="fp-btn fp-btn-receita-ghost" id="catTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
      </div>
      <input type="hidden" name="tipo" id="catTipo" value="despesa">
      <div style="display:flex;align-items:center;gap:10px">
        <label for="catCor" class="fp-faint" style="font-size:.84rem">Cor</label>
        <input type="color" name="cor" id="catCor" value="#7A6A88" style="width:48px;height:38px;padding:2px;border-radius:10px;border:1.5px solid var(--line);background:var(--input)">
      </div>
      <button type="submit" class="fp-btn fp-btn-primary">Salvar</button>
    </form>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('modalCategoria');
  var form = document.getElementById('formCategoria');
  var titulo = document.getElementById('modalCategoriaTitulo');
  var campoNome = document.getElementById('catNome');
  var campoCor = document.getElementById('catCor');
  var tipoWrap = document.getElementById('catTipoWrap');
  var tipoHidden = document.getElementById('catTipo');
  var btnTipoDespesa = document.getElementById('catTipoDespesa');
  var btnTipoReceita = document.getElementById('catTipoReceita');
  var URL_CRIAR = <?= json_encode(url('/financeiro-pessoal/categorias')) ?>;

  // As categorias padrão guardam a cor como var(--cat-x)/var(--inc) (adapta sozinha ao tema) —
  // o <input type="color"> só aceita hex de verdade, então pra ABRIR o seletor já mostrando
  // algo plausível, resolve pro mesmo hex do tema escuro (ver --cat-*/--inc em layouts/
  // financeiro_pessoal.php). Se o usuário salvar sem trocar a cor, ela vira esse hex fixo —
  // trade-off aceito (perde a adaptação automática de tema só quando editada pelo CRUD).
  var CORES_PADRAO_HEX = {
    'var(--cat-moradia)': '#5BD47A',
    'var(--cat-transporte)': '#3CC9C0',
    'var(--cat-compras)': '#8C7CFF',
    'var(--cat-alimentacao)': '#FF9F43',
    'var(--cat-outros)': '#B3A3C4',
    'var(--cat-lazer)': '#D46BFF',
    'var(--cat-saude)': '#D9467C',
    'var(--inc)': '#4FD8A8'
  };
  function corParaInput(cor) {
    if (/^#[0-9a-fA-F]{6}$/.test(cor)) return cor;
    return CORES_PADRAO_HEX[cor] || '#7A6A88';
  }

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnTipoDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-ghost');
    btnTipoReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita' : 'fp-btn-receita-ghost');
  }
  btnTipoDespesa.onclick = function () { marcarTipo('despesa'); };
  btnTipoReceita.onclick = function () { marcarTipo('receita'); };

  function abrirModal() { modal.classList.add('show'); }
  function fecharModal() { modal.classList.remove('show'); }

  function abrirModalNovaCategoria() {
    titulo.textContent = 'Nova categoria';
    campoNome.value = '';
    campoCor.value = '#7A6A88';
    marcarTipo('despesa');
    tipoWrap.style.display = 'flex';
    form.action = URL_CRIAR;
    abrirModal();
  }
  document.getElementById('btnNovaCategoria').onclick = abrirModalNovaCategoria;
  // Mesmo botão do empty state ("Você ainda não tem nenhuma categoria") — só existe na
  // renderização quando $categorias está vazio, por isso o getElementById condicional.
  var btnVazio = document.getElementById('btnNovaCategoriaVazio');
  if (btnVazio) { btnVazio.onclick = abrirModalNovaCategoria; }

  document.querySelectorAll('.btn-editar-categoria').forEach(function (btn) {
    btn.onclick = function () {
      titulo.textContent = 'Editar categoria';
      campoNome.value = btn.dataset.nome;
      campoCor.value = corParaInput(btn.dataset.cor);
      // Tipo não é editável depois de criada (ver comentário no HTML) — escondido aqui.
      tipoWrap.style.display = 'none';
      form.action = URL_CRIAR + '/' + btn.dataset.id + '/atualizar';
      abrirModal();
    };
  });

  document.getElementById('btnFecharCategoria').onclick = fecharModal;
})();
</script>

<?php endif; ?>
