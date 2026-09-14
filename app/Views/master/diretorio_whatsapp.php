<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp do Diretório</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Convite via WhatsApp (número da própria plataforma, mesma instância usada pra redefinir
    senha) pra empresas do ramo — duas frentes independentes, mas com um <strong>único limite
    diário compartilhado</strong> (as duas saem do mesmo número, o risco de bloqueio é do
    número inteiro). Sem rampa de subida de propósito: diferente do e-mail, não existe cota
    documentada pra calibrar contra, é julgamento de risco — comece devagar.
  </p>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Mensagens hoje</div>
        <div class="fs-3 fw-bold <?= $enviadosHoje >= $limiteDiario ? 'text-danger' : 'text-primary' ?>"><?= $enviadosHoje ?>/<?= $limiteDiario ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Restam hoje</div>
        <div class="fs-3 fw-bold text-success"><?= $restanteHoje ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Convites "reivindicar" já enviados</div>
        <div class="fs-3 fw-bold"><?= $totalReivEnviados ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Convites "cadastrar" já enviados</div>
        <div class="fs-3 fw-bold"><?= $totalCadEnviados ?></div>
      </div></div>
    </div>
  </div>

  <!-- Filtros (valem pros dois cards abaixo) -->
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
      <label class="form-label small text-muted mb-1">Busca (nome)</label>
      <input type="text" name="busca" class="form-control form-control-sm" style="width:200px" value="<?= e($filtros['busca']) ?>" placeholder="informática...">
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
      <a href="<?= url('/master/diretorio-whatsapp') ?>" class="btn btn-sm btn-link text-muted">Limpar</a>
    </div>
  </form>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="fw-semibold mb-1"><i class="bi bi-shop-window me-1 text-primary"></i>Reivindicar perfil já existente</div>
          <p class="text-muted small">Empresa já tem ficha publicada no diretório, mas ninguém logou pra gerenciar (<code>empresas.reivindicada = 0</code>).</p>
          <div class="text-muted small mb-3">
            <?= $elegiveisReiv ?> empresa(s) elegível(is) no filtro atual (têm WhatsApp/telefone, ainda não convidadas).
          </div>
          <form method="POST" action="<?= url('/master/diretorio-whatsapp/disparar-reivindicar') ?><?= $_SERVER['QUERY_STRING'] ? '?'.e($_SERVER['QUERY_STRING']) : '' ?>"
                onsubmit="return confirm('Disparar convite pra até ' + Math.min(<?= $elegiveisReiv ?>, <?= $restanteHoje ?>) + ' empresa(s)?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success" <?= ($elegiveisReiv === 0 || $restanteHoje === 0) ? 'disabled' : '' ?>>
              <i class="bi bi-whatsapp me-1"></i>Disparar agora
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="fw-semibold mb-1"><i class="bi bi-shop me-1 text-primary"></i>Cadastrar empresa nova</div>
          <p class="text-muted small">CNPJ do ramo ainda sem ficha nenhuma no diretório (base <code>leads_prospeccao</code>) — link vai pro <a href="<?= url('/diretorio/cadastro-rapido') ?>" target="_blank">formulário rápido</a> (só nome + WhatsApp).</p>
          <div class="text-muted small mb-3">
            <?= $elegiveisCad ?> lead(s) elegível(is) no filtro atual (têm telefone, nunca convidados).
          </div>
          <form method="POST" action="<?= url('/master/diretorio-whatsapp/disparar-cadastrar') ?><?= $_SERVER['QUERY_STRING'] ? '?'.e($_SERVER['QUERY_STRING']) : '' ?>"
                onsubmit="return confirm('Disparar convite pra até ' + Math.min(<?= $elegiveisCad ?>, <?= $restanteHoje ?>) + ' lead(s)?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success" <?= ($elegiveisCad === 0 || $restanteHoje === 0) ? 'disabled' : '' ?>>
              <i class="bi bi-whatsapp me-1"></i>Disparar agora
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <p class="text-muted small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Sem opt-out automático — WhatsApp não tem um "descadastrar" equivalente ao link de e-mail; se alguém reclamar, é manual. Se a taxa de bloqueio/denúncia subir, baixe <code>config/diretorio_whatsapp.php</code> antes de continuar disparando.</p>
</div>
