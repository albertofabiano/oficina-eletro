<?php

namespace App\Services\Prospeccao;

use App\Core\DB;
use App\Services\EmailService;

/**
 * Núcleo do disparo de e-mail de prospecção — compartilhado entre o botão manual
 * "Disparar agora" (MasterController::prospeccaoDisparar(), filtrado pela tela) e a rotina
 * diária automática (scripts/disparar_prospeccao_diario.php, sem filtro, misturando estados).
 * Os dois respeitam o mesmo limite diário (config/prospeccao_email.php), contado pela mesma
 * coluna (email_convite_enviado_em), então não há risco de passar do limite combinando os dois.
 */
class DisparoService
{
    /** Quantos convites já saíram hoje — usado pelos dois caminhos pra descontar do limite diário. */
    public static function enviadosHoje(): int
    {
        return (int) DB::pdo()->query(
            "SELECT COUNT(*) FROM leads_prospeccao WHERE email_convite_enviado_em >= CURDATE()"
        )->fetchColumn();
    }

    /** Disparo manual filtrado (tela /master/prospeccao) — ordem simples pelos mais antigos,
     *  respeitando o filtro atual (status/cnae/uf/cidade) que o master está vendo na tela. */
    public static function dispararFiltrado(array $whereExtra, array $params, int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        $where = array_merge(["email IS NOT NULL", "email <> ''", "email_convite_enviado_em IS NULL"], $whereExtra);
        $stmt = $db->prepare(
            "SELECT id, email, razao_social, nome_fantasia, municipio, uf FROM leads_prospeccao
             WHERE " . implode(' AND ', $where) . "
             ORDER BY criado_em ASC LIMIT {$quantidade}"
        );
        $stmt->execute($params);

        return self::enviarLote($db, $stmt->fetchAll());
    }

    /** Disparo automático diário (scripts/disparar_prospeccao_diario.php) — sem filtro de
     *  cidade/UF, misturando estados diferentes por round-robin: pega 1 lead de cada UF que
     *  ainda tem elegível, dá a volta de novo se sobrar cota, até bater a quantidade pedida.
     *  Evita que o dia inteiro saia concentrado só num estado, mesmo que ele tenha muito mais
     *  leads acumulados que os outros. */
    public static function dispararMisturandoUf(int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        // Teto de 2000 candidatos (bem mais que o suficiente pra cobrir os ~27 estados várias
        // vezes) — evita carregar a base inteira só pra escolher ~20 leads do dia.
        $stmt = $db->query(
            "SELECT id, email, razao_social, nome_fantasia, municipio, uf FROM leads_prospeccao
             WHERE status = 'novo' AND email IS NOT NULL AND email <> '' AND email_convite_enviado_em IS NULL
             ORDER BY uf ASC, criado_em ASC
             LIMIT 2000"
        );

        $porUf = [];
        foreach ($stmt->fetchAll() as $l) {
            $porUf[$l['uf'] ?: '?'][] = $l;
        }

        $selecionados = [];
        while (count($selecionados) < $quantidade && $porUf) {
            foreach (array_keys($porUf) as $uf) {
                if (empty($porUf[$uf])) { unset($porUf[$uf]); continue; }
                $selecionados[] = array_shift($porUf[$uf]);
                if (count($selecionados) >= $quantidade) break;
            }
        }

        return self::enviarLote($db, $selecionados);
    }

    /** Envia e marca cada lead da lista; só conta/marca como enviado o que o SMTP aceitou de
     *  verdade — uma falha de conexão não pode fazer o lead "sumir" da fila sem ninguém perceber. */
    private static function enviarLote(\PDO $db, array $leads): int
    {
        if (!$leads) return 0;

        $appCfg  = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($appCfg['url'], '/');
        $enviados = 0;

        foreach ($leads as $l) {
            $token = bin2hex(random_bytes(20));
            $unsubLink = $baseUrl . '/prospeccao/descadastrar/' . $token;
            $ok = EmailService::convitePropeccao(
                $l['email'],
                $l['nome_fantasia'] ?: $l['razao_social'],
                (string) $l['municipio'],
                (string) $l['uf'],
                $unsubLink
            );
            if ($ok) {
                $db->prepare("UPDATE leads_prospeccao SET email_convite_enviado_em = NOW(), email_unsub_token = ? WHERE id = ?")
                   ->execute([$token, $l['id']]);
                $enviados++;
            }
        }

        return $enviados;
    }
}
