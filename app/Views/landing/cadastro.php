<?php $titulo = 'Criar conta'; ?>

<section class="py-5" style="background: linear-gradient(135deg,#f0f4ff 0%,#fff 100%); min-height: calc(100vh - 64px)">
  <div class="container">
    <div class="row justify-content-center g-5 align-items-start">

      <!-- Benefícios -->
      <div class="col-lg-5 d-none d-lg-block sticky-top" style="top:80px">
        <div class="mb-4">
          <div class="hero-badge d-inline-block mb-3" style="background:rgba(13,110,253,.1);border:1px solid rgba(13,110,253,.2);color:#0d6efd;border-radius:100px;font-size:.8rem;padding:.3rem .9rem">
            <i class="bi bi-stars me-1"></i> 30 dias grátis • Sem cartão
          </div>
          <h2 class="fw-bold" style="font-size:2rem">Comece a usar <span style="color:#0d6efd">hoje mesmo</span></h2>
          <p class="text-muted">Configure sua assistência técnica em minutos e tenha controle total do seu negócio.</p>
        </div>
        <div class="d-flex flex-column gap-3">
          <?php foreach ([
            ['bi-clipboard2-pulse text-primary','Ordens de Serviço completas','Abertura, workflow de status, impressão de laudo e acompanhamento em tempo real.'],
            ['bi-people text-success','CRM integrado','Histórico completo de clientes, equipamentos atendidos e pipeline de negócios.'],
            ['bi-box-seam text-warning','Controle de estoque','Peças e componentes com alerta de mínimo e movimentação automática nas OS.'],
            ['bi-currency-dollar text-info','Financeiro','Contas a receber, fluxo de caixa e comissões dos técnicos.'],
            ['bi-shield-check text-success','Seus dados seguros','Backup automático, dados isolados por empresa e acesso protegido por senha.'],
          ] as [$icon,$title,$desc]): ?>
          <div class="d-flex gap-3 align-items-start">
            <div class="mt-1"><i class="bi <?= $icon ?> fs-5"></i></div>
            <div>
              <div class="fw-semibold small"><?= $title ?></div>
              <div class="text-muted" style="font-size:.82rem"><?= $desc ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="card border-0 shadow-sm mt-4 p-3" style="background:#f8f9fa">
          <div class="d-flex gap-2 align-items-center">
            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;font-size:.8rem">MB</div>
            <div>
              <div class="fw-semibold small">Marcos Borges</div>
              <div class="text-warning small">★★★★★</div>
            </div>
          </div>
          <p class="text-muted small mt-2 mb-0">"Antes eu controlava tudo no papel. Hoje tenho 200+ OS organizadas, meu estoque certinho e sei exatamente o que entrou de receita no mês."</p>
        </div>
      </div>

      <!-- Formulário -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-lg" style="border-radius:20px">
          <div class="card-body p-4 p-md-5">
            <h3 class="fw-bold mb-1">Criar conta gratuita</h3>
            <p class="text-muted small mb-4">Preencha os dados da sua assistência técnica. Leva menos de 2 minutos.</p>

            <?php $err = flash('error'); if ($err): ?>
            <div class="alert alert-danger py-2 small"><?= e($err) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/cadastrar') ?>" id="formCadastro" novalidate>
              <?= csrf_field() ?>

              <!-- Dados da empresa -->
              <div class="mb-3 pb-3 border-bottom">
                <div class="small fw-bold text-muted text-uppercase mb-3" style="letter-spacing:.06em">
                  <i class="bi bi-building me-1"></i> Dados da Assistência Técnica
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Nome da assistência *</label>
                  <input type="text" name="nome_fantasia" class="form-control form-control-lg" placeholder="Ex: TechFix Eletrônicos" required value="<?= e($_POST['nome_fantasia'] ?? '') ?>">
                  <div class="form-text">Como vai aparecer no sistema e nas ordens de serviço.</div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">CNPJ / CPF</label>
                    <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" id="cnpjInput" value="<?= e($_POST['cnpj'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Telefone / WhatsApp</label>
                    <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($_POST['telefone'] ?? '') ?>">
                  </div>
                  <div class="col-md-8">
                    <label class="form-label small fw-semibold">Cidade</label>
                    <input type="text" name="cidade" class="form-control" placeholder="São Paulo" value="<?= e($_POST['cidade'] ?? '') ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">UF</label>
                    <select name="uf" class="form-select">
                      <option value="">—</option>
                      <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                      <option value="<?= $uf ?>" <?= ($_POST['uf']??'')===$uf?'selected':'' ?>><?= $uf ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Dados do administrador -->
              <div class="mb-3 pb-3 border-bottom">
                <div class="small fw-bold text-muted text-uppercase mb-3" style="letter-spacing:.06em">
                  <i class="bi bi-person me-1"></i> Dados do Administrador
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Seu nome completo *</label>
                  <input type="text" name="adm_nome" class="form-control form-control-lg" placeholder="João da Silva" required value="<?= e($_POST['adm_nome'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-semibold">E-mail de acesso *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="seu@email.com" required value="<?= e($_POST['email'] ?? '') ?>">
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Senha *</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock"></i></span>
                      <input type="password" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" id="senhaInput" required>
                      <button type="button" class="btn btn-outline-secondary" onclick="toggleSenha('senhaInput',this)"><i class="bi bi-eye"></i></button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Confirmar senha *</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                      <input type="password" name="senha_confirm" class="form-control" placeholder="Repita a senha" id="senha2Input" required>
                      <button type="button" class="btn btn-outline-secondary" onclick="toggleSenha('senha2Input',this)"><i class="bi bi-eye"></i></button>
                    </div>
                  </div>
                </div>
                <!-- Força da senha -->
                <div class="mt-2">
                  <div class="progress" style="height:4px">
                    <div class="progress-bar" id="senhaForca" style="width:0%;transition:.3s"></div>
                  </div>
                  <div id="senhaMsg" class="text-muted" style="font-size:.75rem;margin-top:3px"></div>
                </div>
              </div>

              <!-- Termos -->
              <div class="mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="termos" required>
                  <label class="form-check-label small text-muted" for="termos">
                    Concordo com os <a href="#" class="text-primary">Termos de Uso</a> e <a href="#" class="text-primary">Política de Privacidade</a>
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold" id="btnSubmit">
                <i class="bi bi-rocket-takeoff me-2"></i>Criar conta grátis
              </button>

              <p class="text-center text-muted small mt-3 mb-0">
                Já tem conta? <a href="<?= url('/login') ?>" class="text-primary fw-semibold">Fazer login</a>
              </p>
            </form>
          </div>
        </div>

        <!-- Selos de confiança -->
        <div class="d-flex justify-content-center gap-4 mt-4 text-muted small">
          <span><i class="bi bi-shield-lock me-1"></i>Dados seguros</span>
          <span><i class="bi bi-credit-card me-1"></i>Sem cartão</span>
          <span><i class="bi bi-x-circle me-1"></i>Cancele quando quiser</span>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
// Mostrar/ocultar senha
function toggleSenha(id, btn) {
  const input = document.getElementById(id);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// Força da senha
document.getElementById('senhaInput').addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if (v.length >= 6)  score += 25;
  if (v.length >= 10) score += 25;
  if (/[A-Z]/.test(v)) score += 25;
  if (/[0-9!@#$%]/.test(v)) score += 25;
  const bar = document.getElementById('senhaForca');
  const msg = document.getElementById('senhaMsg');
  bar.style.width = score + '%';
  bar.className = 'progress-bar bg-' + ({25:'danger',50:'warning',75:'info',100:'success'}[score] || 'danger');
  msg.textContent = ({25:'Fraca',50:'Média',75:'Boa',100:'Forte'}[score] || '');
});

// Validação no submit
document.getElementById('formCadastro').addEventListener('submit', function(e) {
  const s1 = document.getElementById('senhaInput').value;
  const s2 = document.getElementById('senha2Input').value;
  if (s1 !== s2) {
    e.preventDefault();
    document.getElementById('senha2Input').classList.add('is-invalid');
    document.getElementById('senha2Input').focus();
    return;
  }
  if (!document.getElementById('termos').checked) {
    e.preventDefault();
    document.getElementById('termos').focus();
    return;
  }
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Criando conta...';
});
// Máscaras do form de cadastro aplicadas pelo masks.js global
</script>
