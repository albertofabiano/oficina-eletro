<?php $titulo = 'Perfil Público no Diretório'; ?>
<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$slug    = $empresa['slug'] ?? '';
$urlPublica = $slug ? "$baseUrl/assistencias/$slug" : null;
?>

<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">Perfil Público no Diretório</h4>
      <p class="text-muted small mb-0">Configure como sua empresa aparece no diretório público de assistências técnicas.</p>
    </div>
  </div>

  <?php if ($urlPublica): ?>
  <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#0d6efd,#0b5ed7)">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h5 class="fw-bold mb-1 text-white"><i class="bi bi-globe2 me-2"></i>Veja sua empresa na internet</h5>
        <p class="mb-2 text-white-50">Alcance mais clientes, de graça — compartilhe esse link com quem procura assistência técnica.</p>
        <code style="background:rgba(255,255,255,.18);color:#fff;padding:.3rem .65rem;border-radius:6px;font-size:.85rem"><?= e($urlPublica) ?></code>
      </div>
      <a href="<?= e($urlPublica) ?>" target="_blank" class="btn btn-light fw-bold text-nowrap" style="padding:.75rem 1.5rem">
        <i class="bi bi-box-arrow-up-right me-1"></i>Ver minha página
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($empresa['nome_fantasia'])): ?>
  <div class="alert d-flex align-items-center gap-2" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412">
    <i class="bi bi-arrow-down-circle-fill fs-5"></i>
    <div><strong>Falta pouco!</strong> Preencha os dados da sua empresa abaixo (nome, descrição, horário) e ative <strong>“Aparecer no diretório público”</strong> para publicar.</div>
  </div>
  <?php endif; ?>

  <?php
    $emDestaque = (($empresa['diretorio_destaque'] ?? 'none') !== 'none')
                  && (empty($empresa['diretorio_destaque_ate']) || $empresa['diretorio_destaque_ate'] >= date('Y-m-d'));
  ?>
  <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid <?= $emDestaque ? '#16a34a' : '#f97316' ?>!important">
    <div class="card-body">
      <?php if($emDestaque): ?>
      <div class="d-flex align-items-center gap-3">
        <i class="bi bi-star-fill" style="color:#f59e0b;font-size:1.9rem"></i>
        <div>
          <h6 class="fw-bold mb-1" style="color:#16a34a"><i class="bi bi-check-circle-fill me-1"></i>Seu perfil está em DESTAQUE!</h6>
          <p class="text-muted small mb-0">Sua empresa aparece no topo das buscas do diretório com selo de destaque. 🎉</p>
        </div>
      </div>
      <?php else: ?>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div style="flex:1;min-width:240px">
          <h6 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-1"></i>Apareça em destaque no diretório — é grátis</h6>
          <p class="text-muted small mb-2">Fique no <strong>topo das buscas</strong> da sua cidade, com selo de destaque, e seja encontrado antes dos concorrentes. Sem custo.</p>
          <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
            <span class="badge bg-light text-dark border"><i class="bi bi-arrow-up-circle text-warning me-1"></i>Topo das buscas</span>
            <span class="badge bg-light text-dark border"><i class="bi bi-patch-check-fill text-warning me-1"></i>Selo de destaque</span>
            <span class="badge bg-light text-dark border"><i class="bi bi-eye-fill text-warning me-1"></i>Mais visitas</span>
          </div>
        </div>
        <form method="POST" action="<?= url('/empresa/perfil-publico/destaque') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-warning fw-bold text-nowrap" style="padding:.7rem 1.3rem">
            <i class="bi bi-star-fill me-1"></i>Ativar destaque grátis
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$planoCompleto && !empty($empresa['reivindicada'])): ?>
  <div class="alert d-flex align-items-center gap-2" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155">
    <i class="bi bi-info-circle-fill fs-5" style="color:#64748b"></i>
    <div><strong>Seu perfil é grátis</strong> — e, por isso, pode exibir um anúncio de outra empresa parceira do
    FixaOS na sua página pública. Ao assinar qualquer plano do FixaOS, seu perfil fica sem anúncio, além de
    liberar edição completa (cidade, serviços, foto de capa e estatísticas de visitas).
    <a href="<?= url('/planos') ?>" class="fw-semibold">Ver planos</a>.</div>
  </div>
  <?php endif; ?>

  <?php $ok=flash('success');$err=flash('error');$warn=flash('warning');
  if($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif;
  if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif;
  if($warn): ?><div class="alert alert-warning"><?= e($warn) ?></div><?php endif; ?>

  <!-- Fotos da empresa -->
  <div class="card border-0 shadow-sm mb-4" id="fotos">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
        <h5 class="fw-bold mb-0"><i class="bi bi-images text-primary me-1"></i>Fotos da empresa</h5>
        <span class="badge bg-light text-dark border"><?= count($fotos) ?>/4</span>
      </div>
      <p class="text-muted small mb-3">Mostre sua loja, bancada e trabalhos. A <strong>principal</strong> aparece em destaque; as outras viram carrossel. Perfis com fotos passam <strong>muito mais confiança</strong> e se destacam dos gratuitos.</p>

      <div class="row g-3">
        <?php foreach($fotos as $ft): ?>
        <div class="col-6 col-md-3">
          <div class="position-relative border rounded overflow-hidden" style="aspect-ratio:1/1;background:#f1f5f9">
            <img src="<?= url('/uploads/fotos/'.e($ft['arquivo'])) ?>" style="width:100%;height:100%;object-fit:cover" alt="Foto da empresa">
            <?php if($ft['principal']): ?>
            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-1"><i class="bi bi-star-fill"></i> Principal</span>
            <?php endif; ?>
            <div class="position-absolute bottom-0 start-0 end-0 d-flex gap-1 p-1" style="background:linear-gradient(transparent,rgba(0,0,0,.55))">
              <?php if(!$ft['principal']): ?>
              <form method="POST" action="<?= url('/empresa/fotos/'.$ft['id'].'/principal') ?>" class="flex-grow-1">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light w-100 py-0" title="Definir como principal"><i class="bi bi-star"></i></button>
              </form>
              <?php endif; ?>
              <form method="POST" action="<?= url('/empresa/fotos/'.$ft['id'].'/remover') ?>" onsubmit="return confirm('Remover esta foto?')" class="<?= $ft['principal']?'flex-grow-1':'' ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-danger w-100 py-0" title="Remover foto"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if(count($fotos) < 4): ?>
        <div class="col-6 col-md-3">
          <form method="POST" action="<?= url('/empresa/fotos') ?>" enctype="multipart/form-data" id="formFoto">
            <?= csrf_field() ?>
            <label class="border rounded d-flex flex-column align-items-center justify-content-center text-muted" style="aspect-ratio:1/1;cursor:pointer;border-style:dashed!important;background:#f8fafc">
              <i class="bi bi-plus-lg fs-3"></i>
              <span class="small">Adicionar foto</span>
              <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="document.getElementById('formFoto').submit()">
            </label>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>JPG, PNG ou WebP até 4MB. Máximo de 4 fotos.</div>
    </div>
  </div>

  <!-- Minhas avaliações -->
  <div class="card border-0 shadow-sm mb-4" id="avaliacoes">
    <div class="card-body">
      <h5 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-1"></i>Minhas avaliações</h5>
      <p class="text-muted small mb-3">As <strong>verificadas</strong> vêm de clientes atendidos de verdade (Ordem de Serviço real). Você pode <strong>responder</strong> publicamente ou <strong>contestar</strong> uma avaliação injusta — ela sai do ar até a moderação analisar.</p>

      <?php if(empty($avaliacoes)): ?>
      <div class="text-muted small border rounded p-3 bg-light">
        Você ainda não recebeu avaliações. Quando entregar uma OS, o cliente pode avaliar pela página de acompanhamento — e a nota entra aqui com selo <span class="badge bg-success">✓ verificada</span>.
      </div>
      <?php else: foreach($avaliacoes as $av): ?>
      <div class="border rounded p-3 mb-2 <?= $av['situacao']==='contestada' ? 'border-warning bg-warning-subtle' : ($av['situacao']==='oculta'?'border-secondary bg-light':'') ?>">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
          <div>
            <span style="color:#f59e0b"><?php for($i=1;$i<=5;$i++) echo $i<=$av['nota']?'★':'☆'; ?></span>
            <strong class="ms-1"><?= e($av['nome']) ?></strong>
            <?php if(!empty($av['verificada'])): ?><span class="badge bg-success ms-1"><i class="bi bi-patch-check-fill"></i> Verificada</span><?php endif; ?>
            <?php if($av['situacao']==='contestada'): ?><span class="badge bg-warning text-dark ms-1">Em análise (oculta)</span><?php endif; ?>
            <?php if($av['situacao']==='oculta'): ?><span class="badge bg-secondary ms-1">Removida pela moderação</span><?php endif; ?>
          </div>
          <span class="text-muted small"><?= date('d/m/Y', strtotime($av['criado_em'])) ?></span>
        </div>
        <?php if($av['comentario']): ?><div class="mt-1" style="color:#374151"><?= e($av['comentario']) ?></div><?php endif; ?>
        <?php if($av['situacao']==='contestada' && !empty($av['contestacao_motivo'])): ?>
        <div class="small text-muted mt-1"><i class="bi bi-flag-fill text-warning"></i> Contestada: <?= e($av['contestacao_motivo']) ?></div>
        <?php endif; ?>

        <!-- Responder -->
        <form method="POST" action="<?= url('/empresa/avaliacoes/'.$av['id'].'/responder') ?>" class="mt-2">
          <?= csrf_field() ?>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-reply-fill"></i></span>
            <input type="text" name="resposta" class="form-control" maxlength="1000" placeholder="Responder publicamente..." value="<?= e($av['resposta'] ?? '') ?>">
            <button class="btn btn-outline-primary" type="submit"><?= !empty($av['resposta'])?'Atualizar':'Responder' ?></button>
          </div>
        </form>

        <!-- Contestar -->
        <?php if($av['situacao'] !== 'contestada' && $av['situacao'] !== 'oculta'): ?>
        <details class="mt-2">
          <summary class="small text-danger" style="cursor:pointer"><i class="bi bi-flag"></i> Contestar avaliação injusta</summary>
          <form method="POST" action="<?= url('/empresa/avaliacoes/'.$av['id'].'/contestar') ?>" class="mt-2">
            <?= csrf_field() ?>
            <textarea name="motivo" class="form-control form-control-sm mb-1" rows="2" maxlength="1000" placeholder="Explique por que é injusta (ex: nunca foi cliente, engano, ofensa...). A moderação vai analisar." required></textarea>
            <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Contestar esta avaliação? Ela ficará oculta do público até a moderação decidir.')">Enviar contestação</button>
          </form>
        </details>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <form method="POST" action="<?= url('/empresa/perfil-publico') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">

      <!-- Logo -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-bold">Logo</div>
          <div class="card-body d-flex flex-column gap-3">
            <div id="logoPreviewWrap">
              <?php if(!empty($empresa['logo'])): ?>
              <img id="logoPreview" src="<?= url('/uploads/' . e($empresa['logo'])) ?>"
                   class="rounded" style="width:100%;height:140px;object-fit:contain;background:#f8fafc;display:block" alt="Logo">
              <?php else: ?>
              <div id="logoPlaceholder" class="rounded d-flex align-items-center justify-content-center"
                   style="height:140px;background:#f1f5f9;border:2px dashed #cbd5e1">
                <div class="text-center text-muted small">
                  <i class="bi bi-image fs-3 d-block mb-1"></i>Sem logo
                </div>
              </div>
              <img id="logoPreview" src="" class="rounded" style="width:100%;height:140px;object-fit:contain;background:#f8fafc;display:none" alt="Preview">
              <?php endif; ?>
            </div>
            <input type="file" name="logo" id="logoInput"
                   class="form-control form-control-sm"
                   accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif"
                   onchange="previewLogo(this)">
            <div class="form-text">JPG, PNG, SVG ou WebP até 2MB.</div>
          </div>
        </div>
      </div>

      <!-- Identificação e apresentação -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-bold"><i class="bi bi-shop-window me-1 text-primary"></i>Identificação da empresa</div>
          <div class="card-body d-flex flex-column gap-3">
            <div>
              <label class="form-label fw-semibold small">Nome da empresa <span class="text-danger">*</span></label>
              <input type="text" name="nome_fantasia" class="form-control" required maxlength="100"
                     value="<?= e($empresa['nome_fantasia'] ?? '') ?>" placeholder="Ex.: Timetec Assistência Técnica">
            </div>
            <div>
              <label class="form-label fw-semibold small">Descrição pública</label>
              <textarea name="descricao_publica" id="descricaoPublica" class="form-control" rows="4" style="overflow:hidden;resize:none"
                placeholder="Descreva sua assistência: o que você conserta, anos de experiência, diferenciais..."><?= e($empresa['descricao_publica'] ?? '') ?></textarea>
              <div class="form-text">Aparece na sua página e ajuda o Google a entender o que você faz.</div>
            </div>
            <div>
              <label class="form-label fw-semibold small"><i class="bi bi-clock-fill text-primary me-1"></i>Horário de funcionamento</label>
              <div id="horarioEditor" class="border rounded p-2 d-flex flex-column gap-1">
                <?php
                  $diasSemana = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
                  foreach ($diasSemana as $dk => $dLabel): ?>
                <div class="d-flex align-items-center gap-2 py-1" data-dia="<?= $dk ?>">
                  <div class="form-check form-switch mb-0" style="width:110px">
                    <input class="form-check-input dia-aberto" type="checkbox" role="switch" id="dia-<?= $dk ?>-sw">
                    <label class="form-check-label small" for="dia-<?= $dk ?>-sw"><?= $dLabel ?></label>
                  </div>
                  <input type="time" class="form-control form-control-sm dia-abre" style="width:110px">
                  <span class="text-muted small dia-ate-label">às</span>
                  <input type="time" class="form-control form-control-sm dia-fecha" style="width:110px">
                  <span class="text-muted small dia-fechado-label d-none">Fechado</span>
                </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="horario_funcionamento" id="horarioHidden" value="<?= e($empresa['horario_funcionamento'] ?? '') ?>">
              <div class="form-text">Desmarque os dias em que sua empresa não funciona.</div>
            </div>
            <div>
              <label class="form-label fw-semibold small"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp público</label>
              <input type="text" name="whatsapp_publico" class="form-control" placeholder="(11) 99999-9999"
                value="<?= e($empresa['whatsapp_publico'] ?? '') ?>">
              <div class="form-text">É o número que aparece no botão "Chamar no WhatsApp" da sua página.</div>
            </div>
          </div>
        </div>
      </div>

      <?php if($planoCompleto): ?>
      <!-- Cidade/UF, capa e redes sociais — só p/ quem tem plano ativo -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-bold">Foto de capa</div>
          <div class="card-body d-flex flex-column gap-3">
            <div id="capaPreviewWrap">
              <?php if($empresa['foto_capa']): ?>
              <img id="capaPreview" src="<?= url('/uploads/' . e($empresa['foto_capa'])) ?>"
                   class="rounded" style="width:100%;height:140px;object-fit:cover;display:block" alt="Capa">
              <?php else: ?>
              <div id="capaPlaceholder" class="rounded d-flex align-items-center justify-content-center"
                   style="height:140px;background:#f1f5f9;border:2px dashed #cbd5e1">
                <div class="text-center text-muted small">
                  <i class="bi bi-image fs-3 d-block mb-1"></i>Sem foto de capa
                </div>
              </div>
              <img id="capaPreview" src="" class="rounded" style="width:100%;height:140px;object-fit:cover;display:none" alt="Preview">
              <?php endif; ?>
            </div>
            <input type="file" name="foto_capa" id="fotoCapaInput"
                   class="form-control form-control-sm"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   onchange="previewCapa(this)">
            <div class="form-text">Recomendado: <strong>1200×400px</strong>.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-bold d-flex align-items-center justify-content-between">
            <span><i class="bi bi-globe2 me-1 text-primary"></i>Cidade, site e redes sociais</span>
            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Plano completo</span>
          </div>
          <div class="card-body d-flex flex-column gap-3">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold small">Cidade <span class="text-danger">*</span></label>
                <input type="text" name="cidade" class="form-control" maxlength="80"
                  value="<?= e($empresa['cidade'] ?? '') ?>" placeholder="Ex.: São Paulo">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small">UF</label>
                <input type="text" name="uf" class="form-control text-uppercase" maxlength="2"
                  value="<?= e($empresa['uf'] ?? '') ?>" placeholder="SP">
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold small">Especialidades</label>
              <div id="tagsBox" class="d-flex flex-wrap align-items-center gap-2 border rounded p-2">
                <span id="tagsLista" class="d-flex flex-wrap gap-2"></span>
                <input type="text" id="tagInput" class="form-control form-control-sm border-0 shadow-none flex-grow-1"
                       style="min-width:140px;width:auto" placeholder="Digite e aperte Enter..." maxlength="30">
              </div>
              <input type="hidden" name="especialidades" id="tagsHidden" value="<?= e($empresa['especialidades'] ?? '') ?>">
              <div class="form-text">Aparecem como tags na sua página e na listagem do diretório. Digite e aperte Enter (ou vírgula) para adicionar.</div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-globe2 text-primary me-1"></i>Site</label>
                <input type="url" name="site_url" class="form-control" placeholder="https://meusite.com.br"
                  value="<?= e($empresa['site_url'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-envelope-fill text-secondary me-1"></i>E-mail público</label>
                <input type="email" name="email_publico" class="form-control" placeholder="contato@suaempresa.com.br"
                  value="<?= e($empresa['email_publico'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-instagram text-danger me-1"></i>Instagram</label>
                <div class="input-group">
                  <span class="input-group-text">@</span>
                  <input type="text" name="instagram" class="form-control" placeholder="minhaassistencia"
                    value="<?= e(ltrim($empresa['instagram'] ?? '', '@')) ?>">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-facebook text-primary me-1"></i>Facebook</label>
                <input type="text" name="facebook" class="form-control" placeholder="facebook.com/suaempresa"
                  value="<?= e($empresa['facebook'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-youtube text-danger me-1"></i>YouTube</label>
                <input type="text" name="youtube" class="form-control" placeholder="youtube.com/@seucanal"
                  value="<?= e($empresa['youtube'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><i class="bi bi-tiktok me-1"></i>TikTok</label>
                <div class="input-group">
                  <span class="input-group-text">@</span>
                  <input type="text" name="tiktok" class="form-control" placeholder="suaempresa"
                    value="<?= e(ltrim($empresa['tiktok'] ?? '', '@')) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Serviços oferecidos -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <span class="fw-bold">Serviços oferecidos</span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addServico()">
              <i class="bi bi-plus-lg"></i> Adicionar
            </button>
          </div>
          <div class="card-body">
            <div id="servicosLista" class="d-flex flex-column gap-2">
              <?php
              $iconesOpc = ['bi-tools','bi-phone','bi-laptop','bi-tv','bi-snow','bi-water','bi-box2','bi-wind','bi-printer','bi-joystick','bi-cpu','bi-tablet','bi-headphones'];
              foreach($servicos as $s): ?>
              <div class="serv-row d-flex gap-2 align-items-center">
                <select name="serv_icone[]" class="form-select form-select-sm" style="width:130px">
                  <?php foreach($iconesOpc as $ic): ?>
                  <option value="<?= $ic ?>" <?= $s['icone']===$ic?'selected':'' ?>><?= $ic ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="serv_nome[]" class="form-control form-control-sm" value="<?= e($s['nome']) ?>" placeholder="Ex: Troca de tela">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.serv-row').remove()">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if(!$servicos): ?>
            <div class="text-muted small text-center py-2" id="emptyServ">Nenhum serviço cadastrado. Clique em Adicionar.</div>
            <?php endif; ?>
            <div class="mt-3">
              <div class="text-muted small mb-2">Adicionar rapidamente:</div>
              <div class="d-flex flex-wrap gap-1">
                <?php foreach(['Celular/Smartphone','Notebook','TV','Geladeira','Ar Condicionado','Máquina de Lavar','Tablet','Impressora','Videogame','Micro-ondas'] as $sg): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.2rem .55rem" onclick="addServicoRapido('<?= $sg ?>')">
                  + <?= $sg ?>
                </button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Visitas ao perfil -->
      <?php $vTotal = (int)($visitas['total'] ?? 0); ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Visitas ao seu perfil no diretório</h6>
              <span class="badge bg-light text-muted border">Contamos só perfis reivindicados</span>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-4">
                <div class="p-2 rounded text-center" style="background:#f8fafc">
                  <div class="text-muted small">Total</div>
                  <div class="fw-bold" style="font-size:1.7rem;color:#0f172a"><?= number_format($vTotal,0,',','.') ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded text-center" style="background:#f8fafc">
                  <div class="text-muted small">Últimos 30 dias</div>
                  <div class="fw-bold" style="font-size:1.7rem;color:#0f172a"><?= number_format((int)($visitas['mes']??0),0,',','.') ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded text-center" style="background:#f8fafc">
                  <div class="text-muted small">Hoje</div>
                  <div class="fw-bold" style="font-size:1.7rem;color:#0f172a"><?= (int)($visitas['hoje']??0) ?></div>
                </div>
              </div>
            </div>
            <?php if($vTotal > 0): ?>
            <div style="height:170px"><canvas id="chartVisitas"></canvas></div>
            <?php else: ?>
            <div class="text-center text-muted small py-3">
              <i class="bi bi-eye-slash d-block mb-1" style="font-size:1.4rem"></i>
              Seu perfil ainda não recebeu visitas registradas. Compartilhe o link acima para começar a aparecer!
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php if($vTotal > 0): ?>
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
      <script>
      (function(){
        var ctx = document.getElementById('chartVisitas');
        if(!ctx || typeof Chart === 'undefined') return;
        new Chart(ctx, {
          type:'line',
          data:{ labels: <?= json_encode($visitas['labels'] ?? []) ?>,
            datasets:[{ data: <?= json_encode($visitas['dados'] ?? []) ?>,
              borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,.12)', fill:true, tension:.3, pointRadius:2 }] },
          options:{ responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false} },
            scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
        });
      })();
      </script>
      <?php endif; ?>
      <?php else: ?>
      <!-- Teaser do plano completo -->
      <div class="col-12">
        <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#fff7ed,#fff);border:1px dashed #fdba74!important">
          <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div style="flex:1;min-width:260px">
              <h6 class="fw-bold mb-1"><i class="bi bi-unlock-fill text-warning me-1"></i>Desbloqueie a edição completa do perfil</h6>
              <p class="text-muted small mb-2">Assinando um plano da FixaOS, sua empresa também libera:</p>
              <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
                <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt text-warning me-1"></i>Cidade/UF editável</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-image text-warning me-1"></i>Foto de capa</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-share text-warning me-1"></i>Site e redes sociais</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-tools text-warning me-1"></i>Lista de serviços</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-graph-up-arrow text-warning me-1"></i>Contagem de visitas</span>
              </div>
            </div>
            <a href="<?= url('/planos') ?>" target="_top" class="btn btn-warning fw-bold text-nowrap" style="padding:.7rem 1.3rem">
              <i class="bi bi-stars me-1"></i>Ver planos da FixaOS
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Visibilidade -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="fw-bold">Aparecer no diretório público</div>
                <div class="text-muted small">Quando ativado, sua empresa aparece nas buscas do diretório e pode ser encontrada no Google.</div>
              </div>
              <div class="form-check form-switch ms-3">
                <input class="form-check-input" type="checkbox" name="listagem_publica" value="1" id="listPublica"
                       <?= $empresa['listagem_publica'] ? 'checked' : '' ?> style="width:3rem;height:1.5rem">
              </div>
            </div>
            <?php if($urlPublica): ?>
            <div class="mt-3 p-2 rounded" style="background:#f0f9ff;border:1px solid #bae6fd">
              <span class="text-muted small">Sua URL pública: </span>
              <a href="<?= $urlPublica ?>" target="_blank" style="color:#0369a1;font-size:.88rem;font-weight:600"><?= $urlPublica ?></a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary fw-bold px-5">
          <i class="bi bi-check-lg me-1"></i>Salvar perfil público
        </button>
        <?php if($urlPublica): ?>
        <a href="<?= $urlPublica ?>" target="_blank" class="btn btn-outline-secondary ms-2">
          <i class="bi bi-eye me-1"></i>Ver resultado
        </a>
        <?php endif; ?>
      </div>

    </div>
  </form>
</div>

<script>
(function () {
  var ta = document.getElementById('descricaoPublica');
  if (!ta) return;
  function autoResize() { ta.style.height = 'auto'; ta.style.height = (ta.scrollHeight + 2) + 'px'; }
  ta.addEventListener('input', autoResize);
  autoResize();
})();

(function () {
  var hidden = document.getElementById('horarioHidden');
  var editor = document.getElementById('horarioEditor');
  if (!hidden || !editor) return;

  var dadosSalvos = {};
  try {
    var parsed = JSON.parse(hidden.value);
    if (parsed && typeof parsed === 'object') dadosSalvos = parsed;
  } catch (e) { /* valor antigo em texto livre — ignora e começa do padrão */ }

  var linhas = editor.querySelectorAll('[data-dia]');
  linhas.forEach(function (linha) {
    var dia    = linha.dataset.dia;
    var sw     = linha.querySelector('.dia-aberto');
    var abre   = linha.querySelector('.dia-abre');
    var fecha  = linha.querySelector('.dia-fecha');
    var d      = dadosSalvos[dia];

    if (d && d.aberto) {
      sw.checked = true;
      abre.value  = d.abre  || '09:00';
      fecha.value = d.fecha || '18:00';
    } else if (!dadosSalvos || Object.keys(dadosSalvos).length === 0) {
      // Sem dado salvo ainda: sugere Seg-Sex 09h-18h como ponto de partida.
      sw.checked = ['seg','ter','qua','qui','sex'].includes(dia);
      abre.value  = '09:00';
      fecha.value = '18:00';
    } else {
      sw.checked = false;
      abre.value  = '09:00';
      fecha.value = '18:00';
    }
    atualizarLinha(linha);
    sw.addEventListener('change', function () { atualizarLinha(linha); sincronizar(); });
    abre.addEventListener('change', sincronizar);
    fecha.addEventListener('change', sincronizar);
  });

  function atualizarLinha(linha) {
    var aberto = linha.querySelector('.dia-aberto').checked;
    linha.querySelector('.dia-abre').classList.toggle('d-none', !aberto);
    linha.querySelector('.dia-fecha').classList.toggle('d-none', !aberto);
    linha.querySelector('.dia-ate-label').classList.toggle('d-none', !aberto);
    linha.querySelector('.dia-fechado-label').classList.toggle('d-none', aberto);
  }

  function sincronizar() {
    var out = {};
    linhas.forEach(function (linha) {
      var dia = linha.dataset.dia;
      var aberto = linha.querySelector('.dia-aberto').checked;
      out[dia] = aberto
        ? { aberto: true, abre: linha.querySelector('.dia-abre').value || '09:00', fecha: linha.querySelector('.dia-fecha').value || '18:00' }
        : { aberto: false };
    });
    hidden.value = JSON.stringify(out);
  }

  sincronizar();
})();

(function () {
  var hidden = document.getElementById('tagsHidden');
  var lista  = document.getElementById('tagsLista');
  var input  = document.getElementById('tagInput');
  if (!hidden || !lista || !input) return;

  var tags = hidden.value.split(',').map(function (t) { return t.trim().toLowerCase(); }).filter(Boolean);

  function render() {
    lista.innerHTML = '';
    tags.forEach(function (tag, i) {
      var chip = document.createElement('span');
      chip.className = 'd-flex align-items-center gap-1';
      chip.style.cssText = 'background:#f97316;color:#fff;border-radius:20px;font-size:.8rem;font-weight:600;padding:.35rem .6rem .35rem .75rem';
      var txt = document.createElement('span');
      txt.textContent = tag;
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-close btn-close-white';
      btn.style.fontSize = '.6rem';
      btn.setAttribute('aria-label', 'Remover');
      btn.onclick = function () { tags.splice(i, 1); render(); };
      chip.appendChild(txt);
      chip.appendChild(btn);
      lista.appendChild(chip);
    });
    hidden.value = tags.join(',');
  }

  function addFromInput() {
    var val = input.value.trim().toLowerCase();
    if (val && !tags.includes(val)) { tags.push(val); render(); }
    input.value = '';
  }

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addFromInput(); }
    else if (e.key === 'Backspace' && input.value === '' && tags.length) { tags.pop(); render(); }
  });
  input.addEventListener('blur', addFromInput);

  render();
})();

function previewLogo(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = function(e) {
    const preview = document.getElementById('logoPreview');
    const placeholder = document.getElementById('logoPlaceholder');
    if (placeholder) placeholder.style.display = 'none';
    preview.src = e.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

function previewCapa(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = function(e) {
    const preview = document.getElementById('capaPreview');
    const placeholder = document.getElementById('capaPlaceholder');
    if (placeholder) placeholder.style.display = 'none';
    preview.src = e.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

const iconesOpc = <?= json_encode(['bi-tools','bi-phone','bi-laptop','bi-tv','bi-snow','bi-water','bi-box2','bi-wind','bi-printer','bi-joystick','bi-cpu','bi-tablet','bi-headphones']) ?>;

function makeSelectIcone() {
  const sel = document.createElement('select');
  sel.name = 'serv_icone[]';
  sel.className = 'form-select form-select-sm';
  sel.style.width = '130px';
  iconesOpc.forEach(ic => {
    const opt = document.createElement('option');
    opt.value = ic; opt.textContent = ic;
    sel.appendChild(opt);
  });
  return sel;
}

function addServico(nome = '') {
  document.getElementById('emptyServ')?.remove();
  const row = document.createElement('div');
  row.className = 'serv-row d-flex gap-2 align-items-center';
  const inp = document.createElement('input');
  inp.type = 'text'; inp.name = 'serv_nome[]';
  inp.className = 'form-control form-control-sm';
  inp.placeholder = 'Ex: Troca de tela';
  inp.value = nome;
  const btn = document.createElement('button');
  btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger';
  btn.innerHTML = '<i class="bi bi-trash"></i>';
  btn.onclick = () => row.remove();
  row.appendChild(makeSelectIcone());
  row.appendChild(inp);
  row.appendChild(btn);
  document.getElementById('servicosLista').appendChild(row);
  inp.focus();
}

function addServicoRapido(nome) { addServico(nome); }
</script>
