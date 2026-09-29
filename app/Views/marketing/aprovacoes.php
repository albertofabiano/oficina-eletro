<?php
/**
 * Marketing → Aprovações (Etapa 3) — sugestões geradas pelas regras de otimização
 * (App\Services\Marketing\Rules) esperando decisão humana, + histórico do que já foi decidido/
 * executado. Nenhum botão aqui chama a plataforma de anúncio diretamente — tudo passa por
 * App\Services\Marketing\QueueService::aprovar()/rejeitar()/executarAprovados().
 */
use App\Services\Marketing\Money;

function mktTempoAtras(string $datetime): string
{
    $diff = max(0, time() - strtotime($datetime));
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return (int) floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return (int) floor($diff / 3600) . 'h atrás';
    return (int) floor($diff / 86400) . ' dia(s) atrás';
}

function mktRotuloAcao(string $tipo, ?string $payloadJson): string
{
    $p = $payloadJson ? json_decode($payloadJson, true) : null;
    return match ($tipo) {
        'pause_campaign'  => 'Pausar campanha',
        'resume_campaign' => 'Retomar campanha',
        'update_daily_budget' => isset($p['daily_budget_cents'], $p['previous_daily_budget_cents'])
            ? sprintf('Ajustar orçamento: %s → %s', Money::formatCents((int) $p['previous_daily_budget_cents']), Money::formatCents((int) $p['daily_budget_cents']))
            : 'Ajustar orçamento',
        default => $tipo,
    };
}

function mktRotuloStatus(string $status): array
{
    return match ($status) {
        'approved' => ['Aprovado', '#0d6efd'],
        'rejected' => ['Rejeitado', '#6c757d'],
        'executed' => ['Executado', '#16a34a'],
        'failed'   => ['Falhou', '#dc2626'],
        default    => [$status, '#6c757d'],
    };
}
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.1rem}
.fx-mkt-title{font-size:18px;font-weight:700;color:var(--text-1);margin:0}
.fx-mkt-badge{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--surface-2);color:var(--text-3);border:1px solid var(--border)}
.fx-mkt-badge.simulacao{color:#92400e;background:#fef3c7;border-color:#fde68a}
.fx-mkt-voltar{font-size:13px;color:var(--text-2);text-decoration:none}
.fx-mkt-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:16px;margin-bottom:1rem}
.fx-mkt-card h2{font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 .8rem}
.fx-apr-item{border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:.7rem;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}
.fx-apr-item:last-child{margin-bottom:0}
.fx-apr-acao{font-weight:700;color:var(--text-1);font-size:13.5px}
.fx-apr-campanha{font-size:12.5px;color:var(--text-3);margin-top:2px}
.fx-apr-motivo{font-size:12.5px;color:var(--text-2);margin-top:6px;max-width:520px}
.fx-apr-quando{font-size:11px;color:var(--text-3);margin-top:6px}
.fx-apr-botoes{display:flex;gap:.5rem;flex-shrink:0}
.fx-apr-btn{font-size:12.5px;font-weight:600;padding:6px 14px;border-radius:8px;border:1px solid var(--border);cursor:pointer;background:var(--surface-1);color:var(--text-2)}
.fx-apr-btn.aprovar{background:#16a34a;color:#fff;border-color:#16a34a}
.fx-apr-btn.rejeitar{background:var(--surface-1);color:#dc2626;border-color:#dc2626}
.fx-apr-btn:disabled{opacity:.55;cursor:not-allowed}
.fx-mkt-table{width:100%;font-size:12.8px;border-collapse:collapse}
.fx-mkt-table th{text-align:left;color:var(--text-3);font-weight:600;font-size:11.5px;padding:6px 10px;border-bottom:1px solid var(--border)}
.fx-mkt-table td{padding:8px 10px;border-bottom:1px solid var(--border);color:var(--text-1);vertical-align:top}
.fx-hist-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;color:#fff}
</style>

<div class="fx-mkt-head">
  <div>
    <h1 class="fx-mkt-title">Marketing — Aprovações</h1>
    <div style="margin-top:.4rem">
      <?php if ($dryRun): ?><span class="fx-mkt-badge simulacao">Modo simulação</span><?php endif; ?>
    </div>
  </div>
  <a href="<?= url('/marketing') ?>" class="fx-mkt-voltar">← Voltar pro painel</a>
</div>

<div class="fx-mkt-card">
  <h2>Pendentes (<?= count($pendentes) ?>)</h2>
  <?php if (!$pendentes): ?>
    <p style="color:var(--text-3);font-size:13px;margin:0">Nenhuma sugestão esperando decisão no momento.</p>
  <?php endif; ?>
  <?php foreach ($pendentes as $p): ?>
    <div class="fx-apr-item" id="apr-<?= (int) $p['id'] ?>">
      <div>
        <div class="fx-apr-acao"><?= e(mktRotuloAcao($p['action_type'], $p['payload'])) ?></div>
        <div class="fx-apr-campanha"><?= e($p['campaign_name']) ?></div>
        <div class="fx-apr-motivo"><?= e($p['reason']) ?></div>
        <div class="fx-apr-quando">Sugerida <?= mktTempoAtras($p['requested_at']) ?></div>
      </div>
      <div class="fx-apr-botoes">
        <button type="button" class="fx-apr-btn aprovar" onclick="mktDecidir(<?= (int) $p['id'] ?>, 'aprovar')">✓ Aprovar</button>
        <button type="button" class="fx-apr-btn rejeitar" onclick="mktDecidir(<?= (int) $p['id'] ?>, 'rejeitar')">✕ Rejeitar</button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="fx-mkt-card">
  <h2>Histórico</h2>
  <table class="fx-mkt-table">
    <thead><tr><th>Ação</th><th>Campanha</th><th>Status</th><th>Decidido por</th><th>Quando</th></tr></thead>
    <tbody>
      <?php foreach ($historico as $h): [$rotulo, $cor] = mktRotuloStatus($h['status']); ?>
      <tr>
        <td><?= e(mktRotuloAcao($h['action_type'], $h['payload'])) ?></td>
        <td><?= e($h['campaign_name']) ?></td>
        <td>
          <span class="fx-hist-status" style="background:<?= $cor ?>"><?= e($rotulo) ?></span>
          <?php if ($h['status'] === 'executed' && !empty($h['dry_run'])): ?><span class="fx-mkt-badge simulacao" style="margin-left:4px">simulação</span><?php endif; ?>
          <?php if ($h['status'] === 'failed' && !empty($h['error'])): ?><div style="font-size:11px;color:var(--text-3);margin-top:3px"><?= e($h['error']) ?></div><?php endif; ?>
        </td>
        <td><?= e($h['decidido_por_nome'] ?? '—') ?></td>
        <td><?= mktTempoAtras($h['executed_at'] ?? $h['decided_at']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$historico): ?>
      <tr><td colspan="5" style="text-align:center;color:var(--text-3);padding:1rem">Nenhuma decisão registrada ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function mktDecidir(id, decisao) {
  var item = document.getElementById('apr-' + id);
  var botoes = item ? item.querySelectorAll('.fx-apr-btn') : [];
  botoes.forEach(function (b) { b.disabled = true; });

  fetch('<?= url('/marketing/aprovacoes') ?>/' + id + '/' + decisao, {
    method: 'POST',
    headers: { 'X-CSRF-Token': '<?= csrf_token() ?>' },
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.sucesso) {
        alert(j.erro || 'Não foi possível concluir.');
        botoes.forEach(function (b) { b.disabled = false; });
        return;
      }
      alert(j.mensagem || 'Feito.');
      location.reload();
    })
    .catch(function () {
      alert('Falha de rede.');
      botoes.forEach(function (b) { b.disabled = false; });
    });
}
</script>
</div>
