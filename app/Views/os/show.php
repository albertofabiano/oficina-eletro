<?php
/* ══════════════════════════════════════════════════════════════════════
   Tela de detalhe da OS — redesenho (ver CLAUDE.md / conversa de refatoração).
   Restrições respeitadas: nenhuma mudança na lógica de status, impressão,
   envio de mensagem, cálculo de comissão ou nas outras telas (lista, wizard).
   Modais e handlers JS de status/fechar/garantia/serviço/peça/laudo/recado/
   chat continuam os mesmos — só a apresentação em volta deles mudou.
   ══════════════════════════════════════════════════════════════════════ */
$osDescartada = str_contains(mb_strtolower($os['status_nome'] ?? ''), 'descart');

$fone     = only_numbers($os['cliente_whats'] ?? $os['cliente_tel'] ?? '');
$urlAber  = url('/os/' . $os['id'] . '/imprimir');
$urlFech  = url('/os/' . $os['id'] . '/imprimir/fechamento');
$nomeCli  = $os['cliente_nome'] ?? '';
$numOs    = $os['numero'];

$concluida  = in_array($os['status_tipo'], ['concluida','entregue']);
$jaEntregue = $os['status_tipo'] === 'entregue';
$emLaudo    = ($os['status_codigo'] ?? '') === 'laudo_tecnico';
// Continua valendo depois de fechada (status vira "Fechado"/entregue) — fechada_sem_receita é
// o sinal que persiste, já que o status em si não guarda mais "veio de um cancelada".
$emSemConserto = ($os['status_tipo'] ?? '') === 'cancelada' || !empty($os['fechada_sem_receita']);
$nomeStatus  = mb_strtolower($os['status_nome'] ?? '');
// Regra "fechar sem cobrar" vale pra qualquer status do tipo cancelada (Sem Conserto, Recusado, ou
// qualquer outro que a oficina crie) — só o texto explicativo muda conforme o nome do status.
$semConserto = $emSemConserto;
$recusado    = str_contains($nomeStatus, 'recus'); // "recusado/recusada" — troca a explicação pro cliente
$labelFechar = 'Fechar ' . mb_strtolower($os['status_nome'] ?? 'sem conserto');
// Fechar OS disponível em qualquer status (regra já existente) — só some quando ENTREGUE (aí vira "Reabrir OS").
$podeFechar  = !$jaEntregue;

$svcList = $os['servicos'] ?? [];
$pcList  = $os['pecas'] ?? [];
$totalServicos = array_sum(array_column($svcList, 'valor_total'));
$totalPecas    = array_sum(array_column($pcList, 'valor_total'));
$temOrcamento  = ($totalServicos + $totalPecas) > 0;

// ── Telefone/WhatsApp: uma linha só quando o número é o mesmo ──────────
$telNorm    = only_numbers($os['cliente_tel'] ?? '');
$waNorm     = only_numbers($os['cliente_whats'] ?? '');
$telIgualWa = $telNorm !== '' && $telNorm === $waNorm;

// ── Ação primária contextual: o próprio status decide o rótulo/ação ────
$statusProntoId = 0;
foreach ($statusList as $s) { if ($s['tipo'] === 'concluida') { $statusProntoId = (int) $s['id']; break; } }
$statusCanceladaId = 0;
foreach ($statusList as $s) { if ($s['tipo'] === 'cancelada') { $statusCanceladaId = (int) $s['id']; break; } }

$acaoPrimaria = null;
$garantiaRetorno = !empty($os['os_origem_id']) && empty($os['garantia_finalizada']) && !in_array($os['status_tipo'], ['entregue','cancelada'], true);
// "Fechar OS" fica sempre disponível como ação primária, exceto nos status
// Orçamento / Em análise / Pronto — que já têm ação própria mais específica.
$statusExcecaoFechar = str_contains($nomeStatus, 'orçamento') || str_contains($nomeStatus, 'orcamento')
    || str_contains($nomeStatus, 'análise') || str_contains($nomeStatus, 'analise')
    || str_contains($nomeStatus, 'pronto');
if ($garantiaRetorno) {
    $acaoPrimaria = ['label' => 'Finalizar garantia', 'icon' => 'shield-check', 'modal' => '#modalFinalizarGarantia'];
} elseif ($podeFechar && !$statusExcecaoFechar) {
    $acaoPrimaria = ['label' => $semConserto ? $labelFechar : 'Fechar OS', 'icon' => $semConserto ? 'x-circle' : 'check-circle', 'modal' => '#modalFechar'];
} else {
    switch ($os['status_tipo']) {
        case 'aberta':
            if ($fone && $temOrcamento) $acaoPrimaria = ['label' => 'Enviar orçamento', 'icon' => 'send', 'onclick' => "enviarPdfWa('orcamento', this)"];
            break;
        case 'aguardando':
            if ($fone) $acaoPrimaria = ['label' => 'Cobrar aprovação', 'icon' => 'bell', 'onclick' => 'enviarLinkWa(this)'];
            break;
        case 'em_andamento':
            // "Em análise" ainda está em diagnóstico — marcar como pronto direto daí pula a etapa
            // de orçamento/aprovação. Os outros status em_andamento (Em Reparo etc.) continuam com o atalho.
            $emAnalise = str_contains($nomeStatus, 'análise') || str_contains($nomeStatus, 'analise');
            if ($statusProntoId && !$emAnalise) $acaoPrimaria = ['label' => 'Marcar como pronto', 'icon' => 'check2-circle', 'onclick' => 'marcarComoPronto(this)'];
            break;
        case 'concluida':
            if ($podeFechar) {
                $pago = ($os['situacao_pagamento'] ?? '') === 'pago';
                $acaoPrimaria = $pago
                    ? ['label' => 'Entregar e fechar', 'icon' => 'box-seam', 'modal' => '#modalFechar']
                    : ['label' => 'Receber',            'icon' => 'cash-coin', 'modal' => '#modalFechar'];
            }
            break;
    }
}
?>
<style>
/* ── OS detalhe: tokens locais, tudo derivado de tokens.css ───────────── */
.osd-card { background: var(--surface-1); border: 1px solid var(--border-strong); border-radius: var(--radius-lg); overflow: hidden; }
.osd-header { padding: 14px 18px 0; }
.osd-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.osd-title { font-size: 17px; font-weight: 700; color: var(--text-1); text-transform: none !important; margin: 0; }
.osd-prio {
  font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 999px;
  border: 1.5px solid var(--prio-cor, var(--border-strong));
  background: color-mix(in srgb, var(--prio-cor, var(--border-strong)) 15%, var(--surface-1));
  color: var(--prio-cor, var(--text-3)); text-transform: none !important;
}
.osd-tag { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; text-transform: none !important; }
.osd-tag.garantia { background: var(--danger-bg); color: var(--danger); }

.osd-status-badge {
  display: inline-flex; align-items: center; gap: 7px; padding: 4px 6px 4px 10px;
  border-radius: 999px; border: 1.5px solid var(--status-cor, var(--border-strong));
  background: color-mix(in srgb, var(--status-cor, var(--border-strong)) 15%, var(--surface-1));
  cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--text-1);
  text-transform: none !important; line-height: 1.5;
}
.osd-status-badge:hover { filter: brightness(.97); }
.osd-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.osd-status-badge .bi-chevron-down { font-size: 10px; color: var(--text-3); }

/* O painel inteiro (lista + observação + Salvar) não pode ultrapassar a altura da tela — sem
   isso, com o badge de status perto do topo/fundo da viewport, o botão Salvar ficava cortado
   fora da área visível (só a lista de status por dentro tinha scroll próprio). */
.osd-status-dropdown { max-height: calc(100vh - 2rem); overflow-y: auto; }

/* Lista de status pra trocar (substitui o <select> nativo — precisa de cor/ícone por linha) */
.osd-status-list { max-height: 280px; overflow-y: auto; margin-bottom: 8px; }
.osd-status-opt { display: flex; align-items: center; gap: 8px; padding: 7px 8px; border-radius: 6px; cursor: pointer; font-size: 13.5px; color: var(--text-1); }
.osd-status-opt:hover { background: var(--surface-2); }
.osd-status-opt.ativo { background: var(--accent-bg); color: var(--accent-text); font-weight: 600; }
.osd-status-opt-tag { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }
.osd-status-opt-nome { flex: 1; min-width: 0; }
.osd-status-opt-lock { font-size: 11px; color: var(--text-3); flex-shrink: 0; }
.osd-status-opt-custom { font-size: 8px; color: var(--text-4); opacity: .8; flex-shrink: 0; }
.osd-status-opt-header { font-size: 10.5px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; padding: 8px 8px 4px; }

.osd-actions { display: flex; gap: 8px; padding: 10px 0; flex-wrap: wrap; align-items: center; justify-content: space-between; }
.osd-actions-left { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.osd-btn { box-sizing: border-box; display: inline-flex; align-items: center; gap: 6px; height: 36px; font-size: 13px; font-weight: 600; border-radius: 8px; padding: 0 14px; text-decoration: none; cursor: pointer; text-transform: none !important; line-height: 1; }
.osd-btn-primary { background: var(--accent); color: #fff; border: 1px solid transparent; }
.osd-btn-primary:hover { background: var(--accent-hover); color: #fff; }
.osd-btn-primary:disabled { opacity: .6; cursor: default; }
.osd-btn-outline { background: none; border: 1px solid var(--border-strong); color: var(--text-2); }
.osd-btn-outline:hover { border-color: var(--accent); color: var(--accent-text); }
/* Cada botão com um tom leve próprio — só pra diferenciar visualmente, sem gritar */
.osd-btn-print { background: var(--accent-bg); border-color: var(--accent); color: var(--accent-text); }
.osd-btn-print:hover { background: var(--accent); color: #fff; }
.osd-btn-edit { background: var(--warning-bg); border-color: var(--warning-fill); color: var(--warning); }
.osd-btn-edit:hover { background: var(--warning-fill); color: #fff; }
.osd-btn-whatsapp { background: var(--success-bg); color: #22c55e; border-color: #22c55e; }
.osd-btn-whatsapp:hover { background: #22c55e; color: #fff; }
.osd-btn-reabrir { background: var(--accent-bg); border-color: var(--accent); color: var(--accent-text); }
.osd-btn-reabrir:hover { background: var(--accent); color: #fff; }
/* Mesmo tom leve dos botões do cabeçalho, aplicado aos itens do menu de 3 pontos */
#osdMenuDropdown .osd-menu-accent { color: var(--accent-text); }
#osdMenuDropdown .osd-menu-accent:hover, #osdMenuDropdown .osd-menu-accent:focus { background: var(--accent-bg); color: var(--accent-text); }
#osdMenuDropdown .osd-menu-success { color: var(--success); }
#osdMenuDropdown .osd-menu-success:hover, #osdMenuDropdown .osd-menu-success:focus { background: var(--success-bg); color: var(--success); }
#osdMenuDropdown .osd-menu-warning { color: var(--warning); }
#osdMenuDropdown .osd-menu-warning:hover, #osdMenuDropdown .osd-menu-warning:focus { background: var(--warning-bg); color: var(--warning); }
#osdMenuDropdown .osd-menu-danger { color: var(--danger); }
#osdMenuDropdown .osd-menu-danger:hover, #osdMenuDropdown .osd-menu-danger:focus { background: var(--danger-bg); color: var(--danger); }

.osd-doc-row { display: flex; align-items: center; gap: 4px; padding: .3rem .7rem .3rem 1rem; }
.osd-doc-link { flex: 1; min-width: 0; color: var(--text-1); text-decoration: none; font-size: 13.5px; display: flex; align-items: center; padding: .15rem 0; text-transform: none !important; }
.osd-doc-link:hover { color: var(--accent-text); }
.osd-doc-wa { background: none; border: none; color: var(--text-3); padding: 5px 7px; border-radius: 6px; flex-shrink: 0; }
.osd-doc-wa:hover { color: #22c55e; background: var(--surface-2); }

.osd-body { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--border-strong); }
.osd-col { padding: 10px 18px; min-width: 0; }
.osd-col + .osd-col { border-left: 1px solid var(--border-strong); padding-left: 10px; }
.osd-label { font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-3); margin-bottom: 8px; }
.osd-client-name { font-size: 14px; font-weight: 700; color: var(--accent-text); text-decoration: none; text-transform: none !important; }
.osd-client-name:hover { text-decoration: underline; }
.osd-info-line { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-2); margin-top: 7px; text-transform: none !important; line-height: 1.5; }
.osd-info-line i { color: var(--text-3); width: 15px; text-align: center; flex-shrink: 0; }
.osd-wa-link { margin-left: auto; color: #22c55e; flex-shrink: 0; }
.osd-equip-title { font-size: 14px; font-weight: 700; color: var(--text-1); text-transform: none !important; }
.osd-equip-meta { font-size: 12px; color: var(--text-2); margin-top: 3px; text-transform: none !important; }
.osd-mono { font-family: ui-monospace, Menlo, monospace; font-size: 11.5px; color: var(--text-3); margin-top: 5px; }
.osd-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
.osd-chip { display: inline-flex; align-items: center; font-size: 11.5px; font-weight: 600; padding: 3px 10px; border: 1.5px solid var(--warning-fill); border-radius: 999px; color: var(--warning); background: var(--warning-bg); text-transform: none !important; }

.osd-full { grid-column: 1 / -1; padding: 10px 18px; border-top: 1px solid var(--border-strong); }
.osd-full p { margin: 0; font-size: 13px; color: var(--text-1); line-height: 1.6; text-transform: none !important; }
/* Laudo é HTML de rich-text (pode ter <div>/<b> dentro) — não fica dentro de <p> (o navegador
   fecharia o <p> sozinho e o texto escaparia da regra acima), por isso é um bloco à parte. */
.osd-laudo-texto, .osd-laudo-texto * { margin: 0; font-size: 13px; color: var(--text-1); line-height: 1.6; text-transform: none !important; }
.osd-empty-link { color: var(--accent-text); text-decoration: none; font-weight: 600; }
.osd-empty-link:hover { text-decoration: underline; }

.osd-footer { display: flex; flex-wrap: wrap; gap: 6px 22px; padding: 10px 18px; background: var(--surface-2); border-top: 1px solid var(--border-strong); font-size: 11.5px; color: var(--text-2); }
.osd-footer i { color: var(--text-3); margin-right: 4px; }
.osd-footer input[type="number"] { width: 56px; padding: 1px 4px; font-size: 11.5px; border: 1px solid var(--border); border-radius: 5px; background: var(--surface-1); color: var(--text-1); }
.osd-footer input[type="date"] { padding: 1px 4px; font-size: 11.5px; border: 1px solid var(--border); border-radius: 5px; background: var(--surface-1); color: var(--text-1); }

/* ── Financeiro (lateral) ── */
.osd-fin-item { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; color: var(--text-2); padding: 4px 0; text-transform: none !important; }
.osd-fin-item .val, td.osd-val, .osd-total .val { font-variant-numeric: tabular-nums; }
.osd-fin-desc { font-weight: 700; color: var(--text-1); }
.osd-fin-valor-item { font-weight: 700; color: var(--success); background: var(--success-bg); padding: 2px 8px; border-radius: 6px; }
.osd-fin-servicos { padding-bottom: 6px; }
.osd-fin-pecas { padding-top: 6px; margin-top: 4px; border-top: 1px solid var(--border-strong); }
.osd-fin-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; color: var(--text-1); padding: 10px 0 2px; margin-top: 6px; border-top: 1px solid var(--border-strong); text-transform: none !important; }
.osd-fin-total .val { color: var(--success); }
.osd-fin-pay { border-radius: 8px; padding: 10px 12px; margin-top: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 12.5px; font-weight: 700; text-transform: none !important; }
.osd-fin-pay.pendente { background: var(--warning-bg); color: var(--warning); }
.osd-fin-pay.pago { background: var(--success-bg); color: var(--success); }
.osd-fin-pay .osd-btn { height: auto; padding: 5px 11px; font-size: 12px; }
.osd-fin-pay.pendente .osd-btn { background: var(--warning); color: #fff; }
.osd-fin-pay.pago .osd-btn { background: var(--success); color: #fff; }

/* ── Andamento (timeline) ── */
.osd-tl-item { display: flex; gap: 10px; padding: 7px 0; }
.osd-tl-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.osd-tl-dot.atual { background: var(--success-fill); }
.osd-tl-dot.antigo { background: var(--border-strong); }
.osd-tl-txt { min-width: 0; }
.osd-tl-label { font-size: 13px; color: var(--text-1); font-weight: 600; text-transform: none !important; }
.osd-tl-label.antigo { color: var(--text-2); font-weight: 500; }
.osd-tl-meta { font-size: 11px; color: var(--text-3); margin-top: 1px; text-transform: none !important; }
.osd-tl-desc { font-size: 11.5px; color: var(--text-3); margin-top: 1px; text-transform: none !important; }

/* ── Tabelas (serviços/peças) ── */
.osd-table td, .osd-table th { text-transform: none !important; }
.osd-table td.osd-val { text-align: right; font-variant-numeric: tabular-nums; }
.osd-table .osd-row-actions .btn { color: var(--text-3); border-color: transparent; background: none; }
.osd-table .osd-row-actions .btn:hover { color: var(--text-1); background: var(--surface-2); }
.osd-table .osd-row-actions .btn.text-danger-hover:hover { color: var(--danger) !important; }

/* ── Laudo: sem paleta de cores, só formatação básica ── */
#laudoEditorBox { border: 1px solid var(--border); border-radius: .375rem; overflow: hidden; }
#laudoEditorBox:focus-within { border-color: var(--accent); box-shadow: 0 0 0 .2rem var(--accent-bg); }
#laudoToolbar { background: var(--surface-2); border-bottom: 1px solid var(--border); padding: .35rem .5rem; }
#laudoToolbar .btn.active { background: var(--border); border-color: var(--border-strong); }
#laudoTexto, #laudoTexto * { text-transform: none !important; }
#laudoTexto { border: 0; border-radius: 0; background: var(--surface-1); color: var(--text-1); }
#laudoTexto:focus { box-shadow: none; }
#laudoTexto[contenteditable]:empty:before { content: attr(data-placeholder); color: var(--text-3); text-transform: none !important; }
#laudoTexto b, #laudoTexto strong { font-weight: 700; }
#laudoTexto ul, #laudoTexto ol { margin: 0; padding-left: 1.4rem; }

/* Rótulos de seção em maiúsculas — a ÚNICA exceção à caixa normal */
.osd-section-title { font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-2); }

/* ── Menu de 3 pontos: painel deslizante no mobile ── */
@media (max-width: 767.98px) {
  #osdMenuDropdown .dropdown-menu.show {
    position: fixed; left: 0; right: 0; bottom: 0; top: auto; margin: 0;
    width: 100%; max-width: none; border-radius: 14px 14px 0 0;
    padding: 8px 0 max(10px, env(safe-area-inset-bottom, 0px));
    box-shadow: 0 -10px 40px rgba(0,0,0,.25);
  }
  #osdMenuDropdown .dropdown-item, #osdMenuDropdown .osd-menu-btn { min-height: 44px; display: flex; align-items: center; font-size: 14px; }
  .osd-body { grid-template-columns: 1fr; }
  .osd-col + .osd-col { border-left: none; padding-left: 18px; border-top: 1px solid var(--border-strong); padding-top: 10px; }
  .osd-actions { padding-bottom: 70px; }
  .osd-mobile-bar {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 1040;
    background: var(--surface-1); border-top: 1px solid var(--border);
    padding: 10px 14px max(10px, env(safe-area-inset-bottom, 0px));
  }
  .osd-mobile-bar .osd-btn-primary { width: 100%; justify-content: center; min-height: 44px; }
}
@media (min-width: 768px) { .osd-mobile-bar { display: none; } }
</style>

<div class="row g-3">
  <!-- Principal -->
  <div class="col-md-8">
    <div class="osd-card mb-3">
      <div class="osd-header">
        <div class="osd-title-row">
          <span class="osd-title">OS <?= e($os['numero']) ?></span>

          <div class="dropdown" id="osdStatusDropdownWrap">
            <button type="button" class="osd-status-badge" style="--status-cor:<?= e($os['status_cor'] ?: '#8A91A0') ?>" data-bs-toggle="dropdown" aria-expanded="false" title="Clique para alterar o status">
              <span class="osd-status-dot" style="background:<?= e($os['status_cor'] ?: '#8A91A0') ?>"></span>
              <?= e($os['status_nome'] ?? 'Sem status') ?>
              <i class="bi bi-chevron-down"></i>
            </button>
            <div class="dropdown-menu osd-status-dropdown p-3" style="min-width:270px" onclick="event.stopPropagation()">
              <input type="hidden" id="novoStatus" value="<?= (int) $os['status_id'] ?>">
              <?php
              $normais   = array_filter($statusList, fn($s) => $s['tipo'] !== 'garantia');
              $garantias = array_filter($statusList, fn($s) => $s['tipo'] === 'garantia');
              $renderOpt = function ($s) use ($os) {
                  $ativo = $os['status_id'] == $s['id'] ? ' ativo' : '';
                  echo '<div class="osd-status-opt' . $ativo . '" data-id="' . (int) $s['id'] . '" onclick="selecionarStatusOpt(this)">';
                  echo '<span class="osd-status-opt-tag" style="background:' . e($s['cor'] ?: '#8A91A0') . '"></span>';
                  echo '<span class="osd-status-opt-nome">' . e($s['nome']) . '</span>';
                  echo ((int) $s['bloqueado'] === 1)
                      ? '<i class="bi bi-lock-fill osd-status-opt-lock" title="Status nativo do sistema"></i>'
                      : '<i class="bi bi-pencil-fill osd-status-opt-custom" title="Status personalizado"></i>';
                  echo '</div>';
              };
              ?>
              <div class="osd-status-list">
                <?php foreach ($normais as $s) $renderOpt($s); ?>
                <?php if ($garantias): ?>
                <div class="osd-status-opt-header">🛡 Entradas em garantia</div>
                <?php foreach ($garantias as $s) $renderOpt($s); ?>
                <?php endif; ?>
              </div>
              <textarea id="statusDescricao" class="form-control form-control-sm mb-2" rows="2" placeholder="Observação (opcional)"></textarea>
              <button type="button" class="btn btn-primary btn-sm w-100" id="btnSalvarStatus">Salvar</button>
            </div>
          </div>

          <?php if ($os['tipo_servico'] === 'garantia' && !empty($os['os_origem_id'])): ?>
          <span class="osd-tag garantia"><i class="bi bi-shield-check me-1"></i>Garantia</span>
          <?php endif; ?>

          <?php
            $prioCores = ['urgente' => '#dc3545', 'alta' => '#fd7e14', 'normal' => '#0d6efd', 'baixa' => '#6c757d'];
            $prioCor = $prioCores[$os['prioridade']] ?? '#6c757d';
          ?>
          <span class="osd-prio ms-auto" style="--prio-cor:<?= e($prioCor) ?>">Prioridade: <?= ucfirst($os['prioridade']) ?></span>
        </div>

        <!-- Linha de ações: no máximo 4 elementos -->
        <div class="osd-actions">
        <div class="osd-actions-left">
          <?php if ($acaoPrimaria): ?>
            <?php if (!empty($acaoPrimaria['modal'])): ?>
            <button type="button" class="osd-btn osd-btn-primary" data-bs-toggle="modal" data-bs-target="<?= $acaoPrimaria['modal'] ?>">
              <i class="bi bi-<?= $acaoPrimaria['icon'] ?>"></i><?= e($acaoPrimaria['label']) ?>
            </button>
            <?php else: ?>
            <button type="button" class="osd-btn osd-btn-primary" onclick="<?= $acaoPrimaria['onclick'] ?>">
              <i class="bi bi-<?= $acaoPrimaria['icon'] ?>"></i><?= e($acaoPrimaria['label']) ?>
            </button>
            <?php endif; ?>
          <?php endif; ?>

          <div class="dropdown">
            <button type="button" class="osd-btn osd-btn-outline osd-btn-print" data-bs-toggle="dropdown">
              <i class="bi bi-printer"></i>Imprimir
            </button>
            <ul class="dropdown-menu" style="min-width:270px">
              <li><h6 class="dropdown-header">Documentos</h6></li>
              <?php if (!$emLaudo && !$emSemConserto): ?>
              <li class="osd-doc-row">
                <a href="<?= $urlAber ?>" target="_blank" class="osd-doc-link"><i class="bi bi-file-earmark me-2"></i>Abertura</a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('abertura', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <li class="osd-doc-row">
                <a href="<?= url('/os/' . $os['id'] . '/imprimir/etiqueta') ?>" target="_blank" class="osd-doc-link"><i class="bi bi-tag me-2"></i>Etiqueta interna</a>
              </li>
              <?php if ($temOrcamento): ?>
              <li class="osd-doc-row">
                <a href="<?= url('/os/' . $os['id'] . '/imprimir/orcamento') ?>" target="_blank" class="osd-doc-link"><i class="bi bi-file-earmark-text me-2"></i>Orçamento</a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('orcamento', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <?php endif; ?>
              <?php if ($concluida): ?>
              <li class="osd-doc-row">
                <a href="<?= $urlFech ?>" target="_blank" class="osd-doc-link"><i class="bi bi-file-earmark-check me-2"></i>Comprovante</a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('fechamento', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <?php endif; ?>
              <?php if (!empty($os['os_origem_id'])): ?>
              <li class="osd-doc-row">
                <a href="<?= url('/os/' . $os['id'] . '/imprimir/garantia') ?>" target="_blank" class="osd-doc-link"><i class="bi bi-shield-check me-2"></i>Garantia</a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('garantia', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <?php endif; ?>
              <?php endif; ?>
              <?php if (!$emSemConserto && ($emLaudo || !empty($os['laudo_tecnico']))): ?>
              <li class="osd-doc-row">
                <a href="<?= url('/os/' . $os['id'] . '/imprimir/laudo') ?>" target="_blank" class="osd-doc-link"><i class="bi bi-clipboard2-pulse me-2"></i>Laudo técnico</a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('laudo', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <?php endif; ?>
              <?php if ($emSemConserto): ?>
              <li class="osd-doc-row">
                <a href="<?= url('/os/' . $os['id'] . '/imprimir/sem-conserto') ?>" target="_blank" class="osd-doc-link"><i class="bi bi-file-earmark-x me-2"></i><?= ($os['status_tipo'] ?? '') === 'cancelada' ? e($os['status_nome']) : 'Comprovante sem cobrança' ?></a>
                <?php if ($fone): ?><button type="button" class="osd-doc-wa" onclick="enviarPdfWa('sem-conserto', this)" title="Enviar por WhatsApp"><i class="bi bi-whatsapp"></i></button><?php endif; ?>
              </li>
              <?php endif; ?>
              <?php if ($fone): ?>
              <li><hr class="dropdown-divider"></li>
              <li class="osd-doc-row"><button type="button" class="osd-doc-link border-0 bg-transparent w-100 text-start" onclick="enviarLinkWa(this)"><i class="bi bi-link-45deg me-2"></i>Enviar link de acompanhamento</button></li>
              <?php endif; ?>
            </ul>
          </div>

          <?php if ($fone):
            $foneWa   = (strlen($fone) <= 11) ? '55' . $fone : $fone;
            $msgFalar = urlencode("Olá *{$nomeCli}*! Aqui é da " . ($os['empresa_nome'] ?? 'assistência') . " sobre a sua OS *{$numOs}*.");
          ?>
          <a href="https://wa.me/<?= $foneWa ?>?text=<?= $msgFalar ?>" target="_blank" rel="noopener"
             class="osd-btn osd-btn-outline osd-btn-whatsapp" title="Abrir conversa no WhatsApp com o cliente">
            <i class="bi bi-whatsapp"></i>Falar com o cliente
          </a>
          <?php endif; ?>

          <a href="<?= url('/os/' . $os['id'] . '/editar') ?>" class="osd-btn osd-btn-outline osd-btn-edit"><i class="bi bi-pencil"></i>Editar</a>

          <?php if ($jaEntregue): ?>
          <form method="POST" action="<?= url('/os/' . $os['id'] . '/reabrir') ?>" onsubmit="return confirm('Reabrir esta OS? Ela voltará ao status anterior ao fechamento.');" style="display:contents">
            <?= csrf_field() ?>
            <button type="submit" class="osd-btn osd-btn-outline osd-btn-reabrir"><i class="bi bi-arrow-counterclockwise"></i>Reabrir OS</button>
          </form>
          <?php endif; ?>
        </div>

          <div class="dropdown" id="osdMenuDropdown">
            <button type="button" class="osd-btn osd-btn-outline" data-bs-toggle="dropdown" aria-label="Outras opções" title="Outras opções">
              <i class="bi bi-three-dots-vertical"></i>Outras opções
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (($os['em_garantia'] ?? false) && $os['tipo_servico'] !== 'garantia' && empty($os['os_origem_id']) && !$osDescartada && $jaEntregue && empty($os['fechada_sem_receita']) && (float)$os['valor_total'] > 0): ?>
              <li><button type="button" class="dropdown-item osd-menu-btn osd-menu-accent" data-bs-toggle="modal" data-bs-target="#modalGarantia"><i class="bi bi-shield-check me-2"></i>Abrir garantia</button></li>
              <?php endif; ?>
              <?php if ($podeFechar): ?>
              <li><button type="button" class="dropdown-item osd-menu-btn osd-menu-success" data-bs-toggle="modal" data-bs-target="#modalFechar"><i class="bi bi-<?= $semConserto ? 'x-circle' : 'check-circle' ?> me-2"></i><?= $semConserto ? $labelFechar : 'Fechar OS' ?></button></li>
              <?php endif; ?>
              <!-- "Reabrir OS" agora é botão próprio ao lado de Editar (só quando Fechado) — ver osd-actions-left. -->
              <?php if ($statusCanceladaId && $os['status_id'] != $statusCanceladaId && !$jaEntregue): ?>
              <li><button type="button" class="dropdown-item osd-menu-btn osd-menu-danger" onclick="cancelarOs()"><i class="bi bi-slash-circle me-2"></i>Cancelar OS</button></li>
              <?php endif; ?>
              <li>
                <form method="POST" action="<?= url('/os/' . $os['id'] . '/duplicar') ?>" onsubmit="return confirm('Criar uma nova OS a partir desta? Nada de valores, histórico ou laudo é copiado — só cliente, equipamento e defeito relatado, pra você revisar.');">
                  <?= csrf_field() ?>
                  <button type="submit" class="dropdown-item osd-menu-btn osd-menu-accent"><i class="bi bi-files me-2"></i>Duplicar OS</button>
                </form>
              </li>
              <?php if (\App\Core\Auth::isAdmin()): ?>
              <li><hr class="dropdown-divider"></li>
              <li><button type="button" class="dropdown-item osd-menu-btn osd-menu-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirOsDetalhe"><i class="bi bi-trash3 me-2"></i>Excluir OS</button></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Corpo: cliente / equipamento -->
      <div class="osd-body">
        <div class="osd-col">
          <div class="osd-label">Cliente</div>
          <a href="<?= url('/clientes/' . $os['cliente_id']) ?>" class="osd-client-name"><?= e($os['cliente_nome']) ?></a>

          <?php if ($telNorm || $waNorm): ?>
          <div class="osd-info-line">
            <i class="bi bi-<?= ($waNorm) ? 'whatsapp' : 'telephone' ?>" style="<?= $waNorm ? 'color:#22c55e' : '' ?>"></i>
            <?php if ($telIgualWa): ?>
              <?= e($os['cliente_tel']) ?>
            <?php elseif ($waNorm && $telNorm): ?>
              <?= e($os['cliente_whats']) ?><span class="text-body-secondary ms-1" style="font-size:11px">(tel: <?= e($os['cliente_tel']) ?>)</span>
            <?php else: ?>
              <?= e($os['cliente_whats'] ?: $os['cliente_tel']) ?>
            <?php endif; ?>
            <?php if ($waNorm): ?>
            <a href="https://wa.me/<?= (strlen($waNorm) <= 11 ? '55'.$waNorm : $waNorm) ?>?text=<?= urlencode("Olá *{$nomeCli}*! Aqui é da " . ($os['empresa_nome'] ?? 'assistência') . " sobre a sua OS *{$numOs}*.") ?>"
               target="_blank" rel="noopener" class="osd-wa-link" title="Abrir conversa no WhatsApp"><i class="bi bi-chat-dots"></i></a>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($os['cpf_cnpj'])): ?>
          <div class="osd-info-line"><i class="bi bi-card-text"></i><?= e(doc_mask($os['cpf_cnpj'])) ?></div>
          <?php endif; ?>

          <?php
            $endLinha1 = trim(implode(', ', array_filter([
              $os['cli_logradouro'] ?? '',
              ($os['cli_numero'] ?? '') ? 'nº ' . $os['cli_numero'] : '',
              $os['cli_bairro'] ?? '',
            ])));
            $endLinha2 = trim(($os['cli_cidade'] ?? '') . (!empty($os['cli_uf']) ? '/' . $os['cli_uf'] : '') . (!empty($os['cli_cep']) ? ' · CEP ' . $os['cli_cep'] : ''));
          ?>
          <?php if ($endLinha1 || $endLinha2): ?>
          <div class="osd-info-line" style="align-items:flex-start">
            <i class="bi bi-geo-alt mt-1"></i>
            <div>
              <?php if ($endLinha1): ?><div><?= e($endLinha1) ?></div><?php endif; ?>
              <?php if ($endLinha2): ?><div><?= e($endLinha2) ?></div><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="osd-col">
          <div class="osd-label">Equipamento</div>
          <div class="osd-equip-title"><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
          <div class="osd-equip-meta"><?= e($os['equip_tipo']) ?><?= $os['equip_cor'] ? ' · ' . e($os['equip_cor']) : '' ?></div>
          <?php if ($os['numero_serie']): ?><div class="osd-mono">N/S: <?= e($os['numero_serie']) ?></div><?php endif; ?>
          <?php if (!empty($os['imei'])): ?><div class="osd-mono">IMEI <?= e($os['imei']) ?></div><?php endif; ?>
          <?php if ($os['senha_desbloqueio']): ?><div class="osd-info-line"><i class="bi bi-shield-lock"></i><?= e($os['senha_desbloqueio']) ?></div><?php endif; ?>

          <?php
            $infoPecas = array_filter([
              !empty($os['processador'])       ? 'Processador: ' . $os['processador']       : '',
              !empty($os['memoria_ram'])        ? 'Memória: ' . $os['memoria_ram']            : '',
              !empty($os['tipo_armazenamento']) ? 'Armazenamento: ' . $os['tipo_armazenamento']: '',
              !empty($os['placa_video'])        ? 'Placa de vídeo: ' . $os['placa_video']      : '',
              !empty($os['placa_mae'])          ? 'Placa mãe: ' . $os['placa_mae']             : '',
            ]);
          ?>
          <?php if ($infoPecas): ?>
          <div class="osd-info-line"><i class="bi bi-cpu"></i><?= e(implode(' · ', $infoPecas)) ?></div>
          <?php endif; ?>

          <?php if (!empty($os['acessorios'])): ?>
          <div class="osd-chips">
            <?php foreach (array_filter(array_map('trim', explode(',', $os['acessorios']))) as $ac): ?>
            <span class="osd-chip"><?= e($ac) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="osd-full">
          <div class="osd-label">Defeito relatado</div>
          <p><?= nl2br(e($os['defeito_relatado'])) ?></p>
        </div>

        <?php if ($os['defeito_constatado']): ?>
        <div class="osd-full">
          <div class="osd-label">Defeito constatado</div>
          <p><?= nl2br(e($os['defeito_constatado'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="osd-full">
          <div class="osd-label">Laudo técnico</div>
          <?php if (!empty($os['laudo_tecnico'])): ?>
          <div class="osd-laudo-texto"><?= $os['laudo_tecnico'] ?></div>
          <?php else: ?>
          <p class="fst-italic text-body-secondary">Ainda não preenchido — <a href="#laudoTexto" class="osd-empty-link" onclick="var el=document.getElementById('laudoTexto'); el.scrollIntoView({behavior:'smooth', block:'center'}); setTimeout(function(){el.focus();}, 300); return false;">escrever</a></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="osd-footer">
        <span><i class="bi bi-calendar3"></i>Entrada: <?= date_br($os['data_entrada'], true) ?></span>
        <?php if ($_SESSION['mostrar_previsao'] ?? 1): ?>
        <?php $previsaoValor = !empty($os['data_previsao']) ? date('Y-m-d', strtotime($os['data_previsao'])) : date('Y-m-d', strtotime('+3 days')); ?>
        <span>
          <i class="bi bi-clock"></i>Previsão:
          <input type="date" id="previsaoEntrega" value="<?= e($previsaoValor) ?>" title="Editar a previsão de entrega">
          <span id="previsaoOk" class="text-success ms-1" style="display:none"><i class="bi bi-check-circle-fill"></i></span>
        </span>
        <?php endif; ?>
        <span><i class="bi bi-person-gear"></i>Técnico: <?= e($os['tecnico_nome'] ?? '—') ?></span>
        <span>
          <i class="bi bi-shield-check"></i>Garantia:
          <input type="number" id="garDias" value="<?= (int) ($os['garantia_dias'] ?: 90) ?>" min="0" max="3650" title="Editar os dias de garantia"> dias
          <span id="garOk" class="text-success ms-1" style="display:none"><i class="bi bi-check-circle-fill"></i></span>
        </span>
      </div>
    </div>
    <script>
    (function () {
      var inp = document.getElementById('garDias'), ok = document.getElementById('garOk');
      if (!inp) return;
      var atual = inp.value;
      inp.addEventListener('change', function () {
        var v = Math.max(0, Math.min(3650, parseInt(inp.value) || 0));
        inp.value = v;
        if (String(v) === String(atual)) return;
        fetch('<?= url('/os/' . $os['id'] . '/garantia-dias') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
          body: 'garantia_dias=' + v
        }).then(function (r) { return r.json(); }).then(function (d) {
          if (d && d.ok) {
            atual = String(v);
            ok.style.display = ''; setTimeout(function () { ok.style.display = 'none'; }, 2000);
          }
        }).catch(function () {});
      });
    })();
    (function () {
      var inp = document.getElementById('previsaoEntrega'), ok = document.getElementById('previsaoOk');
      if (!inp) return;
      var atual = inp.value;
      inp.addEventListener('change', function () {
        if (inp.value === atual) return;
        fetch('<?= url('/os/' . $os['id'] . '/previsao') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
          body: 'data_previsao=' + encodeURIComponent(inp.value)
        }).then(function (r) { return r.json(); }).then(function (d) {
          if (d && d.ok) {
            atual = inp.value;
            ok.style.display = ''; setTimeout(function () { ok.style.display = 'none'; }, 2000);
          }
        }).catch(function () {});
      });
    })();
    </script>

    <!-- Observações internas (nunca aparece pro cliente) -->
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px">
        <span class="osd-section-title"><i class="bi bi-journal-text me-2 text-secondary"></i>Observações internas</span>
      </div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <p class="small text-body-secondary mb-2" style="font-size:12px">Anotações da equipe — não aparece para o cliente, nem no WhatsApp nem no PDF.</p>
        <textarea id="obsInternasTexto" class="form-control" rows="2"
          placeholder="Anotações internas (não aparece para o cliente)..."><?= e($os['observacoes_internas'] ?? '') ?></textarea>
        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
          <div id="obsInternasMsg" class="small"></div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSalvarObsInternas"><i class="bi bi-save me-1"></i>Salvar</button>
        </div>
      </div>
    </div>

    <!-- Serviços -->
    <div class="osd-card mb-3">
      <div class="osd-header d-flex justify-content-between align-items-center" style="padding-bottom:14px">
        <span class="osd-section-title">Serviços realizados</span>
        <button class="btn btn-sm btn-outline-primary" onclick="resetModalServico()" data-bs-toggle="modal" data-bs-target="#modalServico"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle osd-table" id="tblServicos">
          <thead class="table-light"><tr><th>Descrição</th><th>Qtd</th><th class="text-end">Valor unit.</th><th class="text-end">Total</th><th>Técnico</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($svcList as $s): ?>
            <tr>
              <td><?= e($s['descricao']) ?></td>
              <td><?= $s['quantidade'] ?></td>
              <td class="osd-val"><?= money($s['valor_unitario']) ?></td>
              <td class="fw-semibold osd-val"><?= money($s['valor_total']) ?></td>
              <td><?= e($s['tecnico_nome'] ?? '—') ?></td>
              <td class="text-end osd-row-actions">
                <button type="button" class="btn btn-sm"
                  onclick="preencherServico(<?= $s['id'] ?>, '<?= addslashes(e($s['descricao'])) ?>', <?= $s['quantidade'] ?>, <?= $s['valor_unitario'] ?>, '<?= $s['tecnico_id'] ?>')"
                  data-bs-toggle="modal" data-bs-target="#modalServico">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/servicos/' . $s['id']) ?>"
                   class="btn btn-sm text-danger-hover"
                   data-method="DELETE"
                   data-confirm="Remover este serviço?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$svcList): ?>
            <tr><td colspan="6" class="text-center text-body-secondary py-3">Nenhum serviço adicionado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Peças -->
    <div class="osd-card mb-3">
      <div class="osd-header d-flex justify-content-between align-items-center" style="padding-bottom:14px">
        <span class="osd-section-title">Peças utilizadas</span>
        <button class="btn btn-sm btn-outline-primary" onclick="resetModalPeca()" data-bs-toggle="modal" data-bs-target="#modalPeca"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle osd-table">
          <thead class="table-light"><tr><th>Peça</th><th>Qtd</th><th class="text-end">Valor unit.</th><th class="text-end">Total</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($pcList as $p): ?>
            <tr>
              <td>
                <?= e($p['descricao']) ?>
                <?php if ($p['prod_codigo']): ?><span class="badge bg-light text-body-secondary border ms-1">#<?= e($p['prod_codigo']) ?></span><?php endif; ?>
              </td>
              <td><?= $p['quantidade'] ?></td>
              <td class="osd-val"><?= money($p['valor_unitario']) ?></td>
              <td class="fw-semibold osd-val"><?= money($p['valor_total']) ?></td>
              <td class="text-end osd-row-actions">
                <button type="button" class="btn btn-sm"
                  onclick="preencherPeca(<?= $p['id'] ?>, '<?= addslashes(e($p['descricao'])) ?>', <?= $p['quantidade'] ?>, <?= $p['valor_unitario'] ?>)"
                  data-bs-toggle="modal" data-bs-target="#modalPeca">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/pecas/' . $p['id']) ?>"
                   class="btn btn-sm text-danger-hover"
                   data-method="DELETE"
                   data-confirm="Remover esta peça?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$pcList): ?>
            <tr><td colspan="5" class="text-center text-body-secondary py-3">Nenhuma peça adicionada.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Adiantamento (sinal recebido antes do fechamento — ex.: peça cara, cliente adianta parte) -->
    <div class="osd-card mb-3" style="background:color-mix(in srgb, var(--success) 16%, var(--surface-1));border-color:var(--success)">
      <div class="osd-header d-flex justify-content-between align-items-center" style="padding-bottom:14px">
        <span class="osd-section-title">Adiantamento de Peças/Pagamento Adiantado</span>
        <?php if ($podeFechar): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAdiantamento"><i class="bi bi-plus-lg"></i> Adicionar</button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle osd-table">
          <thead class="table-light"><tr><th>Data</th><th>Forma</th><th class="text-end">Valor</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php
            $formasLabel = ['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'cartao_credito' => 'Cartão de crédito', 'cartao_debito' => 'Cartão de débito', 'transferencia' => 'Transferência', 'boleto' => 'Boleto'];
            foreach ($adiantamentos as $a): ?>
            <tr>
              <td><?= date_br($a['criado_em'], true) ?></td>
              <td><?= e($formasLabel[$a['forma_pagamento']] ?? $a['forma_pagamento']) ?><?= ($a['forma_pagamento'] === 'cartao_credito' && $a['parcelas'] > 1) ? ' ' . (int) $a['parcelas'] . 'x' : '' ?></td>
              <td class="fw-semibold osd-val"><?= money($a['valor_cobrado']) ?></td>
              <td class="text-end osd-row-actions">
                <?php if ($podeFechar): ?>
                <a href="<?= url('/os/' . $os['id'] . '/adiantamentos/' . $a['id']) ?>"
                   class="btn btn-sm text-danger-hover"
                   data-method="DELETE"
                   data-confirm="Remover este adiantamento? O valor recebido será removido do Financeiro.">
                  <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$adiantamentos): ?>
            <tr><td colspan="4" class="text-center text-body-secondary py-3">Nenhum adiantamento registrado.</td></tr>
            <?php endif; ?>
          </tbody>
          <?php if ($adiantamentos): ?>
          <tfoot><tr class="table-light">
            <td colspan="2" class="fw-semibold">Total adiantado</td>
            <td class="text-end fw-semibold osd-val"><?= money(array_sum(array_column($adiantamentos, 'valor_cobrado'))) ?></td>
            <td></td>
          </tr></tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Fotos do estado de entrada (registradas na criação/edição da OS, ou adicionadas daqui) -->
    <div class="osd-card mb-3">
      <div class="osd-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding-bottom:14px">
        <span class="osd-section-title"><i class="bi bi-camera me-2 text-primary"></i>Fotos do estado de entrada</span>
        <div class="d-flex gap-2">
          <input type="file" id="feInputArquivo" accept="image/*" multiple class="d-none">
          <label for="feInputArquivo" class="btn btn-sm btn-outline-primary mb-0"><i class="bi bi-camera me-1"></i>Adicionar foto</label>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btnFeCelular"><i class="bi bi-phone-fill me-1"></i>Tirar foto pelo celular</button>
        </div>
      </div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <div class="d-flex flex-wrap gap-2" id="feFotosGrid">
        <?php foreach ($fotosEntrada as $i => $f): $fUrl = url('/uploads/' . $f['arquivo']); ?>
          <div style="position:relative">
            <button type="button" class="p-0 border-0 bg-transparent osd-foto-entrada" data-foto-index="<?= $i ?>" data-bs-toggle="modal" data-bs-target="#modalFotoEntrada">
              <img src="<?= e($fUrl) ?>" loading="lazy" style="width:82px;height:82px;object-fit:cover;border-radius:8px;border:1px solid var(--border,#dee2e6)">
            </button>
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <button type="button" onclick="excluirFotoEntradaShow(<?= (int) $f['id'] ?>, this)" title="Excluir foto (só admin)"
              style="position:absolute;top:-7px;right:-7px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:22px;height:22px;line-height:20px;font-size:14px;padding:0">&times;</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        </div>
        <p class="fst-italic text-body-secondary mb-0<?= $fotosEntrada ? ' d-none' : '' ?>" id="feFotosVazio">Nenhuma foto do estado de entrada registrada.</p>
        <div class="small mt-2" id="feMsg"></div>
      </div>
    </div>

    <!-- Recado ao cliente (vai no WhatsApp) -->
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px">
        <span class="osd-section-title"><i class="bi bi-chat-left-text me-2 text-success"></i>Recado ao cliente</span>
      </div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <p class="small text-body-secondary mb-2" style="font-size:12px">A mensagem vai pelo WhatsApp junto com o link de acompanhamento e os PDFs. Em branco, envia só o link.</p>
        <textarea id="recadoTexto" class="form-control" rows="2" maxlength="600" spellcheck="true" lang="pt-BR"
          placeholder="Ex.: Segue o orçamento. A peça precisa ser encomendada, prazo de 5 dias úteis."><?= e($os['recado_cliente'] ?? '') ?></textarea>
        <div id="recadoCorrecaoBox" class="mt-2" style="display:none;background:var(--accent-bg,#eef2ff);border:1px solid var(--accent,#6366f1);border-radius:8px;padding:10px 12px">
          <div class="small fw-semibold mb-1" style="color:var(--accent-text,#4338ca)"><i class="bi bi-magic me-1"></i>Sugestão de correção</div>
          <div id="recadoCorrecaoTexto" class="small mb-2" style="white-space:pre-wrap"></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" id="btnUsarCorrecao">Usar esta versão</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDescartarCorrecao">Manter original</button>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
          <div id="recadoMsg" class="small"></div>
          <div class="d-flex align-items-center gap-2">
            <span class="form-text mb-0"><span id="recadoContador">0</span>/600</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCorrigirRecado" title="Corrige ortografia e gramática com IA">
              <i class="bi bi-spellcheck me-1"></i>Corrigir ortografia
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSalvarRecado"><i class="bi bi-save me-1"></i>Salvar</button>
            <button type="button" class="btn btn-sm btn-success" id="btnEnviarRecado"><i class="bi bi-whatsapp me-1"></i>Enviar ao cliente</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Laudo técnico -->
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px"><span class="osd-section-title">Laudo técnico</span></div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <div id="laudoEditorBox">
          <div id="laudoToolbar" class="d-flex align-items-center gap-1 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-cmd="bold" title="Negrito (Ctrl+B)">B</button>
            <button type="button" class="btn btn-sm btn-outline-secondary fst-italic" data-cmd="italic" title="Itálico (Ctrl+I)">I</button>
            <button type="button" class="btn btn-sm btn-outline-secondary text-decoration-underline" data-cmd="underline" title="Sublinhado (Ctrl+U)">U</button>
            <div class="vr mx-1"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertUnorderedList" title="Lista com marcadores"><i class="bi bi-list-ul"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertOrderedList" title="Lista numerada"><i class="bi bi-list-ol"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-cmd="removeFormat" title="Limpar formatação"><i class="bi bi-eraser"></i></button>
          </div>
          <div id="laudoTexto" class="form-control" contenteditable="true" spellcheck="true" lang="pt-BR" style="min-height:80px"
            data-placeholder="Diagnóstico técnico detalhado do defeito e do serviço realizado..."><?= $os['laudo_tecnico'] ?? '' ?></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
          <div id="laudoMsg" class="small"></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnLaudoIA" onclick="gerarLaudoIA(this)" title="Gera um rascunho com base nos dados da OS — sempre revise antes de salvar">
              <i class="bi bi-stars me-1"></i>Preencher com IA
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="btnSalvarLaudo"><i class="bi bi-save me-1"></i>Salvar</button>
          </div>
        </div>
        <div class="form-text mt-1" style="font-size:11.5px">Aparece na impressão de orçamento desta OS.</div>
      </div>
    </div>

    <!-- Lançar comissão manualmente -->
    <?php if (\App\Core\Auth::can('financeiro')): ?>
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px"><span class="osd-section-title"><i class="bi bi-cash-coin me-2"></i>Lançar comissão desta OS</span></div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <form method="POST" action="<?= url('/comissoes') ?>" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <input type="hidden" name="os_id" value="<?= $os['id'] ?>">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Técnico</label>
            <select name="tecnico_id" class="form-select form-select-sm" required>
              <option value="">Selecione...</option>
              <?php foreach ($tecnicos as $t): ?>
              <option value="<?= $t['id'] ?>" <?= (($os['tecnico_id'] ?? null) == $t['id']) ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Valor da mão de obra</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_base" class="form-control" placeholder="0,00" value="<?= $totalServicos > 0 ? number_format($totalServicos, 2, ',', '.') : '' ?>" required>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Comissão</label>
            <div class="input-group input-group-sm">
              <input type="text" name="percentual" class="form-control" placeholder="20" required>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="col-md-3">
            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Lançar comissão</button>
          </div>
        </form>
        <div class="form-text mt-1" style="font-size:11.5px">Cria uma comissão em <a href="<?= url('/comissoes') ?>">Comissões</a> já vinculada à OS <?= e($os['numero']) ?>. Valor da mão de obra pré-preenchido com o total de serviços — ajuste se precisar.</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Garantia (cobertura/retornos) -->
    <?php if ((empty($os['fechada_sem_receita']) && (float)$os['valor_total'] > 0 && !empty($os['garantia_ate'])) || !empty($os['os_origem_numero'])): ?>
    <div class="osd-card mb-3">
      <div class="osd-header d-flex justify-content-between align-items-center" style="padding-bottom:14px">
        <span class="osd-section-title <?= ($os['em_garantia'] ?? false) ? 'text-danger' : '' ?>">
          <i class="bi bi-shield-<?= ($os['em_garantia'] ?? false) ? 'check-fill' : 'x' ?> me-1"></i>
          <?php if (!empty($os['os_origem_numero'])): ?>
            OS de garantia — vinculada à <a href="<?= url('/os/' . $os['os_origem_id']) ?>" class="fw-bold"><?= e($os['os_origem_numero']) ?></a>
          <?php else: ?>
            Garantia do serviço
          <?php endif; ?>
        </span>
        <?php if (($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalGarantia">
          <i class="bi bi-arrow-return-left me-1"></i>Registrar retorno
        </button>
        <?php endif; ?>
      </div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <div class="row g-3 align-items-center">
          <?php if (!empty($os['garantia_ate'])): ?>
          <div class="col-md-4 text-center">
            <div class="small text-body-secondary">Válida até</div>
            <div class="fw-bold fs-5 text-danger"><?= date_br($os['garantia_ate']) ?></div>
          </div>
          <div class="col-md-4 text-center">
            <div class="small text-body-secondary">Prazo</div>
            <div class="fw-bold"><?= $os['garantia_dias'] ?? 0 ?> dias</div>
          </div>
          <div class="col-md-4 text-center">
            <?php $dias = $os['dias_garantia_restantes'] ?? null; ?>
            <div class="small text-body-secondary"><?= $dias !== null && $dias >= 0 ? 'Dias restantes' : 'Expirou há' ?></div>
            <div class="fw-bold text-danger">
              <?php if ($dias !== null): ?><?= abs($dias) ?> dia<?= abs($dias) !== 1 ? 's' : '' ?><?php else: ?>—<?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($os['motivo_retorno'])): ?>
          <div class="col-12">
            <div class="small text-body-secondary fw-semibold">Motivo do retorno</div>
            <div class="small"><?= nl2br(e($os['motivo_retorno'])) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <div class="alert alert-warning mb-0 mt-2 py-2 small">
          <i class="bi bi-exclamation-triangle me-1"></i>Garantia expirada em <?= date_br($os['garantia_ate']) ?>.
        </div>
        <?php endif; ?>

        <?php if (!empty($os['garantias'])): ?>
        <div class="mt-3">
          <div class="small fw-semibold text-body-secondary mb-2">Retornos de garantia registrados:</div>
          <?php foreach ($os['garantias'] as $g): ?>
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-1">
            <i class="bi bi-arrow-return-right text-warning"></i>
            <a href="<?= url('/os/' . $g['id']) ?>" class="fw-semibold text-decoration-none">OS <?= e($g['numero']) ?></a>
            <span class="text-body-secondary small"><?= date_br($g['criado_em']) ?></span>
            <span class="ms-auto text-body-secondary small osd-val"><?= money($g['valor_total']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Lateral -->
  <div class="col-md-4">
    <!-- Financeiro -->
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px"><span class="osd-section-title">Financeiro</span></div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <?php if ($svcList): ?>
        <div class="osd-fin-servicos">
        <?php foreach ($svcList as $s): ?>
        <div class="osd-fin-item"><span class="osd-fin-desc"><?= e($s['descricao']) ?><?= ($s['quantidade'] ?? 1) > 1 ? ' ×'.(int)$s['quantidade'] : '' ?></span><span class="val osd-fin-valor-item"><?= money($s['valor_total']) ?></span></div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($pcList): ?>
        <div class="<?= $svcList ? 'osd-fin-pecas' : '' ?>">
        <?php foreach ($pcList as $p): ?>
        <div class="osd-fin-item"><span class="osd-fin-desc"><?= e($p['descricao']) ?> <span class="fw-semibold" style="color:#0d6efd">(Peças)</span><?= ($p['quantidade'] ?? 1) > 1 ? ' ×'.(int)$p['quantidade'] : '' ?></span><span class="val osd-fin-valor-item"><?= money($p['valor_total']) ?></span></div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!$svcList && !$pcList): ?>
        <div class="osd-fin-item"><span>Serviços e peças</span><span class="val text-body-tertiary">—</span></div>
        <?php endif; ?>
        <?php if ($os['desconto_valor'] > 0): ?>
        <div class="osd-fin-item text-danger"><span>Desconto</span><span class="val">- <?= money($os['desconto_valor']) ?></span></div>
        <?php endif; ?>

        <div class="osd-fin-total"><span>Total</span><span class="val"><?= money($os['valor_total']) ?></span></div>

        <?php if ($os['valor_pago'] > 0):
          // Com a OS ainda aberta, todo valor_pago só pode ter vindo de adiantamento (nada mais
          // escreve nesse campo antes do fechamento) — rótulo inequívoco. Já fechada com algum
          // adiantamento registrado, o valor pode ser uma mistura de adiantamento + pagamento do
          // fechamento, então só avisa que "inclui" um, sem afirmar que é só isso.
          $rotuloPago = $podeFechar ? 'Pago (adiantamento)' : (!empty($adiantamentos) ? 'Pago (inclui adiantamento)' : 'Pago');
        ?>
        <div class="osd-fin-item"><span><?= e($rotuloPago) ?></span><span class="val"><?= money($os['valor_pago']) ?></span></div>
        <div class="osd-fin-item"><span>Saldo</span><span class="val"><?= money($os['valor_total'] - $os['valor_pago']) ?></span></div>
        <?php endif; ?>

        <?php
          $txValor = (float) ($taxaCartaoOS['taxa'] ?? 0);
          if ($txValor > 0):
            $txReceita = (float) ($taxaCartaoOS['receita'] ?? 0);
            $txLiquido = $txReceita - $txValor;
            $txDet = '';
            if (!empty($taxaCartaoOS['taxa_desc']) && preg_match('/\(([^)]+)\)/', $taxaCartaoOS['taxa_desc'], $mDet)) $txDet = $mDet[1];
        ?>
        <hr class="my-2">
        <?php if ($txReceita > (float)$os['valor_total'] + 0.001): ?>
        <div class="osd-fin-item"><span><i class="bi bi-credit-card me-1"></i>Cobrado no cartão</span><span class="val"><?= money($txReceita) ?></span></div>
        <?php endif; ?>
        <div class="osd-fin-item"><span><i class="bi bi-credit-card-2-front me-1"></i>Taxa da maquininha<?= $txDet ? ' ('.e($txDet).')' : '' ?></span><span class="val text-danger">- <?= money($txValor) ?></span></div>
        <div class="osd-fin-item fw-semibold text-success"><span><i class="bi bi-wallet2 me-1"></i>Recebido líquido</span><span class="val"><?= money($txLiquido) ?></span></div>
        <?php endif; ?>

        <?php
          if (!empty($os['garantia_finalizada'])):
        ?>
        <div class="osd-fin-pay pago"><span><i class="bi bi-shield-check me-1"></i>Garantia finalizada</span></div>
        <?php elseif (!empty($os['fechada_sem_receita']) || (float)$os['valor_total'] <= 0): ?>
        <div class="osd-fin-pay pago"><span>Sem débito</span></div>
        <?php elseif ($os['situacao_pagamento'] === 'pago'): ?>
        <div class="osd-fin-pay pago"><span><i class="bi bi-check-circle me-1"></i><?php
          // Com a OS ainda aberta, "pago" só pode ter acontecido via adiantamento (nada mais
          // marca situacao_pagamento='pago' antes do fechamento) — mostra isso e o valor, em vez
          // de um "Pago" mudo que parece a OS já ter sido fechada/entregue.
          if ($podeFechar) {
              echo 'Pago (adiantamento) — ' . money($os['valor_pago']);
          } else {
              echo 'Pago' . (!empty($os['data_pagamento']) ? ' em ' . date_br($os['data_pagamento']) : '');
          }
        ?></span></div>
        <?php else: ?>
        <div class="osd-fin-pay pendente">
          <span><i class="bi bi-hourglass-split me-1"></i><?= ucfirst($os['situacao_pagamento'] ?? 'pendente') ?></span>
          <?php if ($podeFechar && $os['status_tipo'] === 'concluida'): ?><button type="button" class="osd-btn" data-bs-toggle="modal" data-bs-target="#modalFechar">Receber</button><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Andamento -->
    <div class="osd-card mb-3">
      <div class="osd-header" style="padding-bottom:14px"><span class="osd-section-title">Andamento</span></div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <?php $hist = $os['historico'] ?? []; ?>
        <?php if (!$hist): ?>
        <div class="osd-tl-item"><span class="osd-tl-dot atual"></span><div class="osd-tl-txt"><div class="osd-tl-label"><?= e($os['status_nome'] ?? '—') ?></div></div></div>
        <?php else: ?>
        <?php foreach ($hist as $i => $h): ?>
        <div class="osd-tl-item">
          <span class="osd-tl-dot <?= $i === 0 ? 'atual' : 'antigo' ?>"></span>
          <div class="osd-tl-txt">
            <div class="osd-tl-label <?= $i === 0 ? '' : 'antigo' ?>"><?= e($h['status_nov'] ?? '') ?></div>
            <?php if ($h['descricao']): ?><div class="osd-tl-desc"><?= e($h['descricao']) ?></div><?php endif; ?>
            <div class="osd-tl-meta"><?= date_br($h['criado_em'], true) ?> · <?= e($h['usuario_nome'] ?? 'Sistema') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Agenda -->
    <div class="osd-card mb-3">
      <div class="osd-header d-flex align-items-center justify-content-between" style="padding-bottom:14px">
        <span class="osd-section-title">Agenda</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAgendarOs">
          <i class="bi bi-calendar-plus me-1"></i>Agendar novo
        </button>
      </div>
      <div class="osd-full" style="border-top:none;padding-top:0">
        <?php if (!$eventosAgenda): ?>
        <div class="text-muted small">Nenhum evento de agenda vinculado a esta OS.</div>
        <?php else: ?>
        <?php foreach ($eventosAgenda as $ev): ?>
        <div class="osd-tl-item">
          <span class="osd-tl-dot <?= in_array($ev['status'], ['concluido', 'cancelado'], true) ? 'antigo' : 'atual' ?>"></span>
          <div class="osd-tl-txt">
            <div class="osd-tl-label"><a href="<?= url('/agenda?data=' . substr($ev['data_inicio'], 0, 10) . '&view=dia') ?>"><?= e($ev['titulo']) ?></a></div>
            <div class="osd-tl-meta"><?= date_br($ev['data_inicio'], true) ?><?= $ev['usuario_nome'] ? ' · ' . e($ev['usuario_nome']) : '' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (($_SESSION['chat_habilitado'] ?? 1)): ?>
    <!-- Conversa da equipe (chat interno amarrado à OS) -->
    <div class="osd-card">
      <div class="osd-header d-flex align-items-center justify-content-between" style="padding-bottom:14px">
        <span class="osd-section-title"><i class="bi bi-chat-dots-fill text-primary me-2"></i>Conversa da equipe</span>
        <button type="button" id="chatFechar" class="btn btn-sm btn-link text-body-secondary p-0 lh-1" title="Fechar chat" style="text-decoration:none">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="osd-full" style="border-top:none;padding:8px" id="chatCorpo">
        <div id="chatMsgs" class="px-1" style="max-height:340px;overflow-y:auto">
          <div class="text-center text-body-secondary small py-3" id="chatVazio">Nenhuma mensagem ainda.<br>Comece a conversa da equipe sobre esta OS. 👇</div>
        </div>
        <form id="chatForm" class="d-flex gap-1 mt-2">
          <input type="text" id="chatInput" class="form-control form-control-sm" placeholder="Mensagem pra equipe..." maxlength="2000" autocomplete="off">
          <button class="btn btn-primary btn-sm" type="submit" title="Enviar"><i class="bi bi-send"></i></button>
        </form>
      </div>
    </div>
    <script>
    (function () {
      var OS_BASE = '<?= url('/os/' . $os['id']) ?>';
      var URL_MSG = OS_BASE + '/mensagens';
      var CSRF    = '<?= csrf_token() ?>';
      var EU      = <?= (int) ($_SESSION['usuario_id'] ?? 0) ?>;
      var box  = document.getElementById('chatMsgs');
      var form = document.getElementById('chatForm'), input = document.getElementById('chatInput');
      var editando = false, prevCount = -1;
      function esc(s){ var d=document.createElement('div'); d.textContent=(s==null?'':s); return d.innerHTML; }

      function render(data){
        var msgs = (data && data.mensagens) || [];
        var lidoOutros = (data && parseInt(data.lido_outros)) || 0;
        if (!msgs.length){
          box.innerHTML = '<div class="text-center text-body-secondary small py-3">Nenhuma mensagem ainda.<br>Comece a conversa da equipe sobre esta OS. 👇</div>';
          prevCount = 0; return;
        }
        var html = '';
        msgs.forEach(function(m){
          var meu = (parseInt(m.usuario_id) === EU);
          var bg  = meu ? '#dbeafe' : '#f1f5f9';
          var editada = (m.editada == 1) ? ' <span style="opacity:.6">(editada)</span>' : '';
          var acoes = '';
          if (meu){
            var lida  = (parseInt(m.id) <= lidoOutros);
            var check = lida
              ? '<span title="Lida" style="color:#16a34a"><i class="bi bi-check2-all"></i></span>'
              : '<span title="Enviada" style="color:#94a3b8"><i class="bi bi-check2"></i></span>';
            acoes = '<div class="msg-acoes" style="font-size:.72rem;margin-top:3px;display:flex;gap:10px;justify-content:flex-end;align-items:center">'
              + check
              + '<a href="#" data-act="edit" data-id="'+m.id+'" title="Editar" style="color:#64748b"><i class="bi bi-pencil"></i></a>'
              + '<a href="#" data-act="del" data-id="'+m.id+'" title="Apagar" style="color:#dc2626"><i class="bi bi-trash3"></i></a>'
              + '</div>';
          }
          html += '<div class="mb-2 d-flex ' + (meu?'justify-content-end':'justify-content-start') + '">'
            + '<div data-msg="'+m.id+'" style="max-width:85%;background:'+bg+';border-radius:12px;padding:6px 10px;font-size:.85rem">'
            + '<div style="font-size:.7rem;color:#64748b;font-weight:600">'+esc(m.usuario_nome||'—')+' <span style="opacity:.7;font-weight:400">· '+esc(m.quando)+editada+'</span></div>'
            + '<div class="msg-text" style="white-space:pre-wrap;word-break:break-word">'+esc(m.mensagem)+'</div>'
            + acoes + '</div></div>';
        });
        box.innerHTML = html;
        if (msgs.length !== prevCount){ box.scrollTop = box.scrollHeight; }
        prevCount = msgs.length;
      }

      function carregar(){
        if (editando) return;
        fetch(URL_MSG, {headers:{'Accept':'application/json'}})
          .then(function(r){ return r.json(); }).then(function(d){ if(d) render(d); }).catch(function(){});
      }

      form.addEventListener('submit', function(e){
        e.preventDefault();
        var txt = input.value.trim(); if(!txt) return;
        input.value=''; input.focus();
        fetch(URL_MSG, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF}, body:'mensagem='+encodeURIComponent(txt)})
          .then(function(r){ return r.json(); }).then(function(){ carregar(); }).catch(function(){});
      });

      box.addEventListener('click', function(e){
        var a = e.target.closest('[data-act]'); if(!a) return;
        e.preventDefault();
        var id = a.getAttribute('data-id');
        if (a.getAttribute('data-act') === 'del'){
          if(!confirm('Apagar esta mensagem?')) return;
          fetch(OS_BASE+'/mensagens/'+id+'/excluir', {method:'POST', headers:{'X-CSRF-Token':CSRF}})
            .then(function(r){ return r.json(); }).then(function(){ carregar(); }).catch(function(){});
          return;
        }
        var bubble = a.closest('[data-msg]'); if(!bubble) return;
        var textDiv = bubble.querySelector('.msg-text'); if(!textDiv) return;
        var acoesDiv = bubble.querySelector('.msg-acoes');
        editando = true;
        var ta = document.createElement('textarea');
        ta.className='form-control form-control-sm'; ta.rows=2; ta.value=textDiv.textContent; ta.style.marginTop='4px';
        var barra = document.createElement('div'); barra.style.cssText='display:flex;gap:6px;margin-top:4px;justify-content:flex-end';
        barra.innerHTML='<button class="btn btn-sm btn-outline-secondary py-0" data-e="cancel">Cancelar</button><button class="btn btn-sm btn-primary py-0" data-e="save">Salvar</button>';
        textDiv.style.display='none'; if(acoesDiv) acoesDiv.style.display='none';
        bubble.appendChild(ta); bubble.appendChild(barra); ta.focus();
        barra.addEventListener('click', function(ev){
          var b = ev.target.closest('[data-e]'); if(!b) return; ev.preventDefault();
          if (b.getAttribute('data-e')==='save'){
            var novo = ta.value.trim(); if(!novo){ editando=false; carregar(); return; }
            fetch(OS_BASE+'/mensagens/'+id+'/editar', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF}, body:'mensagem='+encodeURIComponent(novo)})
              .then(function(r){ return r.json(); }).then(function(){ editando=false; carregar(); }).catch(function(){ editando=false; });
          } else { editando=false; carregar(); }
        });
      });

      carregar();
      setInterval(carregar, 5000);
    })();
    </script>
    <script>
    (function () {
      var btn = document.getElementById('chatFechar'), corpo = document.getElementById('chatCorpo');
      if (!btn || !corpo) return;
      var K = 'fixaos_chat_fechado';
      function aplicar(fechado) {
        corpo.style.display = fechado ? 'none' : '';
        btn.innerHTML = fechado ? '<i class="bi bi-chevron-down"></i>' : '<i class="bi bi-x-lg"></i>';
        btn.title = fechado ? 'Abrir chat' : 'Fechar chat';
      }
      aplicar(localStorage.getItem(K) === '1');
      btn.addEventListener('click', function () {
        var fechar = (corpo.style.display !== 'none');
        localStorage.setItem(K, fechar ? '1' : '0');
        aplicar(fechar);
      });
    })();
    </script>
    <?php endif; ?>
  </div>
</div>

<!-- Barra de ação primária fixa no rodapé (mobile) -->
<?php if ($acaoPrimaria): ?>
<div class="osd-mobile-bar">
  <?php if (!empty($acaoPrimaria['modal'])): ?>
  <button type="button" class="osd-btn osd-btn-primary" data-bs-toggle="modal" data-bs-target="<?= $acaoPrimaria['modal'] ?>">
    <i class="bi bi-<?= $acaoPrimaria['icon'] ?>"></i><?= e($acaoPrimaria['label']) ?>
  </button>
  <?php else: ?>
  <button type="button" class="osd-btn osd-btn-primary" onclick="<?= $acaoPrimaria['onclick'] ?>">
    <i class="bi bi-<?= $acaoPrimaria['icon'] ?>"></i><?= e($acaoPrimaria['label']) ?>
  </button>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── MODAL RETORNO DE GARANTIA ─────────────────────────── -->
<div class="modal fade" id="modalGarantia" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/garantia') ?>">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-shield-check me-2"></i>Retorno em Garantia — OS <?= e($os['numero']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger py-2 mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-shield-check me-1"></i>
              <strong>Garantia válida</strong> até <?= date_br($os['garantia_ate'] ?? '') ?>
            </div>
            <span class="badge bg-success fs-6">
              <?= $os['dias_garantia_restantes'] ?? 0 ?> dia(s) restante(s)
            </span>
          </div>
        </div>

        <div class="mb-3 p-3 bg-light rounded small">
          <div class="fw-semibold"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
          <div class="text-body-secondary"><?= e($os['equip_tipo']??'') ?></div>
          <?php if ($os['numero_serie']??null): ?><div>S/N: <?= e($os['numero_serie']) ?></div><?php endif; ?>
          <?php if (!empty($os['imei'])): ?><div>IMEI: <?= e($os['imei']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Motivo do retorno *</label>
          <textarea name="motivo_retorno" class="form-control" rows="3" required
            placeholder="Descreva o problema que o cliente está relatando no retorno..."></textarea>
          <div class="form-text">Este texto será o defeito relatado na nova OS de garantia.</div>
        </div>

        <div class="mb-0">
          <label class="form-label fw-semibold">Técnico responsável</label>
          <?php $defTecGar = ($os['tecnico_id'] ?? 0) ?: (int)($_SESSION['usuario_id'] ?? 0); ?>
          <select name="tecnico_id" class="form-select">
            <option value="">— Mesmo técnico (<?= e($os['tecnico_nome'] ?? 'sem técnico') ?>) —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($defTecGar==$t['id'])? 'selected':'' ?>>
              <?= e($t['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="alert alert-info mt-3 mb-0 py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          Uma nova OS do tipo <strong>Garantia</strong> será aberta com valor R$ 0,00,
          vinculada a esta OS original. A garantia da nova OS herda o prazo restante.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold px-4">
          <i class="bi bi-plus-circle me-1"></i>Abrir OS de Garantia
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL FINALIZAR GARANTIA (só OS de retorno) ───────── -->
<?php if (!empty($os['os_origem_id'])): ?>
<div class="modal fade" id="modalFinalizarGarantia" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="<?= url('/os/' . $os['id'] . '/finalizar-garantia') ?>">
      <?= csrf_field() ?>
      <div class="modal-content">
        <div class="modal-header" style="background:rgba(13,202,240,.12)">
          <h5 class="modal-title"><i class="bi bi-shield-check me-2 text-info"></i>Finalizar garantia — OS <?= e($os['numero']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>Retorno em garantia da <strong>OS <?= e($os['os_origem_numero'] ?? $os['os_origem_id']) ?></strong> — <strong>sem cobrança</strong> (coberto pela garantia).
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Solução aplicada</label>
            <textarea name="solucao_aplicada" class="form-control" rows="3" placeholder="O que foi feito no reparo em garantia..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
          </div>
          <div>
            <label class="form-label fw-semibold">Nova garantia (dias)</label>
            <input type="number" name="garantia_dias" class="form-control" min="0" value="<?= (int)($os['garantia_dias'] ?: 90) ?>" style="max-width:150px">
            <div class="form-text">Prazo de garantia do reparo feito agora.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info fw-bold px-4 text-white"><i class="bi bi-check-circle me-1"></i>Finalizar garantia</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ── MODAL AGENDAR (novo evento de agenda vinculado a esta OS) ──────── -->
<div class="modal fade" id="modalAgendarOs" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/agenda') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="tipo" value="ordem_servico">
      <input type="hidden" name="os_id" value="<?= (int) $os['id'] ?>">
      <input type="hidden" name="cliente_id" value="<?= (int) $os['cliente_id'] ?>">
      <input type="hidden" name="redirect_to" value="<?= e(url('/os/' . $os['id'])) ?>">
      <div class="modal-header">
        <h5 class="modal-title">Agendar — OS <?= e($os['numero']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Título *</label>
            <input type="text" name="titulo" class="form-control" required
                   value="OS <?= e($os['numero']) ?><?= !empty($os['equip_tipo']) ? ' - ' . e($os['equip_tipo']) : '' ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Responsável</label>
            <select name="usuario_id" class="form-select">
              <?php foreach ($tecnicos as $t): ?>
              <option value="<?= $t['id'] ?>" <?= (int) $t['id'] === (int) ($os['tecnico_id'] ?? 0) ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Início *</label>
            <input type="datetime-local" name="data_inicio" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Agendar</button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL FECHAR OS ───────────────────────────────────── -->
<div class="modal fade" id="modalFechar" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/fechar') ?>">
      <?= csrf_field() ?>
      <div class="modal-header <?= $semConserto ? 'bg-danger' : 'bg-success' ?> text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-<?= $semConserto ? 'x-circle' : 'check-circle' ?> me-2"></i>
          <?= $semConserto ? 'Fechar como ' . e($os['status_nome']) . ' — OS ' : 'Fechar Ordem de Serviço ' ?><?= e($os['numero']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <?php if ($semConserto): ?>
        <div class="alert alert-danger d-flex gap-2 mb-4">
          <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
          <div>
            <?php if ($recusado): ?>
            <strong>Orçamento recusado.</strong> O cliente não aprovou o orçamento apresentado. Esta OS
            será encerrada sem cobrança de serviços ou peças. O equipamento será devolvido ao cliente no
            estado em que está.
            <?php else: ?>
            <strong>Sem conserto.</strong> Esta OS será encerrada sem cobrança de serviços ou peças.
            O equipamento será devolvido ao cliente no estado em que está.
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-light border mb-4">
          <div class="row text-center g-2">
            <div class="col-4">
              <div class="text-body-secondary small">Serviços</div>
              <div class="fw-bold"><?= money($totalServicos) ?></div>
            </div>
            <div class="col-4">
              <div class="text-body-secondary small">Peças</div>
              <div class="fw-bold"><?= money($totalPecas) ?></div>
            </div>
            <div class="col-4">
              <div class="text-body-secondary small">Total</div>
              <div class="fw-bold fs-5 text-danger"><?= money($os['valor_total']) ?></div>
            </div>
          </div>
          <?php if ((float) ($os['valor_pago'] ?? 0) > 0): ?>
          <hr class="my-2">
          <div class="row text-center g-2">
            <div class="col-6">
              <div class="text-body-secondary small">Já adiantado</div>
              <div class="fw-semibold text-success"><?= money($os['valor_pago']) ?></div>
            </div>
            <div class="col-6">
              <div class="text-body-secondary small">Falta receber</div>
              <div class="fw-semibold"><?= money(max(0, $os['valor_total'] - $os['valor_pago'])) ?></div>
            </div>
          </div>
          <div class="small text-body-secondary mt-1"><i class="bi bi-info-circle me-1"></i>Preencha abaixo só o valor que ainda falta cobrar.</div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">Solução aplicada</label>
            <textarea name="solucao_aplicada" class="form-control" rows="2"
              placeholder="Descreva o que foi feito para resolver o defeito..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
          </div>
          <?php if (!$semConserto): ?>
          <div class="col-12">
            <label class="form-label fw-semibold">Laudo técnico</label>
            <textarea name="laudo_tecnico" class="form-control" rows="2"
              placeholder="Diagnóstico técnico detalhado..."><?= e($os['laudo_tecnico'] ?? '') ?></textarea>
          </div>
          <?php endif; ?>
          <div class="col-12">
            <label class="form-label fw-semibold">Observações para o cliente</label>
            <textarea name="observacoes_cliente" class="form-control" rows="2"
              placeholder="Instruções de uso, cuidados, recomendações..."><?= e($os['observacoes_cliente'] ?? '') ?></textarea>
          </div>

          <?php if (!$semConserto): ?>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Garantia (dias)</label>
            <div class="input-group">
              <input type="number" name="garantia_dias" class="form-control"
                value="<?= e($os['garantia_dias'] ?? 90) ?>" min="0" id="garantiaDias"
                oninput="calcularGarantia(this.value)">
              <span class="input-group-text">dias</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Válida até</label>
            <input type="text" class="form-control bg-light fw-semibold text-danger"
              id="garantiaAte" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Desconto</label>
            <div class="input-group">
              <input type="text" name="desconto_valor" id="descontoValor" class="form-control"
                placeholder="0,00" value="<?= $os['desconto_valor'] > 0 ? number_format($os['desconto_valor'], 2, ',', '.') : '' ?>"
                oninput="recalcularTotal()">
              <select name="desconto_tipo" id="descontoTipo" class="form-select" style="max-width:80px" onchange="recalcularTotal()">
                <option value="valor">R$</option>
                <option value="percentual">%</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Total com desconto</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" id="totalComDesconto" class="form-control bg-light fw-bold text-success" readonly
                value="<?= number_format($os['valor_total'], 2, ',', '.') ?>">
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label fw-semibold mb-0">Pagamento</label>
              <span id="osRestante" class="small fw-semibold"></span>
            </div>
            <div id="pagamentosOs"></div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-1">
              <button type="button" id="btnAddPagamentoOs" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Adicionar forma de pagamento</button>
              <div id="osRepassarWrap" class="d-flex gap-3 align-items-center" style="display:none">
                <span class="small fw-semibold text-body-secondary">Quem paga a taxa:</span>
                <div class="form-check mb-0"><input class="form-check-input" type="radio" name="cartao_repassar" value="0" id="repEmpresa" checked><label class="form-check-label small" for="repEmpresa">A empresa</label></div>
                <div class="form-check mb-0"><input class="form-check-input" type="radio" name="cartao_repassar" value="1" id="repCliente"><label class="form-check-label small" for="repCliente">O cliente</label></div>
              </div>
            </div>
            <input type="hidden" name="pagamentos" id="pagamentosOsInput">
          </div>
          <?php else: ?>
          <input type="hidden" name="garantia_dias" value="0">
          <input type="hidden" name="desconto_valor" value="0">
          <input type="hidden" name="desconto_tipo" value="valor">
          <input type="hidden" name="valor_pago" value="0">
          <?php endif; ?>
        </div>

        <div class="alert alert-<?= $semConserto ? 'danger' : 'warning' ?> mt-3 mb-0 py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          <?php if ($semConserto): ?>
            Ao fechar, a OS será encerrada como <strong><?= e($os['status_nome']) ?></strong>, sem cobrança e com garantia zerada.
          <?php else: ?>
            Ao fechar, a OS será marcada como <strong>Concluída</strong>, a data de conclusão e a garantia serão registradas, e você será redirecionado para o <strong>comprovante de entrega</strong>.
          <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold px-4">
          <i class="bi bi-check-circle me-1"></i>Confirmar fechamento
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL SERVIÇO ─────────────────────────────────────── -->
<div class="modal fade" id="modalServico" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/servicos') ?>" id="formServico">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalServicoTitulo">Novo Serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="servico_id" id="svcId">
        <div class="mb-3 position-relative">
          <label class="form-label fw-semibold">Descrição *</label>
          <input type="text" name="descricao" id="svcDesc" class="form-control" required autocomplete="off" placeholder="Ex: Troca de tela, Diagnóstico...">
          <div id="svcSugestoes" class="list-group position-absolute w-100 shadow" style="z-index:20;max-height:220px;overflow:auto"></div>
          <div class="form-text">Digite para buscar no <a href="<?= url('/servicos') ?>" target="_blank">catálogo de serviços</a> ou escreva algo avulso.</div>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Quantidade</label>
            <input type="text" name="quantidade" id="svcQtd" class="form-control" value="1" required placeholder="1">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Valor Unitário</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_unitario" id="svcVal" class="form-control" required placeholder="0,00">
            </div>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label fw-semibold">Técnico responsável</label>
          <?php $defTecSvc = ($os['tecnico_id'] ?? 0) ?: (int)($_SESSION['usuario_id'] ?? 0); ?>
          <select name="tecnico_id" class="form-select">
            <option value="">— Sem técnico —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($defTecSvc == $t['id']) ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>
<button id="btnAbrirModalServico" data-bs-toggle="modal" data-bs-target="#modalServico" style="display:none"></button>

<!-- ── MODAL PEÇA ────────────────────────────────────────── -->
<div class="modal fade" id="modalPeca" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/pecas') ?>" id="formPeca">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalPecaTitulo">Nova Peça</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="peca_id" id="pecaId">
        <input type="hidden" name="produto_id" id="pecaProdId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Buscar no estoque</label>
          <div class="position-relative">
            <input type="text" id="pecaBusca" class="form-control" placeholder="Digite código ou nome do produto..." autocomplete="off">
            <div id="pecaSugestoes" class="list-group position-absolute w-100 shadow z-3" style="display:none;max-height:180px;overflow-y:auto;top:100%"></div>
          </div>
          <div class="form-text">Opcional — selecione para baixar o estoque automaticamente.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição da peça *</label>
          <input type="text" name="descricao" id="pecaDesc" class="form-control" required placeholder="Ex: Display LCD, Bateria 3000mAh...">
        </div>

        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Quantidade</label>
            <input type="text" name="quantidade" id="pecaQtd" class="form-control" value="1" required placeholder="1">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Valor Unitário</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_unitario" id="pecaVal" class="form-control" required placeholder="0,00">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>
<button id="btnAbrirModalPeca" data-bs-toggle="modal" data-bs-target="#modalPeca" style="display:none"></button>

<!-- ── MODAL ADIANTAMENTO ────────────────────────────────── -->
<div class="modal fade" id="modalAdiantamento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/adiantamentos') ?>" id="formAdiantamento">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Registrar adiantamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-body-secondary">Valor recebido do cliente antes do fechamento da OS (ex.: sinal pra comprar uma peça cara). Vira receita no Financeiro na hora.</p>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Valor *</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor" id="adValor" class="form-control" required placeholder="0,00">
            </div>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Forma de pagamento *</label>
            <select name="forma_pagamento" id="adForma" class="form-select" required>
              <option value="">Selecione...</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="pix">PIX</option>
              <option value="pix_maquininha" style="color:#dc3545;font-weight:600">💳 PIX (maquininha)</option>
              <option value="cartao_credito">💳 Cartão de crédito</option>
              <option value="cartao_debito">💳 Cartão de débito</option>
              <option value="transferencia">Transferência</option>
              <option value="boleto">Boleto</option>
            </select>
          </div>
          <div class="col-6 d-none" id="adParcelasWrap">
            <label class="form-label fw-semibold">Parcelas</label>
            <select name="parcelas" id="adParcelas" class="form-select">
              <?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i ?>"><?= $i ?>x</option><?php endfor; ?>
            </select>
          </div>
          <div class="col-12 d-none" id="adTaxaWrap">
            <div class="small text-body-secondary mb-1" id="adTaxaTexto"></div>
            <div class="d-flex gap-3">
              <div class="form-check mb-0"><input class="form-check-input" type="radio" name="repassar" value="0" id="adRepEmpresa" checked><label class="form-check-label small" for="adRepEmpresa">A empresa paga a taxa</label></div>
              <div class="form-check mb-0"><input class="form-check-input" type="radio" name="repassar" value="1" id="adRepCliente"><label class="form-check-label small" for="adRepCliente">O cliente paga a taxa</label></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Registrar</button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL EXCLUIR OS (só admin) ─────────────────────────── -->
<?php if (\App\Core\Auth::isAdmin()): ?>
<div class="modal fade" id="modalExcluirOsDetalhe" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/excluir') ?>">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Excluir OS <?= e($os['numero']) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-flex gap-2 mb-3">
          <i class="bi bi-trash3 fs-4"></i>
          <div><strong>Esta ação é IRREVERSÍVEL.</strong> A OS, seu histórico, serviços, peças e lançamentos financeiros serão apagados <strong>permanentemente</strong> e não poderão ser recuperados.</div>
        </div>
        <p class="mb-2 small text-body-secondary"><i class="bi bi-shield-check me-1"></i>A exclusão fica registrada no <strong>Registro de Ações</strong> (quem excluiu e quando).</p>
        <label class="form-label small fw-semibold mb-1"><i class="bi bi-lock-fill me-1"></i>Confirme sua senha de login para excluir</label>
        <input type="password" name="senha" class="form-control" autocomplete="off" placeholder="Sua senha" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash me-1"></i>Excluir permanentemente</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalFotoEntrada" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark border-0" style="position:relative">
      <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" style="z-index:2"></button>
      <button type="button" id="btnFotoEntradaAnterior" class="btn btn-dark border-0 position-absolute top-50 start-0 translate-middle-y ms-2" style="z-index:2;opacity:.85">
        <i class="bi bi-chevron-left fs-3"></i>
      </button>
      <img id="modalFotoEntradaImg" src="" class="w-100" style="border-radius:8px;max-height:80vh;object-fit:contain">
      <button type="button" id="btnFotoEntradaProxima" class="btn btn-dark border-0 position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:2;opacity:.85">
        <i class="bi bi-chevron-right fs-3"></i>
      </button>
      <div class="text-center text-white-50 small pb-2" id="modalFotoEntradaContador"></div>
    </div>
  </div>
</div>
<script>
(function () {
  var urls = <?= json_encode(array_map(fn ($f) => url('/uploads/' . $f['arquivo']), $fotosEntrada)) ?>;
  var img = document.getElementById('modalFotoEntradaImg'),
      contador = document.getElementById('modalFotoEntradaContador'),
      idx = 0;

  function mostrar(i) {
    idx = (i + urls.length) % urls.length;
    img.src = urls[idx];
    contador.textContent = urls.length > 1 ? (idx + 1) + ' de ' + urls.length : '';
  }

  document.querySelectorAll('.osd-foto-entrada').forEach(function (btn) {
    btn.addEventListener('click', function () { mostrar(parseInt(this.dataset.fotoIndex, 10)); });
  });

  var btnAnt = document.getElementById('btnFotoEntradaAnterior'), btnProx = document.getElementById('btnFotoEntradaProxima');
  if (urls.length > 1) {
    btnAnt.addEventListener('click', function () { mostrar(idx - 1); });
    btnProx.addEventListener('click', function () { mostrar(idx + 1); });
  } else {
    btnAnt.style.display = 'none';
    btnProx.style.display = 'none';
  }

  document.getElementById('modalFotoEntrada').addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft') mostrar(idx - 1);
    if (e.key === 'ArrowRight') mostrar(idx + 1);
  });
})();
</script>

<!-- Adicionar fotos do estado de entrada daqui: upload local ou pareamento com o celular via QR
     (mesmo mecanismo de pareamento do formulário de OS — ver ScannerController, modo
     "fotos_entrada"). Se quem está nesta tela já é o próprio celular, pula o QR (não faz
     sentido escanear um QR com o mesmo aparelho) e usa a câmera direto. -->
<div class="modal fade" id="modalFeScanner" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0"><i class="bi bi-phone-fill me-1"></i>Fotos do estado de entrada</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="small text-muted mb-2">Abra a câmera do celular (logado no FixaOS na mesma empresa) e escaneie:</p>
        <div id="feScannerQrBox" class="d-flex justify-content-center align-items-center mb-2" style="min-height:186px">
          <div class="spinner-border text-secondary"></div>
        </div>
        <div class="small text-muted">ou acesse <strong><?= e(parse_url(url('/'), PHP_URL_HOST)) ?>/scan</strong> e digite:</div>
        <div id="feScannerCodigo" class="fw-bold fs-4" style="letter-spacing:.2em">••••••</div>
        <div id="feScannerStatus" class="mt-2 small"><span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…</div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var OS_ID = <?= (int) $os['id'] ?>, CSRF = '<?= csrf_token() ?>';
  var inputArquivo = document.getElementById('feInputArquivo');
  var msg = document.getElementById('feMsg');
  var _feScanToken = null, _feScanTimer = null;

  function feComprimir(file) {
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
          var max = 1280, w = img.width, h = img.height;
          if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
          else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
          var c = document.createElement('canvas');
          c.width = w; c.height = h;
          var ctx = c.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
          ctx.drawImage(img, 0, 0, w, h);
          resolve(c.toDataURL('image/jpeg', 0.7));
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  function feEnviar(fotos) {
    if (!fotos.length) return;
    msg.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
    fetch('<?= url('/os/' . $os['id'] . '/fotos-entrada') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ fotos: fotos }),
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { msg.innerHTML = '<span class="text-danger">' + (j.erro || 'Não foi possível salvar.') + '</span>'; return; }
        msg.innerHTML = '<span class="text-success">✓ ' + j.salvas + ' foto(s) salva(s). Atualizando...</span>';
        setTimeout(function () { location.reload(); }, 700);
      })
      .catch(function () { msg.innerHTML = '<span class="text-danger">Falha de conexão.</span>'; });
  }

  inputArquivo.addEventListener('change', async function () {
    var files = [].slice.call(this.files);
    this.value = '';
    if (!files.length) return;
    var fotos = [];
    for (var i = 0; i < files.length; i++) {
      if (!files[i].type.startsWith('image/')) continue;
      fotos.push(await feComprimir(files[i]));
    }
    feEnviar(fotos);
  });

  // Touch + tela estreita = o próprio aparelho tem câmera (é o celular), não faz sentido pedir
  // pra escanear um QR com ele mesmo — usa o input de arquivo direto (a maioria dos navegadores
  // mobile já oferece "Câmera" como opção no seletor).
  function feTemCameraPropria() {
    return ('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 991;
  }

  document.getElementById('btnFeCelular').addEventListener('click', function () {
    if (feTemCameraPropria()) { inputArquivo.click(); return; }

    var modalEl = document.getElementById('modalFeScanner');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('feScannerQrBox').innerHTML = '<div class="spinner-border text-secondary"></div>';
    document.getElementById('feScannerCodigo').textContent = '••••••';
    document.getElementById('feScannerStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…';
    modal.show();
    modalEl.addEventListener('hidden.bs.modal', function () { if (_feScanTimer) { clearInterval(_feScanTimer); _feScanTimer = null; } }, { once: true });

    fetch('<?= url('/scanner/nova') ?>', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ modo: 'fotos_entrada' }),
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        _feScanToken = j.token;
        document.getElementById('feScannerQrBox').innerHTML = '<img src="' + j.qr + '" alt="QR Code" style="width:186px;height:186px">';
        document.getElementById('feScannerCodigo').textContent = j.codigo;
        _feScanTimer = setInterval(fePollScanner, 2000);
      })
      .catch(function () {
        document.getElementById('feScannerStatus').innerHTML = '<span class="text-danger">Erro ao gerar o QR. Feche e tente de novo.</span>';
      });
  });

  function fePollScanner() {
    if (!_feScanToken) return;
    fetch('<?= url('/scanner/status') ?>?token=' + encodeURIComponent(_feScanToken))
      .then(function (r) {
        if (!r.ok) {
          if (r.status === 410) {
            document.getElementById('feScannerStatus').innerHTML = '<span class="text-danger">A sessão expirou. Feche e abra de novo.</span>';
            clearInterval(_feScanTimer); _feScanTimer = null;
          }
          return null;
        }
        return r.json();
      })
      .then(function (j) {
        if (!j || j.status !== 'pronto' || !j.resultado) return;
        clearInterval(_feScanTimer); _feScanTimer = null;
        if (j.erro) {
          document.getElementById('feScannerStatus').innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + j.erro + '</span>';
          setTimeout(function () { bootstrap.Modal.getInstance(document.getElementById('modalFeScanner')).hide(); }, 1500);
          return;
        }
        var fotos = (j.resultado.fotos || []);
        document.getElementById('feScannerStatus').innerHTML = '<span class="text-success fw-semibold">✅ ' + fotos.length + ' foto(s) recebida(s)!</span>';
        setTimeout(function () {
          bootstrap.Modal.getInstance(document.getElementById('modalFeScanner')).hide();
          feEnviar(fotos);
        }, 900);
      });
  }
})();

// Excluir foto do estado de entrada — botão só aparece pra admin (ver \App\Core\Auth::isAdmin()
// na view), mas o endpoint também checa admin no servidor, então mesmo adulterando o HTML não
// dá pra excluir sem ser admin de verdade.
function excluirFotoEntradaShow(fotoId, btn) {
  if (!confirm('Excluir esta foto do estado de entrada? Não tem como desfazer.')) return;
  btn.disabled = true;
  fetch('<?= url('/os/' . $os['id'] . '/fotos-entrada/') ?>' + fotoId + '/excluir', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (j.ok) { location.reload(); return; }
      btn.disabled = false;
      alert(j.erro || 'Não foi possível excluir a foto.');
    })
    .catch(function () {
      btn.disabled = false;
      alert('Falha de conexão ao excluir a foto.');
    });
}
</script>

<script>
// ── Desconto — recalcular total ───────────────────────────
const totalOriginal = <?= (float)($os['valor_total'] ?? 0) ?>;

function recalcularTotal() {
  const descontoInput = document.getElementById('descontoValor');
  const descontoTipoEl = document.getElementById('descontoTipo');
  const totalEl       = document.getElementById('totalComDesconto');
  if (!descontoInput || !totalEl || !descontoTipoEl) return;
  const tipo = descontoTipoEl.value;

  const descontoRaw = parseFloat(descontoInput.value.replace(',','.')) || 0;
  let desconto = tipo === 'percentual'
    ? totalOriginal * descontoRaw / 100
    : descontoRaw;

  desconto = Math.min(desconto, totalOriginal);
  const novoTotal = Math.max(0, totalOriginal - desconto);

  totalEl.value = novoTotal.toFixed(2).replace('.', ',');

  // Só há 1 forma de pagamento e o usuário não mexeu nela manualmente: acompanha o
  // desconto automaticamente. Se já dividiu em várias formas ou editou o valor à mão,
  // não sobrescreve (evita apagar um ajuste manual do usuário).
  if (typeof linhasPagOs !== 'undefined' && linhasPagOs.length === 1 && !pagOsValorManual) {
    linhasPagOs[0].valor = novoTotal.toFixed(2).replace('.', ',');
    if (typeof renderPagamentosOs === 'function') renderPagamentosOs();
  }
  if (typeof atualizarPagamentosOs === 'function') atualizarPagamentosOs();
}

// ── Cartão: parcelas + taxa (config do admin) ─────────────
var TAXAS_CARTAO = <?= json_encode(json_decode(($taxasCartao ?? '') ?: '{}', true) ?: new \stdClass()) ?>;
function brNum(n){ return (isFinite(n)?n:0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function parseBr(v){ return parseFloat((v||'0').toString().replace(/\./g,'').replace(',','.'))||0; }
function totalFechamento(){ var el = document.getElementById('totalComDesconto'); return el ? parseBr(el.value) : 0; }

var linhasPagOs = [{ forma: 'dinheiro', valor: (document.getElementById('totalComDesconto') ? document.getElementById('totalComDesconto').value : '') }];
// true assim que o usuário editar o valor da linha de pagamento à mão — a partir daí o
// desconto para de sobrescrever esse valor automaticamente (recalcularTotal acima).
var pagOsValorManual = false;
function taxaPadraoOs(forma, parcelas){
  if (forma === 'cartao_debito') return TAXAS_CARTAO.debito || 0;
  if (forma === 'cartao_credito') { var c = TAXAS_CARTAO.credito || {}; return c[parcelas] !== undefined ? c[parcelas] : 0; }
  if (forma === 'pix_maquininha') return TAXAS_CARTAO.pix || 0;
  return 0;
}
function renderPagamentosOs(){
  var cont = document.getElementById('pagamentosOs');
  if (!cont) return;
  var temCartao = linhasPagOs.some(function(l){ return l.forma === 'cartao_credito' || l.forma === 'cartao_debito' || l.forma === 'pix_maquininha'; });
  var repassarWrap = document.getElementById('osRepassarWrap');
  if (repassarWrap) repassarWrap.style.display = temCartao ? 'flex' : 'none';

  if (!linhasPagOs.length){
    cont.innerHTML = '<div class="text-body-secondary small border rounded p-2 mb-2">Nenhum pagamento registrado — a OS será fechada sem cobrança registrada.</div>';
    return;
  }
  cont.innerHTML = linhasPagOs.map(function (lin, i) {
    var ehCredito = lin.forma === 'cartao_credito';
    var ehCartao  = ehCredito || lin.forma === 'cartao_debito';
    var mostrarTaxa = ehCartao || lin.forma === 'pix_maquininha';
    var parcelasOpts = '';
    if (ehCredito) {
      var cred = TAXAS_CARTAO.credito || {};
      for (var p = 1; p <= 12; p++) {
        if (cred[p] === undefined && p !== 1) continue;
        parcelasOpts += '<option value="' + p + '"' + (Number(lin.parcelas) === p ? ' selected' : '') + '>' + p + 'x' + (cred[p] !== undefined ? ' — ' + brNum(parseFloat(cred[p])) + '%' : '') + '</option>';
      }
    }
    return '<div class="border rounded p-2 mb-2" data-linha="' + i + '">' +
      '<div class="d-flex justify-content-end mb-1"><button type="button" class="btn btn-sm btn-link text-danger p-0 lh-1 os-rem-linha" data-i="' + i + '"><i class="bi bi-x-lg"></i></button></div>' +
      '<div class="row g-2">' +
        '<div class="col-6"><select class="form-select form-select-sm os-linha-forma" data-i="' + i + '">' +
          '<option value="dinheiro"' + (lin.forma==='dinheiro'?' selected':'') + '>Dinheiro</option>' +
          '<option value="pix"' + (lin.forma==='pix'?' selected':'') + '>PIX</option>' +
          '<option value="pix_maquininha" style="color:#dc3545;font-weight:600"' + (lin.forma==='pix_maquininha'?' selected':'') + '>💳 PIX (maquininha)</option>' +
          '<option value="cartao_credito"' + (lin.forma==='cartao_credito'?' selected':'') + '>Cartão de Crédito</option>' +
          '<option value="cartao_debito"' + (lin.forma==='cartao_debito'?' selected':'') + '>Cartão de Débito</option>' +
          '<option value="transferencia"' + (lin.forma==='transferencia'?' selected':'') + '>Transferência</option>' +
          '<option value="boleto"' + (lin.forma==='boleto'?' selected':'') + '>Boleto</option>' +
        '</select></div>' +
        '<div class="col-6"><input type="text" class="form-control form-control-sm os-linha-valor" data-i="' + i + '" inputmode="decimal" placeholder="0,00" value="' + (lin.valor || '') + '"></div>' +
      '</div>' +
      (mostrarTaxa ? (
        '<div class="row g-2 mt-1">' +
          (ehCredito ? '<div class="col-6"><select class="form-select form-select-sm os-linha-parcelas" data-i="' + i + '">' + parcelasOpts + '</select></div>' : '') +
          '<div class="col-' + (ehCredito ? '6' : '12') + '"><div class="input-group input-group-sm"><input type="number" class="form-control os-linha-taxa" data-i="' + i + '" min="0" max="100" step="0.01" value="' + (lin.taxa != null ? lin.taxa : '') + '" readonly title="Taxa definida em Configurações → Cartões"><span class="input-group-text">%</span></div></div>' +
        '</div>'
      ) : '') +
      '</div>';
  }).join('');

  cont.querySelectorAll('.os-linha-forma').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var i = +this.dataset.i;
      linhasPagOs[i].forma = this.value;
      if (this.value === 'cartao_credito' || this.value === 'cartao_debito' || this.value === 'pix_maquininha') {
        linhasPagOs[i].parcelas = linhasPagOs[i].parcelas || 1;
        linhasPagOs[i].taxa = taxaPadraoOs(this.value, linhasPagOs[i].parcelas);
      }
      renderPagamentosOs(); atualizarPagamentosOs();
    });
  });
  cont.querySelectorAll('.os-linha-valor').forEach(function (inp) {
    inp.addEventListener('input', function () { pagOsValorManual = true; linhasPagOs[+this.dataset.i].valor = this.value; atualizarPagamentosOs(); });
  });
  cont.querySelectorAll('.os-linha-parcelas').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var i = +this.dataset.i;
      linhasPagOs[i].parcelas = this.value;
      linhasPagOs[i].taxa = taxaPadraoOs(linhasPagOs[i].forma, this.value);
      renderPagamentosOs();
    });
  });
  cont.querySelectorAll('.os-linha-taxa').forEach(function (inp) {
    inp.addEventListener('input', function () { linhasPagOs[+this.dataset.i].taxa = this.value; });
  });
  cont.querySelectorAll('.os-rem-linha').forEach(function (b) {
    b.addEventListener('click', function () { linhasPagOs.splice(+this.dataset.i, 1); renderPagamentosOs(); atualizarPagamentosOs(); });
  });
}
var btnAddPagamentoOsEl = document.getElementById('btnAddPagamentoOs');
if (btnAddPagamentoOsEl) btnAddPagamentoOsEl.addEventListener('click', function () {
  linhasPagOs.push({ forma: 'dinheiro', valor: '' });
  renderPagamentosOs(); atualizarPagamentosOs();
});
function atualizarPagamentosOs(){
  var total = totalFechamento();
  var soma = linhasPagOs.reduce(function (s, l) { return s + (parseFloat((l.valor||'0').toString().replace(',','.'))||0); }, 0);
  var restante = total - soma;
  var el = document.getElementById('osRestante');
  if (el) {
    if (restante > 0.004) { el.className = 'small text-danger fw-semibold'; el.textContent = 'Falta ' + brNum(restante); }
    else if (linhasPagOs.length) { el.className = 'small text-success fw-semibold'; el.textContent = 'Coberto ✓'; }
    else { el.className = 'small text-body-secondary fw-semibold'; el.textContent = ''; }
  }
  var inp = document.getElementById('pagamentosOsInput');
  if (inp) {
    inp.value = JSON.stringify(linhasPagOs
      .filter(function (l) { return l.forma && parseBr(l.valor) > 0; })
      .map(function (l) { return { forma: l.forma, valor: parseBr(l.valor), parcelas: l.parcelas || 1, taxa: parseBr(l.taxa) }; }));
  }
}
renderPagamentosOs();
atualizarPagamentosOs();
var formFecharEl = document.querySelector('#modalFechar form');
if (formFecharEl) formFecharEl.addEventListener('submit', function(){ atualizarPagamentosOs(); });

// ── Garantia — cálculo ao vivo ────────────────────────────
function calcularGarantia(dias) {
  const d = new Date();
  d.setDate(d.getDate() + parseInt(dias || 0));
  const dia = String(d.getDate()).padStart(2,'0');
  const mes = String(d.getMonth()+1).padStart(2,'0');
  document.getElementById('garantiaAte').value = `${dia}/${mes}/${d.getFullYear()}`;
}
document.addEventListener('DOMContentLoaded', function() {
  const dias = document.getElementById('garantiaDias');
  if (dias) calcularGarantia(dias.value);
});

// O dropdown de status vive dentro de .osd-card, que tem overflow:hidden (pra respeitar os
// cantos arredondados do card) — isso cortava o painel (e o botão Salvar) sempre que ele
// esticava além dos limites do card. Solução: mover o próprio <div class="dropdown-menu"> pra
// dentro do <body> enquanto estiver aberto (o Popper do Bootstrap continua posicionando
// certinho em relação ao badge, ele usa a posição na tela, não a posição no DOM) e devolver
// pro lugar original ao fechar.
(function () {
  var wrap = document.getElementById('osdStatusDropdownWrap');
  var menu = wrap.querySelector('.dropdown-menu');
  var marcador = document.createComment('osd-status-dropdown');
  wrap.addEventListener('show.bs.dropdown', function () {
    wrap.insertBefore(marcador, menu);
    document.body.appendChild(menu);
  });
  wrap.addEventListener('hidden.bs.dropdown', function () {
    marcador.parentNode.insertBefore(menu, marcador);
    marcador.remove();
  });
})();

function selecionarStatusOpt(el) {
  document.getElementById('novoStatus').value = el.getAttribute('data-id');
  el.parentElement.querySelectorAll('.osd-status-opt.ativo').forEach(function (o) { o.classList.remove('ativo'); });
  el.classList.add('ativo');
}

// ── Status via AJAX — badge clicável no cabeçalho ─────────
function osAtualizarStatus(statusId, descricao, btn) {
  var orig = btn ? btn.innerHTML : null;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
  return fetch('<?= url('/os/' . $os['id'] . '/status') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({ status_id: statusId, descricao: descricao || '' })
  }).then(function (r) {
    return r.text().then(function (txt) {
      var json; try { json = JSON.parse(txt); } catch (e) { json = null; }
      if (!r.ok || !json) {
        throw new Error('HTTP ' + r.status + (txt ? ' — ' + txt.slice(0, 300) : ''));
      }
      return json;
    });
  }).then(function (json) {
    if (json.success) { location.reload(); }
    else {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
      alert('Não foi possível mudar o status: ' + (json.error || 'erro desconhecido.'));
    }
    return json;
  }).catch(function (err) {
    if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    alert('Não foi possível mudar o status. Detalhe técnico: ' + err.message + '\n\nTire um print desta mensagem se o problema continuar.');
  });
}
document.getElementById('btnSalvarStatus').addEventListener('click', function () {
  var statusId = document.getElementById('novoStatus').value;
  var descricao = document.getElementById('statusDescricao').value;
  osAtualizarStatus(statusId, descricao, this);
});
function marcarComoPronto(btn) {
  osAtualizarStatus(<?= (int) $statusProntoId ?>, '', btn);
}
function cancelarOs() {
  if (!confirm('Cancelar esta OS? O status muda para Cancelada — isso não exclui nada, dá pra reverter escolhendo outro status depois.')) return;
  osAtualizarStatus(<?= (int) $statusCanceladaId ?>, 'OS cancelada.');
}

// Modal de resultado do envio por WhatsApp (sucesso/erro) — criado sob demanda
function waResultado(ok, msg) {
  var el = document.getElementById('waResultModal');
  if (!el) {
    el = document.createElement('div');
    el.className = 'modal fade'; el.id = 'waResultModal'; el.tabIndex = -1;
    el.innerHTML =
      '<div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0" style="border-radius:16px">' +
      '<div class="modal-body text-center p-4">' +
      '<div id="waResIcon" class="mb-3"></div>' +
      '<h5 id="waResTitulo" class="fw-bold mb-2"></h5>' +
      '<p id="waResMsg" class="text-body-secondary mb-4" style="font-size:.92rem;line-height:1.6"></p>' +
      '<button type="button" id="waResBtn" class="btn px-4" data-bs-dismiss="modal">Entendi</button>' +
      '</div></div></div>';
    document.body.appendChild(el);
  }
  var icon = el.querySelector('#waResIcon'), tit = el.querySelector('#waResTitulo'),
      m = el.querySelector('#waResMsg'), btn = el.querySelector('#waResBtn');
  if (ok) {
    icon.innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:58px;color:#22c55e"></i>';
    tit.textContent = 'Mensagem enviada com sucesso!';
    m.innerHTML = msg || 'O cliente já recebeu no WhatsApp. 🎉';
    btn.className = 'btn btn-success px-4';
  } else {
    icon.innerHTML = '<i class="bi bi-x-circle-fill" style="font-size:58px;color:#ef4444"></i>';
    tit.textContent = 'Erro no envio';
    m.innerHTML = (msg ? '<strong>' + msg + '</strong><br><br>' : '') +
      'Verifique a <strong>conexão com o WhatsApp</strong> (Configurações → WhatsApp da Empresa) ou se o <strong>número de WhatsApp</strong> do cliente está preenchido corretamente.';
    btn.className = 'btn btn-danger px-4';
  }
  bootstrap.Modal.getOrCreateInstance(el).show();
}

// Enviar PDF (abertura/orcamento/fechamento) pelo WhatsApp do cliente via Evolution
async function enviarPdfWa(tipo, btn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  try {
    const r = await fetch('<?= url('/os/' . $os['id'] . '/whatsapp-pdf') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
      body: JSON.stringify({ tipo })
    });
    const j = await r.json();
    if (j.success) waResultado(true, 'O PDF foi enviado no WhatsApp do cliente.');
    else waResultado(false, j.error || '');
  } catch (e) {
    waResultado(false, 'Não foi possível concluir o envio agora.');
  }
  btn.disabled = false;
  btn.innerHTML = orig;
}

// Enviar o LINK de acompanhamento como mensagem de texto via Evolution (igual o PDF)
async function enviarLinkWa(btn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';
  try {
    const r = await fetch('<?= url('/os/' . $os['id'] . '/whatsapp-link') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' }
    });
    const j = await r.json();
    if (j.success) waResultado(true, 'O link de acompanhamento foi enviado no WhatsApp do cliente.');
    else waResultado(false, j.error || '');
  } catch (e) {
    waResultado(false, 'Não foi possível concluir o envio agora.');
  }
  btn.disabled = false;
  btn.innerHTML = orig;
}

// ── Serviço: apenas preencher dados (modal abre via data-bs-toggle) ─
function resetModalServico() {
  document.getElementById('svcId').value = '';
  document.getElementById('svcDesc').value = '';
  document.getElementById('svcQtd').value = '1';
  document.getElementById('svcVal').value = '';
  document.getElementById('modalServicoTitulo').textContent = 'Novo Serviço';
}

function preencherServico(id, descricao, quantidade, valor, tecnico_id) {
  document.getElementById('svcId').value    = id;
  document.getElementById('svcDesc').value  = descricao;
  document.getElementById('svcQtd').value   = quantidade;
  document.getElementById('svcVal').value   = valor;
  document.querySelector('#formServico [name="tecnico_id"]').value = tecnico_id || '<?= (int)($_SESSION['usuario_id'] ?? 0) ?>';
  document.getElementById('modalServicoTitulo').textContent = 'Editar Serviço';
}

document.getElementById('modalServico').addEventListener('hidden.bs.modal', resetModalServico);

// ── Serviço: autocomplete no catálogo (/api/servicos) ────────────────
(function () {
  var API = '<?= url('/api/servicos') ?>';
  var input = document.getElementById('svcDesc');
  var box   = document.getElementById('svcSugestoes');
  var timer = null, ultimos = [];
  function esc(s){ var d=document.createElement('div'); d.textContent=(s==null?'':s); return d.innerHTML; }
  function limpar(){ box.innerHTML = ''; ultimos = []; }

  input.addEventListener('input', function () {
    clearTimeout(timer);
    var q = input.value.trim();
    if (q.length < 2) { limpar(); return; }
    timer = setTimeout(function () { buscar(q); }, 250);
  });
  input.addEventListener('blur', function () { setTimeout(limpar, 150); });

  function buscar(q) {
    fetch(API + '?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (lista) {
        ultimos = lista || [];
        if (!ultimos.length) { box.innerHTML = ''; return; }
        box.innerHTML = ultimos.map(function (s, i) {
          return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-i="' + i + '">' +
            '<span>' + esc(s.descricao) + '</span>' +
            '<span class="text-muted small">' + (Number(s.valor_padrao) > 0 ? 'R$ ' + Number(s.valor_padrao).toFixed(2).replace('.', ',') : '') + '</span></button>';
        }).join('');
        box.querySelectorAll('[data-i]').forEach(function (b) {
          b.addEventListener('click', function () {
            var s = ultimos[+b.dataset.i];
            input.value = s.descricao;
            if (Number(s.valor_padrao) > 0) document.getElementById('svcVal').value = Number(s.valor_padrao).toFixed(2).replace('.', ',');
            limpar();
          });
        });
      });
  }
})();

// ── Peça: apenas preencher dados (modal abre via data-bs-toggle) ─────
function resetModalPeca() {
  document.getElementById('pecaId').value     = '';
  document.getElementById('pecaProdId').value = '';
  document.getElementById('pecaBusca').value  = '';
  document.getElementById('pecaDesc').value   = '';
  document.getElementById('pecaQtd').value    = '1';
  document.getElementById('pecaVal').value    = '';
  document.getElementById('pecaSugestoes').style.display = 'none';
  document.getElementById('modalPecaTitulo').textContent = 'Nova Peça';
}

function preencherPeca(id, descricao, quantidade, valor) {
  document.getElementById('pecaId').value   = id;
  document.getElementById('pecaDesc').value = descricao;
  document.getElementById('pecaQtd').value  = quantidade;
  document.getElementById('pecaVal').value  = valor;
  document.getElementById('modalPecaTitulo').textContent = 'Editar Peça';
}

document.getElementById('modalPeca').addEventListener('hidden.bs.modal', resetModalPeca);

// ── Adiantamento: forma de pagamento decide se mostra parcelas/taxa (mesmas regras do
// fechamento — taxaPadraoOs()/TAXAS_CARTAO já definidos mais abaixo nesta página, mas o
// script só roda de verdade em resposta a um evento, quando a página já carregou inteira) ──
(function () {
  var forma = document.getElementById('adForma');
  var parcelasWrap = document.getElementById('adParcelasWrap');
  var parcelasSel = document.getElementById('adParcelas');
  var taxaWrap = document.getElementById('adTaxaWrap');
  var taxaTexto = document.getElementById('adTaxaTexto');

  function atualizarAdiantamentoForma() {
    var f = forma.value;
    var ehCredito = f === 'cartao_credito';
    parcelasWrap.classList.toggle('d-none', !ehCredito);

    var pct = 0;
    if (f === 'cartao_debito' || f === 'pix_maquininha') pct = taxaPadraoOs(f, 1);
    else if (ehCredito) pct = taxaPadraoOs(f, parcelasSel.value);

    if (pct > 0) {
      taxaWrap.classList.remove('d-none');
      taxaTexto.innerHTML = 'Taxa da maquininha: <strong>' + brNum(pct) + '%</strong> — quem cobre o custo?';
    } else {
      taxaWrap.classList.add('d-none');
    }
  }
  forma.addEventListener('change', atualizarAdiantamentoForma);
  parcelasSel.addEventListener('change', atualizarAdiantamentoForma);

  document.getElementById('modalAdiantamento').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formAdiantamento').reset();
    parcelasWrap.classList.add('d-none');
    taxaWrap.classList.add('d-none');
  });
})();

// ── Busca de produto no estoque ───────────────────────────
let pecaTimer;
document.getElementById('pecaBusca').addEventListener('input', function() {
  clearTimeout(pecaTimer);
  const q = this.value.trim();
  const box = document.getElementById('pecaSugestoes');
  if (q.length < 2) { box.style.display = 'none'; return; }

  pecaTimer = setTimeout(async () => {
    const r    = await fetch('<?= url('/api/produtos') ?>?q=' + encodeURIComponent(q));
    const list = await r.json();
    box.innerHTML = '';
    if (!list.length) { box.style.display = 'none'; return; }
    list.forEach(p => {
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action small py-2';
      a.href = '#';
      a.innerHTML = `<strong>${p.nome}</strong> <span class="text-body-secondary ms-2">Estoque: ${p.estoque_atual} ${p.unidade} · R$ ${p.valor_venda}</span>`;
      a.addEventListener('click', ev => {
        ev.preventDefault();
        document.getElementById('pecaProdId').value = p.id;
        document.getElementById('pecaDesc').value   = p.nome;
        document.getElementById('pecaVal').value    = p.valor_venda;
        document.getElementById('pecaBusca').value  = p.nome;
        box.style.display = 'none';
      });
      box.appendChild(a);
    });
    box.style.display = 'block';
  }, 300);
});
document.addEventListener('click', e => {
  if (!e.target.closest('#pecaBusca') && !e.target.closest('#pecaSugestoes'))
    document.getElementById('pecaSugestoes').style.display = 'none';
});
</script>

<script>
(function(){
  var ta=document.getElementById('obsInternasTexto'), msg=document.getElementById('obsInternasMsg'), CSRF='<?= csrf_token() ?>';
  if(!ta) return;
  document.getElementById('btnSalvarObsInternas').onclick=function(){
    msg.textContent='';
    fetch('<?= url('/os/' . $os['id'] . '/observacoes-internas') ?>',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body:'observacoes_internas='+encodeURIComponent(ta.value)
    }).then(function(r){return r.json();}).then(function(j){
      msg.innerHTML = j.success ? '<span class="text-success">✓ Observações salvas.</span>' : '<span class="text-danger">'+(j.error||'Erro')+'</span>';
    }).catch(function(){ msg.innerHTML='<span class="text-danger">Falha de conexão.</span>'; });
  };
})();
</script>

<script>
(function(){
  var ta=document.getElementById('recadoTexto'), cont=document.getElementById('recadoContador'),
      msg=document.getElementById('recadoMsg'), CSRF='<?= csrf_token() ?>';
  if(!ta) return;
  function upd(){ cont.textContent = ta.value.length; }
  ta.addEventListener('input', upd); upd();
  function post(url, cb){
    return fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},body:'recado='+encodeURIComponent(ta.value)})
      .then(function(r){return r.json();}).then(cb).catch(function(){ msg.innerHTML='<span class="text-danger">Falha de conexão.</span>'; });
  }
  document.getElementById('btnSalvarRecado').onclick=function(){
    msg.textContent='';
    post('<?= url('/os/' . $os['id'] . '/recado') ?>', function(j){
      msg.innerHTML = j.success ? '<span class="text-success">✓ Recado salvo — vai junto no próximo envio de PDF/link.</span>' : '<span class="text-danger">'+(j.error||'Erro')+'</span>';
    });
  };
  document.getElementById('btnEnviarRecado').onclick=function(){
    var b=this, orig=b.innerHTML; b.disabled=true; b.innerHTML='<span class="spinner-border spinner-border-sm"></span>';
    msg.textContent='';
    post('<?= url('/os/' . $os['id'] . '/recado-whatsapp') ?>', function(j){
      b.disabled=false; b.innerHTML=orig;
      msg.innerHTML = j.success ? '<span class="text-success">✓ Recado enviado no WhatsApp do cliente.</span>' : '<span class="text-danger">'+(j.error||'Erro')+'</span>';
    });
  };

  // Corretor ortográfico próprio (via IA) — sugere, nunca aplica sozinho.
  var boxCorrecao = document.getElementById('recadoCorrecaoBox'),
      textoCorrecao = document.getElementById('recadoCorrecaoTexto'),
      corrigidoAtual = '';
  document.getElementById('btnCorrigirRecado').onclick = function () {
    var b = this, orig = b.innerHTML;
    var texto = ta.value.trim();
    if (!texto) { msg.innerHTML = '<span class="text-danger">Escreva algo pra corrigir.</span>'; return; }
    b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Corrigindo...';
    msg.textContent = ''; boxCorrecao.style.display = 'none';
    fetch('<?= url('/os/' . $os['id'] . '/corrigir-texto') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF },
      body: 'texto=' + encodeURIComponent(texto)
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        b.disabled = false; b.innerHTML = orig;
        if (!j.ok) { msg.innerHTML = '<span class="text-danger">' + (j.erro || 'Falha ao corrigir.') + '</span>'; return; }
        if (!j.mudou) { msg.innerHTML = '<span class="text-success">✓ Sem erros encontrados.</span>'; return; }
        corrigidoAtual = j.texto;
        textoCorrecao.textContent = j.texto;
        boxCorrecao.style.display = '';
      })
      .catch(function () {
        b.disabled = false; b.innerHTML = orig;
        msg.innerHTML = '<span class="text-danger">Falha de conexão.</span>';
      });
  };
  document.getElementById('btnUsarCorrecao').onclick = function () {
    ta.value = corrigidoAtual;
    upd();
    boxCorrecao.style.display = 'none';
    msg.innerHTML = '<span class="text-success">✓ Correção aplicada — clique em Salvar.</span>';
  };
  document.getElementById('btnDescartarCorrecao').onclick = function () {
    boxCorrecao.style.display = 'none';
  };
})();
</script>

<script>
(function(){
  var ta=document.getElementById('laudoTexto'), msg=document.getElementById('laudoMsg'), CSRF='<?= csrf_token() ?>';
  if(!ta) return;

  var botoesCmd = document.querySelectorAll('#laudoToolbar [data-cmd]');
  botoesCmd.forEach(function(btn){
    btn.onclick=function(){
      ta.focus();
      try { document.execCommand('styleWithCSS', false, false); } catch(e) {}
      document.execCommand(btn.dataset.cmd);
      atualizarEstadoBotoes();
    };
  });

  function atualizarEstadoBotoes(){
    botoesCmd.forEach(function(btn){
      var ativo = false;
      try { ativo = document.queryCommandState(btn.dataset.cmd); } catch(e) {}
      btn.classList.toggle('active', !!ativo);
    });
  }
  ['keyup','mouseup','focus'].forEach(function(ev){ ta.addEventListener(ev, atualizarEstadoBotoes); });
  document.addEventListener('selectionchange', function(){
    if (document.activeElement === ta) atualizarEstadoBotoes();
  });

  document.getElementById('btnSalvarLaudo').onclick=function(){
    msg.textContent='';
    fetch('<?= url('/os/' . $os['id'] . '/laudo') ?>', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body: 'laudo_tecnico=' + encodeURIComponent(ta.innerHTML)
    })
      .then(function(r){ return r.json(); })
      .then(function(j){
        msg.innerHTML = j.success ? '<span class="text-success">✓ Laudo salvo.</span>' : '<span class="text-danger">'+(j.error||'Erro')+'</span>';
        if (j.success && j.html !== undefined) ta.innerHTML = j.html;
      })
      .catch(function(){ msg.innerHTML='<span class="text-danger">Falha de conexão.</span>'; });
  };
})();

// Rascunho de laudo técnico com IA — usa os dados da OS (equipamento, defeito) + o que já
// estiver digitado no editor como pista. Só preenche o campo; o "Salvar" continua manual.
async function gerarLaudoIA(btn) {
  var orig = btn.innerHTML;
  var ta   = document.getElementById('laudoTexto');
  var msg  = document.getElementById('laudoMsg');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';
  msg.textContent = '';
  try {
    var r = await fetch('<?= url('/os/' . $os['id'] . '/laudo-ia') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
      body: 'dica=' + encodeURIComponent(ta.innerText || '')
    });
    var j = await r.json();
    if (!j.ok) throw new Error(j.erro || 'Não foi possível gerar o laudo.');
    ta.innerHTML = j.html;
    msg.innerHTML = '<span class="text-success">✓ Rascunho gerado — revise e clique em Salvar.</span>';
  } catch (e) {
    msg.innerHTML = '<span class="text-danger">' + e.message + '</span>';
  }
  btn.disabled = false;
  btn.innerHTML = orig;
}
</script>

<script>
// Salva esta OS no cache local (IndexedDB) pra dar pra consultar sem internet depois.
if (window.FixaosOffline) {
  window.FixaosOffline.salvarDetalheOS(<?= json_encode([
      'id'                    => (int) $os['id'],
      'numero'                => $os['numero'],
      'cliente_nome'          => $os['cliente_nome'],
      'cliente_tel'           => $os['cliente_tel'],
      'cliente_whats'         => $os['cliente_whats'],
      'cliente_email'         => $os['cliente_email'],
      'equip_tipo'            => $os['equip_tipo'],
      'equip_marca'           => $os['equip_marca'],
      'equip_modelo'          => $os['equip_modelo'],
      'numero_serie'          => $os['numero_serie'],
      'imei'                  => $os['imei'],
      'defeito_relatado'      => $os['defeito_relatado'],
      'tecnico_nome'          => $os['tecnico_nome'],
      'status_nome'           => $os['status_nome'],
      'status_cor'            => $os['status_cor'],
      'status_cor_fonte'      => $os['status_cor_fonte'],
      'prioridade'            => $os['prioridade'],
      'valor_total'           => $os['valor_total'],
      'observacoes_internas'  => $os['observacoes_internas'],
      'observacoes_cliente'   => $os['observacoes_cliente'],
      'data_previsao'         => $os['data_previsao'],
      'criado_em'             => $os['criado_em'],
  ], JSON_UNESCAPED_UNICODE) ?>);
}
</script>
