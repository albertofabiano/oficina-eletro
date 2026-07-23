<?php $titulo = 'Migração SHOficina'; ?>
<div class="page-content">

  <div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:48px;height:48px;border-radius:12px;background:rgba(249,115,22,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="bi bi-database-fill-up" style="color:#f97316;font-size:1.4rem"></i>
    </div>
    <div>
      <h4 class="fw-bold mb-0">Importar dados do SHOficina</h4>
      <p class="text-muted small mb-0">Importe clientes, ordens de serviço e histórico do sistema SHOficina.</p>
    </div>
  </div>

  <!-- Alerta de atenção -->
  <div class="alert alert-warning d-flex gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
    <div>
      <strong>Atenção antes de importar:</strong>
      <ul class="mb-0 mt-1 small">
        <li>Faça um <strong>backup</strong> dos dados atuais antes de continuar.</li>
        <li>Use apenas arquivos SQL gerados pela ferramenta de migração do FixaOS.</li>
        <li>O processo pode levar alguns minutos dependendo do volume de dados.</li>
        <li>Dados existentes <strong>não serão apagados</strong> — os registros serão adicionados.</li>
      </ul>
    </div>
  </div>

  <?php $ok=flash('success');$err=flash('error');
  if($ok): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= e($ok) ?></div><?php endif;
  if($err): ?><div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i><?= e($err) ?></div><?php endif; ?>

  <div class="row g-4">

    <!-- Upload do SQL -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">
          <i class="bi bi-upload me-2 text-primary"></i>Enviar arquivo de migração
        </div>
        <div class="card-body">
          <form method="POST" action="<?= url('/empresa/migracao-shoficina') ?>" enctype="multipart/form-data" id="formMigracao">
            <?= csrf_field() ?>

            <div class="mb-4">
              <label class="form-label fw-semibold">Arquivo SQL de migração *</label>
              <div id="dropZone" class="border border-2 border-dashed rounded-3 p-4 text-center"
                   style="border-color:#dee2e6;cursor:pointer;transition:.2s"
                   ondragover="event.preventDefault();this.style.borderColor='#f97316';this.style.background='#fff7ed'"
                   ondragleave="this.style.borderColor='#dee2e6';this.style.background=''"
                   ondrop="handleDrop(event)">
                <i class="bi bi-file-earmark-code fs-1 text-muted d-block mb-2"></i>
                <p class="mb-1 fw-semibold">Arraste o arquivo aqui ou <span class="text-primary" onclick="document.getElementById('sqlFile').click()" style="cursor:pointer">clique para selecionar</span></p>
                <small class="text-muted">Apenas arquivos .sql gerados pela ferramenta de migração FixaOS</small>
                <input type="file" name="arquivo_sql" id="sqlFile" class="d-none" accept=".sql" onchange="previewArquivo(this)">
              </div>
              <div id="previewArq" class="mt-2 d-none">
                <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                  <i class="bi bi-file-earmark-code text-primary fs-5"></i>
                  <div>
                    <div class="fw-semibold small" id="nomeArq"></div>
                    <div class="text-muted" style="font-size:.75rem" id="tamanhoArq"></div>
                  </div>
                  <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="confirmar" required>
                <label class="form-check-label small" for="confirmar">
                  Confirmo que fiz backup dos dados e entendo que esta operação irá adicionar registros ao banco de dados atual.
                </label>
              </div>
            </div>

            <button type="submit" class="btn btn-warning fw-bold w-100" id="btnImportar" disabled>
              <i class="bi bi-database-fill-up me-2"></i>Iniciar importação
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Instruções -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold">
          <i class="bi bi-info-circle me-2 text-primary"></i>Como gerar o arquivo SQL
        </div>
        <div class="card-body">
          <ol class="small ps-3 mb-0 d-flex flex-column gap-2">
            <li>Acesse o seu computador com o <strong>SHOficina instalado</strong></li>
            <li>Localize o arquivo <code>Dados.MDB</code> na pasta do SHOficina</li>
            <li>Execute o script de conversão disponibilizado pelo suporte FixaOS</li>
            <li>O script gera um arquivo <code>migracao_shoficina.sql</code></li>
            <li>Faça o upload desse arquivo aqui</li>
          </ol>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">
          <i class="bi bi-list-check me-2 text-success"></i>O que será importado
        </div>
        <div class="card-body">
          <div class="d-flex flex-column gap-2 small">
            <?php foreach([
              ['bi-people-fill','#0d6efd','Clientes','Nome, telefone, endereço e CPF/CNPJ'],
              ['bi-clipboard2-pulse','#198754','Ordens de Serviço','Número, defeito, valor e datas'],
              ['bi-cpu','#f97316','Equipamentos','Tipo, marca, modelo e série'],
              ['bi-tools','#6f42c1','Serviços realizados','Descrição e valores de cada OS'],
              ['bi-box-seam','#dc3545','Peças utilizadas','Peças e valores'],
              ['bi-tags','#6c757d','Status','Vinculados aos status do FixaOS'],
            ] as[$ic,$c,$t,$d]):?>
            <div class="d-flex align-items-start gap-2">
              <div style="width:30px;height:30px;border-radius:8px;background:<?=$c?>15;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi <?=$ic?>" style="color:<?=$c?>;font-size:.85rem"></i>
              </div>
              <div>
                <div class="fw-semibold"><?=$t?></div>
                <div class="text-muted" style="font-size:.75rem"><?=$d?></div>
              </div>
            </div>
            <?php endforeach;?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function previewArquivo(input) {
  const file = input.files[0];
  if (!file) return;
  document.getElementById('nomeArq').textContent = file.name;
  const kb = (file.size / 1024).toFixed(0);
  const mb = (file.size / 1024 / 1024).toFixed(2);
  document.getElementById('tamanhoArq').textContent = file.size > 1024*1024 ? mb + ' MB' : kb + ' KB';
  document.getElementById('previewArq').classList.remove('d-none');
  verificarBotao();
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor = '#dee2e6';
  document.getElementById('dropZone').style.background  = '';
  const file = e.dataTransfer.files[0];
  if (file && file.name.endsWith('.sql')) {
    const input = document.getElementById('sqlFile');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewArquivo(input);
  }
}

document.getElementById('confirmar').addEventListener('change', verificarBotao);

function verificarBotao() {
  const temArq = document.getElementById('sqlFile').files.length > 0;
  const confirmou = document.getElementById('confirmar').checked;
  document.getElementById('btnImportar').disabled = !(temArq && confirmou);
}

document.getElementById('formMigracao').addEventListener('submit', function() {
  const btn = document.getElementById('btnImportar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importando... aguarde';
});
</script>
