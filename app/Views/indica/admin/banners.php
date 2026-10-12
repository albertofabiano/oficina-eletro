<div class="topo"><h1>Banners</h1></div>

<p style="color:var(--texto-sec);font-size:.88rem;max-width:720px;margin-top:-10px">
  Aparecem na sidebar da página de produto (ver screenshot pedido pelo usuário). Hoje só o
  FixaOS anuncia aqui (planos, Carteira Fixa, Diretório grátis) — lista estática, sem rotação;
  isso pode mudar no futuro (ex. AdSense), mas por ora é só o que você cadastrar aqui, na ordem
  que definir.
</p>

<div class="card">
  <h3 style="margin-top:0">Novo banner</h3>
  <form method="POST" action="/admin/banners">
    <?= csrf_field() ?>
    <div class="campo"><label>Título</label><input type="text" name="titulo" required></div>
    <div class="campo"><label>Descrição (opcional)</label><textarea name="descricao" rows="2" maxlength="200"></textarea></div>
    <div class="campo"><label>Link</label><input type="url" name="link" placeholder="https://fixaos.com.br/planos" required></div>
    <div class="grid-2">
      <div class="campo"><label>Cor de fundo</label><input type="color" name="cor" value="#7c3aed" style="padding:3px;height:42px"></div>
      <div class="campo"><label>Ordem</label><input type="number" name="ordem" value="0"></div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;margin:0 0 16px"><input type="checkbox" name="ativo" value="1" checked style="width:auto"> Ativo</label>
    <button type="submit" class="btn">+ Criar banner</button>
  </form>
</div>

<?php if (empty($banners)): ?>
  <div class="card"><p style="color:var(--texto-sec);margin:0">Nenhum banner cadastrado ainda.</p></div>
<?php endif; ?>

<?php foreach ($banners as $b): ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
      <span style="width:14px;height:14px;border-radius:4px;background:<?= e($b['cor']) ?>;display:inline-block;flex:0 0 auto"></span>
      <div>
        <strong><?= e($b['titulo']) ?></strong>
        <span class="badge <?= $b['ativo'] ? 'badge-verde' : 'badge-cinza' ?>" style="margin-left:8px"><?= $b['ativo'] ? 'Ativo' : 'Inativo' ?></span>
        <div style="color:var(--texto-sec);font-size:.82rem;margin-top:4px">
          <a href="<?= e($b['link']) ?>" target="_blank" rel="noopener"><?= e($b['link']) ?></a> · ordem <?= (int) $b['ordem'] ?>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" class="btn btn-secundario btn-sm" onclick="var f=document.getElementById('f-ban-<?= (int) $b['id'] ?>'); f.style.display = f.style.display === 'none' ? 'block' : 'none';">Editar</button>
      <form method="POST" action="/admin/banners/<?= (int) $b['id'] ?>/excluir" onsubmit="return confirm('Excluir este banner?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-perigo btn-sm">Excluir</button>
      </form>
    </div>
  </div>

  <form method="POST" action="/admin/banners/<?= (int) $b['id'] ?>/atualizar" id="f-ban-<?= (int) $b['id'] ?>" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid var(--borda)">
    <?= csrf_field() ?>
    <div class="campo"><label>Título</label><input type="text" name="titulo" value="<?= e($b['titulo']) ?>" required></div>
    <div class="campo"><label>Descrição</label><textarea name="descricao" rows="2" maxlength="200"><?= e($b['descricao'] ?? '') ?></textarea></div>
    <div class="campo"><label>Link</label><input type="url" name="link" value="<?= e($b['link']) ?>" required></div>
    <div class="grid-2">
      <div class="campo"><label>Cor de fundo</label><input type="color" name="cor" value="<?= e($b['cor']) ?>" style="padding:3px;height:42px"></div>
      <div class="campo"><label>Ordem</label><input type="number" name="ordem" value="<?= (int) $b['ordem'] ?>"></div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;margin:0 0 16px"><input type="checkbox" name="ativo" value="1" <?= $b['ativo'] ? 'checked' : '' ?> style="width:auto"> Ativo</label>
    <button type="submit" class="btn">Salvar</button>
  </form>
</div>
<?php endforeach; ?>
