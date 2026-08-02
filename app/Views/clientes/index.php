<style>
  tr[data-bs-toggle="collapse"]:hover { background:rgba(13,110,253,.04); }
  tr[data-bs-toggle="collapse"] .bi-chevron-down { transition:transform .2s ease; }
  tr[data-bs-toggle="collapse"]:not(.collapsed) .bi-chevron-down { transform:rotate(180deg); }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <form class="d-flex gap-2 flex-grow-1" method="GET" style="min-width:200px;max-width:420px">
    <input type="search" name="busca" class="form-control" placeholder="Nome, CPF, telefone..." value="<?= e($busca) ?>">
    <button class="btn btn-outline-secondary flex-shrink-0"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= url('/clientes/novo') ?>" class="btn btn-primary flex-shrink-0"><i class="bi bi-person-plus"></i> Novo Cliente</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:36px"></th><th>Nome</th><th>Contato</th><th>Bairro</th><th>OS</th><th>Status</th><th>Classif.</th><th>Cadastro</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php
          $origemLabel = ['balcao'=>'Balcão','telefone'=>'Telefone','whatsapp'=>'WhatsApp','site'=>'Site','indicacao'=>'Indicação','outro'=>'Outro'];
        ?>
        <?php foreach ($paginator['data'] as $c): ?>
        <tr style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#cliDet<?= (int)$c['id'] ?>">
          <td class="text-center text-muted">
            <i class="bi bi-chevron-down small"></i>
          </td>
          <td>
            <a href="<?= url('/clientes/' . $c['id']) ?>" class="fw-semibold text-decoration-none" onclick="event.stopPropagation()"><?= e($c['nome']) ?></a>
            <?php if ($c['tipo']==='pj'): ?><span class="badge bg-secondary ms-1">PJ</span><?php endif; ?>
            <div class="small text-muted"><?= e(doc_mask($c['cpf_cnpj'])) ?></div>
          </td>
          <td>
            <?= e($c['telefone']) ?>
            <?php if ($c['whatsapp']): ?>
            <a href="https://wa.me/55<?= only_numbers($c['whatsapp']) ?>" target="_blank" class="text-success ms-1" onclick="event.stopPropagation()"><i class="bi bi-whatsapp"></i></a>
            <?php endif; ?>
            <div class="small text-muted"><?= e($c['email']) ?></div>
          </td>
          <td><?= e($c['bairro']) ?><?= $c['uf'] ? ' - ' . e($c['uf']) : '' ?></td>
          <td><span class="badge bg-light text-dark border"><?= $c['total_os'] ?? 0 ?> OS</span></td>
          <td>
            <?php $map=['ativo'=>'success','inativo'=>'secondary','bloqueado'=>'danger']; ?>
            <span class="badge bg-<?= $map[$c['status']] ?? 'secondary' ?>"><?= ucfirst($c['status']) ?></span>
          </td>
          <td style="white-space:nowrap">
            <?php $es=(int)($c['estrelas']??0); if($es): for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$es?'-fill':'' ?>" style="color:<?= $i<=$es?'#f59e0b':'#dee2e6' ?>;font-size:.78rem"></i><?php endfor; else: ?><span class="text-muted small">—</span><?php endif; ?>
          </td>
          <td><?= date_br($c['criado_em']) ?></td>
          <td class="text-end" onclick="event.stopPropagation()">
            <a href="<?= url('/clientes/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            <a href="<?= url('/clientes/' . $c['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <a href="#" data-method="DELETE" data-href="<?= url('/clientes/' . $c['id']) ?>"
               data-confirm="Excluir o cliente <?= e($c['nome']) ?>? Esta ação não pode ser desfeita."
               class="btn btn-sm btn-outline-danger" title="Excluir cliente (somente admin)"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <tr class="collapse" id="cliDet<?= (int)$c['id'] ?>">
          <td colspan="9" class="bg-light-subtle p-0">
            <div class="p-3">
              <div class="row g-3 small">
                <div class="col-md-4">
                  <div class="text-muted fw-semibold mb-1"><i class="bi bi-geo-alt me-1"></i>Endereço</div>
                  <?php
                    $end = trim(($c['logradouro'] ?? '') . (!empty($c['numero']) ? ', ' . $c['numero'] : ''));
                    if (!empty($c['complemento'])) $end .= ' - ' . $c['complemento'];
                  ?>
                  <div><?= $end ? e($end) : '—' ?></div>
                  <div><?= e($c['bairro'] ?? '') ?><?= $c['cidade'] ? ' — ' . e($c['cidade']) : '' ?><?= $c['uf'] ? '/' . e($c['uf']) : '' ?></div>
                  <?php if (!empty($c['cep'])): ?><div>CEP: <?= e($c['cep']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <div class="text-muted fw-semibold mb-1"><i class="bi bi-person-vcard me-1"></i>Documentos e dados</div>
                  <?php if (!empty($c['rg_ie'])): ?><div><?= $c['tipo']==='pj' ? 'IE' : 'RG' ?>: <?= e($c['rg_ie']) ?></div><?php endif; ?>
                  <?php if (!empty($c['data_nascimento'])): ?><div>Nascimento: <?= date_br($c['data_nascimento']) ?></div><?php endif; ?>
                  <?php if (!empty($c['profissao'])): ?><div>Profissão: <?= e($c['profissao']) ?></div><?php endif; ?>
                  <div>Origem: <?= e($origemLabel[$c['origem'] ?? ''] ?? 'Balcão') ?></div>
                  <?php if (!empty($c['tags'])): ?><div>Tags: <?= e($c['tags']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <div class="text-muted fw-semibold mb-1"><i class="bi bi-cash-coin me-1"></i>Financeiro</div>
                  <div>Saldo de crédito: <?= money($c['saldo_credito'] ?? 0) ?></div>
                  <div>Limite de crédito: <?= money($c['limite_credito'] ?? 0) ?></div>
                  <?php if (!empty($c['observacoes'])): ?>
                  <div class="text-muted fw-semibold mb-1 mt-2"><i class="bi bi-journal-text me-1"></i>Observações</div>
                  <div><?= nl2br(e($c['observacoes'])) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">Nenhum cliente encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> clientes</small>
    <?= pagination($paginator, url('/clientes')) ?>
  </div>
  <?php endif; ?>
</div>
