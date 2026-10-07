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

<button type="button" class="fp-btn fp-btn-primary" id="btnNovoEvento" style="margin-bottom:20px">+ Novo evento</button>

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
      <div id="fpEventoMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary" id="fpEventoBtnSalvar">Criar evento</button>
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

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  var modalEvento = document.getElementById('modalEvento');
  var form = document.getElementById('fpEventoForm');
  var msg = document.getElementById('fpEventoMsg');
  var btnSalvar = document.getElementById('fpEventoBtnSalvar');
  var inputTitulo = document.getElementById('fpEventoTitulo');
  var inputDataHora = document.getElementById('fpEventoDataHora');

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
    abrirModal(modalEvento);
  }

  function novoEvento() {
    editandoId = null;
    form.reset();
    inputDataHora.value = (diaSelecionado || HOJE_STR) + 'T09:00';
    msg.textContent = '';
    btnSalvar.textContent = 'Criar evento';
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
