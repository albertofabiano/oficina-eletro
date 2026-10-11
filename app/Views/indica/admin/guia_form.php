<?php
$editando = $guia !== null;
$acao = $editando ? ('/admin/guias/' . $guia['id'] . '/atualizar') : '/admin/guias';
?>
<div class="topo"><h1><?= $editando ? 'Editar guia' : 'Novo guia' ?></h1></div>

<form method="POST" action="<?= e($acao) ?>" id="formGuia" onsubmit="return guiaSincronizarBlocos()">
  <?= csrf_field() ?>

  <div class="card">
    <h3 style="margin-top:0">Guia</h3>
    <div class="campo"><label>Título</label><input type="text" name="titulo" value="<?= e($guia['titulo'] ?? '') ?>" required></div>
    <div class="grid-2">
      <div class="campo">
        <label>Status</label>
        <select name="status">
          <option value="rascunho" <?= (!$editando || $guia['status'] === 'rascunho') ? 'selected' : '' ?>>Rascunho</option>
          <option value="publicado" <?= ($editando && $guia['status'] === 'publicado') ? 'selected' : '' ?>>Publicado</option>
        </select>
      </div>
    </div>
    <div class="campo"><label>Meta title (opcional)</label><input type="text" name="meta_title" value="<?= e($guia['meta_title'] ?? '') ?>" maxlength="70"></div>
    <div class="campo"><label>Meta description (opcional)</label><textarea name="meta_description" rows="2" maxlength="160"><?= e($guia['meta_description'] ?? '') ?></textarea></div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Conteúdo — blocos</h3>
    <div id="blocosWrap"></div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
      <button type="button" class="btn btn-secundario btn-sm" onclick="guiaAdicionarBloco('titulo')">+ Título de seção</button>
      <button type="button" class="btn btn-secundario btn-sm" onclick="guiaAdicionarBloco('paragrafo')">+ Parágrafo</button>
      <button type="button" class="btn btn-secundario btn-sm" onclick="guiaAdicionarBloco('produto')">+ Card de produto</button>
      <button type="button" class="btn btn-secundario btn-sm" onclick="guiaAdicionarBloco('aviso')">+ Aviso</button>
    </div>
  </div>

  <input type="hidden" name="blocos_json" id="blocosJson">

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Criar guia' ?></button>
    <a href="/admin/guias" class="btn btn-secundario">Cancelar</a>
  </div>
</form>

<script>
var GUIA_PRODUTOS = <?= json_encode($produtos, JSON_UNESCAPED_UNICODE) ?>;
var guiaBlocos = <?= json_encode($blocos, JSON_UNESCAPED_UNICODE) ?>;

function guiaRotuloTipo(tipo) {
  return { titulo: 'Título de seção', paragrafo: 'Parágrafo', produto: 'Card de produto', aviso: 'Aviso' }[tipo] || tipo;
}

function guiaOptsProdutos(selecionadoId) {
  var html = '<option value="">— Selecione um produto —</option>';
  GUIA_PRODUTOS.forEach(function (p) {
    var sel = Number(selecionadoId) === Number(p.id) ? ' selected' : '';
    html += '<option value="' + p.id + '"' + sel + '>' + p.nome.replace(/</g, '&lt;') + '</option>';
  });
  return html;
}

function guiaRenderBlocos() {
  var wrap = document.getElementById('blocosWrap');
  wrap.innerHTML = '';
  if (guiaBlocos.length === 0) {
    wrap.innerHTML = '<p style="color:var(--texto-sec)">Nenhum bloco ainda — use os botões abaixo pra montar o conteúdo do guia.</p>';
    return;
  }
  guiaBlocos.forEach(function (b, i) {
    var div = document.createElement('div');
    div.style.border = '1px solid var(--borda)';
    div.style.borderRadius = '8px';
    div.style.padding = '12px';
    div.style.marginBottom = '10px';

    var campoHtml = '';
    if (b.tipo === 'produto') {
      campoHtml = '<select onchange="guiaBlocos[' + i + '].conteudo.produto_id = this.value ? Number(this.value) : null">' + guiaOptsProdutos(b.conteudo.produto_id) + '</select>';
    } else {
      var linhas = b.tipo === 'titulo' ? 1 : 3;
      campoHtml = '<textarea rows="' + linhas + '" oninput="guiaBlocos[' + i + '].conteudo.texto = this.value" placeholder="' + (b.tipo === 'titulo' ? 'Ex.: Verifique o cabo primeiro' : 'Texto do bloco...') + '">' + (b.conteudo.texto || '').replace(/</g, '&lt;') + '</textarea>';
    }

    div.innerHTML =
      '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">' +
        '<strong style="font-size:.82rem;color:var(--texto-sec)">' + guiaRotuloTipo(b.tipo) + '</strong>' +
        '<div style="display:flex;gap:6px">' +
          (i > 0 ? '<button type="button" class="btn btn-secundario btn-sm" onclick="guiaMoverBloco(' + i + ',-1)">↑</button>' : '') +
          (i < guiaBlocos.length - 1 ? '<button type="button" class="btn btn-secundario btn-sm" onclick="guiaMoverBloco(' + i + ',1)">↓</button>' : '') +
          '<button type="button" class="btn btn-perigo btn-sm" onclick="guiaRemoverBloco(' + i + ')">Remover</button>' +
        '</div>' +
      '</div>' + campoHtml;

    wrap.appendChild(div);
  });
}

function guiaAdicionarBloco(tipo) {
  guiaBlocos.push({ tipo: tipo, conteudo: tipo === 'produto' ? { produto_id: null } : { texto: '' } });
  guiaRenderBlocos();
}

function guiaRemoverBloco(i) {
  guiaBlocos.splice(i, 1);
  guiaRenderBlocos();
}

function guiaMoverBloco(i, delta) {
  var j = i + delta;
  if (j < 0 || j >= guiaBlocos.length) return;
  var tmp = guiaBlocos[i];
  guiaBlocos[i] = guiaBlocos[j];
  guiaBlocos[j] = tmp;
  guiaRenderBlocos();
}

function guiaSincronizarBlocos() {
  document.getElementById('blocosJson').value = JSON.stringify(guiaBlocos.map(function (b) {
    return b.tipo === 'produto' ? { tipo: 'produto', produto_id: b.conteudo.produto_id } : { tipo: b.tipo, texto: b.conteudo.texto };
  }));
  return true;
}

guiaRenderBlocos();
</script>
