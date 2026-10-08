<?php
// Custo é guardado em CENTAVOS fracionários (DECIMAL(10,4) — ver ia_uso_log/IAUsoService),
// de propósito, pra uma leitura de Haiku (fração de centavo) não virar R$0,00 arredondando.
// Exibido aqui com 4 casas decimais, não as 2 de money() — é exatamente o detalhe que esta
// tela existe pra mostrar.
$ciFmt = function ($centavos): string {
    return 'R$ ' . number_format(((float) $centavos) / 100, 4, ',', '.');
};
$ciMesAnterior = date('Y-m', strtotime($mes . '-01 -1 month'));
$ciMesSeguinte = date('Y-m', strtotime($mes . '-01 +1 month'));
$ciMesAtualAlcancado = $ciMesSeguinte > date('Y-m');
?>
<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-cpu me-2 text-primary"></i>Custo de IA</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Custo real estimado de toda chamada à Anthropic logada pelo sistema (<code>ia_uso_log</code>)
    — scanner de contas do Carteira Fixa, leitura de etiqueta de equipamento, leitura de placa, e qualquer
    outro uso que passe por <code>IAService::perguntar()</code>/<code>VisionService</code>.
    Calculado a partir do <code>usage</code> (tokens de entrada/saída) real devolvido pela
    Anthropic em cada resposta, nunca um valor fixo "por leitura" — preços por modelo em
    <code>config/ia_precos.php</code>.
  </p>

  <form method="GET" action="<?= url('/master/custo-ia') ?>" class="d-flex align-items-center gap-2 mb-4">
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/master/custo-ia?mes=' . $ciMesAnterior) ?>">
      <i class="bi bi-chevron-left"></i>
    </a>
    <input type="month" name="mes" value="<?= e($mes) ?>" class="form-control form-control-sm" style="max-width:160px" onchange="this.form.submit()">
    <?php if (!$ciMesAtualAlcancado): ?>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/master/custo-ia?mes=' . $ciMesSeguinte) ?>">
      <i class="bi bi-chevron-right"></i>
    </a>
    <?php endif; ?>
    <noscript><button class="btn btn-sm btn-primary">Ver</button></noscript>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Chamadas no mês</div>
        <div class="fs-3 fw-bold"><?= (int) $resumo['total']['qtd'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Tokens de entrada</div>
        <div class="fs-4 fw-bold"><?= number_format((int) $resumo['total']['te'], 0, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Tokens de saída</div>
        <div class="fs-4 fw-bold"><?= number_format((int) $resumo['total']['ts'], 0, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-cash-coin me-1"></i>Custo estimado no mês</div>
        <div class="fs-3 fw-bold text-success"><?= $ciFmt($resumo['total']['custo']) ?></div>
      </div></div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Por modelo -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-box me-1 text-primary"></i>Por modelo</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr><th>Modelo</th><th class="text-end">Chamadas</th><th class="text-end">Tok. entrada</th><th class="text-end">Tok. saída</th><th class="text-end">Custo</th></tr>
            </thead>
            <tbody>
              <?php if (!$resumo['por_modelo']): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">Sem chamadas registradas neste mês.</td></tr>
              <?php endif; ?>
              <?php foreach ($resumo['por_modelo'] as $m): ?>
              <tr>
                <td><code><?= e($m['modelo']) ?></code></td>
                <td class="text-end"><?= (int) $m['qtd'] ?></td>
                <td class="text-end"><?= number_format((int) $m['te'], 0, ',', '.') ?></td>
                <td class="text-end"><?= number_format((int) $m['ts'], 0, ',', '.') ?></td>
                <td class="text-end fw-semibold"><?= $ciFmt($m['custo']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Por usuário -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-1 text-primary"></i>Por usuário (top 50)</div>
        <div class="table-responsive" style="max-height:420px">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light" style="position:sticky;top:0">
              <tr><th>Usuário</th><th class="text-end">Chamadas</th><th class="text-end">Custo</th></tr>
            </thead>
            <tbody>
              <?php if (!$resumo['por_usuario']): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">Sem chamadas registradas neste mês.</td></tr>
              <?php endif; ?>
              <?php foreach ($resumo['por_usuario'] as $u): ?>
              <tr>
                <td><?= e($u['nome'] ?: ('Usuário #' . (int) $u['usuario_id'])) ?></td>
                <td class="text-end"><?= (int) $u['qtd'] ?></td>
                <td class="text-end fw-semibold"><?= $ciFmt($u['custo']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
