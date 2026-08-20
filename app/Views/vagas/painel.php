<?php
$regimes     = \App\Controllers\VagasController::REGIMES;
$jornadas    = \App\Controllers\VagasController::JORNADAS;
$modalidades = \App\Controllers\VagasController::MODALIDADES;
$niveis      = \App\Controllers\VagasController::NIVEIS;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Vagas de Emprego</h5>
    <p class="text-muted small mb-0">Publique vagas pra técnico de eletrônicos — o candidato entra em contato direto pelo seu WhatsApp, sem cadastro nenhum no sistema.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= url('/vagas') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Ver mural público</a>
    <?php if ($planoCompleto): ?>
    <button type="button" class="btn btn-primary btn-sm" onclick="vagaAbrirNova()"><i class="bi bi-plus-lg me-1"></i>Nova vaga</button>
    <?php endif; ?>
  </div>
</div>

<?php if (!$planoCompleto): ?>
<div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#fff7ed,#fff)">
  <div class="card-body text-center py-5">
    <i class="bi bi-briefcase fs-1 mb-3" style="color:#78350f"></i>
    <h6 class="fw-bold mb-2" style="color:#78350f">Publicar vaga é um recurso de assinante</h6>
    <p class="mb-3" style="color:#9a3412;max-width:460px;margin-inline:auto">
      Divulgue vagas pra sua equipe técnica no mural público do FixaOS, sem custo extra —
      é só ter um plano pago ativo. Enquanto isso, você pode navegar no mural pra ver como fica.
    </p>
    <a href="<?= url('/planos') ?>" class="btn btn-warning fw-semibold"><i class="bi bi-star-fill me-1"></i>Ver planos</a>
  </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Vaga</th>
          <th>Regime</th>
          <th>Local</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vagas as $v): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($v['titulo']) ?></div>
            <div class="text-muted" style="font-size:.76rem"><?= e($jornadas[$v['jornada']] ?? $v['jornada']) ?> · <?= e($modalidades[$v['modalidade']] ?? $v['modalidade']) ?><?php if ($v['nivel']): ?> · <?= e($niveis[$v['nivel']] ?? $v['nivel']) ?><?php endif; ?></div>
          </td>
          <td><?= e($regimes[$v['regime']] ?? $v['regime']) ?></td>
          <td class="text-muted small"><?= e(trim(($v['cidade'] ?? '') . (!empty($v['uf']) ? '/' . $v['uf'] : ''))) ?: '—' ?></td>
          <td>
            <?php if ($v['status'] === 'aberta'): ?>
            <span class="badge bg-success bg-opacity-10 text-success-emphasis border border-success border-opacity-25">Aberta</span>
            <?php else: ?>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Encerrada</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-secondary" title="Editar"
                    onclick='vagaAbrirEdicao(<?= json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="POST" action="<?= url('/empresa/vagas/' . $v['id'] . '/status') ?>" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-secondary" title="<?= $v['status'] === 'aberta' ? 'Encerrar' : 'Reabrir' ?>">
                <i class="bi <?= $v['status'] === 'aberta' ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
              </button>
            </form>
            <form method="POST" action="<?= url('/empresa/vagas/' . $v['id'] . '/excluir') ?>" class="d-inline"
                  onsubmit="return confirm('Excluir a vaga <?= e(addslashes($v['titulo'])) ?>?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$vagas): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">
          <i class="bi bi-briefcase fs-3 d-block mb-2"></i>Nenhuma vaga publicada ainda.
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($planoCompleto): ?>
<div class="modal fade" id="modalVaga" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="POST" id="formVaga">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalVagaTitulo"><i class="bi bi-briefcase me-2"></i>Nova vaga</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Título da vaga *</label>
          <input type="text" name="titulo" id="vgTitulo" class="form-control" required maxlength="150" placeholder="Ex: Técnico em eletrônicos — celulares">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Descrição / atribuições *</label>
          <textarea name="descricao" id="vgDescricao" class="form-control" rows="3" required placeholder="O que a pessoa vai fazer no dia a dia..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Requisitos</label>
          <textarea name="requisitos" id="vgRequisitos" class="form-control" rows="2" placeholder="Experiência, cursos, ferramentas..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Benefícios</label>
          <textarea name="beneficios" id="vgBeneficios" class="form-control" rows="2" placeholder="Vale-transporte, comissão, plano de saúde..."></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Regime</label>
            <select name="regime" id="vgRegime" class="form-select">
              <?php foreach ($regimes as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Jornada</label>
            <select name="jornada" id="vgJornada" class="form-select">
              <?php foreach ($jornadas as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Modalidade</label>
            <select name="modalidade" id="vgModalidade" class="form-select">
              <?php foreach ($modalidades as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Nível</label>
            <select name="nivel" id="vgNivel" class="form-select">
              <option value="">Não informar</option>
              <?php foreach ($niveis as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Cidade</label>
            <input type="text" name="cidade" id="vgCidade" class="form-control" placeholder="<?= e($empresa['cidade'] ?? '') ?>">
            <div class="form-text">Vazio usa a cidade da sua empresa.</div>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">UF</label>
            <input type="text" name="uf" id="vgUf" class="form-control" maxlength="2" style="text-transform:uppercase" placeholder="<?= e($empresa['uf'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Salário</label>
            <div class="d-flex align-items-center gap-2">
              <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="text" name="salario_min" id="vgSalMin" class="form-control" placeholder="mín.">
              </div>
              <span class="text-muted">a</span>
              <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="text" name="salario_max" id="vgSalMax" class="form-control" placeholder="máx.">
              </div>
            </div>
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" name="salario_a_combinar" value="1" id="vgACombinar">
              <label class="form-check-label small" for="vgACombinar">A combinar (não mostrar faixa salarial)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar vaga</button>
      </div>
    </form>
  </div>
</div>

<script>
function vagaAbrirNova() {
  var f = document.getElementById('formVaga');
  f.reset();
  f.action = '<?= url('/empresa/vagas') ?>';
  document.getElementById('modalVagaTitulo').innerHTML = '<i class="bi bi-briefcase me-2"></i>Nova vaga';
  document.getElementById('vgSalMin').disabled = false;
  document.getElementById('vgSalMax').disabled = false;
  new bootstrap.Modal(document.getElementById('modalVaga')).show();
}

function vagaAbrirEdicao(v) {
  var f = document.getElementById('formVaga');
  f.reset();
  f.action = '<?= url('/empresa/vagas') ?>/' + v.id;
  document.getElementById('modalVagaTitulo').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar vaga';
  document.getElementById('vgTitulo').value = v.titulo || '';
  document.getElementById('vgDescricao').value = v.descricao || '';
  document.getElementById('vgRequisitos').value = v.requisitos || '';
  document.getElementById('vgBeneficios').value = v.beneficios || '';
  document.getElementById('vgRegime').value = v.regime || 'clt';
  document.getElementById('vgJornada').value = v.jornada || 'integral';
  document.getElementById('vgModalidade').value = v.modalidade || 'presencial';
  document.getElementById('vgNivel').value = v.nivel || '';
  document.getElementById('vgCidade').value = v.cidade || '';
  document.getElementById('vgUf').value = v.uf || '';
  document.getElementById('vgSalMin').value = v.salario_min || '';
  document.getElementById('vgSalMax').value = v.salario_max || '';
  document.getElementById('vgACombinar').checked = !!Number(v.salario_a_combinar);
  new bootstrap.Modal(document.getElementById('modalVaga')).show();
}

document.getElementById('vgACombinar').addEventListener('change', function () {
  document.getElementById('vgSalMin').disabled = this.checked;
  document.getElementById('vgSalMax').disabled = this.checked;
});
</script>
<?php endif; ?>
