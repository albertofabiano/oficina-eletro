<?php

return [
    'name'     => 'FixaOS',
    'version'  => '1.0.0',
    'url'      => 'https://fixaos.com.br',
    'timezone' => 'America/Sao_Paulo',
    'locale'   => 'pt_BR',
    'debug'    => false,
    'key'      => 'base64:E67L8fF10xigQZ83Df0KNMPZjO49Jfjl1Pi44Fqu7gM=',
    'session_name' => 'oficina_session',
    'upload_max_size' => 5 * 1024 * 1024,
    'upload_path' => dirname(__DIR__) . '/storage/uploads',
    'log_path'   => dirname(__DIR__) . '/storage/logs',

    // Cobrança/licenças: enquanto FALSE, a trava "edita só com licença ativa" fica
    // DORMENTE (ninguém é travado). Ligar para TRUE quando o billing existir.
    'cobranca_ativa' => true,
];
