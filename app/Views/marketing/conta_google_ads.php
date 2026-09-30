<?php
/**
 * Tela onde a própria empresa vincula o Customer ID real dela no Google Ads —
 * ver MarketingController::contaGoogleAds()/conectarGoogleAds()/desconectarGoogleAds().
 */
$erro = flash('error');
$sucesso = flash('success');
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
$conectado = $conta && ($conta['status'] ?? '') === 'active';
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-conta-wrap{max-width:640px;margin:0 auto}
.fx-mkt-conta-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:20px;margin-bottom:1rem}
.fx-mkt-conta-card h2{font-size:15px;font-weight:700;color:var(--text-1);margin:0 0 .6rem}
.fx-mkt-conta-card p{color:var(--text-3);font-size:13.5px;line-height:1.5}
.fx-mkt-conta-passo{display:flex;gap:.7rem;margin-bottom:.9rem}
.fx-mkt-conta-passo-num{flex:none;width:24px;height:24px;border-radius:50%;background:var(--surface-2);color:var(--text-2);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center}
.fx-mkt-conta-passo-txt{font-size:13px;color:var(--text-2);line-height:1.5}
.fx-mkt-conta-input{width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);color:var(--text-1);font-size:14px}
.fx-mkt-conta-btn{display:inline-flex;align-items:center;gap:.4rem;padding:9px 16px;border-radius:8px;font-weight:700;font-size:13.5px;border:none;cursor:pointer}
.fx-mkt-conta-btn.primary{background:var(--accent);color:#fff}
.fx-mkt-conta-btn.danger{background:transparent;color:var(--danger,#dc2626);border:1px solid var(--danger,#dc2626)}
.fx-mkt-conta-status{display:inline-flex;align-items:center;gap:.35rem;font-size:12px;font-weight:700;padding:3px 10px;border-radius:999px;margin-bottom:.6rem}
.fx-mkt-conta-status.on{background:#dcfce7;color:#166534}
.fx-mkt-conta-status.off{background:var(--surface-2);color:var(--text-3)}
.fx-mkt-conta-alert{padding:9px 12px;border-radius:8px;font-size:13px;margin-bottom:1rem}
.fx-mkt-conta-alert.error{background:#fee2e2;color:#991b1b}
.fx-mkt-conta-alert.success{background:#dcfce7;color:#166534}
</style>

<div class="fx-mkt-conta-wrap">
  <h1 style="font-size:18px;font-weight:700;color:var(--text-1);margin-bottom:1rem">
    <a href="<?= url('/marketing') ?>" style="color:var(--text-3);text-decoration:none;margin-right:.4rem">←</a>
    Conectar conta do Google Ads
  </h1>

  <?php if ($erro): ?><div class="fx-mkt-conta-alert error"><?= e($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?><div class="fx-mkt-conta-alert success"><?= e($sucesso) ?></div><?php endif; ?>

  <?php if ($conectado): ?>
  <div class="fx-mkt-conta-card">
    <span class="fx-mkt-conta-status on"><i class="bi bi-check-circle-fill"></i> Conectado</span>
    <h2><?= e($conta['name']) ?></h2>
    <p>Customer ID: <code><?= e($conta['external_id']) ?></code> · Moeda: <?= e($conta['currency']) ?></p>
    <?php if (!empty($conta['last_synced_at'])): ?>
      <p>Última sincronização: <?= date_br($conta['last_synced_at'], true) ?></p>
    <?php endif; ?>
    <?php if (!empty($conta['last_sync_error'])): ?>
      <div class="fx-mkt-conta-alert error" style="margin-top:.6rem">
        Última tentativa de sincronizar falhou: <?= e($conta['last_sync_error']) ?>
      </div>
    <?php endif; ?>
    <div style="display:flex;gap:.6rem;margin-top:1rem">
      <a href="<?= url('/marketing') ?>" class="fx-mkt-conta-btn primary"><i class="bi bi-graph-up-arrow"></i> Ver painel</a>
      <form method="POST" action="<?= url('/marketing/conta-google-ads/desconectar') ?>"
            onsubmit="return confirm('Desconectar esta conta? O painel volta a mostrar dados de demonstração até você conectar outra.')">
        <?= csrf_field() ?>
        <button type="submit" class="fx-mkt-conta-btn danger"><i class="bi bi-power"></i> Desconectar</button>
      </form>
    </div>
  </div>

  <div class="fx-mkt-conta-card">
    <h2>Trocar de conta</h2>
    <p>Pra conectar um Customer ID diferente, é só preencher o formulário abaixo de novo — a conta atual é desligada automaticamente.</p>
  <?php else: ?>
  <div class="fx-mkt-conta-card">
    <span class="fx-mkt-conta-status off"><i class="bi bi-x-circle"></i> Não conectado</span>
    <h2>Como funciona</h2>
    <p>O painel de Marketing hoje mostra só dados de demonstração. É rápido trocar pelos números
      reais das suas campanhas:</p>

    <div class="fx-mkt-conta-passo">
      <div class="fx-mkt-conta-passo-num">1</div>
      <div class="fx-mkt-conta-passo-txt">
        Digite abaixo o <strong>Customer ID</strong> da sua conta do Google Ads (o número que
        aparece no canto superior direito da tela do Google Ads, ex.: 123-456-7890) e clique em
        Conectar.
      </div>
    </div>
    <div class="fx-mkt-conta-passo">
      <div class="fx-mkt-conta-passo-num">2</div>
      <div class="fx-mkt-conta-passo-txt">
        A FixaOS manda um convite de vínculo direto pra sua conta — não precisa navegar menu
        nenhum do Google Ads.
      </div>
    </div>
    <div class="fx-mkt-conta-passo">
      <div class="fx-mkt-conta-passo-num">3</div>
      <div class="fx-mkt-conta-passo-txt">
        Abra seu Google Ads (ou confira seu e-mail) e clique em <strong>Aceitar</strong> no
        convite — é só esse clique, do seu lado.
      </div>
    </div>
    <div class="fx-mkt-conta-passo">
      <div class="fx-mkt-conta-passo-num">4</div>
      <div class="fx-mkt-conta-passo-txt">
        Volte aqui e clique em <strong>Conectar</strong> de novo — os dados reais já aparecem no
        painel.
      </div>
    </div>
  </div>

  <div class="fx-mkt-conta-card">
    <h2>Conectar minha conta</h2>
  <?php endif; ?>
    <form method="POST" action="<?= url('/marketing/conta-google-ads/conectar') ?>">
      <?= csrf_field() ?>
      <label style="display:block;font-size:12.5px;font-weight:600;color:var(--text-2);margin-bottom:.3rem">Customer ID do Google Ads</label>
      <input type="text" name="customer_id" class="fx-mkt-conta-input" placeholder="123-456-7890"
             value="<?= e($old['customer_id'] ?? '') ?>" required>
      <div style="margin-top:.9rem">
        <button type="submit" class="fx-mkt-conta-btn primary"><i class="bi bi-google"></i> Conectar</button>
      </div>
    </form>
  </div>
</div>
</div>
