<?php

namespace App\Services;

use App\Core\DB;

/**
 * Disparo de EmailService::avisoAvaliacaoGoogle() pra base de clientes já cadastrados —
 * mesmo público de NovidadesSistemaService (reivindicada=1, qualquer tipo_conta: cobre quem usa
 * o sistema completo pagando, quem está em trial testando, e quem só reivindicou o Diretório),
 * mas com campanha PRÓPRIA (não reaproveita NovidadesSistemaService::CAMPANHA) — é um aviso
 * pontual de UMA funcionalidade específica, não o pacote de novidades em lote; as duas campanhas
 * não se somam nem se pisam em `empresas_email_log`.
 */
class AvisoAvaliacaoGoogleService
{
    public const CAMPANHA = 'aviso_avaliacao_google_2026_09';

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
            $ok = EmailService::avisoAvaliacaoGoogle((string) $e['email'], (string) $e['nome_contato']);
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

    // ───────────────────────── Mesmo aviso, canal WhatsApp ─────────────────────────
    // Mesmo público (reivindicada=1), mas só quem tem telefone de algum usuário cadastrado —
    // dedup própria em `empresas_whatsapp_log` (campanha igual à do e-mail: é o mesmo aviso,
    // só o canal muda; os dois nunca competem entre si porque cada um lê a própria tabela).

    private static function baseSqlWhatsapp(): string
    {
        return "FROM empresas e
                WHERE e.ativo = 1
                  AND e.reivindicada = 1
                  AND e.id NOT IN (SELECT empresa_id FROM empresas_whatsapp_log WHERE campanha = ?)";
    }

    private static function telefoneContatoSql(): string
    {
        return "COALESCE(
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1),
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1)
                )";
    }

    public static function contarElegiveisWhatsapp(): int
    {
        $sql = "SELECT COUNT(*) FROM (
                    SELECT e.id, " . self::telefoneContatoSql() . " AS telefone
                    " . self::baseSqlWhatsapp() . "
                ) t WHERE t.telefone IS NOT NULL";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        return (int) $stmt->fetchColumn();
    }

    public static function contarJaEnviadosWhatsapp(): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(*) FROM empresas_whatsapp_log WHERE campanha = ?");
        $stmt->execute([self::CAMPANHA]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int,array{id:int,telefone:string,nome_contato:string}> */
    public static function elegiveisWhatsapp(int $limite = 0): array
    {
        // Subconsulta (não HAVING sem GROUP BY, que o SQLite usado nos testes rejeita e o
        // MySQL só tolera por acidente) — filtra por `telefone` só depois de resolvido.
        $sql =
            "SELECT t.id, t.nome_contato, t.telefone FROM (
                SELECT e.id,
                       COALESCE(
                         (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
                         (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
                         e.razao_social, e.nome_fantasia
                       ) AS nome_contato,
                       " . self::telefoneContatoSql() . " AS telefone
                " . self::baseSqlWhatsapp() . "
            ) t WHERE t.telefone IS NOT NULL ORDER BY t.id";
        if ($limite > 0) $sql .= " LIMIT " . (int) $limite;

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        return $stmt->fetchAll();
    }

    /** Envia pra até $limite elegíveis (0 = todos). @return array{total:int,enviados:int,falhas:int} */
    public static function dispararTodosWhatsapp(int $limite = 0): array
    {
        $db       = DB::pdo();
        $empresas = self::elegiveisWhatsapp($limite);
        $enviados = 0;
        $falhas   = 0;

        foreach ($empresas as $e) {
            $ok = \App\Services\WhatsAppService::avisoAvaliacaoGoogle((string) $e['telefone'], (string) $e['nome_contato']);
            if ($ok) {
                $db->prepare("INSERT IGNORE INTO empresas_whatsapp_log (empresa_id, campanha) VALUES (?, ?)")
                   ->execute([$e['id'], self::CAMPANHA]);
                $enviados++;
            } else {
                $falhas++;
            }
        }

        return ['total' => count($empresas), 'enviados' => $enviados, 'falhas' => $falhas];
    }
}
