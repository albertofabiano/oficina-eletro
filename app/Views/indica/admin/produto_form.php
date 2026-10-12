<?php
$editando = $produto !== null;
$acao = $editando ? ('/admin/produtos/' . $produto['id'] . '/atualizar') : '/admin/produtos';
$rotulosSelo = ['frete_gratis' => 'Frete grátis', 'mais_vendido' => 'Mais vendido', 'oferta_do_dia' => 'Oferta do dia'];
$selosAtuais = $editando ? ($produto['selos'] ?? []) : [];
?>
<div class="topo"><h1><?= $editando ? 'Editar produto' : 'Novo produto' ?></h1></div>

<form method="POST" action="<?= e($acao) ?>">
  <?= csrf_field() ?>

  <div class="card">
    <h3 style="margin-top:0">Identificação</h3>
    <div class="grid-2">
      <div class="campo"><label>Nome</label><input type="text" id="campoNome" name="nome" value="<?= e($produto['nome'] ?? '') ?>" required></div>
      <div class="campo">
        <label>Categoria</label>
        <select name="categoria_id">
          <option value="">— Sem categoria —</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= ($editando && (int) ($produto['categoria_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="campo"><label>Especificação curta (aparece no card)</label><input type="text" name="especificacao_curta" value="<?= e($produto['especificacao_curta'] ?? '') ?>" maxlength="200"></div>
    <div class="campo"><label>Descrição completa</label><textarea name="descricao" rows="5"><?= e($produto['descricao'] ?? '') ?></textarea></div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Preço e disponibilidade</h3>
    <div class="grid-2">
      <div class="campo"><label>Preço (R$)</label><input type="text" id="campoPreco" name="preco" value="<?= $editando && $produto['preco'] !== null ? number_format((float) $produto['preco'], 2, ',', '.') : '' ?>" placeholder="199,90"></div>
      <div class="campo" style="display:flex;align-items:end">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" id="campoDisponivel" name="disponivel" value="1" <?= (!$editando || $produto['disponivel']) ? 'checked' : '' ?> style="width:auto"> Disponível</label>
      </div>
    </div>
    <div class="checkbox-row" style="margin-top:6px">
      <?php foreach ($rotulosSelo as $slug => $rotulo): ?>
        <label style="display:flex;align-items:center;gap:6px;font-weight:400;color:var(--texto);margin-right:16px">
          <input type="checkbox" name="selos[]" value="<?= e($slug) ?>" <?= in_array($slug, $selosAtuais, true) ? 'checked' : '' ?> style="width:auto"> <?= e($rotulo) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Links e imagem</h3>
    <div class="campo">
      <label>Link do anúncio no Mercado Livre</label>
      <div style="display:flex;gap:8px">
        <input type="url" id="campoLinkMl" name="link_ml" value="<?= e($produto['link_ml'] ?? '') ?>" placeholder="https://produto.mercadolivre.com.br/...">
        <?php if ($mlConectado ?? false): ?>
          <button type="button" class="btn btn-secundario btn-sm" id="btnBuscarMl" onclick="indicaBuscarMl()" style="white-space:nowrap">Buscar dados do anúncio</button>
        <?php endif; ?>
      </div>
      <?php if (!($mlConectado ?? false)): ?>
        <div style="color:var(--texto-sec);font-size:.78rem;margin-top:4px">
          Conecte o <a href="/admin/config">Mercado Livre em Configurações</a> pra preencher nome/preço/foto automaticamente a partir desse link.
        </div>
      <?php endif; ?>
      <div id="buscarMlMsg" style="font-size:.82rem;margin-top:6px"></div>
    </div>
    <div class="campo">
      <label>Link de afiliado (o que vai no botão "Ver no Mercado Livre")</label>
      <input type="text" name="link_afiliado" value="<?= e($produto['link_afiliado'] ?? '') ?>" placeholder="https://mercadolivre.com/sec/...">
      <div style="color:var(--texto-sec);font-size:.78rem;margin-top:4px">Gerado manualmente no painel de afiliados do Mercado Livre — nunca automatize isso com ferramenta não-oficial.</div>
    </div>
    <div class="campo"><label>URL da imagem</label><input type="url" id="campoImagemUrl" name="imagem_url" value="<?= e($produto['imagem_url'] ?? '') ?>" placeholder="https://..."></div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">SEO e destaque</h3>
    <div class="campo"><label>Meta title (opcional, até 70 caracteres)</label><input type="text" name="meta_title" value="<?= e($produto['meta_title'] ?? '') ?>" maxlength="70"></div>
    <div class="campo"><label>Meta description (opcional, até 160 caracteres)</label><textarea name="meta_description" rows="2" maxlength="160"><?= e($produto['meta_description'] ?? '') ?></textarea></div>
    <div class="checkbox-row">
      <label style="display:flex;align-items:center;gap:6px;font-weight:400;color:var(--texto)"><input type="checkbox" name="destaque_home" value="1" <?= (!empty($produto['destaque_home'])) ? 'checked' : '' ?> style="width:auto"> Mostrar em destaque na home</label>
    </div>
    <div class="checkbox-row">
      <label style="display:flex;align-items:center;gap:6px;font-weight:400;color:var(--texto)"><input type="checkbox" name="vendedor_parceiro" value="1" <?= (!empty($produto['vendedor_parceiro'])) ? 'checked' : '' ?> style="width:auto"> Vendedor parceiro (comissão extra)</label>
    </div>
    <div class="checkbox-row">
      <label style="display:flex;align-items:center;gap:6px;font-weight:400;color:var(--texto)"><input type="checkbox" name="ativo" value="1" <?= (!$editando || $produto['ativo']) ? 'checked' : '' ?> style="width:auto"> Ativo (visível no site)</label>
    </div>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Criar produto' ?></button>
    <a href="/admin/produtos" class="btn btn-secundario">Cancelar</a>
  </div>
</form>

<script>
function indicaBuscarMl() {
  var url = document.getElementById('campoLinkMl').value.trim();
  var msg = document.getElementById('buscarMlMsg');
  var btn = document.getElementById('btnBuscarMl');
  if (!url) { msg.style.color = 'var(--vermelho)'; msg.textContent = 'Cole o link do anúncio primeiro.'; return; }

  btn.disabled = true;
  var textoOriginal = btn.textContent;
  btn.textContent = 'Buscando...';
  msg.style.color = 'var(--texto-sec)';
  msg.textContent = '';

  fetch('/admin/produtos/buscar-ml?url=' + encodeURIComponent(url))
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
    .then(function (res) {
      btn.disabled = false;
      btn.textContent = textoOriginal;
      if (!res.ok) {
        msg.style.color = 'var(--vermelho)';
        msg.textContent = res.body.erro || 'Não foi possível buscar os dados do anúncio.';
        return;
      }
      var d = res.body;
      // Só preenche nome se o campo ainda estiver vazio — nunca sobrescreve um nome já digitado/editado sem avisar.
      var campoNome = document.getElementById('campoNome');
      if (d.titulo && !campoNome.value.trim()) campoNome.value = d.titulo;
      if (d.preco !== null && d.preco !== undefined) {
        document.getElementById('campoPreco').value = Number(d.preco).toFixed(2).replace('.', ',');
      }
      if (typeof d.disponivel === 'boolean') document.getElementById('campoDisponivel').checked = d.disponivel;
      if (d.imagem_url) document.getElementById('campoImagemUrl').value = d.imagem_url;

      if (d.parcial) {
        // Link de página de catálogo (vários vendedores) — nome/foto vieram reais, mas o
        // Mercado Livre não expõe preço/disponibilidade de vendedor nenhum por essa via.
        msg.style.color = '#fbbf24';
        msg.textContent = 'Nome e foto preenchidos — esse link é de uma página de catálogo (vários vendedores), então preço e disponibilidade não vêm automáticos. Confira no próprio anúncio e preencha manualmente.';
      } else {
        msg.style.color = '#4ade80';
        msg.textContent = 'Dados encontrados e preenchidos — confira antes de salvar.';
      }
    })
    .catch(function () {
      btn.disabled = false;
      btn.textContent = textoOriginal;
      msg.style.color = 'var(--vermelho)';
      msg.textContent = 'Erro de conexão ao buscar o anúncio.';
    });
}
</script>
