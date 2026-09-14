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

    /** Quantos convites (dos dois tipos, somados) já saíram hoje — um único contador,
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
        return $deEmpresas + $deLeads;
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
}
