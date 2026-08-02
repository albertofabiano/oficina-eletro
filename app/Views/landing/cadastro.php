<?php $titulo = 'Criar conta gratuita'; ?>
<style>
.cad-wrap{min-height:calc(100vh - 60px);background:#0b0d10;display:flex}

/* Lado esquerdo — benefícios */
.cad-left{
  width:42%;flex-shrink:0;
  background:linear-gradient(160deg,#0f1e3a 0%,#0b0d10 100%);
  border-right:1px solid rgba(255,255,255,.06);
  padding:3rem 2.5rem;
  display:flex;flex-direction:column;justify-content:center;
  position:sticky;top:60px;height:calc(100vh - 60px);overflow-y:auto;
}
@media(max-width:991px){.cad-left{display:none}}

/* Lado direito — formulário */
.cad-right{
  flex:1;padding:3rem 2.5rem;
  display:flex;flex-direction:column;justify-content:center;
  max-width:580px;margin:auto;
}
@media(max-width:767px){.cad-right{padding:2rem 1.2rem}}

/* Benefícios */
.ben-item{display:flex;gap:.9rem;align-items:flex-start;margin-bottom:1.4rem}
.ben-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;margin-top:1px}
.ben-title{color:#fff;font-weight:700;font-size:.92rem;margin-bottom:.2rem}
.ben-desc{color:#4b5563;font-size:.82rem;line-height:1.6}

/* Form */
.cad-section{margin-bottom:1.4rem}
.cad-section-title{
  display:flex;align-items:center;gap:.5rem;
  color:#64748b;font-size:.72rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.08em;
  padding-bottom:.6rem;margin-bottom:1rem;
  border-bottom:1px solid rgba(255,255,255,.06);
}
.cad-label{color:#94a3b8;font-size:.82rem;font-weight:600;display:block;margin-bottom:.4rem}
.cad-input{
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.1);
  color:#fff;border-radius:10px;
  padding:.7rem 1rem;font-size:.9rem;width:100%;
  transition:.2s;outline:none;
}
.cad-input::placeholder{color:#374151}
.cad-input:focus{border-color:#f97316;background:rgba(255,255,255,.07);box-shadow:0 0 0 3px rgba(249,115,22,.15)}
.cad-input option{background:#1a1d23;color:#fff}
.cad-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .8rem center}
.cad-input-wrap{position:relative}
.cad-input-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#6b7280;pointer-events:none}
.cad-input-wrap .cad-input{padding-left:2.6rem}

/* Botão Google */
.btn-google{
  width:100%;background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.12);
  color:#e2e8f0;border-radius:12px;
  padding:.8rem 1.4rem;font-weight:600;font-size:.95rem;
  display:flex;align-items:center;justify-content:center;gap:.6rem;
  text-decoration:none;transition:.2s;
}
.btn-google:hover{background:rgba(255,255,255,.09);color:#fff;border-color:rgba(255,255,255,.2)}

/* Divisor */
.divider{display:flex;align-items:center;gap:.8rem;margin:1rem 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07)}
.divider span{color:#374151;font-size:.78rem}

/* Toggle senha */
.input-eye{position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:.25rem}
.input-eye:hover{color:#94a3b8}

/* Força senha */
.senha-bar{height:4px;border-radius:2px;background:rgba(255,255,255,.06);margin-top:.5rem;overflow:hidden}
.senha-bar-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%}

/* Botão submit */
.btn-submit{
  background:linear-gradient(135deg,#f97316,#ea6c0a);
  border:none;color:#fff;border-radius:12px;
  padding:.9rem 1.4rem;font-weight:800;font-size:1rem;width:100%;
  box-shadow:0 8px 24px rgba(249,115,22,.3);
  transition:.2s;display:flex;align-items:center;justify-content:center;gap:.5rem;
}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 12px 32px rgba(249,115,22,.4)}

/* Selos */
.selos{display:flex;justify-content:center;gap:1.2rem;flex-wrap:wrap;margin-top:1.2rem}
.selo{display:flex;align-items:center;gap:.35rem;color:#374151;font-size:.76rem}
.selo i{color:#4ade80}

/* Depoimento */
.testi-box{
  background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
  border-radius:14px;padding:1.2rem;margin-top:2rem;
}

/* Stats */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem;margin-bottom:2rem}
.stat-box{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:.9rem;text-align:center}
.stat-num{color:#fff;font-weight:900;font-size:1.2rem;line-height:1}
.stat-lbl{color:#4b5563;font-size:.72rem;margin-top:.2rem}
</style>

<div class="cad-wrap">

  <!-- Lado esquerdo -->
  <div class="cad-left">

    <!-- Logo -->
    <div class="mb-4">
      <svg width="110" height="28" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </div>

    <!-- Headline -->
    <div style="margin-bottom:1.8rem">
      <div style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.2);color:#fb923c;border-radius:20px;font-size:.75rem;font-weight:700;padding:.3rem .9rem;margin-bottom:.9rem">
        <i class="bi bi-stars"></i> 15 dias grátis · sem cartão
      </div>
      <h2 style="color:#fff;font-size:1.7rem;font-weight:900;line-height:1.2;margin-bottom:.6rem">
        Comece a usar<br><span style="color:#f97316">hoje mesmo</span>
      </h2>
      <p style="color:#4b5563;font-size:.88rem;line-height:1.7">Configure sua assistência técnica em minutos e tenha controle total do seu negócio.</p>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <?php foreach([['1.200+','Assistências'],['80k+','OS criadas'],['4,7★','Avaliação']] as[$n,$l]):?>
      <div class="stat-box"><div class="stat-num"><?=$n?></div><div class="stat-lbl"><?=$l?></div></div>
      <?php endforeach;?>
    </div>

    <!-- Benefícios -->
    <?php foreach([
      ['bi-clipboard2-pulse-fill','rgba(59,130,246,.15)','#60a5fa','OS ilimitadas','Abra, acompanhe e finalize com status, impressão e WhatsApp.'],
      ['bi-people-fill','rgba(34,197,94,.12)','#4ade80','CRM + Clientes','Histórico completo, pipeline de vendas e pós-atendimento.'],
      ['bi-graph-up-arrow','rgba(168,85,247,.12)','#c084fc','Financeiro completo','Fluxo de caixa, comissões e DRE simplificado.'],
      ['bi-geo-alt-fill','rgba(249,115,22,.12)','#fb923c','Página pública no Google','Seus clientes te encontram pelo buscador com SEO.'],
      ['bi-shop','rgba(251,191,36,.12)','#fbbf24','Marketplace de peças','Compre e venda peças com outras assistências.'],
    ] as[$ic,$bg,$c,$t,$d]):?>
    <div class="ben-item">
      <div class="ben-icon" style="background:<?=$bg?>"><i class="bi <?=$ic?>" style="color:<?=$c?>"></i></div>
      <div><div class="ben-title"><?=$t?></div><div class="ben-desc"><?=$d?></div></div>
    </div>
    <?php endforeach;?>

    <!-- Depoimento -->
    <div class="testi-box">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div style="width:36px;height:36px;border-radius:50%;background:#22c55e;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;flex-shrink:0">MB</div>
        <div>
          <div style="color:#fff;font-weight:700;font-size:.85rem">Marcos Borges</div>
          <div style="color:#f59e0b;font-size:.78rem">★★★★★</div>
        </div>
      </div>
      <p style="color:#6b7280;font-size:.82rem;line-height:1.7;margin:0">"Antes eu controlava tudo no papel. Hoje tenho 200+ OS organizadas, meu estoque certinho e sei exatamente o que entrou de receita no mês."</p>
    </div>

  </div>

  <!-- Lado direito — formulário -->
  <div class="cad-right">

    <div class="mb-4">
      <h1 style="color:#fff;font-size:1.6rem;font-weight:900;margin-bottom:.3rem">Criar conta gratuita</h1>
      <p style="color:#64748b;font-size:.88rem">Leva menos de 2 minutos. Sem cartão de crédito.</p>
    </div>

    <?php if (!empty($vagas) && !empty($planoAutonomo)): ?>
    <div style="background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.25);border-radius:10px;padding:.7rem 1rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem">
      <i class="bi bi-fire" style="color:#f97316;font-size:1.1rem;flex-shrink:0"></i>
      <?php if (!$vagas['esgotado']): ?>
      <div style="color:#fdba74;font-size:.82rem;line-height:1.4">
        <strong>R$ <?= number_format($planoAutonomo['preco_mensal']/100, 2, ',', '.') ?>/mês</strong> no plano Autônomo é valor de lançamento pros primeiros <strong><?= $vagas['limite'] ?></strong> assinantes — restam <strong><?= $vagas['restantes'] ?></strong> vagas. Depois disso o valor sobe pra R$ <?= number_format($planoAutonomo['preco_pos_intro']/100, 2, ',', '.') ?>/mês.
      </div>
      <?php else: ?>
      <div style="color:#fdba74;font-size:.82rem;line-height:1.4">
        As <?= $vagas['limite'] ?> vagas de lançamento do plano Autônomo já acabaram — o valor atual é R$ <?= number_format($planoAutonomo['preco_pos_intro']/100, 2, ',', '.') ?>/mês.
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php $err = flash('error'); if ($err): ?>
    <div class="alert alert-danger py-2 small mb-3 rounded-3"><?= e($err) ?></div>
    <?php endif; ?>

    <?php
    $gSignup = $_SESSION['google_signup'] ?? null;
    if ($gSignup): unset($_SESSION['google_signup']); endif;
    ?>

    <?php if(!$gSignup): ?>
    <a href="<?= url('/auth/google') ?>" class="btn-google mb-2">
      <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
      Cadastrar com Google
    </a>
    <div class="divider"><span>ou preencha manualmente</span></div>
    <?php endif; ?>

    <?php if($gSignup): ?>
    <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:.7rem 1rem;font-size:.85rem;color:#4ade80;margin-bottom:1rem">
      <i class="bi bi-google me-1"></i>Conta Google vinculada! Complete os dados abaixo.
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/cadastrar') ?>" id="formCadastro" novalidate>
      <?= csrf_field() ?>

      <!-- Honeypot anti-bot: invisível para humanos; robôs preenchem e são descartados -->
      <div style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden" aria-hidden="true">
        <label>Não preencha este campo
          <input type="text" name="website" tabindex="-1" autocomplete="off">
        </label>
      </div>

      <!-- Tipo de conta: padrão sistema completo; ?tipo=diretorio mantém o fluxo de reivindicação do diretório -->
      <input type="hidden" name="tipo_conta" value="<?= (($_GET['tipo'] ?? '') === 'diretorio') ? 'diretorio' : 'completo' ?>">

      <!-- Dados da empresa -->
      <div class="cad-section">
        <div class="cad-section-title">
          <i class="bi bi-building-fill" style="color:#60a5fa"></i> Dados da Assistência Técnica
        </div>

        <div class="mb-3">
          <label class="cad-label">Nome da assistência *</label>
          <input type="text" name="nome_fantasia" class="cad-input" placeholder="Ex: TechFix Eletrônicos" required value="<?= e($_POST['nome_fantasia'] ?? '') ?>">
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="cad-label">CNPJ / CPF</label>
            <input type="text" name="cnpj" id="cnpjInput" class="cad-input" placeholder="00.000.000/0000-00" value="<?= e($_POST['cnpj'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="cad-label">Telefone / WhatsApp</label>
            <input type="text" name="telefone" class="cad-input" placeholder="(00) 00000-0000" value="<?= e($_POST['telefone'] ?? '') ?>">
          </div>
          <div class="col-8">
            <label class="cad-label">Cidade</label>
            <input type="text" name="cidade" class="cad-input" placeholder="São Paulo" value="<?= e($_POST['cidade'] ?? '') ?>">
          </div>
          <div class="col-4">
            <label class="cad-label">UF</label>
            <select name="uf" class="cad-input cad-select">
              <option value="">—</option>
              <?php foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
              <option value="<?= $uf ?>" <?= ($_POST['uf']??'')===$uf?'selected':'' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Dados do admin -->
      <div class="cad-section">
        <div class="cad-section-title">
          <i class="bi bi-person-fill" style="color:#fb923c"></i> Dados do Administrador
        </div>

        <?php if($gSignup): ?>
        <input type="hidden" name="google_id" value="<?= e($gSignup['google_id']) ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="cad-label">Nome completo *</label>
          <input type="text" name="adm_nome" class="cad-input" placeholder="João da Silva" required value="<?= e($_POST['adm_nome'] ?? $gSignup['nome'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="cad-label">E-mail *</label>
          <div class="cad-input-wrap">
            <i class="bi bi-envelope cad-input-icon"></i>
            <input type="email" name="email" class="cad-input" placeholder="seu@email.com" required
                   value="<?= e($_POST['email'] ?? $gSignup['email'] ?? '') ?>"
                   <?= $gSignup?'readonly':'' ?>>
          </div>
        </div>

        <?php if(!$gSignup): ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="cad-label">Senha *</label>
            <div class="cad-input-wrap" style="position:relative">
              <i class="bi bi-lock cad-input-icon"></i>
              <input type="password" name="senha" id="senhaInput" class="cad-input" placeholder="Mín. 6 caracteres" required style="padding-right:2.5rem">
              <button type="button" class="input-eye" onclick="toggleSenha('senhaInput',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="senha-bar"><div class="senha-bar-fill" id="senhaForca"></div></div>
            <div id="senhaMsg" style="font-size:.72rem;color:#4b5563;margin-top:.3rem"></div>
          </div>
          <div class="col-md-6">
            <label class="cad-label">Confirmar senha *</label>
            <div class="cad-input-wrap" style="position:relative">
              <i class="bi bi-lock-fill cad-input-icon"></i>
              <input type="password" name="senha_confirm" id="senha2Input" class="cad-input" placeholder="Repita a senha" required style="padding-right:2.5rem">
              <button type="button" class="input-eye" onclick="toggleSenha('senha2Input',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <?php else: ?>
        <input type="hidden" name="senha" value="<?= bin2hex(random_bytes(16)) ?>">
        <input type="hidden" name="senha_confirm" value="">
        <?php endif; ?>
      </div>

      <!-- Termos -->
      <div class="mb-3 d-flex align-items-start gap-2">
        <input type="checkbox" id="termos" required style="margin-top:3px;accent-color:#f97316;width:16px;height:16px;flex-shrink:0">
        <label for="termos" style="color:#6b7280;font-size:.82rem;line-height:1.5;cursor:pointer">
          Concordo com os <a href="#" style="color:#f97316;text-decoration:none">Termos de Uso</a> e <a href="#" style="color:#f97316;text-decoration:none">Política de Privacidade</a>
        </label>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-submit" id="btnSubmit">
        <i class="bi bi-rocket-takeoff-fill fs-5"></i>
        Criar conta grátis
      </button>

      <!-- Selos -->
      <div class="selos">
        <span class="selo"><i class="bi bi-shield-lock-fill"></i>Dados seguros</span>
        <span class="selo"><i class="bi bi-credit-card-fill"></i>Sem cartão</span>
        <span class="selo"><i class="bi bi-x-circle-fill"></i>Cancele quando quiser</span>
      </div>

      <p style="text-align:center;color:#374151;font-size:.82rem;margin-top:1.2rem">
        Já tem conta? <a href="<?= url('/login') ?>" style="color:#f97316;font-weight:700;text-decoration:none">Fazer login</a>
      </p>

    </form>
  </div>

</div>

<script>
function toggleSenha(id, btn) {
  const input = document.getElementById(id);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
}

document.getElementById('senhaInput')?.addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if(v.length >= 6) score += 25;
  if(v.length >= 10) score += 25;
  if(/[A-Z]/.test(v)) score += 25;
  if(/[0-9!@#$%]/.test(v)) score += 25;
  const fill = document.getElementById('senhaForca');
  const msg  = document.getElementById('senhaMsg');
  fill.style.width = score + '%';
  fill.style.background = {25:'#ef4444',50:'#f59e0b',75:'#3b82f6',100:'#22c55e'}[score] || '#ef4444';
  msg.textContent = {25:'Fraca',50:'Média',75:'Boa',100:'Forte'}[score] || '';
  msg.style.color = {25:'#ef4444',50:'#f59e0b',75:'#3b82f6',100:'#22c55e'}[score] || '#ef4444';
});

document.getElementById('formCadastro').addEventListener('submit', function(e) {
  const s1 = document.getElementById('senhaInput')?.value;
  const s2 = document.getElementById('senha2Input')?.value;
  if(s1 !== undefined && s1 !== s2) {
    e.preventDefault();
    document.getElementById('senha2Input').style.borderColor = '#ef4444';
    document.getElementById('senha2Input').focus();
    return;
  }
  if(!document.getElementById('termos').checked) {
    e.preventDefault();
    document.getElementById('termos').focus();
    return;
  }
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Criando conta...';
});
</script>
