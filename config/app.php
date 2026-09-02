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
