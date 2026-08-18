<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div style="max-width:420px;text-align:center">
    <div style="font-size:40px;margin-bottom:12px"><?= $encontrado ? '✅' : '🤔' ?></div>
    <h1 style="font-size:20px;color:#0f172a;margin-bottom:10px">
      <?= $encontrado ? 'Você não vai mais receber este convite' : 'Link não reconhecido' ?>
    </h1>
    <p style="color:#64748b;font-size:14.5px;line-height:1.6">
      <?php if ($encontrado): ?>
        Removemos o contato da nossa lista de convites. Se mudar de ideia, o cadastro grátis no
        diretório continua disponível a qualquer momento em <a href="<?= url('/diretorio/cadastrar') ?>">fixaos.com.br/diretorio/cadastrar</a>.
      <?php else: ?>
        Este link de descadastro já foi usado ou não é mais válido.
      <?php endif; ?>
    </p>
  </div>
</div>
