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
  </div>

  <!-- Coluna direita -->
  <div class="col-md-8">
    <!-- Usuários -->
    <div class="ms-card mb-3">
      <div class="card-header px-3 py-2">
        <span class="fw-semibold text-white small">Usuários (<?= count($usuarios) ?>)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Último login</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td class="text-white small"><?= e($u['nome']) ?></td>
              <td class="text-muted small"><?= e($u['email']) ?></td>
              <td><span class="badge bg-secondary" style="font-size:.65rem"><?= ucfirst($u['perfil']) ?></span></td>
              <td class="text-muted small"><?= date_br($u['ultimo_login'], true) ?: '—' ?></td>
              <td><span class="badge bg-<?= $u['ativo']?'success':'secondary' ?>" style="font-size:.65rem"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
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
        <table class="table table-sm mb-0 align-middle">
          <thead><tr><th>Nº</th><th>Cliente</th><th>Status</th><th>Valor</th><th>Data</th></tr></thead>
          <tbody>
            <?php foreach ($osRecentes as $os): ?>
            <tr>
              <td class="text-white small fw-semibold">OS: <?= e($os['numero']) ?></td>
              <td class="text-muted small"><?= e($os['cliente_nome']) ?></td>
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
          <?php foreach ($configs as $k => $v): ?>
          <div class="col-md-4">
            <div class="bg-opacity-10 rounded p-2" style="background:rgba(255,255,255,.05)">
              <div style="font-size:.68rem;color:#555;text-transform:uppercase"><?= e($k) ?></div>
              <div class="text-white small fw-semibold"><?= e($v ?: '—') ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div>
