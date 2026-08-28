<?php

namespace App\Services\Prospeccao;

use App\Core\DB;
use App\Services\EmailService;

/**
 * Disparo do convite "reivindique seu perfil" pra `diretorio_leads_email` — mesmo conceito de
 * App\Services\Prospeccao\DisparoService (rampa de volume, token por envio, só marca/conta o
 * que o SMTP aceitou de verdade), mas separado dele de propósito: é outra campanha (empresa que
 * já tem ficha publicada, não lead frio sem cadastro), outro público-alvo, outro limite diário
 * (config/diretorio_leads_email.php) — mistura zero com a contagem de leads_prospeccao.
 */
class DisparoDiretorioService
{
    /** Mesma lógica de rampa de DisparoService::limiteDiarioAtual(), config própria. */
    public static function limiteDiarioAtual(array $emailCfg): int
    {
        if (empty($emailCfg['rampa']) || empty($emailCfg['rampa_inicio'])) {
            return (int) ($emailCfg['limite_diario'] ?? 20);
        }

        $inicio = strtotime($emailCfg['rampa_inicio'] . ' 00:00:00');
        if ($inicio === false) return (int) ($emailCfg['limite_diario'] ?? 20);

        $diasPassados = max(0, (int) floor((time() - $inicio) / 86400));

        $limite = 20;
        foreach ($emailCfg['rampa'] as $dia => $valor) {
            if ($diasPassados >= (int) $dia) $limite = (int) $valor;
        }
        return $limite;
    }

    /** Quantos convites já saíram hoje — desconta do limite diário. */
    public static function enviadosHoje(): int
    {
        return (int) DB::pdo()->query(
            "SELECT COUNT(*) FROM diretorio_leads_email WHERE email_convite_enviado_em >= CURDATE()"
        )->fetchColumn();
    }

    /** Disparo manual filtrado (tela /master/diretorio-emails) — ordem pelos mais antigos,
     *  respeitando o filtro atual (uf/cidade) que o master está vendo na tela. Nunca manda pra
     *  quem já reivindicou (não faz sentido convidar a reivindicar de novo) nem pra quem já se
     *  descadastrou. */
    public static function dispararFiltrado(array $whereExtra, array $params, int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        $where = array_merge([
            "email IS NOT NULL", "email <> ''",
            "email_convite_enviado_em IS NULL",
            "descadastrado_em IS NULL",
            "reivindicada = 0",
        ], $whereExtra);
        $stmt = $db->prepare(
            "SELECT id, empresa_id, email, nome_fantasia, cidade, uf FROM diretorio_leads_email
             WHERE " . implode(' AND ', $where) . "
             ORDER BY criado_em ASC LIMIT {$quantidade}"
        );
        $stmt->execute($params);

        return self::enviarLote($db, $stmt->fetchAll());
    }

    /** Envia e marca cada lead da lista; só conta/marca como enviado o que o SMTP aceitou de
     *  verdade — uma falha de conexão não pode fazer o lead "sumir" da fila sem ninguém perceber. */
    private static function enviarLote($db, array $leads): int
    {
        if (!$leads) return 0;

        $stmtSlug = $db->prepare("SELECT slug FROM empresas WHERE id = ?");
        $enviados = 0;

        foreach ($leads as $l) {
            $stmtSlug->execute([$l['empresa_id']]);
            $slug = $stmtSlug->fetchColumn();
            if (!$slug) continue; // empresa removida/sem slug entre a extração e o disparo — pula.

            $token = bin2hex(random_bytes(20));
            $ok = EmailService::conviteReivindicarDiretorio(
                $l['email'],
                (string) $l['nome_fantasia'],
                (string) $l['cidade'],
                (string) $l['uf'],
                (string) $slug,
                $token
            );
            if ($ok) {
                $db->prepare("UPDATE diretorio_leads_email SET email_convite_enviado_em = NOW(), email_unsub_token = ? WHERE id = ?")
                   ->execute([$token, $l['id']]);
                $enviados++;
            }
        }

        return $enviados;
    }
}
