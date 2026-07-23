<?php

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
    return $_SESSION['_old'][$key] ?? $default;
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
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
 * Trava específica de edição do perfil do diretório p/ contas tipo_conta='diretorio',
 * independente do switch global cobranca_ativa. Clientes completos do FixaOS nunca travam.
 */
function perfil_diretorio_editavel(array $empresa): bool
{
    if (($empresa['tipo_conta'] ?? 'completo') === 'completo') return true;
    $hoje = date('Y-m-d');
    if (!empty($empresa['trial_ate'])   && $empresa['trial_ate']   >= $hoje) return true;
    if (!empty($empresa['licenca_ate']) && $empresa['licenca_ate'] >= $hoje) return true;
    return false;
}

/** Preço (centavos) de um plano num ciclo: preco_mensal × meses × (1 − desconto%). */
function plano_preco_ciclo(int $precoMensal, array $ciclo): int
{
    return (int) round($precoMensal * (int) $ciclo['meses'] * (1 - (int) $ciclo['desconto'] / 100));
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
    if (!$ativo) $cod = $cfg['planos'][0]['codigo'];
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

/** Plano da empresa p/ limite de buscas de IA -- trava sempre, independente de cobranca_ativa. */
function plano_para_limite_ia(array $emp): array
{
    $cfg = require BASE_PATH . '/config/planos.php';
    $cod = $emp['plano_atual'] ?? null;
    foreach ($cfg['planos'] as $p) if ($p['codigo'] === $cod) return $p;
    return $cfg['planos'][0];
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
        $plano   = plano_para_limite_ia($emp);
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
        $st = \App\Core\DB::pdo()->prepare("SELECT plano_atual, licenca_ate, creditos_os FROM empresas WHERE id=?");
        $st->execute([$empresaId]);
        $emp = $st->fetch();
        if (!$emp) return null;
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
        $st = \App\Core\DB::pdo()->prepare("SELECT plano_atual, licenca_ate FROM empresas WHERE id=?");
        $st->execute([$empresaId]);
        $emp = $st->fetch();
        if (!$emp) return null;
        $plano = plano_efetivo($emp);
        if (!$plano) return null;
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
