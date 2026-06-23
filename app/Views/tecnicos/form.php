<?php $editando = !empty($tecnico['id']); ?>

<div class="row justify-content-center">
<div class="col-lg-6">

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-person-gear me-2 text-primary"></i>
    <?= $editando ? 'Editar Técnico' : 'Novo Técnico' ?>
  </div>
  <div class="card-body">
    <form method="POST"
          action="<?= url($editando ? '/tecnicos/' . $tecnico['id'] . '/editar' : '/tecnicos') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Nome completo *</label>
        <input type="text" name="nome" class="form-control form-control-lg"
               value="<?= e($tecnico['nome'] ?? '') ?>" required
               placeholder="Ex: Carlos Eduardo Silva">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">E-mail de acesso *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" class="form-control"
                 value="<?= e($tecnico['email'] ?? '') ?>"
                 <?= $editando ? 'readonly' : 'required' ?>
                 placeholder="tecnico@email.com">
        </div>
        <?php if ($editando): ?>
        <div class="form-text">O e-mail não pode ser alterado.</div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Telefone / WhatsApp</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-telephone"></i></span>
          <input type="text" name="telefone" class="form-control"
                 placeholder="(00) 00000-0000"
                 value="<?= e($tecnico['telefone'] ?? '') ?>">
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-7">
          <label class="form-label fw-semibold">
            <?= $editando ? 'Nova senha' : 'Senha *' ?>
          </label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="senha" class="form-control"
                   <?= $editando ? '' : 'required' ?>
                   minlength="6" placeholder="Mínimo 6 caracteres"
                   id="senhaInput">
            <button type="button" class="btn btn-outline-secondary"
                    onclick="toggleSenha()">
              <i class="bi bi-eye" id="senhaIcon"></i>
            </button>
          </div>
          <?php if ($editando): ?>
          <div class="form-text">Deixe em branco para manter a senha atual.</div>
          <?php endif; ?>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-semibold">Status</label>
          <select name="ativo" class="form-select">
            <option value="1" <?= ($tecnico['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
            <option value="0" <?= ($tecnico['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
          </select>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <a href="<?= url($editando ? '/tecnicos/' . $tecnico['id'] : '/tecnicos') ?>"
           class="btn btn-outline-secondary flex-fill">
          Cancelar
        </a>
        <button type="submit" class="btn btn-primary flex-fill fw-semibold">
          <i class="bi bi-check-lg me-1"></i>
          <?= $editando ? 'Salvar alterações' : 'Cadastrar técnico' ?>
        </button>
      </div>
    </form>
  </div>
</div>

</div>
</div>

<script>
function toggleSenha() {
  const inp  = document.getElementById('senhaInput');
  const icon = document.getElementById('senhaIcon');
  const show = inp.type === 'password';
  inp.type   = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
