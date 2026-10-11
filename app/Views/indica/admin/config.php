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

<div class="card">
  <h3 style="margin-top:0">Mercado Livre</h3>
  <?php if (!$mlConfigurado): ?>
    <p style="color:var(--texto-sec)">
      Config não preenchida ainda. No servidor, copie <code>config/mercadolivre.example.php</code>
      para <code>config/mercadolivre.php</code> e siga o passo a passo escrito lá (criar o app em
      developers.mercadolivre.com.br e preencher App ID + Secret Key).
    </p>
  <?php elseif ($mlStatus['conectado']): ?>
    <p>
      <span class="badge badge-verde">Conectado</span>
      <?php if (!empty($mlStatus['conectado_em'])): ?>
        <span style="color:var(--texto-sec);font-size:.85rem;margin-left:8px">desde <?= e(date('d/m/Y', strtotime($mlStatus['conectado_em']))) ?></span>
      <?php endif; ?>
    </p>
    <p style="color:var(--texto-sec);font-size:.85rem">O token se renova sozinho. Só precisa reconectar se aparecer um erro de autorização.</p>
    <form method="POST" action="/admin/mercadolivre/desconectar" onsubmit="return confirm('Desconectar do Mercado Livre? A busca automática de anúncio para de funcionar até reconectar.')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-perigo btn-sm">Desconectar</button>
    </form>
  <?php else: ?>
    <p style="color:var(--texto-sec)">Não conectado — sem isso, o botão "Buscar dados do anúncio" no cadastro de produto não funciona.</p>
    <a href="/admin/mercadolivre/conectar" class="btn">Conectar com Mercado Livre</a>
  <?php endif; ?>
</div>
