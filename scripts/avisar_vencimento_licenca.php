<?php
// Plano COMPLETO (assistência técnica) — aviso de vencimento de trial_ate/licenca_ate, 3 dias
// antes e no dia do vencimento, com link de pagamento JÁ GERADO (não um link genérico pro
// painel — PagamentoController::gerarLinkAssinatura(), extraído de assinar() pra isso) +
// e-mail (EmailService::avisoVencimentoLicenca()) + notificação in-app
// (NotificacaoService::criar(), mesmo sino da topbar). WhatsApp fica preparado mas desligado
// até `config/app.php['aviso_vencimento_whatsapp']` virar true.
//
// Dedup: `empresa_avisos_vencimento` (UNIQUE empresa_id+tipo+data_vencimento) — nunca reenvia
// o mesmo aviso pra MESMA data de vencimento; se a empresa renovar e a data mudar, os avisos
// da data nova voltam a ser elegíveis sozinhos. Seguro rodar todo dia.
//
// Rodar via cron real, 1x/dia, ex.:
//   0 9 * * * php /var/www/fixaos/scripts/avisar_vencimento_licenca.php >> /var/www/fixaos/storage/logs/aviso_vencimento_licenca_cron.log 2>&1

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Controllers\PagamentoController;
use App\Services\EmailService;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
$whatsappLigado = !empty($appConfig['aviso_vencimento_whatsapp']);

$db = DB::pdo();

/**
 * Busca empresas cujo vencimento efetivo (maior entre trial_ate e licenca_ate) cai exatamente
 * em $dataAlvo — só contas 'completo' com reivindicada=1 (mesmo critério usado em todo lugar
 * que trata vencimento de licença; conta 'diretorio'/'fixa' seguem suas próprias réguas, não
 * essa). Vencimento efetivo calculado em SQL (GREATEST, ignorando NULL) pra não trazer empresa
 * nenhuma de fora por engano.
 */
function buscarEmpresasNoVencimento(\PDO $db, string $dataAlvo): array
{
    $stmt = $db->prepare(
        "SELECT e.id, e.email, e.plano_atual,
                GREATEST(COALESCE(e.trial_ate, '1970-01-01'), COALESCE(e.licenca_ate, '1970-01-01')) AS vencimento_efetivo,
                COALESCE(
                  (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
                  (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
                  e.razao_social, e.nome_fantasia
                ) AS nome_contato,
                COALESCE(
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1),
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1)
                ) AS telefone_contato
         FROM empresas e
         WHERE e.ativo = 1 AND e.reivindicada = 1 AND e.tipo_conta = 'completo'
           AND e.email IS NOT NULL AND e.email <> ''
         HAVING vencimento_efetivo = ?"
    );
    $stmt->execute([$dataAlvo]);
    return $stmt->fetchAll();
}

$hoje = date('Y-m-d');
$em3Dias = date('Y-m-d', strtotime('+3 days'));

$candidatos = [
    '3_dias_antes' => buscarEmpresasNoVencimento($db, $em3Dias),
    'vencimento'   => buscarEmpresasNoVencimento($db, $hoje),
];

$enviados = 0;
$total = 0;

foreach ($candidatos as $tipo => $empresas) {
    foreach ($empresas as $emp) {
        $total++;
        $empresaId = (int) $emp['id'];
        $dataVenc  = (string) $emp['vencimento_efetivo'];

        $jaEnviado = $db->prepare(
            "SELECT 1 FROM empresa_avisos_vencimento WHERE empresa_id = ? AND tipo = ? AND data_vencimento = ?"
        );
        $jaEnviado->execute([$empresaId, $tipo, $dataVenc]);
        if ($jaEnviado->fetchColumn()) continue;

        $plano = $emp['plano_atual'] ?: 'autonomo';
        $link  = PagamentoController::gerarLinkAssinatura($db, $empresaId, $plano, 'mensal');
        if (!$link) continue; // InfinitePay fora do ar ou plano inválido — tenta de novo no próximo dia, sem registrar envio

        $dataFormatada = date('d/m/Y', strtotime($dataVenc));
        $jaVenceu = $tipo === 'vencimento';

        $ok = EmailService::avisoVencimentoLicenca(
            (string) $emp['email'], (string) $emp['nome_contato'], $jaVenceu, $dataFormatada, $link
        );
        if (!$ok) continue;

        NotificacaoService::criar(
            $empresaId,
            'aviso_vencimento_licenca',
            $jaVenceu ? 'Sua assinatura venceu' : 'Sua assinatura vence em 3 dias',
            $jaVenceu
                ? "Venceu em {$dataFormatada}. Pague agora para não perder o acesso."
                : "Vence em {$dataFormatada}. Pague antes para continuar sem interrupção.",
            '/planos',
            'bi-exclamation-triangle-fill',
            'warning'
        );

        if ($whatsappLigado && !empty($emp['telefone_contato'])) {
            $texto = $jaVenceu
                ? "Sua assinatura do FixaOS venceu em {$dataFormatada}. Pague agora para não perder o acesso: {$link}"
                : "Sua assinatura do FixaOS vence em {$dataFormatada}. Pague antes para continuar sem interrupção: {$link}";
            WhatsAppService::enviarTextoPlataforma((string) $emp['telefone_contato'], $texto);
        }

        $db->prepare("INSERT INTO empresa_avisos_vencimento (empresa_id, tipo, data_vencimento) VALUES (?, ?, ?)")
            ->execute([$empresaId, $tipo, $dataVenc]);
        $enviados++;
    }
}

printf("[%s] candidatos=%d enviados=%d whatsapp=%s\n", date('Y-m-d H:i:s'), $total, $enviados, $whatsappLigado ? 'ligado' : 'desligado');
