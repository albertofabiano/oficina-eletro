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
    <div class="fp-faint">Organize seus lançamentos do jeito que fizer sentido pra você</div>
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
  var URL_CRIAR = <?= json_encode(url('/financeiro-pessoal/categorias')) ?>;

  // As 7 categorias padrão guardam a cor como var(--cat-x) (adapta sozinha ao tema) — o
  // <input type="color"> só aceita hex de verdade, então pra ABRIR o seletor já mostrando
  // algo plausível, resolve pro mesmo hex do tema escuro (ver --cat-* em layouts/
  // financeiro_pessoal.php). Se o usuário salvar sem trocar a cor, ela vira esse hex fixo —
  // trade-off aceito (perde a adaptação automática de tema só quando editada pelo CRUD).
  var CORES_PADRAO_HEX = {
    'var(--cat-moradia)': '#5BD47A',
    'var(--cat-transporte)': '#3CC9C0',
    'var(--cat-compras)': '#8C7CFF',
    'var(--cat-alimentacao)': '#FF9F43',
    'var(--cat-outros)': '#B3A3C4',
    'var(--cat-lazer)': '#D46BFF',
    'var(--cat-saude)': '#D9467C'
  };
  function corParaInput(cor) {
    if (/^#[0-9a-fA-F]{6}$/.test(cor)) return cor;
    return CORES_PADRAO_HEX[cor] || '#7A6A88';
  }

  function abrirModal() { modal.classList.add('show'); }
  function fecharModal() { modal.classList.remove('show'); }

  function abrirModalNovaCategoria() {
    titulo.textContent = 'Nova categoria';
    campoNome.value = '';
    campoCor.value = '#7A6A88';
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
      form.action = URL_CRIAR + '/' + btn.dataset.id + '/atualizar';
      abrirModal();
    };
  });

  document.getElementById('btnFecharCategoria').onclick = fecharModal;
})();
</script>

<?php endif; ?>
