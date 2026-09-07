<?php

namespace App\Services;

use App\Core\DB;

/**
 * Disparo de EmailService::novidadesSistema() pra base de clientes já cadastrados
 * (reivindicada=1, qualquer tipo_conta) — não é prospecção (público não é lead frio sem conta,
 * por isso não fica em App\Services\Prospeccao), e não tem rampa de volume — diferente de
 * e-mail frio pra desconhecido, aqui é aviso pra quem já confia na marca, sem risco de spam.
 * Dedup via empresas_email_log (tabela genérica, reaproveitável por campanhas futuras).
 */
class NovidadesSistemaService
{
    public const CAMPANHA = 'novidades_2026_09';

    private static function baseSql(): string
    {
        return "FROM empresas e
                WHERE e.ativo = 1
                  AND e.reivindicada = 1
                  AND e.email IS NOT NULL AND e.email <> ''
                  AND e.id NOT IN (SELECT empresa_id FROM empresas_email_log WHERE campanha = ?)";
    }

    public static function contarElegiveis(): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(*) " . self::baseSql());
        $stmt->execute([self::CAMPANHA]);
        return (int) $stmt->fetchColumn();
    }

    public static function contarJaEnviados(): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(*) FROM empresas_email_log WHERE campanha = ?");
        $stmt->execute([self::CAMPANHA]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int,array{id:int,email:string,nome_contato:string}> */
    public static function elegiveis(int $limite = 0): array
    {
        $sql =
            "SELECT e.id, e.email,
                    COALESCE(
                      (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
                      (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
                      e.razao_social, e.nome_fantasia
                    ) AS nome_contato "
            . self::baseSql() . " ORDER BY e.id";
        if ($limite > 0) $sql .= " LIMIT " . (int) $limite;

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        return $stmt->fetchAll();
    }

    /** Envia pra até $limite elegíveis (0 = todos). @return array{total:int,enviados:int,falhas:int} */
    public static function dispararTodos(int $limite = 0): array
    {
        $db       = DB::pdo();
        $empresas = self::elegiveis($limite);
        $enviados = 0;
        $falhas   = 0;

        foreach ($empresas as $e) {
            $ok = EmailService::novidadesSistema((string) $e['email'], (string) $e['nome_contato']);
            if ($ok) {
                $db->prepare("INSERT IGNORE INTO empresas_email_log (empresa_id, campanha) VALUES (?, ?)")
                   ->execute([$e['id'], self::CAMPANHA]);
                $enviados++;
            } else {
                $falhas++;
            }
        }

        return ['total' => count($empresas), 'enviados' => $enviados, 'falhas' => $falhas];
    }
}
