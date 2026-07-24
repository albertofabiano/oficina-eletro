<?php $osDescartada = str_contains(mb_strtolower($os['status_nome'] ?? ''), 'descart'); ?>
<div class="row g-3">
  <!-- Principal -->
  <div class="col-md-8">
    <!-- Cabeçalho OS -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white">
        <!-- Linha 1: identificação da OS + garantia editável -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="fw-bold fs-5">OS: <?= e($os['numero']) ?></span>
            <?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?>
            <span class="badge badge-prioridade-<?= $os['prioridade'] ?>"><?= ucfirst($os['prioridade']) ?></span>
            <?php if ($os['tipo_servico'] === 'garantia' && !empty($os['os_origem_id'])): ?>
            <span class="badge bg-danger"><i class="bi bi-shield-check me-1"></i>Garantia</span>
            <?php endif; ?>
            <?php if (!empty($os['em_garantia']) && $os['tipo_servico'] !== 'garantia' && !$osDescartada): ?>
            <span class="badge bg-success bg-opacity-25 text-danger border border-success" style="font-size:.72rem">
              <i class="bi bi-shield-check me-1"></i><?= $os['dias_garantia_restantes'] ?>d garantia
            </span>
            <?php endif; ?>
          </div>
          <!-- Garantia editável (padrão 90 dias) -->
          <div class="d-inline-flex align-items-center gap-1 small text-muted">
            <i class="bi bi-shield-check text-success"></i>Garantia:
            <input type="number" id="garDias" value="<?= (int) ($os['garantia_dias'] ?: 90) ?>" min="0" max="3650"
                   class="form-control form-control-sm d-inline-block text-center" style="width:88px;padding:2px 6px"
                   title="Clique pra editar os dias de garantia">
            dias
            <span id="garOk" class="text-success ms-1" style="display:none"><i class="bi bi-check-circle-fill"></i></span>
          </div>
        </div>
        <?php
          $fone    = only_numbers($os['cliente_whats'] ?? $os['cliente_tel'] ?? '');
          $urlAber = url('/os/' . $os['id'] . '/imprimir');
          $urlFech = url('/os/' . $os['id'] . '/imprimir/fechamento');
          $nomeCli = $os['cliente_nome'] ?? '';
          $numOs   = $os['numero'];
          $concluida  = in_array($os['status_tipo'], ['concluida','entregue']);
          $jaEntregue = $os['status_tipo'] === 'entregue';
          // Pode fechar: configurável por status (coluna os_status.permite_fechar).
          // O nome só decide a VARIANTE do botão (verde "Fechar OS" x vermelho "Sem Conserto").
          // "Reabrir OS" só aparece quando a OS está FECHADA de fato (entregue) — ver $jaEntregue.
          // "Pronto p/ Retirada" é tipo=concluida (conta no faturamento), mas NÃO é fechada:
          // ali não mostra Fechar (já concluída) nem Reabrir (ainda não entregue) — usa-se o Status.
          $nomeStatus  = mb_strtolower($os['status_nome'] ?? '');
          $semConserto = str_contains($nomeStatus, 'sem conserto') || str_contains($nomeStatus, 'sem reparo');
          // Fechar OS disponível em QUALQUER status (pedido do dono). Só não aparece quando a OS
          // já está ENTREGUE (aí surge "Reabrir OS") — e em OS de garantia vira "Finalizar garantia".
          $podeFechar  = !$jaEntregue;

          $msgAber = urlencode(
            "Olá *{$nomeCli}*! 📋\n" .
            "Recebemos seu equipamento na OS *{$numOs}*.\n" .
            "Segue o comprovante de entrada:\n{$urlAber}"
          );
          $msgFech = urlencode(
            "Olá *{$nomeCli}*! 🎉\n" .
            "Seu equipamento está pronto para retirada!\n" .
            "OS *{$numOs}* — Comprovante de entrega:\n{$urlFech}\n" .
            "Aguardamos você. Obrigado!"
          );
        ?>
        <?php
          $temServicos = array_sum(array_column($os['servicos'] ?? [], 'valor_total')) > 0;
          $temPecas    = array_sum(array_column($os['pecas'] ?? [], 'valor_total')) > 0;
          $temOrcamento = $temServicos || $temPecas;
        ?>
        <!-- Linha 2: ações -->
        <div class="d-flex gap-2 flex-wrap mt-2 pt-2 border-top">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalStatus">
            <i class="bi bi-arrow-repeat"></i> Status
          </button>

          <!-- Imprimir ▾ -->
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-printer"></i> Imprimir
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= url('/os/' . $os['id'] . '/imprimir') ?>" target="_blank"><i class="bi bi-file-earmark me-2"></i>Abertura</a></li>
              <li><a class="dropdown-item" href="<?= url('/os/' . $os['id'] . '/imprimir/etiqueta') ?>" target="_blank"><i class="bi bi-tag me-2 text-secondary"></i>Etiqueta interna</a></li>
              <?php if ($temOrcamento): ?>
              <li><a class="dropdown-item" href="<?= url('/os/' . $os['id'] . '/imprimir/orcamento') ?>" target="_blank"><i class="bi bi-file-earmark-text me-2 text-warning"></i>Orçamento</a></li>
              <?php endif; ?>
              <?php if ($concluida): ?>
              <li><a class="dropdown-item" href="<?= $urlFech ?>" target="_blank"><i class="bi bi-file-earmark-check me-2 text-success"></i>Comprovante</a></li>
              <?php endif; ?>
              <?php if (!empty($os['os_origem_id'])): ?>
              <li><a class="dropdown-item" href="<?= url('/os/' . $os['id'] . '/imprimir/garantia') ?>" target="_blank"><i class="bi bi-shield-check me-2 text-danger"></i>Garantia</a></li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- WhatsApp ▾ (enviar documentos e link ao cliente) -->
          <?php if ($fone): ?>
          <div class="dropdown">
            <button class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-whatsapp"></i> WhatsApp
            </button>
            <ul class="dropdown-menu">
              <li><h6 class="dropdown-header">Enviar PDF ao cliente</h6></li>
              <li><button class="dropdown-item" type="button" onclick="enviarPdfWa('abertura', this)"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Abertura</button></li>
              <?php if ($temOrcamento): ?>
              <li><button class="dropdown-item" type="button" onclick="enviarPdfWa('orcamento', this)"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Orçamento</button></li>
              <?php endif; ?>
              <?php if ($concluida): ?>
              <li><button class="dropdown-item" type="button" onclick="enviarPdfWa('fechamento', this)"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Comprovante</button></li>
              <?php endif; ?>
              <?php if (!empty($os['os_origem_id'])): ?>
              <li><button class="dropdown-item" type="button" onclick="enviarPdfWa('garantia', this)"><i class="bi bi-shield-check me-2 text-danger"></i>Garantia</button></li>
              <?php endif; ?>
              <?php if(!empty($os['token_publico'])):
                $_appCfg   = require BASE_PATH . '/config/app.php';
                $linkAcomp = rtrim($_appCfg['url'], '/') . '/os/acompanhar/' . $os['token_publico'];
                $msgAcomp  = urlencode("Olá " . ($os['cliente_nome'] ?? '') . "! Acompanhe sua OS " . $os['numero'] . " em tempo real: " . $linkAcomp);
              ?>

              <?php endif; ?>
            </ul>
          </div>
          <?php endif; ?>

          <!-- Falar com o cliente (WhatsApp Web / app — link direto, sem API) -->
          <?php if ($fone):
            $foneWa   = (strlen($fone) <= 11) ? '55' . $fone : $fone;
            $msgFalar = urlencode("Olá *{$nomeCli}*! Aqui é da " . ($os['empresa_nome'] ?? 'assistência') . " sobre a sua OS *{$numOs}*.");
          ?>
          <a href="https://wa.me/<?= $foneWa ?>?text=<?= $msgFalar ?>" target="_blank" rel="noopener"
             class="btn btn-sm btn-outline-success fw-semibold" title="Abrir conversa no WhatsApp com o cliente">
            <i class="bi bi-chat-dots"></i> Falar com o cliente
          </a>
          <?php endif; ?>

          <?php if (!empty($os['os_origem_id'])): ?>
            <?php if (empty($os['garantia_finalizada']) && !in_array($os['status_tipo'], ['entregue','cancelada'], true)): // garantia não finalizada e não encerrada — mostra também em Pronto ?>
            <button class="btn btn-sm btn-info fw-semibold text-white"
                    data-bs-toggle="modal" data-bs-target="#modalFinalizarGarantia">
              <i class="bi bi-shield-check me-1"></i>Finalizar garantia
            </button>
            <?php endif; ?>
          <?php elseif ($podeFechar): ?>
            <button class="btn btn-sm <?= $semConserto ? 'btn-danger' : 'btn-success' ?> fw-semibold"
                    data-bs-toggle="modal" data-bs-target="#modalFechar">
              <i class="bi bi-<?= $semConserto ? 'x-circle' : 'check-circle' ?> me-1"></i>
              <?= $semConserto ? 'Fechar Sem Conserto' : 'Fechar OS' ?>
            </button>
          <?php endif; ?>
          <?php if (($os['em_garantia'] ?? false) && $os['tipo_servico'] !== 'garantia' && empty($os['os_origem_id']) && !$osDescartada): ?>
          <button class="btn btn-sm btn-warning fw-semibold"
                  data-bs-toggle="modal" data-bs-target="#modalGarantia">
            <i class="bi bi-shield-check me-1"></i>Abrir Garantia
          </button>
          <?php endif; ?>
          <?php if ($jaEntregue): ?>
          <form method="POST" action="<?= url('/os/' . $os['id'] . '/reabrir') ?>" style="display:inline"
                onsubmit="return confirm('Reabrir esta OS? Ela voltará ao status anterior ao fechamento.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-secondary fw-semibold">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir OS
            </button>
          </form>
          <?php endif; ?>

          <a href="<?= url('/os/' . $os['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil"></i>
          </a>
        </div>

      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Cliente</div>
            <a href="<?= url('/clientes/' . $os['cliente_id']) ?>" class="fw-semibold text-decoration-none fs-6"><?= e($os['cliente_nome']) ?></a>
            <div class="mt-1" style="font-size:.9rem;line-height:1.9">
              <?php if (!empty($os['cliente_contato'])): ?><div><i class="bi bi-person-badge text-primary me-2"></i>Contato: <strong><?= e($os['cliente_contato']) ?></strong></div><?php endif; ?>
              <?php if (!empty($os['cpf_cnpj'])): ?><div><i class="bi bi-card-text text-muted me-2"></i><?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
              <?php if (!empty($os['cliente_tel'])): ?><div><i class="bi bi-telephone text-muted me-2"></i><?= e($os['cliente_tel']) ?></div><?php endif; ?>
              <?php if (!empty($os['cliente_whats'])): ?><div><i class="bi bi-whatsapp text-success me-2"></i><?= e($os['cliente_whats']) ?></div><?php endif; ?>
              <?php if (!empty($os['cliente_email'])): ?><div><i class="bi bi-envelope text-muted me-2"></i><?= e($os['cliente_email']) ?></div><?php endif; ?>
              <?php
                $endCli = array_filter([
                  $os['cli_logradouro'] ?? '',
                  ($os['cli_numero'] ?? '') ? 'nº '.$os['cli_numero'] : '',
                  $os['cli_bairro'] ?? '',
                ]);
                $cidCli = trim(($os['cli_cidade'] ?? '').(!empty($os['cli_uf']) ? '/'.$os['cli_uf'] : ''));
              ?>
              <?php if ($endCli || $cidCli): ?><div><i class="bi bi-geo-alt text-muted me-2"></i><?= e(implode(', ', $endCli)) ?><?= $cidCli ? ' — '.e($cidCli) : '' ?><?php if (!empty($os['cli_cep'])): ?> · CEP <?= e($os['cli_cep']) ?><?php endif; ?></div><?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Equipamento</div>
            <div class="fw-semibold"><?= e($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></div>
            <div><?= e($os['equip_tipo']) ?> <?= $os['equip_cor'] ? '• ' . e($os['equip_cor']) : '' ?></div>
            <?php if ($os['numero_serie']): ?><div class="small text-muted">S/N: <?= e($os['numero_serie']) ?></div><?php endif; ?>
            <?php if (!empty($os['imei'])): ?><div class="small text-muted">IMEI: <?= e($os['imei']) ?></div><?php endif; ?>
            <?php if ($os['senha_desbloqueio']): ?><div class="small"><i class="bi bi-shield-lock"></i> <?= e($os['senha_desbloqueio']) ?></div><?php endif; ?>

            <?php $svcList = $os['servicos'] ?? []; $pcList = $os['pecas'] ?? []; $temItens = count($svcList) || count($pcList); ?>
            <div class="small text-muted fw-semibold mb-1 mt-3">Serviços e peças</div>
            <?php if (!$temItens): ?>
            <div class="text-muted small"><i class="bi bi-inboxes me-1"></i>Nenhum serviço ou peça · <strong>R$ 0,00</strong></div>
            <?php else: ?>
            <ul class="list-unstyled mb-1" style="font-size:.85rem">
              <?php foreach ($svcList as $s): ?>
              <li class="d-flex justify-content-between border-bottom py-1"><span><i class="bi bi-tools text-primary me-1"></i><?= e($s['descricao']) ?><?= ($s['quantidade'] ?? 1) > 1 ? ' <span class="text-muted">×'.(int)$s['quantidade'].'</span>' : '' ?></span><strong><?= money($s['valor_total']) ?></strong></li>
              <?php endforeach; ?>
              <?php foreach ($pcList as $p): ?>
              <li class="d-flex justify-content-between border-bottom py-1"><span><i class="bi bi-cpu text-success me-1"></i><?= e($p['descricao']) ?><?= ($p['quantidade'] ?? 1) > 1 ? ' <span class="text-muted">×'.(int)$p['quantidade'].'</span>' : '' ?></span><strong><?= money($p['valor_total']) ?></strong></li>
              <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between fw-bold" style="font-size:.9rem"><span>Total</span><span class="text-success"><?= money(array_sum(array_column($svcList,'valor_total')) + array_sum(array_column($pcList,'valor_total'))) ?></span></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Defeito relatado</div>
            <p class="mb-0"><?= nl2br(e($os['defeito_relatado'])) ?></p>
          </div>
          <?php if ($os['defeito_constatado']): ?>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Defeito constatado</div>
            <p class="mb-0"><?= nl2br(e($os['defeito_constatado'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($os['laudo_tecnico']): ?>
          <div class="col-12">
            <div class="small text-muted fw-semibold mb-1">Laudo técnico</div>
            <p class="mb-0"><?= nl2br(e($os['laudo_tecnico'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer bg-white">
        <div class="row g-2 small text-muted">
          <div class="col-md-3"><i class="bi bi-calendar3 me-1"></i>Entrada: <?= date_br($os['data_entrada'], true) ?></div>
<?php if ($_SESSION['mostrar_previsao'] ?? 1): ?>
          <div class="col-md-3"><i class="bi bi-clock me-1"></i>Previsão: <?= date_br($os['data_previsao'], true) ?: '—' ?></div>
<?php endif; ?>
          <div class="col-md-3"><i class="bi bi-person-gear me-1"></i>Técnico: <?= e($os['tecnico_nome'] ?? '—') ?></div>
          <div class="col-md-3">
            <i class="bi bi-shield-check me-1"></i>Garantia: <span id="garRodape"><?= (int) ($os['garantia_dias'] ?: 90) ?></span> dias
          </div>
        </div>
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
            var rod = document.getElementById('garRodape'); if (rod) rod.textContent = v;
            ok.style.display = ''; setTimeout(function () { ok.style.display = 'none'; }, 2000);
          }
        }).catch(function () {});
      });
    })();
    </script>

    <!-- Recado ao cliente (vai no WhatsApp) -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold flex-wrap gap-2">
        <span><i class="bi bi-chat-left-text me-2 text-success"></i>Recado ao cliente</span>
        <button type="button" class="btn btn-sm btn-success" id="btnEnviarRecado"><i class="bi bi-whatsapp me-1"></i>Enviar ao cliente (mensagem + link)</button>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">Escreva aqui e clique em <strong>Enviar</strong>: vai direto pro WhatsApp do cliente <strong>junto com o link</strong> de acompanhamento, numa mensagem só. Também acompanha os PDFs. Em branco = só o link.</p>
        <textarea id="recadoTexto" class="form-control" rows="2" maxlength="600"
          placeholder="Ex.: Segue o orçamento. A peça precisa ser encomendada, prazo de 5 dias úteis."><?= e($os['recado_cliente'] ?? '') ?></textarea>
        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
          <div id="recadoMsg" class="small"></div>
          <div class="d-flex align-items-center gap-2">
            <span class="form-text mb-0"><span id="recadoContador">0</span>/600</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSalvarRecado"><i class="bi bi-save me-1"></i>Salvar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Serviços -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold">
        Serviços Realizados
        <button class="btn btn-sm btn-outline-primary" onclick="resetModalServico()" data-bs-toggle="modal" data-bs-target="#modalServico"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle" id="tblServicos">
          <thead class="table-light"><tr><th>Descrição</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th>Técnico</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($os['servicos'] as $s): ?>
            <tr>
              <td><?= e($s['descricao']) ?></td>
              <td><?= $s['quantidade'] ?></td>
              <td><?= money($s['valor_unitario']) ?></td>
              <td class="fw-semibold"><?= money($s['valor_total']) ?></td>
              <td><?= e($s['tecnico_nome'] ?? '—') ?></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="preencherServico(<?= $s['id'] ?>, '<?= addslashes(e($s['descricao'])) ?>', <?= $s['quantidade'] ?>, <?= $s['valor_unitario'] ?>, '<?= $s['tecnico_id'] ?>')"
                  data-bs-toggle="modal" data-bs-target="#modalServico">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/servicos/' . $s['id']) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-method="DELETE"
                   data-confirm="Remover este serviço?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$os['servicos']): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum serviço adicionado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Peças -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold">
        Peças Utilizadas
        <button class="btn btn-sm btn-outline-primary" onclick="resetModalPeca()" data-bs-toggle="modal" data-bs-target="#modalPeca"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle">
          <thead class="table-light"><tr><th>Peça</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($os['pecas'] as $p): ?>
            <tr>
              <td>
                <?= e($p['descricao']) ?>
                <?php if ($p['prod_codigo']): ?><span class="badge bg-light text-muted border ms-1">#<?= e($p['prod_codigo']) ?></span><?php endif; ?>
              </td>
              <td><?= $p['quantidade'] ?></td>
              <td><?= money($p['valor_unitario']) ?></td>
              <td class="fw-semibold"><?= money($p['valor_total']) ?></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="preencherPeca(<?= $p['id'] ?>, '<?= addslashes(e($p['descricao'])) ?>', <?= $p['quantidade'] ?>, <?= $p['valor_unitario'] ?>)"
                  data-bs-toggle="modal" data-bs-target="#modalPeca">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/pecas/' . $p['id']) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-method="DELETE"
                   data-confirm="Remover esta peça?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$os['pecas']): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma peça adicionada.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Garantia -->
    <?php if (!empty($os['garantia_ate']) || !empty($os['os_origem_numero'])): ?>
    <div class="card border-0 shadow-sm mb-3 <?= ($os['em_garantia'] ?? false) ? 'border-danger border' : 'border-secondary border' ?>">
      <div class="card-header d-flex justify-content-between align-items-center
                  <?= ($os['em_garantia'] ?? false) ? 'bg-danger bg-opacity-10' : 'bg-light' ?>">
        <span class="fw-semibold <?= ($os['em_garantia'] ?? false) ? 'text-danger' : 'text-muted' ?>">
          <i class="bi bi-shield-<?= ($os['em_garantia'] ?? false) ? 'check-fill' : 'x' ?> me-1"></i>
          <?php if (!empty($os['os_origem_numero'])): ?>
            OS de Garantia — vinculada à <a href="<?= url('/os/' . $os['os_origem_id']) ?>" class="fw-bold"><?= e($os['os_origem_numero']) ?></a>
          <?php else: ?>
            Garantia do Serviço
          <?php endif; ?>
        </span>
        <?php if (($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalGarantia">
          <i class="bi bi-arrow-return-left me-1"></i>Registrar Retorno
        </button>
        <?php endif; ?>
      </div>
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          <?php if (!empty($os['garantia_ate'])): ?>
          <div class="col-md-4 text-center">
            <div class="small text-muted">Válida até</div>
            <div class="fw-bold fs-5 <?= ($os['em_garantia'] ?? false) ? 'text-danger' : 'text-danger' ?>">
              <?= date_br($os['garantia_ate']) ?>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="small text-muted">Prazo</div>
            <div class="fw-bold"><?= $os['garantia_dias'] ?? 0 ?> dias</div>
          </div>
          <div class="col-md-4 text-center">
            <?php $dias = $os['dias_garantia_restantes'] ?? null; ?>
            <div class="small text-muted">
              <?= $dias !== null && $dias >= 0 ? 'Dias restantes' : 'Expirou há' ?>
            </div>
            <div class="fw-bold <?= ($dias !== null && $dias >= 0) ? 'text-danger' : 'text-danger' ?>">
              <?php if ($dias !== null): ?>
                <?= abs($dias) ?> dia<?= abs($dias) !== 1 ? 's' : '' ?>
              <?php else: ?>—<?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($os['motivo_retorno'])): ?>
          <div class="col-12">
            <div class="small text-muted fw-semibold">Motivo do retorno</div>
            <div class="small"><?= nl2br(e($os['motivo_retorno'])) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <div class="alert alert-warning mb-0 mt-2 py-2 small">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Garantia expirada em <?= date_br($os['garantia_ate']) ?>.
        </div>
        <?php endif; ?>

        <!-- OS filhas de garantia -->
        <?php if (!empty($os['garantias'])): ?>
        <div class="mt-3">
          <div class="small fw-semibold text-muted mb-2">Retornos de garantia registrados:</div>
          <?php foreach ($os['garantias'] as $g): ?>
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-1">
            <i class="bi bi-arrow-return-right text-warning"></i>
            <a href="<?= url('/os/' . $g['id']) ?>" class="fw-semibold text-decoration-none">OS: <?= e($g['numero']) ?></a>
            <span class="text-muted small"><?= date_br($g['criado_em']) ?></span>
            <?= badge_status_os($g['status_tipo'], $g['status_nome'], $g['status_cor'] ?? '', $g['status_cor_fonte'] ?? '#ffffff') ?>
            <span class="ms-auto text-muted small"><?= money($g['valor_total']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Histórico -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Histórico de Status</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($os['historico'] as $h): ?>
        <li class="list-group-item small">
          <div class="d-flex justify-content-between">
            <div>
              <?php if ($h['status_ant']): ?><span class="text-muted"><?= e($h['status_ant']) ?></span> → <?php endif; ?>
              <strong><?= e($h['status_nov'] ?? '') ?></strong>
              <?php if ($h['descricao']): ?> — <?= e($h['descricao']) ?><?php endif; ?>
            </div>
            <small class="text-muted"><?= date_br($h['criado_em'], true) ?> • <?= e($h['usuario_nome'] ?? 'Sistema') ?></small>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Lateral -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Resumo Financeiro</div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Serviços</span><strong><?= money(array_sum(array_column($os['servicos'],'valor_total'))) ?></strong></div>
        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Peças</span><strong><?= money(array_sum(array_column($os['pecas'],'valor_total'))) ?></strong></div>
        <?php if ($os['desconto_valor'] > 0): ?>
        <div class="d-flex justify-content-between mb-1 text-danger"><span>Desconto</span><strong>- <?= money($os['desconto_valor']) ?></strong></div>
        <?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong><?= money($os['valor_total']) ?></strong></div>
        <?php if ($os['valor_pago'] > 0): ?>
        <div class="d-flex justify-content-between text-danger mt-1"><span>Pago</span><strong><?= money($os['valor_pago']) ?></strong></div>
        <div class="d-flex justify-content-between text-danger mt-1"><span>Saldo</span><strong><?= money($os['valor_total'] - $os['valor_pago']) ?></strong></div>
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
        <div class="d-flex justify-content-between mb-1" style="font-size:.9rem"><span class="text-muted"><i class="bi bi-credit-card me-1"></i>Cobrado no cartão</span><strong><?= money($txReceita) ?></strong></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mb-1" style="font-size:.9rem">
          <span class="text-muted"><i class="bi bi-credit-card-2-front me-1"></i>Taxa da maquininha<?= $txDet ? ' <span class="text-muted">('.e($txDet).')</span>' : '' ?></span>
          <strong class="text-danger">- <?= money($txValor) ?></strong>
        </div>
        <div class="d-flex justify-content-between" style="font-size:.95rem">
          <span class="fw-semibold text-success"><i class="bi bi-wallet2 me-1"></i>Recebido líquido</span>
          <strong class="text-success"><?= money($txLiquido) ?></strong>
        </div>
        <?php endif; ?>

        <?php $spMap=['pendente'=>'warning','parcial'=>'info','pago'=>'success']; ?>
        <div class="mt-2">
          <?php if (!empty($os['garantia_finalizada'])): ?>
            <span class="badge" style="background:#0d9488"><i class="bi bi-shield-check me-1"></i>Garantia finalizada</span>
          <?php elseif (!empty($os['fechada_sem_receita'])): ?>
            <span class="badge bg-danger">Sem Débito</span>
          <?php elseif ((float)$os['valor_total'] <= 0): ?>
            <span class="badge bg-dark">Sem Débito</span>
          <?php else: ?>
            <span class="badge bg-<?= $spMap[$os['situacao_pagamento']] ?>"><?= ucfirst($os['situacao_pagamento']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (($_SESSION['chat_habilitado'] ?? 1)): ?>
    <!-- Conversa da equipe (chat interno amarrado à OS) -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
        <span><i class="bi bi-chat-dots-fill text-primary me-2"></i>Conversa da equipe</span>
        <button type="button" id="chatFechar" class="btn btn-sm btn-link text-muted p-0 lh-1" title="Fechar chat" style="text-decoration:none">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="card-body p-2" id="chatCorpo">
        <div id="chatMsgs" class="px-1" style="max-height:340px;overflow-y:auto">
          <div class="text-center text-muted small py-3" id="chatVazio">Nenhuma mensagem ainda.<br>Comece a conversa da equipe sobre esta OS. 👇</div>
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
          box.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma mensagem ainda.<br>Comece a conversa da equipe sobre esta OS. 👇</div>';
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

      // Editar / Apagar (delegação de eventos)
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
        // edição inline
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
        var fechar = (corpo.style.display !== 'none'); // se está aberto, fecha
        localStorage.setItem(K, fechar ? '1' : '0');
        aplicar(fechar);
      });
    })();
    </script>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Status -->
<div class="modal fade" id="modalStatus" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <form class="modal-content" id="formStatus">
      <div class="modal-header"><h5 class="modal-title">Alterar Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <select name="status_id" class="form-select mb-2" id="novoStatus">
          <?php
          $normais   = array_filter($statusList, fn($s) => $s['tipo'] !== 'garantia');
          $garantias = array_filter($statusList, fn($s) => $s['tipo'] === 'garantia');
          ?>
          <?php foreach ($normais as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $os['status_id']==$s['id']?'selected':'' ?>>
            <?= e($s['nome']) ?>
          </option>
          <?php endforeach; ?>
          <?php if ($garantias): ?>
          <option disabled>─────────────────</option>
          <option disabled style="color:#dc2626;font-weight:600">🛡 ENTRADAS EM GARANTIA</option>
          <?php foreach ($garantias as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $os['status_id']==$s['id']?'selected':'' ?>>
            🛡 <?= e($s['nome']) ?>
          </option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <textarea name="descricao" class="form-control" rows="2" placeholder="Observação (opcional)"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

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

        <!-- Info garantia -->
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

        <!-- Equipamento -->
        <div class="mb-3 p-3 bg-light rounded small">
          <div class="fw-semibold"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
          <div class="text-muted"><?= e($os['equip_tipo']??'') ?></div>
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

<!-- ── MODAL FECHAR OS ───────────────────────────────────── -->
<div class="modal fade" id="modalFechar" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/fechar') ?>">
      <?= csrf_field() ?>
      <div class="modal-header <?= $semConserto ? 'bg-danger' : 'bg-success' ?> text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-<?= $semConserto ? 'x-circle' : 'check-circle' ?> me-2"></i>
          <?= $semConserto ? 'Fechar como Sem Conserto — OS ' : 'Fechar Ordem de Serviço ' ?><?= e($os['numero']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <?php if ($semConserto): ?>
        <!-- Aviso Sem Conserto -->
        <div class="alert alert-danger d-flex gap-2 mb-4">
          <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
          <div>
            <strong>Sem conserto.</strong> Esta OS será encerrada sem cobrança de serviços ou peças.
            O equipamento será devolvido ao cliente no estado em que está.
          </div>
        </div>
        <?php else: ?>
        <!-- Resumo financeiro (apenas quando há conserto) -->
        <div class="alert alert-light border mb-4">
          <div class="row text-center g-2">
            <div class="col-4">
              <div class="text-muted small">Serviços</div>
              <div class="fw-bold"><?= money(array_sum(array_column($os['servicos'], 'valor_total'))) ?></div>
            </div>
            <div class="col-4">
              <div class="text-muted small">Peças</div>
              <div class="fw-bold"><?= money(array_sum(array_column($os['pecas'], 'valor_total'))) ?></div>
            </div>
            <div class="col-4">
              <div class="text-muted small">Total</div>
              <div class="fw-bold fs-5 text-danger"><?= money($os['valor_total']) ?></div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">
          <!-- Laudo / Solução -->
          <div class="col-12">
            <label class="form-label fw-semibold">Solução aplicada *</label>
            <textarea name="solucao_aplicada" class="form-control" rows="2"
              required placeholder="Descreva o que foi feito para resolver o defeito..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Laudo técnico</label>
            <textarea name="laudo_tecnico" class="form-control" rows="2"
              placeholder="Diagnóstico técnico detalhado..."><?= e($os['laudo_tecnico'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Observações para o cliente</label>
            <textarea name="observacoes_cliente" class="form-control" rows="2"
              placeholder="Instruções de uso, cuidados, recomendações..."><?= e($os['observacoes_cliente'] ?? '') ?></textarea>
          </div>

          <?php if (!$semConserto): ?>
          <!-- Garantia (apenas com conserto) -->
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

          <!-- Desconto -->
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

          <!-- Pagamento (dividido em múltiplas formas) -->
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label fw-semibold mb-0">Pagamento</label>
              <span id="osRestante" class="small fw-semibold"></span>
            </div>
            <div id="pagamentosOs"></div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-1">
              <button type="button" id="btnAddPagamentoOs" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Adicionar forma de pagamento</button>
              <div id="osRepassarWrap" class="d-flex gap-3 align-items-center" style="display:none">
                <span class="small fw-semibold text-muted">Quem paga a taxa:</span>
                <div class="form-check mb-0"><input class="form-check-input" type="radio" name="cartao_repassar" value="0" id="repEmpresa" checked><label class="form-check-label small" for="repEmpresa">A empresa</label></div>
                <div class="form-check mb-0"><input class="form-check-input" type="radio" name="cartao_repassar" value="1" id="repCliente"><label class="form-check-label small" for="repCliente">O cliente</label></div>
              </div>
            </div>
            <input type="hidden" name="pagamentos" id="pagamentosOsInput">
          </div>
          <?php else: ?>
          <!-- Sem Conserto: garantia zerada e sem pagamento -->
          <input type="hidden" name="garantia_dias" value="0">
          <input type="hidden" name="desconto_valor" value="0">
          <input type="hidden" name="desconto_tipo" value="valor">
          <input type="hidden" name="valor_pago" value="0">
          <?php endif; ?>
        </div>

        <div class="alert alert-<?= $semConserto ? 'danger' : 'warning' ?> mt-3 mb-0 py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          <?php if ($semConserto): ?>
            Ao fechar, a OS será encerrada como <strong>Sem Conserto</strong>, sem cobrança e com garantia zerada.
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
        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição *</label>
          <input type="text" name="descricao" id="svcDesc" class="form-control" required placeholder="Ex: Troca de tela, Diagnóstico...">
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
<!-- Botão oculto para abrir modal serviço via JS (edição) -->
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
<!-- Botão oculto para abrir modal peça via JS (edição) -->
<button id="btnAbrirModalPeca" data-bs-toggle="modal" data-bs-target="#modalPeca" style="display:none"></button>

<script>
// ── Desconto — recalcular total ───────────────────────────
const totalOriginal = <?= (float)($os['valor_total'] ?? 0) ?>;

function recalcularTotal() {
  const descontoInput = document.getElementById('descontoValor');
  const tipo          = document.getElementById('descontoTipo').value;
  const valorPago     = document.getElementById('valorPagoFechamento');
  const totalEl       = document.getElementById('totalComDesconto');
  if (!descontoInput || !totalEl) return;

  const descontoRaw = parseFloat(descontoInput.value.replace(',','.')) || 0;
  let desconto = tipo === 'percentual'
    ? totalOriginal * descontoRaw / 100
    : descontoRaw;

  desconto = Math.min(desconto, totalOriginal);
  const novoTotal = Math.max(0, totalOriginal - desconto);

  totalEl.value = novoTotal.toFixed(2).replace('.', ',');
  if (valorPago) valorPago.value = novoTotal.toFixed(2).replace('.', ',');
  if (typeof atualizarPagamentosOs === 'function') atualizarPagamentosOs();
}

// ── Cartão: parcelas + taxa (config do admin) ─────────────
var TAXAS_CARTAO = <?= json_encode(json_decode(($taxasCartao ?? '') ?: '{}', true) ?: new \stdClass()) ?>;
function brNum(n){ return (isFinite(n)?n:0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function totalFechamento(){ return parseFloat((document.getElementById('totalComDesconto').value||'0').replace(/\./g,'').replace(',','.'))||0; }

// ── Pagamento dividido (múltiplas formas) — mesma UX do PDV ──
var linhasPagOs = [{ forma: 'dinheiro', valor: (document.getElementById('totalComDesconto') ? document.getElementById('totalComDesconto').value : '') }];
function taxaPadraoOs(forma, parcelas){
  if (forma === 'cartao_debito') return TAXAS_CARTAO.debito || 0;
  if (forma === 'cartao_credito') { var c = TAXAS_CARTAO.credito || {}; return c[parcelas] !== undefined ? c[parcelas] : 0; }
  return 0;
}
function renderPagamentosOs(){
  var cont = document.getElementById('pagamentosOs');
  if (!cont) return;
  var temCartao = linhasPagOs.some(function(l){ return l.forma === 'cartao_credito' || l.forma === 'cartao_debito'; });
  var repassarWrap = document.getElementById('osRepassarWrap');
  if (repassarWrap) repassarWrap.style.display = temCartao ? 'flex' : 'none';

  if (!linhasPagOs.length){
    cont.innerHTML = '<div class="text-muted small border rounded p-2 mb-2">Nenhum pagamento registrado — a OS será fechada sem cobrança registrada.</div>';
    return;
  }
  cont.innerHTML = linhasPagOs.map(function (lin, i) {
    var ehCredito = lin.forma === 'cartao_credito';
    var ehCartao  = ehCredito || lin.forma === 'cartao_debito';
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
          '<option value="cartao_credito"' + (lin.forma==='cartao_credito'?' selected':'') + '>Cartão de Crédito</option>' +
          '<option value="cartao_debito"' + (lin.forma==='cartao_debito'?' selected':'') + '>Cartão de Débito</option>' +
          '<option value="transferencia"' + (lin.forma==='transferencia'?' selected':'') + '>Transferência</option>' +
          '<option value="boleto"' + (lin.forma==='boleto'?' selected':'') + '>Boleto</option>' +
        '</select></div>' +
        '<div class="col-6"><input type="text" class="form-control form-control-sm os-linha-valor" data-i="' + i + '" inputmode="decimal" placeholder="0,00" value="' + (lin.valor || '') + '"></div>' +
      '</div>' +
      (ehCartao ? (
        '<div class="row g-2 mt-1">' +
          (ehCredito ? '<div class="col-6"><select class="form-select form-select-sm os-linha-parcelas" data-i="' + i + '">' + parcelasOpts + '</select></div>' : '') +
          '<div class="col-' + (ehCredito ? '6' : '12') + '"><div class="input-group input-group-sm"><input type="number" class="form-control os-linha-taxa" data-i="' + i + '" min="0" max="100" step="0.01" value="' + (lin.taxa != null ? lin.taxa : '') + '"><span class="input-group-text">%</span></div></div>' +
        '</div>'
      ) : '') +
      '</div>';
  }).join('');

  cont.querySelectorAll('.os-linha-forma').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var i = +this.dataset.i;
      linhasPagOs[i].forma = this.value;
      if (this.value === 'cartao_credito' || this.value === 'cartao_debito') {
        linhasPagOs[i].parcelas = linhasPagOs[i].parcelas || 1;
        linhasPagOs[i].taxa = taxaPadraoOs(this.value, linhasPagOs[i].parcelas);
      }
      renderPagamentosOs(); atualizarPagamentosOs();
    });
  });
  cont.querySelectorAll('.os-linha-valor').forEach(function (inp) {
    inp.addEventListener('input', function () { linhasPagOs[+this.dataset.i].valor = this.value; atualizarPagamentosOs(); });
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
    else { el.className = 'small text-muted fw-semibold'; el.textContent = ''; }
  }
  var inp = document.getElementById('pagamentosOsInput');
  if (inp) {
    inp.value = JSON.stringify(linhasPagOs
      .filter(function (l) { return l.forma && (parseFloat((l.valor||'0').toString().replace(',','.'))||0) > 0; })
      .map(function (l) { return { forma: l.forma, valor: (parseFloat((l.valor||'0').toString().replace(',','.'))||0), parcelas: l.parcelas || 1, taxa: parseFloat((l.taxa||'0').toString().replace(',','.'))||0 }; }));
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

// ── Status via AJAX ───────────────────────────────────────
document.getElementById('formStatus').addEventListener('submit', async function(e) {
  e.preventDefault();
  const r    = await fetch('<?= url('/os/' . $os['id'] . '/status') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({
      status_id: document.getElementById('novoStatus').value,
      descricao: this.querySelector('[name=descricao]').value,
    })
  });
  const json = await r.json();
  if (json.success) {
    location.reload();
  }
});

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
      '<p id="waResMsg" class="text-muted mb-4" style="font-size:.92rem;line-height:1.6"></p>' +
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
      a.innerHTML = `<strong>${p.nome}</strong> <span class="text-muted ms-2">Estoque: ${p.estoque_atual} ${p.unidade} · R$ ${p.valor_venda}</span>`;
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
})();
</script>
