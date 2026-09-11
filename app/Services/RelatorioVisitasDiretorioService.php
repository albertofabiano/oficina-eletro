<?php

namespace App\Services;

use App\Core\DB;

/**
 * Relatório SEMANAL de visitas ao perfil do Diretório (contador só existe pra empresa
 * `reivindicada=1`, ver DiretorioController::empresa()) — pedido do usuário pra virar um
 * incentivo mais frequente de assinar/manter plano ou destaque, era mensal antes (ver CLAUDE.md
 * "Relatório mensal de visitas do Diretório" pra histórico da versão original). Disparado pra
 * quem de fato tem algo a defender ali: empresa `tipo_conta='completo'` (assina o sistema) OU
 * com destaque PAGO ativo (`diretorio_destaque_ate` não-nulo e não vencido — o mesmo critério já
 * usado pra decidir se o destaque aparece na busca; `_ate IS NULL` seria a assinatura do bug de
 * destaque grátis já removido, ver CLAUDE.md "Destaque do Diretório deixou de ser grátis").
 * Empresa `tipo_conta='diretorio'` sem destaque pago fica de fora — não paga nada e não usa o
 * sistema, mandar métrica pra ela não converte nem retém.
 *
 * Dedup via empresas_email_log (tabela genérica) com uma campanha por SEMANA referenciada
 * (`relatorio_visitas_20260928`, a data de início do período), então rodar o cron de novo
 * dentro da mesma semana não duplica envio.
 */
class RelatorioVisitasDiretorioService
{
    private const CAMPANHA_PREFIXO = 'relatorio_visitas_';

    /**
     * Período do relatório: os últimos 7 dias antes de hoje (janela corrida, não semana de
     * calendário — não depende de o cron rodar num dia da semana específico). `label` no
     * formato "dd/mm a dd/mm", já pronto pra entrar na frase do e-mail/assunto.
     */
    public static function periodoAnterior(): array
    {
        $fim    = date('Y-m-d'); // exclusivo — hoje
        $inicio = date('Y-m-d', strtotime('-7 days'));
        $inicioLabel = date('d/m', strtotime($inicio));
        $fimLabel    = date('d/m', strtotime('-1 day'));

        return [
            'inicio'    => $inicio,
            'fim'       => $fim,
            'label'     => "{$inicioLabel} a {$fimLabel}",
            'campanha'  => self::CAMPANHA_PREFIXO . date('Ymd', strtotime($inicio)),
        ];
    }

    private static function baseSql(): string
    {
        return "FROM empresas e
                WHERE e.ativo = 1
                  AND e.reivindicada = 1
                  AND e.email IS NOT NULL AND e.email <> ''
                  AND (
                        e.tipo_conta = 'completo'
                        OR (e.diretorio_destaque <> 'none' AND e.diretorio_destaque_ate IS NOT NULL AND e.diretorio_destaque_ate >= CURDATE())
                      )
                  AND e.id NOT IN (SELECT empresa_id FROM empresas_email_log WHERE campanha = ?)";
    }

    public static function contarElegiveis(): int
    {
        $periodo = self::periodoAnterior();
        $stmt = DB::pdo()->prepare("SELECT COUNT(*) " . self::baseSql());
        $stmt->execute([$periodo['campanha']]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int,array{id:int,email:string,nome_contato:string,nome_empresa:string,visitas_mes:int,visitas_total:int}> */
    public static function elegiveis(int $limite = 0): array
    {
        $periodo = self::periodoAnterior();

        $sql =
            "SELECT e.id, e.email,
                    COALESCE(
                      (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
                      (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
                      e.razao_social, e.nome_fantasia
                    ) AS nome_contato,
                    COALESCE(NULLIF(e.nome_fantasia, ''), e.razao_social) AS nome_empresa,
                    (SELECT COALESCE(SUM(dv.total), 0) FROM diretorio_visitas dv
                      WHERE dv.empresa_id = e.id AND dv.dia >= ? AND dv.dia < ?) AS visitas_mes,
                    COALESCE(e.visitas, 0) AS visitas_total "
            . self::baseSql() . " ORDER BY e.id";
        if ($limite > 0) $sql .= " LIMIT " . (int) $limite;

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$periodo['inicio'], $periodo['fim'], $periodo['campanha']]);
        return $stmt->fetchAll();
    }

    /** Envia pra até $limite elegíveis (0 = todos). @return array{total:int,enviados:int,falhas:int,periodo:string} */
    public static function dispararTodos(int $limite = 0): array
    {
        $db       = DB::pdo();
        $periodo  = self::periodoAnterior();
        $empresas = self::elegiveis($limite);
        $enviados = 0;
        $falhas   = 0;

        foreach ($empresas as $e) {
            $ok = EmailService::relatorioVisitasDiretorio(
                (string) $e['email'],
                (string) $e['nome_contato'],
                (string) $e['nome_empresa'],
                (int) $e['visitas_mes'],
                $periodo['label'],
                (int) $e['visitas_total']
            );
            if ($ok) {
                $db->prepare("INSERT IGNORE INTO empresas_email_log (empresa_id, campanha) VALUES (?, ?)")
                   ->execute([$e['id'], $periodo['campanha']]);
                $enviados++;
            } else {
                $falhas++;
            }
        }

        return ['total' => count($empresas), 'enviados' => $enviados, 'falhas' => $falhas, 'periodo' => $periodo['label']];
    }
}
