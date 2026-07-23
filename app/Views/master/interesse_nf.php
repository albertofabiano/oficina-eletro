<div class="container-fluid" style="max-width:900px">
  <h4 class="mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Interesse em Nota Fiscal</h4>
  <p class="text-muted small">Medidor de demanda: empresas que pediram emissão de NFS-e. Use a coluna <strong>por cidade</strong> pra decidir quando (e onde) vale ligar o módulo fiscal.</p>

  <div class="row g-3">
    <div class="col-md-5">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Demanda por cidade</div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Cidade</th><th>UF</th><th class="text-end">Empresas</th></tr></thead>
            <tbody>
              <?php foreach ($porCidade as $c): ?>
              <tr>
                <td><?= e($c['cidade']) ?></td>
                <td><?= e($c['uf']) ?></td>
                <td class="text-end"><span class="badge bg-primary"><?= (int) $c['qtd'] ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$porCidade): ?><tr><td colspan="3" class="text-center text-muted py-3">Ninguém demonstrou interesse ainda.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Empresas interessadas (<?= count($lista) ?>)</div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Empresa</th><th>Cidade/UF</th><th>Plano</th><th>Desde</th></tr></thead>
            <tbody>
              <?php foreach ($lista as $l): ?>
              <tr>
                <td><?= e($l['nome_fantasia'] ?: ('#'.$l['id'])) ?></td>
                <td class="small"><?= e(trim(($l['cidade'] ?? '').'/'.($l['uf'] ?? ''), '/')) ?: '—' ?></td>
                <td class="small"><?= e($l['plano_atual'] ?: '—') ?></td>
                <td class="small text-muted"><?= date_br($l['criado_em']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$lista): ?><tr><td colspan="4" class="text-center text-muted py-3">Nenhuma empresa ainda.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
