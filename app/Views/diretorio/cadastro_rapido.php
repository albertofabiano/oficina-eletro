<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
.cr-page{min-height:100vh;background:#0a1526;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cr-wrap{width:100%;max-width:440px}
.cr-brand{text-align:center;margin-bottom:20px}
.cr-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cr-brand a span{color:#f97316}
.cr-card{background:#fff;border-radius:18px;padding:28px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #f97316}
.cr-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.35rem;margin:0 0 6px;color:#111827;text-align:center}
.cr-card .sub{color:#64748b;font-size:.88rem;text-align:center;margin-bottom:22px}
.cr-field{margin-bottom:16px}
.cr-field label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px}
.cr-field label .opt{font-weight:400;color:#94a3b8}
.cr-field input[type=text],.cr-field input[type=email],.cr-field input[type=tel]{
  width:100%;border:1px solid #d8dee9;border-radius:10px;padding:12px 14px;font-size:1rem;
}
.cr-field input:focus{outline:none;border-color:#f97316;box-shadow:0 0 0 3px rgba(249,115,22,.12)}
.cr-logo-input{width:100%;border:1px dashed #d8dee9;border-radius:10px;padding:10px;font-size:.85rem;color:#64748b;background:#f8fafc}
.cr-btn{width:100%;background:#f97316;color:#fff;border:none;border-radius:10px;padding:13px;font-weight:700;font-size:1rem;cursor:pointer;margin-top:6px}
.cr-btn:hover{background:#ea580c}
.cr-hp{position:absolute;left:-9999px;opacity:0}
.cr-flash{border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px}
.cr-flash-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
</style>

<div class="cr-page">
  <div class="cr-wrap">
    <div class="cr-brand"><a href="<?= url('/') ?>">Fixa<span>OS</span></a></div>
    <div class="cr-card">
      <h1>Cadastre sua empresa grátis</h1>
      <div class="sub">Só o essencial pra colocar você no Diretório FixaOS agora — dá pra completar o resto (fotos, endereço, horário) depois.</div>

      <?php $err = flash('error'); if ($err): ?>
        <div class="cr-flash cr-flash-err"><?= e($err) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('/diretorio/cadastro-rapido') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" class="cr-hp" tabindex="-1" autocomplete="off">

        <div class="cr-field">
          <label for="crNome">Nome da empresa *</label>
          <input type="text" id="crNome" name="nome_fantasia" required maxlength="150" autocomplete="organization" placeholder="Ex.: Assistência Técnica Silva">
        </div>

        <div class="cr-field">
          <label for="crWhats">WhatsApp *</label>
          <input type="tel" id="crWhats" name="whatsapp" required inputmode="tel" autocomplete="tel-national" placeholder="(11) 99999-9999">
        </div>

        <div class="cr-field">
          <label for="crEmail">E-mail <span class="opt">(opcional)</span></label>
          <input type="email" id="crEmail" name="email" autocomplete="email" placeholder="seu@email.com">
        </div>

        <div class="cr-field">
          <label for="crLogo">Logo <span class="opt">(opcional)</span></label>
          <input type="file" id="crLogo" name="logo" accept="image/*" class="cr-logo-input">
        </div>

        <button type="submit" class="cr-btn">Cadastrar grátis</button>
      </form>
    </div>
  </div>
</div>
