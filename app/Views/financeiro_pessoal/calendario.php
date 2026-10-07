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

// Agrega por dia só pra decidir o ponto colorido da grade — a lista de verdade (pra abrir ao
// clicar num dia) vem de $lancamentos mesmo, montada em JS a partir de lancamentosAtuais.
$porDia = [];
foreach ($lancamentos as $l) {
    $diaChave = substr($l['data_hora'], 8, 2);
    if (!isset($porDia[$diaChave])) { $porDia[$diaChave] = ['receita' => false, 'despesa' => false]; }
    $porDia[$diaChave][$l['tipo'] === 'receita' ? 'receita' : 'despesa'] = true;
}
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
    <h1 class="fp-greeting">Calendário</h1>
    <div class="fp-faint">Lançamentos de <?= e($mesLabel) ?>, por dia</div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal/calendario') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal/calendario') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
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
      $info = $porDia[$diaChave] ?? null;
    ?>
    <div class="fp-cal-day<?= $dataCompleta === $hojeStr ? ' hoje' : '' ?>" data-dia="<?= e($dataCompleta) ?>" role="button" tabindex="0" aria-label="<?= $d ?> de <?= e($mesLabel) ?>">
      <span class="fp-cal-day-num"><?= $d ?></span>
      <span class="fp-cal-day-dots">
        <?php if ($info && $info['receita']): ?><span class="fp-cal-dot" style="background:var(--inc)"></span><?php endif; ?>
        <?php if ($info && $info['despesa']): ?><span class="fp-cal-dot" style="background:var(--exp)"></span><?php endif; ?>
      </span>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div id="fpDiaPainel" style="display:none">
  <h2 id="fpDiaTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px"></h2>
  <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
</div>

<!-- Só pra "Editar" um lançamento já existente (abrir ao clicar num dia na grade nunca cria
     lançamento novo aqui — fora de escopo desta tela, ver Lançamentos/Resumo pra isso) — mesmo
     modal/ids de sempre (fpForm/fpTipo*/fpDescricao/fpValor/fpCategoria/fpMsg/fpBtnSalvar/
     fpEditandoAviso/fpCancelarEdicao), copiado aqui porque as telas não compartilham <script>. -->
<div class="fp-modal-backdrop" id="modalLancamento">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Lançamento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharLancamento" aria-label="Fechar">×</button>
    </div>
    <form id="fpForm" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div id="fpEditandoAviso" class="fp-mono" style="display:flex;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando lançamento</span>
        <a href="#" id="fpCancelarEdicao" style="color:var(--muted);text-decoration:underline">cancelar</a>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="fpTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="fpTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
      </div>
      <input type="hidden" name="tipo" id="fpTipo" value="despesa">

      <input type="text" name="descricao" id="fpDescricao" class="fp-input" placeholder="Descrição (ex.: Supermercado)" maxlength="150" required>

      <div class="fp-row-valor-cat">
        <input type="number" name="valor" id="fpValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
        <select name="categoria" id="fpCategoria" class="fp-select" style="flex:1">
          <?php foreach ($categorias as $chave => $c): ?>
          <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="fpMsg" class="fp-muted" style="font-size:.82rem"></div>

      <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Salvar alterações</button>
    </form>
  </div>
</div>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  var FP_SVG = {
    'chevron-down': <?= json_encode(fp_icone('chevron-down')) ?>
  };
  var lista = document.getElementById('fpLista');
  var painel = document.getElementById('fpDiaPainel');
  var tituloPainel = document.getElementById('fpDiaTitulo');
  var modalLancamento = document.getElementById('modalLancamento');
  var form = document.getElementById('fpForm');
  var msg = document.getElementById('fpMsg');
  var btnSalvar = document.getElementById('fpBtnSalvar');
  var tipoHidden = document.getElementById('fpTipo');
  var btnDespesa = document.getElementById('fpTipoDespesa');
  var btnReceita = document.getElementById('fpTipoReceita');
  var csrfToken = '<?= csrf_token() ?>';
  var lancamentosAtuais = <?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>;
  var editandoId = null;
  // Estado de colapso de cada lançamento, chave id — mesmo padrão já usado em Lançamentos/
  // Resumo (client-side só, não persiste entre recargas).
  var lancColapsados = {};
  var editandoAviso = document.getElementById('fpEditandoAviso');
  var diaSelecionado = null;

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-ghost');
    btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita' : 'fp-btn-ghost');
  }
  btnDespesa.onclick = function () { marcarTipo('despesa'); };
  btnReceita.onclick = function () { marcarTipo('receita'); };

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function fmtData(dt) {
    var d = new Date(dt.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dt;
    return d.toLocaleDateString('pt-BR') + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
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
    var itens = lancamentosAtuais.filter(function (l) { return l.data_hora.slice(0, 10) === diaStr; });
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

  // Recalcula os pontinhos coloridos da grade a partir de lancamentosAtuais — chamado depois
  // de qualquer ação que muda o conjunto (editar categoria/tipo, excluir), pra grade e painel
  // do dia nunca ficarem desencontrados sem precisar de F5.
  function atualizarPontosGrade() {
    var porDia = {};
    lancamentosAtuais.forEach(function (l) {
      var d = l.data_hora.slice(0, 10);
      if (!porDia[d]) porDia[d] = { receita: false, despesa: false };
      porDia[d][l.tipo === 'receita' ? 'receita' : 'despesa'] = true;
    });
    document.querySelectorAll('.fp-cal-day:not(.vazio)').forEach(function (el) {
      var info = porDia[el.dataset.dia];
      var dotsWrap = el.querySelector('.fp-cal-day-dots');
      if (!dotsWrap) return;
      var html = '';
      if (info && info.receita) html += '<span class="fp-cal-dot" style="background:var(--inc)"></span>';
      if (info && info.despesa) html += '<span class="fp-cal-dot" style="background:var(--exp)"></span>';
      dotsWrap.innerHTML = html;
    });
  }

  function renderLista(lancamentos) {
    lista.innerHTML = '';
    if (!lancamentos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">Nenhum lançamento nesse dia.</div>';
      return;
    }

    lancamentos.forEach(function (l) {
      var cat = CATS[l.categoria] || { nome: l.categoria, cor: 'var(--muted)' };
      var tipoCor = l.tipo === 'receita' ? 'var(--inc)' : 'var(--exp)';
      var aberto = !!lancColapsados[l.id];

      var card = document.createElement('div');
      card.className = 'fp-lanc-card';
      card.style.borderLeft = '3px solid ' + tipoCor;

      var header = document.createElement('div');
      header.className = 'fp-lanc-header';
      header.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + (l.origem === 'ocr' ? ' <span class="fp-chip fp-chip-muted" style="margin-left:4px">OCR</span>' : '') + '</div>' +
          '<div class="fp-faint fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
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

      var catChipsHtml = Object.keys(CATS).map(function (k) {
        var ativo = k === l.categoria;
        return '<span class="fp-cat-chip' + (ativo ? ' active' : '') + '" data-id="' + l.id + '" data-cat="' + k + '" role="button" tabindex="0">' +
          '<span class="fp-cat-chip-dot" style="background:' + CATS[k].cor + '"></span>' + escapeHtml(CATS[k].nome) +
          '<button type="button" class="fp-cat-chip-edit" data-id="' + l.id + '" data-cat="' + k + '" title="Editar categoria" aria-label="Editar categoria ' + escapeHtml(CATS[k].nome) + '">✎</button>' +
        '</span>';
      }).join('');

      var corpo = document.createElement('div');
      corpo.className = 'fp-lanc-corpo' + (aberto ? ' show' : '');
      corpo.innerHTML =
        '<div style="padding-top:10px;border-top:1px solid var(--line)">' +
          '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-edit" data-id="' + l.id + '">Editar</button>' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-del" data-id="' + l.id + '" style="color:var(--exp)">Excluir</button>' +
          '</div>' +
          '<div class="fp-faint" style="font-size:.74rem;margin-top:12px;margin-bottom:6px">Categoria</div>' +
          '<div style="display:flex;gap:6px;flex-wrap:wrap">' + catChipsHtml + '</div>' +
          '<div class="fp-cat-edit-form" data-id="' + l.id + '" style="display:none;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap">' +
            '<input type="text" class="fp-input fp-cat-edit-nome" placeholder="Nome da categoria" maxlength="40" style="flex:1;min-width:140px;padding:8px 12px;font-size:.85rem">' +
            '<input type="color" class="fp-cat-edit-cor" style="width:38px;height:38px;padding:2px;border-radius:8px;border:1.5px solid var(--line);background:var(--input);cursor:pointer">' +
            '<button type="button" class="fp-btn fp-btn-primary fp-btn-sm fp-cat-edit-salvar">Salvar</button>' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-cat-edit-cancelar">Cancelar</button>' +
            '<span class="fp-cat-edit-msg fp-faint" style="font-size:.78rem;width:100%"></span>' +
          '</div>' +
        '</div>';
      card.appendChild(corpo);

      lista.appendChild(card);
    });

    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-edit').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-cat-chip').forEach(function (btn) {
      btn.onclick = function () {
        if (btn.classList.contains('active')) return;
        trocarCategoria(btn.dataset.id, btn.dataset.cat);
      };
      btn.onkeydown = function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); btn.click(); }
      };
    });
    lista.querySelectorAll('.fp-cat-chip-edit').forEach(function (btn) {
      btn.onclick = function (ev) {
        ev.stopPropagation();
        abrirEdicaoCategoriaInline(btn.dataset.id, btn.dataset.cat);
      };
    });
    lista.querySelectorAll('.fp-cat-edit-cancelar').forEach(function (btn) {
      btn.onclick = function () { btn.closest('.fp-cat-edit-form').style.display = 'none'; };
    });
    lista.querySelectorAll('.fp-cat-edit-salvar').forEach(function (btn) {
      btn.onclick = function () { salvarEdicaoCategoriaInline(btn.closest('.fp-cat-edit-form')); };
    });
  }

  function trocarCategoria(id, novaCategoria) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/atualizar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ tipo: l.tipo, categoria: novaCategoria, descricao: l.descricao, valor: l.valor })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.ok) carregar(); });
  }

  function abrirEdicaoCategoriaInline(lancId, chave) {
    var formEl = lista.querySelector('.fp-cat-edit-form[data-id="' + lancId + '"]');
    var info = CATS[chave];
    if (!formEl || !info) return;
    formEl.dataset.chave = chave;
    formEl.querySelector('.fp-cat-edit-nome').value = info.nome;
    formEl.querySelector('.fp-cat-edit-cor').value = /^#[0-9a-fA-F]{6}$/.test(info.cor) ? info.cor : '#7A6A88';
    formEl.querySelector('.fp-cat-edit-msg').textContent = '';
    formEl.style.display = 'flex';
    formEl.querySelector('.fp-cat-edit-nome').focus();
  }

  function salvarEdicaoCategoriaInline(formEl) {
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
        if (diaSelecionado) selecionarDia(diaSelecionado);
      });
  }

  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    var catSelect = document.getElementById('fpCategoria');
    catSelect.value = l.categoria;
    if (catSelect.selectedIndex === -1 && catSelect.options.length) {
      catSelect.selectedIndex = 0;
    }
    msg.textContent = '';
    abrirModal(modalLancamento);
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    msg.textContent = '';
    fecharModal(modalLancamento);
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
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
        atualizarPontosGrade();
        if (diaSelecionado) selecionarDia(diaSelecionado);
      });
  }

  function excluir(id) {
    if (!confirm('Excluir esse lançamento?')) return;
    fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregar(); });
  }

  // Modal só serve pra EDITAR um lançamento já existente nesta tela (não há "+ Adicionar"
  // aqui) — editandoId sempre vem preenchido por iniciarEdicao() antes do submit chegar.
  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (editandoId === null) return;
    var descricao = document.getElementById('fpDescricao').value.trim();
    var valor = document.getElementById('fpValor').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      msg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    fetch('<?= url('/financeiro-pessoal') ?>/' + editandoId + '/atualizar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        tipo: tipoHidden.value,
        categoria: document.getElementById('fpCategoria').value,
        descricao: descricao,
        valor: valor
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        if (!j.ok) {
          msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        cancelarEdicao();
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');

  // Abre direto no dia de hoje se ele pertencer ao mês sendo exibido — poupa um clique extra
  // no caso comum (olhar o calendário logo depois de mexer em algo hoje).
  <?php if (substr($hojeStr, 0, 7) === $mes): ?>
  selecionarDia(<?= json_encode($hojeStr) ?>);
  <?php endif; ?>

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>

<?php endif; ?>
