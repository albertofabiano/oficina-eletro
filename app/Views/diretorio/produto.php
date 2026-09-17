<?php
$nome    = htmlspecialchars($produto['nome_fantasia'] ?: 'Assistência Técnica');
$empresaSlug = $produto['empresa_slug'];
$wa      = preg_replace('/\D/', '', $produto['whatsapp_publico'] ?? $produto['telefone'] ?? '');
$galeria = !empty($produto['imagens_galeria']) ? (json_decode($produto['imagens_galeria'], true) ?: []) : [];
$todasImagens = array_values(array_filter(array_merge(
    $produto['imagem_principal'] ? [$produto['imagem_principal']] : [],
    $galeria
)));
$msgWa = $wa ? urlencode(
    "Olá! Vi o produto \"{$produto['titulo']}\" (R$ " . number_format((float) $produto['valor'], 2, ',', '.') .
    ") no perfil da {$nome} no FixaOS e tenho interesse. Ainda disponível?"
) : '';
$tags = $tags ?? [];
$quantidade = (int) ($produto['quantidade'] ?? 1);

// JSON-LD Product — dados estruturados de verdade (Google Rich Results/Shopping), diferente da
// meta "keywords" (sem efeito prático no Google desde ~2009): as tags entram aqui como
// `keywords` do schema.org, junto de imagem/preço/disponibilidade. Mesmo padrão de
// json_encode(..., JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) já usado em vagas/ver.php
// (JobPosting), em vez de interpolar string manualmente.
$imagensAbsolutas = array_map(fn($img) => $baseUrl . '/uploads/diretorio-produtos/' . $img, $todasImagens);
$descPlanaLd = trim(strip_tags($produto['descricao'] ?? '')) ?: $produto['titulo'];
$productLd = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $produto['titulo'],
    'description' => mb_substr($descPlanaLd, 0, 500, 'UTF-8'),
    'sku'         => (string) $produto['id'],
    'brand'       => ['@type' => 'Organization', 'name' => $produto['nome_fantasia'] ?: 'Assistência Técnica'],
    'offers'      => [
        '@type'         => 'Offer',
        'url'           => $canonical,
        'priceCurrency' => 'BRL',
        'price'         => number_format((float) $produto['valor'], 2, '.', ''),
        'availability'  => empty($produto['esgotado']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/UsedCondition',
    ],
];
if ($imagensAbsolutas) $productLd['image'] = $imagensAbsolutas;
if ($tags) $productLd['keywords'] = implode(', ', $tags);
?>
<style>
.pdp-breadcrumb{background:#fff;border-bottom:1px solid #e2e8f0;padding:.6rem 0;font-size:.82rem}
.pdp-wrap{background:#f8fafc;padding:2rem 0 3rem}
.pdp-card{background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(15,23,42,.06);padding:1.6rem;max-width:640px;margin:0 auto}
.pdp-main{aspect-ratio:1/1;border-radius:12px;overflow:hidden;background:#f1f5f9;border:1px solid #e2e8f0;position:relative;display:flex;align-items:center;justify-content:center;cursor:zoom-in}
.pdp-main img{width:100%;height:100%;object-fit:contain}
.pdp-lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:4000;align-items:center;justify-content:center;padding:1rem}
.pdp-lightbox img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:6px}
.pdp-lightbox-fechar{position:absolute;top:1rem;right:1.2rem;background:none;border:none;color:#fff;font-size:2.2rem;cursor:pointer;line-height:1;opacity:.85}
.pdp-lightbox-fechar:hover{opacity:1}
.pdp-lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;font-size:2.8rem;cursor:pointer;opacity:.75;line-height:1;padding:.5rem}
.pdp-lightbox-nav:hover{opacity:1}
.pdp-lightbox-prev{left:.3rem}
.pdp-lightbox-next{right:.3rem}
.pdp-thumbs{display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap}
.pdp-thumb{width:64px;height:64px;border-radius:8px;overflow:hidden;border:2px solid #e2e8f0;cursor:pointer;flex-shrink:0}
.pdp-thumb.active,.pdp-thumb:hover{border-color:#f97316}
.pdp-thumb img{width:100%;height:100%;object-fit:cover}
.pdp-titulo{font-size:1.3rem;font-weight:800;color:#0f172a;margin-top:1.1rem}
.pdp-preco{font-size:1.5rem;font-weight:900;color:#16a34a;margin:.3rem 0 1rem}
.pdp-esgotado{font-size:1.1rem;font-weight:800;color:#dc2626;margin:.3rem 0 1rem}
.pdp-vendedor{font-size:.85rem;color:#64748b;margin-top:1rem}
.pdp-vendedor a{color:#f97316;text-decoration:none;font-weight:700}
.pdp-btn-wa{display:flex;align-items:center;justify-content:center;gap:.5rem;background:#25d366;color:#fff;border-radius:12px;padding:.85rem 1.2rem;font-weight:700;text-decoration:none;width:100%}
.pdp-btn-wa:hover{background:#1da852;color:#fff}
.pdp-esgotado-overlay{position:absolute;inset:0;background:rgba(15,23,42,.5);display:flex;align-items:center;justify-content:center}
.pdp-esgotado-badge{background:#dc2626;color:#fff;font-size:.75rem;font-weight:800;letter-spacing:.02em;padding:.35rem .7rem;border-radius:8px;text-transform:uppercase}
.pdp-relacionados{max-width:960px;margin:2.5rem auto 0}
.pdp-rel-card{border:1px solid #eef2f7;border-radius:12px;overflow:hidden;background:#fff;text-decoration:none;display:block;color:inherit}
.pdp-rel-card:hover{box-shadow:0 4px 14px rgba(15,23,42,.08)}
.pdp-rel-img{width:100%;aspect-ratio:1/1;background:#f1f5f9;display:flex;align-items:center;justify-content:center;object-fit:cover}
.pdp-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin:.2rem 0 1rem}
.pdp-tag{display:inline-flex;align-items:center;padding:.22rem .65rem;border-radius:999px;font-size:.76rem;font-weight:700;border:1.5px solid}
.pdp-tag-0{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8}
.pdp-tag-1{background:#f0fdf4;border-color:#22c55e;color:#15803d}
.pdp-tag-2{background:#fff7ed;border-color:#f97316;color:#c2410c}
.pdp-tag-3{background:#faf5ff;border-color:#a855f7;color:#7e22ce}
.pdp-tag-4{background:#fef2f2;border-color:#ef4444;color:#b91c1c}
.pdp-tag-5{background:#f0fdfa;border-color:#14b8a6;color:#0f766e}
.pdp-qtd{font-size:.82rem;font-weight:600;color:#0f766e;background:#f0fdfa;border:1px solid #99f6e4;display:inline-flex;align-items:center;border-radius:8px;padding:.25rem .6rem;margin:0 0 .8rem}
.pdp-voltar{display:inline-flex;align-items:center;gap:.4rem;color:#f97316;font-weight:700;font-size:.85rem;text-decoration:none;margin-bottom:1rem}
.pdp-voltar:hover{color:#c2410c;text-decoration:underline}
</style>

<script type="application/ld+json">
<?= json_encode($productLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<div class="pdp-breadcrumb">
  <div class="container">
    <a href="<?= $baseUrl ?>/assistencias" style="color:#f97316;text-decoration:none">← Diretório</a>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($empresaSlug) ?>" style="color:#64748b;text-decoration:none"><?= $nome ?></a>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <span style="color:#0f172a;font-weight:600"><?= htmlspecialchars($produto['titulo']) ?></span>
  </div>
</div>

<div class="pdp-wrap">
  <div class="container">
    <div class="pdp-card">

      <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($empresaSlug) ?>" class="pdp-voltar">
        <i class="bi bi-arrow-left"></i> Voltar para <?= $nome ?>
      </a>

      <?php if ($todasImagens): ?>
      <div class="pdp-main" id="pdpMain">
        <img id="pdpImgMain" src="<?= $baseUrl ?>/uploads/diretorio-produtos/<?= htmlspecialchars($todasImagens[0]) ?>" alt="<?= htmlspecialchars($produto['titulo']) ?>">
        <?php if (!empty($produto['esgotado'])): ?>
        <div class="pdp-esgotado-overlay"><span class="pdp-esgotado-badge">Fora de estoque</span></div>
        <?php endif; ?>
      </div>
      <?php if (count($todasImagens) > 1): ?>
      <div class="pdp-thumbs">
        <?php foreach ($todasImagens as $i => $img): ?>
        <div class="pdp-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="pdpIrParaImagem(<?= $i ?>); pdpIniciarCarrossel();">
          <img src="<?= $baseUrl ?>/uploads/diretorio-produtos/<?= htmlspecialchars($img) ?>" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="pdp-main">
        <i class="bi bi-image" style="font-size:3rem;color:#cbd5e1"></i>
      </div>
      <?php endif; ?>

      <div class="pdp-titulo"><?= htmlspecialchars($produto['titulo']) ?></div>

      <?php if (!empty($produto['esgotado'])): ?>
      <div class="pdp-esgotado"><i class="bi bi-x-circle-fill me-1"></i>Fora de estoque</div>
      <?php else: ?>
      <div class="pdp-preco">R$ <?= number_format((float) $produto['valor'], 2, ',', '.') ?></div>
      <?php if ($quantidade > 1): ?>
      <div class="pdp-qtd"><i class="bi bi-boxes me-1"></i><?= $quantidade ?> unidades disponíveis</div>
      <?php endif; ?>
      <?php if ($wa): ?>
      <a href="https://wa.me/55<?= $wa ?>?text=<?= $msgWa ?>" target="_blank" class="pdp-btn-wa">
        <i class="bi bi-whatsapp fs-5"></i> Chamar no WhatsApp
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if ($tags): ?>
      <!-- Chips renderizados no servidor de propósito (texto real no HTML, não só via JS) —
           é o que torna a tag efetivamente indexável/crawlable pelo Google, além de já
           alimentar o JSON-LD (Product.keywords) e a meta description logo acima. -->
      <div class="pdp-tags">
        <?php foreach ($tags as $i => $tag): ?>
        <span class="pdp-tag pdp-tag-<?= $i % 6 ?>"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($produto['descricao'])): ?>
      <div style="margin-top:1.1rem;font-size:.92rem;color:#334155;white-space:pre-line"><?= htmlspecialchars($produto['descricao']) ?></div>
      <?php endif; ?>

      <div class="pdp-vendedor">
        <i class="bi bi-shop-window me-1"></i>Vendido por
        <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($empresaSlug) ?>"><?= $nome ?></a>
        <?php if ($produto['cidade']): ?> — <?= htmlspecialchars($produto['cidade']) ?>/<?= htmlspecialchars($produto['uf']) ?><?php endif; ?>
      </div>
    </div>

    <?php if (!empty($relacionados)): ?>
    <div class="pdp-relacionados">
      <h2 style="color:#0f172a;font-size:1.05rem;font-weight:800;margin-bottom:1rem">
        <i class="bi bi-grid-3x3-gap me-2" style="color:#f97316"></i>Outros produtos de <?= $nome ?>
      </h2>
      <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
        <?php foreach ($relacionados as $rel): ?>
        <?php $relEsgotado = $rel['status'] === 'vendido'; ?>
        <div class="col">
          <a href="<?= $baseUrl ?>/produto-diretorio/<?= htmlspecialchars($rel['slug'] ?: $rel['id'], ENT_QUOTES, 'UTF-8') ?>" class="pdp-rel-card">
            <?php if (!empty($rel['imagem_principal'])): ?>
            <img class="pdp-rel-img" src="<?= $baseUrl ?>/uploads/diretorio-produtos/<?= htmlspecialchars($rel['imagem_principal']) ?>"
                 alt="<?= htmlspecialchars($rel['titulo']) ?>" loading="lazy"
                 style="<?= $relEsgotado ? 'filter:grayscale(60%)' : '' ?>">
            <?php else: ?>
            <div class="pdp-rel-img"><i class="bi bi-image" style="font-size:1.6rem;color:#cbd5e1"></i></div>
            <?php endif; ?>
            <div style="padding:.5rem .6rem">
              <div style="font-size:.78rem;font-weight:600;color:#0f172a;line-height:1.3;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;min-height:2rem">
                <?= htmlspecialchars($rel['titulo']) ?>
              </div>
              <?php if ($relEsgotado): ?>
              <div style="font-size:.74rem;font-weight:700;color:#dc2626;margin-top:.15rem">Fora de estoque</div>
              <?php else: ?>
              <div style="font-size:.82rem;font-weight:800;color:#16a34a;margin-top:.15rem">R$ <?= number_format((float) $rel['valor'], 2, ',', '.') ?></div>
              <?php endif; ?>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($todasImagens): ?>
<!-- Lightbox — abre ao clicar na imagem principal, mesma paleta escura do resto dos modais
     desta família de views (#modalReivindicar/#modalVitrineIndisponivel em diretorio/empresa.php),
     sem depender do componente modal do Bootstrap. -->
<div id="pdpLightbox" class="pdp-lightbox">
  <button type="button" class="pdp-lightbox-fechar" onclick="pdpFecharLightbox()" aria-label="Fechar">&times;</button>
  <?php if (count($todasImagens) > 1): ?>
  <button type="button" class="pdp-lightbox-nav pdp-lightbox-prev" onclick="pdpLightboxNavegar(-1)" aria-label="Foto anterior">&lsaquo;</button>
  <button type="button" class="pdp-lightbox-nav pdp-lightbox-next" onclick="pdpLightboxNavegar(1)" aria-label="Próxima foto">&rsaquo;</button>
  <?php endif; ?>
  <img id="pdpLightboxImg" src="" alt="<?= htmlspecialchars($produto['titulo']) ?>">
</div>
<script>
// pdpImgs/pdpIndice/pdpIrParaImagem ficam disponíveis sempre que existe ao menos 1 foto —
// o lightbox funciona com uma foto só (sem setas de navegação); o carrossel automático (timer
// + miniaturas) só entra em jogo com mais de uma, igual antes.
const pdpImgs = <?= json_encode(array_values(array_map(
    fn($img) => $baseUrl . '/uploads/diretorio-produtos/' . $img,
    $todasImagens
))) ?>;
let pdpIndice = 0;
function pdpIrParaImagem(i) {
  pdpIndice = ((i % pdpImgs.length) + pdpImgs.length) % pdpImgs.length;
  document.getElementById('pdpImgMain').src = pdpImgs[pdpIndice];
  document.querySelectorAll('.pdp-thumb').forEach((t, idx) => t.classList.toggle('active', idx === pdpIndice));
}

// Lightbox — reaproveita pdpIndice (a foto que já está em exibição) como ponto de partida.
function pdpAbrirLightbox() {
  document.getElementById('pdpLightboxImg').src = pdpImgs[pdpIndice];
  document.getElementById('pdpLightbox').style.display = 'flex';
  if (typeof pdpPararCarrossel === 'function') pdpPararCarrossel();
}
function pdpFecharLightbox() {
  document.getElementById('pdpLightbox').style.display = 'none';
  if (typeof pdpIniciarCarrossel === 'function') pdpIniciarCarrossel();
}
function pdpLightboxNavegar(delta) {
  pdpIrParaImagem(pdpIndice + delta);
  document.getElementById('pdpLightboxImg').src = pdpImgs[pdpIndice];
}
document.getElementById('pdpMain').addEventListener('click', pdpAbrirLightbox);
document.addEventListener('keydown', function (e) {
  if (document.getElementById('pdpLightbox').style.display !== 'flex') return;
  if (e.key === 'Escape') pdpFecharLightbox();
  if (e.key === 'ArrowLeft') pdpLightboxNavegar(-1);
  if (e.key === 'ArrowRight') pdpLightboxNavegar(1);
});

<?php if (count($todasImagens) > 1): ?>
// Carrossel automático — mesmo padrão já usado em marketplace/peca.php: troca sozinho a cada
// 4s, clicar numa miniatura navega na hora e reinicia a contagem (senão o autoplay "brigaria"
// com o clique, avançando de novo logo em seguida pra uma foto diferente da escolhida).
let pdpTimer = null;
function pdpIniciarCarrossel() {
  if (pdpTimer) clearInterval(pdpTimer);
  pdpTimer = setInterval(() => pdpIrParaImagem(pdpIndice + 1), 4000);
}
function pdpPararCarrossel() { if (pdpTimer) clearInterval(pdpTimer); }
const pdpMainEl = document.getElementById('pdpMain');
pdpMainEl.addEventListener('mouseenter', pdpPararCarrossel);
pdpMainEl.addEventListener('mouseleave', pdpIniciarCarrossel);
pdpIniciarCarrossel();
<?php endif; ?>
</script>
<?php endif; ?>
