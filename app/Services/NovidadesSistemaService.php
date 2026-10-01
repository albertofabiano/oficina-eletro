<?php

namespace App\Services;

use App\Core\DB;

/**
 * Disparo de "novidades do sistema" (e-mail + WhatsApp) pra base de clientes já cadastrados
 * (reivindicada=1, qualquer tipo_conta) — não é prospecção (público não é lead frio sem conta,
 * por isso não fica em App\Services\Prospeccao), e não tem rampa de volume — diferente de
 * e-mail frio pra desconhecido, aqui é aviso pra quem já confia na marca, sem risco de spam.
 * Dedup via `empresas_email_log`/`empresas_whatsapp_log` (tabelas genéricas, reaproveitáveis
 * por qualquer rodada futura).
 *
 * Deliberadamente UMA classe só, não uma por aviso — um aviso pontual (ex.: "peça avaliação no
 * Google") não merece uma classe/tela/dedup própria; ele É a rodada atual de "novidades do
 * sistema". Pra anunciar outra coisa no futuro: troque o conteúdo de
 * EmailService::novidadesSistema()/WhatsAppService::novidadesSistema() e mude a CAMPANHA abaixo
 * (uma data nova reabre a elegibilidade de todo mundo pra essa rodada, mesmo quem já recebeu a
 * rodada anterior — histórico de quem recebeu CADA rodada fica preservado nas duas tabelas de
 * log, só filtrado por campanha).
 */
class NovidadesSistemaService
{
    public const CAMPANHA = 'novidades_2026_10_avaliacao_google';

    // ───────────────────────── Base comum ─────────────────────────

    /** Quantas empresas "de verdade" existem na base (ativa + reivindicada) — denominador de
     *  referência mostrado no painel, não entra em nenhum WHERE de elegibilidade. */
    public static function contarEmpresasBase(): int
    {
        $stmt = DB::pdo()->query("SELECT COUNT(*) FROM empresas WHERE ativo = 1 AND reivindicada = 1");
        return (int) $stmt->fetchColumn();
    }

    private static function nomeContatoSql(): string
    {
        return "COALESCE(
                  (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
                  (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
                  e.razao_social, e.nome_fantasia
                )";
    }

    private static function telefoneContatoSql(): string
    {
        return "COALESCE(
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1),
                  (SELECT u.telefone FROM usuarios u WHERE u.empresa_id = e.id AND u.telefone IS NOT NULL AND u.telefone <> '' ORDER BY u.id LIMIT 1)
                )";
    }

    /** Elegível em PELO MENOS UM dos dois canais (falta receber por e-mail OU por WhatsApp) —
     *  usado só pro badge da sidebar/KPI de resumo, não entra em nenhum disparo. */
    public static function contarElegiveisUniao(): int
    {
        $sql = "SELECT COUNT(*) FROM (
                    SELECT e.id, e.email AS email, " . self::telefoneContatoSql() . " AS telefone,
                           (le.id IS NOT NULL) AS enviado_email,
                           (lw.id IS NOT NULL) AS enviado_whatsapp
                    FROM empresas e
                    LEFT JOIN empresas_email_log le ON le.empresa_id = e.id AND le.campanha = ?
                    LEFT JOIN empresas_whatsapp_log lw ON lw.empresa_id = e.id AND lw.campanha = ?
                    WHERE e.ativo = 1 AND e.reivindicada = 1
                ) t
                WHERE (t.email IS NOT NULL AND t.email <> '' AND t.enviado_email = 0)
                   OR (t.telefone IS NOT NULL AND t.enviado_whatsapp = 0)";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA, self::CAMPANHA]);
        return (int) $stmt->fetchColumn();
    }

    // ───────────────────────── Canal: E-mail ─────────────────────────

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
        $sql = "SELECT e.id, e.email, " . self::nomeContatoSql() . " AS nome_contato "
            . self::baseSql() . " ORDER BY e.id";
        if ($limite > 0) $sql .= " LIMIT " . (int) $limite;

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        return $stmt->fetchAll();
    }

    /** Lista COMPLETA (toda empresa ativa+reivindicada, com e-mail ou não) com o status de envio
     *  desta campanha — pro painel mostrar os 65 contatos e deixar o Master selecionar manualmente
     *  quem recebe, em vez de só confiar na elegibilidade automática.
     *  @return array<int,array{id:int,nome_contato:string,email:?string,enviado:bool}> */
    public static function listaEmail(): array
    {
        $sql = "SELECT e.id, " . self::nomeContatoSql() . " AS nome_contato, e.email,
                       (le.id IS NOT NULL) AS enviado
                FROM empresas e
                LEFT JOIN empresas_email_log le ON le.empresa_id = e.id AND le.campanha = ?
                WHERE e.ativo = 1 AND e.reivindicada = 1
                ORDER BY e.id";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        $linhas = $stmt->fetchAll();
        foreach ($linhas as &$l) { $l['enviado'] = (bool) $l['enviado']; }
        return $linhas;
    }

    /** Envia pra até $limite elegíveis (0 = todos). @return array{total:int,enviados:int,falhas:int} */
    public static function dispararTodos(int $limite = 0): array
    {
        return self::enviarEmailParaLista(self::elegiveis($limite));
    }

    /** Envia só pros ids selecionados manualmente (ignora quem não tem e-mail ou já recebeu,
     *  mesmo que o id venha marcado — dedup nunca é pulado por seleção manual).
     *  @param int[] $ids @return array{total:int,enviados:int,falhas:int} */
    public static function dispararEmailSelecionados(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) return ['total' => 0, 'enviados' => 0, 'falhas' => 0];

        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT e.id, e.email, " . self::nomeContatoSql() . " AS nome_contato
                FROM empresas e
                WHERE e.ativo = 1 AND e.reivindicada = 1
                  AND e.email IS NOT NULL AND e.email <> ''
                  AND e.id IN ($in)
                  AND e.id NOT IN (SELECT empresa_id FROM empresas_email_log WHERE campanha = ?)";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([...$ids, self::CAMPANHA]);
        return self::enviarEmailParaLista($stmt->fetchAll());
    }

    private static function enviarEmailParaLista(array $empresas): array
    {
        $db       = DB::pdo();
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

    // ───────────────────────── Canal: WhatsApp ─────────────────────────
    // Mesmo público (reivindicada=1), mas só quem tem telefone de algum usuário cadastrado —
    // dedup própria em `empresas_whatsapp_log` (mesma campanha do e-mail: é o mesmo aviso, só o
    // canal muda; os dois nunca competem entre si porque cada um lê a própria tabela).

    private static function baseSqlWhatsapp(): string
    {
        return "FROM empresas e
                WHERE e.ativo = 1
                  AND e.reivindicada = 1
                  AND e.id NOT IN (SELECT empresa_id FROM empresas_whatsapp_log WHERE campanha = ?)";
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
        // Subconsulta (não HAVING sem GROUP BY) — filtra por `telefone` só depois de resolvido.
        $sql =
            "SELECT t.id, t.nome_contato, t.telefone FROM (
                SELECT e.id, " . self::nomeContatoSql() . " AS nome_contato,
                       " . self::telefoneContatoSql() . " AS telefone
                " . self::baseSqlWhatsapp() . "
            ) t WHERE t.telefone IS NOT NULL ORDER BY t.id";
        if ($limite > 0) $sql .= " LIMIT " . (int) $limite;

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        return $stmt->fetchAll();
    }

    /** Mesma ideia de listaEmail(), pro canal WhatsApp.
     *  @return array<int,array{id:int,nome_contato:string,telefone:?string,enviado:bool}> */
    public static function listaWhatsapp(): array
    {
        $sql = "SELECT e.id, " . self::nomeContatoSql() . " AS nome_contato,
                       " . self::telefoneContatoSql() . " AS telefone,
                       (lw.id IS NOT NULL) AS enviado
                FROM empresas e
                LEFT JOIN empresas_whatsapp_log lw ON lw.empresa_id = e.id AND lw.campanha = ?
                WHERE e.ativo = 1 AND e.reivindicada = 1
                ORDER BY e.id";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([self::CAMPANHA]);
        $linhas = $stmt->fetchAll();
        foreach ($linhas as &$l) { $l['enviado'] = (bool) $l['enviado']; }
        return $linhas;
    }

    /** Envia pra até $limite elegíveis (0 = todos). @return array{total:int,enviados:int,falhas:int} */
    public static function dispararTodosWhatsapp(int $limite = 0): array
    {
        return self::enviarWhatsappParaLista(self::elegiveisWhatsapp($limite));
    }

    /** Envia só pros ids selecionados manualmente (ignora quem não tem telefone ou já recebeu).
     *  @param int[] $ids @return array{total:int,enviados:int,falhas:int} */
    public static function dispararWhatsappSelecionados(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) return ['total' => 0, 'enviados' => 0, 'falhas' => 0];

        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT t.id, t.nome_contato, t.telefone FROM (
                    SELECT e.id, " . self::nomeContatoSql() . " AS nome_contato,
                           " . self::telefoneContatoSql() . " AS telefone
                    FROM empresas e
                    WHERE e.ativo = 1 AND e.reivindicada = 1
                      AND e.id IN ($in)
                      AND e.id NOT IN (SELECT empresa_id FROM empresas_whatsapp_log WHERE campanha = ?)
                ) t WHERE t.telefone IS NOT NULL";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([...$ids, self::CAMPANHA]);
        return self::enviarWhatsappParaLista($stmt->fetchAll());
    }

    private static function enviarWhatsappParaLista(array $empresas): array
    {
        $db       = DB::pdo();
        $enviados = 0;
        $falhas   = 0;

        foreach ($empresas as $e) {
            $ok = WhatsAppService::novidadesSistema((string) $e['telefone'], (string) $e['nome_contato']);
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
