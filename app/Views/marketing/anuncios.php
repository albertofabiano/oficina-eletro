<?php
/**
 * Anúncios de UMA campanha — busca ao vivo na API (ver MarketingController::anuncios()),
 * não sincronizado/guardado localmente. "Grupo de anúncios" do Google Ads aparece só como uma
 * coluna de referência — o cliente não precisa entender esse conceito pra usar a tela.
 */
use App\Services\Marketing\Money;
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-anuncios-wrap{max-width:1000px}
.fx-mkt-voltar{color:var(--text-3);text-decoration:none;font-size:13px}
.fx-mkt-anuncios-titulo{font-size:18px;font-weight:700;color:var(--text-1);margin:.4rem 0 1rem}
.fx-mkt-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:16px;margin-bottom:1rem}
.fx-mkt-table{width:100%;font-size:13px;border-collapse:collapse}
.fx-mkt-table th{text-align:left;color:var(--text-3);font-weight:600;font-size:11.5px;padding:6px 10px;border-bottom:1px solid var(--border)}
.fx-mkt-table td{padding:8px 10px;border-bottom:1px solid var(--border);color:var(--text-1)}
.fx-mkt-table td.preview{max-width:320px}
.fx-mkt-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.fx-mkt-status.active{background:#dcfce7;color:#166534}
.fx-mkt-status.paused{background:#f1f5f9;color:#475569}
.fx-mkt-status.archived{background:#f1f5f9;color:#94a3b8}
.fx-mkt-icon-btn{border:1px solid var(--border);background:var(--surface-1);color:var(--text-2);border-radius:6px;padding:3px 7px;cursor:pointer;font-size:12px;line-height:1}
.fx-mkt-icon-btn:hover:not(:disabled){background:var(--surface-2)}
.fx-mkt-icon-btn:disabled{opacity:.5;cursor:not-allowed}
.fx-mkt-period a{padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-2);text-decoration:none;border:1px solid var(--border)}
.fx-mkt-period a.ativo{background:var(--accent);color:#fff;border-color:var(--accent)}
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

  <div class="fx-mkt-card">
    <table class="fx-mkt-table">
      <thead><tr><th>Anúncio</th><th>Grupo</th><th>Status</th><th>Cliques</th><th>Impressões</th><th>Leads</th><th>Investimento</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($anuncios as $a): ?>
        <tr data-anuncio-resource="<?= e($a['resource_name']) ?>">
          <td class="preview" title="<?= e($a['preview']) ?>"><?= e($a['preview']) ?></td>
          <td><?= e($a['ad_group_name']) ?></td>
          <td><span class="fx-mkt-status <?= e($a['status']) ?>" data-anuncio-status-texto><?= e(['active'=>'Ativo','paused'=>'Pausado','archived'=>'Arquivado'][$a['status']] ?? $a['status']) ?></span></td>
          <td><?= (int) $a['clicks'] ?></td>
          <td><?= (int) $a['impressions'] ?></td>
          <td><?= (int) $a['leads'] ?></td>
          <td><?= e(Money::formatCents($a['spend_cents'])) ?></td>
          <td>
            <?php if ($a['status'] === 'active'): ?>
            <button type="button" class="fx-mkt-icon-btn" title="Pausar anúncio" onclick="mktAnuncioStatus(this,'paused')"><i class="bi bi-pause-fill"></i></button>
            <?php elseif ($a['status'] === 'paused'): ?>
            <button type="button" class="fx-mkt-icon-btn" title="Reativar anúncio" onclick="mktAnuncioStatus(this,'active')"><i class="bi bi-play-fill"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$anuncios): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-3);padding:1.2rem">Nenhum anúncio no período.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function mktAnuncioStatus(btn, novoStatus) {
  var confirmMsg = novoStatus === 'paused' ? 'Pausar este anúncio de verdade no Google Ads?' : 'Reativar este anúncio de verdade no Google Ads?';
  if (!confirm(confirmMsg)) return;
  var tr = btn.closest('tr');
  var resourceName = tr.getAttribute('data-anuncio-resource');
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
