<?php
  $statusBadge = function(string $s): string {
    return match($s) {
      'contatado'  => '<span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-25">Contatado</span>',
      'convertido' => '<span class="badge bg-success bg-opacity-10 text-success-emphasis border border-success border-opacity-25">Convertido</span>',
      'descartado' => '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Descartado</span>',
      default      => '<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25">Novo</span>',
    };
  };
  $cnaeNome = function(string $c): string {
    return match($c) {
      '9511800' => 'Computadores e periféricos',
      '9521500' => 'Eletroeletrônicos uso pessoal/doméstico',
      '4757100' => 'Peças e acessórios eletroeletrônicos',
      default   => $c,
    };
  };
?>
<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-search-heart me-2 text-success"></i>Prospecção</h4>
  <p class="text-muted small mb-4" style="max-width:760px">
    Empresas de assistência técnica e venda de componentes eletrônicos importadas dos
    <strong>dados abertos de CNPJ da Receita Federal</strong>, filtradas pelas CNAEs do setor.
    Isso é uma lista de prospecção pra contato manual — nada aqui aparece no diretório público
    nem recebe mensagem automática. Marque o status conforme for contatando.
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
        <div class="text-muted small">Novos</div>
        <div class="fs-3 fw-bold text-warning"><?= (int)$kpis['novos'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Contatados</div>
        <div class="fs-3 fw-bold text-info"><?= (int)$kpis['contatados'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Convertidos</div>
        <div class="fs-3 fw-bold text-success"><?= (int)$kpis['convertidos'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Descartados</div>
        <div class="fs-3 fw-bold text-secondary"><?= (int)$kpis['descartados'] ?></div>
      </div></div>
    </div>
  </div>

  <?php if (!$kpis['total']): ?>
    <div class="alert alert-light border">
      <strong>Nenhum lead importado ainda.</strong> Rode <code>scripts/importar_leads_cnpj.php</code>
      no servidor com um CSV filtrado dos dados abertos de CNPJ (veja instruções no topo do script).
    </div>
  <?php else: ?>

  <!-- Filtros -->
  <form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">Status</label>
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach (['novo'=>'Novo','contatado'=>'Contatado','convertido'=>'Convertido','descartado'=>'Descartado'] as $k=>$l): ?>
        <option value="<?= $k ?>" <?= $filtros['status']===$k?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">CNAE</label>
      <select name="cnae" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($cnaes as $c): ?>
        <option value="<?= e($c['cnae']) ?>" <?= $filtros['cnae']===$c['cnae']?'selected':'' ?>><?= e($cnaeNome($c['cnae'])) ?> (<?= (int)$c['total'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label small text-muted mb-1">UF</label>
      <input type="text" name="uf" maxlength="2" class="form-control form-control-sm" style="width:70px" value="<?= e($filtros['uf']) ?>" placeholder="SP">
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
      <a href="<?= url('/master/prospeccao') ?>" class="btn btn-sm btn-link text-muted">Limpar</a>
    </div>
  </form>

  <?php if (!$leads): ?>
    <div class="alert alert-light border">Nenhum lead nesse filtro.</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr><th>Empresa</th><th>CNAE</th><th>Local</th><th>Contato</th><th>Status</th><th class="text-end">Ação</th></tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
          <tr class="<?= $l['status']==='descartado' ? 'opacity-50' : '' ?>">
            <td>
              <div class="fw-semibold"><?= e($l['nome_fantasia'] ?: $l['razao_social']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= e($l['cnpj']) ?></div>
            </td>
            <td class="text-muted"><?= e($cnaeNome($l['cnae'])) ?></td>
            <td class="text-muted"><?= e($l['municipio']) ?><?= $l['uf'] ? '/'.e($l['uf']) : '' ?></td>
            <td>
              <?php $foneNum = preg_replace('/\D/', '', $l['telefone'] ?? ''); ?>
              <?php if ($foneNum): ?>
              <a href="https://wa.me/55<?= e($foneNum) ?>?text=<?= urlencode('Olá! Aqui é da FixaOS, um sistema de gestão pra assistências técnicas. Posso te apresentar rapidinho?') ?>"
                 target="_blank" rel="noopener" class="text-decoration-none">
                <i class="bi bi-whatsapp text-success"></i> <?= e($l['telefone']) ?>
              </a>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><?= $statusBadge($l['status']) ?></td>
            <td class="text-end" style="white-space:nowrap">
              <form method="POST" action="<?= url('/master/prospeccao/' . $l['id'] . '/status') ?><?= $_SERVER['QUERY_STRING'] ? '?'.e($_SERVER['QUERY_STRING']) : '' ?>" class="d-inline">
                <?php if ($l['status'] !== 'contatado'): ?>
                <button name="status" value="contatado" class="btn btn-sm btn-outline-info" title="Marcar como contatado"><i class="bi bi-chat-dots"></i></button>
                <?php endif; ?>
                <?php if ($l['status'] !== 'convertido'): ?>
                <button name="status" value="convertido" class="btn btn-sm btn-outline-success" title="Virou cliente"><i class="bi bi-check-lg"></i></button>
                <?php endif; ?>
                <?php if ($l['status'] !== 'descartado'): ?>
                <button name="status" value="descartado" class="btn btn-sm btn-outline-secondary" title="Descartar"><i class="bi bi-x-lg"></i></button>
                <?php else: ?>
                <button name="status" value="novo" class="btn btn-sm btn-outline-secondary" title="Voltar pra Novo"><i class="bi bi-arrow-counterclockwise"></i></button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Mostrando até 500 leads por vez — use os filtros pra navegar por CNAE/UF/status.</p>
  <?php endif; ?>
  <?php endif; ?>
</div>
