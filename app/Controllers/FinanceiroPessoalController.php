<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

/**
 * Financeiro pessoal — gasto do USUÁRIO (dono/funcionário), separado de propósito do
 * financeiro da empresa (ver migration 075). Layout próprio (não usa o shell da empresa —
 * ver layouts/financeiro_pessoal.php), pra reforçar visualmente que é separado do sistema
 * da empresa, não mais uma aba dele.
 */
class FinanceiroPessoalController extends Controller
{
    private \PDO $db;
    private int $eid;
    private int $uid;
    private array $empresa;

    // 'cor' é uma referência de variável CSS (--cat-*, definida nos dois temas em
    // layouts/financeiro_pessoal.php), não mais um hex fixo — assim a mesma cor servida pelo
    // backend já se adapta sozinha ao tema claro/escuro no navegador, sem o servidor precisar
    // saber qual tema o usuário está usando.
    public const CATEGORIAS = [
        'alimentacao' => ['nome' => 'Alimentação', 'cor' => 'var(--cat-alimentacao)'],
        'transporte'  => ['nome' => 'Transporte',  'cor' => 'var(--cat-transporte)'],
        'lazer'       => ['nome' => 'Lazer',       'cor' => 'var(--cat-lazer)'],
        'compras'     => ['nome' => 'Compras',     'cor' => 'var(--cat-compras)'],
        'moradia'     => ['nome' => 'Moradia',     'cor' => 'var(--cat-moradia)'],
        'saude'       => ['nome' => 'Saúde',       'cor' => 'var(--cat-saude)'],
        'outros'      => ['nome' => 'Outros',      'cor' => 'var(--cat-outros)'],
    ];

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->eid = $this->empresaId();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->eid]);
        $this->empresa = $st->fetch() ?: [];
    }

    /** Tela principal — saudação, seletor de mês, resumo, lançamento rápido e Contas e débitos. */
    public function index(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);

        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $lancamentos = [];
        $listas = [];
        $totalAberto = 0.0;
        $totalProx7Dias = 0.0;
        $itemAtrasado = null;
        if ($liberado) {
            // Lançamentos escopados pro MÊS navegado (não "últimos 200 independente do mês") —
            // só assim navegar pra um mês antigo continua mostrando os lançamentos certos, em
            // vez de depender deles caberem dentro de um LIMIT fixo dos mais recentes.
            $inicioMes = $mes . '-01 00:00:00';
            $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));
            $st = $this->db->prepare(
                "SELECT id, tipo, categoria, descricao, valor, data_hora, origem
                 FROM financeiro_pessoal_lancamentos
                 WHERE usuario_id = ? AND data_hora BETWEEN ? AND ?
                 ORDER BY data_hora DESC"
            );
            $st->execute([$this->uid, $inicioMes, $fimMes]);
            $lancamentos = $st->fetchAll();

            $listas = $this->carregarListasComItens();
            foreach ($listas as $l) {
                foreach ($l['itens'] as $item) {
                    if ($item['pago_em'] !== null) { continue; }
                    $totalAberto += (float) $item['valor'];
                    if ($item['vencimento'] !== null && $item['vencimento'] >= date('Y-m-d') && $item['vencimento'] <= date('Y-m-d', strtotime('+7 days'))) {
                        $totalProx7Dias += (float) $item['valor'];
                    }
                }
            }

            $at = $this->db->prepare(
                "SELECT i.id, i.nome, i.valor, i.vencimento
                 FROM financeiro_pessoal_itens i
                 JOIN financeiro_pessoal_listas l ON l.id = i.lista_id
                 WHERE l.usuario_id = ? AND i.pago_em IS NULL AND i.vencimento < CURDATE()
                 ORDER BY i.vencimento ASC LIMIT 1"
            );
            $at->execute([$this->uid]);
            $itemAtrasado = $at->fetch() ?: null;
            if ($itemAtrasado) {
                $itemAtrasado['dias_atraso'] = (int) ((strtotime(date('Y-m-d')) - strtotime($itemAtrasado['vencimento'])) / 86400);
            }
        }

        $totalMes = 0.0;
        $totalReceitas = 0.0;
        foreach ($lancamentos as $l) {
            if ($l['tipo'] === 'despesa') { $totalMes += (float) $l['valor']; }
            else { $totalReceitas += (float) $l['valor']; }
        }

        $hora = (int) date('G');
        $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

        $this->view('financeiro_pessoal.index', [
            'titulo'          => 'Financeiro pessoal',
            'liberado'        => $liberado,
            'saudacao'        => $saudacao,
            'mes'             => $mes,
            'mesAnteriorNav'  => $mesAnteriorNav,
            'mesProximoNav'   => $mesProximoNav,
            'lancamentos'     => $lancamentos,
            'listas'          => $listas,
            'totalMes'        => $totalMes,
            'totalReceitas'   => $totalReceitas,
            'totalAberto'     => $totalAberto,
            'totalProx7Dias'  => $totalProx7Dias,
            'itemAtrasado'    => $itemAtrasado,
            'categorias'      => self::CATEGORIAS,
            // Tela principal ganhou duas colunas largas (Contas e débitos + Lançamentos) na
            // Fase 2 — precisa da mesma largura cheia que o Dashboard já usa, não mais a
            // coluna estreita de quando só tinha o formulário.
            'wrapFull'        => true,
        ], 'financeiro_pessoal');
    }

    /** Listas do usuário + itens agrupados, na ordem de exibição (posição, depois id). */
    private function carregarListasComItens(): array
    {
        $stL = $this->db->prepare(
            "SELECT id, nome, tipo, posicao, aberta FROM financeiro_pessoal_listas
             WHERE usuario_id = ? ORDER BY posicao, id"
        );
        $stL->execute([$this->uid]);
        $listas = $stL->fetchAll();
        if (!$listas) { return []; }

        $ids = array_column($listas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stI = $this->db->prepare(
            "SELECT id, lista_id, nome, valor, vencimento, pago_em, categoria, origem, parcela, posicao
             FROM financeiro_pessoal_itens
             WHERE lista_id IN ($placeholders)
             ORDER BY (pago_em IS NOT NULL), (vencimento IS NULL), vencimento, posicao, id"
        );
        $stI->execute($ids);
        $itens = $stI->fetchAll();

        $porLista = [];
        foreach ($itens as $it) { $porLista[$it['lista_id']][] = $it; }

        foreach ($listas as &$l) {
            $l['itens'] = $porLista[$l['id']] ?? [];
        }
        unset($l);

        return $listas;
    }

    /** Listas + itens em JSON — recarregado pelo JS depois de qualquer ação, sem reload. */
    public function listarListasAjax(): void
    {
        $this->guard();
        $this->json(['ok' => true, 'listas' => $this->carregarListasComItens()]);
    }

    public function criarLista(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $nome = trim((string) $this->post('nome', ''));
        $tipo = $this->post('tipo', 'despesa') === 'debito' ? 'debito' : 'despesa';
        if ($nome === '') { $this->json(['ok' => false, 'erro' => 'Dê um nome pra lista.'], 400); }

        $pos = $this->db->prepare("SELECT COALESCE(MAX(posicao), 0) + 1 FROM financeiro_pessoal_listas WHERE usuario_id = ?");
        $pos->execute([$this->uid]);

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_listas (usuario_id, nome, tipo, posicao, aberta) VALUES (?, ?, ?, ?, 1)"
        )->execute([$this->uid, $nome, $tipo, (int) $pos->fetchColumn()]);

        $this->json(['ok' => true, 'id' => (int) $this->db->lastInsertId()]);
    }

    /** Abre/fecha o card da lista — estado persiste por usuário, não é só um :open do navegador. */
    public function toggleLista(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $aberta = ((int) $this->post('aberta', 1)) === 1 ? 1 : 0;
        $this->db->prepare("UPDATE financeiro_pessoal_listas SET aberta = ? WHERE id = ? AND usuario_id = ?")
            ->execute([$aberta, (int) $id, $this->uid]);
        $this->json(['ok' => true]);
    }

    /** Exclui a lista inteira — itens somem em cascata; lançamentos já gerados ficam (item_id vira NULL). */
    public function excluirLista(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $this->db->prepare("DELETE FROM financeiro_pessoal_listas WHERE id = ? AND usuario_id = ?")
            ->execute([(int) $id, $this->uid]);
        $this->json(['ok' => true]);
    }

    public function criarItem(string $listaId): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $dono = $this->db->prepare("SELECT 1 FROM financeiro_pessoal_listas WHERE id = ? AND usuario_id = ?");
        $dono->execute([(int) $listaId, $this->uid]);
        if (!$dono->fetchColumn()) { $this->json(['ok' => false, 'erro' => 'Lista não encontrada.'], 404); }

        $nome       = trim((string) $this->post('nome', ''));
        $valor      = moeda_float($this->post('valor', 0));
        $vencimento = (string) $this->post('vencimento', '');
        $categoria  = array_key_exists($this->post('categoria', ''), self::CATEGORIAS) ? $this->post('categoria') : 'outros';

        if ($nome === '') { $this->json(['ok' => false, 'erro' => 'Dê um nome pro item.'], 400); }
        if ($valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }
        $vencimentoSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento) ? $vencimento : null;

        $pos = $this->db->prepare("SELECT COALESCE(MAX(posicao), 0) + 1 FROM financeiro_pessoal_itens WHERE lista_id = ?");
        $pos->execute([(int) $listaId]);

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_itens (lista_id, nome, valor, vencimento, categoria, origem, posicao)
             VALUES (?, ?, ?, ?, ?, 'manual', ?)"
        )->execute([(int) $listaId, $nome, $valor, $vencimentoSql, $categoria, (int) $pos->fetchColumn()]);

        $this->json(['ok' => true, 'id' => (int) $this->db->lastInsertId()]);
    }

    public function excluirItem(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $this->db->prepare(
            "DELETE i FROM financeiro_pessoal_itens i
             JOIN financeiro_pessoal_listas l ON l.id = i.lista_id
             WHERE i.id = ? AND l.usuario_id = ?"
        )->execute([(int) $id, $this->uid]);
        $this->json(['ok' => true]);
    }

    /**
     * Marca um item como pago — gera o lançamento de saída correspondente, pro saldo bater
     * com as listas (regra explícita da spec). Trava a linha do item (FOR UPDATE) antes de
     * checar/gravar, mesma defesa já usada em fechar()/adicionarAdiantamento() do sistema
     * principal contra duplo-clique gerando dois lançamentos pro mesmo item.
     */
    public function pagarItem(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare(
                "SELECT i.* FROM financeiro_pessoal_itens i
                 JOIN financeiro_pessoal_listas l ON l.id = i.lista_id
                 WHERE i.id = ? AND l.usuario_id = ? FOR UPDATE"
            );
            $st->execute([(int) $id, $this->uid]);
            $item = $st->fetch();
            if (!$item) {
                $this->db->rollBack();
                $this->json(['ok' => false, 'erro' => 'Item não encontrado.'], 404);
            }

            if ($item['pago_em'] === null) {
                $agora = date('Y-m-d H:i:s');
                $this->db->prepare("UPDATE financeiro_pessoal_itens SET pago_em = ? WHERE id = ?")
                    ->execute([$agora, $item['id']]);
                // origem do lançamento só conhece 'manual'/'foto' (migration 075); itens vindos
                // de scanner (pix/boleto/consumo/ocr — ainda sem caminho de código, Fase 3)
                // caem em 'foto', o rótulo mais próximo já suportado.
                $origemLanc = $item['origem'] === 'manual' ? 'manual' : 'foto';
                $this->db->prepare(
                    "INSERT INTO financeiro_pessoal_lancamentos
                        (usuario_id, tipo, categoria, descricao, valor, data_hora, origem, item_id)
                     VALUES (?, 'despesa', ?, ?, ?, ?, ?, ?)"
                )->execute([$this->uid, $item['categoria'], $item['nome'], $item['valor'], $agora, $origemLanc, $item['id']]);
            }
            $this->db->commit();
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('FinanceiroPessoal::pagarItem — ' . $e->getMessage());
            $this->json(['ok' => false, 'erro' => 'Não deu pra marcar como pago agora.'], 500);
        }
    }

    /** Desmarca "pago" — remove o lançamento que tinha sido gerado, mesmo lock de pagarItem(). */
    public function despagarItem(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare(
                "SELECT i.id FROM financeiro_pessoal_itens i
                 JOIN financeiro_pessoal_listas l ON l.id = i.lista_id
                 WHERE i.id = ? AND l.usuario_id = ? FOR UPDATE"
            );
            $st->execute([(int) $id, $this->uid]);
            if (!$st->fetch()) {
                $this->db->rollBack();
                $this->json(['ok' => false, 'erro' => 'Item não encontrado.'], 404);
            }

            $this->db->prepare("DELETE FROM financeiro_pessoal_lancamentos WHERE item_id = ? AND usuario_id = ?")
                ->execute([(int) $id, $this->uid]);
            $this->db->prepare("UPDATE financeiro_pessoal_itens SET pago_em = NULL WHERE id = ?")
                ->execute([(int) $id]);
            $this->db->commit();
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('FinanceiroPessoal::despagarItem — ' . $e->getMessage());
            $this->json(['ok' => false, 'erro' => 'Não deu pra desmarcar agora.'], 500);
        }
    }

    /** Resumo do mês — total, variação vs. mês anterior, série diária e por categoria. */
    public function dashboard(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);
        $resumo = $liberado ? $this->montarResumoMensal() : null;

        $this->view('financeiro_pessoal.dashboard', [
            'titulo'     => 'Financeiro pessoal — Resumo',
            'liberado'   => $liberado,
            'resumo'     => $resumo,
            'categorias' => self::CATEGORIAS,
            // Dashboard usa a tela inteira no desktop (dono de empresa usa isso mais no
            // computador que no celular, pedido explícito) — index.php (form + lista) continua
            // numa coluna mais estreita, onde faz mais sentido pra um formulário.
            'wrapFull'   => true,
        ], 'financeiro_pessoal');
    }

    private function montarResumoMensal(): array
    {
        $mesAtual     = date('Y-m');
        $mesAnterior  = date('Y-m', strtotime('-1 month'));
        $inicioJanela = date('Y-m-01', strtotime('-1 month'));

        $st = $this->db->prepare(
            "SELECT tipo, categoria, descricao, valor, data_hora
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND data_hora >= ? ORDER BY data_hora"
        );
        $st->execute([$this->uid, $inicioJanela]);
        $linhas = $st->fetchAll();

        $totalMes = 0.0;
        $totalMesAnterior = 0.0;
        $totalReceitas = 0.0;
        $qtdLancamentos = 0;
        $porDia = [];
        $porCategoria = [];
        $maiorGasto = null;

        foreach ($linhas as $l) {
            $ym    = substr($l['data_hora'], 0, 7);
            $valor = (float) $l['valor'];

            if ($l['tipo'] === 'receita') {
                if ($ym === $mesAtual) { $totalReceitas += $valor; $qtdLancamentos++; }
                continue;
            }

            // despesa daqui pra baixo
            if ($ym === $mesAtual) {
                $totalMes += $valor;
                $qtdLancamentos++;
                $dia = (int) substr($l['data_hora'], 8, 2);
                $porDia[$dia] = ($porDia[$dia] ?? 0) + $valor;
                $porCategoria[$l['categoria']] = ($porCategoria[$l['categoria']] ?? 0) + $valor;
                if ($maiorGasto === null || $valor > $maiorGasto['valor']) {
                    $maiorGasto = ['descricao' => $l['descricao'], 'valor' => $valor, 'categoria' => $l['categoria']];
                }
            } elseif ($ym === $mesAnterior) {
                $totalMesAnterior += $valor;
            }
        }

        arsort($porCategoria);

        $variacaoPct = $totalMesAnterior > 0
            ? (int) round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100)
            : null;

        $diasNoMes = (int) date('t');
        $serieDias = [];
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $serieDias[] = round($porDia[$d] ?? 0, 2);
        }

        return [
            'totalMes'         => $totalMes,
            'totalMesAnterior' => $totalMesAnterior,
            'totalReceitas'    => $totalReceitas,
            'saldoMes'         => $totalReceitas - $totalMes,
            'qtdLancamentos'   => $qtdLancamentos,
            'variacaoPct'      => $variacaoPct,
            'serieDias'        => $serieDias,
            'porCategoria'     => $porCategoria,
            'maiorGasto'       => $maiorGasto,
        ];
    }

    /**
     * Lista em JSON — usado pelo JS da própria tela depois de criar/editar/excluir, sem
     * reload. Escopado pelo mesmo ?mes= que a página carregou (ver index()), senão recarregar
     * depois de uma ação enquanto se navega por um mês antigo voltaria pro mês atual sozinho.
     */
    public function listarAjax(): void
    {
        $this->guard();
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $inicioMes = $mes . '-01 00:00:00';
        $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));

        $st = $this->db->prepare(
            "SELECT id, tipo, categoria, descricao, valor, data_hora, origem
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND data_hora BETWEEN ? AND ?
             ORDER BY data_hora DESC"
        );
        $st->execute([$this->uid, $inicioMes, $fimMes]);
        $this->json(['ok' => true, 'lancamentos' => $st->fetchAll()]);
    }

    public function salvar(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $tipo      = $this->post('tipo', 'despesa') === 'receita' ? 'receita' : 'despesa';
        $categoria = array_key_exists($this->post('categoria', ''), self::CATEGORIAS) ? $this->post('categoria') : 'outros';
        $descricao = trim((string) $this->post('descricao', ''));
        $valor     = moeda_float($this->post('valor', 0));
        $dataHora  = (string) $this->post('data_hora', date('Y-m-d H:i:s'));
        $origem    = $this->post('origem', '') === 'foto' ? 'foto' : 'manual';

        if ($descricao === '') { $this->json(['ok' => false, 'erro' => 'Informe uma descrição.'], 400); }
        if ($valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }
        if (!strtotime($dataHora)) { $dataHora = date('Y-m-d H:i:s'); }

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_lancamentos
                (usuario_id, tipo, categoria, descricao, valor, data_hora, origem)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$this->uid, $tipo, $categoria, $descricao, $valor, $dataHora, $origem]);

        $this->json(['ok' => true, 'id' => (int) $this->db->lastInsertId()]);
    }

    /** Edita um lançamento já existente — mesma validação de salvar(), sem mexer em origem/data. */
    public function atualizar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $tipo      = $this->post('tipo', 'despesa') === 'receita' ? 'receita' : 'despesa';
        $categoria = array_key_exists($this->post('categoria', ''), self::CATEGORIAS) ? $this->post('categoria') : 'outros';
        $descricao = trim((string) $this->post('descricao', ''));
        $valor     = moeda_float($this->post('valor', 0));

        if ($descricao === '') { $this->json(['ok' => false, 'erro' => 'Informe uma descrição.'], 400); }
        if ($valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }

        // Confere posse ANTES do UPDATE — rowCount() de um UPDATE só conta linha REALMENTE
        // alterada (driver do MySQL no PDO), não linha encontrada; se a edição não mudar nada
        // (usuário abre, não mexe em nada, salva), rowCount() viria 0 mesmo a linha existindo
        // e sendo dele — usar isso como sinal de "não encontrado" derrubaria uma edição válida.
        $dono = $this->db->prepare("SELECT 1 FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ?");
        $dono->execute([(int) $id, $this->uid]);
        if (!$dono->fetchColumn()) { $this->json(['ok' => false, 'erro' => 'Lançamento não encontrado.'], 404); }

        $this->db->prepare(
            "UPDATE financeiro_pessoal_lancamentos SET tipo = ?, categoria = ?, descricao = ?, valor = ?
             WHERE id = ? AND usuario_id = ?"
        )->execute([$tipo, $categoria, $descricao, $valor, (int) $id, $this->uid]);

        $this->json(['ok' => true]);
    }

    public function excluir(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $st = $this->db->prepare("DELETE FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ?");
        $st->execute([(int) $id, $this->uid]);

        $this->json(['ok' => true, 'removido' => $st->rowCount() > 0]);
    }

    /** Acesso gated por financeiro_pessoal_liberado() — mesmo critério em todo endpoint. */
    private function guard(): void
    {
        if (!financeiro_pessoal_liberado($this->empresa)) {
            $this->json(['ok' => false, 'erro' => 'Financeiro pessoal ainda não está liberado pro seu plano.'], 403);
        }
    }
}
