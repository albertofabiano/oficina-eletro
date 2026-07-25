<?php $editando = !empty($usuario['id']); ?>
<div class="row justify-content-center"><div class="col-lg-6">
<form method="POST" action="<?= url($editando ? '/usuarios/' . $usuario['id'] : '/usuarios') ?>">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Dados do Usuário</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label small fw-semibold">Nome completo *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($usuario['nome'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label small fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= e($usuario['email'] ?? '') ?>" <?= $editando ? 'readonly' : 'required' ?>>
          <?php if ($editando): ?><div class="form-text">E-mail não pode ser alterado.</div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Perfil</label>
          <?php
            $perfisDisponiveis = ['admin'=>'Administrador','gerente'=>'Gerente','recepcionista'=>'Recepcionista','rh'=>'Recursos Humanos (RH)','financeiro'=>'Financeiro'];
            $perfilAtual = $usuario['perfil'] ?? 'admin';
            // Técnicos são cadastrados pela tela dedicada (Configurações → Técnicos); se um técnico
            // existente cair nesse formulário genérico, preserva a opção pra não trocar sem querer.
            if ($perfilAtual === 'tecnico') { $perfisDisponiveis = ['tecnico' => 'Técnico (gerencie em Configurações → Técnicos)'] + $perfisDisponiveis; }
          ?>
          <select name="perfil" class="form-select">
            <?php foreach ($perfisDisponiveis as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $perfilAtual===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($usuario['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-6" id="atendeOsWrap" style="<?= $perfilAtual === 'tecnico' ? '' : 'display:none' ?>">
          <label class="form-label small fw-semibold d-block">Atende OS?</label>
          <div class="form-check form-switch pt-1">
            <input class="form-check-input" type="checkbox" role="switch" name="atende_os" id="atendeOs" value="1" <?= ($usuario['atende_os'] ?? 1) == 1 ? 'checked' : '' ?>>
            <label class="form-check-label small" for="atendeOs">Aparece como opção de técnico responsável nas OS</label>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold"><?= $editando ? 'Nova senha' : 'Senha *' ?></label>
          <input type="password" name="senha" class="form-control" <?= $editando ? '' : 'required' ?> minlength="6" placeholder="Mínimo 6 caracteres">
          <?php if ($editando): ?><div class="form-text">Deixe em branco para manter a atual.</div><?php endif; ?>
        </div>
        <?php if ($editando): ?>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Status</label>
          <select name="ativo" class="form-select">
            <option value="1" <?= ($usuario['ativo']??1)==1?'selected':'' ?>>Ativo</option>
            <option value="0" <?= ($usuario['ativo']??1)==0?'selected':'' ?>>Inativo</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/usuarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><?= $editando ? 'Salvar' : 'Criar Usuário' ?></button>
  </div>
</form>
<script>
document.querySelector('select[name="perfil"]').addEventListener('change', function () {
  document.getElementById('atendeOsWrap').style.display = this.value === 'tecnico' ? '' : 'none';
});
</script>
</div></div>
