<?php
/*
 * Config do módulo Marketing (tráfego pago). Copie este arquivo para
 * config/marketing.php (gitignored — só existe no servidor) e gere uma chave própria.
 *
 * 'dry_run'        → true (padrão, ligado por decisão consciente) faz o executor da fila
 *                     de aprovação registrar toda ação como "executada em simulação",
 *                     SEM chamar a Meta/Google. Só desligue depois de conferir sugestões
 *                     reais com o dono do produto — ver App\Services\Marketing\*.
 * 'encryption_key' → chave de 32 bytes, em base64, usada pelo CredentialCipher
 *                     (sodium_crypto_secretbox) pra criptografar token de acesso das
 *                     plataformas de anúncio antes de gravar em mkt_credentials. Gere com:
 *                       php -r "echo base64_encode(random_bytes(32));"
 *                     NUNCA reaproveite a mesma chave de outro propósito, nunca versione
 *                     o valor real, nunca logue. Trocar a chave invalida credenciais já
 *                     salvas com key_version antigo — seria preciso reconectar as contas.
 * 'key_version'    → versão da chave acima; incremente ao trocar a chave de verdade.
 */
return [
    'dry_run'        => true,
    'encryption_key' => '',
    'key_version'    => 1,
];
