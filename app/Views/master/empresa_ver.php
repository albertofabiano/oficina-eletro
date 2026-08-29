<div class="row g-3">

  <!-- Coluna esquerda -->
  <div class="col-md-4">
    <div class="ms-card p-4 mb-3">
      <div class="text-center mb-3">
        <div class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white mb-2"
             style="width:56px;height:56px;font-size:1.4rem">
          <?= mb_strtoupper(mb_substr($empresa['nome_fantasia']??'E',0,1)) ?>
        </div>
        <h5 class="text-white mb-0"><?= e($empresa['nome_fantasia']) ?></h5>
        <div class="text-muted small"><?= e($empresa['razao_social']) ?></div>
        <span class="badge badge-plano-<?= $empresa['plano'] ?> mt-1"><?= ucfirst($empresa['plano']) ?></span>
        <span class="badge bg-<?= $empresa['ativo']?'success':'danger' ?> ms-1"><?= $empresa['ativo']?'Ativa':'Inativa' ?></span>
      </div>
      <div class="small text-muted">
        <div><i class="bi bi-envelope me-1"></i><?= e($empresa['email']) ?></div>
        <div><i class="bi bi-telephone me-1"></i><?= e($empresa['telefone']) ?></div>
        <div class="mt-1"><i class="bi bi-geo-alt me-1"></i><?= e($empresa['cidade'].'/'.$empresa['uf']) ?></div>
        <div class="mt-1"><i class="bi bi-calendar me-1"></i>Cadastro: <?= date_br($empresa['criado_em']) ?></div>
      </div>
    </div>

    <!-- Destaque no diretório (fluxo InfinitePay) -->
    <?php
      $destAtivo = ($empresa['diretorio_destaque'] ?? 'none') !== 'none'
                   && (empty($empresa['diretorio_destaque_ate']) || $empresa['diretorio_destaque_ate'] >= date('Y-m-d'));
    ?>
    <div class="ms-card p-3 mb-3">
      <div class="fw-semibold text-white mb-2 small"><i class="bi bi-star-fill text-warning me-1"></i>Destaque no diretório</div>
      <?php if($destAtivo): ?>
        <div class="small text-success mb-2"><i class="bi bi-check-circle-fill me-1"></i>Ativo<?= $empresa['diretorio_destaque_ate'] ? ' até '.date_br($empresa['diretorio_destaque_ate']) : '' ?></div>
      <?php else: ?>
        <div class="small text-muted mb-2">Sem destaque. Ative após confirmar o pagamento na InfinitePay.</div>
      <?php endif; ?>
      <form method="POST" action="<?= url('/master/empresas/'.$empresa['id'].'/destaque') ?>"
            onsubmit="return confirm('<?= $destAtivo ? 'Desativar o destaque desta empresa?' : 'Ativar destaque por 31 dias (empresa vai pro topo do diretorio)?' ?>')">
        <?= csrf_field() ?>
        <button class="btn btn-sm w-100 <?= $destAtivo ? 'btn-outline-warning' : 'btn-warning' ?>">
          <i class="bi bi-star<?= $destAtivo ? '' : '-fill' ?> me-1"></i><?= $destAtivo ? 'Desativar destaque' : 'Ativar destaque (31 dias)' ?>
        </button>
      </form>
    </div>

    <!-- Editar empresa -->
    <div class="ms-card p-3">
      <div class="fw-semibold text-white mb-3 small">Editar empresa</div>
      <form method="POST" action="<?= url('/master/empresas/'.$empresa['id']) ?>">
        <?= csrf_field() ?>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size:.75rem">Nome fantasia</label>
          <input type="text" name="nome_fantasia" class="form-control form-control-sm" value="<?= e($empresa['nome_fantasia']) ?>">
        </div>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size:.75rem">Razão social</label>
          <input type="text" name="razao_social" class="form-control form-control-sm" value="<?= e($empresa['razao_social']) ?>">
        </div>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size:.75rem">E-mail</label>
          <input type="email" name="email" class="form-control form-control-sm" value="<?= e($empresa['email']) ?>">
        </div>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size:.75rem">Telefone</label>
          <input type="text" name="telefone" class="form-control form-control-sm" value="<?= e($empresa['telefone']) ?>">
        </div>
        <div class="row g-2 mb-2">
          <div class="col">
            <label class="form-label text-muted" style="font-size:.75rem">Plano</label>
            <select name="plano" class="form-select form-select-sm">
              <option value="basico" <?= $empresa['plano']==='basico'?'selected':'' ?>>Básico</option>
              <option value="profissional" <?= $empresa['plano']==='profissional'?'selected':'' ?>>Profissional</option>
              <option value="enterprise" <?= $empresa['plano']==='enterprise'?'selected':'' ?>>Enterprise</option>
            </select>
          </div>
          <div class="col">
            <label class="form-label text-muted" style="font-size:.75rem">Max usuários</label>
            <input type="number" name="max_usuarios" class="form-control form-control-sm" value="<?= $empresa['max_usuarios'] ?>">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size:.75rem">Trial até</label>
          <input type="date" name="trial_ate" class="form-control form-control-sm" value="<?= $empresa['trial_ate'] ?? '' ?>">
        </div>
        <div class="mb-3">
          <label class="form-label text-muted" style="font-size:.75rem">Status</label>
          <select name="ativo" class="form-select form-select-sm">
            <option value="1" <?= $empresa['ativo']?'selected':'' ?>>Ativa</option>
            <option value="0" <?= !$empresa['ativo']?'selected':'' ?>>Inativa</option>
          </select>
        </div>
        <button class="btn btn-danger btn-sm w-100">Salvar</button>
      </form>
    </div>

    <!-- Zona de perigo: excluir empresa -->
    <div class="ms-card p-3 mt-3" style="border:1px solid rgba(220,53,69,.45)">
      <div class="fw-semibold mb-1 small" style="color:#f87171"><i class="bi bi-exclamation-triangle me-1"></i>Excluir empresa</div>
      <p class="text-muted mb-2" style="font-size:.72rem;line-height:1.5">
        Esta ação é <strong>permanente</strong> e remove a empresa com <strong>todos os dados</strong>
        (usuários, OS, clientes, estoque, financeiro, créditos). Não há como desfazer.
        Para confirmar, digite o nome fantasia exato: <strong><?= e($empresa['nome_fantasia']) ?></strong>
      </p>
      <form method="POST" action="<?= url('/master/empresas/'.$empresa['id'].'/excluir') ?>"
            onsubmit="return confirm('Excluir DEFINITIVAMENTE a empresa &quot;<?= e($empresa['nome_fantasia']) ?>&quot; e todos os seus dados? Esta ação não pode ser desfeita.');">
        <?= csrf_field() ?>
        <input type="text" name="confirma" class="form-control form-control-sm mb-2"
               placeholder="Digite: <?= e($empresa['nome_fantasia']) ?>" autocomplete="off" required>
        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Excluir empresa permanentemente</button>
      </form>
    </div>
  </div>

  <!-- Coluna direita -->
  <div class="col-md-8">
    <!-- Usuários -->
    <div class="ms-card mb-3">
      <div class="card-header px-3 py-2">
        <span class="fw-semibold text-white small">Usuários (<?= count($usuarios) ?>)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle" style="--bs-table-bg:transparent;--bs-table-color:#e2e8f0;--bs-table-hover-bg:rgba(255,255,255,.04)">
          <thead style="border-color:rgba(255,255,255,.1)"><tr style="color:#9ca3af;font-size:.75rem;text-transform:uppercase"><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Último login</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr style="border-color:rgba(255,255,255,.06)">
              <td style="color:#e2e8f0;font-size:.85rem"><?= e($u['nome']) ?></td>
              <td style="color:#9ca3af;font-size:.82rem"><?= e($u['email']) ?></td>
              <td><span class="badge bg-secondary" style="font-size:.65rem"><?= ucfirst($u['perfil']) ?></span></td>
              <td class="text-muted small"><?= date_br($u['ultimo_login'], true) ?: '—' ?></td>
              <td><span class="badge bg-<?= $u['ativo']?'success':'secondary' ?>" style="font-size:.65rem"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-light" style="font-size:.7rem;padding:.15rem .5rem"
                        title="Alterar senha deste usuário"
                        onclick="abrirModalSenha(<?= (int) $u['id'] ?>, '<?= e(addslashes($u['nome'])) ?>')">
                  <i class="bi bi-key-fill"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Últimas OS -->
    <div class="ms-card mb-3">
      <div class="card-header px-3 py-2">
        <span class="fw-semibold text-white small">Últimas OS</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle" style="--bs-table-bg:transparent;--bs-table-color:#e2e8f0;--bs-table-hover-bg:rgba(255,255,255,.04)">
          <thead style="border-color:rgba(255,255,255,.1)"><tr style="color:#9ca3af;font-size:.75rem;text-transform:uppercase"><th>Nº</th><th>Cliente</th><th>Status</th><th>Valor</th><th>Data</th></tr></thead>
          <tbody>
            <?php foreach ($osRecentes as $os): ?>
            <tr style="border-color:rgba(255,255,255,.06)">
              <td style="color:#e2e8f0;font-size:.85rem;font-weight:600"><?= e($os['numero']) ?></td>
              <td style="color:#9ca3af;font-size:.82rem"><?= e($os['cliente_nome']) ?></td>
              <td><?= badge_status_os($os['status_tipo']??'aberta', $os['status_nome']??'—', $os['status_cor']??'') ?></td>
              <td class="text-white small"><?= money($os['valor_total']) ?></td>
              <td class="text-muted small"><?= date_br($os['criado_em']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$osRecentes): ?>
            <tr><td colspan="5" class="text-center text-muted small py-3">Sem OS.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Configurações -->
    <div class="ms-card">
      <div class="card-header px-3 py-2">
        <span class="fw-semibold text-white small">Configurações do sistema</span>
      </div>
      <div class="p-3">
        <div class="row g-2">
          <?php
          $labelsConfig = [
            'os_prefixo'                => 'Prefixo da OS',
            'os_digitos'                => 'Dígitos da OS',
            'os_numero_inicial'         => 'Número inicial',
            'garantia_padrao_dias'      => 'Garantia (dias)',
            'prazo_retirada_dias'       => 'Prazo retirada (dias)',
            'comissao_tecnico_percentual'=> 'Comissão técnico (%)',
            'comissao_tecnico_modo'     => 'Comissão técnico (modo)',
            'texto_entrada_equipamento' => 'Texto de entrada',
            'texto_garantia'            => 'Texto de garantia',
            'setup_concluido'           => 'Setup concluído',
          ];
          $boolConfig = ['setup_concluido'];
          $ocultos    = ['texto_entrada_equipamento', 'texto_garantia', 'taxas_cartao'];
          foreach ($configs as $k => $v):
            if (in_array($k, $ocultos)) continue;
            $valorExibido = in_array($k, $boolConfig, true) ? ((int) $v === 1 ? 'Sim' : 'Não') : ($v ?: '—');
          ?>
          <div class="col-md-4">
            <div class="bg-opacity-10 rounded p-2" style="background:rgba(255,255,255,.05)">
              <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;margin-bottom:.2rem"><?= e($labelsConfig[$k] ?? $k) ?></div>
              <div class="text-white small fw-semibold"><?= e($valorExibido) ?></div>
            </div>
          </div>
          <?php endforeach;

          // Taxas de cartão vêm como JSON — renderiza um resumo legível em vez do texto cru.
          $taxasCartao = json_decode((string) ($configs['taxas_cartao'] ?? ''), true);
          if (is_array($taxasCartao)):
            $parcelas = [];
            foreach (($taxasCartao['credito'] ?? []) as $n => $taxa) {
                $parcelas[] = $n . 'x: ' . number_format((float) $taxa, 2, ',', '.') . '%';
            }
          ?>
          <div class="col-md-8">
            <div class="bg-opacity-10 rounded p-2" style="background:rgba(255,255,255,.05)">
              <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;margin-bottom:.2rem">Taxas de cartão</div>
              <div class="text-white small fw-semibold">
                Débito: <?= number_format((float) ($taxasCartao['debito'] ?? 0), 2, ',', '.') ?>%
                · Repassar ao cliente: <?= empty($taxasCartao['repassar_padrao']) ? 'Não' : 'Sim' ?>
                · Recebimento: <?= e($taxasCartao['modo_recebimento'] ?? '—') ?>
              </div>
              <?php if ($parcelas): ?>
                <div class="text-muted mt-1" style="font-size:.72rem"><?= e('Crédito parcelado — ' . implode(' · ', $parcelas)) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Modal: alterar senha de usuário (suporte — cliente esqueceu/não consegue trocar sozinho) -->
<div class="modal fade" id="modalSenhaUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#141720;border:1px solid rgba(255,255,255,.08);color:#e0e0e0">
      <div class="modal-header" style="border-color:rgba(255,255,255,.08)">
        <h5 class="modal-title text-white fw-bold">Alterar senha de <span id="senhaUsuarioNome">—</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formSenhaUsuario">
        <?= csrf_field() ?>
        <div class="modal-body">
          <label class="form-label small fw-semibold">Nova senha *</label>
          <input type="text" name="senha" class="form-control" minlength="6" required
                 placeholder="Mínimo 6 caracteres" autocomplete="new-password">
          <div class="form-text text-muted">
            O usuário passa a entrar com essa senha imediatamente — avise-o pelo telefone/WhatsApp.
          </div>
        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.08)">
          <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger btn-sm">Alterar senha</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function abrirModalSenha(usuarioId, nome) {
  document.getElementById('senhaUsuarioNome').textContent = nome;
  document.getElementById('formSenhaUsuario').action = '<?= url('/master/usuarios/') ?>' + usuarioId + '/senha';
  document.getElementById('formSenhaUsuario').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSenhaUsuario')).show();
}
</script>
