<!-- ── Visão geral: Plano, Nota Fiscal e Atalhos aparecem primeiro, sem precisar rolar ── -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Plano atual</div>
      <div class="card-body text-center py-4">
        <?php
          $planoCfg     = !empty($empresa['plano_atual']) ? plano_da_empresa($empresa) : null;
          $nomePlano    = $planoCfg['nome'] ?? strtoupper($empresa['plano'] ?? 'basico');
          $licencaAtiva = !empty($empresa['licenca_ate']) && strtotime($empresa['licenca_ate']) >= strtotime(date('Y-m-d'));
        ?>
        <div class="badge bg-primary fs-6 mb-2"><?= strtoupper($nomePlano) ?></div>
        <?php if ($licencaAtiva): ?>
        <div class="text-muted small mt-2">
          Assinatura ativa até: <strong><?= date_br($empresa['licenca_ate']) ?></strong>
        </div>
        <div class="alert alert-success py-2 mt-2 small"><i class="bi bi-check-circle-fill me-1"></i>Assinatura ativa</div>
        <?php elseif ($empresa['trial_ate'] ?? null): ?>
        <div class="text-muted small mt-2">
          Trial até: <strong><?= date_br($empresa['trial_ate']) ?></strong>
        </div>
        <?php $diasRestantes = (int) ceil((strtotime($empresa['trial_ate']) - time()) / 86400); ?>
        <?php if ($diasRestantes > 0): ?>
        <div class="alert alert-info py-2 mt-2 small"><?= $diasRestantes ?> dia(s) restantes</div>
        <?php else: ?>
        <div class="alert alert-danger py-2 mt-2 small">Trial expirado!</div>
        <?php endif; ?>
        <?php elseif (!empty($empresa['licenca_ate'])): ?>
        <div class="alert alert-danger py-2 mt-2 small">Assinatura expirada em <?= date_br($empresa['licenca_ate']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-receipt me-2"></i>Nota Fiscal (NFS-e)</div>
      <div class="card-body">
        <?php if (!empty($nfInteresse)): ?>
          <div class="text-success small mb-2"><i class="bi bi-check-circle-fill me-1"></i>Interesse registrado. Avisaremos quando a emissão de NFS-e estiver disponível na sua cidade.</div>
          <form method="POST" action="<?= url('/empresa/interesse-nf') . painel_qs() ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary w-100">Remover interesse</button>
          </form>
        <?php else: ?>
          <p class="small text-muted mb-3">Em breve o FixaOS poderá <strong>emitir nota fiscal de serviço (NFS-e)</strong> direto da OS. Estamos priorizando as cidades com maior demanda — registre seu interesse pra entrar na fila.</p>
          <form method="POST" action="<?= url('/empresa/interesse-nf') . painel_qs() ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-hand-thumbs-up me-1"></i>Tenho interesse em emitir nota</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Atalhos</div>
      <div class="list-group list-group-flush">
        <a href="<?= url('/usuarios') ?>" class="list-group-item list-group-item-action"><i class="bi bi-person-gear me-2"></i>Gerenciar Usuários</a>
        <a href="<?= url('/setup') ?>" class="list-group-item list-group-item-action"><i class="bi bi-sliders me-2"></i>Assistente de Configuração</a>
        <a href="<?= url('/planos') ?>" target="_top" class="list-group-item list-group-item-action"><i class="bi bi-stars me-2 text-warning"></i>Planos e Assinatura</a>
        <?php if (\App\Core\Auth::isAdmin()): ?>
        <a href="<?= url('/empresa/logs') ?>" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Registro de Ações (Log)</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Logo + Dados da empresa: lado a lado, é o que se edita no dia a dia ── -->
<form id="formEmpresa" method="POST" action="<?= url('/empresa') . painel_qs() ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="row g-3 mb-3">
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-image me-2"></i>Logo da Empresa</div>
        <div class="card-body d-flex flex-column align-items-center text-center gap-3">
          <div id="logoPreviewWrap">
            <?php if (!empty($empresa['logo'])): ?>
            <img src="<?= url('/uploads/' . e($empresa['logo'])) ?>"
                 alt="Logo" id="logoPreviewImg"
                 style="width:100%;height:auto;object-fit:contain;border-radius:8px;border:1px solid #dee2e6;padding:8px;background:#fff">
            <?php else: ?>
            <div id="logoPreviewImg" class="d-flex align-items-center justify-content-center bg-light rounded border"
                 style="width:100%;min-height:100px;color:#adb5bd">
              <div class="text-center">
                <i class="bi bi-image fs-2 d-block"></i>
                <span class="small">Sem logo</span>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="w-100 text-start">
            <label class="form-label small fw-semibold">
              <?= !empty($empresa['logo']) ? 'Trocar logo' : 'Adicionar logo' ?>
            </label>
            <input type="file" name="logo" id="logoInput" class="form-control form-control-sm"
                   accept=".jpg,.jpeg,.png,.gif,.svg,.webp"
                   onchange="previewLogo(this)">
            <div class="form-text">JPG, PNG, SVG ou WebP • Máx. 2MB • 400×200px</div>

            <?php if (!empty($empresa['logo'])): ?>
            <button type="button" class="btn btn-outline-danger btn-sm mt-2 w-100"
                    onclick="if(confirm('Remover a logo atual?')) document.getElementById('formRemoverLogo').submit()">
              <i class="bi bi-trash me-1"></i>Remover logo
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Dados da Empresa</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Razão Social</label>
              <input type="text" name="razao_social" class="form-control" value="<?= e($empresa['razao_social'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">CNPJ</label>
              <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" value="<?= e($empresa['cnpj'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Nome Fantasia</label>
              <input type="text" name="nome_fantasia" class="form-control" value="<?= e($empresa['nome_fantasia'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-semibold"><i class="bi bi-translate me-1"></i>Idioma</label>
              <select name="idioma" class="form-select">
                <option value="pt_BR" <?= ($empresa['idioma']??'pt_BR')==='pt_BR'?'selected':'' ?>>🇧🇷 PT-BR</option>
                <option value="es_MX" <?= ($empresa['idioma']??'')==='es_MX'?'selected':'' ?>>🇲🇽 ES-MX</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">E-mail</label>
              <input type="email" name="email" class="form-control" value="<?= e($empresa['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Telefone</label>
              <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($empresa['telefone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($empresa['whatsapp'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-geo-alt me-2"></i>Endereço</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" placeholder="00000-000" value="<?= e($empresa['cep'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" value="<?= e($empresa['logradouro'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Número</label>
          <input type="text" name="numero" class="form-control" value="<?= e($empresa['numero'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($empresa['complemento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" value="<?= e($empresa['bairro'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" value="<?= e($empresa['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">UF</label>
          <input type="text" name="uf" class="form-control" maxlength="2" value="<?= e($empresa['uf'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-gear me-2"></i>Configurações do Sistema</div>
    <div class="card-body">
      <div class="row g-3">
        <input type="hidden" name="os_prefixo" value="">
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Dígitos</label>
          <select name="os_digitos" class="form-select">
            <?php foreach ([4,5,6,7,8] as $d): ?>
            <option value="<?= $d ?>" <?= ($configs['os_digitos'] ?? 6) == $d ? 'selected' : '' ?>><?= $d ?> dígitos</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Nº inicial das OS</label>
          <input type="number" name="os_numero_inicial" class="form-control" min="1"
            value="<?= e($configs['os_numero_inicial'] ?? 1) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Garantia padrão</label>
          <div class="input-group">
            <input type="number" name="garantia_padrao_dias" class="form-control" min="0"
              value="<?= e($configs['garantia_padrao_dias'] ?? 90) ?>">
            <span class="input-group-text">dias</span>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Prazo retirada</label>
          <div class="input-group">
            <input type="number" name="prazo_retirada_dias" class="form-control" min="1"
              value="<?= e($configs['prazo_retirada_dias'] ?? 30) ?>">
            <span class="input-group-text">dias</span>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Comissão técnico</label>
          <div class="input-group">
            <input type="number" name="comissao_tecnico_percentual" class="form-control" min="0" max="100" step="0.5"
              value="<?= e($configs['comissao_tecnico_percentual'] ?? 0) ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Comissão incide sobre</label>
          <select name="comissao_tecnico_modo" class="form-select">
            <?php $modoAtual = $configs['comissao_tecnico_modo'] ?? 'mao_obra'; ?>
            <option value="mao_obra" <?= $modoAtual !== 'total' ? 'selected' : '' ?>>Só mão de obra</option>
            <option value="total" <?= $modoAtual === 'total' ? 'selected' : '' ?>>Total da OS (peças + mão de obra)</option>
          </select>
          <div class="form-text">Define o que o botão "Puxar da OS" traz ao lançar uma comissão.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Taxas de Cartão (maquininha) -->
  <?php $tx = json_decode($configs['taxas_cartao'] ?? '', true) ?: []; $txCred = $tx['credito'] ?? []; ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-credit-card me-2"></i>Taxas de Cartão (maquininha)</div>
    <div class="card-body">
      <p class="text-muted small mb-3">Informe quanto sua maquininha/adquirente cobra em cada modalidade. O sistema usa isso pra mostrar o <strong>valor líquido</strong> no caixa e, se você quiser, <strong>repassar a taxa ao cliente</strong>. Deixe 0/em branco o que não usar. Você ainda pode ajustar o % na hora da venda.</p>
      <div class="row g-3 mb-2">
        <div class="col-6 col-md-3">
          <label class="form-label small fw-semibold">Pix (via maquininha)</label>
          <div class="input-group">
            <input type="number" name="taxa_pix" class="form-control" min="0" max="100" step="0.01" placeholder="0" value="<?= e($tx['pix'] ?? '') ?>">
            <span class="input-group-text">%</span>
          </div>
          <div class="form-text">Só o pix cobrado pela maquininha — pix recebido direto na sua conta não tem taxa, deixe 0.</div>
        </div>
      </div>
      <div class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label small fw-semibold">Débito</label>
          <div class="input-group">
            <input type="number" name="taxa_debito" class="form-control" min="0" max="100" step="0.01" placeholder="0" value="<?= e($tx['debito'] ?? '') ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-md-9">
          <label class="form-label small fw-semibold d-block mb-1">Quem paga a taxa por padrão?</label>
          <?php $rep = !empty($tx['repassar_padrao']); ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="cartao_repassar_padrao" value="0" id="taxaEmpresa" <?= $rep ? '' : 'checked' ?>>
            <label class="form-check-label small" for="taxaEmpresa"><strong>A empresa</strong> <span class="text-muted">(absorve a taxa — o valor líquido no caixa diminui)</span></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="cartao_repassar_padrao" value="1" id="taxaCliente" <?= $rep ? 'checked' : '' ?>>
            <label class="form-check-label small" for="taxaCliente"><strong>O cliente</strong> <span class="text-muted">(repassa a taxa — somada ao valor que o cliente paga; a empresa recebe o valor cheio)</span></label>
          </div>
        </div>
      </div>
      <label class="form-label small fw-semibold mt-3 d-block">Crédito — taxa por nº de parcelas</label>
      <div class="row g-3">
        <?php for ($p = 1; $p <= 12; $p++): ?>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="input-group input-group-lg">
            <span class="input-group-text fw-bold" style="min-width:56px;justify-content:center"><?= $p ?>x</span>
            <input type="number" name="taxa_credito[<?= $p ?>]" class="form-control" min="0" max="100" step="0.01" placeholder="0,00" value="<?= e($txCred[$p] ?? '') ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>
        <?php endfor; ?>
      </div>
      <div class="form-text mt-2">Ex.: 1x (crédito à vista) 3,50% · 6x 8,00% · 12x 12,00%.</div>

      <hr class="my-3">
      <label class="form-label small fw-semibold d-block mb-1">Como você recebe o crédito parcelado?</label>
      <p class="text-muted small mb-2">Respeita as taxas acima — só muda como as parcelas aparecem no Financeiro.</p>
      <?php $modoReceb = $tx['modo_recebimento'] ?? 'mesmo_dia'; ?>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="modo_recebimento_credito" value="mesmo_dia" id="recebMesmoDia" <?= $modoReceb === 'mes_a_mes' ? '' : 'checked' ?>>
        <label class="form-check-label small" for="recebMesmoDia"><strong>Tudo no mesmo dia</strong> <span class="text-muted">(antecipação — o valor líquido inteiro entra no caixa na data da venda)</span></label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="modo_recebimento_credito" value="mes_a_mes" id="recebMesAMes" <?= $modoReceb === 'mes_a_mes' ? 'checked' : '' ?>>
        <label class="form-check-label small" for="recebMesAMes"><strong>Mês a mês</strong> <span class="text-muted">(a adquirente repassa 1 parcela por mês — cada parcela vira um lançamento no Financeiro, pendente até a data prevista)</span></label>
      </div>
    </div>
  </div>

  <!-- Textos dinâmicos -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-file-text me-2"></i>Textos do Sistema</div>
    <div class="card-body">

      <!-- Aviso jurídico: revisar/adaptar os termos -->
      <div class="alert alert-warning d-flex gap-2 align-items-start mb-4" role="alert" style="border-left:4px solid #f59e0b">
        <i class="bi bi-shield-exclamation fs-5 mt-1"></i>
        <div class="small">
          <strong>Importante — revise estes termos para proteger sua empresa.</strong><br>
          Já deixamos um texto padrão pronto para os campos abaixo, mas ele é <strong>genérico</strong>.
          Recomendamos <strong>ler e adaptar</strong> à realidade do seu negócio (prazos, tipo de equipamento,
          taxas de visita, etc.). Estes textos são impressos na entrada e na entrega da OS e servem de
          <strong>respaldo jurídico</strong> em caso de disputa com o cliente. Em caso de dúvida, consulte um advogado.
        </div>
      </div>

      <!-- Campo oculto que envia o HTML -->
      <input type="hidden" name="texto_entrada_equipamento" id="hiddenEntrada">
      <input type="hidden" name="texto_garantia"            id="hiddenGarantia">

      <!-- Entrada de Equipamento -->
      <div class="mb-4">
        <label class="form-label fw-semibold">Informações de entrada de equipamento</label>
        <p class="text-muted small">Aparece na impressão da OS de entrada (termos, condições de recebimento, etc.)</p>
        <div class="border rounded" id="editorEntrada">
          <div class="editor-toolbar border-bottom p-1 d-flex flex-wrap gap-1" data-target="entrada">
            <?= editorBtn('bold','bi-type-bold','Negrito') ?>
            <?= editorBtn('italic','bi-type-italic','Itálico') ?>
            <?= editorBtn('underline','bi-type-underline','Sublinhado') ?>
            <span class="border-start mx-1"></span>
            <?= editorBtn('insertUnorderedList','bi-list-ul','Lista') ?>
            <?= editorBtn('insertOrderedList','bi-list-ol','Lista numerada') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','entrada','H3')" title="Título">
              <i class="bi bi-type-h3"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','entrada','P')" title="Parágrafo">
              <i class="bi bi-paragraph"></i>
            </button>
            <span class="border-start mx-1"></span>
            <?= editorExtras('entrada') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
              onclick="limparEditor('entrada')" title="Limpar">
              <i class="bi bi-eraser"></i>
            </button>
          </div>
          <div id="ceEntrada" contenteditable="true"
            class="p-3" style="min-height:120px;outline:none;font-size:.9rem;line-height:1.6"
            oninput="syncHidden('entrada')"><?= $configs['texto_entrada_equipamento'] ?? '' ?></div>
        </div>
      </div>

      <!-- Garantia -->
      <div class="mb-0">
        <label class="form-label fw-semibold">Informações sobre garantia</label>
        <p class="text-muted small">Aparece na impressão do comprovante de entrega/fechamento de OS.</p>
        <div class="border rounded" id="editorGarantia">
          <div class="editor-toolbar border-bottom p-1 d-flex flex-wrap gap-1" data-target="garantia">
            <?= editorBtn('bold','bi-type-bold','Negrito','garantia') ?>
            <?= editorBtn('italic','bi-type-italic','Itálico','garantia') ?>
            <?= editorBtn('underline','bi-type-underline','Sublinhado','garantia') ?>
            <span class="border-start mx-1"></span>
            <?= editorBtn('insertUnorderedList','bi-list-ul','Lista','garantia') ?>
            <?= editorBtn('insertOrderedList','bi-list-ol','Lista numerada','garantia') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','garantia','H3')" title="Título">
              <i class="bi bi-type-h3"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','garantia','P')" title="Parágrafo">
              <i class="bi bi-paragraph"></i>
            </button>
            <span class="border-start mx-1"></span>
            <?= editorExtras('garantia') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
              onclick="limparEditor('garantia')" title="Limpar">
              <i class="bi bi-eraser"></i>
            </button>
          </div>
          <div id="ceGarantia" contenteditable="true"
            class="p-3" style="min-height:120px;outline:none;font-size:.9rem;line-height:1.6"
            oninput="syncHidden('garantia')"><?= $configs['texto_garantia'] ?? '' ?></div>
        </div>
      </div>

    </div>
  </div>

  <div class="d-flex justify-content-end mb-3">
    <button type="submit" id="btnSalvarEmpresa" class="btn btn-primary btn-lg"><i class="bi bi-check-lg"></i> Salvar Configurações</button>
  </div>
</form>

<!-- Backup e Restauração -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-database me-2 text-primary"></i>Backup de Dados
  </div>
  <div class="card-body">
    <div class="fw-semibold mb-1 small">Baixar meus dados</div>
    <p class="text-muted small mb-2">
      Baixe um arquivo com <strong>seus clientes e ordens de serviço</strong> (incluindo serviços e peças).
      Seus dados são seus — guarde onde quiser (Google Drive, e-mail, HD).
    </p>
    <a href="<?= url('/empresa/exportar') ?>" class="btn btn-outline-primary btn-sm fw-semibold">
      <i class="bi bi-download me-1"></i>Baixar meus dados (Clientes + OS)
    </a>
  </div>
</div>

<?php if (!empty($empresa['logo'])): ?>
<form id="formRemoverLogo" method="POST" action="<?= url('/empresa/logo/remover') . painel_qs() ?>" style="display:none">
  <?= csrf_field() ?>
</form>
<?php endif; ?>

<!-- Modal de sucesso/erro ao salvar (substitui o alert do topo nesta tela) -->
<div class="modal fade" id="modalResultado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-body py-4">
        <i id="modalResultadoIcone" class="bi bi-check-circle-fill text-success display-4 mb-3"></i>
        <h5 id="modalResultadoTitulo" class="fw-bold mb-2">Tudo certo!</h5>
        <p id="modalResultadoTexto" class="text-muted mb-0"></p>
      </div>
      <div class="modal-footer border-0 justify-content-center pt-0">
        <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<?php
function editorBtn(string $cmd, string $icon, string $title, string $editor = 'entrada'): string {
  return "<button type=\"button\" class=\"btn btn-sm btn-outline-secondary py-0 px-2\"
    onclick=\"execCmd('{$cmd}','{$editor}')\" title=\"{$title}\">
    <i class=\"bi {$icon}\"></i></button>";
}

// Extras: tamanho de fonte, cor do texto e alinhamento (compatíveis com o PDF)
function editorExtras(string $editor): string {
  $sizes = ['1'=>'Pequena','3'=>'Normal','4'=>'Média','5'=>'Grande','6'=>'Muito grande','7'=>'Enorme'];
  $opts  = "<option value=\"\" selected disabled>Tamanho</option>";
  foreach ($sizes as $v => $l) $opts .= "<option value=\"{$v}\">{$l}</option>";
  return "<select class=\"form-select form-select-sm py-0\" style=\"width:auto;font-size:.78rem\"
      title=\"Tamanho da fonte\" onchange=\"execCmd('fontSize','{$editor}',this.value); this.selectedIndex=0\">{$opts}</select>
    <label class=\"btn btn-sm btn-outline-secondary py-0 px-2 mb-0\" title=\"Cor do texto\" style=\"position:relative;overflow:hidden;cursor:pointer\">
      <i class=\"bi bi-palette-fill\"></i>
      <input type=\"color\" value=\"#0d6efd\"
        style=\"position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;cursor:pointer\"
        onchange=\"execCmd('foreColor','{$editor}',this.value)\"></label>
    <span class=\"border-start mx-1\"></span>"
    . editorBtn('justifyLeft','bi-text-left','Alinhar à esquerda',$editor)
    . editorBtn('justifyCenter','bi-text-center','Centralizar',$editor)
    . editorBtn('justifyRight','bi-text-right','Alinhar à direita',$editor)
    . editorBtn('removeFormat','bi-fonts','Remover formatação',$editor);
}
?>

<style>
/* Estes dois editores são WYSIWYG de um texto que vai pra impressão (comprovante/PDF) — sempre
   em papel branco, então o preview fica sempre claro (fundo branco, texto escuro), mesmo com o
   app no tema escuro. Sem isso, texto com cor definida pelo usuário via "Cor do texto" (que
   escolhe a cor pensando num fundo branco, é o que vai ser impresso) podia ficar ilegível —
   texto escuro sobre o fundo escuro do tema, ou uma cor clara que suma no fundo escuro daqui
   mas que no papel impresso nunca apareceria assim.  */
#ceEntrada, #ceGarantia { background:#fff; color:#212529; }
[contenteditable]:focus { box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
[contenteditable] h3 { font-size:1rem; font-weight:700; margin:.4rem 0 .2rem; }
[contenteditable] ul, [contenteditable] ol { padding-left:1.4rem; margin:.3rem 0; }
[contenteditable] p { margin:.2rem 0; }
</style>
<script>
// ── Editor dinâmico ─────────────────────────────────────
function execCmd(cmd, editor, value = null) {
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  ce.focus();
  document.execCommand(cmd, false, value);
  syncHidden(editor);
}

function syncHidden(editor) {
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  document.getElementById('hidden' + editor.charAt(0).toUpperCase() + editor.slice(1)).value = ce.innerHTML;
}

function limparEditor(editor) {
  if (!confirm('Limpar todo o conteúdo?')) return;
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  ce.innerHTML = '';
  syncHidden(editor);
}

function syncTodos() {
  syncHidden('entrada');
  syncHidden('garantia');
}

// ── Salvar via AJAX ──────────────────────────────────────
// Antes disso o submit era um POST comum (recarrega a página) e o modal só aparecia se
// achasse o flash já renderizado no HTML depois do reload — funcionava, mas dependia do
// redirect voltar exatamente pra essa tela. Submetendo via fetch, o modal aparece na hora,
// sem esperar reload nenhum, e continua funcionando com o upload de logo (FormData cobre
// arquivo igual um submit normal).
const formEmpresa = document.getElementById('formEmpresa');
const btnSalvarEmpresa = document.getElementById('btnSalvarEmpresa');
const btnSalvarHtmlOriginal = btnSalvarEmpresa.innerHTML;

formEmpresa.addEventListener('submit', function (ev) {
  ev.preventDefault();
  syncTodos();

  btnSalvarEmpresa.disabled = true;
  btnSalvarEmpresa.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

  const dados = new FormData(formEmpresa);
  dados.append('_ajax', '1');

  fetch(formEmpresa.action, { method: 'POST', body: dados })
    .then(res => res.json().catch(() => ({ sucesso: false, erro: 'Resposta inesperada do servidor.' })))
    .then(res => mostrarModalResultado(!!res.sucesso, res.sucesso ? (res.mensagem || 'Dados salvos com sucesso!') : (res.erro || 'Não foi possível salvar.')))
    .catch(() => mostrarModalResultado(false, 'Falha de conexão — tente novamente.'))
    .finally(() => {
      btnSalvarEmpresa.disabled = false;
      btnSalvarEmpresa.innerHTML = btnSalvarHtmlOriginal;
    });
});

function mostrarModalResultado(sucesso, mensagem) {
  document.getElementById('modalResultadoIcone').className = 'bi ' + (sucesso ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger') + ' display-4 mb-3';
  document.getElementById('modalResultadoTitulo').textContent = sucesso ? 'Tudo certo!' : 'Ops, algo deu errado';
  document.getElementById('modalResultadoTexto').textContent = mensagem;

  const modalEl = document.getElementById('modalResultado');
  // Depois de salvar com sucesso, recarrega ao fechar o modal — reflete no HTML coisas que só
  // o servidor sabe (ex.: nome do arquivo da logo, endereço geocodificado). Só recarrega uma
  // vez (o listener se remove sozinho) e só se salvou de verdade, senão o usuário perderia o
  // que digitou ao só tentar salvar de novo.
  if (sucesso) {
    modalEl.addEventListener('hidden.bs.modal', () => location.reload(), { once: true });
  }
  new bootstrap.Modal(modalEl).show();
}

// ── Preview logo ────────────────────────────────────────
function previewLogo(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  // Validar tamanho no client
  if (file.size > 2 * 1024 * 1024) {
    alert('Arquivo muito grande! Máximo 2MB.');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const wrap = document.getElementById('logoPreviewWrap');
    wrap.innerHTML = `<img src="${e.target.result}" alt="Preview"
      style="width:100%;height:auto;object-fit:contain;border-radius:8px;border:1px solid #dee2e6;padding:8px;background:#fff">`;
  };
  reader.readAsDataURL(file);
}

// ── Modal de sucesso/erro pras outras ações da tela (fora do form de config) ──
// "Salvar Configurações" mostra o modal na hora via AJAX (acima). As outras ações desta
// página — registrar/remover interesse em NF, remover logo — continuam sendo POST comum
// (recarrega a página); aqui a gente pega o flash que o layout já renderizou no topo e
// mostra no mesmo modal, escondendo o banner pra não duplicar a mensagem.
document.addEventListener('DOMContentLoaded', function () {
  const flashWrap = document.querySelector('.page-content.pb-0');
  if (!flashWrap) return;
  const alertEl = flashWrap.querySelector('.alert-success, .alert-danger, .alert-warning, .alert-info');
  if (!alertEl) return;

  const ehErro = alertEl.classList.contains('alert-danger') || alertEl.classList.contains('alert-warning');
  flashWrap.style.display = 'none';
  mostrarModalResultado(!ehErro, alertEl.textContent.trim());
});
</script>
