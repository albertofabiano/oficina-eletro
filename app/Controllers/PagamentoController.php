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

        $db = DB::pdo();
        $se = $db->prepare("SELECT nome_fantasia, razao_social, email, telefone, whatsapp, whatsapp_publico FROM empresas WHERE id = ?");
        $se->execute([$eid]);
        $e = $se->fetch() ?: [];

        // Vagas de lançamento esgotadas? Cobra o preço cheio desde o 1º mês, não o preço promocional.
        $precoMensal = (int) $p['preco_mensal'];
        if (!empty($p['vagas_promo']) && plano_vagas_info($p)['esgotado']) {
            $precoMensal = (int) ($p['preco_pos_intro'] ?? $precoMensal);
        }

        $orderNsu = 'fx-' . $eid . '-' . time();
        $valor    = plano_preco_ciclo($precoMensal, $ck);
        $dias     = (int) $ck['dias'];

        $db->prepare("INSERT INTO cobrancas (empresa_id, plano, ciclo, dias, valor, order_nsu, status) VALUES (?,?,?,?,?,?, 'pendente')")
           ->execute([$eid, $p['codigo'], $ciclo, $dias, $valor, $orderNsu]);
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
            $this->flash('error', 'Não foi possível gerar o pagamento agora. Tente novamente em instantes.');
            $this->redirect(url('/planos'));
        }

        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        log_acao('cobranca', 'gerar', $cobId, 'Plano ' . $p['nome'] . ' — R$ ' . number_format($valor / 100, 2, ',', '.'));

        header('Location: ' . $link);
        exit;
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
        $txNsu    = $data['transaction_nsu'] ?? '';
        $slug     = $data['invoice_slug'] ?? '';

        if (!$orderNsu) { http_response_code(400); echo json_encode(['success' => false]); return; }

        $db  = DB::pdo();
        $st  = $db->prepare("SELECT * FROM cobrancas WHERE order_nsu = ? LIMIT 1");
        $st->execute([$orderNsu]);
        $c = $st->fetch();
        // desconhecido ou já pago → ACK (idempotente)
        if (!$c || $c['status'] === 'pago') { http_response_code(200); echo json_encode(['success' => true]); return; }

        // NÃO confia no corpo do webhook — confirma consultando a InfinitePay (payment_check).
        $chk  = InfinitePayService::verificarPagamento($orderNsu, $txNsu, $slug);
        $pago = !empty($chk['paid'])
             || !empty($chk['success'])
             || in_array((string) ($chk['status'] ?? ''), ['paid', 'approved', 'captured', 'success'], true);

        if ($pago) {
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE cobrancas SET status='pago', transaction_nsu=?, invoice_slug=?, capture_method=?, paid_amount=?, receipt_url=?, pago_em=NOW() WHERE id=?")
                   ->execute([$txNsu, $slug, $data['capture_method'] ?? null, $data['paid_amount'] ?? null, $data['receipt_url'] ?? null, $c['id']]);

                if (($c['tipo'] ?? 'assinatura') === 'diretorio') {
                    // Anúncio do Diretório (destaque/banner) — libera sozinho, sem aprovação do
                    // Master. 'plano' guarda 'diretorio_{assinaturaId}' (mesma convenção de prefixo
                    // já usada pros pacotes de crédito, ver ramo acima).
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
                } else {
                    // assinatura → estende a licença pelos dias do ciclo + ativa o plano
                    $dias = (int) ($c['dias'] ?? 0) ?: (int) (InfinitePayService::config()['dias_por_ciclo'] ?? 30);
                    $db->prepare("UPDATE empresas SET plano_atual=?, tipo_conta='completo',
                                    licenca_ate = DATE_ADD(GREATEST(CURDATE(), COALESCE(licenca_ate, CURDATE())), INTERVAL ? DAY)
                                  WHERE id=?")
                       ->execute([$c['plano'], $dias, $c['empresa_id']]);
                }
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(400); echo json_encode(['success' => false, 'error' => 'db']); return;
            }
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
            $st = DB::pdo()->prepare("SELECT status FROM cobrancas WHERE id=? AND empresa_id=?");
            $st->execute([$cobId, $this->empresaId()]);
            $paga = ($st->fetchColumn() === 'pago');
        }
        $this->view('empresa.pagamento_retorno', ['titulo' => 'Pagamento', 'paga' => $paga]);
    }
}
