<?php $editando = !empty($cliente['id']); ?>
<div class="row justify-content-center">
<div class="col-lg-9">
<form method="POST" action="<?= url($editando ? '/clientes/' . $cliente['id'] : '/clientes') ?>">
  <?= csrf_field() ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Dados Pessoais</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Tipo</label>
          <select name="tipo" class="form-select" id="tipoPessoa">
            <option value="pf" <?= ($cliente['tipo']??'pf')==='pf'?'selected':'' ?>>Pessoa Física</option>
            <option value="pj" <?= ($cliente['tipo']??'')==='pj'?'selected':'' ?>>Pessoa Jurídica</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nome completo / Razão social *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($cliente['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold" id="lblCpfCnpj">CPF</label>
          <input type="text" name="cpf_cnpj" class="form-control" value="<?= e($cliente['cpf_cnpj'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= e($cliente['email'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($cliente['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($cliente['whatsapp'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Data Nascimento</label>
          <input type="date" name="data_nascimento" class="form-control" value="<?= e($cliente['data_nascimento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Origem</label>
          <select name="origem" class="form-select">
            <?php foreach (['balcao'=>'Balcão','telefone'=>'Telefone','whatsapp'=>'WhatsApp','site'=>'Site','indicacao'=>'Indicação','outro'=>'Outro'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($cliente['origem']??'balcao')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($editando): ?>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="ativo" <?= ($cliente['status']??'')==='ativo'?'selected':'' ?>>Ativo</option>
            <option value="inativo" <?= ($cliente['status']??'')==='inativo'?'selected':'' ?>>Inativo</option>
            <option value="bloqueado" <?= ($cliente['status']??'')==='bloqueado'?'selected':'' ?>>Bloqueado</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Endereço</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" id="cepInput" placeholder="00000-000" value="<?= e($cliente['cep'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" id="logradouro" value="<?= e($cliente['logradouro'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Número</label>
          <input type="text" name="numero" class="form-control" value="<?= e($cliente['numero'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($cliente['complemento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" id="bairro" value="<?= e($cliente['bairro'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" id="cidade" value="<?= e($cliente['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">UF</label>
          <input type="text" name="uf" class="form-control" id="uf" maxlength="2" value="<?= e($cliente['uf'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Observações</div>
    <div class="card-body">
      <textarea name="observacoes" class="form-control" rows="3"><?= e($cliente['observacoes'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $editando ? 'Salvar Alterações' : 'Cadastrar Cliente' ?></button>
  </div>
</form>
</div>
</div>

<?php /* CEP e máscaras aplicados globalmente pelo masks.js */ ?>
