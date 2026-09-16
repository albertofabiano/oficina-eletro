<?php
/* Botão "Enviar por WhatsApp (API)" para as barras de impressão — abre um modal (sem depender
   de Bootstrap, já que os layouts de impressão são autocontidos) pra escrever/editar a mensagem
   antes de enviar, com o PDF anexado.
   Requer: $os (com id, numero, recado_cliente) e $_waTipo em
   {abertura, orcamento, fechamento, garantia, laudo, sem-conserto}. */
$_waTipo = $_waTipo ?? 'fechamento';
$_waRotulos = [
    'abertura'     => 'Comprovante de entrada',
    'orcamento'    => 'Orçamento',
    'fechamento'   => 'Comprovante',
    'garantia'     => 'Comprovante de garantia',
    'laudo'        => 'Laudo técnico',
    'sem-conserto' => 'Comprovante sem cobrança',
];
$_waRotulo = $_waRotulos[$_waTipo] ?? 'Documento';
$_waRecado = trim((string) ($os['recado_cliente'] ?? ''));
$_waMensagemPadrao = ($_waRecado !== '' ? $_waRecado . "\n\n" : '') . "{$_waRotulo} — OS {$os['numero']}";
?>
<button type="button" onclick="abrirModalWaPdf()"
  style="background:#25d366;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
  📲 Enviar por WhatsApp
</button>
<span id="waPdfMsg" style="font-size:13px;font-weight:600"></span>

<div id="waPdfModalOverlay" class="no-print" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:10px;max-width:440px;width:100%;padding:20px;box-shadow:0 10px 40px rgba(0,0,0,.3)">
    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 4px">Enviar por WhatsApp</h3>
    <p style="font-size:12.5px;color:#666;margin:0 0 12px">
      O PDF de "<?= e($_waRotulo) ?>" vai anexado junto com a mensagem abaixo.
    </p>
    <textarea id="waPdfTextarea" rows="5"
      style="width:100%;border:1px solid #ccc;border-radius:6px;padding:8px 10px;font-size:13.5px;font-family:inherit;resize:vertical;box-sizing:border-box"
    ><?= e($_waMensagemPadrao) ?></textarea>
    <div id="waPdfModalErro" style="display:none;color:#dc3545;font-size:12.5px;margin-top:8px;font-weight:600"></div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
      <button type="button" onclick="fecharModalWaPdf()"
        style="background:#f1f3f5;color:#333;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:14px">
        Cancelar
      </button>
      <button type="button" id="waPdfBtnEnviar" onclick="confirmarEnvioWaPdf('<?= $_waTipo ?>')"
        style="background:#25d366;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600">
        Enviar
      </button>
    </div>
  </div>
</div>

<script>
function abrirModalWaPdf(){
  document.getElementById('waPdfModalOverlay').style.display = 'flex';
  document.getElementById('waPdfModalErro').style.display = 'none';
}
function fecharModalWaPdf(){
  document.getElementById('waPdfModalOverlay').style.display = 'none';
}
async function confirmarEnvioWaPdf(tipo){
  const textarea = document.getElementById('waPdfTextarea');
  const btnEnviar = document.getElementById('waPdfBtnEnviar');
  const erroBox = document.getElementById('waPdfModalErro');
  const msg = document.getElementById('waPdfMsg');
  const origBtnEnviar = btnEnviar.innerHTML;
  btnEnviar.disabled = true; btnEnviar.innerHTML = 'Enviando…';
  erroBox.style.display = 'none';
  try{
    const r = await fetch('<?= url('/os/' . $os['id'] . '/whatsapp-pdf') ?>', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>'},
      body:'tipo=' + encodeURIComponent(tipo) + '&mensagem=' + encodeURIComponent(textarea.value)
    });
    const j = await r.json();
    if(!j.success) throw new Error(j.error || 'Falha no envio.');
    fecharModalWaPdf();
    msg.style.color = '#198754'; msg.textContent = '✓ PDF enviado no WhatsApp do cliente.';
  }catch(e){
    erroBox.style.display = 'block'; erroBox.textContent = e.message;
  }
  btnEnviar.disabled = false; btnEnviar.innerHTML = origBtnEnviar;
}
</script>
