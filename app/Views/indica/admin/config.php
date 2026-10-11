<div class="topo"><h1>Configurações</h1></div>

<div class="card">
  <form method="POST" action="/admin/config">
    <?= csrf_field() ?>

    <div class="campo">
      <label>WhatsApp de contato (opcional)</label>
      <input type="text" name="whatsapp_contato" value="<?= e($config['whatsapp_contato'] ?? '') ?>" placeholder="5511999998888">
      <div style="color:var(--texto-sec);font-size:.78rem;margin-top:4px">Só números, com DDI+DDD (ex.: 5511999998888). Deixe vazio pra não mostrar nenhum link de contato no site.</div>
    </div>

    <div class="checkbox-row" style="margin-top:12px">
      <label style="display:flex;align-items:center;gap:8px;font-weight:400;color:var(--texto)">
        <input type="checkbox" name="faixa_fixaos_ativa" value="1" <?= (($config['faixa_fixaos_ativa'] ?? '1') === '1') ? 'checked' : '' ?> style="width:auto">
        Mostrar a faixa de divulgação do FixaOS no rodapé
      </label>
    </div>

    <button type="submit" class="btn" style="margin-top:16px">Salvar configurações</button>
  </form>
</div>
