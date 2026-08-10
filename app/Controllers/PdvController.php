<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Models\Produto;

class PdvController extends Controller
{
    /** Tela do PDV (frente de caixa). */
    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $tx = $db->prepare("SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = 'taxas_cartao'");
        $tx->execute([$eid]);

        $this->view('pdv.index', [
            'titulo'      => 'PDV — Frente de Caixa',
            'taxasCartao' => $tx->fetchColumn() ?: '',
        ]);
    }

    /**
     * Finaliza a venda (AJAX/JSON). Bloqueia se faltar estoque.
     * Grava pdv_vendas + pdv_venda_itens, baixa estoque (saída) e
     * lança a receita paga em fin_lancamentos.
     */
    public function finalizar(): void
    {
        if (!csrf_verify())              { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página (F5).']); }
        if (!Auth::can('pdv', 'ver'))    { $this->json(['ok' => false, 'erro' => 'Você não tem permissão para vender.']); }

        $eid   = $this->empresaId();
        $itens = json_decode((string) $this->post('itens', '[]'), true);
        if (!is_array($itens) || !$itens) { $this->json(['ok' => false, 'erro' => 'Adicione pelo menos um item à venda.']); }

        // Normaliza itens e agrega quantidade por produto
        $linhas  = [];
        $precisa = [];
        foreach ($itens as $it) {
            $pid  = !empty($it['produto_id']) ? (int) $it['produto_id'] : null;
            $desc = trim((string) ($it['descricao'] ?? ''));
            $qtd  = (float) str_replace(',', '.', (string) ($it['quantidade'] ?? 0));
            $vu   = (float) str_replace(',', '.', (string) ($it['valor_unitario'] ?? 0));
            if ($qtd <= 0 || $desc === '' || $vu < 0) continue;
            $linhas[] = ['pid' => $pid, 'desc' => $desc, 'qtd' => $qtd, 'vu' => $vu];
            if ($pid) $precisa[$pid] = ($precisa[$pid] ?? 0) + $qtd;
        }
        if (!$linhas) { $this->json(['ok' => false, 'erro' => 'Itens inválidos.']); }

        $db     = DB::pdo();
        $custos = [];

        // Checagem de estoque — bloqueia venda sem saldo
        $faltas = [];
        foreach ($precisa as $pid => $qtd) {
            $st = $db->prepare("SELECT nome, estoque_atual, unidade, valor_custo FROM produtos WHERE id = ? AND empresa_id = ?");
            $st->execute([$pid, $eid]);
            $p = $st->fetch();
            if (!$p) { $faltas[] = "produto #{$pid} não encontrado"; continue; }
            $custos[$pid] = (float) $p['valor_custo'];
            if ((float) $p['estoque_atual'] < $qtd) {
                $faltas[] = "{$p['nome']} (saldo " . ($p['estoque_atual'] + 0) . ", pedido " . ($qtd + 0) . ")";
            }
        }
        if ($faltas) { $this->json(['ok' => false, 'erro' => 'Estoque insuficiente para: ' . implode('; ', $faltas)]); }

        $subtotal = 0.0;
        foreach ($linhas as $l) $subtotal += $l['qtd'] * $l['vu'];
        $desconto  = moeda_float($this->post('desconto', 0));
        $desconto  = max(0, min($desconto, $subtotal));
        $total     = round($subtotal - $desconto, 2);
        $formasOk  = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro'];

        // Pagamento dividido: lista de linhas (forma, valor, parcelas, taxa). Compatível com
        // o formato antigo (uma forma só) se 'pagamentos' não vier no POST.
        $pagamentosRaw = json_decode((string) $this->post('pagamentos', '[]'), true);
        $pagamentos = [];
        if (is_array($pagamentosRaw)) {
            foreach ($pagamentosRaw as $p) {
                $f = $p['forma'] ?? '';
                if (!in_array($f, $formasOk, true)) continue;
                $v = moeda_float($p['valor'] ?? 0);
                if ($v <= 0) continue;
                $parc = max(1, min(24, (int) ($p['parcelas'] ?? 1)));
                $parcTaxa = $f === 'cartao_credito' ? $parc : 1;
                // Taxa NUNCA vem do formulário — só da config da empresa (Config → Cartões).
                // Pix entra aqui também: é a taxa da maquininha quando o pix é cobrado por ela.
                $tx   = in_array($f, ['cartao_credito', 'cartao_debito', 'pix'], true)
                      ? taxa_cartao_configurada($eid, $f, $parcTaxa) : 0.0;
                $pagamentos[] = ['forma' => $f, 'valor' => round($v, 2), 'parcelas' => $parcTaxa, 'taxa' => $tx];
            }
        }
        if (!$pagamentos) {
            $formaUnica = $this->post('forma_pagamento', 'dinheiro');
            if (!in_array($formaUnica, $formasOk, true)) $formaUnica = 'dinheiro';
            $parcelasFb = max(1, (int) $this->post('cartao_parcelas', 1));
            $pagamentos[] = [
                'forma'    => $formaUnica,
                'valor'    => $total,
                'parcelas' => $parcelasFb,
                // Taxa NUNCA vem do formulário — só da config da empresa (Config → Cartões).
                'taxa'     => in_array($formaUnica, ['cartao_credito', 'cartao_debito', 'pix'], true)
                             ? taxa_cartao_configurada($eid, $formaUnica, $parcelasFb) : 0.0,
            ];
        }

        $clienteId = $this->post('cliente_id') ? (int) $this->post('cliente_id') : null;
        $obs       = trim((string) $this->post('observacoes')) ?: null;

        $somaDeclarada = round(array_sum(array_column($pagamentos, 'valor')), 2);
        if ($somaDeclarada < $total - 0.01) {
            $this->json(['ok' => false, 'erro' => 'A soma das formas de pagamento (' . money($somaDeclarada) . ') é menor que o total da venda (' . money($total) . ').']);
        }

        // Cartão — taxa da maquininha por linha (reflexo no caixa)
        $cartaoRepassar = $this->post('cartao_repassar') === '1';
        foreach ($pagamentos as &$pg) {
            $ehMaquininhaLinha = in_array($pg['forma'], ['cartao_credito', 'cartao_debito', 'pix'], true);
            $taxaAplicaLinha = $ehMaquininhaLinha && $pg['taxa'] > 0 && $pg['valor'] > 0;
            $pg['valor_cobrado'] = ($taxaAplicaLinha && $cartaoRepassar) ? round($pg['valor'] / (1 - $pg['taxa'] / 100), 2) : $pg['valor'];
            $pg['taxa_valor']    = $taxaAplicaLinha ? round($pg['valor_cobrado'] * $pg['taxa'] / 100, 2) : 0.0;
        }
        unset($pg);

        $taxaValorTotal = round(array_sum(array_column($pagamentos, 'taxa_valor')), 2);
        $forma          = count($pagamentos) > 1 ? 'misto' : $pagamentos[0]['forma'];

        // Troco só no caso clássico: uma única forma "dinheiro". Em pagamento dividido,
        // cada linha é tomada como valor exato (sem troco por linha).
        $recebido = null; $troco = 0.0;
        if ($forma === 'dinheiro') {
            $recebido = moeda_float($this->post('valor_recebido', 0));
            $troco    = ($recebido > $total) ? round($recebido - $total, 2) : 0.0;
        }

        // Conta financeira padrão da empresa
        $stC = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
        $stC->execute([$eid]);
        $contaId = $stC->fetchColumn() ?: null;

        try {
            $db->beginTransaction();

            $db->prepare(
                "INSERT INTO pdv_vendas
                 (empresa_id, usuario_id, cliente_id, subtotal, desconto, total, forma_pagamento, valor_recebido, troco, conta_id, status, observacoes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'concluida', ?)"
            )->execute([
                $eid, $this->usuarioId(), $clienteId, $subtotal, $desconto, $total,
                $forma, $recebido ?: null, $troco ?: null, $contaId, $obs,
            ]);
            $vendaId = (int) $db->lastInsertId();

            foreach ($pagamentos as $pg) {
                $db->prepare(
                    "INSERT INTO pdv_venda_pagamentos
                     (empresa_id, venda_id, forma_pagamento, valor, parcelas, taxa_percentual, taxa_valor, valor_cobrado)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([$eid, $vendaId, $pg['forma'], $pg['valor'], $pg['parcelas'], $pg['taxa'], $pg['taxa_valor'], $pg['valor_cobrado']]);
            }

            $produto = new Produto();
            foreach ($linhas as $l) {
                $vtot   = round($l['qtd'] * $l['vu'], 2);
                $vcusto = $l['pid'] ? ($custos[$l['pid']] ?? 0) : 0;
                $db->prepare(
                    "INSERT INTO pdv_venda_itens
                     (empresa_id, venda_id, produto_id, descricao, quantidade, valor_unitario, valor_custo, valor_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([$eid, $vendaId, $l['pid'], $l['desc'], $l['qtd'], $l['vu'], $vcusto, $vtot]);

                if ($l['pid']) {
                    $produto->movimentar($l['pid'], 'saida', $l['qtd'], $l['vu'], "Venda PDV #{$vendaId}");
                }
            }

            // Lançamento financeiro (receita) — 1 linha por forma de pagamento. Crédito parcelado
            // no modo "mês a mês" (Config → Cartões) vira 1 linha por parcela, na data prevista de
            // repasse da adquirente (pendente até chegar o dia); as demais formas são sempre no
            // mesmo dia, já que não há parcela pra espalhar.
            $modoReceb  = modo_recebimento_cartao($eid);
            $hoje       = date('Y-m-d');
            $lancId     = null;
            foreach ($pagamentos as $pg) {
                $contaSimples = in_array($pg['forma'], ['cartao_credito', 'cartao_debito'], true) ? 'cartao'
                              : ($pg['forma'] === 'pix' ? 'banco' : 'caixa');
                $nParc = ($pg['forma'] === 'cartao_credito' && $pg['parcelas'] > 1 && $modoReceb === 'mes_a_mes')
                       ? $pg['parcelas'] : 1;

                if ($nParc === 1) {
                    $db->prepare(
                        "INSERT INTO fin_lancamentos
                         (empresa_id, conta_id, conta_simples, cliente_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento)
                         VALUES (?, ?, ?, ?, ?, 'receita', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
                    )->execute([$eid, $contaId, $contaSimples, $clienteId, $this->usuarioId(), "Venda PDV #{$vendaId}", $pg['valor_cobrado'], $pg['forma']]);
                    $lancId = $lancId ?? (int) $db->lastInsertId();
                    continue;
                }

                $base = round($pg['valor_cobrado'] / $nParc, 2);
                $soma = 0.0;
                for ($i = 1; $i <= $nParc; $i++) {
                    $valorParc = $i < $nParc ? $base : round($pg['valor_cobrado'] - $soma, 2);
                    $soma     += $valorParc;
                    $dataVenc  = date('Y-m-d', strtotime($hoje . ' +' . (($i - 1) * 30) . ' days'));
                    $jaPago    = $dataVenc <= $hoje;
                    $db->prepare(
                        "INSERT INTO fin_lancamentos
                         (empresa_id, conta_id, conta_simples, cliente_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento)
                         VALUES (?, ?, ?, ?, ?, 'receita', ?, ?, ?, ?, ?, ?)"
                    )->execute([
                        $eid, $contaId, $contaSimples, $clienteId, $this->usuarioId(),
                        "Venda PDV #{$vendaId} (parcela {$i}/{$nParc})", $valorParc, $dataVenc,
                        $jaPago ? $dataVenc : null, $jaPago ? 'pago' : 'pendente', $pg['forma'],
                    ]);
                    $lancId = $lancId ?? (int) $db->lastInsertId();
                }
            }

            $db->prepare("UPDATE pdv_vendas SET lancamento_id = ? WHERE id = ?")->execute([$lancId, $vendaId]);

            // Despesa: taxa do cartão — uma linha por forma de cartão usada na venda
            if ($taxaValorTotal > 0) {
                $catStmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='despesa' AND nome='Taxas de cartão' LIMIT 1");
                $catStmt->execute([$eid]);
                $catTaxa = $catStmt->fetchColumn();
                if (!$catTaxa) { $db->prepare("INSERT INTO fin_categorias (empresa_id,tipo,nome,cor) VALUES (?, 'despesa','Taxas de cartão','#dc3545')")->execute([$eid]); $catTaxa = (int) $db->lastInsertId(); }
                foreach ($pagamentos as $pg) {
                    if ($pg['taxa_valor'] <= 0) continue;
                    if ($pg['forma'] === 'pix') {
                        $descTaxa = "Taxa pix (maquininha) — Venda PDV #{$vendaId} (" . number_format($pg['taxa'], 2, ',', '.') . '%)';
                    } else {
                        $qc = $pg['forma'] === 'cartao_debito' ? 'débito' : $pg['parcelas'] . 'x';
                        $descTaxa = "Taxa cartão — Venda PDV #{$vendaId} ({$qc} · " . number_format($pg['taxa'], 2, ',', '.') . '%)';
                    }
                    $db->prepare(
                        "INSERT INTO fin_lancamentos
                         (empresa_id, conta_id, conta_simples, categoria_id, cliente_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento)
                         VALUES (?, ?, 'cartao', ?, ?, ?, 'despesa', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
                    )->execute([$eid, $contaId, $catTaxa, $clienteId, $this->usuarioId(),
                        $descTaxa, $pg['taxa_valor'], $pg['forma']]);
                }
            }

            $db->commit();
            $this->json(['ok' => true, 'venda_id' => $vendaId, 'redirect' => url('/pdv/comprovante/' . $vendaId)]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->json(['ok' => false, 'erro' => 'Erro ao registrar a venda: ' . $e->getMessage()]);
        }
    }

    /** Cupom / comprovante da venda (imprimível). */
    public function comprovante(string $id): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $st = $db->prepare(
            "SELECT v.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone, u.nome AS vendedor
             FROM pdv_vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             LEFT JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.id = ? AND v.empresa_id = ?"
        );
        $st->execute([(int) $id, $eid]);
        $venda = $st->fetch();
        if (!$venda) { $this->flash('error', 'Venda não encontrada.'); $this->redirect(url('/pdv')); }

        $sti = $db->prepare("SELECT * FROM pdv_venda_itens WHERE venda_id = ? AND empresa_id = ? ORDER BY id");
        $sti->execute([(int) $id, $eid]);

        $ste = $db->prepare("SELECT nome_fantasia, cnpj, telefone, logradouro, numero, bairro, cidade, uf, logo FROM empresas WHERE id = ?");
        $ste->execute([$eid]);

        $this->view('pdv.comprovante', [
            'venda'   => $venda,
            'itens'   => $sti->fetchAll(),
            'empresa' => $ste->fetch() ?: [],
        ], 'limpo');
    }
}
