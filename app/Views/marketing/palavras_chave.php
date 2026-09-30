<?php
/**
 * Palavras-chave de UMA campanha — busca ao vivo na API (ver MarketingController::palavrasChave()),
 * não sincronizado/guardado localmente, mesmo padrão de app/Views/marketing/anuncios.php.
 *
 * Duas listas bem separadas, de propósito, porque funcionam de formas diferentes:
 * - "Palavras-chave" (positivas) fazem a campanha APARECER pra uma busca — vivem dentro de um
 *   "grupo de anúncios" (conceito que o resto do painel esconde; aqui só aparece como um detalhe
 *   resolvido sozinho quando há 1 grupo só, ou um <select> simples quando há mais de um).
 * - "Palavras-chave negativas" fazem a campanha NUNCA aparecer pra uma busca — entram direto na
 *   campanha inteira, sem grupo nenhum pra escolher, e não têm métrica (não geram clique/gasto).
 */
use App\Services\Marketing\Money;

$rotuloMatch = [
    'BROAD'  => 'Ampla',
    'PHRASE' => 'Frase',
    'EXACT'  => 'Exata',
    'UNKNOWN' => 'Desconhecida',
];
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-pk-wrap{max-width:1000px}
.fx-mkt-voltar{color:var(--text-3);text-decoration:none;font-size:13px}
.fx-mkt-pk-titulo{font-size:18px;font-weight:700;color:var(--text-1);margin:.4rem 0 1.2rem}
.fx-mkt-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:16px;margin-bottom:1rem}
.fx-mkt-pk-secao-titulo{font-size:15px;font-weight:700;color:var(--text-1);margin:0 0 .2rem}
.fx-mkt-pk-secao-sub{font-size:12.5px;color:var(--text-3);margin:0 0 .9rem}
.fx-mkt-pk-form{display:flex;flex-wrap:wrap;gap:.7rem;align-items:flex-end;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border)}
.fx-mkt-pk-form textarea{flex:1 1 320px;min-width:260px;min-height:74px;resize:vertical;border:1px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--surface-1);color:var(--text-1)}
.fx-mkt-pk-campo{display:flex;flex-direction:column;gap:.25rem}
.fx-mkt-pk-campo label{font-size:11px;font-weight:600;color:var(--text-3)}
.fx-mkt-pk-campo select{border:1px solid var(--border);border-radius:8px;padding:7px 8px;font-size:13px;background:var(--surface-1);color:var(--text-1);min-width:190px}
.fx-mkt-pk-btn{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.fx-mkt-pk-btn:disabled{opacity:.5;cursor:not-allowed}
.fx-mkt-pk-aviso{font-size:12.5px;color:var(--text-3);margin:0}
.fx-mkt-table{width:100%;font-size:13px;border-collapse:collapse}
.fx-mkt-table th{text-align:left;color:var(--text-3);font-weight:600;font-size:11.5px;padding:6px 10px;border-bottom:1px solid var(--border)}
.fx-mkt-table td{padding:8px 10px;border-bottom:1px solid var(--border);color:var(--text-1)}
.fx-mkt-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.fx-mkt-status.active{background:#dcfce7;color:#166534}
.fx-mkt-status.paused{background:#f1f5f9;color:#475569}
.fx-mkt-status.archived{background:#f1f5f9;color:#94a3b8}
.fx-mkt-pk-match{font-size:11px;font-weight:600;color:var(--text-2);background:var(--surface-2);border-radius:6px;padding:2px 7px}
.fx-mkt-icon-btn{border:1px solid var(--border);background:var(--surface-1);color:var(--text-2);border-radius:6px;padding:3px 7px;cursor:pointer;font-size:12px;line-height:1}
.fx-mkt-icon-btn:hover:not(:disabled){background:var(--surface-2)}
.fx-mkt-icon-btn:disabled{opacity:.5;cursor:not-allowed}
.fx-mkt-period a{padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-2);text-decoration:none;border:1px solid var(--border)}
.fx-mkt-period a.ativo{background:var(--accent);color:#fff;border-color:var(--accent)}
</style>

<div class="fx-mkt-pk-wrap">
  <a href="<?= url('/marketing') ?>" class="fx-mkt-voltar">← Voltar pro painel</a>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:.6rem">
    <h1 class="fx-mkt-pk-titulo">Palavras-chave — <?= e($campanhaNome) ?></h1>
    <div class="fx-mkt-period">
      <?php foreach ([7, 14, 30] as $opt): ?>
        <a href="<?= url('/marketing/campanhas/' . $campanhaId . '/palavras-chave?dias=' . $opt) ?>" class="<?= $dias === $opt ? 'ativo' : '' ?>"><?= $opt ?> dias</a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($erro): ?>
  <div class="fx-mkt-card" style="border-color:#dc2626;background:#fee2e2;color:#991b1b"><?= e($erro) ?></div>
  <?php endif; ?>

  <div class="fx-mkt-card">
    <p class="fx-mkt-pk-secao-titulo">Palavras-chave</p>
    <p class="fx-mkt-pk-secao-sub">Termos que fazem esta campanha aparecer numa busca do Google. Métricas do período selecionado acima.</p>

    <form class="fx-mkt-pk-form" onsubmit="return mktKwAdicionar(event,this)" data-endpoint="<?= url('/marketing/campanhas/' . $campanhaId . '/palavras-chave') ?>">
      <textarea name="texto" placeholder="Uma palavra-chave por linha, ex.:&#10;conserto de tv&#10;assistência técnica&#10;conserto de celular" <?= $gruposAnuncio ? '' : 'disabled' ?>></textarea>
      <?php if (count($gruposAnuncio) > 1): ?>
      <div class="fx-mkt-pk-campo">
        <label>Grupo de anúncios</label>
        <select name="ad_group_resource_name">
          <?php foreach ($gruposAnuncio as $g): ?>
          <option value="<?= e($g['resource_name']) ?>"><?= e($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php elseif (count($gruposAnuncio) === 1): ?>
      <input type="hidden" name="ad_group_resource_name" value="<?= e($gruposAnuncio[0]['resource_name']) ?>">
      <?php endif; ?>
      <div class="fx-mkt-pk-campo">
        <label>Tipo de correspondência</label>
        <select name="match_type">
          <option value="BROAD">Ampla — mostra pra buscas relacionadas</option>
          <option value="PHRASE" selected>Frase — a expressão precisa aparecer na busca</option>
          <option value="EXACT">Exata — só pra essa busca, do jeito exato</option>
        </select>
      </div>
      <button type="submit" class="fx-mkt-pk-btn" <?= $gruposAnuncio ? '' : 'disabled' ?>>Adicionar</button>
    </form>
    <?php if (!$gruposAnuncio && !$erro): ?>
    <p class="fx-mkt-pk-aviso">Esta campanha ainda não tem nenhum grupo de anúncios pra receber uma palavra-chave nova.</p>
    <?php endif; ?>

    <table class="fx-mkt-table">
      <thead><tr><th>Palavra-chave</th><th>Correspondência</th><th>Grupo</th><th>Status</th><th>Cliques</th><th>Impressões</th><th>Leads</th><th>Investimento</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($palavras as $p): ?>
        <tr data-kw-resource="<?= e($p['resource_name']) ?>">
          <td><?= e($p['text']) ?></td>
          <td><span class="fx-mkt-pk-match"><?= e($rotuloMatch[$p['match_type']] ?? $p['match_type']) ?></span></td>
          <td><?= e($p['ad_group_name']) ?></td>
          <td><span class="fx-mkt-status <?= e($p['status']) ?>"><?= e(['active'=>'Ativa','paused'=>'Pausada','archived'=>'Removida'][$p['status']] ?? $p['status']) ?></span></td>
          <td><?= (int) $p['clicks'] ?></td>
          <td><?= (int) $p['impressions'] ?></td>
          <td><?= (int) $p['leads'] ?></td>
          <td><?= e(Money::formatCents($p['spend_cents'])) ?></td>
          <td><button type="button" class="fx-mkt-icon-btn" title="Remover palavra-chave" onclick="mktKwRemover(this,'<?= url('/marketing/campanhas/' . $campanhaId . '/palavras-chave/remover') ?>')"><i class="bi bi-trash3"></i></button></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$palavras): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--text-3);padding:1rem">Nenhuma palavra-chave cadastrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="fx-mkt-card">
    <p class="fx-mkt-pk-secao-titulo">Palavras-chave negativas</p>
    <p class="fx-mkt-pk-secao-sub">Termos que bloqueiam a campanha inteira de aparecer numa busca — não geram clique nem gasto, então não têm métrica.</p>

    <form class="fx-mkt-pk-form" onsubmit="return mktKwAdicionar(event,this)" data-endpoint="<?= url('/marketing/campanhas/' . $campanhaId . '/palavras-negativas') ?>">
      <textarea name="texto" placeholder="Uma palavra-chave por linha, ex.:&#10;grátis&#10;curso&#10;emprego"></textarea>
      <div class="fx-mkt-pk-campo">
        <label>Tipo de correspondência</label>
        <select name="match_type">
          <option value="BROAD" selected>Ampla — bloqueia qualquer busca com essas palavras</option>
          <option value="PHRASE">Frase — bloqueia só quando a expressão aparece</option>
          <option value="EXACT">Exata — bloqueia só essa busca, do jeito exato</option>
        </select>
      </div>
      <button type="submit" class="fx-mkt-pk-btn">Adicionar às negativas</button>
    </form>

    <table class="fx-mkt-table">
      <thead><tr><th>Palavra-chave</th><th>Correspondência</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($negativas as $n): ?>
        <tr data-kw-resource="<?= e($n['resource_name']) ?>">
          <td><?= e($n['text']) ?></td>
          <td><span class="fx-mkt-pk-match"><?= e($rotuloMatch[$n['match_type']] ?? $n['match_type']) ?></span></td>
          <td><button type="button" class="fx-mkt-icon-btn" title="Remover palavra-chave negativa" onclick="mktKwRemover(this,'<?= url('/marketing/campanhas/' . $campanhaId . '/palavras-negativas/remover') ?>')"><i class="bi bi-trash3"></i></button></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$negativas): ?>
        <tr><td colspan="3" style="text-align:center;color:var(--text-3);padding:1rem">Nenhuma palavra-chave negativa cadastrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function mktKwAdicionar(ev, form) {
  ev.preventDefault();
  var endpoint = form.getAttribute('data-endpoint');
  var fd = new FormData(form);
  var btn = form.querySelector('button[type="submit"]');
  btn.disabled = true;
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({
      texto: fd.get('texto') || '',
      match_type: fd.get('match_type') || '',
      ad_group_resource_name: fd.get('ad_group_resource_name') || '',
    }),
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (j.sucesso) { location.reload(); return; }
      alert(j.erro || 'Não foi possível cadastrar a palavra-chave.');
      btn.disabled = false;
    })
    .catch(function () {
      alert('Falha de rede ao cadastrar a palavra-chave.');
      btn.disabled = false;
    });
  return false;
}

function mktKwRemover(btn, endpoint) {
  if (!confirm('Remover esta palavra-chave de verdade no Google Ads?')) return;
  var row = btn.closest('[data-kw-resource]');
  var resourceName = row.getAttribute('data-kw-resource');
  btn.disabled = true;
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({ resource_name: resourceName }),
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (j.sucesso) { location.reload(); return; }
      alert(j.erro || 'Não foi possível remover a palavra-chave.');
      btn.disabled = false;
    })
    .catch(function () {
      alert('Falha de rede ao remover a palavra-chave.');
      btn.disabled = false;
    });
}
</script>
</div>
