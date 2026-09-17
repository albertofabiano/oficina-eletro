<?php
$semSaldo = $qtd >= $limite;
?>

<style>
.diretprod-credito-box { background:linear-gradient(135deg,#1a1d23 0%,#212529 100%); border-radius:14px; }
.diretprod-status-ativo   { background:#d1fae5; color:#065f46; }
.diretprod-status-vendido { background:#fee2e2; color:#991b1b; }
</style>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-shop-window me-2 text-primary"></i>Produtos no Diretório</h5>
    <small class="text-muted">Vitrine de até <?= $limite ?> produtos na sua página pública do Diretório</small>
  </div>
  <a href="<?= url('/empresa/perfil-publico') ?>" class="btn btn-outline-secondary btn-sm ms-auto">
    <i class="bi bi-arrow-left me-1"></i>Voltar ao Perfil Público
  </a>
</div>

<?php if (!$planoCompleto): ?>
<div class="alert alert-warning d-flex gap-2 mb-4">
  <i class="bi bi-lock-fill fs-5 flex-shrink-0"></i>
  <div>
    <strong>Recurso exclusivo de plano pago ativo.</strong> Cadastrar produtos na vitrine do Diretório
    faz parte dos planos pagos da FixaOS — o teste grátis não libera este recurso.
    <div class="mt-2"><a href="<?= url('/planos') ?>" class="btn btn-sm btn-warning fw-semibold">Ver planos</a></div>
  </div>
</div>
<?php endif; ?>

<!-- Cabeçalho com vagas -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="diretprod-credito-box text-white p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-white-50 small mb-1">Vagas usadas</div>
          <div class="display-5 fw-bold"><?= $qtd ?><span class="fs-4 text-white-50">/<?= $limite ?></span></div>
          <div class="text-white-50 small">produto<?= $qtd !== 1 ? 's' : '' ?> na vitrine</div>
        </div>
        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
             style="width:48px;height:48px">
          <i class="bi bi-box-seam fs-4 text-white"></i>
        </div>
      </div>
      <div class="mt-3 pt-3 border-top border-secondary">
        <small class="text-white-50">Produto "vendido" continua ocupando a vaga até ser excluído</small><br>
        <small class="text-white-50">Aparece só com plano pago ativo</small>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="alert alert-info mb-0 h-100 d-flex align-items-center" style="font-size:.9rem">
      <div>
        <i class="bi bi-lightbulb-fill me-1" style="color:#f59e0b"></i>
        <strong>ATENÇÃO:</strong> procure anunciar produtos que representem sua empresa —
        capinha de celular, celular usado ou novo, peças em promoção, TV usada, ou qualquer
        outro produto que identifique o que você vende. Não é um marketplace geral, é uma
        vitrine da SUA empresa no Diretório.
      </div>
    </div>
  </div>
</div>

<!-- Ações -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div></div>
  <div>
    <?php if ($planoCompleto && !$semSaldo): ?>
    <button class="btn btn-success btn-sm fw-semibold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProdutoDiretorio">
      <i class="bi bi-plus-lg me-1"></i>Cadastrar produto
    </button>
    <?php elseif ($planoCompleto): ?>
    <button class="btn btn-secondary btn-sm" disabled title="Limite de vagas atingido">
      <i class="bi bi-lock me-1"></i>Cadastrar produto (limite atingido)
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Lista de produtos -->
<?php if (!$produtos): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-shop-window fs-1 d-block mb-3 opacity-30"></i>
  <h5>Nenhum produto cadastrado ainda</h5>
  <?php if ($planoCompleto): ?>
  <p>Cadastre até <?= $limite ?> produtos e eles aparecem na sua página pública do Diretório.</p>
  <button class="btn btn-success mt-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProdutoDiretorio">
    <i class="bi bi-plus-lg me-1"></i>Cadastrar agora
  </button>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Produto</th><th>Valor</th><th>Status</th><th>Data</th><th class="text-end">Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $item): ?>
        <tr>
          <td class="d-flex align-items-center gap-2">
            <?php if ($item['imagem_principal']): ?>
            <img src="<?= url('/uploads/diretorio-produtos/' . e($item['imagem_principal'])) ?>"
                 alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px">
            <?php endif; ?>
            <span class="fw-semibold"><?= e($item['titulo']) ?></span>
          </td>
          <td class="fw-semibold text-success"><?= money($item['valor']) ?></td>
          <td>
            <span class="badge rounded-pill diretprod-status-<?= $item['status'] ?>">
              <?= $item['status'] === 'vendido' ? 'Vendido' : 'Ativo' ?>
            </span>
          </td>
          <td class="text-muted"><?= date_br($item['criado_em']) ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1 justify-content-end">
              <a href="<?= url('/empresa/produtos-diretorio/' . $item['id'] . '/editar') ?>"
                 class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Editar
              </a>
              <button class="btn btn-sm btn-outline-secondary"
                onclick="alternarVendidoProdDiretorio(<?= $item['id'] ?>)"
                title="<?= $item['status']==='ativo' ? 'Marcar como vendido' : 'Marcar como ativo' ?>">
                <i class="bi bi-<?= $item['status']==='ativo' ? 'check2-circle' : 'arrow-counterclockwise' ?>"></i>
              </button>
              <a href="#" class="btn btn-sm btn-outline-danger"
                 data-method="DELETE"
                 data-href="<?= url('/empresa/produtos-diretorio/' . $item['id']) ?>"
                 data-confirm="Excluir este produto da vitrine?">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Offcanvas: Novo Produto -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProdutoDiretorio" style="width:420px">
  <div class="offcanvas-header border-bottom">
    <div>
      <h5 class="offcanvas-title fw-bold mb-0">
        <i class="bi bi-box-seam me-2 text-success"></i>Cadastrar Produto
      </h5>
      <p class="text-muted small mb-0">Vagas: <strong><?= $qtd ?>/<?= $limite ?></strong></p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form method="POST" action="<?= url('/empresa/produtos-diretorio') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="produto_id" value="<?= e($prefill['produto_id'] ?? '') ?>">

      <?php if ($prefill): ?>
      <div class="alert alert-success d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-box-seam flex-shrink-0 mt-1"></i>
        <div>Preenchido a partir do produto do seu estoque.</div>
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Título *</label>
        <input type="text" name="titulo" class="form-control" required maxlength="120"
          value="<?= e($prefill['titulo'] ?? '') ?>" placeholder="Ex: iPhone 12 seminovo 128GB">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Valor (R$) *</label>
        <div class="input-group">
          <span class="input-group-text">R$</span>
          <input type="text" name="valor" class="form-control" required
            value="<?= $prefill && $prefill['valor'] > 0 ? number_format((float) $prefill['valor'], 2, ',', '.') : '' ?>" placeholder="0,00">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"
          placeholder="Condição, garantia, forma de pagamento..."><?= e($prefill['descricao'] ?? '') ?></textarea>
      </div>

      <div class="alert alert-warning d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-lightbulb-fill flex-shrink-0 mt-1" style="color:#f59e0b"></i>
        <div>
          Fotografe em local bem iluminado, de preferência com fundo claro. O sistema
          redimensiona e converte automaticamente — pode enviar qualquer tamanho!
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          <i class="bi bi-image me-1 text-primary"></i>Foto principal
        </label>
        <input type="file" name="imagem_principal" class="form-control"
          accept="image/*" capture="environment" id="inputImgPrincipalDP"
          onchange="previewImgDP(this,'prevMainDP')">
        <div class="form-text">
          <i class="bi bi-magic me-1"></i>Convertida automaticamente para <strong>800×800px WebP</strong> com fundo branco.
        </div>
        <img id="prevMainDP" src="" class="img-fluid rounded mt-2 d-none" style="max-height:180px;object-fit:cover">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          <i class="bi bi-images me-1 text-primary"></i>Galeria de fotos (até 2 — total 3 com a principal)
        </label>
        <input type="file" name="galeria[]" class="form-control" multiple
          accept="image/*" id="inputGaleriaDP"
          onchange="previewGaleriaDP(this)">
        <div class="form-text">Selecione até 2 fotos adicionais</div>
        <div id="prevGaleriaDP" class="d-flex gap-2 mt-2 flex-wrap"></div>
      </div>

      <button type="submit" class="btn btn-success w-100 fw-semibold">
        <i class="bi bi-check-lg me-1"></i>Cadastrar no Diretório
      </button>
    </form>
  </div>
</div>

<script>
<?php if ($prefill): ?>
document.addEventListener('DOMContentLoaded', function () {
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasProdutoDiretorio')).show();
});
<?php endif; ?>

function previewImgDP(input, previewId) {
  const img = document.getElementById(previewId);
  if (!input.files || !input.files[0]) { img.classList.add('d-none'); return; }
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  reader.readAsDataURL(input.files[0]);
}

function previewGaleriaDP(input) {
  const box = document.getElementById('prevGaleriaDP');
  box.innerHTML = '';
  const files = Array.from(input.files).slice(0, 2);
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6';
      box.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

async function alternarVendidoProdDiretorio(id) {
  const r = await fetch(`<?= url('/empresa/produtos-diretorio/') ?>${id}/vender`, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({})
  });
  if ((await r.json()).success) location.reload();
}
</script>
