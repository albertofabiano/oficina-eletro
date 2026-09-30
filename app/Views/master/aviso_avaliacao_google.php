<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-google me-2 text-primary"></i>Aviso: Pedir Avaliação no Google</h4>
  <p class="text-muted small mb-4" style="max-width:780px">
    Aviso pontual da funcionalidade "Pedir avaliação no Google" (botão na tela da OS), mesmo
    texto nos dois canais — e-mail (<code>EmailService::avisoAvaliacaoGoogle()</code>) e WhatsApp
    (<code>WhatsAppService::avisoAvaliacaoGoogle()</code>, número da plataforma). Público igual
    ao de "Novidades do Sistema" — <code>reivindicada = 1</code>, quem assina o sistema completo,
    está testando ou reivindicou o Diretório grátis. Cada canal tem seu próprio dedup
    (<code>empresas_email_log</code> / <code>empresas_whatsapp_log</code>) e nunca reenvia pra
    quem já recebeu por aquele canal — mas os dois são independentes: uma empresa pode receber
    pelos dois, só por um, ou por nenhum se não tiver e-mail/telefone cadastrado.
  </p>

  <div class="row g-4">
    <!-- ── E-mail ───────────────────────────────────────────── -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-envelope-paper me-1 text-primary"></i>Canal: E-mail</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="text-muted small">Elegíveis agora</div>
              <div class="fs-3 fw-bold text-primary"><?= (int) $elegiveisEmail ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Já receberam</div>
              <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosEmail ?></div>
            </div>
          </div>

          <form method="POST" action="<?= url('/master/aviso-avaliacao-google/disparar-email') ?>" class="d-flex align-items-center gap-2 mb-3"
                onsubmit="return confirm('Disparar e-mail pra ' + (document.getElementById('avgLimiteEmail').value || <?= (int) $elegiveisEmail ?>) + ' empresa(s)?');">
            <?= csrf_field() ?>
            <input type="number" name="limite" id="avgLimiteEmail" class="form-control form-control-sm" style="width:110px" min="1" placeholder="Todas (<?= (int) $elegiveisEmail ?>)">
            <button class="btn btn-sm btn-primary" <?= $elegiveisEmail === 0 ? 'disabled' : '' ?>>
              <i class="bi bi-send me-1"></i>Disparar agora
            </button>
          </form>

          <?php if (!$amostraEmail): ?>
            <div class="alert alert-light border mb-0 small">Nenhuma empresa elegível por e-mail no momento.</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light"><tr><th>#</th><th>Contato</th><th>E-mail</th></tr></thead>
              <tbody>
                <?php foreach ($amostraEmail as $e): ?>
                <tr>
                  <td><?= (int) $e['id'] ?></td>
                  <td><?= e($e['nome_contato']) ?></td>
                  <td><?= e($e['email']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ── WhatsApp ─────────────────────────────────────────── -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-whatsapp me-1 text-success"></i>Canal: WhatsApp</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="text-muted small">Elegíveis agora</div>
              <div class="fs-3 fw-bold text-primary"><?= (int) $elegiveisWhatsapp ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Já receberam</div>
              <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosWhatsapp ?></div>
            </div>
          </div>

          <form method="POST" action="<?= url('/master/aviso-avaliacao-google/disparar-whatsapp') ?>" class="d-flex align-items-center gap-2 mb-3"
                onsubmit="return confirm('Disparar WhatsApp pra ' + (document.getElementById('avgLimiteWhatsapp').value || <?= (int) $elegiveisWhatsapp ?>) + ' empresa(s)?');">
            <?= csrf_field() ?>
            <input type="number" name="limite" id="avgLimiteWhatsapp" class="form-control form-control-sm" style="width:110px" min="1" placeholder="Todas (<?= (int) $elegiveisWhatsapp ?>)">
            <button class="btn btn-sm btn-success" <?= $elegiveisWhatsapp === 0 ? 'disabled' : '' ?>>
              <i class="bi bi-send me-1"></i>Disparar agora
            </button>
          </form>

          <div class="text-muted small mb-3">
            Sai do número da <strong>plataforma</strong> (mesma instância do reset de senha por
            WhatsApp), não do WhatsApp de cada empresa. Elegível exige telefone de algum usuário
            cadastrado (prioriza o admin) — quem não tem telefone nenhum fica de fora.
          </div>

          <?php if (!$amostraWhatsapp): ?>
            <div class="alert alert-light border mb-0 small">Nenhuma empresa elegível por WhatsApp no momento.</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light"><tr><th>#</th><th>Contato</th><th>Telefone</th></tr></thead>
              <tbody>
                <?php foreach ($amostraWhatsapp as $e): ?>
                <tr>
                  <td><?= (int) $e['id'] ?></td>
                  <td><?= e($e['nome_contato']) ?></td>
                  <td><?= e($e['telefone']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
