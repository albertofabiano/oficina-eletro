<?php $titulo = 'Anúncios no Diretório'; ?>
<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$planosDestaque = array_filter($planos, fn($p) => $p['tipo'] === 'destaque');
$planosBanner   = array_filter($planos, fn($p) => $p['tipo'] === 'banner');
?>
<style>
.plano-card{background:#fff;border:2px solid #e2e8f0;border-radius:16px;padding:1.6rem;transition:.2s;position:relative}
.plano-card:hover{border-color:#f97316;transform:translateY(-2px);box-shadow:0 8px 24px rgba(249,115,22,.12)}
.plano-card.premium{border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fff)}
.plano-preco{font-size:2rem;font-weight:900;color:#0f172a;line-height:1}
.plano-preco sup{font-size:.9rem;font-weight:600;color:#64748b;vertical-align:top;margin-top:.4rem}
.plano-preco small{font-size:.85rem;font-weight:400;color:#64748b}
.beneficio{display:flex;align-items:center;gap:.5rem;font-size:.86rem;color:#374151;margin-bottom:.4rem}
.beneficio i{color:#22c55e;flex-shrink:0}
.badge-destaque{background:#f97316;color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .7rem;border-radius:20px}
.badge-premium{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .7rem;border-radius:20px}
.status-badge{border-radius:6px;padding:.25rem .7rem;font-size:.75rem;font-weight:700}
.slot-card{background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;padding:1.2rem;text-align:center;transition:.2s}
.slot-card.ocupado{border-style:solid;border-color:#f97316;background:#fff7ed}
.slot-card.livre{cursor:pointer}.slot-card.livre:hover{border-color:#f97316;background:#fff7ed}
</style>

<div class="page-content">

  <!-- Header -->
  <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">Anúncios no Diretório</h4>
      <p class="text-muted small mb-0">Destaque sua assistência técnica e aumente sua visibilidade no diretório público.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= url('/empresa/perfil-publico') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Editar informações da empresa
      </a>
      <a href="<?= $baseUrl ?>/assistencias" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-eye me-1"></i>Ver diretório
      </a>
    </div>
  </div>

  <?php $ok=flash('success');$err=flash('error');
  if($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif;
  if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

  <!-- DESTAQUES -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
      <h6 class="fw-bold mb-0"><i class="bi bi-star-fill me-2" style="color:#f97316"></i>Destaque no Diretório</h6>
      <div class="text-muted small mt-1">Sua empresa aparece no topo da listagem com badge exclusivo.</div>
    </div>
    <div class="card-body">
      <?php $qtdDestaque = count($planosDestaque); ?>
      <div class="row g-3 <?= $qtdDestaque === 1 ? 'justify-content-center' : '' ?>">
        <?php foreach($planosDestaque as $p):
          $beneficios = explode(';', $p['beneficios'] ?? '');
          $isPremium = $p['tipo']==='destaque' && $p['preco'] > 60;
        ?>
        <div class="col-md-6 <?= $qtdDestaque === 1 ? 'col-lg-5' : '' ?>">
          <div class="plano-card <?= $isPremium ? 'premium' : '' ?>">
            <?php if($isPremium): ?>
            <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%)">
              <span class="badge-premium">⭐ MAIS VENDIDO</span>
            </div>
            <?php endif; ?>
            <div class="fw-bold fs-6 mb-1"><?= e($p['nome']) ?></div>
            <div class="plano-preco mb-2"><sup>R$</sup><?= number_format($p['preco'],2,',','.') ?><small>/<?= $p['duracao_dias'] ?> dias</small></div>
            <div class="text-muted small mb-3"><?= e($p['descricao']) ?></div>
            <?php foreach($beneficios as $b): if(trim($b)): ?>
            <div class="beneficio"><i class="bi bi-check-circle-fill"></i><?= e(trim($b)) ?></div>
            <?php endif; endforeach; ?>
            <button class="btn btn-primary w-100 mt-3 fw-bold" onclick="abrirModal(<?= $p['id'] ?>, '<?= e($p['nome']) ?>', '<?= number_format($p['preco'],2,',','.') ?>', 'destaque')">
              Contratar agora
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- BANNERS -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
      <h6 class="fw-bold mb-0"><i class="bi bi-megaphone-fill me-2" style="color:#f97316"></i>Banners de Anúncio</h6>
      <div class="text-muted small mt-1">Cada posição é um lugar próprio no site (busca, página de cidade ou perfil de outra empresa) — só um anunciante por vez em cada uma.</div>
    </div>
    <div class="card-body">
      <!-- Visualização das posições -->
      <div class="mb-3 p-3 rounded" style="background:#0f1117">
        <div class="text-center text-muted small mb-2" style="color:#6b7280!important">Posições de anúncio — visualização</div>
        <div class="row g-2">
          <?php
          $db = \App\Core\DB::pdo();
          $slotsOcupados = [];
          $stmtSlots = $db->query("SELECT p.posicao_banner, e.nome_fantasia FROM diretorio_assinaturas a JOIN diretorio_planos p ON p.id=a.plano_id JOIN empresas e ON e.id=a.empresa_id WHERE a.status='ativo' AND p.tipo='banner' AND p.posicao_banner IS NOT NULL");
          foreach($stmtSlots->fetchAll() as $sl) $slotsOcupados[$sl['posicao_banner']] = $sl['nome_fantasia'];
          ?>
          <?php foreach(diretorio_banner_posicoes() as $slug => $label): $ocupado = isset($slotsOcupados[$slug]); ?>
          <div class="col-6 col-md-3">
            <div class="slot-card <?= $ocupado?'ocupado':'livre' ?>" onclick="<?= $ocupado?'':"abrirSlotLivre('".e($slug)."')" ?>">
              <?php if($ocupado): ?>
              <i class="bi bi-check-circle-fill" style="color:#f97316;font-size:1.2rem"></i>
              <div style="color:#f97316;font-size:.7rem;font-weight:700;margin-top:.3rem">OCUPADO</div>
              <div style="color:#92400e;font-size:.65rem"><?= e(mb_substr($slotsOcupados[$slug],0,14)) ?></div>
              <?php else: ?>
              <i class="bi bi-plus-circle" style="color:#94a3b8;font-size:1.2rem"></i>
              <div style="color:#94a3b8;font-size:.7rem;margin-top:.3rem;line-height:1.25"><?= e($label) ?></div>
              <div style="color:#64748b;font-size:.65rem">Disponível</div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row g-3">
        <?php foreach($planosBanner as $p):
          $slotOcupado = isset($slotsOcupados[$p['posicao_banner']]);
          $beneficios = explode(';', $p['beneficios'] ?? '');
        ?>
        <div class="col-md-6 col-xl-4">
          <div class="plano-card" style="<?= $slotOcupado?'opacity:.5':'' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold"><?= e($p['nome']) ?></div>
              <?php if($slotOcupado): ?>
              <span class="badge bg-secondary" style="font-size:.7rem">Indisponível</span>
              <?php else: ?>
              <span class="badge bg-success" style="font-size:.7rem">Disponível</span>
              <?php endif; ?>
            </div>
            <div class="plano-preco mb-2"><sup>R$</sup><?= number_format($p['preco'],2,',','.') ?><small>/<?= $p['duracao_dias'] ?> dias</small></div>
            <?php foreach($beneficios as $b): if(trim($b)): ?>
            <div class="beneficio"><i class="bi bi-check-circle-fill"></i><?= e(trim($b)) ?></div>
            <?php endif; endforeach; ?>
            <button class="btn w-100 mt-3 fw-bold <?= $slotOcupado?'btn-secondary disabled':'btn-warning' ?>"
                    <?= $slotOcupado?'disabled':'' ?>
                    onclick="abrirModal(<?= $p['id'] ?>, '<?= e($p['nome']) ?>', '<?= number_format($p['preco'],2,',','.') ?>', 'banner', '<?= e($p['posicao_banner']) ?>')">
              <?= $slotOcupado ? 'Indisponível' : 'Comprar slot' ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- MINHAS ASSINATURAS -->
  <?php if($assinaturas): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Meus pedidos e assinaturas</div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Plano</th><th>Tipo</th><th>Valor</th><th>Status</th><th>Validade</th><th>Banner</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($assinaturas as $a):
            $diasParaVencer = $a['data_fim'] ? (int) ceil((strtotime($a['data_fim']) - strtotime(date('Y-m-d'))) / 86400) : null;
            $vencendoOuVencido = $a['status'] === 'expirado' || ($a['status'] === 'ativo' && $diasParaVencer !== null && $diasParaVencer <= 7);
          ?>
          <tr>
            <td class="fw-semibold"><?= e($a['plano_nome']) ?></td>
            <td><span class="badge <?= $a['plano_tipo']==='destaque'?'bg-warning text-dark':'bg-primary' ?>"><?= ucfirst($a['plano_tipo']) ?></span></td>
            <td>R$ <?= number_format($a['valor_pago'],2,',','.') ?></td>
            <td>
              <?php $cores=['pendente'=>'warning','ativo'=>'success','expirado'=>'secondary','cancelado'=>'danger']; ?>
              <span class="badge bg-<?= $cores[$a['status']] ?>"><?= ucfirst($a['status']) ?></span>
              <?php if($a['status']==='ativo' && $diasParaVencer !== null && $diasParaVencer <= 7 && $diasParaVencer >= 0): ?>
              <div class="small text-warning mt-1">Vence em <?= $diasParaVencer ?> dia<?= $diasParaVencer===1?'':'s' ?></div>
              <?php elseif($a['status']==='expirado'): ?>
              <div class="small text-muted mt-1">Anúncio fora do ar</div>
              <?php endif; ?>
            </td>
            <td class="small text-muted">
              <?= $a['data_inicio'] ? date('d/m/Y',strtotime($a['data_inicio'])).' até '.date('d/m/Y',strtotime($a['data_fim'])) : '—' ?>
            </td>
            <td>
              <?php if($a['plano_tipo']==='banner'): ?>
                <?php if($a['banner_id'] && $a['status']==='ativo'): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="abrirUploadBanner(<?= $a['banner_id'] ?>)">
                  <i class="bi bi-upload me-1"></i><?= $a['banner_imagem']?'Atualizar':'Enviar banner' ?>
                </button>
                <?php if($a['banner_aprovado']): ?>
                <span class="badge bg-success ms-1">Aprovado</span>
                <?php elseif($a['banner_imagem']): ?>
                <span class="badge bg-warning text-dark ms-1">Aguardando</span>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <?php if($a['status'] !== 'cancelado'): ?>
              <button class="btn btn-sm <?= $vencendoOuVencido ? 'btn-warning' : 'btn-outline-secondary' ?>"
                      onclick="abrirModal(<?= (int)$a['plano_id'] ?>, <?= json_encode($a['plano_nome']) ?>, '<?= number_format($a['valor_pago'],2,',','.') ?>', '<?= $a['plano_tipo'] ?>', <?= json_encode($a['posicao_banner'] ?? '') ?>)">
                <i class="bi bi-arrow-repeat me-1"></i>Renovar
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal contratar -->
<div class="modal fade" id="modalContratar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Contratar: <span id="modalNomePlano"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formContratar">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-info small">
            <i class="bi bi-shield-check me-1"></i>
            Você será levado pro checkout seguro. Assim que o pagamento for confirmado, o plano é
            ativado automaticamente — sem precisar de aprovação manual. Cobrança recorrente: se não
            renovar até o vencimento, o anúncio some sozinho do diretório.
          </div>
          <div class="mb-3 p-3 rounded text-center" style="background:#f8fafc">
            <div class="text-muted small">Valor a pagar</div>
            <div style="font-size:1.8rem;font-weight:900;color:#0f172a">R$ <span id="modalPrecoPlano"></span></div>
            <div class="text-muted small">Pagamento via PIX, cartão ou boleto (InfinitePay)</div>
          </div>

          <div class="mb-3" id="campoBannerTitulo" style="display:none">
            <label class="form-label fw-semibold small">Título do banner</label>
            <input type="text" name="banner_titulo" class="form-control" placeholder="Ex: TechFix — Consertamos seu celular">
          </div>
          <div class="mb-3" id="campoBannerLink" style="display:none">
            <label class="form-label fw-semibold small">URL de destino do banner</label>
            <input type="url" name="banner_link" class="form-control" placeholder="https://...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-credit-card me-1"></i>Ir para pagamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal upload banner -->
<div class="modal fade" id="modalUploadBanner" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Enviar criativo do banner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="formUploadBanner" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Após enviar, o banner aguardará aprovação do Master Admin antes de ser exibido.</div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Título do banner</label>
            <input type="text" name="banner_titulo" class="form-control" placeholder="Ex: TechFix — Consertamos tudo!">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Imagem do banner</label>
            <input type="file" name="banner_img" class="form-control" accept="image/*">
            <div class="form-text">Tamanho recomendado: 300×100px. PNG, JPG ou WebP.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">URL de destino</label>
            <input type="url" name="banner_link" class="form-control" placeholder="https://...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-upload me-1"></i>Enviar para aprovação</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModal(planoId, nome, preco, tipo, posicao) {
  document.getElementById('modalNomePlano').textContent = nome;
  document.getElementById('modalPrecoPlano').textContent = preco;
  document.getElementById('formContratar').action = '<?= url('/empresa/publicidade/contratar/') ?>' + planoId;
  document.getElementById('campoBannerTitulo').style.display = tipo === 'banner' ? 'block' : 'none';
  document.getElementById('campoBannerLink').style.display   = tipo === 'banner' ? 'block' : 'none';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalContratar')).show();
}

function abrirModalBanner(bannerId) {
  document.getElementById('formUploadBanner').action = '<?= url('/empresa/publicidade/banner/') ?>' + bannerId;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUploadBanner')).show();
}

function abrirUploadBanner(bannerId) { abrirModalBanner(bannerId); }

// Planos de banner indexados pelo slug da posição (ver diretorio_banner_posicoes())
const planosPorPosicao = {
  <?php foreach($planosBanner as $p): ?>
  <?= json_encode($p['posicao_banner']) ?>: { id: <?= (int)$p['id'] ?>, nome: <?= json_encode($p['nome']) ?>, preco: '<?= number_format($p['preco'],2,',','.') ?>', posicao: <?= json_encode($p['posicao_banner']) ?> },
  <?php endforeach; ?>
};

// Clique em slot livre → abre o modal de contratação do plano daquele slot
function abrirSlotLivre(slot) {
  const p = planosPorPosicao[slot];
  if (!p) {
    alert('Não há plano de banner disponível para esta posição no momento.');
    return;
  }
  abrirModal(p.id, p.nome, p.preco, 'banner', p.posicao);
}
</script>
