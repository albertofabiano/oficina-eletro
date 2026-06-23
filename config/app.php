<?php

return [
    'name'     => 'OficinaTech',
    'version'  => '1.0.0',
    'url'      => 'http://localhost/oficina-eletro/public',
    'timezone' => 'America/Sao_Paulo',
    'locale'   => 'pt_BR',
    'debug'    => true,
    'key'      => 'base64:change-this-32-char-secret-key!!',
    'session_name' => 'oficina_session',
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'upload_path' => dirname(__DIR__) . '/storage/uploads',
    'log_path'   => dirname(__DIR__) . '/storage/logs',
];
