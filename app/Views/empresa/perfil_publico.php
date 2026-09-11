<?php $titulo = 'Perfil Público no Diretório'; ?>
<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$slug    = $empresa['slug'] ?? '';
$urlPublica = $slug ? "$baseUrl/assistencias/$slug" : null;
// Cor do banner/título na ficha pública do Diretório — substitui a antiga foto de capa
// (upload de imagem). Sem cor escolhida ainda, usa o mesmo azul-marinho que já era o padrão
// do gradiente da ficha pública (diretorio/empresa.php), pra quem nunca mexeu ver exatamente
// o resultado que já tinha antes de existir essa escolha.
$corCapaAtual = $empresa['cor_capa'] ?: '#1e3a5f';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<style>
/* Nome da empresa (70%) e WhatsApp público (30%) lado a lado — pedido do usuário. Bootstrap
   não tem uma dupla 70/30 nativa no grid de 12 colunas, então usa flex-basis direto; empilha
   em telas estreitas (mesmo breakpoint md=768px já usado no resto do card). */
.pp-nome-col, .pp-whats-col { flex: 0 0 100%; max-width: 100%; }
@media (min-width: 768px) {
  .pp-nome-col  { flex: 0 0 70%; max-width: 70%; }
  .pp-whats-col { flex: 0 0 30%; max-width: 30%; }
}

/* Bloco da Descrição pública destacado do resto do card — pedido do usuário: o campo mais
   "de conteúdo" da tela (com editor rico + IA) ficava visualmente igual a um campo de texto
   comum, sem se diferenciar de Nome/WhatsApp/Endereço ao redor. */
.pp-desc-destaque {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-left: 3px solid var(--accent);
  border-radius: .5rem;
  padding: .9rem 1rem 1rem;
}

/* Editor rico da Descrição pública — mesmo padrão do laudo técnico da OS (contenteditable
   + toolbar de execCommand), aqui pra permitir negrito/itálico/listas na apresentação da
   empresa no Diretório. */
#descricaoPublicaBox { border: 1px solid var(--border); border-radius: .375rem; overflow: hidden; background: var(--surface-1); }
#descricaoPublicaBox:focus-within { border-color: var(--accent); box-shadow: 0 0 0 .2rem var(--accent-bg); }
#descricaoPublicaToolbar { background: var(--surface-2); border-bottom: 1px solid var(--border); padding: .35rem .5rem; }
#descricaoPublicaToolbar .btn.active { background: var(--border); border-color: var(--border-strong); }
#descricaoPublicaTexto { border: 0; border-radius: 0; min-height: 90px; background: var(--surface-1); color: var(--text-1); }
#descricaoPublicaTexto:focus { box-shadow: none; }
#descricaoPublicaTexto[contenteditable]:empty:before { content: attr(data-placeholder); color: var(--text-3); }
#descricaoPublicaTexto b, #descricaoPublicaTexto strong { font-weight: 700; }
#descricaoPublicaTexto ul, #descricaoPublicaTexto ol { margin: 0; padding-left: 1.4rem; }

/* Botão "×" de excluir imagem — Logo (mesmo padrão visual já usado em "Fotos do estado de
   entrada" da OS: círculo vermelho sobre o canto da miniatura). Só aparece quando já existe
   uma imagem salva (condicional no PHP); some sozinho depois do reload que segue a remoção. */
.pp-btn-remover-img {
  position: absolute; top: -7px; right: -7px;
  background: #dc3545; color: #fff; border: none; border-radius: 50%;
  width: 22px; height: 22px; line-height: 20px; font-size: 15px; padding: 0;
  cursor: pointer;
}
.pp-btn-remover-img:hover { background: #b02a37; }
</style>

<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">Perfil Público no Diretório</h4>
      <p class="text-muted small mb-0">Configure como sua empresa aparece no diretório público de assistências técnicas.</p>
    </div>
  </div>

  <?php $ok=flash('success');$err=flash('error');$warn=flash('warning');
  if($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif;
  if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif;
  if($warn): ?><div class="alert alert-warning"><?= e($warn) ?></div><?php endif; ?>

  <?php if (empty($empresa['nome_fantasia'])): ?>
  <div class="alert d-flex align-items-center gap-2" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412">
    <i class="bi bi-arrow-down-circle-fill fs-5"></i>
    <div><strong>Falta pouco!</strong> Preencha os dados da sua empresa abaixo (nome, descrição, horário) e salve para publicar.</div>
  </div>
  <?php endif; ?>

  <!-- Conteúdo principal (Identificação/Especialidades/Serviços, dentro do form de salvar) à
       esquerda; mídia (Logo, Foto de capa, Fotos da empresa) empilhada na mesma coluna à
       direita — pedido do usuário: "coloque esse uploads de foto de capa e fotos da empresa
       na mesma coluna de upload de logo". Logo/Foto de capa continuam submetendo junto do
       form principal via o atributo HTML5 form="editarPerfilDiretorio" (não são mais
       descendentes do <form>, já que HTML não permite form aninhado dentro de outro — "Fotos
       da empresa" já não era descendente dele mesmo antes, tem forms próprios por foto). -->
  <div class="row g-4">

    <div class="col-lg-8">
      <form method="POST" action="<?= url('/empresa/perfil-publico') ?>" enctype="multipart/form-data" id="editarPerfilDiretorio">
        <?= csrf_field() ?>
        <div class="d-flex flex-column gap-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold"><i class="bi bi-shop-window me-1 text-primary"></i>Identificação da empresa</div>
          <div class="card-body d-flex flex-column gap-3">
            <div class="row g-3">
              <div class="pp-nome-col">
                <label class="form-label fw-semibold small">Nome da empresa <span class="text-danger">*</span></label>
                <input type="text" name="nome_fantasia" class="form-control" required maxlength="100"
                       value="<?= e($empresa['nome_fantasia'] ?? '') ?>" placeholder="Ex.: Timetec Assistência Técnica">
              </div>
              <div class="pp-whats-col">
                <label class="form-label fw-semibold small"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp público</label>
                <input type="text" name="whatsapp_publico" class="form-control" placeholder="(11) 99999-9999"
                  value="<?= e($empresa['whatsapp_publico'] ?? '') ?>">
                <div class="form-text">Botão "Chamar no WhatsApp" da sua página.</div>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold small"><i class="bi bi-geo-alt-fill text-primary me-1"></i>Endereço</label>
              <div class="row g-2">
                <div class="col-md-3">
                  <input type="text" name="cep" class="form-control" maxlength="9" placeholder="CEP"
                    value="<?= e($empresa['cep'] ?? '') ?>">
                </div>
                <div class="col-md-9">
                  <input type="text" name="logradouro" class="form-control" maxlength="150" placeholder="Logradouro"
                    value="<?= e($empresa['logradouro'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <input type="text" name="numero" class="form-control" maxlength="20" placeholder="Número"
                    value="<?= e($empresa['numero'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="complemento" class="form-control" maxlength="80" placeholder="Complemento"
                    value="<?= e($empresa['complemento'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                  <input type="text" name="bairro" class="form-control" maxlength="80" placeholder="Bairro"
                    value="<?= e($empresa['bairro'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                  <input type="text" name="cidade" class="form-control" maxlength="80" placeholder="Cidade *"
                    value="<?= e($empresa['cidade'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="uf" class="form-control text-uppercase" maxlength="2" placeholder="UF"
                    value="<?= e($empresa['uf'] ?? '') ?>">
                </div>
              </div>
              <div class="form-text">O endereço completo aparece no mapa e nos dados da sua página pública no Diretório.</div>
            </div>
            <div>
              <label class="form-label fw-semibold small d-block mb-0" style="padding:10px 0;border-bottom:1px solid var(--border)"><i class="bi bi-globe2 text-primary me-1"></i>Site e redes sociais</label>
              <div class="row g-3 mt-1">
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
            <div class="pp-desc-destaque">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <label class="form-label fw-semibold small mb-0">Descrição pública</label>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDescricaoIA"
                  title="Gera um rascunho com IA a partir de informações básicas — sempre revise antes de salvar">
                  <i class="bi bi-stars me-1"></i>Preencher com IA
                </button>
              </div>
              <?php
                // Sempre passa por html_rico_sanitizar() antes de injetar cru no editor —
                // mesmo o texto "aparentemente simples" (a shape do texto NUNCA decide se é
                // seguro renderizar cru; só decide se precisa da ajuda extra abaixo). Descrição
                // salva antes desta feature (quando o campo ainda era um <textarea> comum)
                // nunca passou por sanitização nenhuma até agora — confiar na forma do texto
                // pra pular a sanitização deixaria um "<script>" antigo furar pra valer.
                $descRaw  = (string) ($empresa['descricao_publica'] ?? '');
                $descSafe = html_rico_sanitizar($descRaw);
                // Só decide se o texto (já seguro) precisa de uma <div> por linha — quebra de
                // linha real de texto legado puro (sem tag nenhuma) some dentro do editor rico
                // porque HTML ignora \n fora de tag; o mesmo formato que o próprio editor já
                // produz pra cada parágrafo.
                $descEditorHtml = ($descRaw !== '' && strip_tags($descRaw) === $descRaw)
                    ? implode('', array_map(fn ($l) => '<div>' . e($l) . '</div>', preg_split('/\r\n|\r|\n/', $descSafe)))
                    : $descSafe;
              ?>
              <div id="descricaoPublicaBox">
                <div id="descricaoPublicaToolbar" class="d-flex align-items-center gap-1 flex-wrap">
                  <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-cmd="bold" title="Negrito (Ctrl+B)">B</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary fst-italic" data-cmd="italic" title="Itálico (Ctrl+I)">I</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary text-decoration-underline" data-cmd="underline" title="Sublinhado (Ctrl+U)">U</button>
                  <div class="vr mx-1"></div>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertUnorderedList" title="Lista com marcadores"><i class="bi bi-list-ul"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertOrderedList" title="Lista numerada"><i class="bi bi-list-ol"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-cmd="removeFormat" title="Limpar formatação"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="descricaoPublicaTexto" class="form-control" contenteditable="true" spellcheck="true" lang="pt-BR"
                  data-placeholder="Descreva sua assistência: o que você conserta, anos de experiência, diferenciais..."><?= $descEditorHtml ?></div>
              </div>
              <input type="hidden" name="descricao_publica" id="descricaoPublicaHidden">
              <div class="form-text">Aparece na sua página e ajuda o Google a entender o que você faz. Use <strong>negrito</strong> pra destacar o que for mais importante.</div>
            </div>

            <!-- Preencher a Descrição pública com IA — a partir de informações básicas digitadas
                 aqui (não exige nada além do "o que conserta"/"diferenciais"); nome/cidade/UF já
                 cadastrados entram sozinhos como contexto extra no servidor. Mesmo padrão de
                 "Preencher com IA" já usado no Laudo técnico da OS: só gera um rascunho no
                 editor, quem decide se salva continua sendo o "Salvar perfil público". -->
            <div class="modal fade" id="modalDescricaoIA" tabindex="-1" data-bs-backdrop="static">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-stars me-2 text-primary"></i>Preencher descrição com IA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-2">
                      <label class="form-label small fw-semibold mb-1">O que você conserta</label>
                      <input type="text" id="descIaConserta" class="form-control form-control-sm" maxlength="300"
                        placeholder="Ex.: celulares, notebooks, TVs e eletrodomésticos">
                    </div>
                    <div class="mb-2">
                      <label class="form-label small fw-semibold mb-1">Anos de experiência (opcional)</label>
                      <input type="number" id="descIaAnos" class="form-control form-control-sm" min="0" max="99" placeholder="Ex.: 10">
                    </div>
                    <div class="mb-1">
                      <label class="form-label small fw-semibold mb-1">Diferenciais (opcional)</label>
                      <input type="text" id="descIaDiferenciais" class="form-control form-control-sm" maxlength="300"
                        placeholder="Ex.: orçamento sem compromisso, garantia, atendimento rápido">
                    </div>
                    <div id="descIaMsg" class="small mt-2"></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btnGerarDescricaoIA">
                      <i class="bi bi-stars me-1"></i>Gerar descrição
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label fw-semibold small mb-0"><i class="bi bi-star-fill text-warning me-1"></i>Exibir avaliações de clientes</label>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox" name="avaliacoes_publicas" value="1" id="avalPublicas"
                         <?= ($empresa['avaliacoes_publicas'] ?? 1) ? 'checked' : '' ?> style="width:3rem;height:1.5rem">
                </div>
              </div>
              <div class="form-text">Quando ativado, sua página pública mostra a nota, os comentários e o formulário para novos clientes avaliarem. Desativado, a seção some da página — as avaliações já recebidas ficam guardadas, só não aparecem.</div>
            </div>
          </div>
        </div>
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold">Especialidades</div>
          <div class="card-body d-flex flex-column gap-3">
            <div>
              <div id="tagsBox" class="d-flex flex-wrap align-items-center gap-2 border rounded p-2">
                <span id="tagsLista" class="d-flex flex-wrap gap-2"></span>
                <input type="text" id="tagInput" class="form-control form-control-sm border-0 shadow-none flex-grow-1"
                       style="min-width:140px;width:auto" placeholder="Digite e aperte Enter..." maxlength="30">
              </div>
              <input type="hidden" name="especialidades" id="tagsHidden" value="<?= e($empresa['especialidades'] ?? '') ?>">
              <div class="form-text">Aparecem como tags na sua página e na listagem do diretório. Digite e aperte Enter (ou vírgula) para adicionar.</div>
            </div>
          </div>
        </div>
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
              // Rótulo em português pra cada ícone — antes o <select> mostrava a classe crua
              // (ex.: "bi-water"), que não diz nada pra quem não conhece Bootstrap Icons; o
              // `value` continua sendo a classe (é o que fica salvo e usado pra desenhar o
              // ícone de verdade na ficha pública), só o texto visível na opção mudou.
              $iconesOpc = [
                  'bi-tools'      => 'Ferramentas',
                  'bi-phone'      => 'Celular',
                  'bi-laptop'     => 'Notebook',
                  'bi-tv'         => 'TV',
                  'bi-snow'       => 'Ar Condicionado',
                  'bi-water'      => 'Máquina de Lavar',
                  'bi-box2'       => 'Peças / Estoque',
                  'bi-wind'       => 'Ventilador',
                  'bi-printer'    => 'Impressora',
                  'bi-joystick'   => 'Videogame',
                  'bi-cpu'        => 'Computador',
                  'bi-tablet'     => 'Tablet',
                  'bi-headphones' => 'Fone de Ouvido',
              ];
              foreach($servicos as $s): ?>
              <div class="serv-row d-flex gap-2 align-items-center">
                <select name="serv_icone[]" class="form-select form-select-sm" style="width:170px">
                  <?php foreach($iconesOpc as $ic => $rotulo): ?>
                  <option value="<?= $ic ?>" <?= $s['icone']===$ic?'selected':'' ?>><?= e($rotulo) ?></option>
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
          <div>
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

    <div class="col-lg-4">
      <div class="d-flex flex-column gap-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold"><i class="bi bi-clock-fill text-primary me-1"></i>Horário de funcionamento</div>
          <div class="card-body d-flex flex-column gap-2">
            <div id="horarioEditor" class="d-flex flex-column gap-1">
              <?php
                $diasSemana = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
                foreach ($diasSemana as $dk => $dLabel): ?>
              <div class="d-flex align-items-center flex-wrap gap-2 py-1" data-dia="<?= $dk ?>">
                <div class="form-check form-switch mb-0" style="width:100px">
                  <input class="form-check-input dia-aberto" type="checkbox" role="switch" id="dia-<?= $dk ?>-sw">
                  <label class="form-check-label small" for="dia-<?= $dk ?>-sw"><?= $dLabel ?></label>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <input type="time" class="form-control form-control-sm dia-abre" style="width:100px">
                  <span class="text-muted small dia-ate-label">às</span>
                  <input type="time" class="form-control form-control-sm dia-fecha" style="width:100px">
                  <span class="text-muted small dia-fechado-label d-none">Fechado</span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="horario_funcionamento" id="horarioHidden" form="editarPerfilDiretorio" value="<?= e($empresa['horario_funcionamento'] ?? '') ?>">
            <div class="form-text">Desmarque os dias em que sua empresa não funciona.</div>
          </div>
        </div>
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold">Logo</div>
          <div class="card-body d-flex flex-column gap-3">
            <div id="logoPreviewWrap" style="position:relative">
              <?php if(!empty($empresa['logo'])): ?>
              <img id="logoPreview" src="<?= url('/uploads/' . e($empresa['logo'])) ?>"
                   class="rounded" style="width:100%;height:140px;object-fit:contain;background:#f8fafc;display:block" alt="Logo">
              <button type="button" class="pp-btn-remover-img" onclick="removerImagemPerfil('<?= url('/empresa/logo/remover') ?>', 'Remover a logo atual?')" title="Remover logo">&times;</button>
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
            <input type="file" name="logo" id="logoInput" form="editarPerfilDiretorio"
                   class="form-control form-control-sm"
                   accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif"
                   onchange="abrirEditorLogo(this)">
            <div class="form-text">JPG, PNG, SVG ou WebP até 2MB. Depois de escolher, você pode recortar e redimensionar antes de salvar.</div>
          </div>
        </div>
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold">Cor da capa</div>
          <div class="card-body d-flex flex-column gap-3">
            <div id="capaCorPreview" class="rounded d-flex align-items-center justify-content-center text-center px-2"
                 style="height:140px;background:linear-gradient(135deg,<?= e($corCapaAtual) ?>,<?= e(cor_escurecer($corCapaAtual)) ?>)">
              <span id="capaCorPreviewTexto" class="fw-bold text-white" style="font-size:1.05rem;line-height:1.3;text-shadow:0 2px 10px rgba(0,0,0,.4)">
                <?= e($empresa['nome_fantasia'] ?: 'Nome da sua empresa') ?>
              </span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <input type="color" name="cor_capa" id="corCapaInput" form="editarPerfilDiretorio"
                     class="form-control form-control-color form-control-sm" style="width:52px;height:38px;padding:2px"
                     value="<?= e($corCapaAtual) ?>" oninput="atualizarPreviewCorCapa(this.value)">
              <div class="form-text mb-0">Fundo do banner com o nome da empresa, no lugar da antiga foto de capa.</div>
            </div>
          </div>
        </div>
  <div class="card border-0 shadow-sm" id="fotos">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
        <h5 class="fw-bold mb-0"><i class="bi bi-images text-primary me-1"></i>Fotos da empresa</h5>
        <span class="badge bg-light text-dark border"><?= count($fotos) ?>/4</span>
      </div>
      <p class="text-muted small mb-3">Uma <strong>foto de capa</strong> (aparece em destaque) + até <strong>3 fotos de galeria</strong> (viram carrossel). Mostre sua loja, bancada e trabalhos — perfis com fotos passam <strong>muito mais confiança</strong> e se destacam dos gratuitos.</p>

      <?php
      // A tabela já guarda exatamente esse conceito (`principal`) — não precisou de coluna
      // nova nem endpoint novo, só reorganizar a exibição em duas seções (capa destacada +
      // grade de galeria) em vez da grade única e uniforme de antes.
      $fotoCapa = null;
      $fotosGaleria = [];
      foreach ($fotos as $ft) {
          if ($ft['principal'] && !$fotoCapa) $fotoCapa = $ft;
          else $fotosGaleria[] = $ft;
      }
      ?>

      <div class="small fw-bold text-uppercase mb-2" style="letter-spacing:.03em;font-size:.72rem;color:#64748b">Foto de capa</div>
      <?php if ($fotoCapa): ?>
      <div class="position-relative rounded overflow-hidden mb-3" style="height:140px;background:#f1f5f9">
        <img src="<?= url('/uploads/fotos/'.e($fotoCapa['arquivo'])) ?>" style="width:100%;height:100%;object-fit:cover" alt="Foto de capa">
        <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2"><i class="bi bi-star-fill"></i> Capa</span>
        <form method="POST" action="<?= url('/empresa/fotos/'.$fotoCapa['id'].'/remover') ?>" onsubmit="return confirm('Remover a foto de capa?')"
              class="position-absolute bottom-0 start-0 end-0 p-2" style="background:linear-gradient(transparent,rgba(0,0,0,.55))">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-danger w-100 py-0"><i class="bi bi-trash"></i> Remover</button>
        </form>
      </div>
      <?php else: ?>
      <form method="POST" action="<?= url('/empresa/fotos') ?>" enctype="multipart/form-data" id="formFotoCapa" class="mb-3">
        <?= csrf_field() ?>
        <label class="border rounded d-flex flex-column align-items-center justify-content-center text-muted"
               style="height:140px;cursor:pointer;border-style:dashed!important;background:#f8fafc;background-size:cover;background-position:center">
          <div class="upload-placeholder-conteudo d-flex flex-column align-items-center">
            <i class="bi bi-plus-lg fs-3"></i>
            <span class="small">Adicionar foto de capa</span>
          </div>
          <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewEEnviarFoto(this)">
        </label>
      </form>
      <?php endif; ?>

      <div class="small fw-bold text-uppercase mb-2" style="letter-spacing:.03em;font-size:.72rem;color:#64748b">Galeria (até 3 fotos)</div>
      <?php if (!$fotoCapa): ?>
      <div class="text-muted small fst-italic">Adicione a foto de capa primeiro pra liberar a galeria.</div>
      <?php else: ?>
      <div class="row g-2">
        <?php foreach($fotosGaleria as $ft): ?>
        <div class="col-6">
          <div class="position-relative border rounded overflow-hidden" style="aspect-ratio:1/1;background:#f1f5f9">
            <img src="<?= url('/uploads/fotos/'.e($ft['arquivo'])) ?>" style="width:100%;height:100%;object-fit:cover" alt="Foto da galeria">
            <div class="position-absolute bottom-0 start-0 end-0 d-flex gap-1 p-1" style="background:linear-gradient(transparent,rgba(0,0,0,.55))">
              <form method="POST" action="<?= url('/empresa/fotos/'.$ft['id'].'/principal') ?>" class="flex-grow-1">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light w-100 py-0" title="Tornar capa"><i class="bi bi-star"></i></button>
              </form>
              <form method="POST" action="<?= url('/empresa/fotos/'.$ft['id'].'/remover') ?>" onsubmit="return confirm('Remover esta foto?')" class="flex-grow-1">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-danger w-100 py-0" title="Remover foto"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if(count($fotos) < 4): ?>
        <div class="col-6">
          <form method="POST" action="<?= url('/empresa/fotos') ?>" enctype="multipart/form-data" id="formFotoGaleria">
            <?= csrf_field() ?>
            <label class="border rounded d-flex flex-column align-items-center justify-content-center text-muted"
                   style="aspect-ratio:1/1;cursor:pointer;border-style:dashed!important;background:#f8fafc;background-size:cover;background-position:center">
              <div class="upload-placeholder-conteudo d-flex flex-column align-items-center">
                <i class="bi bi-plus-lg fs-3"></i>
                <span class="small">Adicionar</span>
              </div>
              <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewEEnviarFoto(this)">
            </label>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>JPG, PNG ou WebP até 4MB. 1 capa + até 3 na galeria.</div>
    </div>
  </div>
      </div>
    </div>

      <!-- Editor de logo (recorte/redimensionamento livre, salva sempre como PNG com fundo
           transparente). SVG pula direto pro preview normal — é vetor, não faz sentido
           recortar em pixels. -->
      <div class="modal fade" id="modalEditorLogo" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title fw-bold"><i class="bi bi-crop me-2 text-primary"></i>Ajustar logo</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelarEditorLogo()"></button>
            </div>
            <div class="modal-body">
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-light text-dark border" style="cursor:pointer" onclick="logoSetAspecto(NaN,this)">Livre</span>
                <span class="badge bg-light text-dark border" style="cursor:pointer" onclick="logoSetAspecto(1,this)">Quadrado</span>
                <span class="badge bg-light text-dark border" style="cursor:pointer" onclick="logoSetAspecto(2,this)">2:1 (retangular)</span>
              </div>
              <div style="max-height:60vh;overflow:hidden">
                <img id="logoCropperImg" style="max-width:100%" alt="Recortar logo">
              </div>
              <div class="row g-2 mt-2">
                <div class="col-6">
                  <label class="form-label small fw-semibold mb-1">Largura (px)</label>
                  <input type="number" id="logoCropW" class="form-control form-control-sm" min="1" oninput="logoSetCropDim('w')">
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold mb-1">Altura (px)</label>
                  <input type="number" id="logoCropH" class="form-control form-control-sm" min="1" oninput="logoSetCropDim('h')">
                </div>
              </div>
              <div class="form-text mt-2">O fundo fora da área recortada fica transparente — ideal pra logo sem caixa branca ao redor.</div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="cancelarEditorLogo()">Cancelar</button>
              <button type="button" class="btn btn-primary fw-bold" onclick="aplicarEditorLogo()">
                <i class="bi bi-check-lg me-1"></i>Aplicar
              </button>
            </div>
          </div>
        </div>
      </div>
  </div>

  <!-- Minhas avaliações — forms próprios (responder/contestar por avaliação), fora do form de identidade -->
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

  <!-- Crescimento e monetização — cards de venda/status descem pro fim da página, depois
       de tudo que é edição do perfil em si, pra não competir por atenção com o preenchimento. -->
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
          <h6 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-1"></i>Apareça em destaque no diretório</h6>
          <p class="text-muted small mb-2">Fique no <strong>topo das buscas</strong> da sua cidade, com selo de destaque, e seja encontrado antes dos concorrentes.</p>
          <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
            <span class="badge bg-light text-dark border"><i class="bi bi-arrow-up-circle text-warning me-1"></i>Topo das buscas</span>
            <span class="badge bg-light text-dark border"><i class="bi bi-patch-check-fill text-warning me-1"></i>Selo de destaque</span>
            <span class="badge bg-light text-dark border"><i class="bi bi-eye-fill text-warning me-1"></i>Mais visitas</span>
            <span class="badge bg-light text-dark border"><i class="bi bi-envelope-check-fill text-warning me-1"></i>Relatório semanal de visitas</span>
          </div>
        </div>
        <a href="<?= url('/empresa/publicidade') ?>" class="btn btn-warning fw-bold text-nowrap" style="padding:.7rem 1.3rem">
          <i class="bi bi-star-fill me-1"></i>Contratar destaque
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$planoCompleto && !empty($empresa['reivindicada'])): ?>
  <div class="alert d-flex align-items-center gap-2" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155">
    <i class="bi bi-info-circle-fill fs-5" style="color:#64748b"></i>
    <div><strong>Seu perfil é grátis</strong> — e, por isso, pode exibir um anúncio de outra empresa parceira do
    FixaOS na sua página pública. Ao assinar qualquer plano do FixaOS (ou contratar destaque), seu perfil fica
    sem anúncio, libera a contagem de visitas e passa a receber um relatório semanal de visitas por e-mail.
    <a href="<?= url('/planos') ?>" class="fw-semibold">Ver planos</a>.</div>
  </div>
  <?php endif; ?>

  <!-- Visitas ao perfil — único item ainda exclusivo de quem assina um plano do FixaOS -->
  <?php if($planoCompleto): ?>
  <?php $vTotal = (int)($visitas['total'] ?? 0); ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-2">
        <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Visitas ao seu perfil no diretório</h6>
        <span class="badge bg-light text-muted border">Contamos só perfis reivindicados</span>
      </div>
      <p class="text-muted small mb-3"><i class="bi bi-envelope-check-fill me-1"></i>Você recebe um resumo destes números por e-mail toda semana.</p>
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
  <!-- Convite pro sistema completo (cidade, foto, redes e serviços já são grátis — só
       visitas continua exclusivo daqui) -->
  <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#fff7ed,#fff);border:1px dashed #fdba74!important">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div style="flex:1;min-width:260px">
        <h6 class="fw-bold mb-1" style="color:#78350f"><i class="bi bi-rocket-takeoff-fill text-warning me-1"></i>Conheça o FixaOS completo</h6>
        <p class="small mb-2" style="color:#9a3412">Gerencie Ordens de Serviço, Financeiro, Estoque, Agenda e muito mais em um só lugar — e ainda libera a contagem de visitas do seu perfil no diretório, com relatório semanal por e-mail.</p>
        <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
          <span class="badge bg-light border" style="color:#78350f"><i class="bi bi-clipboard2-check text-warning me-1"></i>Ordens de Serviço</span>
          <span class="badge bg-light border" style="color:#78350f"><i class="bi bi-cash-coin text-warning me-1"></i>Financeiro</span>
          <span class="badge bg-light border" style="color:#78350f"><i class="bi bi-boxes text-warning me-1"></i>Estoque</span>
          <span class="badge bg-light border" style="color:#78350f"><i class="bi bi-graph-up-arrow text-warning me-1"></i>Contagem de visitas</span>
          <span class="badge bg-light border" style="color:#78350f"><i class="bi bi-envelope-check-fill text-warning me-1"></i>Relatório semanal</span>
        </div>
      </div>
      <div class="d-flex flex-column gap-2">
        <a href="<?= url('/demo') ?>" target="_top" class="btn btn-outline-warning fw-bold text-nowrap" style="padding:.6rem 1.2rem;color:#78350f;border-color:#f59e0b">
          <i class="bi bi-play-circle-fill me-1"></i>Ver demonstração ao vivo
        </a>
        <a href="<?= url('/planos') ?>" target="_top" class="btn btn-warning fw-bold text-nowrap" style="padding:.7rem 1.3rem">
          <i class="bi bi-stars me-1"></i>Ver planos da FixaOS
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
// Editor rico da Descrição pública (negrito/itálico/sublinhado/listas via execCommand) —
// não tem "name" próprio, então um <input type="hidden"> homônimo é sincronizado com o
// innerHTML antes do form submeter (mesmo problema/solução já usados no laudo técnico da
// OS, só que lá o save é por AJAX próprio; aqui é o mesmo "Salvar perfil público" de sempre).
(function () {
  var box = document.getElementById('descricaoPublicaTexto'), hidden = document.getElementById('descricaoPublicaHidden');
  if (!box || !hidden) return;
  var form = box.closest('form');

  function sincronizarHidden() { hidden.value = box.innerHTML; }
  sincronizarHidden();

  document.querySelectorAll('#descricaoPublicaToolbar [data-cmd]').forEach(function (btn) {
    btn.onclick = function () {
      box.focus();
      try { document.execCommand('styleWithCSS', false, false); } catch (e) {}
      document.execCommand(btn.dataset.cmd);
      atualizarEstadoBotoes();
      sincronizarHidden();
    };
  });

  function atualizarEstadoBotoes() {
    document.querySelectorAll('#descricaoPublicaToolbar [data-cmd]').forEach(function (btn) {
      var ativo = false;
      try { ativo = document.queryCommandState(btn.dataset.cmd); } catch (e) {}
      btn.classList.toggle('active', !!ativo);
    });
  }
  ['keyup', 'mouseup', 'focus', 'input'].forEach(function (ev) { box.addEventListener(ev, atualizarEstadoBotoes); });
  document.addEventListener('selectionchange', function () {
    if (document.activeElement === box) atualizarEstadoBotoes();
  });

  box.addEventListener('input', sincronizarHidden);
  if (form) form.addEventListener('submit', sincronizarHidden);

  // Preencher descrição com IA — mesmo padrão do laudo técnico da OS: só um rascunho no
  // editor, quem decide se salva continua sendo o "Salvar perfil público".
  var btnGerar = document.getElementById('btnGerarDescricaoIA'), msgIa = document.getElementById('descIaMsg');
  if (btnGerar) {
    btnGerar.onclick = function () {
      var conserta     = document.getElementById('descIaConserta').value.trim();
      var anos         = document.getElementById('descIaAnos').value.trim();
      var diferenciais = document.getElementById('descIaDiferenciais').value.trim();
      if (!conserta && !diferenciais) {
        msgIa.innerHTML = '<span class="text-danger">Preencha ao menos "o que você conserta" ou "diferenciais".</span>';
        return;
      }
      var orig = btnGerar.innerHTML;
      btnGerar.disabled = true;
      btnGerar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';
      msgIa.textContent = '';
      fetch('<?= url('/empresa/perfil-publico/descricao-ia') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: 'conserta=' + encodeURIComponent(conserta)
          + '&anos_experiencia=' + encodeURIComponent(anos)
          + '&diferenciais=' + encodeURIComponent(diferenciais)
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) { msgIa.innerHTML = '<span class="text-danger">' + (j.erro || 'Não foi possível gerar a descrição.') + '</span>'; return; }
          box.innerHTML = j.html;
          sincronizarHidden();
          if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDescricaoIA')).hide();
          }
        })
        .catch(function () { msgIa.innerHTML = '<span class="text-danger">Falha de conexão.</span>'; })
        .finally(function () { btnGerar.disabled = false; btnGerar.innerHTML = orig; });
    };
  }
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

// ── Editor de logo: recorte/redimensionamento livre, sempre exporta PNG transparente ──
let _logoCropper = null;
let _logoAspecto = NaN;
let _logoCropEdit = false;

function abrirEditorLogo(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  // SVG é vetor — não faz sentido recortar em pixels, segue pro preview normal.
  if (file.type === 'image/svg+xml') { previewLogo(input); return; }

  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('logoCropperImg').src = e.target.result;
    const modalEl = document.getElementById('modalEditorLogo');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
    modalEl.addEventListener('shown.bs.modal', function onShown() {
      modalEl.removeEventListener('shown.bs.modal', onShown);
      logoIniciarCropper();
    });
  };
  reader.readAsDataURL(file);
}

function logoIniciarCropper() {
  const imgEl = document.getElementById('logoCropperImg');
  if (_logoCropper) { _logoCropper.destroy(); _logoCropper = null; }
  _logoAspecto = NaN;
  document.querySelectorAll('#modalEditorLogo .badge').forEach(b => {
    b.classList.add('bg-light', 'text-dark');
    b.classList.remove('bg-primary', 'text-white');
  });
  _logoCropper = new Cropper(imgEl, {
    aspectRatio: NaN,
    viewMode: 1,
    dragMode: 'crop',
    guides: true,
    center: true,
    background: true,
    zoomable: true,
    zoomOnWheel: true,
    movable: true,
    rotatable: false,
    scalable: false,
    autoCropArea: 0.9,
    crop(ev) { logoAtualizarCropLive(ev.detail); }
  });
}

document.getElementById('modalEditorLogo').addEventListener('hidden.bs.modal', function() {
  if (_logoCropper) { _logoCropper.destroy(); _logoCropper = null; }
});

function logoSetAspecto(ratio, btn) {
  _logoAspecto = ratio;
  document.querySelectorAll('#modalEditorLogo .badge').forEach(b => {
    b.classList.add('bg-light', 'text-dark');
    b.classList.remove('bg-primary', 'text-white');
  });
  if (btn) {
    btn.classList.remove('bg-light', 'text-dark');
    btn.classList.add('bg-primary', 'text-white');
  }
  if (_logoCropper) _logoCropper.setAspectRatio(ratio || NaN);
}

function logoAtualizarCropLive(detail) {
  if (!_logoCropper || _logoCropEdit) return;
  const d = detail || _logoCropper.getData();
  document.getElementById('logoCropW').value = Math.max(0, Math.round(d.width));
  document.getElementById('logoCropH').value = Math.max(0, Math.round(d.height));
}

function logoSetCropDim(campo) {
  if (!_logoCropper) return;
  _logoCropEdit = true;
  const data = _logoCropper.getData();
  if (campo === 'w') {
    const w = parseInt(document.getElementById('logoCropW').value) || 0;
    let h = data.height;
    if (!isNaN(_logoAspecto) && _logoAspecto) { h = Math.round(w / _logoAspecto); document.getElementById('logoCropH').value = h; }
    _logoCropper.setData({ width: w, height: h });
  } else {
    const h = parseInt(document.getElementById('logoCropH').value) || 0;
    let w = data.width;
    if (!isNaN(_logoAspecto) && _logoAspecto) { w = Math.round(h * _logoAspecto); document.getElementById('logoCropW').value = w; }
    _logoCropper.setData({ width: w, height: h });
  }
  setTimeout(() => { _logoCropEdit = false; }, 50);
}

function aplicarEditorLogo() {
  if (!_logoCropper) return;
  // fillColor 'transparent' preserva o alfa fora da área recortada, em vez de preencher
  // com uma cor sólida — é o que garante o "fundo transparente" pedido.
  const canvas = _logoCropper.getCroppedCanvas({ fillColor: 'transparent', imageSmoothingQuality: 'high' });
  canvas.toBlob(function(blob) {
    if (!blob) return;
    // Injeta o resultado editado direto no <input type="file"> via DataTransfer — o form
    // continua enviando multipart normalmente, sem precisar de um endpoint separado.
    const file = new File([blob], 'logo.png', { type: 'image/png' });
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('logoInput').files = dt.files;

    const preview = document.getElementById('logoPreview');
    const placeholder = document.getElementById('logoPlaceholder');
    if (placeholder) placeholder.style.display = 'none';
    preview.src = canvas.toDataURL('image/png');
    preview.style.display = 'block';

    bootstrap.Modal.getInstance(document.getElementById('modalEditorLogo')).hide();
  }, 'image/png');
}

function cancelarEditorLogo() {
  document.getElementById('logoInput').value = '';
}

// Escurece uma cor hex multiplicando os canais RGB — mesmo cálculo de cor_escurecer() no PHP
// (app/Helpers/functions.php), só que client-side, pra atualizar a prévia do banner sem
// esperar um round-trip ao servidor a cada clique no seletor de cor.
function corEscurecerJs(hex, fator) {
  hex = hex.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  const c = (v) => Math.round(v * fator).toString(16).padStart(2, '0');
  return '#' + c(r) + c(g) + c(b);
}

function atualizarPreviewCorCapa(cor) {
  const escura = corEscurecerJs(cor, 0.55);
  document.getElementById('capaCorPreview').style.background = 'linear-gradient(135deg,' + cor + ',' + escura + ')';
}

// Comprime no navegador antes de enviar (mesmo padrão de comprimirImagemProd() em
// produtos/form.php) — sem isso, uma foto real de câmera/celular (facilmente 5-10MB)
// causava o bug relatado: FileReader.readAsDataURL() no arquivo cru travava a aba por
// alguns segundos gerando a prévia (parecia "travado" depois de escolher o arquivo), e o
// arquivo original — nunca comprimido — ia pro submit do jeito que veio; passando de
// upload_max_filesize/post_max_size do PHP (configuração comum de hospedagem, ex. 2M/8M),
// o servidor descarta $_POST/$_FILES inteiro (guard já existe em public/index.php) e a
// página só recarrega com a contagem de fotos intacta, sem nenhum erro óbvio na tela.
// Reduzir pra no máximo 1280px + JPEG 0.8 antes de tudo evita as duas coisas de uma vez.
function comprimirImagemPerfil(file) {
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => {
        let max = 1280, w = img.width, h = img.height;
        if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
        else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        c.toBlob(blob => {
          resolve(blob ? new File([blob], 'foto.jpg', { type: 'image/jpeg' }) : file);
        }, 'image/jpeg', 0.8);
      };
      img.onerror = () => resolve(file); // não decodificou (formato raro) — envia o original
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

// Mostra a imagem escolhida (capa ou galeria, mesmo placeholder tracejado nos dois) antes
// mesmo do upload terminar — sem isso, entre escolher o arquivo e a página recarregar com o
// resultado do servidor, a tela ficava "parada" sem nenhuma confirmação visual de qual foto
// foi selecionada. Comprime primeiro e substitui o arquivo do próprio <input> via
// DataTransfer (mesma técnica de previewFotoProd() em produtos/form.php) — o submit logo em
// seguida já envia a versão comprimida, não o arquivo original. Texto do miolo vira um
// spinner "Enviando..." — a foto de verdade (já processada pelo servidor) só aparece depois
// do reload que a resposta do formulário dispara.
async function previewEEnviarFoto(input) {
  if (!input.files || !input.files[0]) return;
  const label = input.closest('label');
  const conteudo = label.querySelector('.upload-placeholder-conteudo');
  const form = input.closest('form');
  const comprimida = await comprimirImagemPerfil(input.files[0]);
  const dt = new DataTransfer();
  dt.items.add(comprimida);
  input.files = dt.files;
  const reader = new FileReader();
  reader.onload = function (e) {
    label.style.setProperty('background-image', 'url(' + e.target.result + ')');
    label.style.setProperty('border-style', 'solid', 'important');
    conteudo.innerHTML = '<span class="badge bg-dark bg-opacity-75 text-white">'
      + '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Enviando...</span>';
    // Só envia DEPOIS que a prévia já está no DOM, e ainda espera 2 frames de repintura —
    // form.submit() dispara a navegação de forma praticamente síncrona; sem esperar o
    // navegador pintar, o reload podia acontecer antes de qualquer pixel da prévia aparecer,
    // e o usuário nunca veria a foto escolhida antes do "Enviando...".
    requestAnimationFrame(() => requestAnimationFrame(() => form.submit()));
  };
  reader.readAsDataURL(comprimida);
}

// Excluir imagem já salva — só a Logo tem esse botão hoje ("Fotos da empresa" já tem seu
// próprio botão de remover por foto; a antiga "Foto de capa" virou cor de fundo, sem imagem
// nenhuma pra excluir). Ação imediata (sem esperar "Salvar perfil público"), mesmo padrão de
// excluirFotoEntradaShow() em os/show.php: confirma, chama o endpoint via fetch, recarrega a
// página no sucesso — o redirect que o servidor devolve não importa aqui, o fetch só olha se
// a resposta veio OK.
function removerImagemPerfil(endpoint, confirmMsg) {
  if (!confirm(confirmMsg)) return;
  fetch(endpoint, {
    method: 'POST',
    headers: { 'X-CSRF-Token': '<?= csrf_token() ?>' }
  })
    .then(function (r) { if (r.ok) { location.reload(); } else { alert('Não foi possível remover a imagem.'); } })
    .catch(function () { alert('Falha de conexão ao remover a imagem.'); });
}

// Mesmo mapa classe→rótulo do PHP (linha ~300) — duplicado aqui de propósito, já que este
// arquivo não tem um endpoint JSON pra servir esse mapa só pra montar um <select> no cliente.
const iconesOpc = <?= json_encode($iconesOpc, JSON_UNESCAPED_UNICODE) ?>;

function makeSelectIcone() {
  const sel = document.createElement('select');
  sel.name = 'serv_icone[]';
  sel.className = 'form-select form-select-sm';
  sel.style.width = '170px';
  Object.entries(iconesOpc).forEach(([ic, rotulo]) => {
    const opt = document.createElement('option');
    opt.value = ic; opt.textContent = rotulo;
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
