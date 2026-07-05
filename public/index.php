<?php

define('BASE_PATH', dirname(__DIR__));

// Autoload
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

// Helpers
require BASE_PATH . '/app/Helpers/functions.php';

// Config
$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
ini_set('display_errors', (int) $appConfig['debug']);
error_reporting($appConfig['debug'] ? E_ALL : 0);

// Session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$sessionLifetime = 8 * 60 * 60; // 8h — evita reautenticação no meio de tarefas longas (comum no celular)

ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name($appConfig['session_name']);
session_start();

// Router
$router = new \App\Core\Router();
require BASE_PATH . '/routes/web.php';

// _method override (PUT/DELETE via forms)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$uri = $_SERVER['REQUEST_URI'];
// Strip base path if running in subdir
$basePath = parse_url($appConfig['url'], PHP_URL_PATH);
if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri ?: '/', '/');

$router->dispatch($method, $uri);
