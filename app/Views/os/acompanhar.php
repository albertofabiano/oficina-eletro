<?php
$cfg     = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($cfg['url'], '/');
$wa      = preg_replace('/\D/', '', $os['empresa_whats'] ?? $os['empresa_tel'] ?? '');
$msgWa   = urlencode("Olá! Gostaria de informações sobre a OS: {$os['numero']}");
?>
<style>
body{background:#f8fafc;font-family:'Inter',sans-serif}
.track-hero{background:linear-gradient(135deg,#1e3a5f,#0b1a2e);padding:2rem 0;color:#fff}
.track-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.5rem;margin-bottom:1rem}
.status-pill{display:inline-block;border-radius:20px;padding:.4rem 1.2rem;font-weight:700;font-size:.9rem}
.timeline{position:relative;padding-left:2rem}
.timeline::before{content:'';position:absolute;left:.65rem;top:0;bottom:0;width:2px;background:#e2e8f0}
.tl-item{position:relative;margin-bottom:1.2rem}
.tl-dot{position:absolute;left:-1.65rem;top:.2rem;width:14px;height:14px;border-radius:50%;background:#1e3a5f;border:3px solid #fff;box-shadow:0 0 0 2px #1e3a5f}
.tl-dot.active{background:#f97316;box-shadow:0 0 0 2px #f97316}
.btn-wa{background:#25d366;color:#fff;border:none;border-radius:12px;padding:.8rem 1.6rem;font-weight:700;font-size:1rem;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;transition:.2s}
.btn-wa:hover{background:#1da852;color:#fff}
.track-logo{height:48px;border-radius:8px;background:#fff;padding:4px;object-fit:contain}
@media (max-width:576px){
  .track-logo{width:calc(100% - 20px);height:auto;max-width:calc(100% - 20px);margin:0 10px}
}
</style>

<!-- Hero -->
<div class="track-hero">
  <div class="container">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <?php if($os['empresa_logo']): ?>
      <img src="<?= $baseUrl ?>/uploads/<?= e($os['empresa_logo']) ?>" class="track-logo" alt="Logo">
      <?php endif; ?>
      <div>
        <div style="font-size:.8rem;opacity:.7">Acompanhamento de Ordem de Serviço</div>
        <h1 style="font-size:1.5rem;font-weight:900;margin:0">OS: <?= e($os['numero']) ?></h1>
        <div style="opacity:.8;font-size:.88rem"><?= e($os['empresa_nome']) ?></div>
      </div>
      <div class="ms-auto">
        <span class="status-pill" style="background:<?= e($os['status_cor'] ?? '#6c757d') ?>;color:#fff">
          <?= e($os['status_nome']) ?>
        </span>
      </div>
    </div>
  </div>
</div>

<div class="container py-4">
<div class="row g-4">

  <!-- Coluna principal -->
  <div class="col-lg-8">

    <!-- Avaliação verificada -->
    <?php if(!empty($avaliacaoOs)): ?>
    <div class="track-card" id="avaliar" style="border:1px solid #bbf7d0;background:#f0fdf4">
      <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;flex-wrap:wrap">
        <span style="background:#16a34a;color:#fff;font-size:.7rem;font-weight:800;padding:.22rem .6rem;border-radius:20px"><i class="bi bi-patch-check-fill"></i> AVALIAÇÃO VERIFICADA</span>
        <span style="color:#16a34a;font-weight:700">Obrigado por avaliar!</span>
      </div>
      <div style="color:#f59e0b;font-size:1.4rem;letter-spacing:2px"><?php for($i=1;$i<=5;$i++) echo $i<=$avaliacaoOs['nota']?'★':'☆'; ?></div>
      <?php if($avaliacaoOs['comentario']): ?>
      <div style="color:#374151;margin-top:.4rem;font-style:italic">"<?= e($avaliacaoOs['comentario']) ?>"</div>
      <?php endif; ?>
      <?php if(!empty($avaliacaoOs['resposta'])): ?>
      <div style="background:#fff;border-left:3px solid #0d9488;border-radius:8px;padding:.7rem .9rem;margin-top:.7rem">
        <div style="color:#0f766e;font-weight:700;font-size:.8rem;margin-bottom:.2rem"><i class="bi bi-reply-fill"></i> Resposta da empresa</div>
        <div style="color:#374151;font-size:.9rem"><?= nl2br(e($avaliacaoOs['resposta'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if(!empty($os['empresa_slug'])): ?>
      <a href="<?= $baseUrl ?>/assistencias/<?= e($os['empresa_slug']) ?>#avaliacoes" style="color:#0d9488;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-block;margin-top:.6rem">Ver no diretório <i class="bi bi-arrow-right"></i></a>
      <?php endif; ?>
    </div>
    <?php elseif(!empty($podeAvaliar)): ?>
    <div class="track-card" id="avaliar" style="border:1px solid #fde68a;background:linear-gradient(135deg,#fffbeb,#fff)">
      <?php $ok=flash('success'); $err=flash('error');
      if($ok): ?><div class="alert alert-success py-2 small mb-2"><?= e($ok) ?></div><?php endif;
      if($err): ?><div class="alert alert-danger py-2 small mb-2"><?= e($err) ?></div><?php endif; ?>
      <h5 style="color:#1e3a5f;font-weight:800;margin-bottom:.3rem"><i class="bi bi-star-fill me-1" style="color:#f59e0b"></i>Seu aparelho está pronto — que tal avaliar?</h5>
      <div style="color:#64748b;font-size:.88rem;margin-bottom:1rem">Sua avaliação sai com selo <strong style="color:#16a34a">✓ verificada</strong>, porque veio de um atendimento real. Ajuda outros clientes a escolher com segurança.</div>
      <form method="POST" action="<?= $baseUrl ?>/os/acompanhar/<?= e($os['token_publico']) ?>/avaliar">
        <?= csrf_field() ?>
        <div style="margin-bottom:.8rem">
          <div id="starsOs" style="font-size:2rem;color:#d1d5db;display:inline-flex;gap:.15rem">
            <?php for($i=1;$i<=5;$i++): ?><span data-v="<?= $i ?>" role="button" tabindex="0" aria-label="<?= $i ?> estrela<?= $i>1?'s':'' ?>" style="cursor:pointer">★</span><?php endfor; ?>
          </div>
          <input type="hidden" name="nota" id="notaOs" value="">
        </div>
        <textarea name="comentario" class="form-control mb-2" rows="3" placeholder="Conte como foi o atendimento (opcional)..."></textarea>
        <button type="submit" class="btn w-100" style="background:#16a34a;color:#fff;font-weight:700">Enviar avaliação verificada</button>
      </form>
    </div>
    <script>
    (function(){
      var wrap=document.getElementById('starsOs'), inp=document.getElementById('notaOs');
      if(!wrap) return;
      var stars=wrap.querySelectorAll('span');
      function paint(n){ stars.forEach(function(s,i){ s.style.color = i < n ? '#f59e0b' : '#d1d5db'; }); }
      stars.forEach(function(s){
        s.addEventListener('click', function(){ var v=+s.getAttribute('data-v'); inp.value=v; paint(v); });
        s.addEventListener('mouseenter', function(){ paint(+s.getAttribute('data-v')); });
        s.addEventListener('keydown', function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); var v=+s.getAttribute('data-v'); inp.value=v; paint(v);} });
      });
      wrap.addEventListener('mouseleave', function(){ paint(+inp.value||0); });
    })();
    </script>
    <?php endif; ?>

    <!-- Equipamento -->
    <div class="track-card">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1rem"><i class="bi bi-cpu-fill me-2" style="color:#f97316"></i>Equipamento</h5>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Tipo</div>
          <div style="color:#0f172a;font-weight:600"><?= e($os['equip_tipo'] ?? '--') ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Marca</div>
          <div style="color:#0f172a;font-weight:600"><?= e($os['equip_marca'] ?? '--') ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Modelo</div>
          <div style="color:#0f172a;font-weight:600"><?= e($os['equip_modelo'] ?? '--') ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Cor</div>
          <div style="color:#0f172a;font-weight:600"><?= e($os['equip_cor'] ?? '--') ?></div>
        </div>
      </div>
      <?php if($os['defeito_relatado']): ?>
      <div style="background:#f8fafc;border-radius:10px;padding:1rem;margin-top:1rem">
        <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.4rem">Defeito relatado</div>
        <div style="color:#374151"><?= nl2br(e($os['defeito_relatado'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Fotos do estado de entrada -->
    <?php if (!empty($fotosEntrada)): ?>
    <div class="track-card">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1rem"><i class="bi bi-camera-fill me-2" style="color:#f97316"></i>Fotos do estado de entrada</h5>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($fotosEntrada as $f): ?>
        <a href="<?= $baseUrl ?>/uploads/<?= e($f['arquivo']) ?>" target="_blank" rel="noopener">
          <img src="<?= $baseUrl ?>/uploads/<?= e($f['arquivo']) ?>" loading="lazy"
               style="width:90px;height:90px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0">
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Status e datas -->
    <div class="track-card">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1rem"><i class="bi bi-info-circle-fill me-2" style="color:#f97316"></i>Situação</h5>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Entrada</div>
          <div style="color:#0f172a;font-weight:600"><?= date_br($os['data_entrada']) ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Previsão</div>
          <div style="color:#0f172a;font-weight:600"><?= $os['data_previsao'] ? date_br($os['data_previsao']) : '--' ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Técnico</div>
          <div style="color:#0f172a;font-weight:600"><?= e($os['tecnico_nome'] ?? '--') ?></div>
        </div>
        <div class="col-6 col-md-3">
          <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:.2rem">Valor</div>
          <div style="color:#16a34a;font-weight:700;font-size:1.1rem"><?= money($os['valor_total']) ?></div>
        </div>
      </div>
      <?php if($os['laudo_tecnico']): ?>
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem;margin-top:1rem">
        <div style="color:#166534;font-size:.75rem;text-transform:uppercase;margin-bottom:.4rem;font-weight:700">Laudo técnico</div>
        <div style="color:#374151"><?= $os['laudo_tecnico'] ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Serviços e peças -->
    <?php if($servicos || $pecas): ?>
    <div class="track-card">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1rem"><i class="bi bi-tools me-2" style="color:#f97316"></i>Serviços e peças</h5>
      <?php foreach($servicos as $s): ?>
      <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f1f5f9">
        <span style="color:#374151"><?= e($s['descricao']) ?></span>
        <span style="color:#0f172a;font-weight:600"><?= money($s['valor_total']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php foreach($pecas as $p): ?>
      <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f1f5f9">
        <span style="color:#374151"><i class="bi bi-box-seam me-1" style="color:#94a3b8"></i><?= e($p['descricao']) ?></span>
        <span style="color:#0f172a;font-weight:600"><?= money($p['valor_total']) ?></span>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding:.8rem 0;font-weight:800;font-size:1.05rem">
        <span style="color:#0f172a">Total</span>
        <span style="color:#16a34a"><?= money($os['valor_total']) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <!-- Histórico -->
    <?php if($historico): ?>
    <div class="track-card">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1.2rem"><i class="bi bi-clock-history me-2" style="color:#f97316"></i>Histórico</h5>
      <div class="timeline">
        <?php foreach($historico as $i => $h): ?>
        <div class="tl-item">
          <div class="tl-dot <?= $i === count($historico)-1 ? 'active' : '' ?>"></div>
          <div style="color:#64748b;font-size:.75rem;margin-bottom:.2rem"><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></div>
          <div style="color:#0f172a;font-weight:600"><?= e($h['descricao'] ?? '') ?></div>
          <?php if($h['usuario_nome']): ?>
          <div style="color:#94a3b8;font-size:.78rem"><?= e($h['usuario_nome']) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Coluna lateral -->
  <div class="col-lg-4">
    <div class="track-card" style="position:sticky;top:80px">
      <h5 style="color:#1e3a5f;font-weight:700;margin-bottom:1rem">Fale conosco</h5>

      <?php if($wa): ?>
      <a href="https://wa.me/55<?= $wa ?>?text=<?= $msgWa ?>" target="_blank" class="btn-wa w-100 mb-3 justify-content-center">
        <i class="bi bi-whatsapp fs-5"></i>WhatsApp
      </a>
      <?php endif; ?>

      <?php if($os['empresa_tel']): ?>
      <a href="tel:+55<?= preg_replace('/\D/','',$os['empresa_tel']) ?>" style="display:block;text-align:center;color:#1e3a5f;font-weight:600;margin-bottom:1rem;text-decoration:none">
        <i class="bi bi-telephone-fill me-1"></i><?= e($os['empresa_tel']) ?>
      </a>
      <?php endif; ?>

      <hr style="border-color:#f1f5f9">

      <div style="color:#64748b;font-size:.82rem">
        <div class="fw-bold" style="color:#0f172a;margin-bottom:.3rem"><?= e($os['empresa_nome']) ?></div>
        <?php if($os['cidade']): ?>
        <div><i class="bi bi-geo-alt-fill me-1" style="color:#f97316"></i><?= e($os['cidade']) ?>/<?= e($os['uf']) ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:1.5rem;text-align:center">
        <svg width="80" height="20" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
        <div style="color:#94a3b8;font-size:.72rem;margin-top:.3rem">Gestão de Assistências Técnicas</div>
      </div>
    </div>
  </div>

</div>
</div>
