<div class="topo"><h1>Categorias</h1></div>

<div class="card">
  <h3 style="margin-top:0">Nova categoria</h3>
  <form method="POST" action="/admin/categorias">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="campo"><label>Nome</label><input type="text" name="nome" required></div>
      <div class="campo"><label>Ícone (emoji)</label><input type="text" name="icone" placeholder="🔧"></div>
    </div>
    <div class="campo"><label>Texto SEO (meta description da categoria)</label><textarea name="texto_seo" rows="2"></textarea></div>
    <div class="grid-2">
      <div class="campo"><label>Ordem</label><input type="number" name="ordem" value="0"></div>
      <div class="campo" style="display:flex;align-items:end">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="ativa" value="1" checked style="width:auto"> Ativa</label>
      </div>
    </div>
    <button type="submit" class="btn">+ Criar categoria</button>
  </form>
</div>

<?php if (empty($categorias)): ?>
  <div class="card"><p style="color:var(--texto-sec);margin:0">Nenhuma categoria cadastrada ainda.</p></div>
<?php endif; ?>

<?php foreach ($categorias as $c): ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <div>
      <strong><?= e($c['icone'] ?? '') ?> <?= e($c['nome']) ?></strong>
      <span class="badge <?= $c['ativa'] ? 'badge-verde' : 'badge-cinza' ?>" style="margin-left:8px"><?= $c['ativa'] ? 'Ativa' : 'Inativa' ?></span>
      <div style="color:var(--texto-sec);font-size:.82rem;margin-top:4px">
        <a href="/categoria/<?= e($c['slug']) ?>" target="_blank">/categoria/<?= e($c['slug']) ?></a> · ordem <?= (int) $c['ordem'] ?>
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" class="btn btn-secundario btn-sm" onclick="var f=document.getElementById('f-cat-<?= (int) $c['id'] ?>'); f.style.display = f.style.display === 'none' ? 'block' : 'none';">Editar</button>
      <form method="POST" action="/admin/categorias/<?= (int) $c['id'] ?>/excluir" onsubmit="return confirm('Excluir esta categoria? Produtos ligados a ela ficam sem categoria, não são excluídos.')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-perigo btn-sm">Excluir</button>
      </form>
    </div>
  </div>

  <form method="POST" action="/admin/categorias/<?= (int) $c['id'] ?>/atualizar" id="f-cat-<?= (int) $c['id'] ?>" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid var(--borda)">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="campo"><label>Nome</label><input type="text" name="nome" value="<?= e($c['nome']) ?>" required></div>
      <div class="campo"><label>Ícone</label><input type="text" name="icone" value="<?= e($c['icone'] ?? '') ?>"></div>
    </div>
    <div class="campo"><label>Texto SEO</label><textarea name="texto_seo" rows="2"><?= e($c['texto_seo'] ?? '') ?></textarea></div>
    <div class="grid-2">
      <div class="campo"><label>Ordem</label><input type="number" name="ordem" value="<?= (int) $c['ordem'] ?>"></div>
      <div class="campo" style="display:flex;align-items:end">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="ativa" value="1" <?= $c['ativa'] ? 'checked' : '' ?> style="width:auto"> Ativa</label>
      </div>
    </div>
    <button type="submit" class="btn">Salvar</button>
  </form>
</div>
<?php endforeach; ?>
