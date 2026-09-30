<?php
/**
 * Anúncios de UMA campanha — busca ao vivo na API (ver MarketingController::anuncios()),
 * não sincronizado/guardado localmente. "Grupo de anúncios" do Google Ads aparece só como uma
 * legenda de referência — o cliente não precisa entender esse conceito pra usar a tela.
 *
 * Mostra o anúncio como uma PRÉVIA VISUAL (título · descrição · URL, no estilo de um anúncio de
 * pesquisa de verdade) em vez de só o texto cru — pedido explícito do usuário ("consegue exibir
 * o anúncio em si, o anúncio visual com os textos"). Um Anúncio de Pesquisa Responsivo tem várias
 * variações de título/descrição (o Google combina dinamicamente qual aparece pro usuário final
 * de cada vez) — mostramos as 3 primeiras headlines e as 2 primeiras descrições como uma prévia
 * representativa, não a combinação exata que apareceu numa busca específica (isso a API não
 * expõe).
 */
use App\Services\Marketing\Money;

$urlCurta = function (?string $url): ?string {
    if ($url === null || $url === '') return null;
    $host = parse_url($url, PHP_URL_HOST);
    return $host !== null ? preg_replace('/^www\./', '', $host) : $url;
};
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-anuncios-wrap{max-width:900px}
.fx-mkt-voltar{color:var(--text-3);text-decoration:none;font-size:13px}
.fx-mkt-anuncios-titulo{font-size:18px;font-weight:700;color:var(--text-1);margin:.4rem 0 1rem}
.fx-mkt-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:16px;margin-bottom:1rem}
.fx-mkt-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.fx-mkt-status.active{background:#dcfce7;color:#166534}
.fx-mkt-status.paused{background:#f1f5f9;color:#475569}
.fx-mkt-status.archived{background:#f1f5f9;color:#94a3b8}
.fx-mkt-icon-btn{border:1px solid var(--border);background:var(--surface-1);color:var(--text-2);border-radius:6px;padding:3px 7px;cursor:pointer;font-size:12px;line-height:1}
.fx-mkt-icon-btn:hover:not(:disabled){background:var(--surface-2)}
.fx-mkt-icon-btn:disabled{opacity:.5;cursor:not-allowed}
.fx-mkt-period a{padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-2);text-decoration:none;border:1px solid var(--border)}
.fx-mkt-period a.ativo{background:var(--accent);color:#fff;border-color:var(--accent)}

/* Prévia visual do anúncio, no estilo de um resultado de busca patrocinado real */
.fx-mkt-anuncio-card{display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap}
.fx-mkt-anuncio-preview{flex:1 1 380px;min-width:280px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px}
.fx-mkt-anuncio-badge{display:inline-block;font-size:11px;font-weight:700;color:#202124;background:#f1f3f4;border-radius:3px;padding:1px 5px;margin-right:6px;vertical-align:middle}
.fx-mkt-anuncio-url{font-size:12.5px;color:#202124;display:inline-block;vertical-align:middle}
.fx-mkt-anuncio-titulo{color:#1a0dab;font-size:17px;line-height:1.3;margin:.3rem 0 .2rem;font-weight:400}
.fx-mkt-anuncio-desc{color:#4d5156;font-size:13.5px;line-height:1.4}
.fx-mkt-anuncio-sem-preview{color:var(--text-3);font-size:13px;font-style:italic}
.fx-mkt-anuncio-meta{flex:1 1 260px;min-width:220px}
.fx-mkt-anuncio-grupo{font-size:11.5px;color:var(--text-3);margin-bottom:.5rem}
.fx-mkt-anuncio-metricas{display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;margin-bottom:.7rem}
.fx-mkt-anuncio-metrica-label{font-size:10.5px;color:var(--text-3);font-weight:600}
.fx-mkt-anuncio-metrica-valor{font-size:14px;color:var(--text-1);font-weight:700}
</style>

<div class="fx-mkt-anuncios-wrap">
  <a href="<?= url('/marketing') ?>" class="fx-mkt-voltar">← Voltar pro painel</a>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:.6rem">
    <h1 class="fx-mkt-anuncios-titulo">Anúncios — <?= e($campanhaNome) ?></h1>
    <div class="fx-mkt-period">
      <?php foreach ([7, 14, 30] as $opt): ?>
        <a href="<?= url('/marketing/campanhas/' . $campanhaId . '/anuncios?dias=' . $opt) ?>" class="<?= $dias === $opt ? 'ativo' : '' ?>"><?= $opt ?> dias</a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($erro): ?>
  <div class="fx-mkt-card" style="border-color:#dc2626;background:#fee2e2;color:#991b1b"><?= e($erro) ?></div>
  <?php endif; ?>

  <?php foreach ($anuncios as $a): $temRsa = !empty($a['headlines']); $host = $urlCurta($a['final_url']); ?>
  <div class="fx-mkt-card fx-mkt-anuncio-card" data-anuncio-resource="<?= e($a['resource_name']) ?>">
    <div class="fx-mkt-anuncio-preview">
      <?php if ($temRsa): ?>
        <span class="fx-mkt-anuncio-badge">Anúncio</span>
        <?php if ($host): ?><span class="fx-mkt-anuncio-url"><?= e($host) ?></span><?php endif; ?>
        <div class="fx-mkt-anuncio-titulo"><?= e(implode(' · ', array_slice($a['headlines'], 0, 3))) ?></div>
        <?php if ($a['descriptions']): ?>
        <div class="fx-mkt-anuncio-desc"><?= e(implode(' ', array_slice($a['descriptions'], 0, 2))) ?></div>
        <?php endif; ?>
      <?php else: ?>
        <div class="fx-mkt-anuncio-sem-preview"><?= e($a['preview']) ?> — prévia de texto não disponível pra este tipo de anúncio (<?= e($a['ad_type']) ?>).</div>
      <?php endif; ?>
    </div>
    <div class="fx-mkt-anuncio-meta">
      <div class="fx-mkt-anuncio-grupo">Grupo: <?= e($a['ad_group_name']) ?></div>
      <span class="fx-mkt-status <?= e($a['status']) ?>" data-anuncio-status-texto><?= e(['active'=>'Ativo','paused'=>'Pausado','archived'=>'Arquivado'][$a['status']] ?? $a['status']) ?></span>
      <div class="fx-mkt-anuncio-metricas" style="margin-top:.6rem">
        <div><div class="fx-mkt-anuncio-metrica-label">Cliques</div><div class="fx-mkt-anuncio-metrica-valor"><?= (int) $a['clicks'] ?></div></div>
        <div><div class="fx-mkt-anuncio-metrica-label">Impressões</div><div class="fx-mkt-anuncio-metrica-valor"><?= (int) $a['impressions'] ?></div></div>
        <div><div class="fx-mkt-anuncio-metrica-label">Leads</div><div class="fx-mkt-anuncio-metrica-valor"><?= (int) $a['leads'] ?></div></div>
        <div><div class="fx-mkt-anuncio-metrica-label">Investimento</div><div class="fx-mkt-anuncio-metrica-valor"><?= e(Money::formatCents($a['spend_cents'])) ?></div></div>
      </div>
      <?php if ($a['status'] === 'active'): ?>
      <button type="button" class="fx-mkt-icon-btn" title="Pausar anúncio" onclick="mktAnuncioStatus(this,'paused')"><i class="bi bi-pause-fill"></i> Pausar</button>
      <?php elseif ($a['status'] === 'paused'): ?>
      <button type="button" class="fx-mkt-icon-btn" title="Reativar anúncio" onclick="mktAnuncioStatus(this,'active')"><i class="bi bi-play-fill"></i> Reativar</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$anuncios): ?>
  <div class="fx-mkt-card" style="text-align:center;color:var(--text-3)">Nenhum anúncio no período.</div>
  <?php endif; ?>
</div>

<script>
function mktAnuncioStatus(btn, novoStatus) {
  var confirmMsg = novoStatus === 'paused' ? 'Pausar este anúncio de verdade no Google Ads?' : 'Reativar este anúncio de verdade no Google Ads?';
  if (!confirm(confirmMsg)) return;
  var card = btn.closest('[data-anuncio-resource]');
  var resourceName = card.getAttribute('data-anuncio-resource');
  btn.disabled = true;
  fetch('<?= url('/marketing/campanhas/' . $campanhaId . '/anuncios/status') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({ status: novoStatus, resource_name: resourceName }),
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (j.sucesso) { location.reload(); return; }
      alert(j.erro || 'Não foi possível atualizar o anúncio.');
      btn.disabled = false;
    })
    .catch(function () {
      alert('Falha de rede ao atualizar o anúncio.');
      btn.disabled = false;
    });
}
</script>
</div>
