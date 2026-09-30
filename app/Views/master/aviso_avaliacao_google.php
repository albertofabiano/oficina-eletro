<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-google me-2 text-primary"></i>Aviso: Pedir Avaliação no Google</h4>
  <p class="text-muted small mb-4" style="max-width:780px">
    Aviso pontual da funcionalidade "Pedir avaliação no Google" (botão na tela da OS), mesmo
    texto/print nos dois canais — e-mail (<code>EmailService::avisoAvaliacaoGoogle()</code>) e
    WhatsApp (<code>WhatsAppService::avisoAvaliacaoGoogle()</code>, número da plataforma).
    Público igual ao de "Novidades do Sistema" — <code>reivindicada = 1</code>, quem assina o
    sistema completo, está testando ou reivindicou o Diretório grátis. Uma empresa entra na
    lista abaixo se ainda faltar receber por PELO MENOS UM dos dois canais; cada canal tem seu
    próprio dedup (<code>empresas_email_log</code> / <code>empresas_whatsapp_log</code>) —
    "Disparar para todos agora" manda os dois de uma vez, sem duplicar quem já recebeu.
  </p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Empresas no sistema</div>
        <div class="fs-3 fw-bold"><?= (int) $empresasBase ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Faltam receber (algum canal)</div>
        <div class="fs-3 fw-bold text-primary"><?= (int) $elegiveisUniao ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-envelope-paper me-1"></i>Já receberam por e-mail</div>
        <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosEmail ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-whatsapp me-1"></i>Já receberam por WhatsApp</div>
        <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosWhatsapp ?></div>
      </div></div>
    </div>
  </div>

  <!-- Ação principal, mesmo padrão de "Novidades do Sistema" -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="fw-semibold small"><i class="bi bi-send-fill me-1 text-primary"></i>Disparar para todos agora</div>
        <div class="text-muted" style="font-size:.8rem">
          <?= (int) $elegiveisUniao ?> empresa(s) com pelo menos 1 canal pendente — manda e-mail
          pra quem tem e-mail e ainda não recebeu, e WhatsApp pra quem tem telefone e ainda não
          recebeu (a mesma empresa pode receber pelos dois). Sem limite diário (público já é
          cliente, não é lead frio). Deixe o campo vazio pra enviar pra todas de uma vez, ou
          preencha pra testar num lote pequeno primeiro.
        </div>
      </div>
      <form method="POST" action="<?= url('/master/aviso-avaliacao-google/disparar') ?>" class="d-flex align-items-center gap-2"
            onsubmit="return confirm('Disparar (e-mail + WhatsApp) pra ' + (document.getElementById('avgLimiteTudo').value || <?= (int) $elegiveisUniao ?>) + ' empresa(s)?');">
        <?= csrf_field() ?>
        <input type="number" name="limite" id="avgLimiteTudo" class="form-control form-control-sm" style="width:110px" min="1" placeholder="Todas (<?= (int) $elegiveisUniao ?>)">
        <button class="btn btn-sm btn-primary" <?= $elegiveisUniao === 0 ? 'disabled' : '' ?>>
          <i class="bi bi-send me-1"></i>Disparar para todos agora
        </button>
      </form>
    </div>
  </div>

  <!-- Amostra unificada, com o status dos dois canais lado a lado -->
  <?php if (!$visaoGeral): ?>
    <div class="alert alert-light border mb-4">Nenhuma empresa com canal pendente no momento — todo mundo elegível já recebeu por e-mail e por WhatsApp.</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Amostra dos próximos a receber (10 primeiros)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Contato</th><th>E-mail</th><th>WhatsApp</th></tr></thead>
        <tbody>
          <?php foreach ($visaoGeral as $e): ?>
          <tr>
            <td><?= (int) $e['id'] ?></td>
            <td><?= e($e['nome_contato']) ?></td>
            <td>
              <?php if (empty($e['email'])): ?>
                <span class="text-muted">sem e-mail</span>
              <?php elseif ($e['enviado_email']): ?>
                <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>já recebeu</span>
              <?php else: ?>
                <?= e($e['email']) ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (empty($e['telefone'])): ?>
                <span class="text-muted">sem telefone</span>
              <?php elseif ($e['enviado_whatsapp']): ?>
                <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>já recebeu</span>
              <?php else: ?>
                <?= e($e['telefone']) ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Avançado: disparar só um canal por vez (útil pra testar/isolar um canal específico) -->
  <details class="mb-4">
    <summary class="text-muted small" style="cursor:pointer">Avançado — disparar só por um canal</summary>
    <div class="row g-3 mt-1">
      <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small"><i class="bi bi-envelope-paper me-1"></i>Só e-mail — <?= (int) $elegiveisEmail ?> elegível(is)</div>
            <form method="POST" action="<?= url('/master/aviso-avaliacao-google/disparar-email') ?>" class="d-flex align-items-center gap-2"
                  onsubmit="return confirm('Disparar e-mail pra ' + (document.getElementById('avgLimiteEmail').value || <?= (int) $elegiveisEmail ?>) + ' empresa(s)?');">
              <?= csrf_field() ?>
              <input type="number" name="limite" id="avgLimiteEmail" class="form-control form-control-sm" style="width:90px" min="1" placeholder="Todas">
              <button class="btn btn-sm btn-outline-primary" <?= $elegiveisEmail === 0 ? 'disabled' : '' ?>>Disparar</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small"><i class="bi bi-whatsapp me-1"></i>Só WhatsApp — <?= (int) $elegiveisWhatsapp ?> elegível(is)</div>
            <form method="POST" action="<?= url('/master/aviso-avaliacao-google/disparar-whatsapp') ?>" class="d-flex align-items-center gap-2"
                  onsubmit="return confirm('Disparar WhatsApp pra ' + (document.getElementById('avgLimiteWhatsapp').value || <?= (int) $elegiveisWhatsapp ?>) + ' empresa(s)?');">
              <?= csrf_field() ?>
              <input type="number" name="limite" id="avgLimiteWhatsapp" class="form-control form-control-sm" style="width:90px" min="1" placeholder="Todas">
              <button class="btn btn-sm btn-outline-success" <?= $elegiveisWhatsapp === 0 ? 'disabled' : '' ?>>Disparar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </details>
</div>
