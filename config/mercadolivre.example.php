<?php
/*
 * Config da integração com a API do Mercado Livre (Fixa Indica — busca de dados de anúncio +
 * atualização diária de preço). Copie este arquivo para config/mercadolivre.php (gitignored —
 * só existe no servidor) e preencha com os dados reais do app criado em
 * developers.mercadolivre.com.br/devcenter.
 *
 * Como criar o app:
 *   1. Entre em developers.mercadolivre.com.br/devcenter logado com sua conta Mercado Livre.
 *   2. "Criar nova aplicação".
 *   3. Em "URL de redirect", cole EXATAMENTE:
 *        https://indica.fixaos.com.br/admin/mercadolivre/callback
 *      (tem que bater byte a byte com essa string — o Mercado Livre rejeita qualquer
 *      diferença, mesmo uma barra a mais no fim).
 *   4. Marque os escopos "Leitura" (read) e "offline_access" (é o que garante um
 *      refresh_token, sem isso o acesso expira em poucas horas e não renova sozinho).
 *   5. Ao salvar, o Mercado Livre mostra "App ID" (= client_id) e "Secret Key"
 *      (= client_secret) — cole os dois abaixo.
 *
 * 'app_id'        → App ID (client_id) do app criado.
 * 'client_secret' → Secret Key do app — NUNCA cole isso em nenhum arquivo versionado nem
 *                    mande por chat/e-mail; preencha direto neste arquivo, no servidor.
 *
 * Depois de preencher, acesse https://indica.fixaos.com.br/admin/config e clique em
 * "Conectar com Mercado Livre" — é um passo único (autoriza sua conta Mercado Livre a dar
 * acesso de leitura ao app); o token fica salvo em indica_config e se renova sozinho.
 */
return [
    'app_id'        => '',
    'client_secret' => '',
];
