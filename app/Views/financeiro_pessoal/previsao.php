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

<?php
// Rótulos dos 4 períodos suportados (PREVISAO_PERIODOS, no controller) — chave bate 1:1 com o
// parâmetro ?periodo= aceito por previsao()/previsaoAjax().
$PERIODOS = [
  '7'   => '7 dias',
  '15'  => '15 dias',
  '30'  => '30 dias',
  'mes' => 'Até o fim do mês',
];
?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Previsão financeira</h1>
    <div class="fp-faint">Saldo estimado a partir do que já está lançado e das contas recorrentes — nunca é saldo confirmado</div>
  </div>
  <a href="<?= url('/financeiro-pessoal') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none">← Voltar</a>
</div>

<div class="fp-periodo-row" role="group" aria-label="Período da previsão">
  <?php foreach ($PERIODOS as $chave => $rotulo): ?>
  <?php $chave = (string) $chave; /* chaves puramente numéricas ('7'/'15'/'30') viram int automaticamente como índice de array no PHP — força de volta pra string antes de comparar com $periodo (sempre string) */ ?>
  <button type="button" class="fp-periodo-btn<?= $periodo === $chave ? ' active' : '' ?>" data-periodo="<?= e($chave) ?>"><?= e($rotulo) ?></button>
  <?php endforeach; ?>
</div>

<div id="fpPrevisaoConteudo">
  <div class="fp-muted" style="text-align:center;padding:30px 0">Calculando previsão…</div>
</div>

<script>
window.FP_PREVISAO_INICIAL = <?= json_encode($projecao) ?>;
window.FP_PREVISAO_URL = <?= json_encode(url('/api/financeiro-pessoal/previsao')) ?>;
</script>

<style>
  .fp-periodo-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
  .fp-periodo-btn{border:1.5px solid var(--line);background:var(--surf);color:var(--muted);border-radius:999px;padding:8px 16px;font-size:.84rem;font-weight:700;cursor:pointer}
  .fp-periodo-btn:hover:not(.active){background:var(--surf2)}
  .fp-periodo-btn.active{background:var(--accent);border-color:var(--accent);color:var(--accentInk)}
  .fp-previsao-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
  .fp-previsao-alerta{display:flex;align-items:flex-start;gap:10px;background:var(--dangerSoft);border:1px solid var(--dangerLine);border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:.88rem;line-height:1.5}
  .fp-previsao-faturas{display:flex;flex-direction:column;gap:8px}
  .fp-previsao-fatura-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:10px}
  .fp-previsao-dia{border:1px solid var(--line);border-radius:10px;padding:10px 14px;margin-bottom:6px;cursor:pointer}
  .fp-previsao-dia:hover{background:var(--surf2)}
  .fp-previsao-dia.negativo{border-color:var(--dangerLine);background:var(--dangerSoft)}
  .fp-previsao-dia-topo{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
  .fp-previsao-dia-itens{margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);display:none;font-size:.82rem}
  .fp-previsao-dia.aberto .fp-previsao-dia-itens{display:block}
  .fp-previsao-item-linha{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:4px 0}
  .fp-previsao-badge-simulado{font-size:.66rem;font-weight:700;color:var(--muted);background:var(--surf2);border-radius:999px;padding:2px 7px;margin-left:6px}
</style>

<script>
(function () {
  var conteudo = document.getElementById('fpPrevisaoConteudo');
  var botoes = document.querySelectorAll('.fp-periodo-btn');
  var periodoAtual = <?= json_encode($periodo) ?>;

  function money(v) {
    var neg = v < 0;
    var s = 'R$ ' + Math.abs(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return neg ? '−' + s : s;
  }

  function dataBr(iso) {
    var p = iso.split('-');
    return p[2] + '/' + p[1];
  }

  var DIAS_SEMANA = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
  function diaSemana(iso) {
    // new Date('YYYY-MM-DD') em JS interpreta como UTC meia-noite — soma 12h de folga pra
    // nunca cair no dia anterior por fuso (mesmo risco que já existe em qualquer parse de
    // data pura neste módulo, resolvido com a mesma técnica simples de "meio-dia").
    var d = new Date(iso + 'T12:00:00');
    return DIAS_SEMANA[d.getDay()];
  }

  function humanizarCategoria(chave) {
    return (chave || '').replace(/[_-]/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  function renderizar(p) {
    if (!p) {
      conteudo.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;padding:30px 0">Não deu pra calcular a previsão agora. Tente recarregar a página.</div>';
      return;
    }

    var html = '';

    if (p.incompleta) {
      html += '<div class="fp-card fp-muted" style="text-align:center;padding:30px 24px;margin-bottom:16px">'
        + 'Você ainda não tem lançamentos nesse perfil pra calcular uma previsão. '
        + 'Cadastre receitas, despesas ou contas recorrentes em <a href="<?= url('/financeiro-pessoal/lancamentos') ?>">Lançamentos</a> '
        + 'pra começar a ver a projeção aqui.</div>';
    }

    // KPIs
    var saldoFinalCor = p.saldoFinal < 0 ? 'var(--exp)' : 'var(--text)';
    html += '<div class="fp-previsao-kpis">'
      + '<div class="fp-card"><div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo atual</div>'
      + '<div class="fp-mono" style="font-weight:700;font-size:1.4rem">' + money(p.saldoAtual) + '</div></div>'
      + '<div class="fp-card"><div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo estimado ao final do período</div>'
      + '<div class="fp-mono" style="font-weight:700;font-size:1.4rem;color:' + saldoFinalCor + '">' + money(p.saldoFinal) + '</div></div>'
      + '<div class="fp-card"><div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Dias com risco de saldo negativo</div>'
      + '<div class="fp-mono" style="font-weight:700;font-size:1.4rem;color:' + (p.diasNegativos.length ? 'var(--exp)' : 'var(--text)') + '">' + p.diasNegativos.length + '</div></div>'
      + '</div>';

    // Alerta de saldo negativo
    if (p.diasNegativos.length) {
      html += '<div class="fp-previsao-alerta"><span>⚠️</span><div>'
        + '<b>Seu saldo pode ficar negativo</b> a partir de ' + dataBr(p.diasNegativos[0]) + '. '
        + 'São ' + p.diasNegativos.length + ' dia(s) nessa situação dentro do período — isso é uma projeção com o que já está lançado, '
        + 'não quer dizer que vai acontecer de verdade, mas vale revisar os compromissos desse(s) dia(s) abaixo.'
        + '</div></div>';
    }

    // Próximas faturas (versão simples — agrupado por forma de pagamento tipo cartão + vencimento)
    if (p.proximasFaturas && p.proximasFaturas.length) {
      html += '<div class="fp-card" style="margin-bottom:16px"><div class="fp-muted" style="font-size:.8rem;margin-bottom:10px">Próximas faturas</div><div class="fp-previsao-faturas">';
      p.proximasFaturas.forEach(function (f) {
        html += '<div class="fp-previsao-fatura-item">'
          + '<div><b>' + esc(f.forma_nome) + '</b><div class="fp-faint" style="font-size:.78rem">vence ' + dataBr(f.vencimento) + ' · ' + f.qtd + ' lançamento(s)</div></div>'
          + '<div class="fp-mono" style="font-weight:700;color:var(--exp)">' + money(f.total) + '</div>'
          + '</div>';
      });
      html += '</div></div>';
    }

    // Dia a dia
    html += '<div class="fp-card"><div class="fp-muted" style="font-size:.8rem;margin-bottom:10px">Dia a dia do período</div>';
    p.dias.forEach(function (d) {
      var temMovimento = d.receitas > 0 || d.despesas > 0;
      html += '<div class="fp-previsao-dia' + (d.negativo ? ' negativo' : '') + '" data-dia="' + d.data + '">'
        + '<div class="fp-previsao-dia-topo">'
        + '<div><b class="fp-mono">' + dataBr(d.data) + '</b> <span class="fp-faint">' + diaSemana(d.data) + '</span></div>';
      if (temMovimento) {
        html += '<div style="display:flex;gap:14px;align-items:center">';
        if (d.receitas > 0) html += '<span class="fp-mono" style="color:var(--inc);font-size:.84rem">+' + money(d.receitas) + '</span>';
        if (d.despesas > 0) html += '<span class="fp-mono" style="color:var(--exp);font-size:.84rem">−' + money(d.despesas) + '</span>';
        html += '<span class="fp-mono" style="font-weight:700;color:' + (d.negativo ? 'var(--exp)' : 'var(--text)') + '">' + money(d.saldo) + '</span>'
          + '</div>';
      } else {
        html += '<span class="fp-mono fp-faint" style="font-size:.84rem">' + money(d.saldo) + '</span>';
      }
      html += '</div>';
      if (d.itens.length) {
        html += '<div class="fp-previsao-dia-itens">';
        d.itens.forEach(function (it) {
          var cor = it.tipo === 'receita' ? 'var(--inc)' : 'var(--exp)';
          var sinal = it.tipo === 'receita' ? '+' : '−';
          html += '<div class="fp-previsao-item-linha">'
            + '<div>' + esc(it.descricao) + ' <span class="fp-faint">· ' + esc(humanizarCategoria(it.categoria)) + '</span>'
            + (it.origem === 'simulado' ? '<span class="fp-previsao-badge-simulado" title="Ocorrência futura de uma conta recorrente, ainda não lançada — calculada, não gravada">estimado</span>' : '')
            + (it.vencido ? '<span class="fp-previsao-badge-simulado" style="color:var(--exp);background:var(--expSoft)">vencido</span>' : '')
            + '</div>'
            + '<span class="fp-mono" style="color:' + cor + '">' + sinal + money(it.valor) + '</span>'
            + '</div>';
        });
        html += '</div>';
      }
      html += '</div>';
    });
    html += '</div>';

    conteudo.innerHTML = html;

    // Clique no dia expande/recolhe o detalhamento (só se tiver item — dia vazio não reage).
    conteudo.querySelectorAll('.fp-previsao-dia').forEach(function (el) {
      if (!el.querySelector('.fp-previsao-dia-itens')) return;
      el.addEventListener('click', function () { el.classList.toggle('aberto'); });
    });
  }

  function carregarPeriodo(periodo) {
    botoes.forEach(function (b) { b.classList.toggle('active', b.dataset.periodo === periodo); });
    conteudo.innerHTML = '<div class="fp-muted" style="text-align:center;padding:30px 0">Calculando previsão…</div>';
    fetch(window.FP_PREVISAO_URL + '?periodo=' + encodeURIComponent(periodo))
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.ok) { renderizar(null); return; }
        periodoAtual = res.periodo;
        renderizar(res.projecao);
      })
      .catch(function () { renderizar(null); });
  }

  botoes.forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.dataset.periodo === periodoAtual) return;
      carregarPeriodo(b.dataset.periodo);
    });
  });

  renderizar(window.FP_PREVISAO_INICIAL);
})();
</script>

<?php endif; ?>
