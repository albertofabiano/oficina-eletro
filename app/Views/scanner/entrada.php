<div class="card">
  <h1>Parear com o computador</h1>
  <p class="hint">Escaneie o QR Code com a câmera do celular. Se não conseguir, digite abaixo o código de 6 letras que aparece na tela do computador.</p>

  <?php if (!empty($erro)): ?>
  <div style="background:#fee2e2;color:#991b1b;padding:10px 12px;border-radius:10px;font-size:.85rem;margin-bottom:10px"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="GET" action="<?= url('/scan') ?>">
    <label>Código</label>
    <input type="text" name="codigo" placeholder="Ex: K7P2QX" maxlength="6" autocomplete="off"
           autocapitalize="characters" style="text-transform:uppercase;letter-spacing:.25em;text-align:center;font-size:1.4rem;font-weight:700" required>
    <button type="submit" class="btn btn-send" style="margin-top:14px">Continuar</button>
  </form>
</div>
