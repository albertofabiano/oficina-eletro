<?php
// Carteira Fixa standalone — aviso de vencimento, cobrindo os DOIS casos possíveis ao longo da
// vida de uma assinatura: (1) o teste de 7 dias acabando (aviso único, 2 dias antes, com valor/
// data exatos da 1ª cobrança + link de cancelamento em 1 clique) e (2) um CICLO JÁ PAGO vencendo
// (repete a cada renovação — mensal vence todo mês, anual uma vez por ano), mesma cadência do
// aviso do plano completo (scripts/avisar_vencimento_licenca.php): 3 dias antes + no dia, com um
// link de pagamento JÁ GERADO (FixaCadastroController::gerarLinkPagamento()), não um link
// genérico pro painel.
//
// E-mail (EmailService::avisoTesteFixaTerminando()/avisoCicloFixaVencendo()) + notificação
// in-app (NotificacaoService::criar(), aparece no mesmo sino da topbar — todo usuário tem
// empresa_id, mesmo o Fixa standalone, que cria uma empresa "casca" por baixo — ver CLAUDE.md
// "Cadastro próprio e simples").
//
// Dedup: fixa_assinatura_avisos (UNIQUE assinatura_id+tipo+referencia, migration 095) — a
// REFERÊNCIA é a data do vencimento que gerou aquele aviso, então renovar e vencer de novo
// reabre elegibilidade sozinho, sem apagar nada manualmente; rodar o cron mais de uma vez no
// mesmo dia nunca duplica.
//
// Rodar via cron real, 1x/dia, ex.:
//   0 9 * * * php /var/www/fixaos/scripts/avisar_teste_fixa_terminando.php >> /var/www/fixaos/storage/logs/fixa_aviso_teste_cron.log 2>&1

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\EmailService;
use App\Services\NotificacaoService;
use App\Services\Fixa\AssinaturaService;
use App\Controllers\FixaCadastroController;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
$baseUrl = rtrim($appConfig['url'], '/');

$db = DB::pdo();

$cfgFixa = AssinaturaService::config();
$nomePlano = function (string $codigo) use ($cfgFixa): string {
    foreach ($cfgFixa['planos'] as $p) if ($p['codigo'] === $codigo) return $p['nome'];
    return $codigo;
};

/** Dedup real: INSERT na UNIQUE(assinatura_id, tipo, referencia) — se já existe, não insere e
 *  devolve false (não manda o aviso de novo). */
$jaAvisado = function (\PDO $db, int $assinaturaId, string $tipo, string $referencia): bool {
    $st = $db->prepare("SELECT 1 FROM fixa_assinatura_avisos WHERE assinatura_id = ? AND tipo = ? AND referencia = ?");
    $st->execute([$assinaturaId, $tipo, $referencia]);
    return (bool) $st->fetchColumn();
};
$registrarAviso = function (\PDO $db, int $assinaturaId, string $tipo, string $referencia): void {
    $db->prepare("INSERT INTO fixa_assinatura_avisos (assinatura_id, tipo, referencia) VALUES (?, ?, ?)")
        ->execute([$assinaturaId, $tipo, $referencia]);
};

$enviados = 0;
$candidatasTotal = 0;

// --- Caso 1: teste de 7 dias acabando (aviso único "2 dias antes", valor/data da 1ª cobrança) ---
$stmt = $db->prepare(
    "SELECT a.*, u.nome AS usuario_nome, u.email AS usuario_email, u.empresa_id AS usuario_empresa_id
     FROM fixa_assinaturas a
     JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.status = 'teste'
       AND a.teste_fim BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
       AND a.cancelar_token IS NOT NULL"
);
$stmt->execute();
$candidatasTeste = $stmt->fetchAll();
$candidatasTotal += count($candidatasTeste);

foreach ($candidatasTeste as $a) {
    if (empty($a['usuario_email'])) continue;
    $referencia = date('Y-m-d', strtotime($a['teste_fim']));
    if ($jaAvisado($db, (int) $a['id'], 'teste_terminando', $referencia)) continue;

    $dataCobranca = date('d/m/Y', strtotime($a['teste_fim'] . ' +1 day'));
    $valorFormatado = money(((int) $a['valor_centavos']) / 100);
    $cancelarUrl = $baseUrl . '/fixa/cancelar-teste/' . $a['cancelar_token'];

    $ok = EmailService::avisoTesteFixaTerminando(
        $a['usuario_email'], (string) $a['usuario_nome'], $nomePlano($a['plano']), $dataCobranca, $valorFormatado, $cancelarUrl
    );

    if ($ok) {
        NotificacaoService::criar(
            (int) $a['usuario_empresa_id'],
            'fixa_teste_terminando',
            'Seu teste do Carteira Fixa termina em 2 dias',
            "No dia {$dataCobranca} você será cobrado {$valorFormatado}. Cancele em 1 clique no e-mail que acabamos de enviar, se preferir.",
            '/financeiro-pessoal',
            'bi-clock-history',
            'warning',
            (int) $a['usuario_id']
        );
        $registrarAviso($db, (int) $a['id'], 'teste_terminando', $referencia);
        $enviados++;
    }
}

// --- Caso 2: ciclo JÁ PAGO vencendo — 3 dias antes + no dia (mesma cadência do plano completo) ---
// `status='ativa'` é o único valor que a linha tem gravado de verdade enquanto o ciclo ainda
// não venceu — 'inadimplente'/'bloqueada' são computados por AssinaturaService::statusEfetivo(),
// NUNCA gravados na coluna (ver confirmarPagamento()/cancelarAssinatura() — só escrevem 'ativa'
// ou 'cancelada'), então não há necessidade de incluir esses valores no filtro de SQL.
$stmt = $db->prepare(
    "SELECT a.*, u.nome AS usuario_nome, u.email AS usuario_email, u.empresa_id AS usuario_empresa_id
     FROM fixa_assinaturas a
     JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.status = 'ativa'
       AND a.data_fim IS NOT NULL
       AND a.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
);
$stmt->execute();
$candidatasCiclo = $stmt->fetchAll();
$candidatasTotal += count($candidatasCiclo);

foreach ($candidatasCiclo as $a) {
    if (empty($a['usuario_email'])) continue;

    foreach (['3_dias_antes', 'vencimento'] as $tipo) {
        // Re-confere com a MESMA regra de AssinaturaService (não confia só no filtro de SQL —
        // mesmo princípio de nunca duplicar a lógica de decisão em dois lugares que podem
        // divergir).
        if (!AssinaturaService::precisaAviso($a, $tipo)) continue;

        $referencia = AssinaturaService::vencimentoParaAviso($a);
        if ($referencia === null || $jaAvisado($db, (int) $a['id'], $tipo, $referencia)) continue;

        $jaVenceu = $tipo === 'vencimento';
        $dataFmt = date('d/m/Y', strtotime($referencia));
        $link = FixaCadastroController::gerarLinkPagamento($db, (int) $a['usuario_empresa_id'], $a, (string) $a['ciclo']);
        if (!$link) continue; // sem pagamento ativo/plano inválido — não manda aviso sem link real

        $ok = EmailService::avisoCicloFixaVencendo(
            $a['usuario_email'], (string) $a['usuario_nome'], $nomePlano($a['plano']), $jaVenceu, $dataFmt, $link
        );

        if ($ok) {
            NotificacaoService::criar(
                (int) $a['usuario_empresa_id'],
                'fixa_ciclo_vencendo',
                $jaVenceu ? 'Sua assinatura do Carteira Fixa venceu' : 'Sua assinatura do Carteira Fixa vai vencer',
                $jaVenceu
                    ? "Venceu em {$dataFmt}. Pague pra continuar lançando normalmente."
                    : "Vence em {$dataFmt}. Pague antes dessa data pra continuar sem interrupção.",
                '/carteira-fixa/forma-pagamento',
                'bi-clock-history',
                'warning',
                (int) $a['usuario_id']
            );
            $registrarAviso($db, (int) $a['id'], $tipo, $referencia);
            $enviados++;
        }
    }
}

printf("[%s] candidatas=%d enviados=%d\n", date('Y-m-d H:i:s'), $candidatasTotal, $enviados);
