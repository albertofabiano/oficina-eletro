<?php
/* Botão "Enviar por WhatsApp (API)" para as barras de impressão.
   Requer: $os (com id) e $_waTipo em {abertura, orcamento, fechamento, garantia}. */
$_waTipo = $_waTipo ?? 'fechamento';
?>
<button type="button" onclick="enviarPdfWaPrint(this,'<?= $_waTipo ?>')"
  style="background:#25d366;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
  📲 Enviar por WhatsApp
</button>
<span id="waPdfMsg" style="font-size:13px;font-weight:600"></span>
<script>
async function enviarPdfWaPrint(btn, tipo){
  const orig = btn.innerHTML, msg = document.getElementById('waPdfMsg');
  btn.disabled = true; btn.style.opacity = .6; btn.innerHTML = 'Enviando…'; msg.textContent = '';
  try{
    const r = await fetch('<?= url('/os/' . $os['id'] . '/whatsapp-pdf') ?>', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>'},
      body:'tipo=' + encodeURIComponent(tipo)
    });
    const j = await r.json();
    if(!j.success) throw new Error(j.error || 'Falha no envio.');
    btn.innerHTML = '✓ Enviado'; btn.style.background = '#198754'; btn.style.opacity = 1;
    msg.style.color = '#198754'; msg.textContent = 'PDF enviado no WhatsApp do cliente.';
  }catch(e){
    btn.disabled = false; btn.style.opacity = 1; btn.innerHTML = orig;
    msg.style.color = '#dc3545'; msg.textContent = e.message;
  }
}
</script>
