<?php

/**
 * Sufixo de query string pra preservar ?painel=1 em <form action> e links dentro das
 * páginas usadas nos iframes das abas de Configurações — sem isso, o POST/redirect
 * perde o contexto do iframe e o layout completo (sidebar/topbar) acaba renderizando
 * empilhado dentro da caixinha pequena do iframe.
 */
function painel_qs(): string
{
    return !empty($_GET['painel']) ? '?painel=1' : '';
}

// ── Tradução / Internacionalização ───────────────────────────────────
function lang(): string
{
    // 1. Idioma da sessão (empresa logada)
    if (!empty($_SESSION['usuario']['idioma'])) {
        return $_SESSION['usuario']['idioma'];
    }
    // 2. Padrão
    return 'pt_BR';
}

function __(string $key, string $fallback = ''): string
{
    static $strings = [];
    $idioma = lang();

    if (empty($strings[$idioma])) {
        $file = BASE_PATH . '/lang/' . $idioma . '.php';
        $strings[$idioma] = file_exists($file) ? require $file : [];
    }

    return $strings[$idioma][$key] ?? ($fallback ?: $key);
}

function money(float $value): string
{
    $simbolo = __('moeda_simbolo', 'R$');
    return $simbolo . ' ' . number_format($value, 2, ',', '.');
}

/**
 * Converte um valor monetário digitado (formato BR) para float.
 * Regra BR: vírgula = separador decimal; ponto = separador de milhar.
 * Ex: "1.200"    -> 1200.0
 *     "1.200,50" -> 1200.5
 *     "1200,50"  -> 1200.5
 *     "1200"     -> 1200.0
 */
function moeda_float($str): float
{
    $s = preg_replace('/[^\d,.\-]/', '', (string) $str);
    if ($s === '' || $s === '-') return 0.0;

    if (strpos($s, ',') !== false) {
        // Tem vírgula decimal (padrão BR): pontos são milhar
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif (substr_count($s, '.') === 1 && strlen(substr($s, strrpos($s, '.') + 1)) <= 2) {
        // Um único ponto com 1-2 casas = separador DECIMAL (ex.: "44.90" vindo do estoque/DB). Mantém.
    } else {
        // Vários pontos, ou ponto com 3 casas = separador de milhar -> remover
        $s = str_replace('.', '', $s);
    }
    return (float) $s;
}

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

function old(string $key, mixed $default = ''): mixed
{
    $val = $_SESSION['_old'][$key] ?? $default;
    unset($_SESSION['_old'][$key]);
    return $val;
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Detecta robô de busca/crawler pelo User-Agent — checagem barata (sem chamada externa,
 * sem latência), usada antes de qualquer coisa que grave "visita" de verdade (contador do
 * diretório, fila de geolocalização por IP). Sem essa checagem, o Googlebot (e qualquer
 * outro crawler) incrementaria o contador a cada rastreamento — diferente de visita humana,
 * ele nunca carrega cookie/sessão de volta, então o dedup "1x por sessão" já existente não
 * pega esse caso: cada crawl vira uma "visita nova". Cobre os crawlers mais comuns
 * (Google/Bing/Yandex/Baidu/DuckDuckGo, prévias de link do WhatsApp/Telegram/Facebook/
 * Twitter/LinkedIn, SEO tools) — "bot"/"crawler"/"spider" sozinhos já cobrem a esmagadora
 * maioria, incluindo os que não estão na lista nomeada.
 */
function requisicao_de_robo(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') return true; // sem User-Agent nenhum não é navegador real
    return (bool) preg_match(
        '/bot|crawler|spider|slurp|facebookexternalhit|whatsapp|telegrambot|applebot|embedly|quora link preview|pinterest/i',
        $ua
    );
}

/**
 * Primeiro nome de um nome completo — sempre a primeira palavra, com acento e tudo
 * (não remove acentuação, só corta no primeiro espaço). Usado em listas onde o nome
 * completo do cliente ocuparia espaço demais.
 */
function primeiro_nome(?string $nomeCompleto): string
{
    $nome = trim((string) $nomeCompleto);
    if ($nome === '') {
        return '';
    }
    $primeiro = strtok($nome, ' ');
    return $primeiro !== false ? $primeiro : $nome;
}

/**
 * Escapa HTML e transforma URLs (http/https) em links clicáveis, abrindo em nova aba — pra
 * texto livre digitado pelo usuário (ex.: Observações internas da OS), nunca HTML de verdade.
 * Preserva quebra de linha (nl2br).
 */
function linkify(?string $texto): string
{
    $escapado = e($texto);
    $comLinks = preg_replace(
        '~(https?://[^\s<]+)~i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $escapado
    );
    return nl2br($comLinks);
}

/**
 * Escurece uma cor hex (#RRGGBB) multiplicando cada canal por um fator (0–1, padrão 0.55) —
 * usado pra gerar o segundo tom de um gradiente a partir de uma cor só escolhida pelo usuário
 * (ex.: banner do perfil público do Diretório), sem precisar guardar duas cores por registro.
 * Hex inválido devolve a mesma entrada, sem gerar erro.
 */
function cor_escurecer(string $hex, float $fator = 0.55): string
{
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#' . $hex;
    $fator = max(0, min(1, $fator));
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    return sprintf('#%02x%02x%02x', (int) round($r * $fator), (int) round($g * $fator), (int) round($b * $fator));
}

/**
 * Ícones inline (SVG) do Financeiro Pessoal — área deliberadamente isolada do resto do
 * FixaOS (mesmo login, nenhum recurso compartilhado, inclusive visual/CDN). Antes usava o
 * mesmo link de CDN do Bootstrap Icons que `layouts/main.php` já carrega; ficou sujeito a
 * falhar nesse ponto específico (rede do usuário/bloqueio de CDN) mesmo com o resto do
 * FixaOS funcionando, e além disso é uma dependência externa que a área não devia ter.
 * Cada ícone é desenhado à mão, sem arquivo/fonte externa — sempre renderiza, mesmo offline.
 * Usa `currentColor` pra herdar a cor do elemento (mesmo comportamento que a fonte de ícones
 * tinha via CSS `color`); partes que precisam do tom de fundo (furo do círculo, texto da
 * linha do cartão) usam `style="fill:var(--surf)"` (var() funciona em atributo `style` por
 * ser CSS de verdade, diferente de um atributo de apresentação solto).
 */
function fp_icone(string $nome): string
{
    static $mapa = null;
    if ($mapa === null) {
        $mapa = [
            'chat-dots-fill' => '<path d="M1 7.5C1 4 4.1 1.5 8 1.5s7 2.5 7 6-3.1 6-7 6c-.7 0-1.4-.1-2-.3L3.5 15l.7-2.4C2.4 11.5 1 9.6 1 7.5z" fill="currentColor"/><circle cx="5.3" cy="7.5" r="0.9" style="fill:var(--surf)"/><circle cx="8" cy="7.5" r="0.9" style="fill:var(--surf)"/><circle cx="10.7" cy="7.5" r="0.9" style="fill:var(--surf)"/>',
            'bar-chart-fill' => '<rect x="1.5" y="9" width="3" height="5" rx="0.5" fill="currentColor"/><rect x="6.5" y="5.5" width="3" height="8.5" rx="0.5" fill="currentColor"/><rect x="11.5" y="2" width="3" height="12" rx="0.5" fill="currentColor"/>',
            'box-arrow-left' => '<path d="M9.5 1.5H13a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H9.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 11L2.5 8 6 5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 8h7" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
            'moon-stars' => '<path d="M10.5 2.3A6 6 0 1 0 13.7 11a5 5 0 0 1-3.2-8.7z" fill="currentColor"/><path d="M13.8 1.6l.3.9.9.3-.9.3-.3.9-.3-.9-.9-.3.9-.3zM11.5 5.2l.2.6.6.2-.6.2-.2.6-.2-.6-.6-.2.6-.2z" fill="currentColor"/>',
            'sun' => '<circle cx="8" cy="8" r="3.3" fill="currentColor"/><path d="M8 1v1.6M8 13.4V15M1 8h1.6M13.4 8H15M3.1 3.1l1.1 1.1M11.8 11.8l1.1 1.1M3.1 12.9l1.1-1.1M11.8 4.2l1.1-1.1" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
            'chevron-left' => '<polyline points="10,3 5,8 10,13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-right' => '<polyline points="6,3 11,8 6,13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-down' => '<polyline points="3,6 8,11 13,6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            'check-lg' => '<polyline points="2,8.5 6,13 14,3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            'qr-code-scan' => '<path d="M1.5 4.5V2A.5.5 0 0 1 2 1.5h2.5M14.5 4.5V2a.5.5 0 0 0-.5-.5h-2.5M1.5 11.5V14a.5.5 0 0 0 .5.5h2.5M14.5 11.5V14a.5.5 0 0 1-.5.5h-2.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><rect x="6" y="6" width="4" height="4" fill="currentColor"/>',
            'list-ul' => '<rect x="2" y="3" width="12" height="1.7" rx="0.85" fill="currentColor"/><rect x="2" y="7.15" width="12" height="1.7" rx="0.85" fill="currentColor"/><rect x="2" y="11.3" width="12" height="1.7" rx="0.85" fill="currentColor"/>',
            'arrow-down-circle-fill' => '<circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M8 4.2v4.6M5.6 7l2.4 2.6L10.4 7" fill="none" style="stroke:var(--surf)" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
            'arrow-up-circle-fill' => '<circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M8 11.8V7.2M5.6 9l2.4-2.6L10.4 9" fill="none" style="stroke:var(--surf)" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
            'pencil-fill' => '<path d="M13.3 1.3a1.5 1.5 0 0 1 2.1 2.1l-.9.9-2.1-2.1zM11.6 3l2.1 2.1-8 8-2.6.5.5-2.6z" fill="currentColor"/>',
            'credit-card-2-front-fill' => '<rect x="1" y="3" width="14" height="10" rx="1.5" fill="currentColor"/><rect x="1" y="3" width="14" height="2.2" style="fill:var(--surf)"/><rect x="3" y="9.3" width="4" height="1.6" rx="0.5" style="fill:var(--surf)"/>',
            'receipt' => '<path d="M3 1.5h10v13l-1.5-1-1.5 1-1.5-1-1.5 1-1.5-1-1.5 1v-13z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 5h6M5 7.5h6M5 10h4" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>',
            'trash3' => '<path d="M2.5 3.5h11" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M5.5 3.5V2a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v1.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 3.5l.6 9.5a1.5 1.5 0 0 0 1.5 1.4h4.8a1.5 1.5 0 0 0 1.5-1.4l.6-9.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 6.5v5M9.5 6.5v5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
            'tag-fill' => '<path d="M1.5 1.5h5.6a1 1 0 0 1 .7.3l6.4 6.4a1 1 0 0 1 0 1.4l-5.6 5.6a1 1 0 0 1-1.4 0L.8 8.8a1 1 0 0 1-.3-.7V2.5a1 1 0 0 1 1-1z" fill="currentColor"/><circle cx="4.7" cy="4.7" r="1.2" style="fill:var(--surf)"/>',
            'sliders' => '<line x1="2" y1="4" x2="14" y2="4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="10" cy="4" r="1.6" fill="currentColor"/><line x1="2" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="5" cy="8" r="1.6" fill="currentColor"/><line x1="2" y1="12" x2="14" y2="12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="11" cy="12" r="1.6" fill="currentColor"/>',
            'calendar3' => '<rect x="1.5" y="2.5" width="13" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.3"/><line x1="1.5" y1="6" x2="14.5" y2="6" stroke="currentColor" stroke-width="1.3"/><line x1="4.5" y1="1" x2="4.5" y2="3.6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><line x1="11.5" y1="1" x2="11.5" y2="3.6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="5" cy="9" r="0.9" fill="currentColor"/><circle cx="8" cy="9" r="0.9" fill="currentColor"/><circle cx="11" cy="9" r="0.9" fill="currentColor"/><circle cx="5" cy="12" r="0.9" fill="currentColor"/><circle cx="8" cy="12" r="0.9" fill="currentColor"/>',
            'bell-fill' => '<path d="M8 1a1 1 0 0 1 1 1v.17a4.5 4.5 0 0 1 3.5 4.39v2.56l1.06 2.02a1 1 0 0 1-.88 1.47H3.32a1 1 0 0 1-.88-1.47L3.5 9.12V6.56A4.5 4.5 0 0 1 7 2.17V2a1 1 0 0 1 1-1z" fill="currentColor"/><path d="M6.2 13.6a1.9 1.9 0 0 0 3.6 0" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
            // Fixa Fase 1 (perfis/contas/lançamentos) — ícones novos, mesmo estilo dos acima
            // (traço 1.3, viewBox 16x16).
            'search' => '<circle cx="6.8" cy="6.8" r="4.8" fill="none" stroke="currentColor" stroke-width="1.4"/><line x1="10.3" y1="10.3" x2="14.5" y2="14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
            'paperclip' => '<path d="M11.5 3.5L4.8 10.2a2.6 2.6 0 0 0 3.7 3.7l6.2-6.2a1.7 1.7 0 0 0-2.4-2.4L6.6 11a0.8.8 0 0 0 1.1 1.1l5.6-5.6" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>',
            'arrow-counterclockwise' => '<path d="M13.5 8A5.5 5.5 0 1 1 8 2.5c1.6 0 3 .65 4 1.7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><polyline points="12.2,1.8 12.4,4.6 9.6,4.9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>',
            'building' => '<rect x="3" y="1.5" width="8" height="13" fill="none" stroke="currentColor" stroke-width="1.2" rx="0.6"/><rect x="5" y="3.5" width="1.6" height="1.6" fill="currentColor"/><rect x="9" y="3.5" width="1.6" height="1.6" fill="currentColor"/><rect x="5" y="6.5" width="1.6" height="1.6" fill="currentColor"/><rect x="9" y="6.5" width="1.6" height="1.6" fill="currentColor"/><rect x="5" y="9.5" width="1.6" height="1.6" fill="currentColor"/><rect x="9" y="9.5" width="1.6" height="1.6" fill="currentColor"/><rect x="6.2" y="12" width="3.6" height="2.5" fill="currentColor"/>',
            'person-fill' => '<circle cx="8" cy="5" r="3" fill="currentColor"/><path d="M2.5 14c.4-3.2 2.7-5 5.5-5s5.1 1.8 5.5 5" fill="currentColor"/>',
            'wallet2' => '<path d="M1.5 4.5A1.5 1.5 0 0 1 3 3h9a1.5 1.5 0 0 1 1.5 1.5v7A1.5 1.5 0 0 1 12 13H3a1.5 1.5 0 0 1-1.5-1.5v-7z" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M1.5 6.5h12" stroke="currentColor" stroke-width="1.2"/><circle cx="10.5" cy="9" r="0.9" fill="currentColor"/>',
            'plus-circle-fill' => '<circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M8 4.8v6.4M4.8 8h6.4" stroke="currentColor" style="stroke:var(--surf)" stroke-width="1.4" stroke-linecap="round"/>',
            'chevron-expand' => '<polyline points="5,6 8,3 11,6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="5,10 8,13 11,10" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>',
        ];
    }

    $miolo = $mapa[$nome] ?? '';
    return '<svg viewBox="0 0 16 16" width="1em" height="1em" aria-hidden="true" focusable="false" style="display:inline-block;vertical-align:-0.125em">' . $miolo . '</svg>';
}

/**
 * Sanitiza HTML vindo de um editor WYSIWYG contenteditable simples (negrito/itálico/
 * sublinhado/listas/cor, via execCommand) — mantém só tags de formatação básica, sem
 * atributos, exceto "style" em <span>, e mesmo assim só a propriedade color com valor
 * hex/rgb válido. Extraída de `OrdemServicoController::sanitizarLaudoHtml()` (laudo técnico
 * da OS) pra ser reaproveitada por qualquer outro campo rico do sistema (ex.: descrição
 * pública da empresa) sem duplicar a mesma regra em cada controller.
 */
function html_rico_sanitizar(string $html): string
{
    $html = trim($html);
    if ($html === '') { return ''; }

    $html = strip_tags($html, '<b><strong><i><em><u><span><font><br><div><p><ul><ol><li>');

    // Normaliza <font color="..."> pro mesmo formato de <span style="color:...">
    // (browsers antigos/execCommand sem styleWithCSS geram <font> em vez de span+style).
    $html = preg_replace_callback('/<font([^>]*)>/i', function ($m) {
        if (preg_match('/color\s*=\s*"?(#[0-9a-fA-F]{3,8})"?/i', $m[1], $cm)) {
            return '<span style="color:' . $cm[1] . '">';
        }
        return '<span>';
    }, $html);
    $html = str_ireplace('</font>', '</span>', $html);

    $html = preg_replace_callback('/<span([^>]*)>/i', function ($m) {
        if (preg_match('/style\s*=\s*"([^"]*)"/i', $m[1], $sm)
            && preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,8}|rgb\([\d,\s]+\))/i', $sm[1], $cm)) {
            return '<span style="color:' . $cm[1] . '">';
        }
        return '<span>';
    }, $html);

    $html = preg_replace('/<(b|strong|i|em|u|br|div|p|ul|ol|li)\s[^>]*>/i', '<$1>', $html);

    return trim($html);
}

/** Valida um CNPJ (dígitos verificadores). Aceita com ou sem máscara. */
function cnpj_valido(string $cnpj): bool
{
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) !== 14) return false;
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false; // todos iguais
    for ($t = 12; $t < 14; $t++) {
        $d = 0; $m = $t - 7;
        for ($i = 0; $i < $t; $i++) {
            $d += (int) $cnpj[$i] * $m;
            $m = ($m === 2) ? 9 : $m - 1;
        }
        $d = ((10 * $d) % 11) % 10;
        if ((int) $cnpj[$t] !== $d) return false;
    }
    return true;
}

function cpf_valido(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return false;
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false; // todos iguais
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($i = 0; $i < $t; $i++) {
            $d += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $d) % 11) % 10;
        if ((int) $cpf[$t] !== $d) return false;
    }
    return true;
}

/** CPF (11) ou CNPJ (14) válido. Vazio = true (documento é opcional). */
function documento_valido(string $doc): bool
{
    $n = preg_replace('/\D/', '', $doc);
    if ($n === '') return true;
    if (strlen($n) === 11) return cpf_valido($n);
    if (strlen($n) === 14) return cnpj_valido($n);
    return false;
}

/** Formata CPF/CNPJ pra exibição (000.000.000-00 / 00.000.000/0000-00) — só pela quantidade
 *  de dígitos, não depende de saber se é 'pf'/'pj' de antemão. Sem 11/14 dígitos (incompleto,
 *  ou já veio formatado por engano), devolve só os dígitos, nunca lixo misturado. */
function documento_mascara(?string $doc): string
{
    $n = preg_replace('/\D/', '', (string) $doc);
    if (strlen($n) === 11) {
        return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9, 2);
    }
    if (strlen($n) === 14) {
        return substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12, 2);
    }
    return $n;
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

/**
 * Feriados nacionais brasileiros de um ano, incluindo os móveis (calculados a
 * partir da Páscoa via algoritmo de Meeus/Jones/Butcher — sem depender da
 * extensão `calendar`/`easter_date()`, que nem sempre vem habilitada).
 * Retorna ['Y-m-d' => 'Nome do feriado'].
 */
function feriados_nacionais_brasil(int $ano): array
{
    $a = $ano % 19;
    $b = intdiv($ano, 100);
    $c = $ano % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $mesPascoa = intdiv($h + $l - 7 * $m + 114, 31);
    $diaPascoa = (($h + $l - 7 * $m + 114) % 31) + 1;
    $pascoa    = mktime(0, 0, 0, $mesPascoa, $diaPascoa, $ano);

    $fixos = [
        "$ano-01-01" => 'Confraternização Universal',
        "$ano-04-21" => 'Tiradentes',
        "$ano-05-01" => 'Dia do Trabalho',
        "$ano-09-07" => 'Independência do Brasil',
        "$ano-10-12" => 'Nossa Senhora Aparecida',
        "$ano-11-02" => 'Finados',
        "$ano-11-15" => 'Proclamação da República',
        "$ano-11-20" => 'Consciência Negra',
        "$ano-12-25" => 'Natal',
    ];

    $moveis = [
        date('Y-m-d', strtotime('-47 days', $pascoa)) => 'Carnaval',
        date('Y-m-d', strtotime('-2 days', $pascoa))  => 'Sexta-feira Santa',
        date('Y-m-d', strtotime('+60 days', $pascoa)) => 'Corpus Christi',
    ];

    return $fixos + $moveis;
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim($text, '-'));
}

/**
 * Remove acentos via mapa manual — não iconv//TRANSLIT, que pode falhar silenciosamente
 * conforme locale do servidor (mesmo motivo documentado abaixo em slug_empresa_unico()).
 * Compartilhado entre slug_empresa_unico() e empresa_nome_indica_servico().
 */
function remover_acentos(string $s): string
{
    static $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a','É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
        'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i','Ó'=>'o','Ò'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
        'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','Ç'=>'c','Ñ'=>'n'];
    return strtr($s, $mapa);
}

/**
 * Cores determinísticas (fundo leve + texto + borda saturada) pra uma tag de texto livre
 * (ex.: "especialidades" da empresa no Diretório) — mesma tag sempre cai no mesmo conjunto de
 * cores, em vez de todo chip sair igual. Mesmas 8 combinações bg/texto/borda já usadas e com
 * contraste conferido em config/eventos_agenda.php (variante "light" de cada tipo de evento),
 * não inventa paleta nova. Escolhida via soma dos códigos de caractere do texto (minúsculo +
 * sem acento, pra "TV"/"tv" e "Televisão"/"televisao" caírem no mesmo conjunto). Mesmo
 * algoritmo replicado em JS (ver empresa/perfil_publico.php, coresDaTag()) pra tag igual
 * renderizar igual tanto no editor (client-side) quanto na ficha pública (aqui, server-side).
 */
function tag_cores(string $texto): array
{
    static $paleta = [
        ['bg' => '#e0e7ff', 'texto' => '#3730a3', 'borda' => '#4f46e5'],
        ['bg' => '#fef3c7', 'texto' => '#92400e', 'borda' => '#f59e0b'],
        ['bg' => '#dcfce7', 'texto' => '#166534', 'borda' => '#16a34a'],
        ['bg' => '#ffedd5', 'texto' => '#9a3412', 'borda' => '#ea580c'],
        ['bg' => '#ccfbf1', 'texto' => '#115e59', 'borda' => '#0d9488'],
        ['bg' => '#ede9fe', 'texto' => '#5b21b6', 'borda' => '#7c3aed'],
        ['bg' => '#fce7f3', 'texto' => '#9d174d', 'borda' => '#db2777'],
        ['bg' => '#e0f2fe', 'texto' => '#0369a1', 'borda' => '#0ea5e9'],
    ];
    $t = remover_acentos(mb_strtolower(trim($texto)));
    $soma = 0;
    for ($i = 0, $len = strlen($t); $i < $len; $i++) {
        $soma += ord($t[$i]);
    }
    return $paleta[$soma % count($paleta)];
}

/**
 * Slug único da URL pública de uma empresa (/assistencias/{slug}), a partir de nome + cidade.
 * Compartilhado entre EmpresaController (editar perfil) e DiretorioController (cadastro
 * inicial) — antes cada um reimplementava essa lógica separado, o que já rendeu um bug real
 * (um lugar recalculava o slug ao salvar o nome, o outro não, deixando a URL pública presa
 * num nome antigo/com erro). Nunca apaga um slug já existente com um vazio.
 * $eid = 0 para empresa nova recém-inserida (chame depois do INSERT, já com o id real, senão
 * uma colisão seria resolvida como "-0" em vez do id de verdade).
 */
function slug_empresa_unico(string $nome, string $cidade, int $eid, ?string $slugAtual): ?string
{
    if ($nome === '') return $slugAtual;

    $rawSlug  = remover_acentos($nome . '-' . $cidade);
    $rawSlug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $rawSlug));
    $novoSlug = trim($rawSlug, '-');
    if ($novoSlug === '') return $slugAtual;

    $stmtSl = \App\Core\DB::pdo()->prepare("SELECT id FROM empresas WHERE slug = ? AND id != ?");
    $stmtSl->execute([$novoSlug, $eid]);
    if ($stmtSl->fetch()) $novoSlug .= '-' . $eid;
    return $novoSlug;
}

/**
 * Palavras que, presentes no nome da empresa, indicam que ela é de fato do ramo de assistência
 * técnica/conserto — usado pra restringir a indexação SEO das fichas NÃO reivindicadas (ver
 * empresa_nome_indica_servico() e SitemapController). A base de CNPJ importada filtra só por
 * CNAE do setor, mas nem toda empresa dentro dessas CNAEs presta o serviço (achado real:
 * "Software Developer", "Via Legis", nome de pessoa física como MEI) — o nome ainda é o único
 * sinal disponível sem depender de revisão manual de ~18 mil fichas.
 */
function empresa_palavras_servico(): array
{
    return [
        'informatica', 'conserto', 'eletrodomestico', 'computador', 'assistencia tecnica',
        'assistencia', 'eletronica', 'manutencao', 'celular', 'smartphone', 'notebook',
        'refrigeracao', 'ar condicionado', 'climatizacao', 'reparo', 'eletroeletronico',
        'tech', 'repair', 'cell', 'phone', 'televisao', 'lavadora', 'geladeira', 'freezer',
        'placa', 'solda', 'servico tecnico', 'servicos tecnicos', 'informatico', 'eletrica',
    ];
}

/**
 * Nome da empresa contém alguma palavra de empresa_palavras_servico() (sem acento/case),
 * como PALAVRA inteira — não como pedaço de outra palavra (ex.: "tech" não deve bater dentro
 * de "Technog"/"Btechstore"; achado real testando contra a amostra de dados de produção).
 */
function empresa_nome_indica_servico(?string $nome): bool
{
    $nome = trim((string) $nome);
    if ($nome === '') return false;
    $norm = mb_strtolower(remover_acentos($nome));
    foreach (empresa_palavras_servico() as $kw) {
        // (es|s)? antes da borda final: aceita plural (celular/celulares, computador/
        // computadores — plural em -r/-l soma "es" em português — e phone/phones).
        if (preg_match('/(?<![a-z])' . preg_quote($kw, '/') . '(es|s)?(?![a-z])/', $norm)) return true;
    }
    return false;
}

/**
 * Nome completo do estado a partir da sigla (UF) — usado no breadcrumb/título das páginas de
 * cidade/serviço do Diretório ("Início › São Paulo › Dracena"). Não existia em lugar nenhum
 * do projeto antes (empresas.uf sempre guarda só a sigla de 2 letras).
 */
function uf_nome_estado(string $uf): string
{
    static $mapa = [
        'AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará',
        'DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão',
        'MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará',
        'PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro',
        'RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima',
        'SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins',
    ];
    return $mapa[strtoupper($uf)] ?? strtoupper($uf);
}

/**
 * Sigla de verdade (uma das 27 UFs) ou não — reaproveita uf_nome_estado() em vez de duplicar o
 * mapa: UF válida sempre devolve um nome diferente da sigla em si; UF desconhecida cai no
 * fallback (devolve a própria sigla em maiúsculo, sem traduzir pra nome nenhum). Usado em
 * DiretorioController::empresa() pra decidir se um "slug" de 2 letras é na verdade a página de
 * estado (/assistencias/{uf}), não uma empresa de verdade.
 */
function uf_e_valida(string $uf): bool
{
    $uf = strtoupper(trim($uf));
    return uf_nome_estado($uf) !== $uf;
}

/**
 * "outubro de 2026" a partir de uma data SQL — não existia helper de nome de mês em português
 * no projeto (date_br() só formata dd/mm/aaaa). Usado na linha "Atualizado em" das páginas de
 * cidade/serviço do Diretório.
 */
function mes_ano_br(?string $data): string
{
    if (empty($data)) return '';
    static $meses = [1=>'janeiro','fevereiro','março','abril','maio','junho','julho','agosto',
        'setembro','outubro','novembro','dezembro'];
    $ts = strtotime($data);
    if ($ts === false) return '';
    return $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}

/**
 * Catálogo canônico de categorias de serviço do Diretório — fonte ÚNICA pros filtros/tags da
 * página de cidade (Fase 1) e pras páginas de serviço (Fase 2), em vez de usar
 * `empresa_servicos.nome` (texto 100% livre digitado por cada empresa — "Troca de tela",
 * "Conserto de TV", "TV"... fragmentaria demais pra virar URL/filtro). A base confiável é
 * `empresa_servicos.icone`, escolhido de uma lista FIXA de 13 opções (ver
 * empresa/perfil_publico.php, $iconesOpc) — cada categoria aqui agrupa 1+ desses ícones.
 * Cores: só as 5 citadas pelo usuário (tv/celular/notebook/tablet/eletrodomesticos) vieram de
 * pedido explícito; as demais (computador/videogame/impressora/ferramentas/pecas/fone) caem
 * num bucket neutro único ("outros") pra não inventar paleta nova sem necessidade real — a
 * maioria das cidades só deve mesmo ter chip pros 5 grupos principais.
 */
function diretorio_servico_categorias(): array
{
    return [
        'tv' => [
            'label' => 'TV', 'slug_url' => 'conserto-de-tv', 'icones' => ['bi-tv'],
            'cor_borda' => '#2F6FDB', 'cor_fundo' => '#EAF1FC', 'cor_texto' => '#1D4C9E',
        ],
        'celular' => [
            'label' => 'Celular', 'slug_url' => 'conserto-de-celular', 'icones' => ['bi-phone'],
            'cor_borda' => '#D9480F', 'cor_fundo' => '#FDEEE6', 'cor_texto' => '#A33A0B',
        ],
        'notebook' => [
            'label' => 'Notebook', 'slug_url' => 'conserto-de-notebook', 'icones' => ['bi-laptop'],
            'cor_borda' => '#7048E8', 'cor_fundo' => '#F1ECFD', 'cor_texto' => '#5233B5',
        ],
        'tablet' => [
            'label' => 'Tablet', 'slug_url' => 'conserto-de-tablet', 'icones' => ['bi-tablet'],
            'cor_borda' => '#0C8599', 'cor_fundo' => '#E3F5F8', 'cor_texto' => '#0A6676',
        ],
        'eletrodomesticos' => [
            'label' => 'Eletrodomésticos', 'slug_url' => 'conserto-de-eletrodomesticos',
            'icones' => ['bi-snow', 'bi-water', 'bi-wind'],
            'cor_borda' => '#2B8A3E', 'cor_fundo' => '#E8F5EB', 'cor_texto' => '#22702F',
        ],
        'outros' => [
            'label' => 'Outros serviços', 'slug_url' => 'outros-servicos',
            'icones' => ['bi-cpu', 'bi-joystick', 'bi-printer', 'bi-tools', 'bi-box2', 'bi-headphones'],
            'cor_borda' => '#495057', 'cor_fundo' => '#F1F3F5', 'cor_texto' => '#343A40',
        ],
    ];
}

/** Slug da categoria (ver diretorio_servico_categorias()) a que um `icone` de empresa_servicos pertence. */
function diretorio_icone_para_categoria(string $icone): ?string
{
    static $mapa = null;
    if ($mapa === null) {
        $mapa = [];
        foreach (diretorio_servico_categorias() as $slug => $cat) {
            foreach ($cat['icones'] as $ic) { $mapa[$ic] = $slug; }
        }
    }
    return $mapa[$icone] ?? null;
}

/**
 * "Perfil completo" pra fins de ordenação/destaque nas páginas de cidade/serviço do Diretório
 * (ver CLAUDE.md) — logo OU descrição pública preenchidos, pelo menos 1 serviço cadastrado, e
 * telefone OU WhatsApp público preenchido. Eixo diferente de `perfil_diretorio_completo()`
 * (que é sobre PLANO PAGO do sistema, não sobre o CONTEÚDO do perfil em si).
 */
function empresa_perfil_conteudo_completo(array $empresa, array $servicos): bool
{
    $temMidiaOuTexto = !empty($empresa['logo']) || trim(strip_tags((string) ($empresa['descricao_publica'] ?? ''))) !== '';
    $temServico      = count($servicos) > 0;
    $temContato      = !empty($empresa['telefone']) || !empty($empresa['whatsapp_publico']);
    return $temMidiaOuTexto && $temServico && $temContato;
}

function only_numbers(string $str): string
{
    return preg_replace('/\D/', '', $str);
}

/**
 * Telefone no formato internacional (+55DDNNNNNNNNN) exigido pela API da InfinitePay
 * (customer.phone_number). Números já com o 55 na frente (12-13 dígitos) não repetem
 * o código do país; números só com DDD+número (10-11 dígitos) recebem o +55.
 */
function telefone_internacional(string $numero): string
{
    $d = only_numbers($numero);
    if ($d === '') return '';
    return '+' . (strlen($d) >= 12 ? $d : '55' . $d);
}

/**
 * Taxa de cartão configurada pela empresa (Config → Cartões), pra forma+parcelas dadas.
 * FONTE ÚNICA DA VERDADE do percentual — nunca confiar no valor que vem do formulário de
 * pagamento (fechamento de OS / PDV), pra evitar erro de digitação virar despesa errada.
 */
function taxa_cartao_configurada(int $empresaId, string $forma, int $parcelas): float
{
    $st = \App\Core\DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='taxas_cartao'");
    $st->execute([$empresaId]);
    $cfg = json_decode((string) $st->fetchColumn(), true) ?: [];

    if ($forma === 'cartao_debito') return (float) ($cfg['debito'] ?? 0);
    if ($forma === 'cartao_credito') return (float) ($cfg['credito'][$parcelas] ?? 0);
    // Pix cobrado pela maquininha de cartão (não o pix recebido direto na conta da empresa, que
    // não tem taxa nenhuma) — mesma ideia do débito: uma taxa única, sem parcela.
    if ($forma === 'pix') return (float) ($cfg['pix'] ?? 0);
    return 0.0;
}

/**
 * Modo de recebimento do crédito parcelado (Config → Cartões): 'mesmo_dia' (a maquininha
 * antecipa tudo de uma vez, já refletido na taxa configurada), 'mes_a_mes' (a adquirente
 * repassa 1 parcela por mês — o sistema lança 1 receita por parcela, na data prevista de cada
 * uma) ou 'prazo_fixo' (a adquirente demora N dias fixos pra repassar o valor total, qualquer
 * que seja o número de parcelas — ver dias_prazo_recebimento_cartao()).
 */
function modo_recebimento_cartao(int $empresaId): string
{
    $st = \App\Core\DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='taxas_cartao'");
    $st->execute([$empresaId]);
    $cfg = json_decode((string) $st->fetchColumn(), true) ?: [];
    $modo = $cfg['modo_recebimento'] ?? 'mesmo_dia';
    return in_array($modo, ['mes_a_mes', 'prazo_fixo'], true) ? $modo : 'mesmo_dia';
}

/**
 * Prazo (em dias, 0 a 30) usado só quando modo_recebimento_cartao() === 'prazo_fixo' — quantos
 * dias depois da venda/fechamento o valor cai no Financeiro (0 = mesmo dia, 1 = dia seguinte,
 * 7 = semanal, etc.). Configurável em Config → Empresa → Cartões.
 */
function dias_prazo_recebimento_cartao(int $empresaId): int
{
    $st = \App\Core\DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='taxas_cartao'");
    $st->execute([$empresaId]);
    $cfg = json_decode((string) $st->fetchColumn(), true) ?: [];
    return max(0, min(30, (int) ($cfg['prazo_dias'] ?? 0)));
}

/**
 * Licença ativa para EDITAR o perfil no diretório. FONTE ÚNICA DA VERDADE.
 * Enquanto `cobranca_ativa` (config/app.php) for false, retorna sempre true (trava dormente).
 * Quando o billing entrar: liga a flag e preenche `empresas.licenca_ate` a cada pagamento.
 */
function licenca_ativa_diretorio(array $empresa): bool
{
    static $cobranca = null;
    if ($cobranca === null) { $cfg = require BASE_PATH . '/config/app.php'; $cobranca = !empty($cfg['cobranca_ativa']); }
    if (!$cobranca) return true;                                    // dormente enquanto não há cobrança
    if (($empresa['tipo_conta'] ?? 'completo') === 'completo') return true; // cliente do sistema nunca trava
    $hoje = date('Y-m-d');
    if (!empty($empresa['trial_ate'])   && $empresa['trial_ate']   >= $hoje) return true;
    if (!empty($empresa['licenca_ate']) && $empresa['licenca_ate'] >= $hoje) return true;
    return false;
}

/**
 * Bloqueio total do sistema (OS, financeiro, estoque, tudo) pra contas completas sem trial nem
 * licença ativa. Justo com quem paga: sem essa trava, quem nunca assina usaria o sistema de
 * graça pra sempre depois do teste. Contas 'diretorio' já são restritas à parte (soDiretorio,
 * no AuthMiddleware) e não passam por aqui.
 */
function sistema_bloqueado(array $empresa): bool
{
    static $cobranca = null;
    if ($cobranca === null) { $cfg = require BASE_PATH . '/config/app.php'; $cobranca = !empty($cfg['cobranca_ativa']); }
    if (!$cobranca) return false;                                    // dormente enquanto não há cobrança
    $hoje = date('Y-m-d');
    if (!empty($empresa['trial_ate'])   && $empresa['trial_ate']   >= $hoje) return false;
    if (!empty($empresa['licenca_ate']) && $empresa['licenca_ate'] >= $hoje) return false;
    return true;
}

/**
 * Edição do perfil do diretório é gratuita para todas as empresas, sem trava de plano/licença.
 */
function perfil_diretorio_editavel(array $empresa): bool
{
    return true;
}

/**
 * Recursos completos do perfil do diretório (redes sociais, serviços, cidade/UF editável,
 * contagem de visitas) só liberam quando a empresa tem um plano pago ativo da FixaOS.
 */
function perfil_diretorio_completo(array $empresa): bool
{
    return !empty($empresa['licenca_ate']) && $empresa['licenca_ate'] >= date('Y-m-d');
}

/**
 * Acesso ao Financeiro Pessoal (ver migration 075) — hoje só o lado GRÁTIS já combinado:
 * empresa reivindicada (dono de verdade logado) + plano Oficina/Empresa. Autônomo e conta
 * só-diretório ficam de fora por enquanto — a assinatura paga avulsa (R$19,90) ainda não tem
 * cobrança integrada, fica pra quando o piloto confirmar demanda real.
 */
/**
 * Libera o Fixa de graça por dois caminhos independentes: (a) empresa reivindicada num plano
 * pago do FixaOS que inclui Fixa — 'basico' fica de fora de propósito, só 'autonomo'/'oficina'/
 * 'empresa' incluem (pedido explícito da Etapa 2); ou (b) assinatura Fixa STANDALONE própria,
 * ativa ou ainda em teste (ver fixa_assinatura_ativa_ou_teste()) — cobre quem nunca teve conta
 * de assistência técnica nenhuma, só quer o financeiro pessoal.
 */
/**
 * Limite mensal de leituras do scanner de contas do Fixa (Etapa 4) — dois caminhos:
 * (a) acesso via plano pago do FixaOS (autonomo/oficina/empresa): usa `scan_fixa_conta_mes`
 *     do plano (mesma regra que já existe pra leitura de etiqueta, pedido explícito);
 * (b) assinatura Fixa STANDALONE: 100/mês fixo (config/planos_fixa.php).
 * Conta sempre contra `fixa_scanner_leituras` (não `scanner_sessoes` — o caminho direto do
 * Fixa, câmera do próprio aparelho sem QR, nunca cria linha lá).
 * @return array{liberado:bool, mensagem:?string, usado:int, limite:int}
 */
function fixa_scanner_verificar(int $usuarioId, array $empresa): array
{
    $ref = date('Y-m');
    try {
        $db = \App\Core\DB::pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM fixa_scanner_leituras WHERE usuario_id=? AND referencia_mes=?");
        $st->execute([$usuarioId, $ref]);
        $usado = (int) $st->fetchColumn();
    } catch (\Throwable $e) {
        return ['liberado' => true, 'mensagem' => null, 'usado' => 0, 'limite' => 0];
    }

    $viaPlanoEmpresa = !empty($empresa['reivindicada']) && in_array($empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true);
    if ($viaPlanoEmpresa) {
        $plano = plano_da_empresa($empresa);
        $limite = (int) ($plano['scan_fixa_conta_mes'] ?? 0);
    } else {
        $cfgFixa = require BASE_PATH . '/config/planos_fixa.php';
        $limite = (int) ($cfgFixa['scanner_leituras_mes'] ?? 100);
    }

    if ($limite <= 0) return ['liberado' => true, 'mensagem' => null, 'usado' => $usado, 'limite' => $limite];
    if ($usado < $limite) return ['liberado' => true, 'mensagem' => null, 'usado' => $usado, 'limite' => $limite];

    return [
        'liberado' => false,
        'mensagem' => "Você já usou as {$limite} leituras do scanner de contas deste mês — pode continuar lançando manualmente.",
        'usado'    => $usado,
        'limite'   => $limite,
    ];
}

function financeiro_pessoal_liberado(array $empresa): bool
{
    if (!empty($empresa['reivindicada']) && in_array($empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true)) {
        return true;
    }
    return !empty($empresa['_fixa_standalone_liberado']);
}

/**
 * URL pra exibir usuarios.avatar — esse campo guarda OU uma URL remota (foto do Google, só
 * nome do arquivo e ela já é só a URL completa) OU o nome de um arquivo local salvo em
 * storage/uploads/avatares/ (upload manual em Financeiro pessoal → Configurações, ver
 * FinanceiroPessoalController::salvarAvatar()) — os dois jeitos convivem no mesmo campo.
 * Retorna null quando não há avatar nenhum, pra quem chama decidir o fallback (iniciais etc.).
 */
function financeiro_pessoal_avatar_url(?string $avatar): ?string
{
    $avatar = trim((string) $avatar);
    if ($avatar === '') return null;
    if (preg_match('~^https?://~i', $avatar)) return $avatar;
    return url('/uploads/avatares/' . basename($avatar));
}

/**
 * Nome exibível pra uma `categoria` (chave) de lançamento que não bate com NENHUMA linha de
 * `financeiro_pessoal_categorias` do usuário — fallback de defesa, não deveria acontecer na
 * prática depois de `App\Services\Fixa\PerfilService::categoriasDoPerfil()` sempre completar os
 * 7 padrão (ver migration 078/correção de "reformule todas as categorias"), mas cobre o caso
 * residual de uma `categoria` órfã mesmo assim (categoria excluída por fora do fluxo normal,
 * import, etc.) — em vez de mostrar a chave crua ("alimentacao"), humaniza pra algo legível
 * ("Alimentacao"). Mesma lógica replicada em JS (lancamentos.php/index.php, cada view com sua
 * própria cópia — sem partial de script compartilhado entre views neste projeto).
 */
function financeiro_pessoal_categoria_humanizar(string $chave): string
{
    return ucwords(str_replace(['_', '-'], ' ', trim($chave)));
}

/** minúsculo, sem acento, só [a-z0-9 espaço] — chave estável pra "Enel Distribuição" e "ENEL
 *  DISTRIBUIÇÃO SP" caírem na mesma regra aprendida de categoria (ver migration 077). */
function financeiro_pessoal_normalizar_beneficiario(string $texto): string
{
    $t = remover_acentos(mb_strtolower(trim($texto), 'UTF-8'));
    $t = preg_replace('/[^a-z0-9 ]/', '', $t) ?? '';
    $t = preg_replace('/\s+/', ' ', trim($t)) ?? '';
    return mb_substr($t, 0, 80);
}

/** Categoria que o próprio usuário já corrigiu antes pra esse beneficiário, se houver —
 *  checada ANTES da sugestão da IA, pra "o usuário trocou uma vez" valer da próxima vez em
 *  diante (ver ScannerController::receberFotoFinanceira()/FinanceiroPessoalController::ocrConta()). */
function financeiro_pessoal_categoria_aprendida(int $usuarioId, string $beneficiario): ?string
{
    $chave = financeiro_pessoal_normalizar_beneficiario($beneficiario);
    if ($chave === '') return null;
    $st = \App\Core\DB::pdo()->prepare(
        "SELECT categoria FROM financeiro_pessoal_categoria_regras WHERE usuario_id = ? AND beneficiario_normalizado = ?"
    );
    $st->execute([$usuarioId, $chave]);
    $cat = $st->fetchColumn();
    return $cat ?: null;
}

/**
 * Fixa Fase 1 — status de um lançamento, SEMPRE calculado a partir de `pago_em`/`vencimento`/
 * hoje, nunca gravado numa coluna própria (pedido explícito: "Status é calculado, não
 * gravado"). Função pura (sem banco), espelhada em JS (financeiro_pessoal/lancamentos.php,
 * `statusLancamento()`) — mesmo princípio já usado noutros pontos do projeto (ex.:
 * slug_empresa_unico()/slugify() client-side), uma só fórmula, duas linguagens.
 *
 * - 'pago': tem `pago_em` (não importa o que `vencimento` diz — já foi resolvido).
 * - 'vencido': sem `pago_em`, com `vencimento` no passado.
 * - 'a_pagar'/'a_receber': sem `pago_em`, sem vencimento vencido (ou sem vencimento nenhum) —
 *   o rótulo muda conforme o `tipo` do lançamento (despesa/receita), nunca "vencido" sozinho
 *   deixa ambíguo se é uma conta a pagar ou uma entrada esperada.
 */
function fixa_status_lancamento(?string $pagoEm, ?string $vencimento, string $tipo, ?string $hoje = null): string
{
    $hoje = $hoje ?? date('Y-m-d');
    if (!empty($pagoEm)) return 'pago';
    if (!empty($vencimento) && $vencimento < $hoje) return 'vencido';
    return $tipo === 'receita' ? 'a_receber' : 'a_pagar';
}

/** Rótulo em português de um status calculado por fixa_status_lancamento(). */
function fixa_status_rotulo(string $status): string
{
    return [
        'pago'       => 'Pago',
        'vencido'    => 'Vencido',
        'a_pagar'    => 'A pagar',
        'a_receber'  => 'A receber',
    ][$status] ?? $status;
}

/**
 * Fixa Fase 1 — saldo ATUAL de uma conta/perfil: o que já entrou e saiu de verdade, sem contar
 * nada que ainda está em aberto. `$receitasPagas`/`$despesasPagas` somam só lançamentos com
 * `pago_em` preenchido (até hoje — não é possível ter `pago_em` no futuro, a UI não permite).
 */
function fixa_saldo_atual(float $saldoInicial, float $receitasPagas, float $despesasPagas): float
{
    return round($saldoInicial + $receitasPagas - $despesasPagas, 2);
}

/**
 * Fixa Fase 1 — saldo PREVISTO até o fim do mês navegado: o saldo atual somado ao que ainda
 * está em aberto dentro do mês (a receber soma, a pagar subtrai) — nunca inclui o que já virou
 * `fixa_saldo_atual()` (pago), senão contaria duas vezes o mesmo lançamento.
 */
function fixa_saldo_previsto(float $saldoAtual, float $aReceberAteFimDoMes, float $aPagarAteFimDoMes): float
{
    return round($saldoAtual + $aReceberAteFimDoMes - $aPagarAteFimDoMes, 2);
}

/**
 * Posições reais de banner do Diretório — cada uma é um lugar físico próprio na tela, não mais
 * um número arbitrário 1-5 que só limitava quantos anunciantes cabiam num único espaço sorteado.
 * Usado tanto pro formulário de plano do Master (lista de opções) quanto pra validar o valor
 * recebido no POST (whitelist — evita gravar um slug inventado).
 */
function diretorio_banner_posicoes(): array
{
    return [
        'busca_topo'    => 'Topo da busca (/assistencias)',
        'busca_lateral' => 'Barra lateral da busca e das páginas de cidade',
        'perfil'        => 'Perfil da empresa (barra lateral)',
        'cidade'        => 'Topo das páginas de cidade (/assistencias/{uf}/{cidade})',
    ];
}

/** Preço (centavos) de um plano num ciclo: preco_mensal × meses × (1 − desconto%). */
function plano_preco_ciclo(int $precoMensal, array $ciclo): int
{
    return (int) round($precoMensal * (int) $ciclo['meses'] * (1 - (int) $ciclo['desconto'] / 100));
}

/**
 * Vagas da promoção de lançamento de um plano (ex.: preço de fundador pros N primeiros
 * cadastros -- config `vagas_promo`). Conta empresas REAIS do FixaOS (ativo=1 AND
 * reivindicada=1 -- mesmo critério usado no dashboard master para "Empresas"), pagantes
 * ou não. Não conta perfis do Diretório de Assistências ainda não reivindicados.
 * Sem `vagas_promo` configurado: retorna sempre "não esgotado" (promoção não existe).
 */
function plano_vagas_info(array $plano): array
{
    $limite = (int) ($plano['vagas_promo'] ?? 0);
    if ($limite <= 0) return ['limite' => 0, 'usadas' => 0, 'restantes' => 0, 'esgotado' => false];
    try {
        $usadas = (int) \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM empresas WHERE ativo=1 AND reivindicada=1")->fetchColumn();
    } catch (\Throwable $e) {
        $usadas = 0;
    }
    $restantes = max(0, $limite - $usadas);
    return ['limite' => $limite, 'usadas' => $usadas, 'restantes' => $restantes, 'esgotado' => $restantes <= 0];
}

/**
 * Config do plano vigente da empresa p/ ENFORCEMENT de limites.
 * Retorna null = NÃO enforce (cobrança desligada → trava dormente). Sem plano pago ativo → 1º plano (Autônomo).
 */
function plano_efetivo(array $emp): ?array
{
    static $cob = null;
    if ($cob === null) { $c = require BASE_PATH . '/config/app.php'; $cob = !empty($c['cobranca_ativa']); }
    if (!$cob) return null;
    $cfg  = require BASE_PATH . '/config/planos.php';
    $cod  = $emp['plano_atual'] ?? null;
    $ativo = $cod && !empty($emp['licenca_ate']) && $emp['licenca_ate'] >= date('Y-m-d');
    if (!$ativo) {
        // Sem assinatura paga ainda: se o teste grátis (trial_ate) está valendo, usa um plano
        // generoso pra não capar quem ainda está conhecendo o sistema. Sem trial nem plano,
        // cai no Autônomo -- por código, não pela posição no array (planos.php pode reordenar
        // os planos pra exibição, ex.: Básico mais barato exibido primeiro, sem quebrar isso).
        $emTrial = !empty($emp['trial_ate']) && $emp['trial_ate'] >= date('Y-m-d');
        $cod = $emTrial ? 'oficina' : 'autonomo';
    }
    foreach ($cfg['planos'] as $p) if ($p['codigo'] === $cod) return $p;
    return $cfg['planos'][0];
}

/** Nº de OS abertas pela empresa no mês corrente. */
function os_uso_mes(int $empresaId): int
{
    try {
        $st = \App\Core\DB::pdo()->prepare("SELECT COUNT(*) FROM ordens_servico WHERE empresa_id=? AND criado_em >= DATE_FORMAT(CURDATE(),'%Y-%m-01')");
        $st->execute([$empresaId]);
        return (int) $st->fetchColumn();
    } catch (\Throwable $e) { return 0; }
}

/**
 * Plano real da empresa (pelo código em `plano_atual`) -- trava sempre, independente de
 * cobranca_ativa. Usado pra limites de consumo/teto (usuários, produtos, buscas de IA):
 * esses não devem "abrir" só porque o billing ainda não foi ligado no config.
 */
function plano_da_empresa(array $emp): array
{
    $cfg = require BASE_PATH . '/config/planos.php';
    $cod = $emp['plano_atual'] ?? null;
    foreach ($cfg['planos'] as $p) if ($p['codigo'] === $cod) return $p;
    // Sem plano_atual reconhecido: cai no Autônomo por código, não pela posição no array.
    foreach ($cfg['planos'] as $p) if ($p['codigo'] === 'autonomo') return $p;
    return $cfg['planos'][0];
}

/**
 * Um módulo inteiro (mesmo nome usado por Auth::moduloDoUri() — 'agenda', 'crm',
 * 'marketplace', 'pdv', 'marketing' etc.) pode ficar fora de um plano (hoje só o Básico, ver
 * config/planos.php, `modulos_bloqueados`) — eixo DIFERENTE da permissão por papel
 * (Auth::can()), checado em AuthMiddleware por cima dela. Fail-open em erro de leitura, mesmo
 * espírito best-effort já usado pelos outros checks de plano (scan_equip_habilitado etc.).
 */
function plano_permite_modulo(string $modulo, array $empresa): bool
{
    $bloqueados = plano_da_empresa($empresa)['modulos_bloqueados'] ?? [];
    return !in_array($modulo, $bloqueados, true);
}

/** Perguntas feitas ao Mentor pela empresa no mês corrente. */
function mentor_uso_mes(int $empresaId): int
{
    try {
        $st = \App\Core\DB::pdo()->prepare("SELECT COUNT(*) FROM mentor_perguntas WHERE empresa_id=? AND criado_em >= DATE_FORMAT(CURDATE(),'%Y-%m-01')");
        $st->execute([$empresaId]);
        return (int) $st->fetchColumn();
    } catch (\Throwable $e) { return 0; }
}

/** Buscas de IA (equipamento ou placa) usadas pela empresa no mes corrente. */
function scan_uso_mes(int $empresaId, string $modo): int
{
    try {
        $st = \App\Core\DB::pdo()->prepare(
            "SELECT COUNT(*) FROM scanner_sessoes WHERE empresa_id=? AND modo=? AND ia_usada=1 AND criado_em >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"
        );
        $st->execute([$empresaId, $modo]);
        return (int) $st->fetchColumn();
    } catch (\Throwable $e) { return 0; }
}

/**
 * Verifica se a empresa pode usar mais uma busca de IA (equipamento ou placa) este mes.
 * Dentro do limite: libera. Acima do limite com credito: consome 1 credito e libera.
 * Acima do limite sem credito: bloqueia e devolve mensagem explicativa pro cliente.
 * @return array{liberado:bool, usouCredito:bool, mensagem:?string}
 */
function scan_ia_verificar(int $empresaId, string $modo): array
{
    try {
        $db = \App\Core\DB::pdo();
        $st = $db->prepare("SELECT plano_atual, creditos_scan_equip, creditos_scan_placa FROM empresas WHERE id=?");
        $st->execute([$empresaId]);
        $emp = $st->fetch();
        if (!$emp) return ['liberado' => true, 'usouCredito' => false, 'mensagem' => null];

        $ehPlaca = $modo === 'placa';
        $plano   = plano_da_empresa($emp);

        // Feature DESLIGADA no plano (não é "sem limite", é "sem acesso") -- diferente de
        // scan_..._mes <= 0, que sempre significou ilimitado neste arquivo. Planos que não
        // declaram essa chave continuam com a feature ligada (default true).
        $chaveHabilitado = $ehPlaca ? 'scan_placa_habilitado' : 'scan_equip_habilitado';
        if (($plano[$chaveHabilitado] ?? true) === false) {
            $rotuloFeat = $ehPlaca ? 'a leitura de placa por IA' : 'o cadastro automático por foto (leitura de etiqueta)';
            return [
                'liberado'    => false,
                'usouCredito' => false,
                'mensagem'    => 'O plano ' . $plano['nome'] . ' não inclui ' . $rotuloFeat . ' -- preencha os dados manualmente, ou faça upgrade de plano em Planos e Assinatura.',
            ];
        }

        $limite  = (int) ($plano[$ehPlaca ? 'scan_placa_mes' : 'scan_equip_mes'] ?? 0);
        if ($limite <= 0) return ['liberado' => true, 'usouCredito' => false, 'mensagem' => null];

        $uso = scan_uso_mes($empresaId, $modo);
        if ($uso < $limite) return ['liberado' => true, 'usouCredito' => false, 'mensagem' => null];

        $credito = (int) ($ehPlaca ? $emp['creditos_scan_placa'] : $emp['creditos_scan_equip']);
        $rotulo  = $ehPlaca ? 'buscas de placa (marketplace)' : 'buscas de equipamento';

        if ($credito > 0) {
            if ($ehPlaca) {
                $db->prepare("UPDATE empresas SET creditos_scan_placa = creditos_scan_placa - 1 WHERE id=?")->execute([$empresaId]);
            } else {
                $db->prepare("UPDATE empresas SET creditos_scan_equip = creditos_scan_equip - 1 WHERE id=?")->execute([$empresaId]);
            }
            return ['liberado' => true, 'usouCredito' => true, 'mensagem' => null];
        }

        return [
            'liberado'    => false,
            'usouCredito' => false,
            'mensagem'    => 'Voce atingiu o limite de ' . $limite . ' ' . $rotulo . ' do plano ' . $plano['nome'] . ' este mes. '
                . 'Cada leitura por camera usa inteligencia artificial, que tem custo por uso -- por isso os planos tem um limite mensal. '
                . 'Voce pode preencher manualmente sem custo, ou comprar mais buscas avulsas em Planos e Assinatura.',
        ];
    } catch (\Throwable $e) {
        return ['liberado' => true, 'usouCredito' => false, 'mensagem' => null];
    }
}

/**
 * Verifica o limite de OS do mês APÓS abrir uma OS. Consome 1 crédito se estourar (soft, nunca bloqueia).
 * Retorna a mensagem de aviso p/ flash, ou null. Fail-open.
 */
function os_checar_limite(int $empresaId): ?string
{
    try {
        $st = \App\Core\DB::pdo()->prepare("SELECT plano_atual, licenca_ate, trial_ate, creditos_os, nome_fantasia, razao_social FROM empresas WHERE id=?");
        $st->execute([$empresaId]);
        $emp = $st->fetch();
        if (!$emp) return null;
        // Empresa fictícia de teste (scripts/seed_empresa_eletrocenter.php, "à vontade" pra
        // testar o sistema) — nunca deve ver aviso de limite de OS, mesma busca por nome
        // (não por id fixo) já usada pelo script que a criou/mantém.
        if (stripos((string) ($emp['nome_fantasia'] ?? ''), 'Eletrocenter') !== false
            || stripos((string) ($emp['razao_social'] ?? ''), 'Eletrocenter') !== false) {
            return null;
        }
        $plano = plano_efetivo($emp);
        if (!$plano) return null;
        $limite = (int) $plano['os_mes'];
        if ($limite <= 0) return null; // ilimitado
        $count = os_uso_mes($empresaId);
        if ($count > $limite) {
            $cred = (int) $emp['creditos_os'];
            if ($cred > 0) {
                \App\Core\DB::pdo()->prepare("UPDATE empresas SET creditos_os = GREATEST(creditos_os-1,0) WHERE id=?")->execute([$empresaId]);
                return 'Você usou 1 crédito de OS (saldo: ' . ($cred - 1) . ').';
            }
            return '⚠️ Você atingiu o limite de ' . $limite . ' OS do seu plano este mês. Compre um pacote de crédito para não parar.';
        }
        $rest = $limite - $count;
        if ($rest <= 5 && $rest >= 0) return 'Atenção: faltam ' . $rest . ' OS no seu plano este mês.';
        return null;
    } catch (\Throwable $e) { return null; }
}

/** Bloqueio de limite (produtos/usuários). Retorna mensagem se ATINGIU o teto, senão null. Fail-open. */
function limite_plano_atingido(int $empresaId, string $chaveLimite, int $usoAtual): ?string
{
    try {
        $st = \App\Core\DB::pdo()->prepare("SELECT plano_atual, licenca_ate, trial_ate FROM empresas WHERE id=?");
        $st->execute([$empresaId]);
        $emp = $st->fetch();
        if (!$emp) return null;
        $plano = plano_da_empresa($emp);
        $limite = (int) ($plano[$chaveLimite] ?? 0);
        if ($limite <= 0) return null; // ilimitado
        if ($usoAtual >= $limite) return 'Seu plano ' . $plano['nome'] . ' permite até ' . $limite . '. Faça upgrade para adicionar mais.';
        return null;
    } catch (\Throwable $e) { return null; }
}

/** Dias restantes do acesso ao diretório (max entre trial e licença). null = sem prazo. */
function licenca_dias_restantes(array $empresa): ?int
{
    $datas = array_filter([$empresa['trial_ate'] ?? null, $empresa['licenca_ate'] ?? null]);
    if (!$datas) return null;
    return (int) ceil((max(array_map('strtotime', $datas)) - strtotime(date('Y-m-d'))) / 86400);
}

/**
 * Registra uma ação do usuário no log de auditoria (tabela log_acoes). Best-effort:
 * nunca lança exceção — não pode quebrar a ação principal.
 * @param string   $modulo     ex.: 'os', 'cliente', 'financeiro', 'config'
 * @param string   $acao       ex.: 'excluir', 'fechar', 'criar', 'editar'
 * @param int|null $registroId id do registro afetado (ex.: id da OS)
 * @param string|null $detalhes texto livre (ex.: "OS 0501 — Cliente X — R$ 210,00")
 */
function log_acao(string $modulo, string $acao, ?int $registroId = null, ?string $detalhes = null): void
{
    try {
        $eid = \App\Core\Auth::empresaId();
        if (!$eid) return;
        $uid = $_SESSION['usuario_id'] ?? ($_SESSION['usuario']['id'] ?? null);
        \App\Core\DB::pdo()->prepare(
            "INSERT INTO log_acoes (empresa_id, usuario_id, modulo, acao, registro_id, detalhes, ip, user_agent)
             VALUES (?,?,?,?,?,?,?,?)"
        )->execute([
            (int) $eid,
            $uid ? (int) $uid : null,
            mb_substr($modulo, 0, 50),
            mb_substr($acao, 0, 100),
            $registroId,
            // coluna detalhes tem CHECK json_valid — grava como JSON {"texto": "..."}
            $detalhes !== null ? json_encode(['texto' => mb_substr($detalhes, 0, 5000)], JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (\Throwable $e) { /* nunca quebra a ação principal */ }
}

/**
 * Paginação condensada e reutilizável (« ‹ 1 … 5 6 [7] 8 9 … 44 › »).
 * $urlFor: callback fn(int $p): string que devolve a URL da página $p.
 * Usada por pagination() e pelas listas com URL própria (diretório, marketplace público).
 */
function paginacao_condensada(int $cur, int $last, callable $urlFor): string
{
    if ($last <= 1) return '';
    $cur = max(1, min($cur, $last));

    $item = function (string $label, int $p, bool $active = false, bool $disabled = false) use ($urlFor) {
        if ($disabled) return "<li class=\"page-item disabled\"><span class=\"page-link\">{$label}</span></li>";
        $cls  = $active ? ' active' : '';
        $href = htmlspecialchars($urlFor($p), ENT_QUOTES);
        return "<li class=\"page-item{$cls}\"><a class=\"page-link\" href=\"{$href}\">{$label}</a></li>";
    };

    $delta = 2;
    $paginas = [1, $last];
    for ($i = $cur - $delta; $i <= $cur + $delta; $i++) {
        if ($i >= 1 && $i <= $last) $paginas[] = $i;
    }
    $paginas = array_values(array_unique($paginas));
    sort($paginas);

    $html = '<nav aria-label="Paginação"><ul class="pagination pagination-sm mb-0 flex-wrap">';
    $html .= $item('«', 1,        false, $cur <= 1);
    $html .= $item('‹', $cur - 1, false, $cur <= 1);
    $prev = 0;
    foreach ($paginas as $p) {
        if ($prev && $p - $prev > 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= $item((string) $p, $p, $p === $cur);
        $prev = $p;
    }
    $html .= $item('›', $cur + 1, false, $cur >= $last);
    $html .= $item('»', $last,    false, $cur >= $last);
    return $html . '</ul></nav>';
}

/**
 * Paginação padrão via array $paginator + baseUrl. Preserva os filtros da URL atual (troca só "page").
 */
function pagination(array $paginator, string $baseUrl): string
{
    $last = (int) ($paginator['last_page'] ?? 1);
    $cur  = (int) ($paginator['current_page'] ?? 1);
    if ($last <= 1) return '';

    $qs   = $_GET ?? [];
    $base = $baseUrl;
    if (($pos = strpos($baseUrl, '?')) !== false) {
        parse_str(substr($baseUrl, $pos + 1), $bq);
        $qs   = array_merge($qs, $bq);
        $base = substr($baseUrl, 0, $pos);
    }
    return paginacao_condensada($cur, $last, function (int $p) use ($base, $qs) {
        $qs['page'] = $p;
        return $base . '?' . http_build_query($qs);
    });
}

/** Duração em horas de um evento de agenda — mesmas regras de fallback usadas pela barra de
 *  ocupação da visão Técnicos (_grade_tecnicos.php) e pelo painel "Hoje" (AgendaController):
 *  sem data_fim, assume 1h; data_fim <= data_inicio (dado inconsistente), assume 30min. Fonte
 *  única pra não recalcular diferente em cada lugar que soma ocupação de técnico. */
function agenda_evento_duracao_horas(array $ev): float
{
    $ini = strtotime((string) $ev['data_inicio']);
    $fim = !empty($ev['data_fim']) ? strtotime((string) $ev['data_fim']) : $ini + 3600;
    if ($fim <= $ini) $fim = $ini + 1800;
    return ($fim - $ini) / 3600;
}

function badge_status_os(?string $tipo, ?string $nome, ?string $cor = '', ?string $corFonte = '#ffffff'): string
{
    $tipo     = $tipo     ?? 'aberta';
    $nome     = $nome     ?? 'Sem status';
    $cor      = $cor      ?? '';
    $corFonte = $corFonte ?? '#ffffff';
    if ($cor) {
        return "<span class=\"badge\" style=\"background:{$cor};color:{$corFonte}\">" . e($nome) . "</span>";
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
    $partes = array_values(array_filter(explode(' ', trim($nome)), fn($p) => $p !== ''));
    if (!$partes) return 'U';

    $ini = mb_strtoupper(mb_substr($partes[0], 0, 1));

    // Primeiro sobrenome de verdade — pula conectivos (de, da, do, das, dos, e)
    $conectivos = ['de', 'da', 'do', 'das', 'dos', 'e'];
    for ($i = 1; $i < count($partes); $i++) {
        if (in_array(mb_strtolower($partes[$i]), $conectivos, true)) continue;
        $ini .= mb_strtoupper(mb_substr($partes[$i], 0, 1));
        break;
    }
    return $ini;
}

/** Fonte única das seções do Manual do Usuário (app/Views/ajuda/manual.php) — usada tanto pra
 *  montar a sidebar/nav quanto pela busca (BuscaController::buscar()). Antes eram duas listas
 *  mantidas à mão separadamente; a de busca ficava pra trás toda vez que uma seção nova era
 *  adicionada ao manual (aconteceu com o grupo inteiro de Agenda) e a busca simplesmente não
 *  encontrava esses artigos — daí só existir uma lista agora. */
function manual_secoes(): array
{
    return [
        'Primeiros Passos' => [
            ['inicio', 'Visão geral do sistema'],
            ['dashboard', 'Dashboard'],
            ['navegacao', 'Navegação e atalhos'],
            ['busca-global', 'Busca global'],
        ],
        'Ordens de Serviço' => [
            ['os-abrir', 'Abrir nova OS'],
            ['equip-scanner', 'Cadastro de equipamento por IA'],
            ['os-fotos-whatsapp', 'Fotos do estado de entrada'],
            ['os-status', 'Status e workflow'],
            ['os-servicos', 'Serviços e peças'],
            ['os-chat', 'Chat interno da equipe'],
            ['os-imprimir', 'Impressão e PDF'],
            ['os-fechar', 'Fechar OS'],
            ['os-adiantamento', 'Adiantamento (pagamento antecipado)'],
            ['os-garantia', 'Garantia e retorno'],
            ['os-reabrir', 'Reabrir OS'],
            ['os-offline', 'Modo offline'],
        ],
        'Clientes e CRM' => [
            ['clientes', 'Cadastro de clientes'],
            ['crm', 'Pipeline de vendas'],
        ],
        'Estoque' => [
            ['estoque-produtos', 'Cadastro de produtos'],
            ['estoque-mov', 'Movimentações'],
            ['estoque-servicos', 'Catálogo de serviços'],
        ],
        'Frente de Caixa' => [
            ['pdv', 'PDV — Vendas rápidas'],
        ],
        'Financeiro' => [
            ['fin-lancamentos', 'Lançamentos'],
            ['fin-fluxo', 'Fluxo de caixa'],
            ['fin-relatorios', 'Relatórios'],
            ['fin-comissoes', 'Comissão de técnico'],
        ],
        'Agenda' => [
            ['agenda', 'Visões e navegação'],
            ['agenda-eventos', 'Criar e editar eventos'],
            ['agenda-recorrencia', 'Repetir compromissos'],
            ['agenda-mover', 'Arrastar, redimensionar e teclado'],
            ['agenda-lembretes', 'Lembretes'],
            ['agenda-indicadores', 'Painel Hoje, indicadores e Próximos 7 dias'],
            ['agenda-financeiro', 'Gera lançamento no Financeiro'],
            ['agenda-tecnico', 'Enviar dados ao técnico + Atendimento rápido'],
        ],
        'Marketplace' => [
            ['mkt-anuncios', 'Criar anúncio'],
            ['mkt-creditos', 'Créditos'],
            ['mkt-vitrine', 'Vitrine e Marketplace Público'],
            ['mkt-pedidos', 'Pedidos de Peças'],
        ],
        'Fórum Técnico' => [
            ['forum-usar', 'Como usar o Fórum'],
        ],
        'Ferramentas' => [
            ['editor-imagens', 'Editor de Imagens'],
        ],
        'Configurações' => [
            ['cfg-empresa', 'Dados da empresa'],
            ['cfg-usuarios', 'Usuários'],
            ['cfg-tecnicos', 'Técnicos e % de comissão'],
            ['cfg-status', 'Status de OS'],
            ['cfg-ferramentas', 'Ligar/desligar funções'],
            ['cfg-limites', 'Limite de usuários e sessão'],
        ],
    ];
}

if (!function_exists('doc_mask')) {
    /** Formata CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) pelo nº de dígitos.
     *  Se não bater 11 nem 14 dígitos, devolve o valor original (não mascara). */
    function doc_mask(?string $doc): string
    {
        $n = preg_replace('/\D/', '', (string) $doc);
        if (strlen($n) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $n);
        }
        if (strlen($n) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $n);
        }
        return (string) $doc;
    }
}
