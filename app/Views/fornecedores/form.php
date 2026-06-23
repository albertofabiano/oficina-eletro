<?php $editando = !empty($fornecedor['id']); ?>
<div class="row justify-content-center"><div class="col-lg-8">
<form method="POST" action="<?= url($editando ? '/fornecedores/' . $fornecedor['id'] : '/fornecedores') ?>">
  <?= csrf_field() ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Dados do Fornecedor</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label small fw-semibold">Razão Social *</label>
          <input type="text" name="razao_social" class="form-control" value="<?= e($fornecedor['razao_social'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Nome Fantasia</label>
          <input type="text" name="nome_fantasia" class="form-control" value="<?= e($fornecedor['nome_fantasia'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">CNPJ / CPF</label>
          <input type="text" name="cnpj_cpf" class="form-control" placeholder="00.000.000/0000-00" value="<?= e($fornecedor['cnpj_cpf'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($fornecedor['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($fornecedor['whatsapp'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= e($fornecedor['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Site</label>
          <input type="url" name="site" class="form-control" placeholder="https://" value="<?= e($fornecedor['site'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nome do contato</label>
          <input type="text" name="contato_nome" class="form-control" value="<?= e($fornecedor['contato_nome'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Endereço</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" placeholder="00000-000" value="<?= e($fornecedor['cep'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" value="<?= e($fornecedor['logradouro'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Número</label>
          <input type="text" name="numero" class="form-control" value="<?= e($fornecedor['numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" value="<?= e($fornecedor['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">UF</label>
          <input type="text" name="uf" class="form-control" maxlength="2" value="<?= e($fornecedor['uf'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Observações</div>
    <div class="card-body">
      <textarea name="observacoes" class="form-control" rows="3"><?= e($fornecedor['observacoes'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/fornecedores') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><?= $editando ? 'Salvar' : 'Cadastrar' ?></button>
  </div>
</form>
</div></div>
