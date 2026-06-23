<?php

function url(string $path = ''): string
{
    $cfg = require BASE_PATH . '/config/app.php';
    return rtrim($cfg['url'], '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function csrf_token(): string
{
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['_token'] ?? '', $token);
}

function flash(string $type = null): ?string
{
    if ($type === null) return null;
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function money(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function date_br(?string $date, bool $withTime = false): string
{
    if (empty($date) || $date === '0000-00-00') return '-';
    $fmt = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($fmt, strtotime($date));
}

function date_mysql(string $date): string
{
    if (str_contains($date, '/')) {
        $parts = explode('/', $date);
        return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
    }
    return $date;
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim($text, '-'));
}

function only_numbers(string $str): string
{
    return preg_replace('/\D/', '', $str);
}

function pagination(array $paginator, string $baseUrl): string
{
    if ($paginator['last_page'] <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm mb-0">';
    for ($i = 1; $i <= $paginator['last_page']; $i++) {
        $active = $i === $paginator['current_page'] ? ' active' : '';
        $html .= "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$i}\">{$i}</a></li>";
    }
    return $html . '</ul></nav>';
}

function badge_status_os(string $tipo, string $nome, string $cor = ''): string
{
    if ($cor) {
        return "<span class=\"badge\" style=\"background:{$cor}\">" . e($nome) . "</span>";
    }
    $map = [
        'aberta'       => 'secondary',
        'em_andamento' => 'info',
        'aguardando'   => 'warning',
        'concluida'    => 'success',
        'entregue'     => 'success',
        'cancelada'    => 'danger',
    ];
    return "<span class=\"badge bg-" . ($map[$tipo] ?? 'secondary') . "\">" . e($nome) . "</span>";
}

function numero_os(int $id, string $prefixo = 'OS', int $digitos = 6): string
{
    return $prefixo . str_pad($id, $digitos, '0', STR_PAD_LEFT);
}

function avatar_iniciais(string $nome): string
{
    $partes = explode(' ', trim($nome));
    $ini = mb_strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) $ini .= mb_strtoupper(mb_substr(end($partes), 0, 1));
    return $ini;
}
