<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-envelope-heart me-2 text-success"></i>E-mails do Diretório</h4>
  <p class="text-muted small mb-4" style="max-width:780px">
    Empresas que <strong>já têm ficha publicada</strong> no diretório (importadas de CNPJ ou
    cadastradas) mas ainda não foram reivindicadas por ninguém — base extraída de
    <code>empresas</code> por <code>scripts/extrair_emails_diretorio.php</code>. Diferente da
    Prospecção (lead frio sem cadastro nenhum), o convite daqui é "reivindique seu perfil já
    existente", com link direto pra página pública da empresa. Respeita o mesmo limite diário
    (config própria, separada da Prospecção) — não conta pro limite dela nem vice-versa.
  </p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Total</div>
        <div class="fs-3 fw-bold"><?= (int)$kpis['total'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Reivindicadas</div>
        <div class="fs-3 fw-bold text-success"><?= (int)$kpis['reivindicadas'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Descadastrados</div>
        <div class="fs-3 fw-bold text-secondary"><?= (int)$kpis['descadastrados'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">E-mails hoje</div>
        <div class="fs-3 fw-bold <?= $enviadosHoje >= $limiteDiario ? 'text-danger' : 'text-primary' ?>"><?= $enviadosHoje ?>/<?= $limiteDiario ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small" title="Estimativa — depende do cliente de e-mail carregar imagens remotas, ver observação abaixo da tabela">Taxa de abertura</div>
        <div class="fs-3 fw-bold text-info">
          <?= (int)$kpis['convites_enviados'] > 0 ? round(100 * (int)$kpis['convites_abertos'] / (int)$kpis['convites_enviados']) : 0 ?>%
        </div>
        <div class="text-muted" style="font-size:11px"><?= (int)$kpis['convites_abertos'] ?> de <?= (int)$kpis['convites_enviados'] ?> convites</div>
      </div></div>
    </div>
  </div>

  <?php if (!$kpis['total']): ?>
    <div class="alert alert-light border">
      <strong>Nenhuma empresa extraída ainda.</strong> Rode <code>scripts/extrair_emails_diretorio.php --aplicar</code>
      no servidor pra popular esta tabela a partir das empresas do diretório com e-mail preenchido.
    </div>
  <?php else: ?>

  <!-- Filtros -->
  <form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">UF</label>
      <input type="text" name="uf" maxlength="2" class="form-control form-control-sm" style="width:70px" value="<?= e($filtros['uf']) ?>" placeholder="SP">
    </div>
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">Cidade</label>
      <input type="text" name="cidade" class="form-control form-control-sm" style="width:160px" value="<?= e($filtros['cidade']) ?>" placeholder="Feira de Santana">
    </div>
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">Busca (nome/e-mail)</label>
      <input type="text" name="busca" class="form-control form-control-sm" style="width:200px" value="<?= e($filtros['busca']) ?>" placeholder="informática, gmail.com...">
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
      <a href="<?= url('/master/diretorio-emails') ?>" class="btn btn-sm btn-link text-muted">Limpar</a>
    </div>
  </form>

  <!-- Disparo de e-mail de convite (respeita o filtro acima + limite diário) -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
      <div class="flex-grow-1">
        <div class="fw-semibold small"><i class="bi bi-envelope-paper-heart me-1 text-primary"></i>Disparar convite "reivindique seu perfil"</div>
        <div class="text-muted" style="font-size:.8rem">
          <?= $elegiveisNoFiltro ?> empresa(s) elegível(is) no filtro atual (têm e-mail, ainda não
          reivindicada, nunca receberam o convite, não descadastrada).
          Limite diário: <?= $limiteDiario ?> — <?= max(0, $limiteDiario - $enviadosHoje) ?> ainda disponíve(is) hoje.
        </div>
      </div>
      <form method="POST" action="<?= url('/master/diretorio-emails/disparar') ?><?= $_SERVER['QUERY_STRING'] ? '?'.e($_SERVER['QUERY_STRING']) : '' ?>"
            onsubmit="return confirm('Disparar convite pra até ' + Math.min(<?= $elegiveisNoFiltro ?>, Math.max(0, <?= $limiteDiario - $enviadosHoje ?>)) + ' empresa(s)?');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-primary" <?= ($elegiveisNoFiltro === 0 || $enviadosHoje >= $limiteDiario) ? 'disabled' : '' ?>>
          <i class="bi bi-send me-1"></i>Disparar agora
        </button>
      </form>
    </div>
  </div>

  <?php if (!$leads): ?>
    <div class="alert alert-light border">Nenhuma empresa nesse filtro.</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr><th>Empresa</th><th>Local</th><th>E-mail</th><th>Convite</th><th>Situação</th></tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
          <tr class="<?= !empty($l['descadastrado_em']) || !empty($l['reivindicada']) ? 'opacity-50' : '' ?>">
            <td>
              <div class="fw-semibold"><?= e($l['nome_fantasia']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= e($l['cnpj']) ?></div>
            </td>
            <td class="text-muted"><?= e($l['cidade']) ?><?= $l['uf'] ? '/'.e($l['uf']) : '' ?></td>
            <td class="text-muted" style="font-size:.8rem"><?= e($l['email']) ?></td>
            <td class="text-muted" style="font-size:.78rem">
              <?php if (!empty($l['email_convite_enviado_em'])): ?>
                <i class="bi bi-envelope-check text-success"></i> <?= date('d/m/Y', strtotime($l['email_convite_enviado_em'])) ?>
                <?php if (!empty($l['email_aberto_em'])): ?>
                  <br><i class="bi bi-eye text-info"></i> aberto em <?= date('d/m/Y', strtotime($l['email_aberto_em'])) ?>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">pendente</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($l['reivindicada'])): ?>
                <span class="badge bg-success bg-opacity-10 text-success-emphasis border border-success border-opacity-25">Reivindicada</span>
              <?php elseif (!empty($l['descadastrado_em'])): ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Descadastrada</span>
              <?php else: ?>
                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25">Elegível</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Mostrando até 500 empresas por vez — use os filtros pra navegar por UF/cidade/busca.</p>
  <p class="text-muted small mt-1 mb-0"><i class="bi bi-info-circle me-1"></i>"Aberto" é uma estimativa (pixel de rastreamento) — só conta se o cliente de e-mail carregar imagens remotas. Gmail costuma carregar por padrão (às vezes até sem abrir de fato, por pré-carregamento); Apple Mail com "Proteção de Privacidade de Mail" pode marcar como aberto mesmo sem leitura humana. Números direcionais, não exatos.</p>
  <?php endif; ?>
  <?php endif; ?>
</div>
