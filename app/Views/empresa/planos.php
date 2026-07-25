<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-stars me-2 text-warning"></i>Planos e Assinatura</h4>
    <a href="<?= url('/empresa') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
  <p class="text-muted small">Sistema completo para todos os planos — o que muda é o tamanho (usuários e OS/mês).</p>

  <?php
    $planoAtual = $emp['plano_atual'] ?? null;
    $licAte     = $emp['licenca_ate'] ?? null;
    $trialAte   = $emp['trial_ate'] ?? null;
  ?>
  <?php if ($planoAtual && $licAte): ?>
  <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill fs-5"></i>
    <div>Seu plano <strong><?= e(ucfirst($planoAtual)) ?></strong> está ativo até <strong><?= date_br($licAte) ?></strong>.</div></div>
  <?php elseif ($trialAte && $trialAte >= date('Y-m-d')): ?>
  <div class="alert alert-info d-flex align-items-center gap-2"><i class="bi bi-hourglass-split fs-5"></i>
    <div>Você está em <strong>teste grátis</strong> até <strong><?= date_br($trialAte) ?></strong>. Assine para continuar sem interrupção.</div></div>
  <?php endif; ?>

  <?php if (!$pagamentoAtivo): ?>
  <div class="alert alert-warning d-flex align-items-start gap-2"><i class="bi bi-info-circle-fill fs-5"></i>
    <div><strong>Pagamento online em configuração.</strong> Os valores abaixo são iniciais. Para ativar sua assinatura agora, <a href="https://wa.me/5511979930702" target="_blank" rel="noopener" class="alert-link">fale com o FixaOS</a>.</div></div>
  <?php endif; ?>

  <!-- Seletor de ciclo -->
  <div class="row g-3 mb-4" id="cicloSel">
    <?php $primeiro = true; foreach ($ciclos as $ck => $c): ?>
    <div class="col-md-4">
      <button type="button" class="btn w-100 <?= $primeiro ? 'btn-primary active' : 'btn-outline-primary' ?>" data-ciclo="<?= $ck ?>" onclick="selCiclo('<?= $ck ?>')">
        <?= e($c['nome']) ?><?php if ($c['desconto'] > 0): ?> <span class="badge bg-success ms-1">-<?= $c['desconto'] ?>%</span><?php endif; ?>
      </button>
    </div>
    <?php $primeiro = false; endforeach; ?>
  </div>

  <div class="row g-3">
    <?php foreach ($planos as $p): ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 <?= !empty($p['destaque']) ? 'border-top border-4 border-primary' : '' ?>">
        <div class="card-body d-flex flex-column">
          <?php if (!empty($p['destaque'])): ?><span class="badge bg-primary align-self-start mb-2">Mais popular</span><?php endif; ?>
          <h5 class="fw-bold mb-0"><?= e($p['nome']) ?></h5>
          <div class="text-muted small mb-2"><?= (int)$p['max_usuarios'] === 0 ? 'Usuários ilimitados' : 'Até '.(int)$p['max_usuarios'].' usuários' ?> · <?= (int)$p['os_mes'] === 0 ? 'OS ilimitada' : (int)$p['os_mes'].' OS/mês' ?></div>

          <?php // preços de cada ciclo (server-side), JS mostra o do ciclo escolhido ?>
          <div class="mb-3">
            <?php $pf = true; foreach ($ciclos as $ck => $c):
              $total = plano_preco_ciclo((int)$p['preco_mensal'], $c);
              $porMes = $total / $c['meses'];
            ?>
            <div class="preco-ciclo" data-ciclo="<?= $ck ?>" style="<?= $pf ? '' : 'display:none' ?>">
              <span class="fs-3 fw-bold">R$ <?= number_format($porMes/100, 2, ',', '.') ?></span><span class="text-muted">/mês</span>
              <?php if ($c['meses'] > 1): ?><div class="small text-success">R$ <?= number_format($total/100, 2, ',', '.') ?> à vista (<?= e($c['nome']) ?>) — economize <?= $c['desconto'] ?>%</div><?php endif; ?>
            </div>
            <?php $pf = false; endforeach; ?>
          </div>

          <ul class="list-unstyled small mb-4 flex-grow-1">
            <?php foreach ($p['beneficios'] as $b): ?><li class="mb-1"><i class="bi bi-check2 text-success me-1"></i><?= e($b) ?></li><?php endforeach; ?>
          </ul>

          <?php if ($pagamentoAtivo): ?>
          <a href="#" target="_top" class="btn <?= !empty($p['destaque']) ? 'btn-primary' : 'btn-outline-primary' ?> fw-bold w-100 btn-assinar" data-plano="<?= $p['codigo'] ?>">
            <i class="bi bi-credit-card me-1"></i><?= $planoAtual === $p['codigo'] ? 'Renovar' : 'Assinar' ?>
          </a>
          <?php else: ?>
          <button class="btn btn-outline-secondary fw-bold w-100" disabled><i class="bi bi-lock me-1"></i>Em breve</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pagamentoAtivo && !empty($credito)): ?>
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <i class="bi bi-lightning-charge-fill text-warning me-1"></i><strong>Precisa de mais OS este mês?</strong>
        Compre <strong><?= (int)$credito['qtd'] ?> OS extra</strong> por <strong>R$ <?= number_format($credito['preco']/100, 2, ',', '.') ?></strong>.
        <?php if (!empty($emp['creditos_os'])): ?><span class="badge bg-success ms-2">Saldo atual: <?= (int)$emp['creditos_os'] ?> OS</span><?php endif; ?>
      </div>
      <a href="<?= url('/comprar-credito') ?>" target="_top" class="btn btn-outline-success fw-bold"><i class="bi bi-plus-circle me-1"></i>Comprar +<?= (int)$credito['qtd'] ?> OS</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($pagamentoAtivo && (!empty($creditoScanEquip) || !empty($creditoScanPlaca))): ?>
  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-camera-fill text-primary"></i>
        <strong>Buscas por foto com IA este mês</strong>
      </div>
      <p class="text-muted small mb-3">
        A leitura automática por câmera (etiqueta do equipamento na OS, ou placa de identificação no marketplace) usa inteligência
        artificial, que tem um custo por uso. Por isso cada plano inclui uma cota mensal de buscas; passando dela, você pode preencher
        manualmente sem custo, ou comprar buscas extra avulsas abaixo.
      </p>
      <div class="row g-3">
        <?php if (!empty($creditoScanEquip)): ?>
        <div class="col-md-6">
          <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="fw-semibold">Equipamento (Ordem de Serviço)</div>
              <div class="small text-muted">
                Uso este mês: <?= (int)$usoScanEquip ?><?= !empty($planoAtualCfg['scan_equip_mes']) ? ' / '.(int)$planoAtualCfg['scan_equip_mes'] : '' ?>
                <?php if (!empty($emp['creditos_scan_equip'])): ?><span class="badge bg-success ms-1">+<?= (int)$emp['creditos_scan_equip'] ?> crédito(s)</span><?php endif; ?>
              </div>
            </div>
            <a href="<?= url('/comprar-credito-scan-equip') ?>" target="_top" class="btn btn-sm btn-outline-primary mt-2">
              <i class="bi bi-plus-circle me-1"></i>Comprar +<?= (int)$creditoScanEquip['qtd'] ?> buscas — R$ <?= number_format($creditoScanEquip['preco']/100, 2, ',', '.') ?>
            </a>
          </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($creditoScanPlaca)): ?>
        <div class="col-md-6">
          <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="fw-semibold">Placa (Marketplace)</div>
              <div class="small text-muted">
                Uso este mês: <?= (int)$usoScanPlaca ?><?= !empty($planoAtualCfg['scan_placa_mes']) ? ' / '.(int)$planoAtualCfg['scan_placa_mes'] : '' ?>
                <?php if (!empty($emp['creditos_scan_placa'])): ?><span class="badge bg-success ms-1">+<?= (int)$emp['creditos_scan_placa'] ?> crédito(s)</span><?php endif; ?>
              </div>
            </div>
            <a href="<?= url('/comprar-credito-scan-placa') ?>" target="_top" class="btn btn-sm btn-outline-primary mt-2">
              <i class="bi bi-plus-circle me-1"></i>Comprar +<?= (int)$creditoScanPlaca['qtd'] ?> buscas — R$ <?= number_format($creditoScanPlaca['preco']/100, 2, ',', '.') ?>
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Pagamento pela <strong>InfinitePay</strong> — PIX ou cartão em até 12x. Sua página do diretório continua no ar mesmo sem plano ativo.</p>
</div>

<script>
let cicloAtivo = '<?= array_key_first($ciclos) ?>';
function selCiclo(ck) {
  cicloAtivo = ck;
  document.querySelectorAll('#cicloSel button').forEach(b => {
    const on = b.dataset.ciclo === ck;
    b.classList.toggle('btn-primary', on); b.classList.toggle('active', on);
    b.classList.toggle('btn-outline-primary', !on);
  });
  document.querySelectorAll('.preco-ciclo').forEach(el => el.style.display = el.dataset.ciclo === ck ? '' : 'none');
  document.querySelectorAll('.btn-assinar').forEach(a => a.setAttribute('href', '<?= url('/assinar/') ?>' + a.dataset.plano + '/' + ck));
}
selCiclo(cicloAtivo);
</script>
