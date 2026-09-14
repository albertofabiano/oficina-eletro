<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp do Diretório</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Convite via WhatsApp (número da própria plataforma, mesma instância usada pra redefinir
    senha) pra empresas do ramo — três frentes independentes, mas com um <strong>único limite
    diário compartilhado</strong> (todas saem do mesmo número, o risco de bloqueio é do
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
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Convites manuais já enviados</div>
        <div class="fs-3 fw-bold"><?= $totalManualEnviados ?></div>
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

  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-1"><i class="bi bi-pencil-square me-1 text-primary"></i>Lista manual (curada por você)</div>
          <p class="text-muted small mb-3">
            Cole nome + WhatsApp de empresas que você mesmo achou na internet (Google Maps,
            Instagram, site da empresa etc.) — foge do risco de número morto/errado que a base
            de CNPJ (<code>leads_prospeccao</code>) pode ter. Uma empresa por linha, qualquer
            separador antes do telefone funciona, por exemplo:
          </p>
          <pre class="bg-light border rounded p-2 small text-muted mb-3" style="white-space:pre-wrap">Assistência Silva; 11999998888
Conserto Rápido Eletrônicos - (21) 98888-7777
Fix Celulares, 71 97777-6666</pre>

          <form method="POST" action="<?= url('/master/diretorio-whatsapp/manual/adicionar') ?>" class="mb-4">
            <?= csrf_field() ?>
            <textarea name="lista" class="form-control mb-2" rows="5" placeholder="Cole a lista aqui, uma empresa por linha..."></textarea>
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Adicionar à lista</button>
          </form>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold small">Pendentes na lista (<?= $pendentesManual ?>)</div>
            <form method="POST" action="<?= url('/master/diretorio-whatsapp/disparar-manual') ?>"
                  onsubmit="return confirm('Disparar convite pra até ' + Math.min(<?= $pendentesManual ?>, <?= $restanteHoje ?>) + ' empresa(s) da lista manual?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-success" <?= ($pendentesManual === 0 || $restanteHoje === 0) ? 'disabled' : '' ?>>
                <i class="bi bi-whatsapp me-1"></i>Disparar agora
              </button>
            </form>
          </div>

          <?php if (empty($listaManual)): ?>
            <p class="text-muted small mb-0">Nenhuma empresa pendente — cole uma lista acima.</p>
          <?php else: ?>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto">
              <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Empresa</th><th>WhatsApp</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                  <?php foreach ($listaManual as $c): ?>
                    <tr>
                      <td><?= e($c['nome_empresa']) ?></td>
                      <td><?= e($c['whatsapp']) ?></td>
                      <td class="text-end">
                        <form method="POST" action="<?= url('/master/diretorio-whatsapp/manual/' . $c['id'] . '/excluir') ?>"
                              onsubmit="return confirm('Remover esta empresa da lista?');" class="d-inline">
                          <?= csrf_field() ?>
                          <button class="btn btn-sm btn-outline-danger" title="Remover"><i class="bi bi-trash3"></i></button>
                        </form>
                      </td>
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

  <p class="text-muted small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Sem opt-out automático — WhatsApp não tem um "descadastrar" equivalente ao link de e-mail; se alguém reclamar, é manual. Se a taxa de bloqueio/denúncia subir, baixe <code>config/diretorio_whatsapp.php</code> antes de continuar disparando.</p>
</div>
