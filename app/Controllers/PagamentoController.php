<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\InfinitePayService;

class PagamentoController extends Controller
{
    private function cfg(): array { return require BASE_PATH . '/config/planos.php'; }

    /** Gera a cobrança de um plano+ciclo e manda o cliente pro checkout da InfinitePay. */
    public function assinar(string $plano, string $ciclo = 'mensal'): void
    {
        $eid = $this->empresaId();
        $cfg = $this->cfg();
        $p   = null;
        foreach ($cfg['planos'] as $pl) if ($pl['codigo'] === $plano) $p = $pl;
        $ck  = $cfg['ciclos'][$ciclo] ?? null;
        if (!$p || !$ck) { $this->flash('error', 'Plano ou ciclo inválido.'); $this->redirect(url('/planos')); }

        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte para ativar seu plano. 🙂');
            $this->redirect(url('/planos'));
        }

        $link = self::gerarLinkAssinatura(DB::pdo(), $eid, $plano, $ciclo);
        if (!$link) {
            $this->flash('error', 'Não foi possível gerar o pagamento agora. Tente novamente em instantes.');
            $this->redirect(url('/planos'));
        }

        header('Location: ' . $link);
        exit;
    }

    /**
     * Monta a cobrança de um plano+ciclo e devolve o link do checkout, pronto pra redirecionar
     * OU pra embutir num e-mail/notificação de aviso de vencimento (scripts/
     * avisar_vencimento_licenca.php) — extraído de assinar() pra ser reaproveitável fora de um
     * request HTTP (sem $this->flash()/$this->redirect(), sem depender de sessão). Devolve null
     * em qualquer falha (plano/ciclo inválido, InfinitePay fora do ar) — quem chama decide como
     * reagir (redirect com flash, pular o aviso daquele dia etc.).
     */
    public static function gerarLinkAssinatura(\PDO $db, int $empresaId, string $plano, string $ciclo): ?string
    {
        $cfg = require BASE_PATH . '/config/planos.php';
        $p   = null;
        foreach ($cfg['planos'] as $pl) if ($pl['codigo'] === $plano) $p = $pl;
        $ck  = $cfg['ciclos'][$ciclo] ?? null;
        if (!$p || !$ck) return null;
        if (!InfinitePayService::ativo()) return null;

        $se = $db->prepare("SELECT nome_fantasia, razao_social, email, telefone, whatsapp, whatsapp_publico FROM empresas WHERE id = ?");
        $se->execute([$empresaId]);
        $e = $se->fetch() ?: [];

        // Vagas de lançamento esgotadas? Cobra o preço cheio desde o 1º mês, não o preço promocional.
        $precoMensal = (int) $p['preco_mensal'];
        if (!empty($p['vagas_promo']) && plano_vagas_info($p)['esgotado']) {
            $precoMensal = (int) ($p['preco_pos_intro'] ?? $precoMensal);
        }

        $orderNsu = 'fx-' . $empresaId . '-' . time();
        $valor    = plano_preco_ciclo($precoMensal, $ck);
        $dias     = (int) $ck['dias'];

        $db->prepare("INSERT INTO cobrancas (empresa_id, plano, ciclo, dias, valor, order_nsu, status) VALUES (?,?,?,?,?,?, 'pendente')")
           ->execute([$empresaId, $p['codigo'], $ciclo, $dias, $valor, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items    = [['description' => 'FixaOS — Plano ' . $p['nome'] . ' (' . $ck['nome'] . ')', 'quantity' => 1, 'price' => $valor]];
        $customer = array_filter([
            'name'         => $e['nome_fantasia'] ?? ($e['razao_social'] ?? null),
            'email'        => $e['email'] ?? null,
            'phone_number' => telefone_internacional($e['whatsapp'] ?: ($e['whatsapp_publico'] ?: ($e['telefone'] ?? ''))),
        ]);

        $link = InfinitePayService::criarLink(
            $orderNsu, $items,
            url('/pagamento/retorno?c=' . $cobId),
            url('/webhook/infinitepay'),
            $customer
        );

        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            return null;
        }

        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        // log_acao() lê a empresa/usuário da SESSÃO (Auth::empresaId()) — fora de um request
        // HTTP (ex.: chamado pelo cron de aviso de vencimento) não há sessão nenhuma, então ele
        // só retorna sem gravar (guard já existente em log_acao()); dentro de um request
        // (assinar()) continua registrando normalmente.
        log_acao('cobranca', 'gerar', $cobId, 'Plano ' . $p['nome'] . ' — R$ ' . number_format($valor / 100, 2, ',', '.'));

        return $link;
    }

    /** Gera a cobrança de um PACOTE DE CRÉDITO de OS extra e manda pro checkout. */
    public function comprarCredito(): void
    {
        $eid  = $this->empresaId();
        $pack = $this->cfg()['credito_os'] ?? null;
        if (!$pack) { $this->flash('error', 'Pacote de crédito indisponível.'); $this->redirect(url('/planos')); }
        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte. 🙂');
            $this->redirect(url('/planos'));
        }

        $db = DB::pdo();
        $se = $db->prepare("SELECT nome_fantasia, razao_social, email, telefone, whatsapp, whatsapp_publico FROM empresas WHERE id = ?");
        $se->execute([$eid]);
        $e = $se->fetch() ?: [];

        $qtd      = (int) $pack['qtd'];
        $valor    = (int) $pack['preco'];
        $orderNsu = 'fxc-' . $eid . '-' . time();

        $db->prepare("INSERT INTO cobrancas (empresa_id, tipo, plano, valor, order_nsu, status) VALUES (?, 'credito', ?, ?, ?, 'pendente')")
           ->execute([$eid, 'credito_' . $qtd, $valor, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items    = [['description' => 'FixaOS — Pacote de ' . $qtd . ' OS extra', 'quantity' => 1, 'price' => $valor]];
        $customer = array_filter([
            'name'         => $e['nome_fantasia'] ?? ($e['razao_social'] ?? null),
            'email'        => $e['email'] ?? null,
            'phone_number' => telefone_internacional($e['whatsapp'] ?: ($e['whatsapp_publico'] ?: ($e['telefone'] ?? ''))),
        ]);

        $link = InfinitePayService::criarLink($orderNsu, $items, url('/pagamento/retorno?c=' . $cobId), url('/webhook/infinitepay'), $customer);
        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            $this->flash('error', 'Não foi possível gerar o pagamento agora.');
            $this->redirect(url('/planos'));
        }
        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        log_acao('cobranca', 'credito', $cobId, $qtd . ' OS por R$ ' . number_format($valor / 100, 2, ',', '.'));

        header('Location: ' . $link);
        exit;
    }

    /** Gera a cobrança de um PACOTE DE CRÉDITO de buscas de IA extra (equipamento ou placa). */
    private function comprarCreditoScanBase(string $configKey, string $planoPrefixo, string $descricao): void
    {
        $eid  = $this->empresaId();
        $pack = $this->cfg()[$configKey] ?? null;
        if (!$pack) { $this->flash('error', 'Pacote de crédito indisponível.'); $this->redirect(url('/planos')); }
        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte. 🙂');
            $this->redirect(url('/planos'));
        }

        $db = DB::pdo();
        $se = $db->prepare("SELECT nome_fantasia, razao_social, email, telefone, whatsapp, whatsapp_publico FROM empresas WHERE id = ?");
        $se->execute([$eid]);
        $e = $se->fetch() ?: [];

        $qtd      = (int) $pack['qtd'];
        $valor    = (int) $pack['preco'];
        $orderNsu = 'fxs-' . $eid . '-' . time();

        $db->prepare("INSERT INTO cobrancas (empresa_id, tipo, plano, valor, order_nsu, status) VALUES (?, 'credito', ?, ?, ?, 'pendente')")
           ->execute([$eid, $planoPrefixo . $qtd, $valor, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items    = [['description' => 'FixaOS — Pacote de ' . $qtd . ' ' . $descricao, 'quantity' => 1, 'price' => $valor]];
        $customer = array_filter([
            'name'         => $e['nome_fantasia'] ?? ($e['razao_social'] ?? null),
            'email'        => $e['email'] ?? null,
            'phone_number' => telefone_internacional($e['whatsapp'] ?: ($e['whatsapp_publico'] ?: ($e['telefone'] ?? ''))),
        ]);

        $link = InfinitePayService::criarLink($orderNsu, $items, url('/pagamento/retorno?c=' . $cobId), url('/webhook/infinitepay'), $customer);
        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            $this->flash('error', 'Não foi possível gerar o pagamento agora.');
            $this->redirect(url('/planos'));
        }
        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        log_acao('cobranca', 'credito', $cobId, $qtd . ' ' . $descricao . ' por R$ ' . number_format($valor / 100, 2, ',', '.'));

        header('Location: ' . $link);
        exit;
    }

    /** Compra de crédito extra de buscas de IA para leitura de EQUIPAMENTO (Ordem de Serviço). */
    public function comprarCreditoScanEquip(): void
    {
        $this->comprarCreditoScanBase('credito_scan_equip', 'creditoscanequip_', 'buscas de equipamento');
    }

    /** Compra de crédito extra de buscas de IA para leitura de PLACA (Marketplace). */
    public function comprarCreditoScanPlaca(): void
    {
        $this->comprarCreditoScanBase('credito_scan_placa', 'creditoscanplaca_', 'buscas de placa (marketplace)');
    }

    /** Webhook público da InfinitePay: confirma o pagamento e estende a licença. */
    public function webhook(): void
    {
        header('Content-Type: application/json');
        $data     = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $orderNsu = $data['order_nsu'] ?? '';
        if (!$orderNsu) { http_response_code(400); echo json_encode(['success' => false]); return; }

        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM cobrancas WHERE order_nsu = ? LIMIT 1");
        $st->execute([$orderNsu]);
        $cobId = $st->fetchColumn();
        // desconhecido → ACK (não é um erro nosso; webhook de uma cobrança que não existe aqui)
        if (!$cobId) { http_response_code(200); echo json_encode(['success' => true]); return; }

        try {
            self::confirmarCobranca($db, (int) $cobId, $data);
        } catch (\Throwable $e) {
            error_log('PagamentoController::webhook — ' . $e->getMessage());
            // 400 de propósito (não 200) — sinaliza pra InfinitePay tentar reenviar o webhook
            // depois; um 200 aqui faria ela desistir mesmo com o pagamento ainda não aplicado.
            http_response_code(400); echo json_encode(['success' => false, 'error' => 'db']); return;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    /** Landing após o pagamento (redirect_url do checkout). */
    public function retorno(): void
    {
        $cobId = (int) $this->get('c', 0);
        $paga  = false;
        if ($cobId) {
            $db = DB::pdo();
            $st = $db->prepare("SELECT status FROM cobrancas WHERE id=? AND empresa_id=?");
            $st->execute([$cobId, $this->empresaId()]);
            $row = $st->fetch();
            if ($row) {
                $paga = $row['status'] === 'pago';
                if (!$paga) {
                    // O webhook pode ainda não ter chegado — confirma na hora (mesma checagem
                    // estrita), pra quem acabou de pagar não ver "não pago" à toa por causa de
                    // uma corrida de tempo entre o redirect do checkout e o webhook assíncrono.
                    try { $paga = self::confirmarCobranca($db, $cobId); }
                    catch (\Throwable $e) { error_log('PagamentoController::retorno — ' . $e->getMessage()); }
                }
            }
        }
        $this->view('empresa.pagamento_retorno', ['titulo' => 'Pagamento', 'paga' => $paga]);
    }

    /**
     * Confirmação ESTRITA de pagamento — único ponto de decisão "isso está pago?" de todo o
     * sistema, usado tanto por webhook() quanto por retorno(). NUNCA confia em nada que vem do
     * corpo do webhook/parâmetro de URL pra decidir se está pago: sempre reconsulta a
     * InfinitePay (payment_check) e compara contra o valor gravado no banco.
     *
     * Campos reais confirmados via scripts/diagnostico_payment_check.php contra produção
     * (2026-10-08) — a resposta NUNCA tem um campo `status` (a checagem antiga comparava contra
     * uma lista de strings adivinhadas — 'approved'/'captured'/'success' — que não existem de
     * verdade na resposta, nunca batiam com nada):
     *   pendente: {"success": false}
     *   pago:     {"success": true, "paid": true, "amount": N, "paid_amount": N,
     *              "installments": N, "capture_method": "pix"|...}
     *
     * "Pago" exige as TRÊS condições: success===true E paid===true E paid_amount (centavos)
     * EXATAMENTE igual ao valor da cobrança já gravado em `cobrancas.valor` — nunca o valor
     * do corpo do webhook, só o que o payment_check devolveu agora, comparado contra o banco.
     *
     * Idempotência contra corrida real (webhook e retorno() chegando quase ao mesmo tempo):
     * `SELECT ... FOR UPDATE` trava a linha da cobrança antes de checar o status — a segunda
     * chamada concorrente fica bloqueada até a primeira commitar e, ao retomar, já vê
     * status='pago', sem reprocessar (sem creditar/estender duas vezes).
     *
     * @param array $webhookData corpo bruto do webhook, só pra registrar metadados
     *                           (transaction_nsu/invoice_slug/receipt_url — nenhum deles entra
     *                           na decisão de "está pago"); vazio quando chamado por retorno().
     * @return bool true se a cobrança está (ou já estava) paga.
     */
    private static function confirmarCobranca(\PDO $db, int $cobrancaId, array $webhookData = []): bool
    {
        $db->beginTransaction();
        try {
            $st = $db->prepare("SELECT * FROM cobrancas WHERE id = ? FOR UPDATE");
            $st->execute([$cobrancaId]);
            $c = $st->fetch();
            if (!$c) { $db->commit(); return false; }
            if ($c['status'] === 'pago') { $db->commit(); return true; }

            $chk = InfinitePayService::verificarPagamento(
                (string) $c['order_nsu'],
                (string) ($webhookData['transaction_nsu'] ?? $c['transaction_nsu'] ?? ''),
                (string) ($webhookData['invoice_slug'] ?? $c['invoice_slug'] ?? '')
            );

            $pago = ($chk['success'] ?? null) === true
                 && ($chk['paid'] ?? null) === true
                 && isset($chk['paid_amount'])
                 && (int) $chk['paid_amount'] === (int) $c['valor'];

            if (!$pago) { $db->commit(); return false; }

            $db->prepare(
                "UPDATE cobrancas SET status='pago', transaction_nsu=?, invoice_slug=?, capture_method=?, paid_amount=?, receipt_url=?, pago_em=NOW() WHERE id=?"
            )->execute([
                $webhookData['transaction_nsu'] ?? $c['transaction_nsu'],
                $webhookData['invoice_slug'] ?? $c['invoice_slug'],
                $chk['capture_method'] ?? ($webhookData['capture_method'] ?? null),
                (int) $chk['paid_amount'],
                $webhookData['receipt_url'] ?? null,
                $cobrancaId,
            ]);

            self::aplicarEfeitoCobranca($db, $c);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Efeito de negócio de uma cobrança confirmada como paga — extraído de confirmarCobranca()
     * pra manter o dispatch por `tipo` isolado da parte de confirmação/idempotência. Mesmo
     * dispatch de sempre (diretorio/credito/fixa/assinatura), lógica interna inalterada.
     */
    private static function aplicarEfeitoCobranca(\PDO $db, array $c): void
    {
        if (($c['tipo'] ?? 'assinatura') === 'diretorio') {
            // Anúncio do Diretório (destaque/banner) — libera sozinho, sem aprovação do
            // Master. 'plano' guarda 'diretorio_{assinaturaId}' (mesma convenção de prefixo
            // já usada pros pacotes de crédito, ver ramo abaixo).
            $assinaturaId = (int) preg_replace('/\D/', '', (string) $c['plano']);
            $sa = $db->prepare("SELECT a.*, p.duracao_dias, p.tipo AS plano_tipo, p.preco FROM diretorio_assinaturas a JOIN diretorio_planos p ON p.id = a.plano_id WHERE a.id = ?");
            $sa->execute([$assinaturaId]);
            $a = $sa->fetch();
            if ($a) {
                $dataInicio = $a['data_inicio'] ?: date('Y-m-d');
                $db->prepare(
                    "UPDATE diretorio_assinaturas SET status='ativo', data_inicio=?, valor_pago=?,
                        data_fim = DATE_ADD(GREATEST(CURDATE(), COALESCE(data_fim, CURDATE())), INTERVAL ? DAY)
                     WHERE id=?"
                )->execute([$dataInicio, $a['preco'], (int) $a['duracao_dias'], $assinaturaId]);

                if ($a['plano_tipo'] === 'destaque') {
                    $fim = $db->prepare("SELECT data_fim FROM diretorio_assinaturas WHERE id=?");
                    $fim->execute([$assinaturaId]);
                    $tipoDestaque = $a['preco'] > 60 ? 'premium' : 'basico';
                    $db->prepare("UPDATE empresas SET diretorio_destaque=?, diretorio_destaque_ate=? WHERE id=?")
                       ->execute([$tipoDestaque, $fim->fetchColumn(), $a['empresa_id']]);
                }
            }
        } elseif (($c['tipo'] ?? 'assinatura') === 'credito') {
            // pacote de crédito → soma ao saldo certo conforme o prefixo salvo em 'plano'
            $planoCred = (string) $c['plano'];
            $qtd = (int) preg_replace('/\D/', '', $planoCred);
            if (strpos($planoCred, 'creditoscanequip_') === 0) {
                $db->prepare("UPDATE empresas SET creditos_scan_equip = creditos_scan_equip + ? WHERE id=?")->execute([$qtd, $c['empresa_id']]);
            } elseif (strpos($planoCred, 'creditoscanplaca_') === 0) {
                $db->prepare("UPDATE empresas SET creditos_scan_placa = creditos_scan_placa + ? WHERE id=?")->execute([$qtd, $c['empresa_id']]);
            } else {
                // 'credito_25' (OS extra)
                $db->prepare("UPDATE empresas SET creditos_os = creditos_os + ? WHERE id=?")->execute([$qtd, $c['empresa_id']]);
            }
        } elseif (($c['tipo'] ?? 'assinatura') === 'fixa') {
            // Carteira Fixa standalone — reaproveita 100% o motor de checkout/webhook já
            // usado pro plano completo e pro Diretório, só ramificando por `tipo` (mesmo
            // padrão). 'plano' guarda 'fixa_upgrade_{assinaturaId}' (upgrade Individual→
            // Diretório, único upgrade possível hoje nos 2 planos existentes) ou
            // 'fixa_{assinaturaId}' (teste virando pago, ou renovação de um ciclo já
            // ativo) — mesma convenção de prefixo já usada pro Diretório
            // ('diretorio_{assinaturaId}').
            $planoCobranca = (string) $c['plano'];
            if (strpos($planoCobranca, 'fixa_upgrade_') === 0) {
                $assinaturaId = (int) substr($planoCobranca, strlen('fixa_upgrade_'));
                \App\Services\Fixa\AssinaturaService::confirmarUpgrade($db, $assinaturaId, 'fixa_diretorio');
            } else {
                $assinaturaId = (int) substr($planoCobranca, strlen('fixa_'));
                \App\Services\Fixa\AssinaturaService::confirmarPagamento($db, $assinaturaId);
            }
        } else {
            // assinatura → estende a licença pelos dias do ciclo + ativa o plano
            $dias = (int) ($c['dias'] ?? 0) ?: (int) (InfinitePayService::config()['dias_por_ciclo'] ?? 30);
            $db->prepare("UPDATE empresas SET plano_atual=?, tipo_conta='completo',
                            licenca_ate = DATE_ADD(GREATEST(CURDATE(), COALESCE(licenca_ate, CURDATE())), INTERVAL ? DAY)
                          WHERE id=?")
               ->execute([$c['plano'], $dias, $c['empresa_id']]);

            // Fixa Fase Cobrança: plano novo inclui Fixa de graça (autonomo/oficina/
            // empresa) → cancela qualquer assinatura Fixa STANDALONE ativa dos usuários
            // dessa empresa, creditando o proporcional (pedido explícito da Etapa 2).
            if (in_array($c['plano'], ['autonomo', 'oficina', 'empresa'], true)) {
                $us = $db->prepare("SELECT id FROM usuarios WHERE empresa_id = ?");
                $us->execute([$c['empresa_id']]);
                foreach ($us->fetchAll(\PDO::FETCH_COLUMN) as $usuarioId) {
                    $assinatura = \App\Services\Fixa\AssinaturaService::doUsuario($db, (int) $usuarioId);
                    if ($assinatura && in_array($assinatura['status'], ['teste', 'ativa', 'inadimplente'], true)) {
                        \App\Services\Fixa\AssinaturaService::cancelarComCredito($db, (int) $assinatura['id']);
                    }
                }
            }
        }
    }
}
