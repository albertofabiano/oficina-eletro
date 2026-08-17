<style>
  /* Fita arco-íris no slider de matiz */
  .hsl-hue { --rainbow: linear-gradient(to right,#f00,#ff0,#0f0,#0ff,#00f,#f0f,#f00); }
  .hsl-hue::-webkit-slider-runnable-track { background: var(--rainbow); height: 10px; border-radius: 6px; }
  .hsl-hue::-moz-range-track { background: var(--rainbow); height: 10px; border-radius: 6px; }
</style>

<div class="row g-4">

  <!-- Lista de status -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Status cadastrados</span>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small d-none d-md-inline">Arraste para reordenar</span>
          <button type="button" class="btn btn-sm btn-success" onclick="novoStatus()">
            <i class="bi bi-plus-lg"></i> Novo status
          </button>
        </div>
      </div>

      <ul class="list-group list-group-flush" id="listaStatus">
        <?php foreach ($lista as $s): ?>
        <?php $bloqueado = !empty($s['bloqueado']); ?>
        <li class="list-group-item d-flex align-items-center gap-3 py-3" data-id="<?= $s['id'] ?>" data-bloqueado="<?= $bloqueado ? 1 : 0 ?>">
          <!-- Handle drag — todos reordenáveis (inclusive os nativos) -->
          <i class="bi bi-grip-vertical <?= $bloqueado ? 'text-primary' : 'text-muted' ?> fs-5" style="cursor:grab" title="Arraste para reordenar"></i>

          <!-- Cor -->
          <div class="rounded-circle flex-shrink-0" style="width:14px;height:14px;background:<?= e($s['cor']) ?>"></div>

          <!-- Info -->
          <div class="flex-grow-1">
            <div class="fw-semibold"><?= e($s['nome']) ?><?php if ($bloqueado): ?> <i class="bi bi-lock-fill text-muted small" title="Status nativo do sistema"></i><?php endif; ?></div>
            <div class="small text-muted">
              <?php $tipos = [
                'aberta'=>'Aberta','em_andamento'=>'Em andamento','aguardando'=>'Aguardando',
                'concluida'=>'Concluída','entregue'=>'Entregue','cancelada'=>'Cancelada','garantia'=>'Garantia'
              ]; ?>
              Tipo: <?= $tipos[$s['tipo']] ?? $s['tipo'] ?> &nbsp;•&nbsp; Ordem: <?= $s['ordem'] ?>
              <?php if (!empty($s['permite_fechar'])): ?>
              &nbsp;•&nbsp; <span class="text-success"><i class="bi bi-check-circle-fill"></i> Fecha OS</span>
              <?php endif; ?>
              <?php if (!empty($s['fecha_sem_cobranca'])): ?>
              &nbsp;•&nbsp; <span class="text-danger"><i class="bi bi-lightning-fill"></i> Fecha sozinho, sem cobrança</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Badge OS vinculadas -->
          <span class="badge bg-light text-dark border" title="OS vinculadas">
            <?= $s['total_os'] ?> OS
          </span>

          <!-- Preview badge -->
          <span class="badge" style="background:<?= e($s['cor']) ?>;color:<?= e($s['cor_fonte'] ?? '#ffffff') ?>"><?= e($s['nome']) ?></span>

          <!-- Ações -->
          <div class="d-flex gap-1 align-items-center">
            <?php if ($bloqueado): ?>
            <span class="badge px-2 py-1" style="background:#eef2ff;color:#4338ca;font-size:.7rem" title="Status nativo do sistema — só as cores podem ser alteradas">
              <i class="bi bi-shield-lock-fill me-1"></i>Nativo
            </span>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary"
              onclick="abrirEdicao(<?= $s['id'] ?>, '<?= e(addslashes($s['nome'])) ?>', '<?= e($s['cor']) ?>', '<?= e($s['cor_fonte'] ?? '#ffffff') ?>', '<?= e($s['tipo']) ?>', <?= (int)($s['permite_fechar'] ?? 0) ?>, <?= (int)($s['sem_valor'] ?? 0) ?>, <?= (int)($s['fecha_sem_cobranca'] ?? 0) ?>, <?= $bloqueado ? 'true' : 'false' ?>)"
              title="<?= $bloqueado ? 'Ajustar cores e comportamento' : 'Editar' ?>">
              <i class="bi bi-<?= $bloqueado ? 'sliders' : 'pencil' ?>"></i>
            </button>
            <?php if ($bloqueado): ?>
            <button class="btn btn-sm btn-outline-danger" disabled title="Status nativo — não pode ser excluído">
              <i class="bi bi-trash"></i>
            </button>
            <?php elseif ($s['total_os'] == 0): ?>
            <a href="#" class="btn btn-sm btn-outline-danger"
              data-method="DELETE"
              data-href="<?= url('/os/status/' . $s['id']) ?>"
              data-confirm="Excluir o status «<?= e($s['nome']) ?>»?"
              title="Excluir">
              <i class="bi bi-trash"></i>
            </a>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-danger" disabled title="Possui OS vinculadas">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (!$lista): ?>
        <li class="list-group-item text-center text-muted py-4">Nenhum status cadastrado.</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Dica tipos -->
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold small">O que cada tipo significa?</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0 small">
          <thead class="table-light"><tr><th>Tipo</th><th>Comportamento no sistema</th></tr></thead>
          <tbody>
            <tr><td><span class="badge bg-secondary">Aberta</span></td><td>OS recém-criada, aguardando triagem</td></tr>
            <tr><td><span class="badge bg-info">Em andamento</span></td><td>Técnico trabalhando — aparece nas OS ativas</td></tr>
            <tr><td><span class="badge bg-warning text-dark">Aguardando</span></td><td>Parada (aprovação, peça, cliente) — não conta como atrasada</td></tr>
            <tr><td><span class="badge bg-success">Concluída</span></td><td>Serviço pronto — preenche data_conclusao automaticamente</td></tr>
            <tr><td><span class="badge bg-success">Entregue</span></td><td>Cliente retirou — preenche data_entrega automaticamente</td></tr>
            <tr><td><span class="badge bg-danger">Cancelada</span></td><td>OS encerrada sem conserto — sai dos relatórios de faturamento</td></tr>
            <tr><td><span class="badge" style="background:#dc3545">Garantia</span></td><td>Retorno em garantia — exibida no filtro "Em Garantia" e separada dos status regulares</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulário -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px">
      <div class="card-header bg-white fw-semibold" id="formTitulo">
        <i class="bi bi-plus-circle me-1 text-primary"></i> Novo Status
      </div>
      <div class="card-body">
        <form method="POST" action="<?= url('/os/status') . painel_qs() ?>" id="formStatus">
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="statusId" value="">

          <div id="lockNote" class="alert alert-primary py-2 px-3 small d-flex align-items-center gap-2" style="display:none">
            <i class="bi bi-shield-lock-fill"></i>
            <span><b>Status nativo do sistema.</b> Nome e tipo são fixos (protegem o fluxo). Você pode ajustar as <b>cores</b> e os <b>dois comportamentos abaixo</b> (botão “Fechar OS” e bloqueio de valor).</span>
          </div>

          <div id="newNote" class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2">
            <i class="bi bi-stars"></i>
            <span><b>Status personalizado.</b> Você define nome, cores e o <b>tipo</b> (que controla o comportamento no sistema). Ele entra depois dos nativos e pode ser reordenado arrastando na lista.</span>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nome do status *</label>
            <input type="text" name="nome" id="statusNome" class="form-control"
              placeholder="Ex: Em diagnóstico, Aguardando peça..." required maxlength="60">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo *</label>
            <select name="tipo" id="statusTipo" class="form-select">
              <option value="aberta">Aberta</option>
              <option value="em_andamento">Em andamento</option>
              <option value="aguardando">Aguardando</option>
              <option value="concluida">Concluída</option>
              <option value="entregue">Entregue</option>
              <option value="cancelada">Cancelada</option>
              <option value="garantia">Garantia</option>
            </select>
            <div class="form-text">Define o comportamento automático no sistema.</div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="permite_fechar" id="statusPermiteFechar" value="1">
              <label class="form-check-label fw-semibold" for="statusPermiteFechar">
                Exibir botão “Fechar OS” neste status
              </label>
              <div class="form-text">Quando marcado, a OS neste status mostra o botão de fechamento/baixa.</div>
            </div>
          </div>

          <div class="mb-3" id="wrapFechaSemCobranca" style="display:none">
            <div class="form-check border border-danger-subtle rounded p-2" style="background:#fff5f5">
              <input type="checkbox" class="form-check-input" name="fecha_sem_cobranca" id="statusFechaSemCobranca" value="1">
              <label class="form-check-label fw-semibold text-danger" for="statusFechaSemCobranca">
                <i class="bi bi-lightning-fill"></i> Fechar automaticamente sem cobrança neste status
              </label>
              <div class="form-text">
                Assim que uma OS entrar neste status (por qualquer caminho — troca rápida de status ou
                edição da OS), ela é fechada na hora como “<span id="fscNomePreview">Sem Conserto</span>”:
                vai pro status “Fechado”, sem gerar cobrança nem lançamento no Financeiro, com o mesmo
                comprovante de “Sem Conserto/Recusado” já usado hoje. Não pede confirmação — use só em
                status que realmente significam devolução sem custo (ex.: Sem Conserto, Recusado, Descartado).
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Cor do badge (fundo)</label>
            <div class="d-flex gap-2 align-items-center flex-wrap mb-2" id="paletaCores">
              <?php foreach ([
                '#6c757d','#0d6efd','#0dcaf0','#198754','#20c997',
                '#ffc107','#fd7e14','#dc3545','#6f42c1','#d63384','#212529'
              ] as $cor): ?>
              <div class="cor-btn rounded-circle border"
                style="width:28px;height:28px;background:<?= $cor ?>;cursor:pointer"
                data-cor="<?= $cor ?>"
                onclick="selecionarCor('<?= $cor ?>')"
                title="<?= $cor ?>"></div>
              <?php endforeach; ?>
            </div>
            <div class="input-group">
              <input type="color" name="cor" id="statusCor" class="form-control form-control-color" value="#6c757d">
              <input type="text" id="corTexto" class="form-control" value="#6c757d" maxlength="7"
                placeholder="#RRGGBB" oninput="corBadgeDigitada(this.value)">
            </div>

            <!-- Misturador de cor personalizada (sliders inline — não abre o seletor do sistema) -->
            <div class="border rounded p-2 mt-2" style="background:#f8f9fb">
              <div class="small text-muted mb-2"><i class="bi bi-sliders2 me-1"></i>Ou misture sua própria cor:</div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="small text-muted" style="width:64px">Matiz</span>
                <input type="range" class="form-range hsl-hue" id="hslH" min="0" max="360" value="270" oninput="aplicarHsl()">
              </div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="small text-muted" style="width:64px">Saturação</span>
                <input type="range" class="form-range" id="hslS" min="0" max="100" value="65" oninput="aplicarHsl()">
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="small text-muted" style="width:64px">Brilho</span>
                <input type="range" class="form-range" id="hslL" min="0" max="100" value="52" oninput="aplicarHsl()">
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Cor da fonte</label>
            <div class="d-flex gap-2 align-items-center flex-wrap mb-2" id="paletaFonte">
              <?php foreach (['#ffffff','#000000','#1e293b','#ffc107','#0d6efd','#dc3545','#198754'] as $cf): ?>
              <div class="cor-fonte-btn rounded-circle border"
                style="width:28px;height:28px;background:<?= $cf ?>;cursor:pointer;border-color:#aaa!important"
                data-cor="<?= $cf ?>"
                onclick="selecionarCorFonte('<?= $cf ?>')"
                title="<?= $cf ?>"></div>
              <?php endforeach; ?>
            </div>
            <div class="input-group">
              <input type="color" name="cor_fonte" id="statusCorFonte" class="form-control form-control-color" value="#ffffff">
              <input type="text" id="corFonteTexto" class="form-control" value="#ffffff" maxlength="7"
                oninput="document.getElementById('statusCorFonte').value=this.value;atualizarPreviewBadge(document.getElementById('statusCor').value)">
              <span class="input-group-text">
                <span id="previewBadge" class="badge px-3 py-2" style="background:#6c757d;color:#ffffff;font-size:.85rem">Preview</span>
              </span>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill" id="btnSalvar">
              <i class="bi bi-check-lg"></i> Salvar
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnCancelar" onclick="limparForm()" style="display:none">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Paleta de cores de fundo
function selecionarCor(cor) {
  document.getElementById('statusCor').value = cor;
  document.getElementById('corTexto').value  = cor;
  atualizarPreviewBadge(cor);
  syncSlidersFromHex(cor);
  document.querySelectorAll('.cor-btn').forEach(b => b.style.outline = 'none');
  document.querySelector(`.cor-btn[data-cor="${cor}"]`)?.style.setProperty('outline','3px solid #000');
}

// Paleta de cores de fonte
function selecionarCorFonte(cor) {
  document.getElementById('statusCorFonte').value = cor;
  document.getElementById('corFonteTexto').value  = cor;
  atualizarPreviewBadge(document.getElementById('statusCor').value);
  document.querySelectorAll('.cor-fonte-btn').forEach(b => b.style.outline = 'none');
  document.querySelector(`.cor-fonte-btn[data-cor="${cor}"]`)?.style.setProperty('outline','3px solid #666');
}

function atualizarPreviewBadge(cor) {
  const nome     = document.getElementById('statusNome').value || 'Preview';
  const corFonte = document.getElementById('statusCorFonte').value || '#ffffff';
  const badge    = document.getElementById('previewBadge');
  badge.style.background = cor;
  badge.style.color      = corFonte;
  badge.textContent      = nome;
}

// ── Misturador de cor (HSL) — cria cores sem abrir o seletor do sistema ──
function hslToHex(h, s, l) {
  s /= 100; l /= 100;
  const k = n => (n + h / 30) % 12;
  const a = s * Math.min(l, 1 - l);
  const f = n => l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
  const toHex = x => Math.round(255 * x).toString(16).padStart(2, '0');
  return '#' + toHex(f(0)) + toHex(f(8)) + toHex(f(4));
}
function hexToHsl(hex) {
  hex = hex.replace('#', '');
  if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
  let r = parseInt(hex.substr(0,2),16)/255, g = parseInt(hex.substr(2,2),16)/255, b = parseInt(hex.substr(4,2),16)/255;
  const max = Math.max(r,g,b), min = Math.min(r,g,b); let h, s, l = (max+min)/2;
  if (max === min) { h = s = 0; }
  else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) { case r: h = (g-b)/d + (g < b ? 6 : 0); break; case g: h = (b-r)/d + 2; break; default: h = (r-g)/d + 4; }
    h /= 6;
  }
  return { h: Math.round(h*360), s: Math.round(s*100), l: Math.round(l*100) };
}
const hexValido = v => /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v);

// Arrastou um slider → gera a cor e aplica em tudo
function aplicarHsl() {
  const h = +document.getElementById('hslH').value;
  const s = +document.getElementById('hslS').value;
  const l = +document.getElementById('hslL').value;
  const hex = hslToHex(h, s, l);
  document.getElementById('statusCor').value = hex;
  document.getElementById('corTexto').value  = hex;
  atualizarPreviewBadge(hex);
}
// Uma cor veio de fora (preset, hex digitado, seletor) → posiciona os sliders
function syncSlidersFromHex(hex) {
  if (!hexValido(hex)) return;
  const { h, s, l } = hexToHsl(hex);
  const H = document.getElementById('hslH'), S = document.getElementById('hslS'), L = document.getElementById('hslL');
  if (H) { H.value = h; S.value = s; L.value = l; }
}
// Digitou/colou um hex no campo de texto
function corBadgeDigitada(v) {
  document.getElementById('statusCor').value = v;
  atualizarPreviewBadge(v);
  syncSlidersFromHex(v);
}

document.getElementById('statusNome').addEventListener('input', function() {
  atualizarPreviewBadge(document.getElementById('statusCor').value);
  document.getElementById('fscNomePreview').textContent = this.value || 'Sem Conserto';
});

// "Fechar sem cobrança" só faz sentido pra status tipo=Cancelada — some/desmarca nos outros
// tipos, pra não sobrar uma configuração contraditória sem ninguém perceber.
function atualizarVisibilidadeFechaSemCobranca() {
  const ehCancelada = document.getElementById('statusTipo').value === 'cancelada';
  document.getElementById('wrapFechaSemCobranca').style.display = ehCancelada ? '' : 'none';
  if (!ehCancelada) document.getElementById('statusFechaSemCobranca').checked = false;
}
document.getElementById('statusTipo').addEventListener('change', atualizarVisibilidadeFechaSemCobranca);
document.getElementById('statusCor').addEventListener('input', function() {
  document.getElementById('corTexto').value = this.value;
  atualizarPreviewBadge(this.value);
  syncSlidersFromHex(this.value);
});

// Nativos: nome e tipo ficam travados (protegem o esqueleto); cor e os 2 comportamentos são livres.
function travarCamposIdentidade(travar) {
  ['statusNome','statusTipo'].forEach(id => {
    document.getElementById(id).disabled = travar;
  });
  document.getElementById('lockNote').style.display = travar ? '' : 'none';
  document.getElementById('newNote').style.display  = travar ? 'none' : '';
}

// Abrir edição
function abrirEdicao(id, nome, cor, corFonte, tipo, permiteFechar, semValor, fechaSemCobranca, bloqueado) {
  document.getElementById('statusId').value        = id;
  document.getElementById('statusNome').value      = nome;
  document.getElementById('statusCor').value       = cor;
  document.getElementById('corTexto').value        = cor;
  document.getElementById('statusCorFonte').value  = corFonte || '#ffffff';
  document.getElementById('corFonteTexto').value   = corFonte || '#ffffff';
  document.getElementById('statusTipo').value      = tipo;
  document.getElementById('statusPermiteFechar').checked = !!Number(permiteFechar);
  atualizarVisibilidadeFechaSemCobranca();
  document.getElementById('statusFechaSemCobranca').checked = !!Number(fechaSemCobranca);
  document.getElementById('fscNomePreview').textContent = nome || 'Sem Conserto';
  travarCamposIdentidade(!!bloqueado);
  document.getElementById('formTitulo').innerHTML  = bloqueado
    ? '<i class="bi bi-sliders me-1 text-primary"></i> Ajustar: ' + nome
    : '<i class="bi bi-pencil me-1 text-warning"></i> Editando: ' + nome;
  document.getElementById('btnSalvar').innerHTML   = '<i class="bi bi-check-lg"></i> Atualizar';
  document.getElementById('btnCancelar').style.display = '';
  atualizarPreviewBadge(cor);
  syncSlidersFromHex(cor);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limparForm() {
  document.getElementById('statusId').value        = '';
  document.getElementById('statusNome').value      = '';
  document.getElementById('statusCor').value       = '#6c757d';
  document.getElementById('corTexto').value        = '#6c757d';
  document.getElementById('statusCorFonte').value  = '#ffffff';
  document.getElementById('corFonteTexto').value   = '#ffffff';
  document.getElementById('statusTipo').value      = 'aberta';
  document.getElementById('statusPermiteFechar').checked = false;
  atualizarVisibilidadeFechaSemCobranca();
  travarCamposIdentidade(false);
  document.getElementById('formTitulo').innerHTML  = '<i class="bi bi-plus-circle me-1 text-primary"></i> Novo Status';
  document.getElementById('btnSalvar').innerHTML   = '<i class="bi bi-check-lg"></i> Salvar';
  document.getElementById('btnCancelar').style.display = 'none';
  atualizarPreviewBadge('#6c757d');
  syncSlidersFromHex('#6c757d');
}

// Iniciar a criação de um novo status personalizado (limpa, rola até o form e foca)
function novoStatus() {
  limparForm();
  document.getElementById('formStatus').scrollIntoView({ behavior: 'smooth', block: 'start' });
  setTimeout(() => document.getElementById('statusNome').focus(), 350);
}

// Drag and drop para reordenar
const lista = document.getElementById('listaStatus');
if (lista && typeof Sortable !== 'undefined') {
  Sortable.create(lista, {
    handle: '.bi-grip-vertical',
    animation: 150,
    onEnd: async function() {
      const ids = [...lista.querySelectorAll('[data-id]')].map(el => el.dataset.id);
      await fetch('<?= url('/os/status/reordenar') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: JSON.stringify({ ids })
      });
      // Atualiza números de ordem visualmente
      lista.querySelectorAll('[data-id]').forEach((el, i) => {
        const info = el.querySelector('.small.text-muted');
        if (info) info.textContent = info.textContent.replace(/Ordem: \d+/, 'Ordem: ' + (i + 1));
      });
    }
  });
}

// Estado inicial (cobre o caso do navegador restaurar o valor do <select> num F5/voltar)
atualizarVisibilidadeFechaSemCobranca();
</script>
