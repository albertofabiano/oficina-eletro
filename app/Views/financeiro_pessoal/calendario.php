<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];

$anoMesPartes = explode('-', $mes);
$mesLabel = $mesesPt[(int) $anoMesPartes[1]] . ' de ' . $anoMesPartes[0];
$ano = (int) $anoMesPartes[0];
$mesNum = (int) $anoMesPartes[1];
$primeiroDiaSemana = (int) date('w', mktime(0, 0, 0, $mesNum, 1, $ano)); // 0=domingo
$totalDias = (int) date('t', mktime(0, 0, 0, $mesNum, 1, $ano));
$hojeStr = date('Y-m-d');

// Agrupa por dia, em ordem de hora — é daqui que a grade tira o título do primeiro item de
// cada dia (mostrado direto no quadradinho); a lista de verdade (pra abrir ao clicar num dia)
// vem de $itensAgenda mesmo, montada em JS a partir de itensAtuais.
//
// $vencimentosDoMes (lançamentos em aberto, lido DIRETO de financeiro_pessoal_lancamentos —
// nunca copiado pra `_eventos`, ver FinanceiroPessoalController::calendario()) vira um "evento
// virtual": mesmo formato {id, titulo, data_hora}, só que `id` ganha o prefixo "lanc-" (nunca
// colide com id de evento de verdade) e carrega `valor`/`vencido`/`ehLancamento` extras pra
// view saber desenhar diferente (sem editar/excluir, cor vermelha se vencido).
$itensAgenda = [];
foreach ($eventos as $ev) {
    $itensAgenda[] = $ev + ['ehLancamento' => false];
}
foreach ($vencimentosDoMes as $v) {
    $vencido = $v['vencimento'] < $hojeStr;
    $emoji = $v['tipo'] === 'receita' ? '💰 ' : '💸 ';
    $itensAgenda[] = [
        'id'          => 'lanc-' . $v['id'],
        'titulo'      => $emoji . $v['descricao'],
        'data_hora'   => $v['vencimento'] . ' 00:00:00',
        'ehLancamento'=> true,
        'valor'       => (float) $v['valor'],
        'tipo'        => $v['tipo'],
        'vencido'     => $vencido,
    ];
}

$eventosPorDiaGrade = [];
foreach ($itensAgenda as $ev) {
    $diaChave = substr($ev['data_hora'], 8, 2);
    $eventosPorDiaGrade[$diaChave][] = $ev;
}
foreach ($eventosPorDiaGrade as &$itensDia) {
    usort($itensDia, fn($a, $b) => $a['data_hora'] <=> $b['data_hora']);
}
unset($itensDia);
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
    <h1 class="fp-greeting">Agenda</h1>
    <div class="fp-faint">Seus compromissos de <?= e($mesLabel) ?></div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal/calendario') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal/calendario') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovoEvento">+ Novo evento</button>
  <button type="button" class="fp-btn fp-btn-ghost" id="btnEventosRecorrentes" style="display:inline-flex;align-items:center;gap:8px">
    <?= fp_icone('arrow-counterclockwise') ?> Eventos recorrentes
  </button>
</div>

<div class="fp-card" style="margin-bottom:20px">
  <div class="fp-cal-grid">
    <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dw): ?>
    <div class="fp-cal-weekday"><?= $dw ?></div>
    <?php endforeach; ?>

    <?php for ($i = 0; $i < $primeiroDiaSemana; $i++): ?>
    <div class="fp-cal-day vazio" aria-hidden="true"></div>
    <?php endfor; ?>

    <?php for ($d = 1; $d <= $totalDias; $d++):
      $diaChave = sprintf('%02d', $d);
      $dataCompleta = $mes . '-' . $diaChave;
      $itensDia = $eventosPorDiaGrade[$diaChave] ?? [];
      $primeiro = $itensDia[0] ?? null;
      $extraDia = count($itensDia) - 1;
      // Lançamento vencido pinta vermelho (pedido explícito); outros itens (evento manual ou
      // lançamento ainda não vencido) usam a cor padrão do título.
      $corTitulo = ($primeiro && !empty($primeiro['ehLancamento']) && !empty($primeiro['vencido'])) ? 'var(--exp)' : null;
    ?>
    <div class="fp-cal-day<?= $dataCompleta === $hojeStr ? ' hoje' : '' ?>" data-dia="<?= e($dataCompleta) ?>" role="button" tabindex="0" aria-label="<?= $d ?> de <?= e($mesLabel) ?>">
      <span class="fp-cal-day-num"><?= $d ?></span>
      <div class="fp-cal-day-eventos">
        <?php if ($primeiro !== null): ?>
        <span class="fp-cal-day-titulo"<?= $corTitulo ? ' style="color:' . e($corTitulo) . '"' : '' ?>>
          <?= e($primeiro['titulo']) ?><?php if (!empty($primeiro['ehLancamento'])): ?> · R$ <?= number_format($primeiro['valor'], 2, ',', '.') ?><?php endif; ?>
        </span>
        <?php if ($extraDia > 0): ?><span class="fp-cal-day-mais" title="+<?= $extraDia ?> a mais nesse dia">+<?= $extraDia ?></span><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div id="fpDiaPainel" style="display:none">
  <h2 id="fpDiaTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px"></h2>
  <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
</div>

<div class="fp-modal-backdrop" id="modalEvento">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Evento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharEvento" aria-label="Fechar">×</button>
    </div>
    <form id="fpEventoForm" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <input type="text" name="titulo" id="fpEventoTitulo" class="fp-input" placeholder="Título (ex.: Consulta médica)" maxlength="150" required>
      <input type="datetime-local" name="data_hora" id="fpEventoDataHora" class="fp-input" required>

      <!-- Só visível editando um item que vem de um molde recorrente — Contas Recorrentes
           (lançamento parcelado, `lancamento_recorrente_id`) ou Eventos Recorrentes
           (`evento_recorrente_id`). Mexe direto no período da SÉRIE, não neste item específico,
           mesmo atalho já existente no modal de Lançamento (lancamentos.php). -->
      <div id="fpEventoRecBloco" style="display:none;flex-direction:column;gap:10px;border-top:1px solid var(--border);padding-top:10px">
        <div class="fp-faint" id="fpEventoRecLabel" style="font-size:.8rem;display:flex;align-items:center;gap:6px">
          <?= fp_icone('arrow-counterclockwise') ?> Isto é recorrente — editar a série:
        </div>
        <div style="display:flex;gap:8px">
          <div style="flex:1">
            <label for="fpEventoRecDataInicio" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Início</label>
            <input type="date" id="fpEventoRecDataInicio" class="fp-input">
          </div>
          <div style="flex:1">
            <label for="fpEventoRecParcelas" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Repetir por</label>
            <select id="fpEventoRecParcelas" class="fp-select">
              <option value="">Sem fim (repete pra sempre)</option>
              <?php for ($n = 1; $n <= 60; $n++): ?>
              <option value="<?= $n ?>"><?= $n ?> <?= $n === 1 ? 'mês' : 'meses' ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <div id="fpEventoMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary" id="fpEventoBtnSalvar">Criar evento</button>
    </form>
  </div>
</div>

<div class="fp-modal-backdrop" id="modalEventosRecorrentes">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Eventos recorrentes</strong>
      <button type="button" class="fp-modal-close" id="btnFecharEventosRecorrentes" aria-label="Fechar">×</button>
    </div>

    <div id="evrList" style="display:flex;flex-direction:column;gap:8px">
      <div id="evrListaVazia" class="fp-faint" style="font-size:.85rem;display:none">Nenhum evento recorrente ainda — cadastre uma consulta, uma assinatura ou qualquer compromisso que se repete todo mês.</div>
      <div id="evrListaItens" style="display:flex;flex-direction:column;gap:8px"></div>
      <button type="button" class="fp-btn fp-btn-primary" id="btnNovoEventoRecorrente" style="margin-top:4px">+ Novo evento recorrente</button>
    </div>

    <form id="evrForm" style="display:none;flex-direction:column;gap:10px;margin-top:4px">
      <?= csrf_field() ?>
      <div id="evrEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando evento recorrente</span>
      </div>

      <input type="text" name="titulo" id="evrTitulo" class="fp-input" placeholder="Título (ex.: Consulta médica)" maxlength="150" required>

      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="evrDiaMes" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Todo dia (1 a 31)</label>
          <input type="number" name="dia_mes" id="evrDiaMes" class="fp-input" min="1" max="31" step="1" placeholder="Ex.: 10" required>
        </div>
        <div style="flex:1">
          <label for="evrHora" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Horário</label>
          <input type="time" name="hora" id="evrHora" class="fp-input" required>
        </div>
      </div>
      <div class="fp-faint" style="font-size:.76rem;margin-top:-4px">Gera o evento sozinho todo mês nesse dia e horário (mês com menos dias cai no último dia dele).</div>

      <div style="display:flex;gap:8px">
        <div style="flex:1">
          <label for="evrDataInicio" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Início</label>
          <input type="date" name="data_inicio" id="evrDataInicio" class="fp-input" required>
        </div>
        <div style="flex:1">
          <label for="evrParcelas" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Repetir por</label>
          <select name="parcelas" id="evrParcelas" class="fp-select">
            <option value="">Sem fim (repete pra sempre)</option>
            <?php for ($n = 1; $n <= 60; $n++): ?>
            <option value="<?= $n ?>"><?= $n ?> <?= $n === 1 ? 'mês' : 'meses' ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="fp-faint" style="font-size:.76rem;margin-top:-4px">"Repetir por" serve pra um tratamento com data pra acabar (ex.: 6 consultas) — "Sem fim" é pra compromisso fixo tipo "pagar fatura".</div>

      <div id="evrMsg" class="fp-muted" style="font-size:.82rem"></div>

      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-ghost" id="evrCancelarForm" style="flex:1">Voltar pra lista</button>
        <button type="submit" class="fp-btn fp-btn-primary" id="evrBtnSalvar" style="flex:1">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  var HOJE_STR = <?= json_encode($hojeStr) ?>;
  var csrfToken = '<?= csrf_token() ?>';
  var painel = document.getElementById('fpDiaPainel');
  var tituloPainel = document.getElementById('fpDiaTitulo');
  var lista = document.getElementById('fpLista');
  // Mistura evento manual + lançamento em aberto (vencimento), mesma lógica do PHP acima —
  // junta aqui pra JS e PHP nunca divergirem no formato que renderLista()/atualizarGrade()
  // esperam. "fmtValor" usado só pros itens com ehLancamento=true.
  function montarItensAgenda(eventos, vencimentos, hojeStr) {
    var itens = eventos.map(function (e) { return Object.assign({ ehLancamento: false }, e); });
    (vencimentos || []).forEach(function (v) {
      itens.push({
        id: 'lanc-' + v.id,
        titulo: (v.tipo === 'receita' ? '💰 ' : '💸 ') + v.descricao,
        data_hora: v.vencimento + ' 00:00:00',
        ehLancamento: true,
        valor: parseFloat(v.valor),
        tipo: v.tipo,
        vencido: v.vencimento < hojeStr
      });
    });
    return itens;
  }
  var eventosAtuais = montarItensAgenda(<?= json_encode($eventos, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($vencimentosDoMes, JSON_UNESCAPED_UNICODE) ?>, HOJE_STR);
  var diaSelecionado = null;
  var editandoId = null;
  // Molde recorrente (Contas Recorrentes OU Eventos Recorrentes) por trás do item em edição —
  // null quando o item é um evento manual sem vínculo nenhum. "tipo" diz qual endpoint salvar
  // o período chama (#fpEventoRecBloco).
  var recorrenteEditandoId = null;
  var recorrenteEditandoTipo = null; // 'lancamento' | 'evento' | null

  // DD/MM/AAAA a partir de "YYYY-MM-DD" via split (não via Date, que interpretaria a data como
  // meia-noite UTC e voltaria um dia em qualquer fuso negativo tipo America/Sao_Paulo) — mesma
  // técnica já usada em lancamentos.php e na própria IIFE de Eventos Recorrentes abaixo.
  function fmtDataLonga(iso) {
    if (!iso) return '';
    var p = iso.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  // Quantos meses de início até fim, inclusive os dois — espelha o cálculo do servidor
  // (data_fim = início + N-1 meses), ao contrário, pra popular "Repetir por" ao editar.
  function mesesEntre(inicioIso, fimIso) {
    var pi = inicioIso.split('-'), pf = fimIso.split('-');
    return (Number(pf[0]) - Number(pi[0])) * 12 + (Number(pf[1]) - Number(pi[1])) + 1;
  }

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  var modalEvento = document.getElementById('modalEvento');
  var form = document.getElementById('fpEventoForm');
  var msg = document.getElementById('fpEventoMsg');
  var btnSalvar = document.getElementById('fpEventoBtnSalvar');
  var inputTitulo = document.getElementById('fpEventoTitulo');
  var inputDataHora = document.getElementById('fpEventoDataHora');
  var recBloco = document.getElementById('fpEventoRecBloco');
  var recLabel = document.getElementById('fpEventoRecLabel');
  var recDataInicio = document.getElementById('fpEventoRecDataInicio');
  var recParcelas = document.getElementById('fpEventoRecParcelas');

  // Busca o molde por trás do item (lançamento-recorrente OU evento-recorrente — nunca os
  // dois juntos, ver comentário de buscarEventosDoMes() no controller) e popula Início/Repetir
  // por. Reaproveita os mesmos endpoints de listagem que "Contas recorrentes"/"Eventos
  // recorrentes" já usam (lista inteira; não existe endpoint de buscar 1 só, e a lista de
  // recorrências de um perfil é sempre pequena o bastante pra não valer a pena criar um).
  function popularBlocoRecorrenteEvento(lancamentoRecorrenteId, eventoRecorrenteId) {
    if (!lancamentoRecorrenteId && !eventoRecorrenteId) {
      recorrenteEditandoId = null;
      recorrenteEditandoTipo = null;
      recBloco.style.display = 'none';
      return;
    }
    var tipo = eventoRecorrenteId ? 'evento' : 'lancamento';
    var url = eventoRecorrenteId
      ? '<?= url('/api/financeiro-pessoal/eventos-recorrentes') ?>'
      : '<?= url('/api/financeiro-pessoal/recorrentes') ?>';
    var idProcurado = eventoRecorrenteId || lancamentoRecorrenteId;
    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        var rec = j.recorrentes.filter(function (x) { return String(x.id) === String(idProcurado); })[0];
        if (!rec) return; // molde pode ter sido excluído — item continua, só sem o atalho
        recorrenteEditandoId = rec.id;
        recorrenteEditandoTipo = tipo;
        recLabel.innerHTML = '<?= fp_icone('arrow-counterclockwise') ?> ' +
          (tipo === 'evento' ? 'Este evento é recorrente' : 'Esta conta é recorrente') + ' — editar a série:';
        var dataInicioRec = rec.data_inicio || HOJE_STR;
        recDataInicio.value = dataInicioRec;
        var parcelasRec = rec.data_fim ? Math.max(1, Math.min(60, mesesEntre(dataInicioRec, rec.data_fim))) : '';
        recParcelas.value = String(parcelasRec);
        recBloco.style.display = 'flex';
      });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }
  function fmtHora(dt) {
    var d = new Date(dt.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dt;
    return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  function ucfirstJs(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function fmtDiaLabel(diaStr) {
    var d = new Date(diaStr + 'T00:00:00');
    return ucfirstJs(d.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' }));
  }

  function selecionarDia(diaStr) {
    diaSelecionado = diaStr;
    document.querySelectorAll('.fp-cal-day').forEach(function (el) {
      el.classList.toggle('selected', el.dataset.dia === diaStr);
    });
    var itens = eventosAtuais
      .filter(function (e) { return e.data_hora.slice(0, 10) === diaStr; })
      .sort(function (a, b) { return a.data_hora < b.data_hora ? -1 : (a.data_hora > b.data_hora ? 1 : 0); });
    tituloPainel.textContent = fmtDiaLabel(diaStr) + (itens.length ? ' (' + itens.length + ')' : '');
    painel.style.display = 'block';
    renderLista(itens);
  }

  document.querySelectorAll('.fp-cal-day:not(.vazio)').forEach(function (el) {
    el.onclick = function () { selecionarDia(el.dataset.dia); };
    el.onkeydown = function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); el.click(); }
    };
  });

  // Recalcula o título mostrado em cada quadradinho a partir de eventosAtuais — chamado
  // depois de qualquer ação que muda o conjunto (criar/editar/excluir), pra grade e painel
  // nunca ficarem desencontrados sem precisar de F5. Mesma regra do PHP (primeiro evento do
  // dia, em ordem de hora, + contagem do resto).
  function atualizarGrade() {
    var porDia = {};
    eventosAtuais
      .slice()
      .sort(function (a, b) { return a.data_hora < b.data_hora ? -1 : (a.data_hora > b.data_hora ? 1 : 0); })
      .forEach(function (e) {
        var d = e.data_hora.slice(0, 10);
        (porDia[d] = porDia[d] || []).push(e);
      });
    document.querySelectorAll('.fp-cal-day:not(.vazio)').forEach(function (el) {
      var wrap = el.querySelector('.fp-cal-day-eventos');
      if (!wrap) return;
      var itens = porDia[el.dataset.dia] || [];
      if (!itens.length) { wrap.innerHTML = ''; return; }
      var primeiro = itens[0];
      var extra = itens.length - 1;
      var corEstilo = (primeiro.ehLancamento && primeiro.vencido) ? ' style="color:var(--exp)"' : '';
      var tituloTxt = escapeHtml(primeiro.titulo) + (primeiro.ehLancamento ? ' · ' + fmtValor(primeiro.valor) : '');
      wrap.innerHTML =
        '<span class="fp-cal-day-titulo"' + corEstilo + '>' + tituloTxt + '</span>' +
        (extra > 0 ? '<span class="fp-cal-day-mais" title="+' + extra + ' a mais nesse dia">+' + extra + '</span>' : '');
    });
  }

  function renderLista(eventos) {
    lista.innerHTML = '';
    if (!eventos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">Nenhum evento nesse dia.</div>';
      return;
    }
    eventos.forEach(function (e) {
      var row = document.createElement('div');
      row.className = 'fp-evento-row';
      if (e.ehLancamento) {
        // Lançamento em aberto — só leitura aqui (editar/marcar como pago é na tela de
        // Lançamentos); mostra o valor, vermelho se já venceu.
        row.innerHTML =
          '<div class="fp-evento-row-info">' +
            '<div class="fp-evento-titulo">' + escapeHtml(e.titulo) + '</div>' +
            '<div class="fp-evento-hora fp-mono" style="color:' + (e.vencido ? 'var(--exp)' : 'var(--muted)') + '">' +
              (e.vencido ? 'Venceu' : 'Vence') + ' · ' + fmtValor(e.valor) +
            '</div>' +
          '</div>' +
          '<a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="fp-btn fp-btn-ghost fp-btn-sm" style="text-decoration:none">Ver lançamento</a>';
        lista.appendChild(row);
        return;
      }
      row.innerHTML =
        '<div class="fp-evento-row-info">' +
          '<div class="fp-evento-titulo">' + escapeHtml(e.titulo) + '</div>' +
          '<div class="fp-evento-hora fp-mono">' + fmtHora(e.data_hora) + '</div>' +
        '</div>' +
        '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-evento-editar" data-id="' + e.id + '">Editar</button>' +
        '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-evento-excluir" data-id="' + e.id + '" style="color:var(--exp)">Excluir</button>';
      lista.appendChild(row);
    });
    lista.querySelectorAll('.fp-evento-editar').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-evento-excluir').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
  }

  function iniciarEdicao(id) {
    var ev = eventosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!ev) return;
    editandoId = id;
    inputTitulo.value = ev.titulo;
    inputDataHora.value = ev.data_hora.slice(0, 16).replace(' ', 'T');
    msg.textContent = '';
    btnSalvar.textContent = 'Salvar alterações';
    popularBlocoRecorrenteEvento(ev.lancamento_recorrente_id, ev.evento_recorrente_id);
    abrirModal(modalEvento);
  }

  function novoEvento() {
    editandoId = null;
    form.reset();
    inputDataHora.value = (diaSelecionado || HOJE_STR) + 'T09:00';
    msg.textContent = '';
    btnSalvar.textContent = 'Criar evento';
    popularBlocoRecorrenteEvento(null, null);
    abrirModal(modalEvento);
    inputTitulo.focus();
  }
  document.getElementById('btnNovoEvento').onclick = novoEvento;
  document.getElementById('btnFecharEvento').onclick = function () { fecharModal(modalEvento); };

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal/eventos') ?>?mes=' + encodeURIComponent(MES_SELECIONADO))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        eventosAtuais = montarItensAgenda(j.eventos, j.vencimentos, HOJE_STR);
        atualizarGrade();
        if (diaSelecionado) selecionarDia(diaSelecionado);
      });
  }

  function excluir(id) {
    if (!confirm('Excluir esse evento?')) return;
    fetch('<?= url('/financeiro-pessoal/eventos') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregar(); });
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var titulo = inputTitulo.value.trim();
    var dataHora = inputDataHora.value;
    if (!titulo || !dataHora) {
      msg.innerHTML = '<span style="color:var(--exp)">Preencha título e data/hora.</span>';
      return;
    }
    if (recorrenteEditandoId !== null && !recDataInicio.value) {
      msg.innerHTML = '<span style="color:var(--exp)">Informe a data de início da recorrência.</span>';
      return;
    }
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    var emEdicao = editandoId !== null;
    var saveUrl = emEdicao
      ? '<?= url('/financeiro-pessoal/eventos') ?>/' + editandoId + '/atualizar'
      : '<?= url('/financeiro-pessoal/eventos') ?>';

    fetch(saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ titulo: titulo, data_hora: dataHora })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        if (!j.ok) {
          msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        // Se o bloco "Esta conta/evento é recorrente" está visível, salva o período do MOLDE
        // antes de seguir — requisição separada, endpoint próprio (atualizarPeriodo() de cada
        // service) — e recarrega a página inteira, porque isso pode gerar/mover ocorrência em
        // qualquer mês (mesmo motivo do reload em lancamentos.php: carregar() só atualiza a
        // lista deste item, nunca o resto da grade).
        if (recorrenteEditandoId !== null) {
          var urlPeriodo = recorrenteEditandoTipo === 'evento'
            ? '<?= url('/financeiro-pessoal/eventos-recorrentes') ?>/' + recorrenteEditandoId + '/periodo'
            : '<?= url('/financeiro-pessoal/recorrentes') ?>/' + recorrenteEditandoId + '/periodo';
          fetch(urlPeriodo, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
            body: new URLSearchParams({ data_inicio: recDataInicio.value, parcelas: recParcelas.value })
          }).catch(function () {}).then(function () { window.location.reload(); });
          return;
        }
        fecharModal(modalEvento);
        // Se criou/editou pra um dia diferente do que estava selecionado, já troca a seleção
        // pro novo dia — o usuário vê o resultado sem precisar clicar de novo.
        diaSelecionado = dataHora.slice(0, 10);
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  // ── Eventos recorrentes (consulta médica etc.) — mesmo padrão de "Contas recorrentes" do
  // Lançamentos (lancamentos.php), só sem nenhum campo de dinheiro. ─────────────────────────
  (function () {
    var modal = document.getElementById('modalEventosRecorrentes');
    var listaWrap = document.getElementById('evrList');
    var listaVazia = document.getElementById('evrListaVazia');
    var listaItens = document.getElementById('evrListaItens');
    var formWrap = document.getElementById('evrForm');
    var form = document.getElementById('evrForm');
    var msg = document.getElementById('evrMsg');
    var btnSalvar = document.getElementById('evrBtnSalvar');
    var editandoAviso = document.getElementById('evrEditandoAviso');
    var editandoIdEvr = null;
    var recorrentesAtuais = [];

    // DD/MM/AAAA a partir de "YYYY-MM-DD" via split (não via Date, que interpretaria a data
    // como meia-noite UTC e voltaria um dia em qualquer fuso negativo tipo America/Sao_Paulo).
    function fmtDataLongaEvr(iso) {
      if (!iso) return '';
      var p = iso.split('-');
      return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }
    // Quantos meses de início até fim, inclusive os dois — espelha o cálculo do servidor
    // (dadosEventoRecorrenteDoPost(): data_fim = início + N-1 meses), só que ao contrário, pra
    // popular o <select> "Repetir por" ao editar uma recorrência já salva.
    function mesesEntreEvr(inicioIso, fimIso) {
      var pi = inicioIso.split('-'), pf = fimIso.split('-');
      return (Number(pf[0]) - Number(pi[0])) * 12 + (Number(pf[1]) - Number(pi[1])) + 1;
    }

    function mostrarListaEvr() {
      listaWrap.style.display = 'flex';
      formWrap.style.display = 'none';
    }
    function mostrarFormEvr() {
      listaWrap.style.display = 'none';
      formWrap.style.display = 'flex';
    }

    function limparFormEvr() {
      editandoIdEvr = null;
      form.reset();
      document.getElementById('evrDataInicio').value = HOJE_STR;
      document.getElementById('evrHora').value = '08:00';
      document.getElementById('evrParcelas').value = '';
      editandoAviso.style.display = 'none';
      btnSalvar.textContent = 'Salvar';
      msg.textContent = '';
    }

    function renderListaEvr() {
      if (!recorrentesAtuais.length) {
        listaVazia.style.display = 'block';
        listaItens.innerHTML = '';
        return;
      }
      listaVazia.style.display = 'none';
      listaItens.innerHTML = recorrentesAtuais.map(function (r) {
        var pausada = Number(r.ativo) !== 1;
        return '<div class="fp-card" style="padding:10px 12px;display:flex;align-items:center;gap:10px' + (pausada ? ';opacity:.55' : '') + '">' +
          '<div style="flex:1;min-width:0">' +
            '<div style="font-weight:600;font-size:.9rem">' + escapeHtml(r.titulo) + (pausada ? ' <span class="fp-faint" style="font-weight:400">(pausado)</span>' : '') + '</div>' +
            '<div class="fp-faint" style="font-size:.78rem">todo dia ' + r.dia_mes + ' às ' + String(r.hora).slice(0, 5) + (r.data_fim ? ' · até ' + fmtDataLongaEvr(r.data_fim) : '') + '</div>' +
          '</div>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="editar" data-id="' + r.id + '" title="Editar">✎</button>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="pausar" data-id="' + r.id + '" title="' + (pausada ? 'Retomar' : 'Pausar') + '">' + (pausada ? '▶' : '⏸') + '</button>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" data-acao="excluir" data-id="' + r.id + '" title="Excluir">🗑</button>' +
        '</div>';
      }).join('');

      listaItens.querySelectorAll('[data-acao="editar"]').forEach(function (btn) {
        btn.onclick = function () { abrirEdicaoEvr(btn.dataset.id); };
      });
      listaItens.querySelectorAll('[data-acao="pausar"]').forEach(function (btn) {
        btn.onclick = function () {
          var r = recorrentesAtuais.filter(function (x) { return String(x.id) === String(btn.dataset.id); })[0];
          if (!r) return;
          var novoAtivo = Number(r.ativo) === 1 ? '0' : '1';
          fetch('<?= url('/financeiro-pessoal/eventos-recorrentes') ?>/' + r.id + '/pausar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
            body: new URLSearchParams({ ativo: novoAtivo })
          })
            .then(function (resp) { return resp.json(); })
            // Reativar também gera o evento deste mês na hora (mesmo gatilho do criar, ver
            // eventoRecorrenteSalvar()/eventoRecorrentePausar() no controller) — reload, não só
            // a lista do modal. Pausar não toca em nenhum evento, só atualiza a lista.
            .then(function () { if (novoAtivo === '1') { window.location.reload(); } else { carregarEventosRecorrentes(); } });
        };
      });
      listaItens.querySelectorAll('[data-acao="excluir"]').forEach(function (btn) {
        btn.onclick = function () {
          if (!confirm('Excluir este evento recorrente? Os eventos já gerados por ele continuam existindo, só não gera mais nenhum novo.')) return;
          fetch('<?= url('/financeiro-pessoal/eventos-recorrentes') ?>/' + btn.dataset.id + '/excluir', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken }
          })
            .then(function (resp) { return resp.json(); })
            .then(function () { carregarEventosRecorrentes(); });
        };
      });
    }

    function abrirEdicaoEvr(id) {
      var r = recorrentesAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
      if (!r) return;
      editandoIdEvr = r.id;
      document.getElementById('evrTitulo').value = r.titulo;
      document.getElementById('evrDiaMes').value = r.dia_mes;
      document.getElementById('evrHora').value = String(r.hora).slice(0, 5);
      // Recorrência antiga, de antes de data_inicio existir, vem com o campo null — cai em
      // hoje em vez de deixar o input vazio (campo é required).
      var dataInicioEdit = r.data_inicio || HOJE_STR;
      document.getElementById('evrDataInicio').value = dataInicioEdit;
      // "Sem fim" quando data_fim é null; senão, calcula de volta quantos meses isso
      // representa (clampado 1..60, mesmo teto do <select> e do servidor).
      var parcelasEdit = r.data_fim ? Math.max(1, Math.min(60, mesesEntreEvr(dataInicioEdit, r.data_fim))) : '';
      document.getElementById('evrParcelas').value = String(parcelasEdit);
      editandoAviso.style.display = 'flex';
      btnSalvar.textContent = 'Salvar alterações';
      msg.textContent = '';
      mostrarFormEvr();
    }

    function carregarEventosRecorrentes() {
      fetch('<?= url('/api/financeiro-pessoal/eventos-recorrentes') ?>')
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) return;
          recorrentesAtuais = j.recorrentes;
          renderListaEvr();
        });
    }

    document.getElementById('btnEventosRecorrentes').onclick = function () {
      mostrarListaEvr();
      carregarEventosRecorrentes();
      abrirModal(modal);
    };
    document.getElementById('btnFecharEventosRecorrentes').onclick = function () { fecharModal(modal); };
    document.getElementById('btnNovoEventoRecorrente').onclick = function () { limparFormEvr(); mostrarFormEvr(); };
    document.getElementById('evrCancelarForm').onclick = function () { limparFormEvr(); mostrarListaEvr(); };

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var titulo = document.getElementById('evrTitulo').value.trim();
      var dia = document.getElementById('evrDiaMes').value;
      var hora = document.getElementById('evrHora').value;
      var dataInicio = document.getElementById('evrDataInicio').value;
      if (!titulo) {
        msg.innerHTML = '<span style="color:var(--exp)">Dê um título pra esse evento recorrente.</span>';
        return;
      }
      if (!dia || dia < 1 || dia > 31) {
        msg.innerHTML = '<span style="color:var(--exp)">Informe um dia entre 1 e 31.</span>';
        return;
      }
      if (!hora) {
        msg.innerHTML = '<span style="color:var(--exp)">Informe o horário.</span>';
        return;
      }
      if (!dataInicio) {
        msg.innerHTML = '<span style="color:var(--exp)">Informe a data de início.</span>';
        return;
      }
      var emEdicaoEvr = editandoIdEvr !== null;
      var url = emEdicaoEvr
        ? '<?= url('/financeiro-pessoal/eventos-recorrentes') ?>/' + editandoIdEvr + '/atualizar'
        : '<?= url('/financeiro-pessoal/eventos-recorrentes') ?>';
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
          // Criar OU editar pode gerar evento na hora (os dois chamam
          // gerarEventosRecorrentesPendentes() no servidor) — se algum cair no mês visível, a
          // grade do calendário precisa refletir isso, então recarrega a página.
          window.location.reload();
        })
        .catch(function () {
          btnSalvar.disabled = false;
          msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
        });
    });
  })();

  // Abre direto no dia de hoje se ele pertencer ao mês sendo exibido — poupa um clique no
  // caso comum (olhar a agenda logo depois de mexer em algo hoje).
  <?php if (substr($hojeStr, 0, 7) === $mes): ?>
  selecionarDia(HOJE_STR);
  <?php endif; ?>

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>

<?php endif; ?>
