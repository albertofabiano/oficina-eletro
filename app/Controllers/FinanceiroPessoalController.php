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

    // Semente das 7 categorias padrão — gravadas de verdade (migration 078) no primeiro
    // acesso de CADA usuário (ver categoriasDoUsuario()), não mais um PHP const fixo e igual
    // pra todo mundo: virou CRUD de verdade (criar/editar/excluir), pedido do usuário. As
    // CHAVES são as mesmas de sempre — todo lançamento já existente guarda uma dessas strings
    // em `categoria`, então manter a chave igual evita qualquer migração de dado. 'cor' nos
    // padrões é uma referência de variável CSS (--cat-*, nos dois temas em
    // layouts/financeiro_pessoal.php) — se o usuário editar uma categoria padrão pelo CRUD
    // novo, ela passa a usar um hex fixo escolhido na hora (perde a adaptação automática de
    // tema, mesmo trade-off já aceito em empresas.cor_capa).
    private const CATEGORIAS_PADRAO = [
        ['alimentacao', 'Alimentação', 'var(--cat-alimentacao)'],
        ['transporte',  'Transporte',  'var(--cat-transporte)'],
        ['lazer',       'Lazer',       'var(--cat-lazer)'],
        ['compras',     'Compras',     'var(--cat-compras)'],
        ['moradia',     'Moradia',     'var(--cat-moradia)'],
        ['saude',       'Saúde',       'var(--cat-saude)'],
        ['outros',      'Outros',      'var(--cat-outros)'],
    ];

    // Mesma whitelist/limite já usado em ProdutoController pra upload de imagem — sem
    // compartilhar uma constante entre os dois controllers (cada um já tem a própria cópia
    // nesse projeto, ver histórico), só o valor é igual.
    private const AVATAR_MIME_PERMITIDO = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const AVATAR_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB

    /**
     * Categorias ativas do usuário, chave => ['id','nome','cor'] — mesmo formato do antigo
     * CATEGORIAS fixo, pra todo código que já consumia esse shape continuar funcionando sem
     * mudança. Primeiro acesso de um usuário (nenhuma linha em financeiro_pessoal_categorias)
     * semeia as 7 padrão uma única vez. Estático (recebe $db/$usuarioId em vez de usar $this)
     * porque ScannerController::receberFotoFinanceira() também precisa chamar isso fora de
     * uma instância deste controller (fluxo de pareamento por QR, usuário dono da sessão nem
     * sempre é quem está logado nesta requisição).
     */
    public static function categoriasDoUsuario(\PDO $db, int $usuarioId): array
    {
        $st = $db->prepare(
            "SELECT id, chave, nome, cor FROM financeiro_pessoal_categorias
             WHERE usuario_id = ? AND ativo = 1 ORDER BY posicao, id"
        );
        $st->execute([$usuarioId]);
        $linhas = $st->fetchAll(\PDO::FETCH_ASSOC);

        if (!$linhas) {
            $ins = $db->prepare(
                "INSERT INTO financeiro_pessoal_categorias (usuario_id, chave, nome, cor, posicao)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach (self::CATEGORIAS_PADRAO as $i => $d) {
                $ins->execute([$usuarioId, $d[0], $d[1], $d[2], $i]);
                $linhas[] = ['id' => (int) $db->lastInsertId(), 'chave' => $d[0], 'nome' => $d[1], 'cor' => $d[2]];
            }
        }

        $out = [];
        foreach ($linhas as $l) {
            $out[$l['chave']] = ['id' => (int) $l['id'], 'nome' => $l['nome'], 'cor' => $l['cor']];
        }
        return $out;
    }

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->eid = $this->empresaId();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->eid]);
        $this->empresa = $st->fetch() ?: [];
    }

    /** Tela principal — saudação, seletor de mês, resumo e lista de lançamentos. */
    public function index(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);

        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $lancamentos = [];
        $categorias = [];
        if ($liberado) {
            // Mesma cautela da tela de Categorias — nunca deixa isso derrubar a página
            // inteira com 500; pior caso, o formulário de adicionar lançamento fica sem
            // opção de categoria nenhuma (cai no fallback 'outros' no servidor de qualquer
            // forma, ver salvar()/atualizar()), mas a tela continua de pé.
            try {
                $categorias = self::categoriasDoUsuario($this->db, $this->uid);
            } catch (\Throwable $e) {
                error_log('FinanceiroPessoal::index — ' . $e->getMessage());
            }
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
        }

        $totalMes = 0.0;
        $totalReceitas = 0.0;
        foreach ($lancamentos as $l) {
            if ($l['tipo'] === 'despesa') { $totalMes += (float) $l['valor']; }
            else { $totalReceitas += (float) $l['valor']; }
        }

        $hora = (int) date('G');
        $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

        // Resumo (gráfico "dia a dia" + "por categoria" + variação vs. mês anterior) — era
        // uma página própria (/financeiro-pessoal/dashboard), virou parte desta mesma tela a
        // pedido do usuário ("o dashboard vai ficar no lugar dela"). Navega junto com o mesmo
        // ?mes= do resto da página — antes o Resumo só olhava "mês atual", fixo.
        $resumo = $liberado ? $this->montarResumoMensal($mes) : null;

        $this->view('financeiro_pessoal.index', [
            'titulo'          => 'Financeiro pessoal',
            'liberado'        => $liberado,
            'saudacao'        => $saudacao,
            'mes'             => $mes,
            'mesAnteriorNav'  => $mesAnteriorNav,
            'mesProximoNav'   => $mesProximoNav,
            'lancamentos'     => $lancamentos,
            'totalMes'        => $totalMes,
            'totalReceitas'   => $totalReceitas,
            'categorias'      => $categorias,
            'resumo'          => $resumo,
            // Resumo + Lançamentos precisam da largura cheia que o Dashboard já usava — não
            // mais a coluna estreita de quando a tela só tinha o formulário de lançamento.
            'wrapFull'        => true,
        ], 'financeiro_pessoal');
    }

    /**
     * Tela própria só com a lista de lançamentos do mês (sem KPIs/gráfico) — pedido do
     * usuário: recriar a lista separada que existia antes dela virar parte do Resumo, com
     * ícone próprio na sidebar. Mesma query/escopo por mês de index(), sem $resumo/$totalMes/
     * $totalReceitas (nada aqui soma nada, é só a lista em si).
     */
    public function lancamentos(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);

        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $lancamentos = [];
        $categorias = [];
        if ($liberado) {
            try {
                $categorias = self::categoriasDoUsuario($this->db, $this->uid);
            } catch (\Throwable $e) {
                error_log('FinanceiroPessoal::lancamentos — ' . $e->getMessage());
            }
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
        }

        $this->view('financeiro_pessoal.lancamentos', [
            'titulo'          => 'Financeiro pessoal — Lançamentos',
            'liberado'        => $liberado,
            'mes'             => $mes,
            'mesAnteriorNav'  => $mesAnteriorNav,
            'mesProximoNav'   => $mesProximoNav,
            'lancamentos'     => $lancamentos,
            'categorias'      => $categorias,
            // Mesma largura cheia de index()/categorias() — consistência entre as 3 telas.
            'wrapFull'        => true,
        ], 'financeiro_pessoal');
    }

    /**
     * Mesma validação repetida em salvar()/atualizar()/criarItem() — nunca deixa uma falha
     * transitória de banco na BUSCA de categorias (categoriasDoUsuario()) derrubar a ação
     * principal (salvar um lançamento/item) com um 500 que o JS só sabe mostrar como "Falha
     * de conexão" (confuso — não tem nada a ver com rede; bug real reportado pelo usuário).
     * Categoria inválida OU lookup indisponível caem no mesmo fallback de sempre: 'outros'.
     */
    private function categoriaValidaOuPadrao(string $enviada): string
    {
        try {
            $categorias = self::categoriasDoUsuario($this->db, $this->uid);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::categoriaValidaOuPadrao — ' . $e->getMessage());
            return 'outros';
        }
        return array_key_exists($enviada, $categorias) ? $enviada : 'outros';
    }

    /** Mesma cautela acima, pros 2 pontos que precisam da LISTA inteira (não validar 1 valor). */
    private function categoriasDoUsuarioOuVazio(): array
    {
        try {
            return self::categoriasDoUsuario($this->db, $this->uid);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::categoriasDoUsuarioOuVazio — ' . $e->getMessage());
            return [];
        }
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
        $categoria  = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));

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

    /**
     * Rota antiga (/financeiro-pessoal/dashboard) — o Resumo virou a própria tela principal
     * (ver index()), pedido do usuário. Fica só redirecionando, pra não quebrar favorito/link
     * salvo de quem já tinha essa URL.
     */
    public function dashboard(): void
    {
        $this->redirect(url('/financeiro-pessoal'));
    }

    /** Resumo do mês ($mes, 'YYYY-MM') — total, variação vs. mês anterior, série diária e por
     * categoria. Navega junto com o ?mes= da tela principal (ver index()). */
    private function montarResumoMensal(string $mes): array
    {
        $mesAtual     = $mes;
        $mesAnterior  = date('Y-m', strtotime($mes . '-01 -1 month'));
        $inicioJanela = date('Y-m-01', strtotime($mes . '-01 -1 month'));

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

        $diasNoMes = (int) date('t', strtotime($mes . '-01'));
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
        $categoria = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));
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
        $categoria = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));
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

    /**
     * Celular do PRÓPRIO usuário (sem QR/pareamento, mesmo padrão de ScannerController::
     * lerDireto()): fotografou a conta direto no aparelho que já está com a tela aberta —
     * processa e lê com a IA de visão na hora, sem passar por scanner_sessoes (não precisa:
     * é o mesmo dispositivo que vai abrir o formulário de revisão em seguida).
     */
    public function ocrConta(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $durl = (string) $this->post('foto', '');
        if (!preg_match('~^data:image/(jpe?g|png|webp);base64,~', $durl)) {
            $this->json(['ok' => false, 'erro' => 'Foto inválida.'], 400);
        }
        $bin = base64_decode(substr($durl, strpos($durl, ',') + 1), true);
        if ($bin === false || strlen($bin) < 100 || strlen($bin) > 4_000_000) {
            $this->json(['ok' => false, 'erro' => 'Foto inválida.'], 400);
        }

        $dir = BASE_PATH . '/storage/uploads/scanner';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $caminho = $dir . '/conta_direto_' . $this->uid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.webp';
        if (!\App\Services\ImageService::binarioParaWebp($bin, $caminho, 85, 1600)) {
            $this->json(['ok' => false, 'erro' => 'Não deu pra processar a foto. Tente de novo.'], 400);
        }

        $extraido = \App\Services\VisionService::lerConta($caminho, array_keys($this->categoriasDoUsuarioOuVazio()));
        @unlink($caminho); // nada fica salvo — a foto só serve de referência na revisão

        if ($extraido && $extraido['descricao'] !== '') {
            $aprendida = financeiro_pessoal_categoria_aprendida($this->uid, $extraido['descricao']);
            if ($aprendida !== null) { $extraido['categoria'] = $aprendida; }
        }

        $this->json(['ok' => true, 'extraido' => $extraido]);
    }

    /**
     * Grava (ou atualiza) a categoria aprendida pra um beneficiário — chamado pelo JS da
     * revisão só quando o usuário escolhe uma categoria DIFERENTE da que a IA sugeriu, pra
     * essa correção valer sozinha na próxima leitura (ver financeiro_pessoal_categoria_
     * aprendida(), usada em ScannerController::receberFotoFinanceira() e ocrConta() acima).
     */
    public function aprenderCategoria(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $benef = trim((string) $this->post('beneficiario', ''));
        $categoria = (string) $this->post('categoria', '');
        if ($benef === '' || !array_key_exists($categoria, $this->categoriasDoUsuarioOuVazio())) {
            $this->json(['ok' => false], 400);
        }

        $chave = financeiro_pessoal_normalizar_beneficiario($benef);
        if ($chave === '') { $this->json(['ok' => false], 400); }

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_categoria_regras (usuario_id, beneficiario_normalizado, categoria)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE categoria = VALUES(categoria), atualizado_em = NOW()"
        )->execute([$this->uid, $chave, $categoria]);

        $this->json(['ok' => true]);
    }

    /**
     * CRUD de Categorias (menu novo na barra lateral) — pedido do usuário: "vai ter um crud
     * em lista". Página simples de form+redirect (não AJAX, diferente do resto deste
     * controller) — mesmo padrão já usado por catálogos simples do sistema, ex.
     * ServicosCatalogoController.
     */
    public function categorias(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);
        $categorias = [];
        if ($liberado) {
            try {
                $categorias = self::categoriasDoUsuario($this->db, $this->uid);
            } catch (\Throwable $e) {
                // Nunca derruba a tela com 500 por causa disso — pior caso, a pessoa vê "você
                // ainda não tem categoria nenhuma" (empty state de verdade, ver categorias.php)
                // em vez de uma página quebrada. Fica no log pra investigar depois.
                error_log('FinanceiroPessoal::categorias — ' . $e->getMessage());
                $categorias = [];
            }
        }

        $this->view('financeiro_pessoal.categorias', [
            'titulo'     => 'Financeiro pessoal — Categorias',
            'liberado'   => $liberado,
            'categorias' => $categorias,
            // Largura cheia, pedido do usuário com print — mesmo .fp-wrap-full já usado em
            // index()/dashboard() (sem isso, .fp-wrap trava em min(820px,94vw)).
            'wrapFull'   => true,
        ], 'financeiro_pessoal');
    }

    /**
     * Chave (slug) de uma categoria nova — gerada uma vez na criação e NUNCA muda depois; é
     * esse valor que fica gravado em financeiro_pessoal_lancamentos.categoria pra sempre, uma
     * mudança de chave quebraria o vínculo com todo lançamento já existente.
     */
    private function gerarChaveCategoria(string $nome): string
    {
        $base = strtolower(remover_acentos($nome));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base);
        $base = trim($base, '_');
        if ($base === '') { $base = 'categoria'; }
        $base = substr($base, 0, 30);

        $chave = $base;
        $i = 2;
        $st = $this->db->prepare("SELECT 1 FROM financeiro_pessoal_categorias WHERE usuario_id = ? AND chave = ?");
        while (true) {
            $st->execute([$this->uid, $chave]);
            if (!$st->fetchColumn()) { break; }
            $chave = $base . '_' . $i;
            $i++;
        }
        return $chave;
    }

    /** Só aceita hex de verdade vindo do <input type="color"> — qualquer outra coisa (campo
     * vazio, POST forjado) cai num tom neutro, nunca grava lixo na coluna. */
    private function corCategoriaValida(string $cor): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : '#7A6A88';
    }

    /** Insere a categoria nova e devolve a chave gerada — usado só por categoriaSalvar() (tela
     * de Categorias); o chip "+ Nova" do card colapsado foi removido (pedido do usuário: ali
     * ele quer EDITAR uma categoria já existente, não criar uma nova). */
    private function criarCategoria(string $nome, string $cor): string
    {
        $pos = $this->db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE usuario_id = ?");
        $pos->execute([$this->uid]);

        $chave = $this->gerarChaveCategoria($nome);
        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_categorias (usuario_id, chave, nome, cor, posicao) VALUES (?, ?, ?, ?, ?)"
        )->execute([$this->uid, $chave, $nome, $cor, (int) $pos->fetchColumn()]);

        return $chave;
    }

    /** Edita nome/cor de uma categoria já existente — compartilhado entre categoriaAtualizar()
     * (form+redirect, tela de Categorias) e categoriaEditarAjax() (JSON, lápis em cada chip do
     * card colapsado de um lançamento). A `chave` em si nunca muda depois de criada (ver
     * gerarChaveCategoria()). rowCount() > 0 confirma que a linha é mesmo do usuário logado. */
    private function atualizarCategoria(int $id, string $nome, string $cor): bool
    {
        $st = $this->db->prepare(
            "UPDATE financeiro_pessoal_categorias SET nome = ?, cor = ? WHERE id = ? AND usuario_id = ?"
        );
        $st->execute([$nome, $cor, $id, $this->uid]);
        return $st->rowCount() > 0;
    }

    public function categoriaSalvar(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $nome = trim((string) $this->post('nome', ''));
        $cor  = $this->corCategoriaValida((string) $this->post('cor', ''));
        if ($nome === '') { $this->flash('error', 'Dê um nome pra categoria.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->criarCategoria($nome, $cor);

        $this->flash('success', 'Categoria criada!');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

    public function categoriaAtualizar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $nome = trim((string) $this->post('nome', ''));
        $cor  = $this->corCategoriaValida((string) $this->post('cor', ''));
        if ($nome === '') { $this->flash('error', 'Dê um nome pra categoria.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->atualizarCategoria((int) $id, $nome, $cor);

        $this->flash('success', 'Categoria atualizada!');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

    /** Mesma edição de categoriaAtualizar(), só que em JSON — usada pelo lápis em cada chip do
     * card colapsado (pedido do usuário: corrigir nome/cor de uma categoria sem sair da tela
     * de Lançamentos/Resumo, ex. uma categoria que ficou com nome errado por engano). */
    public function categoriaEditarAjax(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $nome = trim((string) $this->post('nome', ''));
        $cor  = $this->corCategoriaValida((string) $this->post('cor', ''));
        if ($nome === '') { $this->json(['ok' => false, 'erro' => 'Dê um nome pra categoria.'], 400); }

        if (!$this->atualizarCategoria((int) $id, $nome, $cor)) {
            $this->json(['ok' => false, 'erro' => 'Categoria não encontrada.'], 404);
        }

        $this->json(['ok' => true, 'nome' => $nome, 'cor' => $cor]);
    }

    /** Soft delete (ativo=0) — lançamentos antigos que já usavam essa categoria continuam
     * guardando a chave normalmente, só deixam de oferecer ela pra lançamento NOVO. */
    public function categoriaExcluir(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->db->prepare(
            "UPDATE financeiro_pessoal_categorias SET ativo = 0 WHERE id = ? AND usuario_id = ?"
        )->execute([(int) $id, $this->uid]);

        $this->flash('success', 'Categoria excluída.');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

    /**
     * Configurações — só a foto do usuário por enquanto (pedido do usuário: mostrar o rosto
     * dele no lugar do ícone decorativo da marca na trilha de ícones da sidebar, ver
     * .fp-sidebar-brand em layouts/financeiro_pessoal.php).
     */
    public function configuracoes(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);
        $this->view('financeiro_pessoal.configuracoes', [
            'titulo'   => 'Financeiro pessoal — Configurações',
            'liberado' => $liberado,
            'avatar'   => (string) ($_SESSION['usuario']['avatar'] ?? ''),
            'wrapFull' => true,
        ], 'financeiro_pessoal');
    }

    /** Valida formato/tamanho da foto enviada — mesma whitelist já usada em
     *  ProdutoController, nenhuma gravação acontece antes dessa checagem passar. */
    private function validarAvatar(array $file): ?string
    {
        if (($file['size'] ?? 0) > self::AVATAR_TAMANHO_MAX) {
            return 'Imagem maior que 8MB. Reduza o tamanho e tente de novo.';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::AVATAR_MIME_PERMITIDO, true)) {
            return 'Formato de imagem não suportado. Use JPG, PNG, WebP, GIF ou BMP.';
        }
        return null;
    }

    public function salvarAvatar(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }

        if (empty($_FILES['avatar']['tmp_name'])) {
            $this->flash('error', 'Escolha uma foto.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }
        $erro = $this->validarAvatar($_FILES['avatar']);
        if ($erro) {
            $this->flash('error', $erro);
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        $dir = BASE_PATH . '/storage/uploads/avatares';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $arquivo = 'usuario_' . $this->uid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.webp';

        if (!\App\Services\ImageService::paraWebp($_FILES['avatar']['tmp_name'], $dir . '/' . $arquivo, 85, 600)) {
            $this->flash('error', 'Não deu pra processar essa foto. Tente outro arquivo.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        // O antigo só é apagado se também for um arquivo LOCAL nosso — login via Google grava
        // uma URL remota (https://...) nesse mesmo campo, nunca tentamos apagar/unlink uma URL.
        $antigo = (string) ($_SESSION['usuario']['avatar'] ?? '');
        if ($antigo !== '' && !preg_match('~^https?://~i', $antigo)) {
            @unlink($dir . '/' . basename($antigo));
        }

        $this->db->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?")->execute([$arquivo, $this->uid]);
        // Mesmo padrão já usado em outros pontos do sistema (ex.: DashboardController::tema())
        // pra refletir uma mudança de perfil na sessão sem precisar de novo login.
        $_SESSION['usuario']['avatar'] = $arquivo;

        $this->flash('success', 'Foto atualizada!');
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }

    /** Acesso gated por financeiro_pessoal_liberado() — mesmo critério em todo endpoint. */
    private function guard(): void
    {
        if (!financeiro_pessoal_liberado($this->empresa)) {
            $this->json(['ok' => false, 'erro' => 'Financeiro pessoal ainda não está liberado pro seu plano.'], 403);
        }
    }
}
