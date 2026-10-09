<?php
// Cor do badge de status efetivo — mesma paleta de risco já usada no resto do Master
// (sucesso/aviso/perigo/neutro), sem inventar uma quarta classe nova.
$cfStatusBadge = [
    'teste'        => 'bg-info text-dark',
    'ativa'        => 'bg-success',
    'inadimplente' => 'bg-warning text-dark',
    'bloqueada'    => 'bg-danger',
    'cancelada'    => 'bg-secondary',
];
$cfStatusLabel = [
    'teste'        => 'Teste',
    'ativa'        => 'Ativa',
    'inadimplente' => 'Inadimplente',
    'bloqueada'    => 'Bloqueada',
    'cancelada'    => 'Cancelada',
];
?>
<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-wallet2 me-2 text-warning"></i>Carteira Fixa — Controle e Usuários</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Os dois caminhos de acesso ao módulo (ver <code>financeiro_pessoal_liberado()</code>):
    empresa que já paga o FixaOS completo (plano autônomo/oficina/empresa) ganha Carteira Fixa
    de graça; quem não tem empresa nenhuma paga a assinatura standalone diretamente
    (<code>fixa_assinaturas</code>). As contagens de cada grupo ficam no rodapé desta página.
  </p>

  <!-- ── Assinaturas individuais (pagantes standalone) ───────────────────── -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-person-badge me-1 text-warning"></i>Assinaturas individuais — <?= count($assinaturas) ?> no total
    </div>
    <div class="table-responsive" style="max-height:460px">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light" style="position:sticky;top:0">
          <tr>
            <th>Usuário</th><th>E-mail</th><th>Plano</th><th>Ciclo</th><th>Status</th>
            <th>Vencimento</th><th>Valor</th><th style="width:110px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$assinaturas): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma assinatura individual ainda.</td></tr>
          <?php endif; ?>
          <?php foreach ($assinaturas as $a):
            $venc = $a['status_efetivo'] === 'teste' ? ($a['teste_fim'] ?? null) : ($a['data_fim'] ?? null);
          ?>
          <tr>
            <td><?= e($a['usuario_nome']) ?></td>
            <td><?= e($a['usuario_email']) ?></td>
            <td><?= e($a['plano_nome']) ?></td>
            <td class="text-capitalize"><?= e($a['ciclo']) ?></td>
            <td>
              <span class="badge <?= $cfStatusBadge[$a['status_efetivo']] ?? 'bg-secondary' ?>">
                <?= e($cfStatusLabel[$a['status_efetivo']] ?? $a['status_efetivo']) ?>
              </span>
            </td>
            <td><?= $venc ? date_br($venc) : '—' ?></td>
            <td>R$ <?= number_format(((int) $a['valor_centavos']) / 100, 2, ',', '.') ?></td>
            <td>
              <?php if ($a['status_efetivo'] !== 'cancelada'): ?>
              <form method="POST" action="<?= url('/master/carteira-fixa') ?>/<?= (int) $a['id'] ?>/cancelar"
                    onsubmit="return confirm('Cancelar esta assinatura? O crédito proporcional (se houver) é preservado.');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Empresas com acesso via plano FixaOS ────────────────────────────── -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-building-check me-1 text-primary"></i>Empresas com Carteira Fixa de graça (plano FixaOS) — <?= count($empresasPlano) ?> no total
    </div>
    <div class="table-responsive" style="max-height:320px">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light" style="position:sticky;top:0">
          <tr><th>Empresa</th><th>Plano</th></tr>
        </thead>
        <tbody>
          <?php if (!$empresasPlano): ?>
          <tr><td colspan="2" class="text-center text-muted py-4">Nenhuma empresa nessa condição ainda.</td></tr>
          <?php endif; ?>
          <?php foreach ($empresasPlano as $emp): ?>
          <tr>
            <td><?= e($emp['nome_fantasia'] ?: $emp['razao_social']) ?></td>
            <td class="text-capitalize"><?= e($emp['plano_atual']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white text-muted small">
      Edição de plano/reivindicação continua em <a href="<?= url('/master/empresas') ?>">Empresas</a> — esta lista é só leitura.
    </div>
  </div>

  <!-- ── Contagem ─────────────────────────────────────────────────────────── -->
  <div class="row g-3">
    <div class="col-6 col-md-4">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-building-check me-1"></i>Pagantes de plano FixaOS</div>
        <div class="fs-2 fw-bold text-primary"><?= (int) $pagantesPlanoFixaOS ?></div>
        <div class="text-muted small">empresa(s) com Carteira Fixa de graça pelo plano</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-person-badge me-1"></i>Pagantes individuais</div>
        <div class="fs-2 fw-bold text-warning"><?= (int) $pagantesIndividuais ?></div>
        <div class="text-muted small">assinatura(s) standalone ativa(s) agora</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-people-fill me-1"></i>Total de pagantes</div>
        <div class="fs-2 fw-bold"><?= (int) $pagantesPlanoFixaOS + (int) $pagantesIndividuais ?></div>
        <div class="text-muted small">somando os dois caminhos</div>
      </div></div>
    </div>
  </div>
</div>
