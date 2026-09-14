<?php

namespace App\Services\Prospeccao;

use App\Core\DB;
use App\Services\WhatsAppService;

/**
 * Disparo de convite via WhatsApp pro Diretório — duas frentes, mesmo número (instância
 * `fixaos`), mesmo limite diário COMPARTILHADO (ver config/diretorio_whatsapp.php pro porquê
 * de não ter rampa/duas cotas separadas como o e-mail tem):
 *   - reivindicar(): empresa já tem ficha em `empresas`, ninguém logou pra gerenciar.
 *   - cadastrar(): CNPJ só existe em `leads_prospeccao`, nenhuma ficha no diretório ainda.
 */
class DisparoWhatsappDiretorioService
{
    public static function limiteDiarioAtual(array $cfg): int
    {
        return (int) ($cfg['limite_diario'] ?? 15);
    }

    /** Quantos convites (dos três tipos, somados) já saíram hoje — um único contador,
     *  porque o risco (bloqueio do número) é do número inteiro, não por campanha. */
    public static function enviadosHoje(): int
    {
        $db = DB::pdo();
        $deEmpresas = (int) $db->query(
            "SELECT COUNT(*) FROM empresas WHERE whatsapp_convite_enviado_em >= CURDATE()"
        )->fetchColumn();
        $deLeads = (int) $db->query(
            "SELECT COUNT(*) FROM leads_prospeccao WHERE whatsapp_convite_enviado_em >= CURDATE()"
        )->fetchColumn();
        $deManual = (int) $db->query(
            "SELECT COUNT(*) FROM diretorio_convites_manuais WHERE enviado_em >= CURDATE()"
        )->fetchColumn();
        return $deEmpresas + $deLeads + $deManual;
    }

    /** Convite "reivindique seu perfil" pros elegíveis do filtro atual (tela master) —
     *  empresa publicada, ainda não reivindicada, com telefone, nunca convidada por WhatsApp.
     *  Reconfere `empresa_nome_indica_servico()` na hora de enviar (defesa extra: mesmo que
     *  `listagem_publica=1` já devesse garantir isso, custa pouco checar de novo antes de
     *  gastar uma mensagem de verdade). */
    public static function dispararReivindicar(array $whereExtra, array $params, int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        $where = array_merge([
            "ativo = 1",
            "listagem_publica = 1",
            "reivindicada = 0",
            "slug IS NOT NULL AND slug <> ''",
            "whatsapp_convite_enviado_em IS NULL",
            "(COALESCE(whatsapp_publico,'') <> '' OR COALESCE(telefone,'') <> '')",
        ], $whereExtra);
        $stmt = $db->prepare(
            "SELECT id, nome_fantasia, slug, whatsapp_publico, telefone FROM empresas
             WHERE " . implode(' AND ', $where) . "
             ORDER BY criado_em ASC LIMIT {$quantidade}"
        );
        $stmt->execute($params);

        $enviados = 0;
        foreach ($stmt->fetchAll() as $emp) {
            $nome = (string) $emp['nome_fantasia'];
            if (!empresa_nome_indica_servico($nome)) continue;

            $numero = $emp['whatsapp_publico'] ?: $emp['telefone'];
            $ok = WhatsAppService::conviteDiretorioReivindicar($numero, $nome, (string) $emp['slug']);
            if ($ok) {
                $db->prepare("UPDATE empresas SET whatsapp_convite_enviado_em = NOW() WHERE id = ?")
                   ->execute([$emp['id']]);
                $enviados++;
            }
        }
        return $enviados;
    }

    /** Convite "cadastre-se grátis" pros elegíveis do filtro atual — CNPJ ainda sem ficha no
     *  diretório, com telefone, nunca convidado por WhatsApp. `leads_prospeccao` já é filtrada
     *  por CNAE do ramo desde a importação original, não precisa reconferir nome aqui (mesmo
     *  critério que o convite por e-mail equivalente já usa). */
    public static function dispararCadastrar(array $whereExtra, array $params, int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        $where = array_merge([
            "status <> 'descartado'",
            "whatsapp_convite_enviado_em IS NULL",
            "COALESCE(telefone,'') <> ''",
        ], $whereExtra);
        $stmt = $db->prepare(
            "SELECT id, nome_fantasia, razao_social, telefone FROM leads_prospeccao
             WHERE " . implode(' AND ', $where) . "
             ORDER BY criado_em ASC LIMIT {$quantidade}"
        );
        $stmt->execute($params);

        $enviados = 0;
        foreach ($stmt->fetchAll() as $lead) {
            $nome = (string) ($lead['nome_fantasia'] ?: $lead['razao_social']);
            $ok = WhatsAppService::conviteDiretorioCadastrar((string) $lead['telefone'], $nome);
            if ($ok) {
                $db->prepare("UPDATE leads_prospeccao SET whatsapp_convite_enviado_em = NOW() WHERE id = ?")
                   ->execute([$lead['id']]);
                $enviados++;
            }
        }
        return $enviados;
    }

    /** Processa um texto colado pelo Master (uma linha por empresa) e grava o que é válido em
     *  `diretorio_convites_manuais` — terceira fonte de convite, curada à mão (nome + WhatsApp
     *  achados na internet), pra fugir do risco de número morto/errado da base de CNPJ.
     *  Formato livre: qualquer separador antes do telefone funciona ("Nome; 11999998888",
     *  "Nome - (11) 99999-8888", "Nome, 11 99999-8888") — o parser sempre trata a sequência de
     *  dígitos/pontuação de telefone NO FIM da linha como o WhatsApp e o resto como o nome.
     *  Dedup só DENTRO desta lista (`UNIQUE KEY uq_whatsapp`) — não cruza com o número já usado
     *  em `empresas`/`leads_prospeccao`; é uma lista à parte, do jeito mais simples que atende
     *  o pedido (evitar reenviar pro mesmo número colado duas vezes aqui). */
    public static function adicionarManual(string $texto): array
    {
        $db = DB::pdo();
        $adicionados = 0;
        $duplicados  = 0;
        $invalidos   = [];

        $stmt = $db->prepare(
            "INSERT IGNORE INTO diretorio_convites_manuais (nome_empresa, whatsapp) VALUES (?, ?)"
        );

        foreach (preg_split('/\r\n|\r|\n/', $texto) as $linha) {
            $linha = trim($linha);
            if ($linha === '') continue;

            // Acha o TELEFONE primeiro (trecho final da linha, só dígitos/espaço/parênteses/
            // ponto/traço/mais) e só depois corta o nome do que sobrou antes dele — não dá pra
            // resolver os dois de uma vez com um único regex guloso (nome comia parte do
            // telefone: "Assistência Silva; 119" / "99998888", greedy demais).
            if (!preg_match('/[\d\s()+.-]{8,}$/u', $linha, $m, PREG_OFFSET_CAPTURE)) {
                $invalidos[] = $linha;
                continue;
            }
            $nome  = trim(substr($linha, 0, $m[0][1]), " \t\n\r\0\x0B;,|-");
            $whats = only_numbers($m[0][0]);
            if ($nome === '' || strlen($whats) < 10 || strlen($whats) > 13) {
                $invalidos[] = $linha;
                continue;
            }

            $stmt->execute([mb_substr($nome, 0, 150), $whats]);
            if ($stmt->rowCount() > 0) {
                $adicionados++;
            } else {
                $duplicados++;
            }
        }

        return ['adicionados' => $adicionados, 'duplicados' => $duplicados, 'invalidos' => $invalidos];
    }

    public static function contarPendentesManual(): int
    {
        return (int) DB::pdo()->query(
            "SELECT COUNT(*) FROM diretorio_convites_manuais WHERE enviado_em IS NULL"
        )->fetchColumn();
    }

    public static function listarPendentesManual(int $limit = 300): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = DB::pdo()->prepare(
            "SELECT id, nome_empresa, whatsapp FROM diretorio_convites_manuais
             WHERE enviado_em IS NULL ORDER BY criado_em ASC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function excluirManual(int $id): void
    {
        DB::pdo()->prepare(
            "DELETE FROM diretorio_convites_manuais WHERE id = ? AND enviado_em IS NULL"
        )->execute([$id]);
    }

    /** Dispara pros pendentes da lista manual, mais antigos primeiro — mesmo template de
     *  mensagem do "cadastrar" (são sempre empresas sem ficha nenhuma no diretório ainda). */
    public static function dispararManual(int $quantidade): int
    {
        if ($quantidade <= 0) return 0;

        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT id, nome_empresa, whatsapp FROM diretorio_convites_manuais
             WHERE enviado_em IS NULL ORDER BY criado_em ASC LIMIT {$quantidade}"
        );
        $stmt->execute();

        $enviados = 0;
        foreach ($stmt->fetchAll() as $c) {
            $ok = WhatsAppService::conviteDiretorioCadastrar((string) $c['whatsapp'], (string) $c['nome_empresa']);
            if ($ok) {
                $db->prepare("UPDATE diretorio_convites_manuais SET enviado_em = NOW() WHERE id = ?")
                   ->execute([$c['id']]);
                $enviados++;
            }
        }
        return $enviados;
    }
}
