<?php
// Preço de cada combinação plano×ciclo, pré-calculado no servidor (mesma fonte de verdade de
// config/planos_fixa.php, via plano_preco_ciclo() — nunca um valor duplicado/hardcoded aqui)
// pra popular o JS sem round-trip nenhum ao trocar plano/ciclo no formulário.
$precos = [];
foreach ($planos as $p) {
    foreach ($ciclos as $chaveCiclo => $c) {
        $precos[$p['codigo']][$chaveCiclo] = plano_preco_ciclo((int) $p['preco_mensal'], $c);
    }
}

// Mesmo padrão de diretorio/cadastrar.php: volta do /auth/google/callback com o e-mail/nome
// já confirmados pelo Google — some da sessão assim que lido (só serve pra este render).
$gSignup = $_SESSION['google_signup'] ?? null;
if ($gSignup) { unset($_SESSION['google_signup']); }
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
.cf-page{min-height:100vh;background:#1B1025;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cf-wrap{width:100%;max-width:480px}
.cf-brand{text-align:center;margin-bottom:20px}
.cf-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cf-brand a span{color:#3CC9C0}
.cf-card{background:#fff;border-radius:18px;padding:28px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #8C7CFF}
.cf-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.35rem;margin:0 0 6px;color:#111827;text-align:center}
.cf-card .sub{color:#64748b;font-size:.88rem;text-align:center;margin-bottom:22px}
.cf-field{margin-bottom:14px}
.cf-field label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px}
.cf-field input[type=text],.cf-field input[type=email],.cf-field input[type=password],.cf-field select{
  width:100%;border:1px solid #d8dee9;border-radius:10px;padding:12px 14px;font-size:1rem;box-sizing:border-box;
}
.cf-field input:focus,.cf-field select:focus{outline:none;border-color:#8C7CFF;box-shadow:0 0 0 3px rgba(140,124,255,.15)}
.cf-planos{display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
.cf-plano-opt{display:block;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 14px;cursor:pointer;transition:border-color .12s,background .12s}
.cf-plano-opt input{margin-right:8px}
.cf-plano-opt.is-selected{border-color:#8C7CFF;background:#f6f4ff}
.cf-plano-nome{font-weight:700;color:#111827;font-size:.94rem}
.cf-plano-preco{float:right;font-weight:800;color:#8C7CFF;font-size:.94rem}
.cf-plano-beneficios{margin:6px 0 0 24px;padding:0;font-size:.78rem;color:#64748b;list-style:disc}
.cf-hp{position:absolute;left:-9999px;opacity:0}
.cf-btn{width:100%;background:#8C7CFF;color:#fff;border:none;border-radius:10px;padding:13px;font-weight:700;font-size:1rem;cursor:pointer;margin-top:6px}
.cf-btn:hover{background:#7563f0}
.cf-flash{border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px}
.cf-flash-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.cf-aviso{background:#f6f4ff;border:1px solid #ddd6fe;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#5b21b6;margin-bottom:18px;text-align:center}
.cf-login{text-align:center;margin-top:16px;font-size:.84rem;color:#64748b}
.cf-login a{color:#8C7CFF;font-weight:600;text-decoration:none}
.cf-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;border:1.5px solid #d8dee9;border-radius:10px;padding:11px;font-size:.92rem;font-weight:600;color:#374151;text-decoration:none;margin-bottom:14px;background:#fff}
.cf-google:hover{background:#f8fafc}
.cf-div{display:flex;align-items:center;gap:10px;margin:0 0 16px;color:#94a3b8;font-size:.78rem}
.cf-div::before,.cf-div::after{content:'';flex:1;height:1px;background:#e2e8f0}
.cf-google-linked{display:flex;align-items:center;gap:8px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:10px;padding:10px 12px;font-size:.84rem;margin-bottom:14px}
</style>

<div class="cf-page">
  <div class="cf-wrap">
    <div class="cf-brand"><a href="<?= url('/') ?>">Carteira<span> Fixa</span></a></div>
    <div class="cf-card">
      <h1>Crie sua conta</h1>
      <div class="sub">Controle financeiro pessoal e de MEI/empresa, sem precisar de uma assistência técnica por trás.</div>

      <div class="cf-aviso"><?= (int) $testeDias ?> dias grátis, sem compromisso. Nenhuma cobrança nesse período.</div>

      <?php $err = flash('error'); if ($err): ?>
        <div class="cf-flash cf-flash-err"><?= e($err) ?></div>
      <?php endif; ?>

      <?php
      $rasc = $_SESSION['carteira_fixa_cadastro_rascunho'] ?? []; unset($_SESSION['carteira_fixa_cadastro_rascunho']);
      $preNome  = $gSignup['nome']  ?? ($rasc['nome']  ?? '');
      $preEmail = $gSignup['email'] ?? ($rasc['email'] ?? '');
      ?>

      <form method="POST" action="<?= url('/carteira-fixa/cadastrar') ?>" novalidate id="formCadastroFixa">
        <?= csrf_field() ?>
        <input type="text" name="website" class="cf-hp" tabindex="-1" autocomplete="off">
        <?php if ($gSignup): ?><input type="hidden" name="google_id" value="<?= e($gSignup['google_id']) ?>"><?php endif; ?>

        <?php if (!$gSignup): ?>
        <a href="<?= url('/auth/google?to=fixa') ?>" class="cf-google">
          <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
          Continuar com Google
        </a>
        <div class="cf-div"><span>ou preencha à mão</span></div>
        <?php else: ?>
        <div class="cf-google-linked"><i class="bi bi-google"></i> Conta Google vinculada — confirme o plano abaixo pra continuar.</div>
        <?php endif; ?>

        <div class="cf-field">
          <label for="cfNome">Nome *</label>
          <input type="text" id="cfNome" name="nome" required maxlength="100" autocomplete="name" value="<?= e($preNome) ?>" <?= $gSignup ? 'readonly style="background:#f1f5f9"' : '' ?>>
        </div>
        <div class="cf-field">
          <label for="cfEmail">E-mail *</label>
          <input type="email" id="cfEmail" name="email" required autocomplete="email" value="<?= e($preEmail) ?>" <?= $gSignup ? 'readonly style="background:#f1f5f9"' : '' ?>>
        </div>
        <?php if ($gSignup): ?>
          <input type="hidden" name="senha" value="<?= e(bin2hex(random_bytes(16))) ?>">
          <input type="hidden" name="senha_confirm" value="">
        <?php else: ?>
        <div class="cf-field">
          <label for="cfSenha">Senha *</label>
          <input type="password" id="cfSenha" name="senha" required minlength="6" autocomplete="new-password">
        </div>
        <div class="cf-field">
          <label for="cfSenhaConfirm">Confirmar senha *</label>
          <input type="password" id="cfSenhaConfirm" name="senha_confirm" required minlength="6" autocomplete="new-password">
        </div>
        <?php endif; ?>

        <div class="cf-field">
          <label>Plano *</label>
          <div class="cf-planos" id="cfPlanos">
            <?php foreach ($planos as $i => $p): ?>
            <label class="cf-plano-opt<?= $i === 0 ? ' is-selected' : '' ?>" data-plano="<?= e($p['codigo']) ?>">
              <input type="radio" name="plano" value="<?= e($p['codigo']) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
              <span class="cf-plano-nome"><?= e($p['nome']) ?></span>
              <span class="cf-plano-preco" data-preco-plano="<?= e($p['codigo']) ?>">R$ <?= number_format($precos[$p['codigo']]['mensal'] / 100, 2, ',', '.') ?>/mês</span>
              <ul class="cf-plano-beneficios">
                <?php foreach (array_slice($p['beneficios'], 0, 3) as $b): ?><li><?= e($b) ?></li><?php endforeach; ?>
              </ul>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="cf-field">
          <label for="cfCiclo">Cobrança</label>
          <select id="cfCiclo" name="ciclo">
            <?php foreach ($ciclos as $chave => $c): ?>
            <option value="<?= e($chave) ?>"><?= e($c['nome']) ?><?= $c['desconto'] > 0 ? ' — ' . (int) $c['desconto'] . '% off' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="cf-btn">Começar meus <?= (int) $testeDias ?> dias grátis</button>
      </form>

      <div class="cf-login">Já tem conta? <a href="<?= url('/carteira-fixa/login') ?>">Entrar</a></div>
    </div>
  </div>
</div>

<script>
(function () {
  var PRECOS = <?= json_encode($precos) ?>;
  var planosEls = document.querySelectorAll('.cf-plano-opt');
  var ciclo = document.getElementById('cfCiclo');

  function fmt(centavos) {
    return 'R$ ' + (centavos / 100).toFixed(2).replace('.', ',');
  }

  function rotuloCiclo(chave) {
    return chave === 'mensal' ? '/mês' : '';
  }

  function atualizarPrecos() {
    var c = ciclo.value;
    document.querySelectorAll('[data-preco-plano]').forEach(function (el) {
      var plano = el.getAttribute('data-preco-plano');
      var centavos = PRECOS[plano] && PRECOS[plano][c] != null ? PRECOS[plano][c] : 0;
      el.textContent = fmt(centavos) + rotuloCiclo(c);
    });
  }

  planosEls.forEach(function (label) {
    label.addEventListener('click', function () {
      planosEls.forEach(function (l) { l.classList.remove('is-selected'); });
      label.classList.add('is-selected');
    });
  });
  ciclo.addEventListener('change', atualizarPrecos);
  atualizarPrecos();

  document.getElementById('formCadastroFixa').addEventListener('submit', function (ev) {
    // Conta vinda do Google não tem esses campos (são hidden, sem id) — nada pra conferir aqui.
    var campoSenha = document.getElementById('cfSenha');
    var campoConfirm = document.getElementById('cfSenhaConfirm');
    if (campoSenha && campoConfirm && campoSenha.value !== campoConfirm.value) {
      ev.preventDefault();
      alert('As senhas não conferem.');
    }
  });
})();
</script>
