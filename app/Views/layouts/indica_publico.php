<?php
// Fixa Indica — layout público (indica.fixaos.com.br). Mesmo CONTRATO de variáveis de SEO já
// usado em layouts/landing.php ($tituloFull/$metaDesc/$canonical/$ogImage/$ogImageWidth/
// $ogImageHeight/$noindex), não o HTML/CSS dele — visual propositalmente diferente: fundo
// claro, laranja só nos botões "Ver no Mercado Livre", verde só nos selos, sem amarelo/logo do
// Mercado Livre (identidade do site é FixaOS + "Indica", não do marketplace).
$tituloFull = $tituloFull ?? 'Fixa Indica — produtos indicados por técnicos';
$metaDesc   = $metaDesc ?? 'Ferramentas, peças e eletrônicos indicados por técnicos de assistência técnica, com link direto pro Mercado Livre.';
$canonical  = $canonical ?? indica_url($_SERVER['REQUEST_URI'] ?? '/');
$noindex    = $noindex ?? false;
$faixaFixaosAtiva = \App\Services\Indica\CatalogoService::config('faixa_fixaos_ativa', '1') !== '0';
$whatsappContato   = \App\Services\Indica\CatalogoService::config('whatsapp_contato', '');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloFull) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($tituloFull) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if (!empty($ogImage)): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php if (!empty($ogImageWidth)): ?><meta property="og:image:width" content="<?= (int) $ogImageWidth ?>"><?php endif; ?>
<?php if (!empty($ogImageHeight)): ?><meta property="og:image:height" content="<?= (int) $ogImageHeight ?>"><?php endif; ?>
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fixaos.com.br">
<style>
:root{--fx-laranja:#f97316;--fx-laranja-escuro:#c2410c;--fx-verde:#16a34a;--fx-roxo:#7c3aed;--fx-texto:#1a1a2e;--fx-texto-sec:#64748b;--fx-borda:#e5e7eb;--fx-fundo:#f8fafc}
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--fx-texto);background:#fff;line-height:1.5}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
.fx-container{max-width:1100px;margin:0 auto;padding:0 16px}
.fx-header{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid var(--fx-borda)}
.fx-header-row{display:flex;align-items:center;gap:16px;padding:12px 0}
.fx-logo{display:flex;align-items:center;gap:6px;font-weight:800;font-size:1.15rem;white-space:nowrap}
.fx-logo .fixa{color:var(--fx-roxo)}
.fx-logo .indica{color:var(--fx-laranja)}
.fx-busca{flex:1;display:flex}
.fx-busca input{flex:1;padding:10px 14px;border:1.5px solid var(--fx-borda);border-radius:8px 0 0 8px;font-size:.95rem;min-width:0}
.fx-busca button{padding:10px 16px;border:none;background:var(--fx-texto);color:#fff;border-radius:0 8px 8px 0;cursor:pointer}
.fx-cats-faixa{display:flex;gap:8px;overflow-x:auto;padding:10px 0;border-top:1px solid var(--fx-borda)}
.fx-cats-faixa a{flex:0 0 auto;padding:6px 14px;background:var(--fx-fundo);border-radius:999px;font-size:.85rem;font-weight:600;white-space:nowrap}
.fx-main{min-height:60vh}
.fx-btn-ml{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:var(--fx-laranja);color:#fff;font-weight:700;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:.92rem}
.fx-btn-ml:hover{background:var(--fx-laranja-escuro)}
.fx-selo{display:inline-block;background:var(--fx-verde);color:#fff;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:4px;margin-right:4px}
.fx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.fx-card{border:1px solid var(--fx-borda);border-radius:10px;overflow:hidden;background:#fff}
.fx-card-img{aspect-ratio:1;background:var(--fx-fundo);overflow:hidden}
.fx-card-img img{width:100%;height:100%;object-fit:contain}
.fx-carrossel-principal{position:relative;aspect-ratio:1;background:var(--fx-fundo);border:1px solid var(--fx-borda);border-radius:10px;overflow:hidden}
.fx-carrossel-img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;opacity:0;transition:opacity .2s;pointer-events:none}
.fx-carrossel-img.ativa{opacity:1}
.fx-carrossel-seta{position:absolute;top:50%;transform:translateY(-50%);width:34px;height:34px;border-radius:999px;border:none;background:rgba(255,255,255,.92);box-shadow:0 1px 5px rgba(0,0,0,.25);font-size:1.4rem;line-height:1;color:var(--fx-texto);cursor:pointer;display:flex;align-items:center;justify-content:center}
.fx-carrossel-seta:hover{background:#fff}
.fx-carrossel-prev{left:8px}
.fx-carrossel-next{right:8px}
.fx-carrossel-thumbs{display:flex;gap:8px;margin-top:10px;overflow-x:auto;padding-bottom:2px}
.fx-carrossel-thumb{width:56px;height:56px;flex:0 0 auto;object-fit:cover;border-radius:6px;border:2px solid transparent;cursor:pointer;opacity:.6;background:var(--fx-fundo)}
.fx-carrossel-thumb.ativa{border-color:var(--fx-laranja);opacity:1}
.fx-card-body{padding:10px}
.fx-card-nome{font-size:.85rem;font-weight:600;margin:0 0 6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.3em}
.fx-card-preco{font-size:.78rem;color:var(--fx-texto-sec);margin-bottom:8px}
.fx-card-preco strong{display:block;font-size:1.05rem;color:var(--fx-texto)}
.fx-section{padding:28px 0}
.fx-section-titulo{font-size:1.2rem;font-weight:800;margin:0 0 14px}
.fx-footer{background:var(--fx-texto);color:#cbd5e1;padding:28px 0;margin-top:40px;font-size:.82rem;line-height:1.7}
.fx-footer a{color:#f1f5f9;text-decoration:underline}
.fx-faixa-fixaos{background:var(--fx-fundo);border:1px solid var(--fx-borda);border-radius:12px;padding:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:24px 0}
.fx-faixa-fixaos .btn{background:var(--fx-roxo);color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;font-size:.85rem}
@media (max-width:600px){.fx-header-row{flex-wrap:wrap}.fx-busca{order:3;width:100%}}
</style>
</head>
<body>
<header class="fx-header">
  <div class="fx-container">
    <div class="fx-header-row">
      <a href="/" class="fx-logo"><span class="fixa">Fixa</span><span class="indica">Indica</span></a>
      <form class="fx-busca" action="/busca" method="GET" role="search">
        <input type="search" name="q" placeholder="Buscar ferramenta, peça, aparelho…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar">
        <button type="submit" aria-label="Buscar">🔎</button>
      </form>
    </div>
    <?php if (!empty($categoriasMenu)): ?>
    <nav class="fx-cats-faixa" aria-label="Categorias">
      <?php foreach ($categoriasMenu as $c): ?>
      <a href="/categoria/<?= e($c['slug']) ?>"><?= e($c['nome']) ?></a>
      <?php endforeach; ?>
      <a href="/guias">Guias</a>
    </nav>
    <?php endif; ?>
  </div>
</header>

<main class="fx-main">
<?= $content() ?>
</main>

<footer class="fx-footer">
  <div class="fx-container">
    <?php if ($faixaFixaosAtiva): ?>
    <div class="fx-faixa-fixaos">
      <div style="flex:1;min-width:200px">
        <strong style="color:#fff">FixaOS — sistema de gestão pra assistência técnica</strong>
        <div>Ordens de serviço, financeiro, estoque e PDV num só lugar.</div>
      </div>
      <a class="btn" href="https://fixaos.com.br" target="_blank" rel="noopener">Conheça o FixaOS</a>
    </div>
    <?php endif; ?>
    <?php if ($whatsappContato !== ''): ?>
    <p><a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsappContato)) ?>" target="_blank" rel="noopener">Fale com a gente pelo WhatsApp</a></p>
    <?php endif; ?>
    <p>Fixa Indica é um produto FixaOS e participa do Programa de Afiliados do Mercado Livre.
    Ao comprar pelos nossos links, podemos receber uma comissão, sem custo extra para você.
    Preços e estoque são definidos no Mercado Livre.</p>
  </div>
</footer>
</body>
</html>
