<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-megaphone me-2 text-primary"></i>Novidades do Sistema</h4>
  <p class="text-muted small mb-4" style="max-width:780px">
    Aviso de melhorias recentes (<code>EmailService::novidadesSistema()</code>) pra empresas
    <strong>já cadastradas de verdade</strong> — <code>reivindicada = 1</code>, tanto quem assina
    o sistema completo quanto quem se cadastrou grátis no Diretório com login próprio. Não inclui
    as fichas importadas de CNPJ sem conta nenhuma (essas usam os e-mails de prospecção/
    reivindicação, não este). Dedup por <code>empresas_email_log</code> — nunca reenvia pra quem
    já recebeu esta campanha.
  </p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Elegíveis agora</div>
        <div class="fs-3 fw-bold text-primary"><?= (int) $elegiveis ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Já receberam esta campanha</div>
        <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviados ?></div>
      </div></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="fw-semibold small"><i class="bi bi-envelope-paper me-1 text-primary"></i>Disparar "Novidades do Sistema"</div>
        <div class="text-muted" style="font-size:.8rem">
          <?= (int) $elegiveis ?> empresa(s) elegível(is) — sem limite diário (público já é
          cliente, não é lead frio). Deixe o campo abaixo vazio pra enviar pra todas de uma vez,
          ou preencha pra testar num lote pequeno primeiro.
        </div>
      </div>
      <form method="POST" action="<?= url('/master/novidades-sistema/disparar') ?>" class="d-flex align-items-center gap-2"
            onsubmit="return confirm('Disparar pra ' + (document.getElementById('nsLimite').value || <?= (int) $elegiveis ?>) + ' empresa(s)?');">
        <?= csrf_field() ?>
        <input type="number" name="limite" id="nsLimite" class="form-control form-control-sm" style="width:110px" min="1" placeholder="Todas (<?= (int) $elegiveis ?>)">
        <button class="btn btn-sm btn-primary" <?= $elegiveis === 0 ? 'disabled' : '' ?>>
          <i class="bi bi-send me-1"></i>Disparar agora
        </button>
      </form>
    </div>
  </div>

  <?php if (!$amostra): ?>
    <div class="alert alert-light border">Nenhuma empresa elegível no momento.</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Amostra dos próximos a receber (10 primeiros)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Contato</th><th>E-mail</th></tr></thead>
        <tbody>
          <?php foreach ($amostra as $e): ?>
          <tr>
            <td><?= (int) $e['id'] ?></td>
            <td><?= e($e['nome_contato']) ?></td>
            <td><?= e($e['email']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
