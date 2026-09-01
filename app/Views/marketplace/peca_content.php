<?php
// View de conteúdo — usada dentro do layout main (usuário logado)
$imgPrincipal = $peca['imagem_principal'] ?? null;
$galeria      = !empty($peca['imagens_galeria']) ? json_decode($peca['imagens_galeria'], true) : [];
$todasImagens = array_filter(array_merge($imgPrincipal ? [$imgPrincipal] : [], $galeria));
$logado       = \App\Core\Auth::check();
?>

<style>
.img-principal-wrap { position:relative;overflow:hidden;border-radius:12px;background:#f8f9fa;cursor:zoom-in;aspect-ratio:4/3;border:1px solid #e9ecef; }
.img-principal-wrap img { width:100%;height:100%;object-fit:contain;transition:transform .4s ease; }
.img-principal-wrap:hover img { transform:scale(1.6); }
.galeria-thumbs { display:flex;gap:8px;margin-top:10px; }
.thumb { width:80px;height:80px;border-radius:8px;overflow:hidden;border:2px solid #dee2e6;cursor:pointer;flex-shrink:0;transition:.15s; }
.thumb:hover,.thumb.active { border-color:#0d6efd; }
.thumb img { width:100%;height:100%;object-fit:cover; }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/pecas">Marketplace</a></li>
    <?php if ($peca['tipo']): ?>
    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/pecas?tipo=<?= urlencode($peca['tipo']) ?>"><?= e($peca['tipo']) ?></a></li>
    <?php endif; ?>
    <li class="breadcrumb-item active"><?= e(mb_substr($peca['titulo'], 0, 40)) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <!-- Galeria -->
  <div class="col-lg-5">
    <?php if ($todasImagens): ?>
    <div class="img-principal-wrap mb-2" id="imgWrap">
      <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars(reset($todasImagens), ENT_QUOTES, 'UTF-8') ?>"
           id="imgPrincipal" alt="<?= e($peca['titulo']) ?>">
    </div>
    <?php if (count($todasImagens) > 1): ?>
    <div class="galeria-thumbs">
      <?php foreach ($todasImagens as $i => $img): ?>
      <div class="thumb <?= $i===0?'active':'' ?>" onclick="irParaImagem(<?= $i ?>); iniciarCarrossel();">
        <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="Foto <?= $i+1 ?>">
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="aspect-ratio:4/3">
      <i class="bi bi-image fs-1 text-muted opacity-30"></i>
    </div>
    <?php endif; ?>
  </div>

  <!-- Detalhes -->
  <div class="col-lg-7">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <?php if ($peca['tipo']): ?>
      <span class="badge rounded-pill" style="background:#e8f0fe;color:#1a56db"><?= e($peca['tipo']) ?></span>
      <?php endif; ?>
      <?php if ($peca['marca']): ?>
      <span class="badge bg-light text-dark border"><?= e($peca['marca']) ?></span>
      <?php endif; ?>
    </div>

    <h4 class="fw-bold mb-1"><?= e($peca['titulo']) ?></h4>

    <?php if ($peca['modelo']): ?>
    <p class="text-muted mb-3">Modelo: <strong><?= e($peca['modelo']) ?></strong></p>
    <?php endif; ?>

    <?php if ($peca['codigo_interno']): ?>
    <p class="text-muted small mb-3">Código da peça/placa: <code><?= e($peca['codigo_interno']) ?></code></p>
    <?php endif; ?>

    <div class="display-6 fw-bold text-success mb-3">
      R$ <?= number_format($peca['valor'], 2, ',', '.') ?>
    </div>

    <?php if ($peca['produto_id'] && $peca['produto_estoque'] !== null): ?>
    <p class="mb-3">
      <?php if ((float) $peca['produto_estoque'] > 0): ?>
      <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-box-seam me-1"></i><?= (int) $peca['produto_estoque'] ?> em estoque</span>
      <?php else: ?>
      <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-box-seam me-1"></i>Sem estoque no momento</span>
      <?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if ($peca['descricao']): ?>
    <div class="mb-4 text-muted" style="white-space:pre-wrap"><?= e($peca['descricao']) ?></div>
    <?php endif; ?>

    <!-- Vendedor — visível para logados -->
    <?php
    $endereco = array_filter([
        $peca['empresa_logradouro'] ?? '',
        $peca['empresa_numero']     ?? '',
        $peca['empresa_bairro']     ?? '',
        ($peca['empresa_cidade'] ?? '') . ($peca['empresa_uf'] ? '/' . $peca['empresa_uf'] : ''),
    ]);
    $enderecoStr = implode(', ', $endereco);
    $wa  = only_numbers($peca['empresa_whatsapp'] ?? '');
    $tel = only_numbers($peca['empresa_telefone'] ?? '');
    $linkPeca = url('/pecas/' . ($peca['slug'] ?? $peca['id']));
    $msgWa = urlencode('Olá! Vi seu anúncio no FixaOS e tenho interesse:' . "\n\n" . '📦 *' . $peca['titulo'] . '*' . "\n" . '💰 R$ ' . number_format($peca['valor'],2,',','.') . "\n" . '🔗 ' . $linkPeca . "\n\n" . 'Ainda disponível?');
    ?>
    <div class="card shadow-sm mb-3" style="border:1.5px solid #cbd5e1 !important">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shop me-2 text-primary"></i><?= e($peca['empresa_nome']) ?></span>
        <a href="<?= url('/pecas?empresa=' . (int) $peca['empresa_id_vendedor']) ?>" class="small text-decoration-none"
           title="Ver todos os anúncios de <?= e($peca['empresa_nome']) ?>">
          <i class="bi bi-grid-3x3-gap"></i> Ver todos
        </a>
      </div>
      <div class="card-body">

        <!-- Endereço -->
        <?php if ($enderecoStr): ?>
        <div class="d-flex align-items-start gap-2 mb-2 small text-muted">
          <i class="bi bi-geo-alt-fill text-danger mt-1 flex-shrink-0"></i>
          <div>
            <?= e($enderecoStr) ?>
            <?php if ($peca['empresa_cidade']): ?>
            <br>
            <a href="https://maps.google.com/?q=<?= urlencode($enderecoStr) ?>"
               target="_blank" class="text-primary text-decoration-none">
              <i class="bi bi-map me-1"></i>Ver no mapa
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php elseif ($peca['empresa_cidade']): ?>
        <div class="d-flex align-items-center gap-2 mb-2 small text-muted">
          <i class="bi bi-geo-alt-fill text-danger"></i>
          <?= e($peca['empresa_cidade']) ?>/<?= e($peca['empresa_uf']) ?>
        </div>
        <?php endif; ?>

        <!-- Telefone -->
        <?php if ($tel): ?>
        <div class="d-flex align-items-center gap-2 mb-3 small">
          <i class="bi bi-telephone-fill text-primary"></i>
          <a href="tel:+55<?= $tel ?>" class="text-dark fw-semibold text-decoration-none">
            <?= e($peca['empresa_telefone']) ?>
          </a>
        </div>
        <?php endif; ?>

        <!-- Botões de contato -->
        <div class="d-flex flex-column gap-2">
          <?php if ($wa): ?>
          <a href="https://wa.me/55<?= $wa ?>?text=<?= $msgWa ?>"
             target="_blank"
             class="btn btn-success btn-sm fw-semibold" style="width:fit-content">
            <i class="bi bi-whatsapp me-1"></i>Chamar no WhatsApp
          </a>
          <?php endif; ?>
          <?php if ($tel && $tel !== $wa): ?>
          <a href="tel:+55<?= $tel ?>"
             class="btn btn-outline-primary fw-semibold">
            <i class="bi bi-telephone me-2"></i>Ligar agora
          </a>
          <?php endif; ?>
          <?php if (!$wa && !$tel): ?>
          <div class="alert alert-warning py-2 small mb-0">
            <i class="bi bi-info-circle me-1"></i>Contato não informado pelo vendedor.
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <a href="<?= $baseUrl ?>/pecas" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Voltar ao marketplace
    </a>
    <?php if (\App\Core\Auth::empresaId() == ($peca['empresa_id_vendedor'] ?? 0)): ?>
    <a href="<?= $baseUrl ?>/pecas/<?= $peca['id'] ?>" target="_blank"
       class="btn btn-outline-primary btn-sm fw-semibold">
      <i class="bi bi-eye me-1"></i>Ver anúncio público
    </a>
    <a href="<?= url('/marketplace/anuncios/' . $peca['id'] . '/editar') ?>"
       class="btn btn-warning btn-sm fw-semibold">
      <i class="bi bi-pencil-square me-1"></i>Editar anúncio
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Relacionadas -->
<?php if ($relacionadas): ?>
<hr class="my-4">
<h6 class="fw-bold mb-3">Peças relacionadas</h6>
<div class="row g-3">
  <?php foreach ($relacionadas as $r): ?>
  <div class="col-sm-6 col-md-3">
    <a href="<?= $baseUrl ?>/pecas/<?= $r['id'] ?>" class="text-decoration-none text-dark">
      <div class="card border-0 shadow-sm h-100">
        <?php if (!empty($r['imagem_principal'])): ?>
        <img src="<?= $baseUrl ?>/uploads/marketplace/<?= e($r['imagem_principal']) ?>"
             class="card-img-top" style="aspect-ratio:4/3;object-fit:cover" alt="">
        <?php endif; ?>
        <div class="card-body p-2">
          <div class="fw-semibold small"><?= e(mb_substr($r['titulo'], 0, 50)) ?></div>
          <div class="text-success fw-bold small">R$ <?= number_format($r['valor'],2,',','.') ?></div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
<?php if (count($todasImagens) > 1): ?>
// Carrossel automático — troca de foto sozinho a cada 4s; clicar numa miniatura navega na hora
// e reinicia a contagem (senão o autoplay "brigaria" com o clique, avançando de novo logo em
// seguida pra uma foto diferente da que o usuário acabou de escolher).
const carrosselImgs = <?= json_encode(array_values(array_map(
    fn($img) => $baseUrl . '/uploads/marketplace/' . $img,
    $todasImagens
))) ?>;
let carrosselIndice = 0;
let carrosselTimer = null;
function irParaImagem(i) {
  carrosselIndice = ((i % carrosselImgs.length) + carrosselImgs.length) % carrosselImgs.length;
  document.getElementById('imgPrincipal').src = carrosselImgs[carrosselIndice];
  document.querySelectorAll('.thumb').forEach((t, idx) => t.classList.toggle('active', idx === carrosselIndice));
}
function iniciarCarrossel() {
  if (carrosselTimer) clearInterval(carrosselTimer);
  carrosselTimer = setInterval(() => irParaImagem(carrosselIndice + 1), 4000);
}
function pararCarrossel() { if (carrosselTimer) clearInterval(carrosselTimer); }
const imgWrapEl = document.getElementById('imgWrap');
imgWrapEl.addEventListener('mouseenter', pararCarrossel);
imgWrapEl.addEventListener('mouseleave', iniciarCarrossel);
iniciarCarrossel();
<?php endif; ?>
</script>
