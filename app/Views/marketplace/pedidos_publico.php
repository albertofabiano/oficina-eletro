<?php
$titulo  = 'Pedidos de Peças — Comunidade FixaOS';
$metaDesc= 'Técnicos buscando peças específicas. Tem o que eles precisam? Responda e feche negócio.';
?>
<title><?= $titulo ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $baseUrl ?>/pecas/pedidos">

<style>
:root{--brand:#f97316}
.ped-hero{background:linear-gradient(135deg,#0b0d10,#1a2744);padding:3.5rem 0 2.5rem;position:relative;overflow:hidden}
.ped-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 70% 50%,rgba(249,115,22,.1),transparent 70%)}
.ped-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:1.4rem;transition:.2s;text-decoration:none;display:block;color:inherit}
.ped-card:hover{border-color:#f97316;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.ped-card.urgente{border-color:#ef4444;border-width:2px}
.badge-urgente{background:#fef2f2;color:#ef4444;border:1px solid #fecaca;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem}
.badge-normal{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem}
.s-input{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);color:#fff;border-radius:10px;padding:.65rem 1rem;font-size:.9rem;width:100%}
.s-input::placeholder{color:#4b5563}
.s-input:focus{outline:none;border-color:rgba(249,115,22,.6)}
.btn-criar{background:#f97316;color:#fff;border:none;border-radius:10px;padding:.75rem 1.8rem;font-weight:700;font-size:1rem;cursor:pointer;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}
.btn-criar:hover{background:#ea6c0a;color:#fff}
</style>

<!-- Hero -->
<div class="ped-hero">
  <div class="container position-relative">
    <div class="text-center mb-4">
      <div style="display:inline-flex;align-items:center;gap:.5rem;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:#fb923c;border-radius:100px;font-size:.78rem;font-weight:700;padding:.35rem 1rem;margin-bottom:1rem">
        <i class="bi bi-megaphone-fill"></i> Pedidos da Comunidade
      </div>
      <h1 style="color:#fff;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;margin-bottom:.5rem">
        Precisa de uma <span style="color:#f97316">peça específica?</span>
      </h1>
      <p style="color:#94a3b8;max-width:520px;margin:auto;font-size:.95rem">
        Publique seu pedido e deixe a comunidade de assistências te ajudar. <?= $total ?> pedido<?= $total!=1?'s':'' ?> aberto<?= $total!=1?'s':'' ?>.
      </p>
    </div>

    <!-- Busca -->
    <div style="max-width:560px;margin:1.5rem auto 0;display:flex;gap:.6rem">
      <form method="GET" action="<?= $baseUrl ?>/pecas/pedidos" style="flex:1;display:flex;gap:.6rem">
        <input type="search" name="busca" class="s-input" placeholder="Buscar peça, marca, modelo..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" style="background:#f97316;color:#fff;border:none;border-radius:10px;padding:.65rem 1.2rem;cursor:pointer"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <!-- Botão criar (só logado) -->
    <?php if(\App\Core\Auth::check()): ?>
    <div class="text-center mt-3">
      <button onclick="document.getElementById('modalNovoPedido').style.display='flex'" class="btn-criar">
        <i class="bi bi-plus-circle-fill"></i>Publicar pedido de peça
      </button>
    </div>
    <?php else: ?>
    <div class="text-center mt-3">
      <a href="<?= $baseUrl ?>/login" class="btn-criar">
        <i class="bi bi-plus-circle-fill"></i>Entrar para publicar pedido
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Lista de pedidos -->
<div style="background:#f8fafc;padding:3rem 0;min-height:60vh">
<div class="container">

  <?php if($pedidos): ?>
  <div class="row g-3">
    <?php foreach($pedidos as $p): ?>
    <div class="col-md-6 col-lg-4">
      <a href="<?= $baseUrl ?>/pecas/pedidos/<?= $p['id'] ?>" class="ped-card <?= $p['urgencia']==='urgente'?'urgente':'' ?>">

        <div class="d-flex align-items-start justify-content-between mb-2">
          <span class="<?= $p['urgencia']==='urgente'?'badge-urgente':'badge-normal' ?>">
            <?= $p['urgencia']==='urgente'?'🔥 URGENTE':'✓ Normal' ?>
          </span>
          <?php if($p['total_respostas'] > 0): ?>
          <span style="background:#f0f9ff;color:#0369a1;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-chat-dots me-1"></i><?= $p['total_respostas'] ?> resp.
          </span>
          <?php endif; ?>
        </div>

        <h3 style="color:#0f172a;font-size:.95rem;font-weight:700;margin-bottom:.4rem;line-height:1.4"><?= htmlspecialchars($p['titulo']) ?></h3>

        <?php if($p['marca'] || $p['modelo']): ?>
        <div style="color:#475569;font-size:.82rem;margin-bottom:.4rem">
          <?= htmlspecialchars(trim($p['marca'].' '.$p['modelo'])) ?>
        </div>
        <?php endif; ?>

        <?php if($p['descricao']): ?>
        <div style="color:#64748b;font-size:.82rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.6rem">
          <?= htmlspecialchars($p['descricao']) ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.8rem;padding-top:.8rem;border-top:1px solid #f1f5f9">
          <div style="color:#64748b;font-size:.78rem">
            <i class="bi bi-building me-1"></i><?= htmlspecialchars($p['empresa_nome']) ?>
            <?php if($p['cidade']): ?> · <?= htmlspecialchars($p['cidade']) ?>/<?= htmlspecialchars($p['uf']) ?><?php endif; ?>
          </div>
          <div style="color:#94a3b8;font-size:.72rem"><?= date('d/m', strtotime($p['criado_em'])) ?></div>
        </div>

      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Paginação -->
  <?php if($totalPags > 1): ?>
  <div class="mt-4 d-flex justify-content-center">
    <?= paginacao_condensada($pag, $totalPags, function($p) use($baseUrl,$busca){
          $q = array_filter(['pag'=>$p>1?$p:null,'busca'=>$busca]);
          return $baseUrl.'/pecas/pedidos'.($q?'?'.http_build_query($q):'');
        }) ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:4rem 2rem;color:#94a3b8">
    <i class="bi bi-search" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
    <h4 style="color:#475569">Nenhum pedido encontrado</h4>
    <p style="font-size:.9rem">Seja o primeiro a publicar um pedido!</p>
  </div>
  <?php endif; ?>

</div>
</div>

<!-- Modal novo pedido -->
<?php if(\App\Core\Auth::check()): ?>
<div id="modalNovoPedido" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:1rem">
  <div style="background:#fff;border-radius:20px;padding:2rem;max-width:520px;width:100%;max-height:90vh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
      <h4 style="font-weight:800;margin:0;color:#0f172a">Publicar pedido de peça</h4>
      <button onclick="document.getElementById('modalNovoPedido').style.display='none'" style="background:none;border:none;font-size:1.4rem;color:#94a3b8;cursor:pointer">×</button>
    </div>

    <form method="POST" action="<?= $baseUrl ?>/marketplace/pedidos">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Título do pedido *</label>
        <input type="text" name="titulo" class="form-control" placeholder="Ex: Placa principal Samsung UN40D5500" required>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Tipo de equipamento</label>
          <input type="text" name="tipo" class="form-control" placeholder="TV, Celular, Notebook...">
        </div>
        <div class="col-6">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Marca</label>
          <input type="text" name="marca" class="form-control" placeholder="Samsung, LG...">
        </div>
      </div>

      <div class="mb-3">
        <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Modelo</label>
        <input type="text" name="modelo" class="form-control" placeholder="UN40D5500, Galaxy S23...">
      </div>

      <div class="mb-3">
        <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Descrição / detalhes</label>
        <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva a peça, o defeito, referências que você tem..."></textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Urgência</label>
          <select name="urgencia" class="form-select">
            <option value="normal">Normal</option>
            <option value="urgente">Urgente 🔥</option>
          </select>
        </div>
        <div class="col-6">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">WhatsApp para contato</label>
          <input type="text" name="contato_whatsapp" class="form-control" placeholder="(11) 99999-9999">
        </div>
      </div>

      <button type="submit" style="background:#f97316;color:#fff;border:none;border-radius:12px;padding:.85rem 1.6rem;font-weight:700;width:100%;font-size:1rem;cursor:pointer">
        <i class="bi bi-megaphone-fill me-2"></i>Publicar pedido
      </button>
    </form>
  </div>
</div>
<?php endif; ?>
