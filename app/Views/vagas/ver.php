<?php
$regimes     = \App\Controllers\VagasController::REGIMES;
$jornadas    = \App\Controllers\VagasController::JORNADAS;
$modalidades = \App\Controllers\VagasController::MODALIDADES;
$niveis      = \App\Controllers\VagasController::NIVEIS;

$local = trim(($vaga['cidade'] ?? '') . (!empty($vaga['uf']) ? '/' . $vaga['uf'] : ''));
$wa    = preg_replace('/\D/', '', $vaga['empresa_whatsapp'] ?? $vaga['empresa_telefone'] ?? '');
$msgWa = "Olá! Vi a vaga \"{$vaga['titulo']}\" no FixaOS e gostaria de saber mais.";

// title/description/canonical reais já foram montados no controller (VagasController::ver()) e
// vão só pro <head> via layouts/landing.php — o JSON-LD abaixo é válido em qualquer parte do
// documento, então reaproveita as mesmas variáveis em vez de recalcular um texto próprio.
$salarioTexto = null;
if (!$vaga['salario_a_combinar'] && ($vaga['salario_min'] || $vaga['salario_max'])) {
    $salarioTexto = $vaga['salario_min'] && $vaga['salario_max']
        ? money($vaga['salario_min']) . ' a ' . money($vaga['salario_max'])
        : money($vaga['salario_min'] ?: $vaga['salario_max']);
}
?>

<script type="application/ld+json">
<?= json_encode([
    '@context'          => 'https://schema.org',
    '@type'             => 'JobPosting',
    'title'             => $vaga['titulo'],
    'description'       => $vaga['descricao'],
    'datePosted'        => date('Y-m-d', strtotime($vaga['criado_em'])),
    'employmentType'    => strtoupper($vaga['regime']) === 'CLT' ? 'FULL_TIME' : strtoupper($vaga['regime']),
    'hiringOrganization' => ['@type' => 'Organization', 'name' => $vaga['empresa_nome']],
    'jobLocation'       => [
        '@type'   => 'Place',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $vaga['cidade'], 'addressRegion' => $vaga['uf'], 'addressCountry' => 'BR'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<style>
.vgd-hero{background:linear-gradient(135deg,#0b0d10 0%,#1a2744 60%,#1e3a5f 100%);padding:2.4rem 0}
.vgd-hero h1{color:#fff;font-weight:900;font-size:1.7rem;margin-bottom:.3rem}
.vgd-hero .vgd-empresa{color:#cbd5e1;font-size:.95rem}
.vgd-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.9rem}
.vgd-tag{background:rgba(255,255,255,.1);color:#fff;border-radius:20px;font-size:.78rem;font-weight:600;padding:.3rem .8rem}
.vgd-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.6rem}
.vgd-sal{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:10px;padding:.7rem 1rem;font-weight:700;text-align:center}
.vgd-wa{display:inline-flex;align-items:center;gap:.5rem;background:#25d366;color:#fff;border-radius:10px;padding:.85rem 1.4rem;font-weight:800;text-decoration:none;width:100%;justify-content:center}
.vgd-wa:hover{background:#1eb455;color:#fff}
.vgd-sec h6{font-weight:800;color:#0f172a;margin-bottom:.5rem}
.vgd-sec p{color:#334155;white-space:pre-line;font-size:.92rem;line-height:1.6}
</style>

<section class="vgd-hero">
  <div class="container">
    <a href="<?= url('/vagas') ?>" class="text-decoration-none" style="color:#cbd5e1;font-size:.85rem"><i class="bi bi-arrow-left me-1"></i>Voltar pra todas as vagas</a>
    <h1 class="mt-2"><?= e($vaga['titulo']) ?></h1>
    <div class="vgd-empresa"><i class="bi bi-shop me-1"></i><?= e($vaga['empresa_nome']) ?><?php if ($local): ?> · <i class="bi bi-geo-alt me-1"></i><?= e($local) ?><?php endif; ?></div>
    <div class="vgd-tags">
      <span class="vgd-tag"><?= e($regimes[$vaga['regime']] ?? $vaga['regime']) ?></span>
      <span class="vgd-tag"><?= e($jornadas[$vaga['jornada']] ?? $vaga['jornada']) ?></span>
      <span class="vgd-tag"><?= e($modalidades[$vaga['modalidade']] ?? $vaga['modalidade']) ?></span>
      <?php if ($vaga['nivel']): ?><span class="vgd-tag"><?= e($niveis[$vaga['nivel']] ?? $vaga['nivel']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<div class="container py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="vgd-card">
        <div class="vgd-sec mb-4">
          <h6><i class="bi bi-card-text me-1"></i>Descrição</h6>
          <p><?= e($vaga['descricao']) ?></p>
        </div>
        <?php if ($vaga['requisitos']): ?>
        <div class="vgd-sec mb-4">
          <h6><i class="bi bi-check2-square me-1"></i>Requisitos</h6>
          <p><?= e($vaga['requisitos']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($vaga['beneficios']): ?>
        <div class="vgd-sec">
          <h6><i class="bi bi-gift me-1"></i>Benefícios</h6>
          <p><?= e($vaga['beneficios']) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="vgd-card d-flex flex-column gap-3">
        <?php if ($salarioTexto): ?>
        <div class="vgd-sal"><?= $salarioTexto ?></div>
        <?php endif; ?>
        <?php if ($wa): ?>
        <a href="https://wa.me/55<?= e($wa) ?>?text=<?= urlencode($msgWa) ?>" target="_blank" class="vgd-wa">
          <i class="bi bi-whatsapp"></i> Tenho interesse
        </a>
        <p class="text-muted small mb-0 text-center">Você fala direto com a empresa pelo WhatsApp — nada é enviado pelo FixaOS.</p>
        <?php else: ?>
        <p class="text-muted small mb-0 text-center">Esta empresa ainda não cadastrou um WhatsApp de contato.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
