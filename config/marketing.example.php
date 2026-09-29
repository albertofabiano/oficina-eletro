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
 *
 * 'google_ads'     → config "de aplicação" da integração com o Google Ads (não é segredo de
 *                     UMA empresa — é o app FixaOS falando com a API, modelo agência: uma
 *                     conta Gerenciadora vê a conta de cada empresa cliente):
 *   - client_id/client_secret: credenciais OAuth2 do projeto no Google Cloud Console
 *     (APIs e serviços → Credenciais → ID do cliente OAuth, tipo "Aplicativo da Web"), com o
 *     redirect URI cadastrado exatamente como
 *     https://fixaos.com.br/marketing/conectar/google/callback (ver
 *     MasterController::marketingGoogleRedirectUri()).
 *   - developer_token: DEPRECIADO pelo Google em 09/09/2026 — o nível de acesso (antes preso
 *     ao token) passou a ser do PROJETO do Google Cloud (Google Ads API → "Níveis de acesso"
 *     → Gerenciar, na própria página da API no Cloud Console), condicionado a passar por
 *     "verificação de marca" (Tela de permissão OAuth → Branding → Verificar marca). O campo
 *     continua aqui só por compatibilidade — o valor é ignorado pela API, pode ficar vazio.
 *   - login_customer_id: o Customer ID da própria conta Gerenciadora (só dígitos, sem traço).
 *   - api_version: versão da API do Google Ads em uso (ex. "v18") — atualize quando o Google
 *     depreciar a versão corrente (eles avisam com bastante antecedência por e-mail).
 *   O refresh_token (o segredo de quem de fato logou e autorizou o FixaOS a agir pela conta
 *   Gerenciadora) NÃO fica aqui — é obtido pelo fluxo de conexão em
 *   /master/marketing/google-ads (MasterController::marketingConectarGoogle*()), cifrado com
 *   CredentialCipher e gravado em mkt_credentials (scope='global', platform='google_ads'),
 *   igual já vale pro token da Meta.
 */
return [
    'dry_run'        => true,
    'encryption_key' => '',
    'key_version'    => 1,
    'google_ads'     => [
        'client_id'         => '',
        'client_secret'     => '',
        'developer_token'   => '',
        'login_customer_id' => '',
        'api_version'       => 'v18',
    ],
];
