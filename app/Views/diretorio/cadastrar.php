<?php
  $gSignup = $_SESSION['google_signup'] ?? null;
  if ($gSignup) { unset($_SESSION['google_signup']); }
  $preNome  = $gSignup['nome']  ?? ($_POST['nome']  ?? '');
  $preEmail = $gSignup['email'] ?? ($_POST['email'] ?? '');

  // Rascunho dos dados da empresa preservado se uma validação anterior falhou — ninguém
  // deveria ter que redigitar nome/cidade/UF/WhatsApp só porque errou a senha, por exemplo.
  $rascunho = $_SESSION['cadastro_empresa_rascunho'] ?? [];
  unset($_SESSION['cadastro_empresa_rascunho']);
  $preNomeEmpresa = $rascunho['nomeEmpresa'] ?? ($_POST['nome_fantasia'] ?? '');
  $preCidade      = $rascunho['cidade']      ?? ($_POST['cidade'] ?? '');
  $preUf          = $rascunho['uf']          ?? ($_POST['uf'] ?? '');
  $preWhatsapp    = $rascunho['whatsapp']    ?? ($_POST['whatsapp_publico'] ?? '');

  $ufs = [
    'AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará',
    'DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso',
    'MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná',
    'PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte',
    'RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina',
    'SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins',
  ];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#0a1526;
  --brand:#f97316;
  --ink:#111827;
  --muted:#64748b;
  --line:#d8dee9;
  --ok:#16a34a;
  --err:#dc2626;
}
*{box-sizing:border-box}
.cad-page{
  min-height:100vh;
  background:var(--navy);
  padding:48px 16px 24px;
  font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
  color:var(--ink);
}
.cad-wrap{max-width:900px;margin:0 auto}
.cad-brand{text-align:center;margin-bottom:22px}
.cad-brand a{text-decoration:none;font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cad-brand a span{color:var(--brand)}
.cad-brand .tag{color:#94a3b8;font-size:.88rem;margin-top:6px}

.cad-card{
  background:#fff;border-radius:20px;overflow:hidden;
  box-shadow:0 24px 60px rgba(0,0,0,.35);
  border-top:4px solid var(--brand);
}
.cad-head{padding:28px 30px 22px}
.cad-head h1{
  font-family:'Fraunces',serif;font-weight:600;font-size:1.7rem;
  margin:0 0 8px;color:var(--ink);letter-spacing:-.01em;
}
.cad-head p{margin:0;color:var(--muted);font-size:.94rem;line-height:1.5;max-width:56ch}

/* Elemento marcante: canhoto picotado, como o de uma ficha de Ordem de Serviço */
.cad-perf{
  position:relative;height:0;border-top:2px dashed var(--line);margin:0 30px;
}
.cad-perf::before,.cad-perf::after{
  content:'';position:absolute;top:-7px;width:14px;height:14px;border-radius:50%;
  background:var(--navy);
}
.cad-perf::before{left:-37px}
.cad-perf::after{right:-37px}

.cad-body{padding:26px 30px 30px}
.cad-grid{display:grid;grid-template-columns:1fr 1px 1fr;gap:26px}
.cad-divider{background:var(--line)}
.cad-col h2{
  font-family:'IBM Plex Mono',monospace;font-size:.72rem;font-weight:600;
  letter-spacing:.09em;text-transform:uppercase;color:var(--muted);
  margin:0 0 16px;display:flex;align-items:center;gap:.4rem;
}
.cad-col h2 i{color:var(--brand);font-style:normal}

.cad-field{margin-bottom:16px}
.cad-field label{
  display:block;font-family:'IBM Plex Mono',monospace;font-size:.7rem;font-weight:600;
  letter-spacing:.05em;text-transform:uppercase;color:#475569;margin-bottom:6px;
}
.cad-field label .req{color:var(--brand);margin-left:2px}
.cad-input{
  width:100%;min-height:48px;padding:.6rem .8rem;font-size:16px;font-family:'Inter',sans-serif;
  border:1.5px solid var(--line);border-radius:10px;color:var(--ink);background:#fff;
  transition:border-color .15s,box-shadow .15s;
}
.cad-input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(249,115,22,.15)}
.cad-input[aria-invalid="true"]{border-color:var(--err)}
.cad-input[aria-invalid="true"]:focus{box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.cad-input.ok{border-color:var(--ok)}
.cad-hint{font-size:.8rem;color:var(--muted);margin-top:5px;line-height:1.45}
.cad-error{font-size:.8rem;color:var(--err);margin-top:5px;display:none;font-weight:600}
.cad-error.show{display:block}

.cad-pwd-wrap{position:relative}
.cad-eye{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;
  color:#94a3b8;cursor:pointer;padding:.3rem;line-height:0;
}
.cad-eye:hover{color:#475569}

.cad-meter{display:flex;gap:5px;margin-top:8px}
.cad-meter i{flex:1;height:4px;border-radius:2px;background:var(--line);transition:background .2s}
.cad-meter-label{font-size:.76rem;color:var(--muted);margin-top:5px;font-weight:600}

.cad-match{display:flex;align-items:center;gap:.35rem;font-size:.8rem;margin-top:5px;font-weight:600;min-height:1.1em}
.cad-match.ok{color:var(--ok)}
.cad-match.err{color:var(--err)}

.cad-google{
  display:flex;align-items:center;justify-content:center;gap:.6rem;width:100%;min-height:48px;
  border:1.5px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);
  font-weight:700;font-size:.94rem;text-decoration:none;transition:background .15s;margin-bottom:16px;
}
.cad-google:hover{background:#f8fafc;color:var(--ink)}
.cad-div{display:flex;align-items:center;gap:.8rem;margin:0 0 16px;color:#94a3b8;font-size:.8rem}
.cad-div::before,.cad-div::after{content:'';flex:1;height:1px;background:var(--line)}
.cad-google-linked{
  background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.25);border-radius:10px;
  padding:.65rem 1rem;font-size:.85rem;color:var(--ok);margin-bottom:16px;font-weight:600;
}

/* Prévia ao vivo — o que torna concreta a promessa de "vai ao ar na hora" */
.cad-preview{
  border:1.5px dashed var(--line);border-radius:12px;padding:14px;margin-top:6px;
  display:flex;gap:12px;align-items:flex-start;background:#fafbfc;
}
.cad-preview-ico{
  width:38px;height:38px;border-radius:9px;background:var(--brand);color:#fff;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;
  font-family:'Fraunces',serif;
}
.cad-preview-info{min-width:0;flex:1}
.cad-preview-nome{font-weight:700;font-size:.92rem;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cad-preview-meta{font-size:.78rem;color:var(--muted);margin-top:1px}
.cad-preview-url{
  font-family:'IBM Plex Mono',monospace;font-size:.76rem;color:var(--brand);margin-top:5px;
  word-break:break-all;
}
.cad-preview-placeholder{font-size:.82rem;color:#94a3b8;font-style:italic}

.cad-submit-wrap{margin-top:24px}
.cad-submit{
  width:100%;min-height:52px;background:var(--brand);color:#fff;font-weight:700;font-size:1rem;
  border:none;border-radius:12px;cursor:pointer;transition:background .15s,transform .1s;
  display:flex;align-items:center;justify-content:center;gap:.5rem;
}
.cad-submit:hover:not(:disabled){background:#ea580c}
.cad-submit:disabled{opacity:.75;cursor:default}
.cad-spinner{
  width:16px;height:16px;border-radius:50%;border:2.5px solid rgba(255,255,255,.4);
  border-top-color:#fff;animation:cadspin .7s linear infinite;display:none;
}
.cad-submit.loading .cad-spinner{display:inline-block}
@media (prefers-reduced-motion: reduce){ .cad-spinner{animation:none} }
@keyframes cadspin{to{transform:rotate(360deg)}}

.cad-login{text-align:center;color:var(--muted);font-size:.86rem;margin-top:14px}
.cad-login a{color:var(--brand);font-weight:700;text-decoration:none}

.cad-foot{text-align:center;color:#94a3b8;font-size:.78rem;margin-top:20px;line-height:1.6}
.cad-foot a{color:#cbd5e1}
.cad-foot .hl{color:#fdba74}

/* Responsivo: uma coluna no mobile, divider vira linha horizontal, botão fixo no rodapé */
@media (max-width:767.98px){
  .cad-grid{grid-template-columns:1fr;gap:0}
  .cad-divider{height:1px;width:100%;margin:6px 0 20px}
  .cad-perf{margin:0 18px}
  .cad-perf::before{left:-25px}
  .cad-perf::after{right:-25px}
  .cad-head,.cad-body{padding-left:20px;padding-right:20px}
  .cad-submit-wrap{
    position:sticky;bottom:0;margin:24px -20px -30px;padding:12px 20px calc(12px + env(safe-area-inset-bottom));
    background:linear-gradient(to top,#fff 60%,rgba(255,255,255,0));
  }
}
</style>

<div class="cad-page">
  <div class="cad-wrap">

    <div class="cad-brand">
      <a href="<?= url('/assistencias') ?>">Fixa<span>OS</span></a>
      <div class="tag">Diretório de Assistências Técnicas</div>
    </div>

    <div class="cad-card">
      <div class="cad-head">
        <h1>Cadastre sua assistência técnica — grátis</h1>
        <p>Um cadastro só, sem passo 2. Preencha os dois blocos abaixo e sua página pública vai ao ar na hora, assim que você enviar.</p>
      </div>

      <div class="cad-perf"></div>

      <div class="cad-body">
        <?php foreach(['error','info','success'] as $t): $m = flash($t); if($m): ?>
          <div class="cad-error show" style="position:static;margin-bottom:16px;padding:.7rem 1rem;background:<?= $t==='error'?'#fef2f2':'#f0f9ff' ?>;border-radius:10px"><?= e($m) ?></div>
        <?php endif; endforeach; ?>

        <form method="POST" action="<?= url('/diretorio/cadastrar') ?>" autocomplete="off" id="formCadastro" novalidate>
          <?= csrf_field() ?>
          <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
          <?php if($gSignup): ?><input type="hidden" name="google_id" value="<?= e($gSignup['google_id']) ?>"><?php endif; ?>

          <div class="cad-grid">

            <!-- Bloco 1 -->
            <div class="cad-col">
              <h2><i>①</i>Seus dados de acesso</h2>

              <?php if(!$gSignup): ?>
                <a href="<?= url('/auth/google?to=diretorio') ?>" class="cad-google">
                  <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                  Continuar com Google
                </a>
                <div class="cad-div"><span>ou preencha à mão</span></div>
              <?php else: ?>
                <div class="cad-google-linked"><i class="bi bi-google"></i> Conta Google vinculada! Confirme abaixo pra continuar.</div>
              <?php endif; ?>

              <div class="cad-field">
                <label for="fNome">Seu nome<span class="req">*</span></label>
                <input type="text" name="nome" id="fNome" class="cad-input" required maxlength="100"
                       autocomplete="name" placeholder="Seu nome completo" value="<?= e($preNome) ?>"
                       aria-describedby="err-nome">
                <div class="cad-error" id="err-nome" role="alert"></div>
              </div>

              <div class="cad-field">
                <label for="fEmail">E-mail<span class="req">*</span></label>
                <input type="email" name="email" id="fEmail" class="cad-input" required maxlength="100"
                       autocomplete="email" placeholder="voce@empresa.com.br" value="<?= e($preEmail) ?>"
                       aria-describedby="err-email"
                       <?= $gSignup ? 'readonly style="background:#f1f5f9"' : '' ?>>
                <div class="cad-error" id="err-email" role="alert"></div>
              </div>

              <?php if($gSignup): ?>
                <input type="hidden" name="senha" value="<?= e(bin2hex(random_bytes(16))) ?>">
                <input type="hidden" name="senha_confirm" value="">
              <?php else: ?>
              <div class="cad-field">
                <label for="fSenha">Senha<span class="req">*</span></label>
                <div class="cad-pwd-wrap">
                  <input type="password" name="senha" id="fSenha" class="cad-input" required minlength="6"
                         autocomplete="new-password" placeholder="Mín. 6 caracteres" style="padding-right:2.6rem"
                         aria-describedby="err-senha">
                  <button type="button" class="cad-eye" data-target="fSenha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                </div>
                <div class="cad-meter" id="meterSenha" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                <div class="cad-meter-label" id="meterLabel">&nbsp;</div>
                <div class="cad-error" id="err-senha" role="alert"></div>
              </div>

              <div class="cad-field">
                <label for="fSenha2">Confirmar senha<span class="req">*</span></label>
                <div class="cad-pwd-wrap">
                  <input type="password" name="senha_confirm" id="fSenha2" class="cad-input" required minlength="6"
                         autocomplete="new-password" placeholder="Repita a senha" style="padding-right:2.6rem"
                         aria-describedby="err-senha2 matchSenha">
                  <button type="button" class="cad-eye" data-target="fSenha2" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                </div>
                <div class="cad-match" id="matchSenha">&nbsp;</div>
                <div class="cad-error" id="err-senha2" role="alert"></div>
              </div>
              <?php endif; ?>
            </div>

            <div class="cad-divider"></div>

            <!-- Bloco 2 -->
            <div class="cad-col">
              <h2><i>②</i>Sua empresa</h2>

              <div class="cad-field">
                <label for="fEmpresa">Nome da empresa<span class="req">*</span></label>
                <input type="text" name="nome_fantasia" id="fEmpresa" class="cad-input" required maxlength="100"
                       autocomplete="organization" placeholder="Ex.: Timetec Assistência Técnica" value="<?= e($preNomeEmpresa) ?>"
                       aria-describedby="err-empresa">
                <div class="cad-error" id="err-empresa" role="alert"></div>
              </div>

              <div style="display:flex;gap:12px">
                <div class="cad-field" style="flex:2">
                  <label for="fCidade">Cidade<span class="req">*</span></label>
                  <input type="text" name="cidade" id="fCidade" class="cad-input" required maxlength="80"
                         autocomplete="address-level2" placeholder="Ex.: São Paulo" value="<?= e($preCidade) ?>"
                         aria-describedby="err-cidade">
                  <div class="cad-error" id="err-cidade" role="alert"></div>
                </div>
                <div class="cad-field" style="flex:1">
                  <label for="fUf">UF<span class="req">*</span></label>
                  <select name="uf" id="fUf" class="cad-input" required autocomplete="address-level1" aria-describedby="err-uf">
                    <option value="">--</option>
                    <?php foreach($ufs as $sigla => $nomeUf): ?>
                    <option value="<?= $sigla ?>" <?= $preUf===$sigla?'selected':'' ?>><?= $sigla ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="cad-error" id="err-uf" role="alert"></div>
                </div>
              </div>

              <div class="cad-field">
                <label for="fWhats">WhatsApp público</label>
                <input type="tel" name="whatsapp_publico" id="fWhats" class="cad-input"
                       autocomplete="tel-national" inputmode="tel" placeholder="(11) 99999-9999" value="<?= e($preWhatsapp) ?>">
                <div class="cad-hint">Aparece no botão "Chamar no WhatsApp" da sua página. Pode preencher depois.</div>
              </div>

              <div class="cad-field" style="margin-bottom:0">
                <label style="margin-bottom:8px">Sua página vai ficar assim</label>
                <div class="cad-preview" id="cadPreview">
                  <div class="cad-preview-placeholder">Comece a digitar o nome da empresa pra ver a prévia da sua página pública.</div>
                </div>
              </div>
            </div>

          </div>

          <div class="cad-submit-wrap">
            <button type="submit" class="cad-submit" id="btnSubmit">
              <span class="cad-spinner"></span>
              <span id="btnSubmitLabel">Criar conta e publicar minha página</span>
            </button>
          </div>

          <p class="cad-login">Já tem cadastro? <a href="<?= url('/login') ?>">Entrar</a></p>
        </form>
      </div>
    </div>

    <p class="cad-foot">
      Conta grátis de diretório — não dá acesso ao sistema de gestão completo (que virá por convite).<br>
      Ao cadastrar, você concorda com os <a href="<?= url('/termos') ?>">Termos</a> e a
      <a href="<?= url('/privacidade') ?>">Privacidade</a>.<br>
      <span class="hl">Depois de publicar, você ainda edita logo, fotos, horário de funcionamento, redes sociais e lista de serviços em Empresa → Perfil público.</span>
    </p>
  </div>
</div>

<script>
(function () {
  var form = document.getElementById('formCadastro');
  var campos = {
    nome:     { el: document.getElementById('fNome'),    err: document.getElementById('err-nome') },
    email:    { el: document.getElementById('fEmail'),   err: document.getElementById('err-email') },
    senha:    { el: document.getElementById('fSenha'),   err: document.getElementById('err-senha') },
    senha2:   { el: document.getElementById('fSenha2'),  err: document.getElementById('err-senha2') },
    empresa:  { el: document.getElementById('fEmpresa'), err: document.getElementById('err-empresa') },
    cidade:   { el: document.getElementById('fCidade'),  err: document.getElementById('err-cidade') },
    uf:       { el: document.getElementById('fUf'),      err: document.getElementById('err-uf') },
  };
  var tocado = {};

  function setErro(campo, msg) {
    if (!campo.el) return;
    campo.el.setAttribute('aria-invalid', 'true');
    campo.el.classList.remove('ok');
    if (campo.err) { campo.err.textContent = msg; campo.err.classList.add('show'); }
  }
  function limparErro(campo) {
    if (!campo.el) return;
    campo.el.removeAttribute('aria-invalid');
    if (campo.err) { campo.err.textContent = ''; campo.err.classList.remove('show'); }
  }

  function validar(nome, mostrarSempre) {
    var c = campos[nome];
    if (!c || !c.el) return true;
    if (!mostrarSempre && !tocado[nome]) return true; // não acusa erro antes do 1º blur/envio

    var v = c.el.value.trim();
    var ok = true, msg = '';

    if (nome === 'nome' && !v) { ok = false; msg = 'Digite seu nome.'; }
    if (nome === 'email') {
      if (!v) { ok = false; msg = 'Digite seu e-mail.'; }
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { ok = false; msg = 'Digite um e-mail válido.'; }
    }
    if (nome === 'senha' && v.length < 6) { ok = false; msg = 'A senha precisa de pelo menos 6 caracteres.'; }
    if (nome === 'senha2') {
      var s1 = campos.senha.el ? campos.senha.el.value : '';
      if (v.length < 6) { ok = false; msg = 'A senha precisa de pelo menos 6 caracteres.'; }
      else if (v !== s1) { ok = false; msg = 'As senhas não são iguais.'; }
    }
    if (nome === 'empresa' && !v) { ok = false; msg = 'Digite o nome da empresa.'; }
    if (nome === 'cidade' && !v) { ok = false; msg = 'Digite a cidade.'; }
    if (nome === 'uf' && !v) { ok = false; msg = 'Selecione o estado.'; }

    if (ok) { limparErro(c); if (v) c.el.classList.add('ok'); } else { setErro(c, msg); }
    return ok;
  }

  Object.keys(campos).forEach(function (nome) {
    var c = campos[nome];
    if (!c.el) return;
    c.el.addEventListener('blur', function () { tocado[nome] = true; validar(nome, false); });
    c.el.addEventListener('input', function () { if (tocado[nome]) validar(nome, false); });
  });
  // Confirmar senha depende da senha principal também mudar
  if (campos.senha.el && campos.senha2.el) {
    campos.senha.el.addEventListener('input', function () { if (tocado.senha2) validar('senha2', false); });
  }

  // ── Medidor de força da senha (4 segmentos) ──
  var meter = document.getElementById('meterSenha');
  var meterLabel = document.getElementById('meterLabel');
  if (campos.senha.el && meter) {
    var barras = meter.querySelectorAll('i');
    var cores = ['#dc2626', '#f97316', '#eab308', '#16a34a'];
    var labels = ['Fraca', 'Razoável', 'Boa', 'Forte'];
    campos.senha.el.addEventListener('input', function () {
      var v = this.value;
      var score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10) score++;
      if (/[a-z]/.test(v) && /[A-Z0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) score++;
      score = Math.min(score, 4);
      barras.forEach(function (b, i) { b.style.background = i < score ? cores[Math.max(score - 1, 0)] : '#d8dee9'; });
      meterLabel.textContent = v ? labels[Math.max(score - 1, 0)] : ' ';
    });
  }

  // ── Confirmação visual de senha igual ──
  var matchBox = document.getElementById('matchSenha');
  if (campos.senha2.el && matchBox) {
    function checarMatch() {
      var v2 = campos.senha2.el.value;
      var v1 = campos.senha.el ? campos.senha.el.value : '';
      matchBox.classList.remove('ok', 'err');
      if (!v2) { matchBox.textContent = ' '; return; }
      if (v2 === v1) { matchBox.textContent = '✓ As senhas conferem'; matchBox.classList.add('ok'); }
      else { matchBox.textContent = ''; }
    }
    campos.senha2.el.addEventListener('input', checarMatch);
    if (campos.senha.el) campos.senha.el.addEventListener('input', checarMatch);
  }

  // ── Mostrar/ocultar senha ──
  document.querySelectorAll('.cad-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      var isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  });

  // ── Máscara de WhatsApp ──
  var whats = document.getElementById('fWhats');
  if (whats) {
    whats.addEventListener('input', function () {
      var d = this.value.replace(/\D/g, '').slice(0, 11);
      var out = d;
      if (d.length > 2) out = '(' + d.slice(0, 2) + ') ' + d.slice(2);
      if (d.length > 7) out = '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
      this.value = out;
      atualizarPreview();
    });
  }

  // ── Prévia ao vivo da página pública ──
  function slugify(str) {
    return (str || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }
  var preview = document.getElementById('cadPreview');
  function atualizarPreview() {
    var nome = campos.empresa.el.value.trim();
    var cidade = campos.cidade.el.value.trim();
    var uf = campos.uf.el.value.trim();
    var whatsVal = whats ? whats.value.trim() : '';

    if (!nome) {
      preview.innerHTML = '<div class="cad-preview-placeholder">Comece a digitar o nome da empresa pra ver a prévia da sua página pública.</div>';
      return;
    }
    var slug = slugify(nome + (cidade ? '-' + cidade : ''));
    var inicial = nome.trim().charAt(0).toUpperCase();
    var metaPartes = [];
    if (cidade) metaPartes.push(cidade + (uf ? ' · ' + uf : ''));
    if (whatsVal) metaPartes.push(whatsVal);

    preview.innerHTML =
      '<div class="cad-preview-ico">' + (inicial || '?') + '</div>' +
      '<div class="cad-preview-info">' +
        '<div class="cad-preview-nome"></div>' +
        '<div class="cad-preview-meta"></div>' +
        '<div class="cad-preview-url">fixaos.com.br/' + slug + '</div>' +
      '</div>';
    preview.querySelector('.cad-preview-nome').textContent = nome;
    preview.querySelector('.cad-preview-meta').textContent = metaPartes.join(' · ');
  }
  [campos.empresa.el, campos.cidade.el, campos.uf.el].forEach(function (el) {
    if (el) el.addEventListener('input', atualizarPreview);
  });
  if (campos.uf.el) campos.uf.el.addEventListener('change', atualizarPreview);
  atualizarPreview();

  // ── Envio: valida tudo, foca no primeiro erro, mostra estado de carregando ──
  form.addEventListener('submit', function (e) {
    var ordem = ['nome', 'email', 'senha', 'senha2', 'empresa', 'cidade', 'uf'];
    var primeiroInvalido = null;
    ordem.forEach(function (nome) {
      tocado[nome] = true;
      var ok = validar(nome, true);
      if (!ok && !primeiroInvalido && campos[nome].el) primeiroInvalido = campos[nome].el;
    });

    if (primeiroInvalido) {
      e.preventDefault();
      primeiroInvalido.focus();
      primeiroInvalido.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
      return;
    }

    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.classList.add('loading');
    document.getElementById('btnSubmitLabel').textContent = 'Criando conta e publicando sua página...';
  });
})();
</script>
