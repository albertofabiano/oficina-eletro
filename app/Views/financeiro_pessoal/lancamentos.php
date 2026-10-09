<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];

$anoMesPartes = explode('-', $mes);
$mesLabel = $mesesPt[(int) $anoMesPartes[1]] . ' de ' . $anoMesPartes[0];
?>

<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0 0 20px">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
  <div style="display:flex;flex-direction:column;gap:10px;max-width:280px;margin:0 auto">
    <a href="<?= url('/logout') ?>" class="fp-btn fp-btn-primary" style="text-decoration:none;display:inline-block">
      Entrar com outra conta
    </a>
    <a href="<?= url('/assistencias') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none;display:inline-block">
      Ver o Diretório
    </a>
  </div>
</div>

<?php else: ?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Lançamentos</h1>
    <div class="fp-faint">Todas as entradas e saídas de <?= e($mesLabel) ?> — perfil "<?= e($perfil['nome']) ?>"</div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal/lancamentos') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal/lancamentos') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
</div>

<?php if ($resumo): ?>
<!-- Resumo do mês no topo (pedido explícito da spec) — recorte compacto do mesmo cálculo do
     Dashboard (montarResumoMensal()): Gasto/Recebido/Saldo do mês, com o que ainda está em
     aberto somado abaixo do valor pago, mesmo padrão visual do Dashboard (.fp-dash-kpis). -->
<div class="fp-dash-kpis" style="margin-bottom:20px">
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Gasto no mês</div>
    <div class="fp-mono" style="font-weight:700;font-size:1.4rem;color:var(--exp)">R$ <?= number_format($resumo['gastoPagoMes'], 2, ',', '.') ?></div>
    <?php if ($resumo['gastoAbertoMes'] > 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">+ R$ <?= number_format($resumo['gastoAbertoMes'], 2, ',', '.') ?> em aberto</div>
    <?php endif; ?>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Recebido no mês</div>
    <div class="fp-mono" style="font-weight:700;font-size:1.4rem;color:var(--inc)">R$ <?= number_format($resumo['recebidoPagoMes'], 2, ',', '.') ?></div>
    <?php if ($resumo['recebidoAbertoMes'] > 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">+ R$ <?= number_format($resumo['recebidoAbertoMes'], 2, ',', '.') ?> a receber</div>
    <?php endif; ?>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo do mês</div>
    <?php $saldoMesNeg = $resumo['saldoMesAtual'] < 0; ?>
    <div class="fp-mono" style="font-weight:700;font-size:1.4rem;color:<?= $saldoMesNeg ? 'var(--exp)' : 'var(--inc)' ?>">
      <?= $saldoMesNeg ? '−' : '' ?>R$ <?= number_format(abs($resumo['saldoMesAtual']), 2, ',', '.') ?>
    </div>
    <div class="fp-faint" style="font-size:.72rem;margin-top:4px">recebido (pago + a receber) − gasto (pago + em aberto)</div>
  </div>
</div>
<?php endif; ?>

<div class="fp-acoes-rapidas" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
  <button type="button" class="fp-btn fp-btn-scan" id="btnEscanearConta" style="flex:0 0 auto;display:inline-flex;align-items:center;gap:8px">
    <?= fp_icone('qr-code-scan') ?> Escanear conta
  </button>
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovoLancamento" style="flex:0 0 auto">+ Adicionar lançamento</button>
  <button type="button" class="fp-btn fp-btn-recorrente" id="btnRecorrentes" style="flex:0 0 auto;display:inline-flex;align-items:center;gap:8px">
    <?= fp_icone('arrow-counterclockwise') ?> Contas recorrentes
  </button>
  <?php if ($limiteScanner && $limiteScanner['limite'] > 0): ?>
  <!-- Contador visível de leituras do scanner (Etapa 4, pedido explícito) — só aparece quando
       o plano/assinatura tem um teto de verdade (limite=0 é "ilimitado", não mostra nada). -->
  <span class="fp-faint" style="font-size:.76rem" id="fpScannerContador">
    <?= (int) $limiteScanner['usado'] ?>/<?= (int) $limiteScanner['limite'] ?> leituras usadas este mês
  </span>
  <?php endif; ?>
</div>

<!-- Busca + filtros (categoria/status/conta) — tudo client-side, sobre lancamentosAtuais (já
     carregado pro mês navegado), mesmo espírito do filtro Entradas/Saídas que já existia. -->
<div class="fp-card" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:12px 14px">
  <div style="position:relative;flex:1;min-width:180px">
    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"><?= fp_icone('search') ?></span>
    <input type="text" id="fpBusca" class="fp-input" placeholder="Buscar por descrição…" style="padding-left:36px">
  </div>
  <select id="fpFiltroCategoria" class="fp-select" style="max-width:160px"><option value="">Toda categoria</option></select>
  <select id="fpFiltroStatus" class="fp-select" style="max-width:150px">
    <option value="">Todo status</option>
    <option value="pago">Pago</option>
    <option value="vencido">Vencido</option>
    <option value="a_pagar">A pagar</option>
    <option value="a_receber">A receber</option>
  </select>
  <select id="fpFiltroConta" class="fp-select" style="max-width:160px"><option value="">Toda conta</option></select>
</div>

<div class="fp-modal-backdrop" id="modalLancamento">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Lançamento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharLancamento" aria-label="Fechar">×</button>
    </div>
    <form id="fpForm" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div id="fpEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando lançamento</span>
        <a href="#" id="fpCancelarEdicao" style="color:var(--muted);text-decoration:underline">cancelar</a>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="fpTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="fpTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
      </div>
      <input type="hidden" name="tipo" id="fpTipo" value="despesa">

      <input type="text" name="descricao" id="fpDescricao" class="fp-input" placeholder="Descrição (ex.: Supermercado)" maxlength="150" required>

      <!-- Notas extras — visível de cara (não escondida em "Mais detalhes"), mesma coluna
           `observacao` de sempre: um comentário livre sobre o lançamento ("2x no cartão",
           "combinado com o síndico" etc.), sem efeito em cálculo nenhum. -->
      <textarea name="observacao" id="fpObservacao" class="fp-input" placeholder="Notas extras (opcional)" maxlength="500" rows="2" style="resize:vertical"></textarea>

      <div style="display:flex;gap:8px">
        <input type="number" name="valor" id="fpValor" class="fp-input" placeholder="R$ Valor" step="0.01" min="0.01" required style="flex:1">
        <select name="conta_id" id="fpConta" class="fp-select" style="flex:1"></select>
      </div>

      <!-- Categoria em chip clicável — cada um já tem o lápis de editar nome/cor. Só mostra as
           chips do TIPO atual (Gasto/Entrada); trocar o tipo re-renderiza a lista. -->
      <input type="hidden" name="categoria" id="fpCategoria">
      <div>
        <div id="fpCategoriaChips" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        <div class="fp-cat-edit-form" id="fpCategoriaEditForm" style="display:none;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap">
          <input type="text" class="fp-input fp-cat-edit-nome" placeholder="Nome da categoria" maxlength="40" style="flex:1;min-width:140px;padding:8px 12px;font-size:.85rem">
          <input type="color" class="fp-cat-edit-cor" style="width:38px;height:38px;padding:2px;border-radius:8px;border:1.5px solid var(--line);background:var(--input);cursor:pointer">
          <button type="button" class="fp-btn fp-btn-primary fp-btn-sm fp-cat-edit-salvar">Salvar</button>
          <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-cat-edit-cancelar">Cancelar</button>
          <span class="fp-cat-edit-msg fp-faint" style="font-size:.78rem;width:100%"></span>
        </div>
      </div>
      <a href="<?= url('/financeiro-pessoal/categorias') ?>" target="_blank" rel="noopener" class="fp-faint" style="font-size:.78rem;text-decoration:underline;align-self:flex-start">Ver todas as categorias →</a>

      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="fpVencimento" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Vencimento (opcional)</label>
          <input type="date" name="vencimento" id="fpVencimento" class="fp-input">
        </div>
        <div style="flex:1">
          <label for="fpPagoEm" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Pago em (opcional)</label>
          <input type="date" name="pago_em" id="fpPagoEm" class="fp-input">
        </div>
      </div>

      <!-- Anexo/código de barras/Pix removidos do formulário manual a pedido do usuário — o
           campo que de fato captura código de barras/Pix é o scanner de conta por foto
           (#modalRevisaoConta, formulário separado). Lançamento antigo que já tinha esses
           dados salvos (ex.: escaneado antes) continua preservado — ver atualizar() no
           controller, que só sobrescreve um campo se ele vier no POST; como esses 3 campos não
           existem mais aqui, o valor já salvo nunca é apagado ao editar por este modal. -->
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.82rem">
        <input type="checkbox" name="hora_informada" id="fpHoraInformada" value="1" checked style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
        <span>Mostrar o horário deste lançamento na lista</span>
      </label>

      <!-- Só aparece editando um lançamento que veio de uma conta recorrente (l.recorrente_id
           preenchido) — mexe direto no período do MOLDE (financeiro_pessoal_recorrentes), não
           neste lançamento específico (que continua tendo seu próprio vencimento/pago em
           acima). Pedido do usuário: poder ajustar "Início"/"Repetir por" sem precisar sair
           daqui pra achar a recorrência na lista de "Contas recorrentes". -->
      <div id="fpRecorrenteBloco" style="display:none;flex-direction:column;gap:10px;border-top:1px solid var(--border);padding-top:10px">
        <div class="fp-faint" style="font-size:.8rem;display:flex;align-items:center;gap:6px">
          <?= fp_icone('arrow-counterclockwise') ?> Esta conta é recorrente — editar a série:
        </div>
        <div style="display:flex;gap:8px">
          <div style="flex:1">
            <label for="fpRecDataInicio" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Início</label>
            <input type="date" id="fpRecDataInicio" class="fp-input">
          </div>
          <div style="flex:1">
            <label for="fpRecParcelas" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Repetir por</label>
            <select id="fpRecParcelas" class="fp-select">
              <option value="">Sem fim (repete pra sempre)</option>
              <?php for ($n = 1; $n <= 60; $n++): ?>
              <option value="<?= $n ?>"><?= $n ?> <?= $n === 1 ? 'mês' : 'meses' ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <div id="fpMsg" class="fp-muted" style="font-size:.82rem"></div>

      <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Adicionar lançamento</button>
    </form>
  </div>
</div>

<!-- "Marcar como pago" — pede data e valor pago (pode ter saído diferente do planejado, ex.
     juros/desconto). -->
<div class="fp-modal-backdrop" id="modalMarcarPago">
  <div class="fp-modal" style="max-width:360px">
    <div class="fp-modal-header">
      <strong>Marcar como pago</strong>
      <button type="button" class="fp-modal-close" id="btnFecharMarcarPago" aria-label="Fechar">×</button>
    </div>
    <form id="formMarcarPago" style="display:flex;flex-direction:column;gap:10px">
      <div id="fpMarcarPagoDescricao" class="fp-muted" style="font-size:.88rem"></div>
      <div>
        <label for="mpData" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Data do pagamento</label>
        <input type="date" id="mpData" class="fp-input">
      </div>
      <div>
        <label for="mpValor" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Valor pago</label>
        <input type="number" id="mpValor" class="fp-input" step="0.01" min="0.01">
      </div>
      <div id="fpMarcarPagoMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary">Confirmar pagamento</button>
    </form>
  </div>
</div>

<!-- Contas recorrentes (aluguel etc.) — lista + formulário de criar/editar no mesmo modal,
     alternando qual bloco aparece (mesmo padrão de "editando" já usado no modal de
     Lançamento: #fpEditandoAviso). Cada item gerado automaticamente a partir daqui já aparece
     como um lançamento comum na lista/Agenda — este modal só cuida do MOLDE, nunca lista os
     lançamentos já gerados (esses já aparecem em Lançamentos/Agenda normalmente). -->
<div class="fp-modal-backdrop" id="modalRecorrentes">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Contas recorrentes</strong>
      <button type="button" class="fp-modal-close" id="btnFecharRecorrentes" aria-label="Fechar">×</button>
    </div>

    <div id="recList" style="display:flex;flex-direction:column;gap:8px">
      <div id="recListaVazia" class="fp-faint" style="font-size:.85rem;display:none">Nenhuma conta recorrente ainda — cadastre o aluguel, uma assinatura ou qualquer gasto/recebimento que se repete todo mês.</div>
      <div id="recListaItens" style="display:flex;flex-direction:column;gap:8px"></div>
      <button type="button" class="fp-btn fp-btn-primary" id="btnNovaRecorrente" style="margin-top:4px">+ Nova conta recorrente</button>
    </div>

    <form id="recForm" style="display:none;flex-direction:column;gap:10px;margin-top:4px">
      <?= csrf_field() ?>
      <div id="recEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando conta recorrente</span>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="recTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="recTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
      </div>
      <input type="hidden" name="tipo" id="recTipo" value="despesa">

      <input type="text" name="descricao" id="recDescricao" class="fp-input" placeholder="Descrição (ex.: Aluguel)" maxlength="150" required>
      <textarea name="notas" id="recNotas" class="fp-input" placeholder="Notas extras (opcional)" maxlength="500" rows="2" style="resize:vertical"></textarea>

      <div style="display:flex;gap:8px">
        <input type="number" name="valor" id="recValor" class="fp-input" placeholder="R$ Valor" step="0.01" min="0.01" required style="flex:1">
        <select name="conta_id" id="recConta" class="fp-select" style="flex:1"></select>
      </div>

      <input type="hidden" name="categoria" id="recCategoria">
      <div id="recCategoriaChips" style="display:flex;gap:6px;flex-wrap:wrap"></div>

      <div>
        <label for="recDiaVencimento" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Todo dia (1 a 31)</label>
        <input type="number" name="dia_vencimento" id="recDiaVencimento" class="fp-input" min="1" max="31" step="1" placeholder="Ex.: 5" required style="max-width:120px">
        <div class="fp-faint" style="font-size:.76rem;margin-top:4px">Gera o lançamento sozinho todo mês nesse dia (mês com menos dias cai no último dia dele) e avisa pelo sino no dia do vencimento.</div>
      </div>

      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="recDataInicio" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Início</label>
          <input type="date" name="data_inicio" id="recDataInicio" class="fp-input" required>
        </div>
        <div style="flex:1">
          <label for="recParcelas" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Repetir por</label>
          <select name="parcelas" id="recParcelas" class="fp-select">
            <option value="">Sem fim (repete pra sempre)</option>
            <?php for ($n = 1; $n <= 60; $n++): ?>
            <option value="<?= $n ?>"><?= $n ?> <?= $n === 1 ? 'mês' : 'meses' ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="fp-faint" style="font-size:.76rem;margin-top:-4px">"Repetir por" serve pra compra parcelada no cartão (ex.: 12 meses) — "Sem fim" é pra conta fixa tipo aluguel.</div>

      <div id="recMsg" class="fp-muted" style="font-size:.82rem"></div>

      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-ghost" id="recCancelarForm" style="flex:1">Voltar pra lista</button>
        <button type="submit" class="fp-btn fp-btn-primary" id="recBtnSalvar" style="flex:1">Salvar</button>
      </div>
    </form>
  </div>
</div>

<section class="fp-lanc-col" aria-labelledby="fpLancTitulo">
  <div style="display:flex;gap:10px;align-items:flex-start">
    <div class="fp-filtros">
      <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><?= fp_icone('arrow-down-circle-fill') ?><span>Entradas</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><?= fp_icone('arrow-up-circle-fill') ?><span>Saídas</span></button>
    </div>
    <div style="flex:1;min-width:0">
      <h2 id="fpLancTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px">Lançamentos</h2>
      <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
    </div>
  </div>
</section>

<input id="scanInputDireto" type="file" accept="image/*" capture="environment" style="display:none">

<div class="fp-modal-backdrop" id="modalScanQr">
  <div class="fp-modal" style="max-width:360px;text-align:center">
    <div class="fp-modal-header">
      <strong>📷 Escanear conta</strong>
      <button type="button" class="fp-modal-close" id="btnFecharScanQr" aria-label="Fechar">×</button>
    </div>
    <p class="fp-muted" style="font-size:.85rem;margin:0 0 10px">Abra a câmera do celular (logado na mesma conta) e escaneie:</p>
    <div id="scanQrBox" style="display:flex;justify-content:center;align-items:center;min-height:186px;background:var(--surf2);border-radius:12px"></div>
    <p class="fp-faint" style="font-size:.78rem;margin:10px 0 2px">ou acesse <strong><?= e(parse_url(url('/'), PHP_URL_HOST) ?: 'o site') ?>/scan</strong> e digite:</p>
    <div id="scanCodigo" class="fp-mono" style="font-weight:800;font-size:1.3rem;letter-spacing:.2em">••••••</div>
    <div id="scanStatus" class="fp-faint" style="margin-top:10px;font-size:.84rem">Aguardando o celular…</div>
  </div>
</div>

<div class="fp-modal-backdrop" id="modalRevisaoConta">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Revisar antes de inserir</strong>
      <button type="button" class="fp-modal-close" id="btnFecharRevisao" aria-label="Fechar">×</button>
    </div>
    <img id="revisaoFotoImg" src="" alt="Foto da conta escaneada" style="width:100%;max-height:200px;object-fit:contain;border-radius:12px;background:var(--surf2);margin-bottom:8px">
    <div id="revisaoLendoAviso" class="fp-faint" style="display:none;font-size:.8rem;margin-bottom:10px">🔎 Lendo a conta automaticamente…</div>
    <form id="formRevisaoConta" style="display:flex;flex-direction:column;gap:10px">
      <input type="text" id="revisaoDescricao" class="fp-input" placeholder="Descrição (ex.: Conta de luz)" maxlength="150" required>
      <div>
        <div class="fp-row-valor-cat">
          <input type="number" id="revisaoValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
          <select id="revisaoCategoria" class="fp-select" style="flex:1">
            <?php foreach ($categorias as $chave => $c): if ($c['tipo'] !== 'receita'): ?>
            <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
            <?php endif; endforeach; ?>
          </select>
        </div>
        <div id="revisaoConfiancaValor" class="fp-faint" style="display:none;font-size:.74rem;margin-top:4px"></div>
        <div id="revisaoAprendidoBadge" style="display:none;font-size:.74rem;margin-top:4px;color:var(--inc)">✓ Categoria aprendida — você já corrigiu isso antes</div>
      </div>
      <div>
        <input type="date" id="revisaoDataPagamento" class="fp-input" placeholder="Data do pagamento">
      </div>

      <div id="revisaoMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary" id="btnRevisaoSalvar">Inserir no sistema</button>
    </form>
  </div>
</div>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var CONTAS = <?= json_encode($contas, JSON_UNESCAPED_UNICODE) ?>;
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  var HOJE_STR = <?= json_encode(date('Y-m-d')) ?>;
  var FP_SVG = {
    'chevron-down': <?= json_encode(fp_icone('chevron-down')) ?>,
    'pencil-fill': <?= json_encode(fp_icone('pencil-fill')) ?>,
    'trash3': <?= json_encode(fp_icone('trash3')) ?>
  };
  var STATUS_ROTULO = { pago: 'Pago', vencido: 'Vencido', a_pagar: 'A pagar', a_receber: 'A receber' };
  var STATUS_CHIP_CLASSE = { pago: 'fp-chip-inc', vencido: 'fp-chip-danger', a_pagar: 'fp-chip-warn', a_receber: 'fp-chip-warn' };

  // Mesma fórmula de fixa_status_lancamento() (app/Helpers/functions.php) — espelhada aqui
  // pra não precisar de round-trip ao servidor só pra saber o status de cada lançamento.
  function statusLancamento(l) {
    if (l.pago_em) return 'pago';
    if (l.vencimento && l.vencimento < HOJE_STR) return 'vencido';
    return l.tipo === 'receita' ? 'a_receber' : 'a_pagar';
  }

  var lista = document.getElementById('fpLista');
  var modalLancamento = document.getElementById('modalLancamento');
  var form = document.getElementById('fpForm');
  var msg = document.getElementById('fpMsg');
  var btnSalvar = document.getElementById('fpBtnSalvar');
  var tipoHidden = document.getElementById('fpTipo');
  var btnDespesa = document.getElementById('fpTipoDespesa');
  var btnReceita = document.getElementById('fpTipoReceita');
  var csrfToken = '<?= csrf_token() ?>';
  var lancamentosAtuais = [];
  var filtroAtivo = 'todos';
  var editandoId = null;
  // Id do MOLDE (financeiro_pessoal_recorrentes), não do lançamento — preenchido quando o
  // lançamento em edição veio de uma conta recorrente, pro bloco "Esta conta é recorrente"
  // (#fpRecorrenteBloco) saber em qual recorrência salvar Início/Repetir por.
  var recorrenteEditandoId = null;
  var lancColapsados = {};
  var editandoAviso = document.getElementById('fpEditandoAviso');
  var TEXTO_SALVAR_NOVO = 'Adicionar lançamento';
  var TEXTO_SALVAR_EDICAO = 'Salvar alterações';

  // ── Selects de Conta (modal + filtro) ────────────────────────────────────────────────────
  function preencherSelectContas(select, comOpcaoTodas) {
    var html = comOpcaoTodas ? '<option value="">Toda conta</option>' : '';
    html += CONTAS.map(function (c) { return '<option value="' + c.id + '">' + escapeHtml(c.nome) + '</option>'; }).join('');
    select.innerHTML = html;
  }
  preencherSelectContas(document.getElementById('fpConta'), false);
  preencherSelectContas(document.getElementById('fpFiltroConta'), true);

  // ── Select de Categoria (filtro — todas as categorias, independente do tipo) ────────────
  (function () {
    var sel = document.getElementById('fpFiltroCategoria');
    sel.innerHTML = '<option value="">Toda categoria</option>' +
      Object.keys(CATS).map(function (k) { return '<option value="' + k + '">' + escapeHtml(CATS[k].nome) + '</option>'; }).join('');
  })();

  document.querySelectorAll('.fp-filtro-btn').forEach(function (btn) {
    btn.onclick = function () {
      filtroAtivo = filtroAtivo === btn.dataset.filtro ? 'todos' : btn.dataset.filtro;
      document.querySelectorAll('.fp-filtro-btn').forEach(function (b) {
        b.classList.toggle('active', b.dataset.filtro === filtroAtivo);
      });
      aplicarFiltroEExibir();
    };
  });
  document.getElementById('fpBusca').addEventListener('input', aplicarFiltroEExibir);
  document.getElementById('fpFiltroCategoria').addEventListener('change', aplicarFiltroEExibir);
  document.getElementById('fpFiltroStatus').addEventListener('change', aplicarFiltroEExibir);
  document.getElementById('fpFiltroConta').addEventListener('change', aplicarFiltroEExibir);

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa-ativo' : 'fp-btn-ghost');
    btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita-ativo' : 'fp-btn-ghost');
    btnSalvar.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-receita');
    renderCategoriaChipsModal(document.getElementById('fpCategoria').value, tipo);
  }
  btnDespesa.onclick = function () { marcarTipo('despesa'); };
  btnReceita.onclick = function () { marcarTipo('receita'); };

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function fmtData(dt, comHora) {
    var d = new Date(dt.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dt;
    var base = d.toLocaleDateString('pt-BR');
    return comHora ? base + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : base;
  }
  function fmtDataCurta(iso) {
    if (!iso) return '';
    var p = iso.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] : iso;
  }
  // DD/MM/AAAA a partir de "YYYY-MM-DD" via split (não via Date, que interpretaria a data como
  // meia-noite UTC e voltaria um dia em qualquer fuso negativo tipo America/Sao_Paulo).
  function fmtDataLonga(iso) {
    if (!iso) return '';
    var p = iso.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  // Quantos meses de "YYYY-MM-DD" início até "YYYY-MM-DD" fim, inclusive os dois — espelha o
  // cálculo do servidor (dadosRecorrenteDoPost(): data_fim = início + N-1 meses), só que ao
  // contrário, pra popular o <select> "Repetir por" ao editar uma recorrência já salva.
  function mesesEntre(inicioIso, fimIso) {
    var pi = inicioIso.split('-'), pf = fimIso.split('-');
    return (Number(pf[0]) - Number(pi[0])) * 12 + (Number(pf[1]) - Number(pi[1])) + 1;
  }
  function fmtDiaCabecalho(iso) {
    if (iso === HOJE_STR) return 'Hoje';
    var ontem = new Date(HOJE_STR + 'T00:00:00'); ontem.setDate(ontem.getDate() - 1);
    if (iso === ontem.toISOString().slice(0, 10)) return 'Ontem';
    var d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' }).replace(/^\w/, function (c) { return c.toUpperCase(); });
  }
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }
  function humanizarCategoria(chave) {
    return String(chave || '').replace(/[_-]/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
  }

  function mesAtualStr() { return MES_SELECIONADO; }

  var MSG_VAZIO = {
    todos: 'Nenhum lançamento em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    receita: 'Nenhuma entrada em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    despesa: 'Nenhuma saída em ' + '<?= e($mesLabel) ?>' + ' ainda.'
  };

  function aplicarFiltroEExibir() {
    var mesAtual = mesAtualStr();
    var busca = document.getElementById('fpBusca').value.trim().toLowerCase();
    var fCategoria = document.getElementById('fpFiltroCategoria').value;
    var fStatus = document.getElementById('fpFiltroStatus').value;
    var fConta = document.getElementById('fpFiltroConta').value;

    var filtrados = lancamentosAtuais.filter(function (l) {
      if (l.data_hora.slice(0, 7) !== mesAtual) return false;
      if (filtroAtivo !== 'todos' && l.tipo !== filtroAtivo) return false;
      if (busca && l.descricao.toLowerCase().indexOf(busca) === -1) return false;
      if (fCategoria && l.categoria !== fCategoria) return false;
      if (fStatus && statusLancamento(l) !== fStatus) return false;
      if (fConta && String(l.conta_id) !== fConta) return false;
      return true;
    });
    renderLista(filtrados);
  }

  function renderLista(lancamentos) {
    lista.innerHTML = '';
    if (!lancamentos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">' + MSG_VAZIO[filtroAtivo] + '</div>';
      return;
    }

    // Agrupa por dia (pedido explícito) — lançamentos já vêm ordenados por data_hora DESC do
    // servidor/carregar(), então basta detectar troca de dia e inserir um cabeçalho.
    var diaAnterior = null;

    lancamentos.forEach(function (l) {
      var diaChave = l.data_hora.slice(0, 10);
      if (diaChave !== diaAnterior) {
        var cab = document.createElement('div');
        cab.className = 'fp-faint fp-mono';
        cab.style.cssText = 'font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;margin:' + (diaAnterior === null ? '0' : '10px') + ' 0 2px';
        cab.textContent = fmtDiaCabecalho(diaChave);
        lista.appendChild(cab);
        diaAnterior = diaChave;
      }

      var cat = CATS[l.categoria] || { nome: humanizarCategoria(l.categoria), cor: 'var(--muted)' };
      var tipoCor = l.tipo === 'receita' ? 'var(--inc)' : 'var(--exp)';
      var aberto = !!lancColapsados[l.id];
      var status = statusLancamento(l);
      var conta = CONTAS.filter(function (c) { return String(c.id) === String(l.conta_id); })[0];

      var card = document.createElement('div');
      card.className = 'fp-lanc-card';
      card.style.borderLeft = '3px solid ' + tipoCor;

      var header = document.createElement('div');
      header.className = 'fp-lanc-header';
      header.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) +
            (l.anexo_url ? ' ' + FP_SVG_PAPERCLIP : '') +
          '</div>' +
          '<div class="fp-faint fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' +
            escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora, !!l.hora_informada) + (l.origem === 'foto' ? ' · 📷' : '') +
            (conta ? ' · ' + escapeHtml(conta.nome) : '') +
          '</div>' +
          '<div style="margin-top:4px;display:flex;gap:6px;flex-wrap:wrap">' +
            '<span class="fp-chip ' + STATUS_CHIP_CLASSE[status] + '">' + STATUS_ROTULO[status] +
              (status === 'pago' ? ' em ' + fmtDataCurta(l.pago_em) : (l.vencimento ? ' ' + fmtDataCurta(l.vencimento) : '')) +
            '</span>' +
          '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + tipoCor + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<span class="fp-lanc-chevron" aria-hidden="true" style="transform:rotate(' + (aberto ? '180' : '0') + 'deg)">' + FP_SVG['chevron-down'] + '</span>';
      header.onclick = function () {
        lancColapsados[l.id] = !aberto;
        renderLista(lancamentos);
      };
      card.appendChild(header);

      // Trocar categoria passou a ser só via "Editar" (abre o modal, que já tem o mesmo
      // seletor de chips) — a fileira de chips clicáveis aqui dentro do item expandido virou
      // redundante com isso e deixava o card cheio num perfil com muita categoria (pedido do
      // usuário, vendo a tela: "facilita o entendimento").
      var corpo = document.createElement('div');
      corpo.className = 'fp-lanc-corpo' + (aberto ? ' show' : '');
      var obsHtml = l.observacao ? '<div class="fp-faint" style="font-size:.8rem;margin-top:10px">' + escapeHtml(l.observacao) + '</div>' : '';
      var anexoHtml = l.anexo_url ? '<div style="margin-top:8px"><a href="' + l.anexo_url + '" target="_blank" rel="noopener" class="fp-faint" style="font-size:.78rem;text-decoration:underline">Ver anexo →</a></div>' : '';
      corpo.innerHTML =
        '<div style="padding-top:10px;border-top:1px solid var(--line)">' +
          '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">' +
            (status !== 'pago' ? '<button type="button" class="fp-btn fp-btn-primary fp-btn-sm fp-marcar-pago" data-id="' + l.id + '">Marcar como pago</button>' : '') +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-edit" data-id="' + l.id + '">Editar</button>' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-del" data-id="' + l.id + '" style="color:var(--exp)">Excluir</button>' +
          '</div>' +
          obsHtml + anexoHtml +
        '</div>';
      card.appendChild(corpo);

      lista.appendChild(card);
    });

    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluirComDesfazer(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-edit').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-marcar-pago').forEach(function (btn) {
      btn.onclick = function () { abrirMarcarPago(btn.dataset.id); };
    });
  }

  var FP_SVG_PAPERCLIP = <?= json_encode(fp_icone('paperclip')) ?>;

  // Usada só pelo mini-editor de nome/cor de categoria DENTRO DO MODAL (ver
  // renderCategoriaChipsModal()/abrirEdicaoCategoriaModal() abaixo) — trocar a categoria de um
  // lançamento específico agora é sempre via "Editar" (abre o modal).
  function salvarEdicaoCategoriaInline(formEl, aoSalvar) {
    var chave = formEl.dataset.chave;
    var info = CATS[chave];
    var msgEl = formEl.querySelector('.fp-cat-edit-msg');
    if (!info || !info.id) {
      msgEl.textContent = 'Categoria não encontrada.';
      msgEl.style.color = 'var(--exp)';
      return;
    }
    var nome = formEl.querySelector('.fp-cat-edit-nome').value.trim();
    var cor = formEl.querySelector('.fp-cat-edit-cor').value;
    if (!nome) {
      msgEl.textContent = 'Dê um nome pra categoria.';
      msgEl.style.color = 'var(--exp)';
      return;
    }
    msgEl.textContent = '';
    fetch('<?= url('/api/financeiro-pessoal/categorias') ?>/' + info.id + '/editar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ nome: nome, cor: cor })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) {
          msgEl.textContent = j.erro || 'Não deu pra salvar.';
          msgEl.style.color = 'var(--exp)';
          return;
        }
        CATS[chave].nome = j.nome;
        CATS[chave].cor = j.cor;
        aplicarFiltroEExibir();
        if (aoSalvar) aoSalvar();
      });
  }

  // Só mostra os chips do TIPO atual do modal (Gasto -> despesa, Entrada -> receita).
  function renderCategoriaChipsModal(selecionada, tipo) {
    var wrap = document.getElementById('fpCategoriaChips');
    var chaves = Object.keys(CATS).filter(function (k) { return CATS[k].tipo === tipo; });
    wrap.innerHTML = chaves.map(function (k) {
      var ativo = k === selecionada;
      return '<span class="fp-cat-chip' + (ativo ? ' active' : '') + '" data-cat="' + k + '" role="button" tabindex="0">' +
        '<span class="fp-cat-chip-dot" style="background:' + CATS[k].cor + '"></span>' + escapeHtml(CATS[k].nome) +
        '<button type="button" class="fp-cat-chip-edit" data-cat="' + k + '" title="Editar categoria" aria-label="Editar categoria ' + escapeHtml(CATS[k].nome) + '">✎</button>' +
      '</span>';
    }).join('');
    // Nenhum chip ativo ainda (ex.: trocou de tipo) — marca o primeiro da lista nova, pra
    // sempre sobrar uma categoria válida selecionada.
    if (chaves.length && !chaves.includes(selecionada)) {
      document.getElementById('fpCategoria').value = chaves[0];
      wrap.querySelector('.fp-cat-chip').classList.add('active');
    } else {
      document.getElementById('fpCategoria').value = selecionada;
    }
    wrap.querySelectorAll('.fp-cat-chip').forEach(function (chip) {
      chip.onclick = function () {
        document.getElementById('fpCategoria').value = chip.dataset.cat;
        wrap.querySelectorAll('.fp-cat-chip').forEach(function (c) { c.classList.toggle('active', c === chip); });
      };
      chip.onkeydown = function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); chip.click(); }
      };
    });
    wrap.querySelectorAll('.fp-cat-chip-edit').forEach(function (btn) {
      btn.onclick = function (ev) {
        ev.stopPropagation();
        abrirEdicaoCategoriaModal(btn.dataset.cat);
      };
    });
  }

  function abrirEdicaoCategoriaModal(chave) {
    var formEl = document.getElementById('fpCategoriaEditForm');
    var info = CATS[chave];
    if (!info) return;
    formEl.dataset.chave = chave;
    formEl.querySelector('.fp-cat-edit-nome').value = info.nome;
    formEl.querySelector('.fp-cat-edit-cor').value = /^#[0-9a-fA-F]{6}$/.test(info.cor) ? info.cor : '#7A6A88';
    formEl.querySelector('.fp-cat-edit-msg').textContent = '';
    formEl.style.display = 'flex';
    formEl.querySelector('.fp-cat-edit-nome').focus();
  }

  (function () {
    var formEl = document.getElementById('fpCategoriaEditForm');
    formEl.querySelector('.fp-cat-edit-cancelar').onclick = function () { formEl.style.display = 'none'; };
    formEl.querySelector('.fp-cat-edit-salvar').onclick = function () {
      salvarEdicaoCategoriaInline(formEl, function () {
        var selecionadaAtual = document.getElementById('fpCategoria').value;
        renderCategoriaChipsModal(selecionadaAtual, tipoHidden.value);
        formEl.style.display = 'none';
      });
    };
  })();

  var fpRecorrenteBloco = document.getElementById('fpRecorrenteBloco');
  var fpRecDataInicio = document.getElementById('fpRecDataInicio');
  var fpRecParcelas = document.getElementById('fpRecParcelas');

  // Busca o molde (financeiro_pessoal_recorrentes) que gerou este lançamento e popula
  // Início/Repetir por — mesma conta recorrente, reaproveita o endpoint que "Contas
  // recorrentes" já usa (lista inteira; não existe endpoint de buscar 1 só, e a lista de
  // recorrências de um perfil é sempre pequena o bastante pra não valer a pena criar um).
  function popularBlocoRecorrente(recorrenteId) {
    if (!recorrenteId) {
      recorrenteEditandoId = null;
      fpRecorrenteBloco.style.display = 'none';
      return;
    }
    fetch('<?= url('/api/financeiro-pessoal/recorrentes') ?>')
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        var rec = j.recorrentes.filter(function (x) { return String(x.id) === String(recorrenteId); })[0];
        if (!rec) return; // molde pode ter sido excluído — lançamento continua, só sem o atalho
        recorrenteEditandoId = rec.id;
        var dataInicioRec = rec.data_inicio || HOJE_STR;
        fpRecDataInicio.value = dataInicioRec;
        var parcelasRec = rec.data_fim ? Math.max(1, Math.min(60, mesesEntre(dataInicioRec, rec.data_fim))) : '';
        fpRecParcelas.value = String(parcelasRec);
        fpRecorrenteBloco.style.display = 'flex';
      });
  }

  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    document.getElementById('fpConta').value = l.conta_id || '';
    document.getElementById('fpVencimento').value = l.vencimento || '';
    document.getElementById('fpPagoEm').value = l.pago_em || '';
    document.getElementById('fpObservacao').value = l.observacao || '';
    document.getElementById('fpHoraInformada').checked = !!l.hora_informada;
    var categoriaFinal = CATS[l.categoria] ? l.categoria : (Object.keys(CATS)[0] || '');
    renderCategoriaChipsModal(categoriaFinal, l.tipo);
    editandoAviso.style.display = 'flex';
    btnSalvar.textContent = TEXTO_SALVAR_EDICAO;
    msg.textContent = '';
    popularBlocoRecorrente(l.recorrente_id);
    abrirModal(modalLancamento);
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    document.getElementById('fpCategoriaEditForm').style.display = 'none';
    editandoAviso.style.display = 'none';
    msg.textContent = '';
    popularBlocoRecorrente(null);
    btnSalvar.textContent = TEXTO_SALVAR_NOVO;
    fecharModal(modalLancamento);
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
  };

  document.getElementById('btnNovoLancamento').onclick = function () {
    cancelarEdicao();
    abrirModal(modalLancamento);
  };
  document.getElementById('btnFecharLancamento').onclick = function () {
    cancelarEdicao();
  };

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>?mes=' + encodeURIComponent(MES_SELECIONADO))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        lancamentosAtuais = j.lancamentos;
        aplicarFiltroEExibir();
      });
  }

  // ── Excluir com "Desfazer" por 10s (toast) — remove da tela na hora (otimista), só chama o
  // servidor de verdade depois de 10s sem cancelar. Reaproveita o mesmo container de toast do
  // sino de notificação (layouts/financeiro_pessoal.php), que já existe na página. ───────────
  function excluirComDesfazer(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    lancamentosAtuais = lancamentosAtuais.filter(function (x) { return String(x.id) !== String(id); });
    aplicarFiltroEExibir();

    var wrap = document.getElementById('fpNotifToastWrap');
    var toast = document.createElement('div');
    toast.className = 'fp-notif-toast';
    toast.innerHTML = '<span>Lançamento excluído.</span> <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" style="margin-left:8px">Desfazer</button>';
    wrap.appendChild(toast);

    var cancelado = false;
    var timer = setTimeout(function () {
      if (cancelado) return;
      toast.remove();
      fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/excluir', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken }
      });
    }, 10000);

    toast.querySelector('button').onclick = function () {
      cancelado = true;
      clearTimeout(timer);
      toast.remove();
      lancamentosAtuais.push(l);
      aplicarFiltroEExibir();
    };
  }

  // ── Marcar como pago (modal com data + valor) ───────────────────────────────────────────
  var modalMarcarPago = document.getElementById('modalMarcarPago');
  var formMarcarPago = document.getElementById('formMarcarPago');
  var mpMsg = document.getElementById('fpMarcarPagoMsg');
  var mpLancId = null;

  function abrirMarcarPago(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    mpLancId = id;
    document.getElementById('fpMarcarPagoDescricao').textContent = l.descricao + ' — ' + fmtValor(l.valor);
    document.getElementById('mpData').value = HOJE_STR;
    document.getElementById('mpValor').value = l.valor;
    mpMsg.textContent = '';
    abrirModal(modalMarcarPago);
  }
  document.getElementById('btnFecharMarcarPago').onclick = function () { fecharModal(modalMarcarPago); };

  formMarcarPago.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var pagoEm = document.getElementById('mpData').value;
    var valor = document.getElementById('mpValor').value;
    if (!valor || parseFloat(valor) <= 0) {
      mpMsg.innerHTML = '<span style="color:var(--exp)">Informe um valor válido.</span>';
      return;
    }
    fetch('<?= url('/financeiro-pessoal') ?>/' + mpLancId + '/marcar-pago', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ pago_em: pagoEm, valor: valor })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { mpMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra confirmar agora.') + '</span>'; return; }
        // "Marcar como pago" também insere valor/data e mexe nos cards de KPI do topo (move o
        // valor de "a pagar"/"a receber" pra "pago") — mesmo motivo do reload no salvar/editar
        // acima, não só carregar() (que só atualiza a lista abaixo).
        window.location.reload();
      })
      .catch(function () { mpMsg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>'; });
  });

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('fpDescricao').value.trim();
    var valor = document.getElementById('fpValor').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      msg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }
    if (recorrenteEditandoId !== null && !fpRecDataInicio.value) {
      msg.innerHTML = '<span style="color:var(--exp)">Informe a data de início da recorrência.</span>';
      return;
    }
    var emEdicao = editandoId !== null;
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    var saveUrl = emEdicao
      ? '<?= url('/financeiro-pessoal') ?>/' + editandoId + '/atualizar'
      : '<?= url('/financeiro-pessoal') ?>';

    fetch(saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        tipo: tipoHidden.value,
        categoria: document.getElementById('fpCategoria').value,
        descricao: descricao,
        valor: valor,
        conta_id: document.getElementById('fpConta').value,
        vencimento: document.getElementById('fpVencimento').value,
        pago_em: document.getElementById('fpPagoEm').value,
        observacao: document.getElementById('fpObservacao').value,
        hora_informada: document.getElementById('fpHoraInformada').checked ? '1' : '0'
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        if (!j.ok) {
          btnSalvar.textContent = orig;
          msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        // Reload em vez de carregar() por AJAX — carregar() só atualiza a lista abaixo
        // (lancamentosAtuais), nunca os cards de KPI do topo (Gasto/Recebido/Saldo do mês),
        // que são montados no PHP do carregamento inicial da página; sem isso, um lançamento
        // novo aparecia na lista mas os cards ficavam com o valor antigo até um F5 manual.
        //
        // Se o bloco "Esta conta é recorrente" está visível, salva o período do MOLDE antes de
        // recarregar (requisição separada, endpoint próprio — ver recorrenteAtualizarPeriodo())
        // — precisa terminar ANTES do reload, senão o fetch é abortado pela navegação.
        if (recorrenteEditandoId !== null) {
          fetch('<?= url('/financeiro-pessoal/recorrentes') ?>/' + recorrenteEditandoId + '/periodo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
            body: new URLSearchParams({ data_inicio: fpRecDataInicio.value, parcelas: fpRecParcelas.value })
          }).catch(function () {}).then(function () { window.location.reload(); });
          return;
        }
        window.location.reload();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');
  lancamentosAtuais = <?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>;
  aplicarFiltroEExibir();

  // ────────────────────────────────────────────────────────────────────
  // Escanear conta — câmera pelo celular. No PC, parea por QR (mesmo
  // mecanismo genérico de ScannerController, modo 'financeiro_conta');
  // em celular/tablet, abre a câmera direto (mesmo aparelho que já está
  // com a tela aberta).
  // ────────────────────────────────────────────────────────────────────
  var scanInputDireto = document.getElementById('scanInputDireto');
  var modalScanQr = document.getElementById('modalScanQr');
  var modalRevisaoConta = document.getElementById('modalRevisaoConta');
  var scanToken = null;
  var scanTimer = null;

  function temCameraPropria() {
    return ('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 991;
  }

  function comprimirImagem(file) {
    var suportaWebp = (function () {
      var c = document.createElement('canvas'); c.width = c.height = 1;
      return c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    })();
    return new Promise(function (resolve, reject) {
      var reader = new FileReader();
      reader.onerror = function () { reject(new Error('Não deu pra ler o arquivo escolhido.')); };
      reader.onload = function (e) {
        var img = new Image();
        img.onerror = function () { reject(new Error('Formato de imagem não suportado.')); };
        img.onload = function () {
          var max = 1280, w = img.width, h = img.height;
          if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
          else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
          var c = document.createElement('canvas');
          c.width = w; c.height = h;
          var ctx = c.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
          ctx.drawImage(img, 0, 0, w, h);
          resolve(suportaWebp ? c.toDataURL('image/webp', 0.78) : c.toDataURL('image/jpeg', 0.7));
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  function abrirScan() {
    if (temCameraPropria()) { scanInputDireto.click(); return; }
    abrirModalQr();
  }
  document.getElementById('btnEscanearConta').onclick = abrirScan;

  // Pré-checagem local (Etapa 4, controle de custo) — tenta ler um código de barras/QR Pix
  // DIRETO da foto, no próprio navegador, via BarcodeDetector nativo (Chrome/Edge/Android;
  // sem lib nenhuma, sem CDN). Sem suporte (Safari/Firefox) resolve null — segue reto pro
  // fluxo de IA de sempre, sem nenhum efeito colateral. Achando um código, ANTES de chamar a
  // API verifica se já existe lançamento com esse código: achando, nunca chega a chamar
  // /ocr-conta — é exatamente o "não chamar a API" do pedido, não só um aviso depois do gasto.
  function detectarCodigoLocal(file) {
    if (!('BarcodeDetector' in window) || typeof createImageBitmap !== 'function') return Promise.resolve(null);
    var detector;
    try {
      detector = new BarcodeDetector({ formats: ['code_128', 'itf', 'ean_13', 'qr_code', 'pdf417'] });
    } catch (e) { return Promise.resolve(null); }
    return createImageBitmap(file)
      .then(function (bitmap) { return detector.detect(bitmap); })
      .then(function (codes) {
        if (!codes || !codes.length) return null;
        var raw = (codes[0].rawValue || '').trim();
        // Pix "copia e cola" é um payload EMV e sempre começa com "000201" (Payload Format
        // Indicator) — jeito confiável de diferenciar de um código de barras de boleto (só
        // dígitos) sem decodificar o EMV inteiro.
        if (raw.indexOf('000201') === 0) return { pix_copia_cola: raw };
        if (/^\d{8,60}$/.test(raw)) return { codigo_barras: raw };
        return null;
      })
      .catch(function () { return null; });
  }

  function avisoContaJaLancada(l) {
    var dataFmt = l.vencimento ? l.vencimento.split('-').reverse().join('/') : '';
    return '📋 Essa conta já foi lançada' + (dataFmt ? ' (venc. ' + dataFmt + ')' : '') + ' — ' +
      fmtValor(l.valor) + ', ' + (l.pago ? 'já paga' : 'ainda não paga') +
      '. Não chamamos a leitura automática de novo pra não gastar à toa — confira na lista, ou preencha abaixo se for mesmo uma conta diferente.';
  }

  function seguirParaIA(dataUrl, codigoDetectado) {
    abrirRevisao(dataUrl, null, true);
    fetch('<?= url('/financeiro-pessoal/ocr-conta') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: 'foto=' + encodeURIComponent(dataUrl)
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        // O código lido localmente (mais confiável que a IA "lendo" um código numa foto) some
        // junto com o resto se a leitura cair no limite mensal/falhar — mesmo assim ainda vale
        // anexar ao objeto vazio, pra ficar gravado quando o usuário preencher na mão.
        var extraido = (j.ok && j.extraido) ? j.extraido : (codigoDetectado ? {} : null);
        if (extraido && codigoDetectado) {
          if (codigoDetectado.codigo_barras) extraido.codigo_barras = codigoDetectado.codigo_barras;
          if (codigoDetectado.pix_copia_cola) extraido.pix_copia_cola = codigoDetectado.pix_copia_cola;
        }
        aplicarExtraido(extraido, j.limite_atingido ? j.erro : null, !!j.aprendido);
      })
      .catch(function () { aplicarExtraido(codigoDetectado ? codigoDetectado : null); });
  }

  scanInputDireto.addEventListener('change', function () {
    if (!scanInputDireto.files.length) return;
    var arquivo = scanInputDireto.files[0];
    detectarCodigoLocal(arquivo)
      .then(function (codigo) {
        if (!codigo) {
          comprimirImagem(arquivo).then(function (dataUrl) { seguirParaIA(dataUrl, null); }).catch(falhaAbrirFoto);
          return;
        }
        fetch('<?= url('/financeiro-pessoal/verificar-codigo') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
          body: new URLSearchParams(codigo)
        })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            comprimirImagem(arquivo).then(function (dataUrl) {
              if (j && j.ok && j.duplicado) {
                abrirRevisao(dataUrl, { codigo_barras: codigo.codigo_barras, pix_copia_cola: codigo.pix_copia_cola }, false, avisoContaJaLancada(j.lancamento));
                return;
              }
              seguirParaIA(dataUrl, codigo);
            }).catch(falhaAbrirFoto);
          })
          .catch(function () {
            comprimirImagem(arquivo).then(function (dataUrl) { seguirParaIA(dataUrl, codigo); }).catch(falhaAbrirFoto);
          });
      });
    scanInputDireto.value = '';
  });

  function falhaAbrirFoto() {
    alert('Não conseguimos abrir essa foto (formato não suportado pelo navegador). Tente tirar uma foto nova pela câmera, ou escolher outra imagem (JPG/PNG) da galeria.');
  }

  function abrirModalQr() {
    document.getElementById('scanQrBox').innerHTML = '';
    document.getElementById('scanCodigo').textContent = '••••••';
    document.getElementById('scanStatus').textContent = 'Gerando QR…';
    abrirModal(modalScanQr);

    fetch('<?= url('/scanner/nova') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: 'modo=financeiro_conta'
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        scanToken = j.token;
        document.getElementById('scanQrBox').innerHTML = '<img src="' + j.qr + '" alt="QR Code" style="width:186px;height:186px">';
        document.getElementById('scanCodigo').textContent = j.codigo;
        document.getElementById('scanStatus').textContent = 'Aguardando o celular…';
        scanTimer = setInterval(pollScan, 2000);
      })
      .catch(function () {
        document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">Erro ao gerar o QR. Feche e tente de novo.</span>';
      });
  }

  function pollScan() {
    if (!scanToken) return;
    fetch('<?= url('/scanner/status') ?>?token=' + encodeURIComponent(scanToken))
      .then(function (r) {
        if (!r.ok) {
          if (r.status === 410) {
            document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">A sessão expirou. Feche e tente de novo.</span>';
            clearInterval(scanTimer); scanTimer = null;
          }
          return null;
        }
        return r.json();
      })
      .then(function (j) {
        if (!j || j.status !== 'pronto' || !j.resultado) return;
        clearInterval(scanTimer); scanTimer = null;
        if (j.erro) {
          document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">' + j.erro + '</span>';
          setTimeout(function () { fecharModal(modalScanQr); }, 1500);
          return;
        }
        var fotos = j.resultado.fotos || [];
        document.getElementById('scanStatus').innerHTML = '<span style="color:var(--inc);font-weight:700">✅ Foto recebida!</span>';
        setTimeout(function () {
          fecharModal(modalScanQr);
          if (fotos.length) {
            abrirRevisao(
              fotos[0],
              j.resultado.extraido || null,
              false,
              j.resultado.limite_atingido ? j.resultado.erro_limite : null
            );
          }
        }, 700);
      });
  }

  document.getElementById('btnFecharScanQr').onclick = function () {
    if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
    fecharModal(modalScanQr);
  };

  var revisaoDataPagamento = document.getElementById('revisaoDataPagamento');
  var btnRevisaoSalvar = document.getElementById('btnRevisaoSalvar');
  var revisaoMsg = document.getElementById('revisaoMsg');

  function atualizarTextoBotaoRevisao() {
    var v = parseFloat(document.getElementById('revisaoValor').value) || 0;
    btnRevisaoSalvar.textContent = v > 0 ? 'Inserir ' + fmtValor(v) + ' no sistema' : 'Inserir no sistema';
  }
  document.getElementById('revisaoValor').addEventListener('input', atualizarTextoBotaoRevisao);

  var categoriaSugerida = null;
  var revisaoLendoAviso = document.getElementById('revisaoLendoAviso');
  var revisaoConfValor = document.getElementById('revisaoConfiancaValor');
  // Código de barras/Pix copia-e-cola da leitura atual (IA ou pré-checagem local) — guardado só
  // em memória pra ir junto no POST de salvar (formRevisaoConta), fechando o mesmo campo que
  // `financeiro-pessoal` já sabe gravar desde sempre (ver FinanceiroPessoalController::salvar())
  // mas que essa tela nunca preenchia — sem isso, verificarCodigo() nunca teria contra o que
  // comparar na PRÓXIMA leitura da mesma conta.
  var revisaoCodigoBarrasAtual = null;
  var revisaoPixColaAtual = null;

  function abrirRevisao(fotoDataUrl, extraido, carregando, avisoLimite) {
    document.getElementById('revisaoFotoImg').src = fotoDataUrl;
    document.getElementById('revisaoDescricao').value = '';
    document.getElementById('revisaoValor').value = '';
    revisaoDataPagamento.value = new Date().toISOString().slice(0, 10);
    document.getElementById('revisaoCategoria').value = 'outros';
    revisaoMsg.textContent = '';
    revisaoConfValor.style.display = 'none';
    document.getElementById('revisaoAprendidoBadge').style.display = 'none';
    categoriaSugerida = null;
    revisaoCodigoBarrasAtual = null;
    revisaoPixColaAtual = null;
    atualizarTextoBotaoRevisao();

    abrirModal(modalRevisaoConta);

    revisaoLendoAviso.style.display = carregando ? 'block' : 'none';
    if (!carregando) {
      aplicarExtraido(extraido, avisoLimite);
      document.getElementById('revisaoDescricao').focus();
    }
  }

  // avisoLimite: mensagem de "limite de leituras do mês atingido" (Etapa 4) — quando vem
  // preenchida, a leitura automática não rolou de propósito (nem chegou a chamar a IA); o
  // usuário cai no mesmo formulário de revisão, só que avisado, pra preencher manualmente.
  function aplicarExtraido(extraido, avisoLimite, aprendido) {
    revisaoLendoAviso.style.display = 'none';
    if (avisoLimite) {
      revisaoMsg.innerHTML = '<span style="color:var(--exp)">' + avisoLimite + '</span>';
    }
    document.getElementById('revisaoAprendidoBadge').style.display = aprendido ? 'block' : 'none';
    if (!extraido) { document.getElementById('revisaoDescricao').focus(); return; }

    if (extraido.descricao) document.getElementById('revisaoDescricao').value = extraido.descricao;
    if (extraido.valor > 0) document.getElementById('revisaoValor').value = extraido.valor.toFixed(2);
    if (extraido.categoria) {
      document.getElementById('revisaoCategoria').value = extraido.categoria;
      categoriaSugerida = extraido.categoria;
    }
    if (extraido.codigo_barras) revisaoCodigoBarrasAtual = String(extraido.codigo_barras);
    if (extraido.pix_copia_cola) revisaoPixColaAtual = String(extraido.pix_copia_cola);
    atualizarTextoBotaoRevisao();

    if (extraido.confianca && extraido.valor > 0) {
      var baixa = extraido.confianca.valor === 'baixa';
      revisaoConfValor.style.display = 'block';
      revisaoConfValor.innerHTML = baixa
        ? '<span style="color:var(--warn)">⚠ confira o valor, a leitura não ficou clara</span>'
        : '<span style="color:var(--inc)">✓ lido automaticamente</span>';
    }
    document.getElementById('revisaoDescricao').focus();
  }

  document.getElementById('btnFecharRevisao').onclick = function () { fecharModal(modalRevisaoConta); };

  document.getElementById('formRevisaoConta').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('revisaoDescricao').value.trim();
    var valor = document.getElementById('revisaoValor').value;
    var categoria = document.getElementById('revisaoCategoria').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      revisaoMsg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }

    btnRevisaoSalvar.disabled = true;
    var dataPagamento = revisaoDataPagamento.value;
    fetch('<?= url('/financeiro-pessoal') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      // hora_informada=0: só temos a DATA escolhida na revisão, nunca uma hora real — mostrar
      // "00:00" na lista seria enganoso, ver hora_informada em renderLista()/date_br().
      body: new URLSearchParams({
        tipo: 'despesa', categoria: categoria, descricao: descricao, valor: valor, origem: 'foto',
        data_hora: dataPagamento, hora_informada: '0',
        codigo_barras: revisaoCodigoBarrasAtual || '', pix_copia_cola: revisaoPixColaAtual || ''
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnRevisaoSalvar.disabled = false;
        if (!j.ok) { revisaoMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>'; return; }
        fecharModal(modalRevisaoConta);
        // Mesmo motivo do formLancamento: insere um valor de verdade, os cards de KPI do topo
        // precisam refletir isso, não só a lista abaixo.
        window.location.reload();
      })
      .catch(function () {
        btnRevisaoSalvar.disabled = false;
        revisaoMsg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  // ── Contas recorrentes (aluguel etc.) ───────────────────────────────────────────────────
  (function () {
    var modal = document.getElementById('modalRecorrentes');
    var listaWrap = document.getElementById('recList');
    var listaVazia = document.getElementById('recListaVazia');
    var listaItens = document.getElementById('recListaItens');
    var formWrap = document.getElementById('recForm');
    var form = document.getElementById('recForm');
    var msg = document.getElementById('recMsg');
    var btnSalvar = document.getElementById('recBtnSalvar');
    var tipoHidden = document.getElementById('recTipo');
    var btnDespesa = document.getElementById('recTipoDespesa');
    var btnReceita = document.getElementById('recTipoReceita');
    var editandoAviso = document.getElementById('recEditandoAviso');
    var editandoId = null;
    var recorrentesAtuais = [];

    preencherSelectContas(document.getElementById('recConta'), false);

    function renderCategoriaChipsRec(selecionada, tipo) {
      var wrap = document.getElementById('recCategoriaChips');
      var chaves = Object.keys(CATS).filter(function (k) { return CATS[k].tipo === tipo; });
      wrap.innerHTML = chaves.map(function (k) {
        var ativo = k === selecionada;
        return '<span class="fp-cat-chip' + (ativo ? ' active' : '') + '" data-cat="' + k + '" role="button" tabindex="0">' +
          '<span class="fp-cat-chip-dot" style="background:' + CATS[k].cor + '"></span>' + escapeHtml(CATS[k].nome) +
        '</span>';
      }).join('');
      if (chaves.length && !chaves.includes(selecionada)) {
        document.getElementById('recCategoria').value = chaves[0];
        wrap.querySelector('.fp-cat-chip').classList.add('active');
      } else {
        document.getElementById('recCategoria').value = selecionada;
      }
      wrap.querySelectorAll('.fp-cat-chip').forEach(function (chip) {
        chip.onclick = function () {
          document.getElementById('recCategoria').value = chip.dataset.cat;
          wrap.querySelectorAll('.fp-cat-chip').forEach(function (c) { c.classList.remove('active'); });
          chip.classList.add('active');
        };
      });
    }

    function marcarTipoRec(tipo) {
      tipoHidden.value = tipo;
      btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa-ativo' : 'fp-btn-ghost');
      btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita-ativo' : 'fp-btn-ghost');
      btnSalvar.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-receita');
      renderCategoriaChipsRec(document.getElementById('recCategoria').value, tipo);
    }
    btnDespesa.onclick = function () { marcarTipoRec('despesa'); };
    btnReceita.onclick = function () { marcarTipoRec('receita'); };

    function mostrarLista() {
      listaWrap.style.display = 'flex';
      formWrap.style.display = 'none';
    }
    function mostrarForm() {
      listaWrap.style.display = 'none';
      formWrap.style.display = 'flex';
    }

    function limparForm() {
      editandoId = null;
      form.reset();
      document.getElementById('recDataInicio').value = HOJE_STR;
      document.getElementById('recParcelas').value = '';
      editandoAviso.style.display = 'none';
      btnSalvar.textContent = 'Salvar';
      msg.textContent = '';
      marcarTipoRec('despesa');
    }

    function renderListaRec() {
      if (!recorrentesAtuais.length) {
        listaVazia.style.display = 'block';
        listaItens.innerHTML = '';
        return;
      }
      listaVazia.style.display = 'none';
      listaItens.innerHTML = recorrentesAtuais.map(function (r) {
        var cor = CATS[r.categoria] ? CATS[r.categoria].cor : '#7A6A88';
        var pausada = Number(r.ativo) !== 1;
        return '<div class="fp-card" style="padding:10px 12px;display:flex;align-items:center;gap:10px' + (pausada ? ';opacity:.55' : '') + '">' +
          '<span class="fp-cat-chip-dot" style="background:' + cor + ';flex:0 0 auto"></span>' +
          '<div style="flex:1;min-width:0">' +
            '<div style="font-weight:600;font-size:.9rem">' + escapeHtml(r.descricao) + (pausada ? ' <span class="fp-faint" style="font-weight:400">(pausada)</span>' : '') + '</div>' +
            '<div class="fp-faint" style="font-size:.78rem">' + fmtValor(r.valor) + ' · todo dia ' + r.dia_vencimento + (r.tipo === 'receita' ? ' · entrada' : '') + (r.data_fim ? ' · até ' + fmtDataLonga(r.data_fim) : '') + '</div>' +
          '</div>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="editar" data-id="' + r.id + '" title="Editar"><svg width="14" height="14" viewBox="0 0 16 16">' + FP_SVG['pencil-fill'] + '</svg></button>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="pausar" data-id="' + r.id + '" title="' + (pausada ? 'Retomar' : 'Pausar') + '">' + (pausada ? '▶' : '⏸') + '</button>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="excluir" data-id="' + r.id + '" title="Excluir"><svg width="14" height="14" viewBox="0 0 16 16">' + FP_SVG['trash3'] + '</svg></button>' +
        '</div>';
      }).join('');

      listaItens.querySelectorAll('[data-acao="editar"]').forEach(function (btn) {
        btn.onclick = function () { abrirEdicao(btn.dataset.id); };
      });
      listaItens.querySelectorAll('[data-acao="pausar"]').forEach(function (btn) {
        btn.onclick = function () {
          var r = recorrentesAtuais.filter(function (x) { return String(x.id) === String(btn.dataset.id); })[0];
          if (!r) return;
          var novoAtivo = Number(r.ativo) === 1 ? '0' : '1';
          fetch('<?= url('/financeiro-pessoal/recorrentes') ?>/' + r.id + '/pausar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
            body: new URLSearchParams({ ativo: novoAtivo })
          })
            .then(function (resp) { return resp.json(); })
            // Reativar ('1') também gera o lançamento deste mês na hora (mesmo gatilho do
            // criar, ver recorrenteSalvar()/recorrentePausar()) — reload, não só carregar().
            // Pausar ('0') não toca em nenhum lançamento, só atualiza a lista do modal.
            .then(function () { if (novoAtivo === '1') { window.location.reload(); } else { carregarRecorrentes(); } });
        };
      });
      listaItens.querySelectorAll('[data-acao="excluir"]').forEach(function (btn) {
        btn.onclick = function () {
          if (!confirm('Excluir esta conta recorrente? Os lançamentos já gerados por ela continuam existindo, só não gera mais nenhum novo.')) return;
          fetch('<?= url('/financeiro-pessoal/recorrentes') ?>/' + btn.dataset.id + '/excluir', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken }
          })
            .then(function (resp) { return resp.json(); })
            .then(function () { carregarRecorrentes(); });
        };
      });
    }

    function abrirEdicao(id) {
      var r = recorrentesAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
      if (!r) return;
      editandoId = r.id;
      document.getElementById('recDescricao').value = r.descricao;
      document.getElementById('recNotas').value = r.notas || '';
      document.getElementById('recValor').value = r.valor;
      document.getElementById('recConta').value = r.conta_id || '';
      document.getElementById('recDiaVencimento').value = r.dia_vencimento;
      // Recorrência antiga, de antes de data_inicio existir, vem com o campo null — cai em hoje
      // em vez de deixar o input vazio (campo é required, F5 sem mexer precisa ter algo válido).
      var dataInicioEdit = r.data_inicio || HOJE_STR;
      document.getElementById('recDataInicio').value = dataInicioEdit;
      // "Sem fim" quando data_fim é null; senão, calcula de volta quantos meses isso representa
      // (clampado 1..60, mesmo teto do <select> e do servidor) pra pré-selecionar a opção certa.
      var parcelasEdit = r.data_fim ? Math.max(1, Math.min(60, mesesEntre(dataInicioEdit, r.data_fim))) : '';
      document.getElementById('recParcelas').value = String(parcelasEdit);
      marcarTipoRec(r.tipo);
      document.getElementById('recCategoria').value = r.categoria;
      renderCategoriaChipsRec(r.categoria, r.tipo);
      editandoAviso.style.display = 'flex';
      btnSalvar.textContent = 'Salvar alterações';
      msg.textContent = '';
      mostrarForm();
    }

    function carregarRecorrentes() {
      fetch('<?= url('/api/financeiro-pessoal/recorrentes') ?>')
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) return;
          recorrentesAtuais = j.recorrentes;
          renderListaRec();
        });
    }

    document.getElementById('btnRecorrentes').onclick = function () {
      mostrarLista();
      carregarRecorrentes();
      abrirModal(modal);
    };
    document.getElementById('btnFecharRecorrentes').onclick = function () { fecharModal(modal); };
    document.getElementById('btnNovaRecorrente').onclick = function () { limparForm(); mostrarForm(); };
    document.getElementById('recCancelarForm').onclick = function () { limparForm(); mostrarLista(); };

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var descricao = document.getElementById('recDescricao').value.trim();
      var valor = document.getElementById('recValor').value;
      var dia = document.getElementById('recDiaVencimento').value;
      if (!descricao || !valor || parseFloat(valor) <= 0) {
        msg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
        return;
      }
      if (!dia || dia < 1 || dia > 31) {
        msg.innerHTML = '<span style="color:var(--exp)">Informe um dia entre 1 e 31.</span>';
        return;
      }
      var dataInicio = document.getElementById('recDataInicio').value;
      if (!dataInicio) {
        msg.innerHTML = '<span style="color:var(--exp)">Informe a data de início.</span>';
        return;
      }
      var emEdicao = editandoId !== null;
      var url = emEdicao
        ? '<?= url('/financeiro-pessoal/recorrentes') ?>/' + editandoId + '/atualizar'
        : '<?= url('/financeiro-pessoal/recorrentes') ?>';
      btnSalvar.disabled = true;
      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
        body: new URLSearchParams(new FormData(form))
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          btnSalvar.disabled = false;
          if (!j.ok) { msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>'; return; }
          // Criar OU editar pode gerar lançamento na hora (os dois chamam
          // gerarRecorrentesPendentes() no servidor — editar também, porque pode abrir/alargar
          // uma janela "Repetir por" finita que ainda não tinha sido preenchida) — se algum
          // vencimento cair no mês visível, os cards de KPI do topo mudam, então sempre recarrega
          // a página, igual o salvar de lançamento comum.
          window.location.reload();
        })
        .catch(function () {
          btnSalvar.disabled = false;
          msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
        });
    });

    marcarTipoRec('despesa');
  })();

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>

<?php endif; ?>
