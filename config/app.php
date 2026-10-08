<?php

$config = [
    'name'     => 'OficinaTech',
    'version'  => '1.1.0',
    'url'      => 'http://localhost/oficina-eletro/public',
    'timezone' => 'America/Sao_Paulo',
    'locale'   => 'pt_BR',
    'debug'    => true,
    'key'      => 'base64:change-this-32-char-secret-key!!',
    'session_name' => 'oficina_session',
    'cobranca_ativa' => true, // liga o enforcement de trial/licença (plano_efetivo, licenca_ativa_diretorio, etc.)
    // Dias de carência após trial_ate/licenca_ate vencer ANTES de sistema_bloqueado() bloquear
    // de verdade — ver app/Helpers/functions.php. Mesma constante reaproveitada pelo bloqueio
    // do Carteira Fixa standalone (AssinaturaService::statusEfetivo()), pedido explícito.
    'carencia_dias' => 3,
    // Avisos de vencimento (scripts/avisar_vencimento_licenca.php,
    // scripts/avisar_teste_fixa_terminando.php) já têm o e-mail e a notificação in-app
    // prontos; o WhatsApp fica preparado mas DESLIGADO até decisão explícita de ligar — true
    // passa a chamar WhatsAppService::enviarTextoPlataforma() também.
    'aviso_vencimento_whatsapp' => false,
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'upload_path' => dirname(__DIR__) . '/storage/uploads',
    'log_path'   => dirname(__DIR__) . '/storage/logs',
];

// config/app.local.php NUNCA entra no git (.gitignore) — guarda os valores reais de
// CADA ambiente (hoje só url/debug/key fazem sentido divergir; o resto é seguro
// compartilhar) e sempre vence os defaults acima. É isso que torna seguro rodar
// `git checkout github/<branch> -- config/app.php` em produção: o arquivo versionado
// pode ser sobrescrito à vontade que o ambiente real nunca muda — antes disso, um
// checkout desse arquivo já derrubou produção sobrescrevendo url/debug com os valores
// de dev (ver CLAUDE.md, "Padrão de deploy deste projeto"). Primeira configuração de um
// ambiente novo: copiar config/app.local.php.example pra cá e preencher os valores reais.
$localFile = __DIR__ . '/app.local.php';
if (is_file($localFile)) {
    $config = array_merge($config, require $localFile);
}

return $config;
