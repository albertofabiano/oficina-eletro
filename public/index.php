<?php

define('BASE_PATH', dirname(__DIR__));

// Autoload
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

// Helpers
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Helpers/rrule.php';

// Config
$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
ini_set('display_errors', (int) $appConfig['debug']); // só exibe erros na tela em modo debug
ini_set('log_errors', '1');                            // sempre registra no log de erros
error_reporting(E_ALL);                                // reporta tudo (para o log)

// Session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$sessionLifetime = 8 * 60 * 60; // 8h - evita reautenticacao no meio de tarefas longas (comum no celular)

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
header('Content-Type: text/html; charset=UTF-8');

// Envio de formulário maior que post_max_size: o PHP descarta $_POST/$_FILES,
// o que faria o csrf_verify() falhar com "token inválido" (confuso). Detecta
// esse caso (só em formulários, não em APIs JSON) e avisa de forma clara.
$_ct = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    && empty($_POST) && empty($_FILES)
    && (str_contains($_ct, 'multipart/form-data') || str_contains($_ct, 'x-www-form-urlencoded'))) {
    $_SESSION['flash']['error'] = 'O arquivo/imagem enviado é grande demais. Reduza a foto e tente novamente.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
    exit;
}

// Router
$router = new \App\Core\Router();
require BASE_PATH . '/routes/web.php';

// _method override (PUT/DELETE via forms E via fetch com corpo JSON)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    if (isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    } elseif (str_contains($_ct, 'application/json')) {
        // php://input é re-legível para JSON; o Controller o relê depois normalmente.
        $_body = json_decode(file_get_contents('php://input'), true);
        if (is_array($_body) && !empty($_body['_method'])) {
            $method = strtoupper($_body['_method']);
        }
    }
}

$uri = $_SERVER['REQUEST_URI'];
// Strip base path if running in subdir
$basePath = parse_url($appConfig['url'], PHP_URL_PATH);
if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri ?: '/', '/');

$router->dispatch($method, $uri);
