<?php if ($erro = flash('error')): ?>
  <div class="erro"><?= e($erro) ?></div>
<?php endif; ?>
<form method="POST" action="/admin/login">
  <?= csrf_field() ?>
  <label for="email">E-mail</label>
  <input type="email" id="email" name="email" required autofocus>
  <label for="senha">Senha</label>
  <input type="password" id="senha" name="senha" required>
  <button type="submit">Entrar</button>
</form>
