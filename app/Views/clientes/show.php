<div class="row g-3">
  <!-- Col esquerda -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body text-center py-4">
        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-2" style="width:64px;height:64px;font-size:1.5rem">
          <?= avatar_iniciais($cliente['nome']) ?>
        </div>
        <h5 class="mb-0"><?= e($cliente['nome']) ?></h5>
        <div class="text-muted small"><?= $cliente['tipo']==='pj' ? 'Pessoa Jurídica' : 'Pessoa Física' ?></div>
        <?php $map=['ativo'=>'success','inativo'=>'secondary','bloqueado'=>'danger']; ?>
        <span class="badge bg-<?= $map[$cliente['status']] ?> mt-1"><?= ucfirst($cliente['status']) ?></span>
      </div>
      <ul class="list-group list-group-flush">
        <?php if ($cliente['cpf_cnpj']): ?><li class="list-group-item small"><i class="bi bi-card-text text-muted me-2"></i><?= e($cliente['cpf_cnpj']) ?></li><?php endif; ?>
        <?php if ($cliente['telefone']): ?><li class="list-group-item small"><i class="bi bi-telephone text-muted me-2"></i><?= e($cliente['telefone']) ?></li><?php endif; ?>
        <?php if ($cliente['whatsapp']): ?><li class="list-group-item small"><i class="bi bi-whatsapp text-success me-2"></i><a href="https://wa.me/55<?= only_numbers($cliente['whatsapp']) ?>" target="_blank"><?= e($cliente['whatsapp']) ?></a></li><?php endif; ?>
        <?php if ($cliente['email']): ?><li class="list-group-item small"><i class="bi bi-envelope text-muted me-2"></i><?= e($cliente['email']) ?></li><?php endif; ?>
        <?php if ($cliente['cidade']): ?><li class="list-group-item small"><i class="bi bi-geo-alt text-muted me-2"></i><?= e($cliente['logradouro'] . ', ' . $cliente['numero'] . ' - ' . $cliente['cidade'] . '/' . $cliente['uf']) ?></li><?php endif; ?>
      </ul>
      <div class="card-footer d-flex gap-2">
        <a href="<?= url('/clientes/' . $cliente['id'] . '/editar') ?>" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-pencil"></i> Editar</a>
        <a href="<?= url('/os/nova?cliente_id=' . $cliente['id']) ?>" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-plus-lg"></i> Nova OS</a>
      </div>
    </div>
  </div>

  <!-- Col direita -->
  <div class="col-md-8">
    <ul class="nav nav-tabs mb-3" id="tabs">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOS">OS (<?= count($historico) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEquip">Equipamentos (<?= count($equipamentos) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContatos">Contatos (<?= count($contatos) ?>)</button></li>
    </ul>

    <div class="tab-content">
      <!-- OS -->
      <div class="tab-pane fade show active" id="tabOS">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover mb-0 small align-middle">
              <thead class="table-light"><tr><th>Nº</th><th>Equipamento</th><th>Status</th><th>Valor</th><th>Data</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($historico as $os): ?>
                <tr>
                  <td><a href="<?= url('/os/' . $os['id']) ?>" class="fw-semibold">OS: <?= e($os['numero']) ?></a></td>
                  <td><?= e($os['marca'] . ' ' . $os['modelo']) ?><br><span class="text-muted"><?= e($os['equip_tipo']) ?></span></td>
                  <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '') ?></td>
                  <td><?= money($os['valor_total']) ?></td>
                  <td><?= date_br($os['data_entrada']) ?></td>
                  <td><a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$historico): ?><tr><td colspan="6" class="text-center text-muted py-3">Nenhuma OS.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Equipamentos -->
      <div class="tab-pane fade" id="tabEquip">
        <div class="row g-2">
          <?php foreach ($equipamentos as $eq): ?>
          <div class="col-md-6">
            <div class="card border-0 shadow-sm">
              <div class="card-body p-3">
                <div class="fw-semibold"><?= e($eq['marca'] . ' ' . $eq['modelo']) ?></div>
                <div class="small text-muted"><?= e($eq['tipo']) ?> • <?= e($eq['categoria_nome'] ?? '') ?></div>
                <?php if ($eq['numero_serie']): ?><div class="small">S/N: <?= e($eq['numero_serie']) ?></div><?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (!$equipamentos): ?><div class="col"><p class="text-muted">Nenhum equipamento.</p></div><?php endif; ?>
        </div>
      </div>

      <!-- Contatos CRM -->
      <div class="tab-pane fade" id="tabContatos">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Histórico de Contatos</span>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalContato"><i class="bi bi-plus-lg"></i> Registrar</button>
          </div>
          <ul class="list-group list-group-flush">
            <?php foreach ($contatos as $ct): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between">
                <div>
                  <span class="badge bg-secondary"><?= e(ucfirst($ct['tipo'])) ?></span>
                  <span class="badge bg-light text-dark border ms-1"><?= e(ucfirst($ct['direcao'])) ?></span>
                  <strong class="ms-2"><?= e($ct['assunto']) ?></strong>
                </div>
                <small class="text-muted"><?= date_br($ct['criado_em'], true) ?></small>
              </div>
              <?php if ($ct['descricao']): ?><p class="mb-0 mt-1 small text-muted"><?= e($ct['descricao']) ?></p><?php endif; ?>
              <small class="text-muted">por <?= e($ct['usuario_nome'] ?? 'Sistema') ?></small>
            </li>
            <?php endforeach; ?>
            <?php if (!$contatos): ?><li class="list-group-item text-muted">Nenhum contato registrado.</li><?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal registrar contato -->
<div class="modal fade" id="modalContato" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= url('/crm/contatos') ?>" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
      <div class="modal-header"><h5 class="modal-title">Registrar Contato</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="tipo" class="form-select">
              <option value="ligacao">Ligação</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="email">E-mail</option>
              <option value="visita">Visita</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Direção</label>
            <select name="direcao" class="form-select">
              <option value="saida">Saída (nós ligamos)</option>
              <option value="entrada">Entrada (cliente ligou)</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Assunto</label>
            <input type="text" name="assunto" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Descrição</label>
            <textarea name="descricao" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Próximo contato</label>
            <input type="datetime-local" name="proximo_contato" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

