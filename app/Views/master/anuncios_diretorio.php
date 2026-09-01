<?php $titulo = 'Anúncios do Diretório'; ?>
<div id="msmain">
  <div id="mstopbar" class="d-flex align-items-center justify-content-between">
    <div class="fw-bold text-white">Anúncios do Diretório</div>
    <small class="text-secondary">FixaOS Master</small>
  </div>
  <div class="p-4">

    <?php $ok=flash('success');$err=flash('error');
    if($ok): ?><div class="alert alert-success py-2 small"><?= e($ok) ?></div><?php endif;
    if($err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <?php foreach([
        ['Pendentes',   $kpis['pendentes'],  '#f59e0b','bi-hourglass-split'],
        ['Ativos',      $kpis['ativos'],     '#22c55e','bi-check-circle-fill'],
        ['Receita/mês', 'R$ '.number_format($kpis['receita'],2,',','.'), '#60a5fa','bi-currency-dollar'],
        ['Banners p/ aprovar', $kpis['banners_pendentes'], '#f97316','bi-image'],
      ] as[$l,$v,$c,$i]):?>
      <div class="col-6 col-md-3">
        <div class="ms-card p-3 d-flex align-items-center gap-3">
          <div style="width:42px;height:42px;border-radius:10px;background:<?= $c ?>22;display:flex;align-items:center;justify-content:center">
            <i class="bi <?= $i ?>" style="color:<?= $c ?>;font-size:1.2rem"></i>
          </div>
          <div>
            <div style="font-size:1.4rem;font-weight:900;color:#fff;line-height:1"><?= $v ?></div>
            <div style="color:#6b7280;font-size:.78rem"><?= $l ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" style="border-color:rgba(255,255,255,.1)">
      <?php foreach([
        ['assinaturas','Assinaturas'],
        ['banners','Banners para aprovar'],
        ['planos','Planos'],
      ] as[$t,$l]):?>
      <li class="nav-item">
        <button class="ms-tab-btn <?= ($tab??'assinaturas')===$t ? 'ms-tab-ativo' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab_<?= $t ?>"><?= $l ?>
          <?php if($t==='assinaturas' && $kpis['pendentes']>0): ?>
          <span class="badge ms-1" style="background:#f59e0b;color:#000;font-size:.65rem"><?= $kpis['pendentes'] ?></span>
          <?php endif; ?>
          <?php if($t==='banners' && $kpis['banners_pendentes']>0): ?>
          <span class="badge ms-1" style="background:#f97316;color:#fff;font-size:.65rem"><?= $kpis['banners_pendentes'] ?></span>
          <?php endif; ?>
        </button>
      </li>
      <?php endforeach; ?>
    </ul>

    <div class="tab-content">

      <!-- ASSINATURAS -->
      <div class="tab-pane fade show active" id="tab_assinaturas">
        <?php if($assinaturas): ?>
        <div class="ms-card overflow-hidden">
          <table class="table table-hover mb-0" style="--bs-table-bg:transparent;--bs-table-hover-bg:rgba(255,255,255,.03)">
            <thead><tr style="border-color:rgba(255,255,255,.08)">
              <th style="color:#9ca3af;font-size:.78rem">Empresa</th>
              <th style="color:#9ca3af;font-size:.78rem">Plano</th>
              <th style="color:#9ca3af;font-size:.78rem">Valor</th>
              <th style="color:#9ca3af;font-size:.78rem">Status</th>
              <th style="color:#9ca3af;font-size:.78rem">Período</th>
              <th style="color:#9ca3af;font-size:.78rem">Comprovante</th>
              <th style="color:#9ca3af;font-size:.78rem">Ações</th>
            </tr></thead>
            <tbody>
            <?php foreach($assinaturas as $a): ?>
            <tr style="border-color:rgba(255,255,255,.05)">
              <td style="color:#e2e8f0;font-weight:600"><?= e($a['empresa_nome']) ?></td>
              <td>
                <span style="color:#e2e8f0;font-size:.85rem"><?= e($a['plano_nome']) ?></span>
                <span class="badge ms-1 <?= $a['plano_tipo']==='destaque'?'bg-warning text-dark':'bg-primary' ?>" style="font-size:.65rem"><?= ucfirst($a['plano_tipo']) ?></span>
              </td>
              <td style="color:#4ade80;font-weight:700">R$ <?= number_format($a['valor_pago'],2,',','.') ?></td>
              <td>
                <?php $cores=['pendente'=>'#f59e0b','ativo'=>'#22c55e','expirado'=>'#6b7280','cancelado'=>'#ef4444']; ?>
                <span style="background:<?= $cores[$a['status']] ?>22;color:<?= $cores[$a['status']] ?>;border:1px solid <?= $cores[$a['status']] ?>44;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
                  <?= ucfirst($a['status']) ?>
                </span>
              </td>
              <td style="color:#9ca3af;font-size:.8rem">
                <?= $a['data_inicio'] ? date('d/m/Y',strtotime($a['data_inicio'])).' – '.date('d/m/Y',strtotime($a['data_fim'])) : '—' ?>
              </td>
              <td>
                <?php if($a['comprovante']): ?>
                <a href="<?= url('/uploads/'.$a['comprovante']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem">
                  <i class="bi bi-file-earmark-image"></i> Ver
                </a>
                <?php else: ?><span style="color:#4b5563;font-size:.8rem">—</span><?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <?php if($a['status']==='pendente'): ?>
                  <form method="POST" action="<?= url('/master/diretorio/assinatura/'.$a['id'].'/ativar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm fw-bold" style="background:#22c55e;color:#fff;border:none;font-size:.75rem">
                      <i class="bi bi-check-lg"></i> Ativar
                    </button>
                  </form>
                  <form method="POST" action="<?= url('/master/diretorio/assinatura/'.$a['id'].'/cancelar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm fw-bold" style="background:#ef4444;color:#fff;border:none;font-size:.75rem">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </form>
                  <?php elseif($a['status']==='ativo'): ?>
                  <form method="POST" action="<?= url('/master/diretorio/assinatura/'.$a['id'].'/cancelar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm fw-bold" style="background:#ef444422;color:#ef4444;border:1px solid #ef444444;font-size:.75rem">
                      Cancelar
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="ms-card p-5 text-center"><div style="color:#6b7280">Nenhuma assinatura ainda.</div></div>
        <?php endif; ?>
      </div>

      <!-- BANNERS -->
      <div class="tab-pane fade" id="tab_banners">
        <?php if($banners): ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach($banners as $b): ?>
          <div class="ms-card p-4">
            <div class="d-flex align-items-start gap-4 flex-wrap">
              <?php if($b['imagem']): ?>
              <img src="<?= url('/uploads/'.$b['imagem']) ?>" alt="Banner" style="height:80px;border-radius:8px;object-fit:contain;background:#1f2937">
              <?php else: ?>
              <div style="width:160px;height:80px;background:#1f2937;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#4b5563;font-size:.8rem">Sem imagem</div>
              <?php endif; ?>
              <div style="flex:1">
                <div style="color:#fff;font-weight:700"><?= e($b['empresa_nome']) ?></div>
                <div style="color:#9ca3af;font-size:.82rem"><?= e(diretorio_banner_posicoes()[$b['posicao']] ?? $b['posicao']) ?> · <?= e($b['titulo']??'') ?></div>
                <?php if($b['link_url']): ?>
                <a href="<?= e($b['link_url']) ?>" target="_blank" style="color:#60a5fa;font-size:.8rem"><?= e($b['link_url']) ?></a>
                <?php endif; ?>
                <div style="margin-top:.4rem">
                  <span style="background:<?= $b['aprovado']?'#22c55e22':'#f59e0b22' ?>;color:<?= $b['aprovado']?'#22c55e':'#f59e0b' ?>;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
                    <?= $b['aprovado'] ? 'Aprovado' : 'Aguardando aprovação' ?>
                  </span>
                </div>
              </div>
              <div class="d-flex gap-2">
                <?php if(!$b['aprovado']): ?>
                <form method="POST" action="<?= url('/master/diretorio/banner/'.$b['id'].'/aprovar') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm fw-bold" style="background:#22c55e;color:#fff;border:none">
                    <i class="bi bi-check-lg me-1"></i>Aprovar
                  </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= url('/master/diretorio/banner/'.$b['id'].'/reprovar') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm fw-bold" style="background:#ef4444;color:#fff;border:none">
                    <i class="bi bi-x-lg me-1"></i>Reprovar
                  </button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="ms-card p-5 text-center"><div style="color:#6b7280">Nenhum banner aguardando aprovação.</div></div>
        <?php endif; ?>
      </div>

      <!-- PLANOS -->
      <div class="tab-pane fade" id="tab_planos">

        <!-- Botão novo plano -->
        <div class="d-flex justify-content-end mb-3">
          <button class="btn btn-sm fw-bold" style="background:#f97316;color:#fff;border:none"
                  onclick="abrirModalPlano()">
            <i class="bi bi-plus-lg me-1"></i>Novo plano
          </button>
        </div>

        <div class="row g-3">
          <?php foreach($planos as $p): ?>
          <div class="col-md-6">
            <div class="ms-card p-3">
              <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div style="color:#fff;font-weight:700"><?= e($p['nome']) ?></div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge <?= $p['tipo']==='destaque'?'bg-warning text-dark':'bg-primary' ?>"><?= ucfirst($p['tipo']) ?></span>
                  <form method="POST" action="<?= url('/master/diretorio/plano/'.$p['id'].'/toggle') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm" style="background:<?= $p['ativo']?'#22c55e22':'#ef444422' ?>;color:<?= $p['ativo']?'#22c55e':'#ef4444' ?>;border:1px solid;border-color:<?= $p['ativo']?'#22c55e44':'#ef444444' ?>;font-size:.72rem;font-weight:700">
                      <?= $p['ativo']?'Ativo':'Inativo' ?>
                    </button>
                  </form>
                  <button class="btn btn-sm" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);font-size:.72rem"
                          onclick="editarPlano(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <form method="POST" action="<?= url('/master/diretorio/plano/'.$p['id'].'/excluir') ?>"
                        onsubmit="return confirm('Excluir este plano?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm" style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);font-size:.72rem">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </form>
                </div>
              </div>
              <div style="color:#4ade80;font-size:1.4rem;font-weight:900">
                R$ <?= number_format($p['preco'],2,',','.') ?>
                <span style="color:#6b7280;font-size:.8rem;font-weight:400"> /<?= $p['duracao_dias'] ?> dias</span>
              </div>
              <div style="color:#9ca3af;font-size:.8rem;margin-top:.3rem"><?= e($p['descricao']) ?></div>
              <?php if($p['posicao_banner']): ?>
              <div style="color:#f97316;font-size:.78rem;margin-top:.3rem"><i class="bi bi-geo-alt me-1"></i><?= e(diretorio_banner_posicoes()[$p['posicao_banner']] ?? $p['posicao_banner']) ?></div>
              <?php endif; ?>
              <?php if($p['beneficios']): ?>
              <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.25rem">
                <?php foreach(explode(';',$p['beneficios']) as $b): if(trim($b)):?>
                <span style="background:rgba(34,197,94,.1);color:#4ade80;border-radius:4px;font-size:.68rem;padding:.1rem .4rem"><?= e(trim($b)) ?></span>
                <?php endif;endforeach;?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.ms-tab-btn{
  border:none;background:none;
  color:#6b7280;
  border-bottom:2px solid transparent;
  border-radius:0;padding:.6rem 1rem;
  font-weight:400;font-size:.88rem;
  transition:color .15s, border-color .15s;
  cursor:pointer;
}
.ms-tab-btn:hover{color:#e2e8f0}
.ms-tab-btn.ms-tab-ativo{
  color:#fff;font-weight:700;
  border-bottom:2px solid #f97316;
}
</style>

<!-- Modal Criar/Editar Plano -->
<div class="modal fade" id="modalPlano" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#141720;border:1px solid rgba(255,255,255,.08);color:#e0e0e0">
      <div class="modal-header" style="border-color:rgba(255,255,255,.08)">
        <h5 class="modal-title text-white fw-bold" id="modalPlanoTitulo">Novo Plano</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('/master/diretorio/planos') ?>" id="formPlano">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="planoId" value="0">
        <div class="modal-body d-flex flex-column gap-3">

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Nome do plano *</label>
              <input type="text" name="nome" id="planoNome" class="form-control" required placeholder="Ex: Destaque Premium">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Tipo *</label>
              <select name="tipo" id="planoTipo" class="form-select" onchange="toggleBannerPos()">
                <option value="destaque">Destaque</option>
                <option value="banner">Banner</option>
              </select>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Preço (R$) *</label>
              <input type="text" name="preco" id="planoPreco" class="form-control" required placeholder="49,90">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Duração (dias)</label>
              <input type="number" name="duracao_dias" id="planoDuracao" class="form-control" value="30" min="1">
            </div>
            <div class="col-md-4" id="campoPosicao">
              <label class="form-label small fw-semibold">Posição do banner</label>
              <select name="posicao_banner" id="planoPosicao" class="form-select">
                <option value="">—</option>
                <?php foreach(diretorio_banner_posicoes() as $slug => $label): ?>
                <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div>
            <label class="form-label small fw-semibold">Descrição</label>
            <textarea name="descricao" id="planoDescricao" class="form-control" rows="2" placeholder="Descreva o plano brevemente"></textarea>
          </div>

          <div>
            <label class="form-label small fw-semibold">Benefícios <span class="text-muted fw-normal">(separados por ponto e vírgula)</span></label>
            <textarea name="beneficios" id="planoBeneficios" class="form-control" rows="2"
              placeholder="Badge exclusivo;Posição no topo;Mais visibilidade"></textarea>
          </div>

          <div class="d-flex align-items-center gap-2">
            <input type="checkbox" name="ativo" id="planoAtivo" value="1" class="form-check-input" checked>
            <label class="form-check-label small" for="planoAtivo">Plano ativo (visível para empresas)</label>
          </div>

        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.08)">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm fw-bold" style="background:#f97316;color:#fff;border:none">
            <i class="bi bi-check-lg me-1"></i>Salvar plano
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalPlano() {
  document.getElementById('modalPlanoTitulo').textContent = 'Novo Plano';
  document.getElementById('formPlano').reset();
  document.getElementById('planoId').value = '0';
  document.getElementById('planoDuracao').value = '30';
  document.getElementById('planoAtivo').checked = true;
  toggleBannerPos();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPlano')).show();
}

function editarPlano(p) {
  document.getElementById('modalPlanoTitulo').textContent = 'Editar Plano';
  document.getElementById('planoId').value        = p.id;
  document.getElementById('planoNome').value      = p.nome;
  document.getElementById('planoTipo').value      = p.tipo;
  document.getElementById('planoPreco').value     = p.preco.toString().replace('.',',');
  document.getElementById('planoDuracao').value   = p.duracao_dias;
  document.getElementById('planoDescricao').value = p.descricao || '';
  document.getElementById('planoBeneficios').value= p.beneficios || '';
  document.getElementById('planoAtivo').checked   = p.ativo == 1;
  if(p.posicao_banner) document.getElementById('planoPosicao').value = p.posicao_banner;
  toggleBannerPos();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPlano')).show();
}

function toggleBannerPos() {
  const tipo = document.getElementById('planoTipo').value;
  document.getElementById('campoPosicao').style.display = tipo === 'banner' ? 'block' : 'none';
}

toggleBannerPos();

// Atualizar estado visual das abas ao clicar
document.querySelectorAll('.ms-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.ms-tab-btn').forEach(b => b.classList.remove('ms-tab-ativo'));
    btn.classList.add('ms-tab-ativo');
  });
});
</script>
