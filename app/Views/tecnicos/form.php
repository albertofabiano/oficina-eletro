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
        <input type="text" name="nome" id="tecNomeInput" class="form-control form-control-lg"
               value="<?= e($tecnico['nome'] ?? '') ?>" required
               placeholder="Ex: Carlos Eduardo Silva">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">E-mail de acesso *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" id="tecEmailInput" class="form-control"
                 value="<?= e($tecnico['email'] ?? '') ?>"
                 <?= $editando ? 'readonly' : 'required' ?>
                 placeholder="tecnico@email.com">
        </div>
        <?php if ($editando): ?>
        <div class="form-text">O e-mail não pode ser alterado.</div>
        <?php else: ?>
        <div class="form-text" id="tecEmailMsg"></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Telefone / WhatsApp *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-telephone"></i></span>
          <input type="text" name="telefone" id="tecTelInput" class="form-control" required
                 placeholder="(00) 00000-0000"
                 value="<?= e($tecnico['telefone'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="ativo" class="form-select">
          <option value="1" <?= ($tecnico['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
          <option value="0" <?= ($tecnico['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold d-block">Vincular a Ordens de Serviço?</label>
        <div class="form-check form-switch pt-1">
          <input class="form-check-input" type="checkbox" role="switch" name="atende_os" id="atendeOs" value="1" <?= ($tecnico['atende_os'] ?? 1) == 1 ? 'checked' : '' ?>>
          <label class="form-check-label small" for="atendeOs">Aparece como opção de técnico responsável nas OS</label>
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

<?php if (!$editando): ?>
<script>
(function(){
  var email = document.getElementById('tecEmailInput');
  var nome  = document.getElementById('tecNomeInput');
  var tel   = document.getElementById('tecTelInput');
  var msg   = document.getElementById('tecEmailMsg');
  if (!email) return;
  email.addEventListener('blur', async function(){
    var v = email.value.trim();
    if (!v) { msg.textContent = ''; return; }
    try {
      var r = await fetch('<?= url('/api/tecnicos/buscar-email') ?>?email=' + encodeURIComponent(v));
      var j = await r.json();
      if (j.encontrado) {
        if (!nome.value.trim()) nome.value = j.nome || '';
        if (!tel.value.trim())  tel.value  = j.telefone || '';
        msg.textContent = 'E-mail já cadastrado — preenchemos nome e telefone com os dados existentes.';
        msg.className = 'form-text text-warning';
      } else {
        msg.textContent = '';
      }
    } catch (e) {}
  });
})();
</script>
<?php endif; ?>

