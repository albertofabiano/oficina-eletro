<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Fixa\PerfilService;

/**
 * Financeiro pessoal ("Fixa") — gasto do USUÁRIO (dono/funcionário), separado de propósito do
 * financeiro da EMPRESA (ver migration 075). Layout próprio (não usa o shell da empresa — ver
 * layouts/financeiro_pessoal.php), pra reforçar visualmente que é separado do sistema da
 * empresa, não mais uma aba dele.
 *
 * Fase 1 (PF/PJ, ver docs do pedido): tudo neste módulo passou a viver DENTRO de um "perfil"
 * (financeiro_pessoal_perfis) — cada usuário sempre tem pelo menos o perfil "Pessoal" (pf),
 * criado automaticamente (ver App\Services\Fixa\PerfilService::perfilAtivo()), e pode ter mais
 * perfis (outro pf, ou um pj — MEI/empresa que ele também administra). TODA tabela de dado
 * (categorias/lançamentos/eventos) agora filtra por `usuario_id` E `perfil_id` juntos (defesa
 * em dupla camada, pedido explícito) — trocar de perfil no seletor do topo troca o que aparece
 * em TODA tela deste módulo, sem precisar de outra URL.
 */
class FinanceiroPessoalController extends Controller
{
    private \PDO $db;
    private int $eid;
    private int $uid;
    private array $empresa;
    private bool $liberado;
    private array $perfil;
    private int $perfilId;
    private array $assinaturaFixa = []; // assinatura standalone do usuário, se tiver (vazio = Fixa de graça pelo plano da empresa, ou nenhuma)
    private bool $apenasExportacao = false; // Etapa 3: bloqueada/cancelada ainda dentro da retenção — só lê/exporta, não cria/edita

    // Mesma whitelist/limite já usado em ProdutoController pra upload de imagem — sem
    // compartilhar uma constante entre os dois controllers (cada um já tem a própria cópia
    // nesse projeto, ver histórico), só o valor é igual.
    private const AVATAR_MIME_PERMITIDO = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const AVATAR_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB

    // Anexo de lançamento (comprovante) — imagem OU PDF, mesmo teto de tamanho do avatar.
    private const ANEXO_MIME_PERMITIDO = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    private const ANEXO_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->eid = $this->empresaId();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->eid]);
        $this->empresa = $st->fetch() ?: [];

        // Assinatura Fixa STANDALONE (quem não tem Fixa de graça pelo plano da empresa) — ver
        // AssinaturaService e financeiro_pessoal_liberado(), que checa os dois caminhos.
        try {
            $this->assinaturaFixa = \App\Services\Fixa\AssinaturaService::doUsuario($this->db, $this->uid) ?? [];
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::__construct (assinatura) — ' . $e->getMessage());
        }
        $this->empresa['_fixa_standalone_liberado'] = $this->assinaturaFixa
            && \App\Services\Fixa\AssinaturaService::acessoCompleto($this->assinaturaFixa);

        $this->liberado = financeiro_pessoal_liberado($this->empresa);
        $this->apenasExportacao = $this->assinaturaFixa
            && \App\Services\Fixa\AssinaturaService::somenteExportacao($this->assinaturaFixa);
        // Quem tem acesso via plano da empresa nunca fica "apenas exportação" por causa de uma
        // assinatura standalone antiga bloqueada — o plano da empresa manda, se ele libera.
        if ($this->empresa['_fixa_standalone_liberado'] === false
            && !empty($this->empresa['reivindicada'])
            && in_array($this->empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true)) {
            $this->apenasExportacao = false;
        }

        $this->perfil   = $this->liberado ? PerfilService::perfilAtivo($this->db, $this->uid) : [];
        $this->perfilId = (int) ($this->perfil['id'] ?? 0);
    }

    /** Bloqueio de escrita (Etapa 3: assinatura standalone bloqueada/cancelada dentro da
     *  retenção de 30 dias) — guard à parte de guard()/guardFlash(), porque aqui o módulo
     *  continua LIBERADO (dá pra ver/exportar), só não pode criar/editar/excluir. */
    private function guardEscrita(): void
    {
        if ($this->apenasExportacao) {
            $this->json(['ok' => false, 'erro' => 'Sua assinatura do Carteira Fixa está bloqueada. Você ainda pode exportar seus dados, mas não criar ou editar lançamentos. Regularize o pagamento pra voltar a usar normalmente.'], 403);
        }
    }

    /** Acesso gated por financeiro_pessoal_liberado() — mesmo critério em todo endpoint AJAX. */
    private function guard(): void
    {
        if (!$this->liberado) {
            $this->json(['ok' => false, 'erro' => 'Financeiro pessoal ainda não está liberado pro seu plano.'], 403);
        }
    }

    /** Mesma cautela já documentada antes: nunca deixa uma falha transitória na BUSCA de
     *  categorias derrubar a ação principal com um 500 confuso — cai no fallback 'outros'. */
    private function categoriaValidaOuPadrao(string $enviada): string
    {
        try {
            $categorias = PerfilService::categoriasDoPerfil($this->db, $this->perfilId, $this->perfil['tipo']);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::categoriaValidaOuPadrao — ' . $e->getMessage());
            return 'outros';
        }
        return array_key_exists($enviada, $categorias) ? $enviada : (array_key_first($categorias) ?? 'outros');
    }

    private function categoriasDoPerfilOuVazio(): array
    {
        try {
            return PerfilService::categoriasDoPerfil($this->db, $this->perfilId, $this->perfil['tipo']);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::categoriasDoPerfilOuVazio — ' . $e->getMessage());
            return [];
        }
    }

    /** Conta válida pro perfil ativo — cai na primeira conta não-arquivada se a enviada não
     *  existir/não for desse perfil (nunca bloqueia salvar um lançamento por isso). */
    private function contaValidaOuPadrao(string $enviada): ?int
    {
        $contas = PerfilService::contasDoPerfil($this->db, $this->perfilId);
        if (!$contas) return null;
        $enviadaInt = (int) $enviada;
        foreach ($contas as $c) {
            if ((int) $c['id'] === $enviadaInt) return $enviadaInt;
        }
        return (int) $contas[0]['id'];
    }

    /** Gera lançamentos pendentes de contas recorrentes (mês atual + próximo) — best-effort,
     *  nunca derruba a página se falhar; chamado no início de toda tela que lê lançamentos ou
     *  agenda (ver RecorrenteService::gerarPendentes()). */
    private function gerarRecorrentesPendentes(): void
    {
        try {
            \App\Services\Fixa\RecorrenteService::gerarPendentes($this->db, $this->uid, $this->perfilId);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::gerarRecorrentesPendentes — ' . $e->getMessage());
        }
    }

    // ───────────────────────────── Perfil ativo (seletor do topo) ─────────────────────────

    /** Todo perfil não-arquivado do usuário — usado pelo seletor do topo (layout) e pela view
     *  de Configurações (gerenciar perfis). */
    private function perfisParaView(): array
    {
        if (!$this->liberado) return [];
        try {
            return PerfilService::perfisDoUsuario($this->db, $this->uid, true);
        } catch (\Throwable $e) {
            error_log('FinanceiroPessoal::perfisParaView — ' . $e->getMessage());
            return [];
        }
    }

    /** Troca o perfil ativo da sessão — form simples (<select onchange="submit()">), não AJAX,
     *  porque troca praticamente tudo que a página mostra (mais simples recarregar). */
    public function perfilAtivoTrocar(): void
    {
        if (!$this->liberado) { $this->redirect(url('/financeiro-pessoal')); }
        if (!csrf_verify()) { $this->redirect(url('/financeiro-pessoal')); }

        $perfilId = (int) $this->post('perfil_id', 0);
        $perfil = PerfilService::pertenceAoUsuario($this->db, $perfilId, $this->uid);
        if ($perfil && empty($perfil['arquivado'])) {
            $_SESSION['fixa_perfil_id'] = $perfilId;
        }
        $this->redirect(url('/financeiro-pessoal'));
    }

    public function perfilCriar(): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }

        $tipo      = (string) $this->post('tipo', 'pf');
        $nome      = trim((string) $this->post('nome', ''));
        $documento = trim((string) $this->post('documento', ''));
        $cor       = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->post('cor', '')) ? (string) $this->post('cor') : '#8C7CFF';
        $regime    = (string) $this->post('regime', '');

        if ($nome === '') { $this->flash('error', 'Dê um nome pro perfil.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }
        if ($documento !== '' && !documento_valido($documento)) {
            $this->flash('error', 'CPF/CNPJ inválido.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        $novo = PerfilService::criarPerfil($this->db, $this->uid, $tipo, $nome, $documento, $cor, $regime);
        $_SESSION['fixa_perfil_id'] = $novo['id'];

        $this->flash('success', 'Perfil criado!');
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }

    public function perfilAtualizar(string $id): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }

        $nome      = trim((string) $this->post('nome', ''));
        $documento = trim((string) $this->post('documento', ''));
        $cor       = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->post('cor', '')) ? (string) $this->post('cor') : '#8C7CFF';
        $regime    = (string) $this->post('regime', '');

        if ($nome === '') { $this->flash('error', 'Dê um nome pro perfil.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }
        if ($documento !== '' && !documento_valido($documento)) {
            $this->flash('error', 'CPF/CNPJ inválido.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        if (!PerfilService::atualizarPerfil($this->db, (int) $id, $this->uid, $nome, $documento, $cor, $regime)) {
            $this->flash('error', 'Perfil não encontrado.');
        } else {
            $this->flash('success', 'Perfil atualizado!');
        }
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }

    public function perfilArquivar(string $id): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }

        $arquivar = $this->post('arquivar', '1') === '1';
        $ok = PerfilService::arquivarPerfil($this->db, (int) $id, $this->uid, $arquivar);
        if (!$ok) {
            $this->flash('error', $arquivar ? 'Não dá pra arquivar o único perfil ativo.' : 'Perfil não encontrado.');
        } else {
            if ($arquivar && (int) $id === (int) ($_SESSION['fixa_perfil_id'] ?? 0)) {
                unset($_SESSION['fixa_perfil_id']); // perfilAtivo() resolve outro sozinho no próximo acesso
            }
            $this->flash('success', $arquivar ? 'Perfil arquivado.' : 'Perfil reativado.');
        }
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }

    /** Mesmo espírito de guard(), só que pra endpoint de form+redirect (flash), não JSON. */
    private function guardFlash(): void
    {
        if (!$this->liberado) {
            $this->flash('error', 'Financeiro pessoal ainda não está liberado pro seu plano.');
            $this->redirect(url('/financeiro-pessoal'));
        }
    }

    // ───────────────────────────────────── Resumo (dashboard) ─────────────────────────────

    public function index(): void
    {
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $categorias = [];
        $resumo = null;
        $proximosVencimentos = [];
        $ultimosLancamentos = [];
        if ($this->liberado) {
            $this->gerarRecorrentesPendentes();
            try {
                $categorias = PerfilService::categoriasDoPerfil($this->db, $this->perfilId, $this->perfil['tipo']);
            } catch (\Throwable $e) {
                error_log('FinanceiroPessoal::index — ' . $e->getMessage());
            }
            $resumo = $this->montarResumoMensal($mes);

            $pvSt = $this->db->prepare(
                "SELECT id, tipo, categoria, descricao, valor, vencimento, data_hora
                 FROM financeiro_pessoal_lancamentos
                 WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL
                 ORDER BY COALESCE(vencimento, DATE(data_hora)) ASC
                 LIMIT 5"
            );
            $pvSt->execute([$this->uid, $this->perfilId]);
            $proximosVencimentos = $pvSt->fetchAll();

            $ulSt = $this->db->prepare(
                "SELECT id, tipo, categoria, descricao, valor, data_hora, pago_em, vencimento, hora_informada
                 FROM financeiro_pessoal_lancamentos
                 WHERE usuario_id = ? AND perfil_id = ?
                 ORDER BY data_hora DESC, id DESC
                 LIMIT 5"
            );
            $ulSt->execute([$this->uid, $this->perfilId]);
            $ultimosLancamentos = $ulSt->fetchAll();
        }

        $hora = (int) date('G');
        $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

        $this->view('financeiro_pessoal.index', [
            'titulo'               => 'Financeiro pessoal',
            'liberado'             => $this->liberado,
            'perfil'               => $this->perfil,
            'perfis'               => $this->perfisParaView(),
            'saudacao'              => $saudacao,
            'mes'                  => $mes,
            'mesAnteriorNav'       => $mesAnteriorNav,
            'mesProximoNav'        => $mesProximoNav,
            'categorias'           => $categorias,
            'resumo'               => $resumo,
            'proximosVencimentos'  => $proximosVencimentos,
            'ultimosLancamentos'   => $ultimosLancamentos,
            'wrapFull'             => true,
        ], 'financeiro_pessoal');
    }

    /**
     * Resumo do mês ($mes, 'YYYY-MM') — pago vs. em aberto (realizado/previsto), saldo atual
     * (de TODAS as contas não-arquivadas do perfil, desde sempre) e saldo previsto até o fim do
     * mês navegado, série diária (despesa/receita, pago vs. em aberto) e por categoria.
     */
    private function montarResumoMensal(string $mes): array
    {
        $mesAtual     = $mes;
        $mesAnterior  = date('Y-m', strtotime($mes . '-01 -1 month'));
        $fimMesNav    = date('Y-m-t', strtotime($mes . '-01'));
        $hoje         = date('Y-m-d');

        // Toda a vida do perfil (não só a janela do mês) — precisa pro saldo ATUAL (cumulativo,
        // não zera a cada mês) e pro "a pagar/a receber até o fim do mês navegado" (inclui
        // atraso de mês anterior, que ainda vai afetar o saldo).
        $st = $this->db->prepare(
            "SELECT tipo, categoria, descricao, valor, data_hora, vencimento, pago_em
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND perfil_id = ?
             ORDER BY data_hora"
        );
        $st->execute([$this->uid, $this->perfilId]);
        $linhas = $st->fetchAll();

        $receitasPagasTotal = 0.0;
        $despesasPagasTotal = 0.0;
        $aReceberAteFim = 0.0;
        $aPagarAteFim = 0.0;

        $gastoPagoMes = 0.0;
        $gastoAbertoMes = 0.0;
        $recebidoPagoMes = 0.0;
        $recebidoAbertoMes = 0.0;
        $gastoPagoMesAnterior = 0.0;
        $recebidoPagoMesAnterior = 0.0;

        $qtdLancamentosMes = 0;
        $porDiaPago = [];
        $porDiaAberto = [];
        $porDiaPagoReceita = [];
        $porDiaAbertoReceita = [];
        $porCategoria = [];
        $maiorGasto = null;

        foreach ($linhas as $l) {
            $valor = (float) $l['valor'];
            $pago  = !empty($l['pago_em']);
            $efetiva = $l['vencimento'] ?: substr($l['data_hora'], 0, 10); // data que "conta" pro agrupamento
            $ymEfetiva = substr($efetiva, 0, 7);
            $ymLanc = substr($l['data_hora'], 0, 7);

            if ($l['tipo'] === 'receita') {
                if ($pago) { $receitasPagasTotal += $valor; }
                elseif ($efetiva <= $fimMesNav) { $aReceberAteFim += $valor; }

                if ($pago && $ymLanc === $mesAtual) {
                    $recebidoPagoMes += $valor;
                    $qtdLancamentosMes++;
                    $dia = (int) substr($l['data_hora'], 8, 2);
                    $porDiaPagoReceita[$dia] = ($porDiaPagoReceita[$dia] ?? 0) + $valor;
                }
                if (!$pago && $ymEfetiva === $mesAtual) {
                    $recebidoAbertoMes += $valor;
                    $qtdLancamentosMes++;
                    $dia = (int) substr($efetiva, 8, 2);
                    $porDiaAbertoReceita[$dia] = ($porDiaAbertoReceita[$dia] ?? 0) + $valor;
                }
                if ($pago && $ymLanc === $mesAnterior) { $recebidoPagoMesAnterior += $valor; }
                continue;
            }

            if ($l['tipo'] === 'transferencia') { continue; } // não entra em gasto/receita, só move entre contas

            // despesa daqui pra baixo
            if ($pago) { $despesasPagasTotal += $valor; }
            elseif ($efetiva <= $fimMesNav) { $aPagarAteFim += $valor; }

            if ($pago && $ymLanc === $mesAtual) {
                $gastoPagoMes += $valor;
                $qtdLancamentosMes++;
                $dia = (int) substr($l['data_hora'], 8, 2);
                $porDiaPago[$dia] = ($porDiaPago[$dia] ?? 0) + $valor;
                $porCategoria[$l['categoria']] = ($porCategoria[$l['categoria']] ?? 0) + $valor;
                if ($maiorGasto === null || $valor > $maiorGasto['valor']) {
                    $maiorGasto = ['descricao' => $l['descricao'], 'valor' => $valor, 'categoria' => $l['categoria']];
                }
            }
            if (!$pago && $ymEfetiva === $mesAtual) {
                $gastoAbertoMes += $valor;
                $qtdLancamentosMes++;
                $dia = (int) substr($efetiva, 8, 2);
                $porDiaAberto[$dia] = ($porDiaAberto[$dia] ?? 0) + $valor;
                $porCategoria[$l['categoria']] = ($porCategoria[$l['categoria']] ?? 0) + $valor;
            }
            if ($pago && $ymLanc === $mesAnterior) { $gastoPagoMesAnterior += $valor; }
        }

        arsort($porCategoria);

        $saldoAtual = $this->saldoAtualDoPerfil($receitasPagasTotal, $despesasPagasTotal);
        $saldoPrevisto = fixa_saldo_previsto($saldoAtual, $aReceberAteFim, $aPagarAteFim);

        // Caixinhas — total guardado (saldo acumulado, nunca zera) + quanto foi guardado no mês
        // NAVEGADO especificamente (card "Guardado em caixinhas" do Resumo, ver CaixinhaService).
        $caixinhasTotal = \App\Services\Fixa\CaixinhaService::saldoTotalCaixinhas($this->db, $this->perfilId) / 100;
        $caixinhasGuardadoMes = \App\Services\Fixa\CaixinhaService::guardadoNoMes($this->db, $this->perfilId, $mesAtual) / 100;

        $saldoMesAtual = $recebidoPagoMes + $recebidoAbertoMes - $gastoPagoMes - $gastoAbertoMes;
        $saldoMesAnterior = $recebidoPagoMesAnterior - $gastoPagoMesAnterior;

        $variacao = function (float $atual, float $anterior): ?int {
            if ($anterior <= 0) return null;
            return (int) round((($atual - $anterior) / $anterior) * 100);
        };

        $diasNoMes = (int) date('t', strtotime($mes . '-01'));
        $serieDiasPago = [];
        $serieDiasAberto = [];
        $serieDiasPagoReceita = [];
        $serieDiasAbertoReceita = [];
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $serieDiasPago[]          = round($porDiaPago[$d] ?? 0, 2);
            $serieDiasAberto[]        = round($porDiaAberto[$d] ?? 0, 2);
            $serieDiasPagoReceita[]   = round($porDiaPagoReceita[$d] ?? 0, 2);
            $serieDiasAbertoReceita[] = round($porDiaAbertoReceita[$d] ?? 0, 2);
        }

        return [
            'gastoPagoMes'           => $gastoPagoMes,
            'gastoAbertoMes'         => $gastoAbertoMes,
            'recebidoPagoMes'        => $recebidoPagoMes,
            'recebidoAbertoMes'      => $recebidoAbertoMes,
            'saldoMesAtual'          => $saldoMesAtual,
            'saldoAtual'             => $saldoAtual,
            'saldoPrevisto'          => $saldoPrevisto,
            'variacaoGastoPct'       => $variacao($gastoPagoMes, $gastoPagoMesAnterior),
            'variacaoRecebidoPct'    => $variacao($recebidoPagoMes, $recebidoPagoMesAnterior),
            'variacaoSaldoPct'       => $variacao($saldoMesAtual, $saldoMesAnterior),
            'gastoPagoMesAnterior'   => $gastoPagoMesAnterior,
            'recebidoPagoMesAnterior' => $recebidoPagoMesAnterior,
            'qtdLancamentos'         => $qtdLancamentosMes,
            'serieDiasPago'          => $serieDiasPago,
            'serieDiasAberto'        => $serieDiasAberto,
            'serieDiasPagoReceita'   => $serieDiasPagoReceita,
            'serieDiasAbertoReceita' => $serieDiasAbertoReceita,
            'porCategoria'           => $porCategoria,
            'maiorGasto'             => $maiorGasto,
            'hoje'                   => $hoje,
            'caixinhasTotal'         => $caixinhasTotal,
            'caixinhasGuardadoMes'   => $caixinhasGuardadoMes,
        ];
    }

    /** Saldo atual = soma, por TODA conta não-arquivada do perfil, de
     *  saldo_inicial + receitas pagas − despesas pagas daquela conta (sempre, não só no mês),
     *  menos o que está guardado em caixinhas (dinheiro guardado se comporta como se tivesse
     *  saído da conta — mesmo princípio aplicado em FixaContasController::index()). */
    private function saldoAtualDoPerfil(float $receitasPagasTotalPerfil, float $despesasPagasTotalPerfil): float
    {
        // Mantido simples (soma agregada do perfil inteiro, não conta a conta) porque é isso
        // que os cards/gráfico do Resumo mostram; a tela de Contas (FixaContasController) já
        // calcula o saldo POR CONTA separadamente, com a mesma fórmula.
        $stContas = $this->db->prepare(
            "SELECT COALESCE(SUM(saldo_inicial), 0) FROM financeiro_pessoal_contas WHERE perfil_id = ? AND arquivada = 0"
        );
        $stContas->execute([$this->perfilId]);
        $saldoInicialTotal = (float) $stContas->fetchColumn();

        $saldo = fixa_saldo_atual($saldoInicialTotal, $receitasPagasTotalPerfil, $despesasPagasTotalPerfil);
        $saldo -= \App\Services\Fixa\CaixinhaService::saldoTotalCaixinhas($this->db, $this->perfilId) / 100;

        return round($saldo, 2);
    }

    // ───────────────────────────────── Lançamentos (lista própria) ────────────────────────

    public function lancamentos(): void
    {
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $lancamentos = [];
        $categorias = [];
        $contas = [];
        $resumo = null;
        if ($this->liberado) {
            $this->gerarRecorrentesPendentes();
            try {
                $categorias = PerfilService::categoriasDoPerfil($this->db, $this->perfilId, $this->perfil['tipo']);
            } catch (\Throwable $e) {
                error_log('FinanceiroPessoal::lancamentos — ' . $e->getMessage());
            }
            $contas = PerfilService::contasDoPerfil($this->db, $this->perfilId);
            $lancamentos = $this->buscarLancamentosDoMes($mes);
            // Resumo do mês no topo (pedido explícito da spec) — mesmo cálculo já usado no
            // Dashboard (montarResumoMensal()), só a view exibe um recorte mais compacto dele
            // (Gasto/Recebido/Saldo do mês), sem duplicar "Saldo atual"/gráfico, que já são
            // conteúdo próprio do Dashboard.
            $resumo = $this->montarResumoMensal($mes);
        }
        // Contador de leituras do scanner (Etapa 4, "contador visível ao usuário") — calculado
        // mesmo sem $this->liberado ser falso não importa, fixa_scanner_verificar() já degrada
        // sozinho se não achar a empresa.
        $limiteScanner = $this->liberado ? fixa_scanner_verificar($this->uid, $this->empresa) : null;

        $this->view('financeiro_pessoal.lancamentos', [
            'titulo'          => 'Financeiro pessoal — Lançamentos',
            'liberado'        => $this->liberado,
            'perfil'          => $this->perfil,
            'perfis'          => $this->perfisParaView(),
            'limiteScanner'   => $limiteScanner,
            'mes'             => $mes,
            'mesAnteriorNav'  => $mesAnteriorNav,
            'mesProximoNav'   => $mesProximoNav,
            'lancamentos'     => $lancamentos,
            'categorias'      => $categorias,
            'contas'          => $contas,
            'resumo'          => $resumo,
            'wrapFull'        => true,
        ], 'financeiro_pessoal');
    }

    private function buscarLancamentosDoMes(string $mes): array
    {
        $inicioMes = $mes . '-01 00:00:00';
        $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));
        $st = $this->db->prepare(
            "SELECT id, tipo, categoria, descricao, valor, data_hora, vencimento, pago_em,
                    data_competencia, observacao, anexo_url, codigo_barras, pix_copia_cola,
                    hora_informada, conta_id, origem
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND perfil_id = ? AND data_hora BETWEEN ? AND ?
             ORDER BY data_hora DESC"
        );
        $st->execute([$this->uid, $this->perfilId, $inicioMes, $fimMes]);
        return $st->fetchAll();
    }

    /** Lista em JSON — usado pelo JS depois de criar/editar/excluir, sem reload. */
    public function listarAjax(): void
    {
        $this->guard();
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $this->json(['ok' => true, 'lancamentos' => $this->buscarLancamentosDoMes($mes)]);
    }

    // ───────────────────────────────────────── Agenda ──────────────────────────────────────

    /**
     * Agenda — eventos manuais (`financeiro_pessoal_eventos`) + lançamentos em aberto do
     * perfil, lidos DIRETO (sem copiar nada pra `financeiro_pessoal_eventos`, pedido
     * explícito). Cada lançamento em aberto com `vencimento` dentro do mês navegado vira um
     * "evento virtual" (id prefixado `lanc-`, nunca colide com id de evento de verdade) — a
     * view já sabe diferenciar os dois pelo prefixo.
     */
    public function calendario(): void
    {
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $mesAnteriorNav = date('Y-m', strtotime($mes . '-01 -1 month'));
        $mesProximoNav  = date('Y-m', strtotime($mes . '-01 +1 month'));

        $eventos = [];
        $vencimentosDoMes = [];
        if ($this->liberado) {
            $this->gerarRecorrentesPendentes();
            $eventos = $this->buscarEventosDoMes($mes);
            $vencimentosDoMes = $this->buscarVencimentosDoMes($mes);
        }

        $this->view('financeiro_pessoal.calendario', [
            'titulo'            => 'Financeiro pessoal — Agenda',
            'liberado'          => $this->liberado,
            'perfil'            => $this->perfil,
            'perfis'            => $this->perfisParaView(),
            'mes'               => $mes,
            'mesAnteriorNav'    => $mesAnteriorNav,
            'mesProximoNav'     => $mesProximoNav,
            'eventos'           => $eventos,
            'vencimentosDoMes'  => $vencimentosDoMes,
            'wrapFull'          => true,
        ], 'financeiro_pessoal');
    }

    private function buscarEventosDoMes(string $mes): array
    {
        $inicioMes = $mes . '-01 00:00:00';
        $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));
        $st = $this->db->prepare(
            "SELECT id, titulo, data_hora, lancamento_id
             FROM financeiro_pessoal_eventos
             WHERE usuario_id = ? AND perfil_id = ? AND data_hora BETWEEN ? AND ?
             ORDER BY data_hora ASC"
        );
        $st->execute([$this->uid, $this->perfilId, $inicioMes, $fimMes]);
        return $st->fetchAll();
    }

    /** Lançamentos em ABERTO (pago_em nulo) cujo vencimento cai no mês navegado — é isso que
     *  vira "evento" na grade da Agenda, lido direto, nunca gravado em `_eventos`. */
    private function buscarVencimentosDoMes(string $mes): array
    {
        $inicioMes = $mes . '-01';
        $fimMes = date('Y-m-t', strtotime($inicioMes . '-01'));
        $st = $this->db->prepare(
            "SELECT id, tipo, descricao, valor, vencimento
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL
               AND vencimento BETWEEN ? AND ?
             ORDER BY vencimento ASC"
        );
        $st->execute([$this->uid, $this->perfilId, $inicioMes, $fimMes]);
        return $st->fetchAll();
    }

    public function eventosAjax(): void
    {
        $this->guard();
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }
        $this->json([
            'ok' => true,
            'eventos' => $this->buscarEventosDoMes($mes),
            'vencimentos' => $this->buscarVencimentosDoMes($mes),
        ]);
    }

    public function eventoSalvar(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $titulo = trim((string) $this->post('titulo', ''));
        $dataHora = (string) $this->post('data_hora', '');
        if ($titulo === '') { $this->json(['ok' => false, 'erro' => 'Dê um título pro evento.'], 400); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dataHora)) {
            $this->json(['ok' => false, 'erro' => 'Informe uma data e hora válidas.'], 400);
        }
        $dataHora = str_replace('T', ' ', $dataHora) . ':00';

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_eventos (usuario_id, perfil_id, titulo, data_hora) VALUES (?, ?, ?, ?)"
        )->execute([$this->uid, $this->perfilId, $titulo, $dataHora]);

        $this->json(['ok' => true, 'id' => (int) $this->db->lastInsertId()]);
    }

    public function eventoAtualizar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $titulo = trim((string) $this->post('titulo', ''));
        $dataHora = (string) $this->post('data_hora', '');
        if ($titulo === '') { $this->json(['ok' => false, 'erro' => 'Dê um título pro evento.'], 400); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dataHora)) {
            $this->json(['ok' => false, 'erro' => 'Informe uma data e hora válidas.'], 400);
        }
        $dataHora = str_replace('T', ' ', $dataHora) . ':00';

        $st = $this->db->prepare(
            "UPDATE financeiro_pessoal_eventos SET titulo = ?, data_hora = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$titulo, $dataHora, (int) $id, $this->uid, $this->perfilId]);
        if ($st->rowCount() === 0) { $this->json(['ok' => false, 'erro' => 'Evento não encontrado.'], 404); }

        $this->json(['ok' => true]);
    }

    public function eventoExcluir(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->prepare(
            "DELETE FROM financeiro_pessoal_eventos WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        )->execute([(int) $id, $this->uid, $this->perfilId]);

        $this->json(['ok' => true]);
    }

    // ───────────────────────── "Contas e débitos" (listas/itens) — CÓDIGO MORTO ───────────
    // Nenhuma view usa mais nada disto (removido da UI antes da Fase 1 — ver comentário em
    // lancamentos.php: "'Contas e débitos' foi removido do sistema de propósito"). Mantido
    // 100% intocado aqui de propósito (decisão explícita: não remover sem confirmação à parte,
    // só sinalizado como achado) — continua escopado só por usuario_id, SEM perfil_id, porque
    // financeiro_pessoal_listas/_itens não ganharam essa coluna em nenhuma migration da Fase 1.

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

    // ───────────────────────────── Contas recorrentes (aluguel etc.) ──────────────────────
    // Ver App\Services\Fixa\RecorrenteService — este bloco é só a casca HTTP (validação de
    // entrada + csrf + json), toda a regra de geração/idempotência vive lá.

    public function listarRecorrentesAjax(): void
    {
        $this->guard();
        $this->json(['ok' => true, 'recorrentes' => \App\Services\Fixa\RecorrenteService::listar($this->db, $this->perfilId)]);
    }

    /** Lê e valida os campos comuns a criar()/atualizar() — devolve erro pronto pra responder
     *  (string) se algo for inválido, ou o array de dados já normalizado. */
    private function dadosRecorrenteDoPost(): array
    {
        $tipo       = $this->post('tipo', 'despesa') === 'receita' ? 'receita' : 'despesa';
        $categoria  = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));
        $descricao  = trim((string) $this->post('descricao', ''));
        $notas      = trim((string) $this->post('notas', ''));
        $notas      = $notas !== '' ? mb_substr($notas, 0, 500) : null;
        $valor      = moeda_float($this->post('valor', 0));
        $dia        = max(1, min(31, (int) $this->post('dia_vencimento', 0)));
        $contaId    = (string) $this->post('conta_id', '') !== '' ? $this->contaValidaOuPadrao((string) $this->post('conta_id', '')) : null;
        $dataInicio = trim((string) $this->post('data_inicio', ''));
        $dataFim    = trim((string) $this->post('data_fim', ''));

        if ($descricao === '') { return ['erro' => 'Dê um nome pra essa conta recorrente (ex.: Aluguel).']; }
        if ($valor <= 0) { return ['erro' => 'Informe um valor maior que zero.']; }
        if ((int) $this->post('dia_vencimento', 0) < 1 || (int) $this->post('dia_vencimento', 0) > 31) {
            return ['erro' => 'O dia do vencimento precisa ser entre 1 e 31.'];
        }
        if ($dataInicio === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) {
            return ['erro' => 'Informe a data de início.'];
        }
        if ($dataFim !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
                return ['erro' => 'Data de término inválida.'];
            }
            if ($dataFim < $dataInicio) {
                return ['erro' => 'A data de término precisa ser igual ou depois do início.'];
            }
        }

        return [
            'tipo' => $tipo, 'categoria' => $categoria, 'descricao' => $descricao, 'notas' => $notas,
            'valor' => $valor, 'dia_vencimento' => $dia, 'conta_id' => $contaId,
            'data_inicio' => $dataInicio, 'data_fim' => $dataFim !== '' ? $dataFim : null,
        ];
    }

    public function recorrenteSalvar(): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $dados = $this->dadosRecorrenteDoPost();
        if (isset($dados['erro'])) { $this->json(['ok' => false, 'erro' => $dados['erro']], 400); }

        $id = \App\Services\Fixa\RecorrenteService::criar($this->db, $this->uid, $this->perfilId, $dados);
        // Gera na hora (não espera o próximo page load) — quem acabou de cadastrar "Aluguel"
        // quer ver o lançamento deste mês aparecer já na lista, sem precisar recarregar duas
        // vezes. Best-effort, mesma cautela de gerarRecorrentesPendentes().
        $this->gerarRecorrentesPendentes();

        $this->json(['ok' => true, 'id' => $id]);
    }

    public function recorrenteAtualizar(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $dados = $this->dadosRecorrenteDoPost();
        if (isset($dados['erro'])) { $this->json(['ok' => false, 'erro' => $dados['erro']], 400); }

        if (!\App\Services\Fixa\RecorrenteService::atualizar($this->db, (int) $id, $this->uid, $this->perfilId, $dados)) {
            $this->json(['ok' => false, 'erro' => 'Conta recorrente não encontrada.'], 404);
        }
        $this->json(['ok' => true]);
    }

    /** Pausar/retomar — não apaga nada, só liga/desliga a geração de novos lançamentos
     *  (ver RecorrenteService::alternarAtivo()). */
    public function recorrentePausar(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $ativo = $this->post('ativo', '1') === '1';
        if (!\App\Services\Fixa\RecorrenteService::alternarAtivo($this->db, (int) $id, $this->uid, $this->perfilId, $ativo)) {
            $this->json(['ok' => false, 'erro' => 'Conta recorrente não encontrada.'], 404);
        }
        if ($ativo) { $this->gerarRecorrentesPendentes(); }
        $this->json(['ok' => true]);
    }

    public function recorrenteExcluir(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $removido = \App\Services\Fixa\RecorrenteService::excluir($this->db, (int) $id, $this->uid, $this->perfilId);
        $this->json(['ok' => true, 'removido' => $removido]);
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

    public function salvar(): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $tipo       = in_array($this->post('tipo', 'despesa'), ['receita', 'despesa', 'transferencia'], true) ? $this->post('tipo') : 'despesa';
        $categoria  = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));
        $descricao  = trim((string) $this->post('descricao', ''));
        $valor      = moeda_float($this->post('valor', 0));
        $dataHora   = (string) $this->post('data_hora', date('Y-m-d H:i:s'));
        $origem     = $this->post('origem', '') === 'foto' ? 'foto' : 'manual';
        $vencimento = $this->dataOpcionalOuNull($this->post('vencimento'));
        $pagoEm     = $this->dataOpcionalOuNull($this->post('pago_em'));
        $dataCompet = $this->dataOpcionalOuNull($this->post('data_competencia'));
        $observacao = trim((string) $this->post('observacao', ''));
        $observacao = $observacao !== '' ? mb_substr($observacao, 0, 500) : null;
        $anexoUrl   = trim((string) $this->post('anexo_url', '')) ?: null;
        $codBarras  = trim((string) $this->post('codigo_barras', '')) ?: null;
        $pixCola    = trim((string) $this->post('pix_copia_cola', '')) ?: null;
        $contaId    = $this->contaValidaOuPadrao((string) $this->post('conta_id', ''));
        $horaInformada = $this->post('hora_informada', '1') === '0' ? 0 : 1;

        if ($descricao === '') { $this->json(['ok' => false, 'erro' => 'Informe uma descrição.'], 400); }
        if ($valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }
        if (!strtotime($dataHora)) { $dataHora = date('Y-m-d H:i:s'); }

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_lancamentos
                (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor, data_hora,
                 vencimento, pago_em, data_competencia, observacao, anexo_url, codigo_barras,
                 pix_copia_cola, hora_informada, origem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $this->uid, $this->perfilId, $contaId, $tipo, $categoria, $descricao, $valor, $dataHora,
            $vencimento, $pagoEm, $dataCompet, $observacao, $anexoUrl, $codBarras,
            $pixCola, $horaInformada, $origem,
        ]);

        $this->json(['ok' => true, 'id' => (int) $this->db->lastInsertId()]);
    }

    /** Valida "YYYY-MM-DD" vindo de um <input type="date"> — qualquer outra coisa (vazio,
     *  formato inválido, campo nem enviado) vira NULL, nunca grava lixo em data opcional. */
    private function dataOpcionalOuNull(?string $v): ?string
    {
        $v = trim((string) $v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    /** Edita um lançamento já existente — mesma validação de salvar(). Campos opcionais
     *  (vencimento/pago_em/data_competencia/observacao/anexo/código de barras/pix/conta) só
     *  são tocados quando vêm no POST — trocarCategoria() (chip rápido) manda só tipo/
     *  categoria/descricao/valor, sem o resto, e não pode apagar o que já estava salvo. */
    public function atualizar(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $tipo      = in_array($this->post('tipo', 'despesa'), ['receita', 'despesa', 'transferencia'], true) ? $this->post('tipo') : 'despesa';
        $categoria = $this->categoriaValidaOuPadrao((string) $this->post('categoria', ''));
        $descricao = trim((string) $this->post('descricao', ''));
        $valor     = moeda_float($this->post('valor', 0));

        if ($descricao === '') { $this->json(['ok' => false, 'erro' => 'Informe uma descrição.'], 400); }
        if ($valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }

        $dono = $this->db->prepare(
            "SELECT vencimento, pago_em, data_competencia, observacao, anexo_url, codigo_barras,
                    pix_copia_cola, hora_informada, conta_id
             FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $dono->execute([(int) $id, $this->uid, $this->perfilId]);
        $atual = $dono->fetch();
        if (!$atual) { $this->json(['ok' => false, 'erro' => 'Lançamento não encontrado.'], 404); }

        $body = array_merge($_POST, $this->jsonBody());
        $campoOuAtual = function (string $campo, $valorAtual, bool $isData = false) use ($body) {
            if (!array_key_exists($campo, $body)) return $valorAtual;
            return $isData ? $this->dataOpcionalOuNull((string) $body[$campo]) : ((string) $body[$campo] !== '' ? $body[$campo] : null);
        };

        $vencimento = $campoOuAtual('vencimento', $atual['vencimento'], true);
        $pagoEm     = $campoOuAtual('pago_em', $atual['pago_em'], true);
        $dataCompet = $campoOuAtual('data_competencia', $atual['data_competencia'], true);
        $observacao = $campoOuAtual('observacao', $atual['observacao']);
        $observacao = $observacao !== null ? mb_substr(trim((string) $observacao), 0, 500) : null;
        $anexoUrl   = $campoOuAtual('anexo_url', $atual['anexo_url']);
        $codBarras  = $campoOuAtual('codigo_barras', $atual['codigo_barras']);
        $pixCola    = $campoOuAtual('pix_copia_cola', $atual['pix_copia_cola']);
        $contaId    = array_key_exists('conta_id', $body) ? $this->contaValidaOuPadrao((string) $body['conta_id']) : $atual['conta_id'];
        $horaInformada = array_key_exists('hora_informada', $body) ? ((string) $body['hora_informada'] === '0' ? 0 : 1) : (int) $atual['hora_informada'];

        $this->db->prepare(
            "UPDATE financeiro_pessoal_lancamentos SET tipo = ?, categoria = ?, descricao = ?, valor = ?,
                vencimento = ?, pago_em = ?, data_competencia = ?, observacao = ?, anexo_url = ?,
                codigo_barras = ?, pix_copia_cola = ?, hora_informada = ?, conta_id = ?
             WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        )->execute([
            $tipo, $categoria, $descricao, $valor, $vencimento, $pagoEm, $dataCompet, $observacao,
            $anexoUrl, $codBarras, $pixCola, $horaInformada, $contaId, (int) $id, $this->uid, $this->perfilId,
        ]);

        $this->json(['ok' => true]);
    }

    /** "Marcar como pago" — pede data e valor pago (pode diferir do valor originalmente
     *  lançado, ex. juros/desconto); grava os dois no mesmo lançamento. */
    public function marcarPago(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $pagoEm = $this->dataOpcionalOuNull($this->post('pago_em')) ?? date('Y-m-d');
        $valorPost = (string) $this->post('valor', '');
        $valor = $valorPost !== '' ? moeda_float($valorPost) : null;

        if ($valor !== null && $valor <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }

        $sql = $valor !== null
            ? "UPDATE financeiro_pessoal_lancamentos SET pago_em = ?, valor = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
            : "UPDATE financeiro_pessoal_lancamentos SET pago_em = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?";
        $params = $valor !== null
            ? [$pagoEm, $valor, (int) $id, $this->uid, $this->perfilId]
            : [$pagoEm, (int) $id, $this->uid, $this->perfilId];

        $st = $this->db->prepare($sql);
        $st->execute($params);
        if ($st->rowCount() === 0) {
            // rowCount()=0 também acontece se o valor/data não mudaram — confere posse antes de
            // decidir que é erro de verdade (mesmo cuidado já documentado em atualizar()).
            $dono = $this->db->prepare("SELECT 1 FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ? AND perfil_id = ?");
            $dono->execute([(int) $id, $this->uid, $this->perfilId]);
            if (!$dono->fetchColumn()) { $this->json(['ok' => false, 'erro' => 'Lançamento não encontrado.'], 404); }
        }

        $this->json(['ok' => true]);
    }

    /** Desmarca "pago" — volta pro status calculado a_pagar/a_receber/vencido sozinho. */
    public function desmarcarPago(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->prepare("UPDATE financeiro_pessoal_lancamentos SET pago_em = NULL WHERE id = ? AND usuario_id = ? AND perfil_id = ?")
            ->execute([(int) $id, $this->uid, $this->perfilId]);
        $this->json(['ok' => true]);
    }

    public function excluir(string $id): void
    {
        $this->guard();
        $this->guardEscrita();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $st = $this->db->prepare("DELETE FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ? AND perfil_id = ?");
        $st->execute([(int) $id, $this->uid, $this->perfilId]);

        $this->json(['ok' => true, 'removido' => $st->rowCount() > 0]);
    }

    /** Upload do anexo (comprovante) de um lançamento — endpoint à parte, chamado assim que o
     *  arquivo é escolhido no modal (antes do lançamento em si ser salvo); devolve a URL pra
     *  ir junto no POST de salvar()/atualizar() como `anexo_url`, texto puro — o modal continua
     *  enviando o resto como application/x-www-form-urlencoded de sempre, sem precisar virar
     *  multipart inteiro só por causa do anexo. */
    public function anexoUpload(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        if (empty($_FILES['anexo']['tmp_name'])) {
            $this->json(['ok' => false, 'erro' => 'Escolha um arquivo.'], 400);
        }
        $file = $_FILES['anexo'];
        if (($file['size'] ?? 0) > self::ANEXO_TAMANHO_MAX) {
            $this->json(['ok' => false, 'erro' => 'Arquivo maior que 8MB.'], 400);
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ANEXO_MIME_PERMITIDO, true)) {
            $this->json(['ok' => false, 'erro' => 'Formato não suportado. Use JPG, PNG, WebP ou PDF.'], 400);
        }

        $dir = BASE_PATH . '/storage/uploads/fixa_anexos';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

        if ($mime === 'application/pdf') {
            $arquivo = 'anexo_' . $this->uid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $arquivo)) {
                $this->json(['ok' => false, 'erro' => 'Não deu pra salvar o PDF. Tente de novo.'], 400);
            }
        } else {
            $arquivo = 'anexo_' . $this->uid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.webp';
            if (!\App\Services\ImageService::paraWebp($file['tmp_name'], $dir . '/' . $arquivo, 85, 1600)) {
                $this->json(['ok' => false, 'erro' => 'Não deu pra processar essa imagem. Tente outro arquivo.'], 400);
            }
        }

        $this->json(['ok' => true, 'url' => url('/uploads/fixa_anexos/' . $arquivo), 'nome' => $arquivo]);
    }

    /**
     * Celular do PRÓPRIO usuário (sem QR/pareamento, mesmo padrão de ScannerController::
     * lerDireto()): fotografou a conta direto no aparelho que já está com a tela aberta —
     * processa e lê com a IA de visão na hora, sem passar por scanner_sessoes (não precisa:
     * é o mesmo dispositivo que vai abrir o formulário de revisão em seguida).
     */
    /**
     * Etapa 4 (controle de custo do scanner): pré-checagem local de código de barras/Pix ANTES
     * de chamar a API — o JS decodifica o código direto da foto (BarcodeDetector nativo do
     * navegador, sem round-trip algum) e só manda esse código pra cá; se já existir um
     * lançamento deste usuário com o MESMO código, devolve ele pronto e o front-end nunca chega
     * a chamar /ocr-conta (nem gasta 1 token de IA nisso). Sem código reconhecido (a maioria dos
     * casos — boleto impresso pequeno, foto torta, navegador sem BarcodeDetector), o front-end
     * simplesmente não chama este endpoint e segue pro fluxo normal de IA, sem nenhum atraso.
     */
    public function verificarCodigo(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $codBarras = trim((string) $this->post('codigo_barras', ''));
        $pixCola   = trim((string) $this->post('pix_copia_cola', ''));
        if ($codBarras === '' && $pixCola === '') { $this->json(['ok' => true, 'duplicado' => false]); }

        $st = $this->db->prepare(
            "SELECT id, descricao, valor, vencimento, pago_em FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND (
               (codigo_barras IS NOT NULL AND codigo_barras <> '' AND codigo_barras = ?)
               OR (pix_copia_cola IS NOT NULL AND pix_copia_cola <> '' AND pix_copia_cola = ?)
             ) ORDER BY id DESC LIMIT 1"
        );
        $st->execute([$this->uid, $codBarras, $pixCola]);
        $lanc = $st->fetch();

        if (!$lanc) { $this->json(['ok' => true, 'duplicado' => false]); }

        $this->json([
            'ok' => true, 'duplicado' => true,
            'lancamento' => [
                'id' => (int) $lanc['id'],
                'descricao' => $lanc['descricao'],
                'valor' => (float) $lanc['valor'],
                'vencimento' => $lanc['vencimento'],
                'pago' => !empty($lanc['pago_em']),
            ],
        ]);
    }

    /**
     * Link de cancelamento em 1 clique do aviso de vencimento (ver AssinaturaService::
     * precisaAviso()/cancelarPeloToken(), scripts/avisar_teste_fixa_terminando.php)
     * — PÚBLICA de propósito (sem AuthMiddleware), mesmo padrão já usado pelos links de
     * descadastro de e-mail do projeto (ex.: MasterController::prospeccaoDescadastrar()): o
     * token já É a autorização, ninguém precisa estar logado pra cancelar o próprio teste a
     * partir do e-mail. Idempotente — clicar de novo (ou um scanner de e-mail pré-carregando o
     * link) nunca dá erro, só mostra "já tinha sido cancelado".
     */
    public function cancelarTesteFixa(string $token): void
    {
        $cancelou = \App\Services\Fixa\AssinaturaService::cancelarPeloToken($this->db, $token);
        $this->view('financeiro_pessoal.teste_cancelado', [
            'titulo' => 'Teste cancelado', 'cancelou' => $cancelou, 'noindex' => true,
        ], 'landing');
    }

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

        // Etapa 4: limite mensal de leituras — checa ANTES de gastar tempo/custo processando a
        // foto. Sem limite, segue direto pro scan; no limite, avisa e deixa cair pro lançamento
        // manual (o front-end já sabe fazer isso quando $extraido vem null).
        $limiteInfo = fixa_scanner_verificar($this->uid, $this->empresa);
        if (!$limiteInfo['liberado']) {
            $this->json(['ok' => true, 'extraido' => null, 'limite_atingido' => true, 'erro' => $limiteInfo['mensagem']]);
        }

        $dir = BASE_PATH . '/storage/uploads/scanner';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $caminho = $dir . '/conta_direto_' . $this->uid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.webp';
        if (!\App\Services\ImageService::binarioParaWebp($bin, $caminho, 85, 1568)) {
            $this->json(['ok' => false, 'erro' => 'Não deu pra processar a foto. Tente de novo.'], 400);
        }

        // Conta a leitura assim que decide chamar a API (o custo já foi incorrido, sucesso ou
        // não) — nunca depois, senão uma leitura que falhou não contaria contra o limite.
        $this->db->prepare("INSERT INTO fixa_scanner_leituras (usuario_id, referencia_mes) VALUES (?, ?)")
            ->execute([$this->uid, date('Y-m')]);

        $extraido = \App\Services\VisionService::lerConta($caminho, array_keys($this->categoriasDoPerfilOuVazio()), $this->uid, $this->eid);
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
     * essa correção valer sozinha na próxima leitura.
     */
    public function aprenderCategoria(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false], 400); }

        $benef = trim((string) $this->post('beneficiario', ''));
        $categoria = (string) $this->post('categoria', '');
        if ($benef === '' || !array_key_exists($categoria, $this->categoriasDoPerfilOuVazio())) {
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
     * CRUD de Categorias (menu na barra lateral) — escopado pro perfil ativo (dois perfis do
     * mesmo usuário têm catálogos independentes).
     */
    public function categorias(): void
    {
        $categorias = [];
        if ($this->liberado) {
            try {
                $categorias = PerfilService::categoriasDoPerfil($this->db, $this->perfilId, $this->perfil['tipo']);
            } catch (\Throwable $e) {
                error_log('FinanceiroPessoal::categorias — ' . $e->getMessage());
                $categorias = [];
            }
        }

        $this->view('financeiro_pessoal.categorias', [
            'titulo'     => 'Financeiro pessoal — Categorias',
            'liberado'   => $this->liberado,
            'perfil'     => $this->perfil,
            'perfis'     => $this->perfisParaView(),
            'categorias' => $categorias,
            'wrapFull'   => true,
        ], 'financeiro_pessoal');
    }

    /** Chave (slug) de uma categoria nova — gerada uma vez na criação e NUNCA muda depois;
     *  única dentro do PERFIL (dois perfis podem ter a mesma chave, ver migration 084). */
    private function gerarChaveCategoria(string $nome): string
    {
        $base = strtolower(remover_acentos($nome));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base);
        $base = trim($base, '_');
        if ($base === '') { $base = 'categoria'; }
        $base = substr($base, 0, 30);

        $chave = $base;
        $i = 2;
        $st = $this->db->prepare("SELECT 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ? AND chave = ?");
        while (true) {
            $st->execute([$this->perfilId, $chave]);
            if (!$st->fetchColumn()) { break; }
            $chave = $base . '_' . $i;
            $i++;
        }
        return $chave;
    }

    private function corCategoriaValida(string $cor): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : '#7A6A88';
    }

    private function criarCategoria(string $nome, string $cor, string $tipo): string
    {
        $pos = $this->db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
        $pos->execute([$this->perfilId]);

        $chave = $this->gerarChaveCategoria($nome);
        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_categorias (usuario_id, perfil_id, chave, nome, cor, tipo, posicao)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$this->uid, $this->perfilId, $chave, $nome, $cor, $tipo, (int) $pos->fetchColumn()]);

        return $chave;
    }

    private function atualizarCategoria(int $id, string $nome, string $cor): bool
    {
        $st = $this->db->prepare(
            "UPDATE financeiro_pessoal_categorias SET nome = ?, cor = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$nome, $cor, $id, $this->uid, $this->perfilId]);
        return $st->rowCount() > 0;
    }

    public function categoriaSalvar(): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $nome = trim((string) $this->post('nome', ''));
        $cor  = $this->corCategoriaValida((string) $this->post('cor', ''));
        $tipo = $this->post('tipo', 'despesa') === 'receita' ? 'receita' : 'despesa';
        if ($nome === '') { $this->flash('error', 'Dê um nome pra categoria.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->criarCategoria($nome, $cor, $tipo);

        $this->flash('success', 'Categoria criada!');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

    public function categoriaAtualizar(string $id): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $nome = trim((string) $this->post('nome', ''));
        $cor  = $this->corCategoriaValida((string) $this->post('cor', ''));
        if ($nome === '') { $this->flash('error', 'Dê um nome pra categoria.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->atualizarCategoria((int) $id, $nome, $cor);

        $this->flash('success', 'Categoria atualizada!');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

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

    public function categoriaExcluir(string $id): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/categorias')); }

        $this->db->prepare(
            "UPDATE financeiro_pessoal_categorias SET ativo = 0 WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        )->execute([(int) $id, $this->uid, $this->perfilId]);

        $this->flash('success', 'Categoria excluída.');
        $this->redirect(url('/financeiro-pessoal/categorias'));
    }

    /**
     * Configurações — foto do usuário + perfis (criar/editar/arquivar) + preferências do sino
     * de notificação. Lê direto do banco (não da sessão) pra sempre mostrar o valor de verdade.
     */
    public function configuracoes(): void
    {
        $notifSom = 1;
        $notifTempo = 6;
        if ($this->liberado) {
            $st = $this->db->prepare("SELECT fp_notif_som, fp_notif_tempo_exibicao FROM usuarios WHERE id = ?");
            $st->execute([$this->uid]);
            $row = $st->fetch();
            if ($row) {
                $notifSom = (int) $row['fp_notif_som'];
                $notifTempo = (int) $row['fp_notif_tempo_exibicao'];
            }
        }

        // Assinatura standalone (ver AssinaturaService) — vazio pra quem tem Fixa de graça
        // pelo plano da empresa (reivindicada + Oficina/Top Empresa), que não paga nada aqui.
        $statusAssinatura = $this->assinaturaFixa ? \App\Services\Fixa\AssinaturaService::statusEfetivo($this->assinaturaFixa) : null;

        $this->view('financeiro_pessoal.configuracoes', [
            'titulo'           => 'Financeiro pessoal — Configurações',
            'liberado'         => $this->liberado,
            'perfil'           => $this->perfil,
            'perfis'           => $this->perfisParaView(),
            'avatar'           => (string) ($_SESSION['usuario']['avatar'] ?? ''),
            'notifSom'         => $notifSom,
            'notifTempo'       => $notifTempo,
            'wrapFull'         => true,
            'assinaturaFixa'   => $this->assinaturaFixa,
            'statusAssinatura' => $statusAssinatura,
        ], 'financeiro_pessoal');
    }

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
        $this->guardFlash();
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

        $antigo = (string) ($_SESSION['usuario']['avatar'] ?? '');
        if ($antigo !== '' && !preg_match('~^https?://~i', $antigo)) {
            @unlink($dir . '/' . basename($antigo));
        }

        $this->db->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?")->execute([$arquivo, $this->uid]);
        $_SESSION['usuario']['avatar'] = $arquivo;

        $this->flash('success', 'Foto atualizada!');
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }

    /**
     * Sino de notificação da Agenda — avisa (badge + som + popup) quando um evento OU um
     * lançamento em aberto chega no vencimento (`data_hora <= NOW()`), igual o "instante 0" do
     * sistema principal. Eventos de QUALQUER perfil do usuário entram aqui (não só o ativo) —
     * trocar de perfil não deveria silenciar o aviso de uma conta vencendo no outro perfil.
     */
    public function notificacoesAjax(): void
    {
        $this->guard();
        $st = $this->db->prepare(
            "SELECT id, titulo, data_hora FROM financeiro_pessoal_eventos
             WHERE usuario_id = ? AND lido_em IS NULL AND data_hora <= NOW()
             ORDER BY data_hora ASC"
        );
        $st->execute([$this->uid]);
        $this->json(['ok' => true, 'notificacoes' => $st->fetchAll()]);
    }

    public function notificacaoLer(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->prepare("UPDATE financeiro_pessoal_eventos SET lido_em = NOW() WHERE id = ? AND usuario_id = ?")
            ->execute([(int) $id, $this->uid]);
        $this->json(['ok' => true]);
    }

    public function notificacoesLerTodas(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $this->db->prepare(
            "UPDATE financeiro_pessoal_eventos SET lido_em = NOW()
             WHERE usuario_id = ? AND lido_em IS NULL AND data_hora <= NOW()"
        )->execute([$this->uid]);
        $this->json(['ok' => true]);
    }

    /**
     * Alerta em MODAL (não é o sino) pra lançamento vencido sem marcar como pago — mesmo
     * padrão já usado no sistema principal pro alerta de evento de agenda não concluído: joga
     * o throttle de "repete de 3 em 3h" pro banco (ultimo_alerta_vencido_em), não pro cliente —
     * cada vencido retornado aqui já teve o carimbo atualizado, então um poll seguinte dentro
     * da mesma janela de 3h simplesmente não traz ele de novo, sem precisar de dedup em JS.
     * Escopado por perfil ativo, igual todo o resto do módulo.
     */
    public function alertasVencidosAjax(): void
    {
        $this->guard();
        $st = $this->db->prepare(
            "SELECT id, descricao, valor, vencimento FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL
               AND vencimento IS NOT NULL AND vencimento < CURDATE()
               AND (ultimo_alerta_vencido_em IS NULL OR ultimo_alerta_vencido_em < DATE_SUB(NOW(), INTERVAL 3 HOUR))
             ORDER BY vencimento ASC"
        );
        $st->execute([$this->uid, $this->perfilId]);
        $vencidos = $st->fetchAll();

        if ($vencidos) {
            $ids = array_column($vencidos, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->db->prepare(
                "UPDATE financeiro_pessoal_lancamentos SET ultimo_alerta_vencido_em = NOW() WHERE id IN ({$placeholders})"
            )->execute($ids);
        }

        $this->json(['ok' => true, 'vencidos' => $vencidos]);
    }

    public function salvarNotificacoesConfig(): void
    {
        $this->guardFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/configuracoes')); }

        $som = $this->post('notif_som') === '1' ? 1 : 0;
        $tempo = max(2, min(30, (int) $this->post('notif_tempo', 6)));

        $this->db->prepare("UPDATE usuarios SET fp_notif_som = ?, fp_notif_tempo_exibicao = ? WHERE id = ?")
            ->execute([$som, $tempo, $this->uid]);
        $_SESSION['usuario']['fp_notif_som'] = $som;
        $_SESSION['usuario']['fp_notif_tempo_exibicao'] = $tempo;

        $this->flash('success', 'Preferências de notificação salvas!');
        $this->redirect(url('/financeiro-pessoal/configuracoes'));
    }
}
