<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\CaixinhaService;
use App\Services\Fixa\AssinaturaService;

/**
 * Fixa Fase 1 — CRUD de Contas (corrente/poupança/dinheiro/cartão de crédito/investimento) do
 * PERFIL ativo (ver PerfilService::perfilAtivo()). Mesmo padrão simples de form+redirect já
 * usado por ServicosCatalogoController/FinanceiroPessoalController::categoriaSalvar() — sem
 * AJAX aqui, a tela de Contas não precisa de nada reativo em tempo real.
 */
class FixaContasController extends Controller
{
    private \PDO $db;
    private int $uid;
    private array $empresa;
    private array $perfil;
    private bool $apenasExportacao = false;

    private const TIPOS_VALIDOS = ['corrente', 'poupanca', 'dinheiro', 'cartao_credito', 'investimento'];

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->empresaId()]);
        $this->empresa = $st->fetch() ?: [];

        // Mesmo guard de escrita por assinatura bloqueada já usado em CaixinhasController —
        // ajustarSaldo() mexe em dinheiro de verdade (cria lançamento), então precisa dele,
        // mesmo essa controller não tendo esse guard nos outros métodos (gap pré-existente,
        // fora de escopo corrigir aqui).
        $assinaturaFixa = [];
        try {
            $assinaturaFixa = AssinaturaService::doUsuario($this->db, $this->uid) ?? [];
        } catch (\Throwable $e) {
            error_log('FixaContasController::__construct (assinatura) — ' . $e->getMessage());
        }
        $this->empresa['_fixa_standalone_liberado'] = $assinaturaFixa && AssinaturaService::acessoCompleto($assinaturaFixa);
        $this->apenasExportacao = $assinaturaFixa && AssinaturaService::somenteExportacao($assinaturaFixa);
        if ($this->empresa['_fixa_standalone_liberado'] === false
            && !empty($this->empresa['reivindicada'])
            && in_array($this->empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true)) {
            $this->apenasExportacao = false;
        }

        $this->perfil = financeiro_pessoal_liberado($this->empresa)
            ? PerfilService::perfilAtivo($this->db, $this->uid)
            : [];
    }

    private function guard(): void
    {
        if (!financeiro_pessoal_liberado($this->empresa)) {
            $this->flash('error', 'Financeiro pessoal ainda não está liberado pro seu plano.');
            $this->redirect(url('/financeiro-pessoal'));
        }
    }

    /** Bloqueio de escrita (assinatura standalone bloqueada/cancelada dentro da retenção) —
     *  mesmo espírito de CaixinhasController::guardEscritaFlash(). */
    private function guardEscritaFlash(): void
    {
        if ($this->apenasExportacao) {
            $this->flash('error', 'Sua assinatura do Carteira Fixa está bloqueada. Regularize o pagamento pra voltar a ajustar saldo.');
            $this->redirect(url('/financeiro-pessoal/contas'));
        }
    }

    /** Saldo atual de cada conta (saldo_inicial + receitas pagas − despesas pagas), calculado
     *  na hora — nunca gravado em coluna (mesmo princípio de fixa_saldo_atual()). */
    private function saldosPorConta(array $contaIds): array
    {
        if (!$contaIds) return [];
        $ph = implode(',', array_fill(0, count($contaIds), '?'));
        $st = $this->db->prepare(
            "SELECT conta_id,
                    SUM(CASE WHEN tipo = 'receita' AND pago_em IS NOT NULL THEN valor ELSE 0 END) receitas_pagas,
                    SUM(CASE WHEN tipo = 'despesa' AND pago_em IS NOT NULL THEN valor ELSE 0 END) despesas_pagas
             FROM financeiro_pessoal_lancamentos
             WHERE conta_id IN ($ph)
             GROUP BY conta_id"
        );
        $st->execute($contaIds);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[(int) $r['conta_id']] = ['receitas_pagas' => (float) $r['receitas_pagas'], 'despesas_pagas' => (float) $r['despesas_pagas']];
        }
        return $out;
    }

    public function index(): void
    {
        $this->guard();

        $contas = PerfilService::contasDoPerfil($this->db, (int) $this->perfil['id'], true);
        $contaIds = array_map(fn($c) => (int) $c['id'], $contas);
        $somas = $this->saldosPorConta($contaIds);
        // Caixinhas: depósito "tira" da conta de origem, retirada devolve pra conta de destino
        // — mesmo princípio de dinheiro guardado se comportar como se tivesse saído da conta
        // (CaixinhaService::saldoPorConta(), centavos → reais na fronteira).
        $caixinhaPorConta = CaixinhaService::saldoPorConta($this->db, $contaIds);

        foreach ($contas as &$c) {
            $s = $somas[(int) $c['id']] ?? ['receitas_pagas' => 0.0, 'despesas_pagas' => 0.0];
            $saldo = fixa_saldo_atual((float) $c['saldo_inicial'], $s['receitas_pagas'], $s['despesas_pagas']);
            $saldo -= (($caixinhaPorConta[(int) $c['id']] ?? 0) / 100);
            $c['saldo_atual'] = round($saldo, 2);
        }
        unset($c);

        $this->view('financeiro_pessoal.contas', [
            'titulo'   => 'Financeiro pessoal — Contas',
            'liberado' => true,
            'perfil'   => $this->perfil,
            'contas'   => $contas,
            'wrapFull' => true,
        ], 'financeiro_pessoal');
    }

    private function tipoValidoOuPadrao(string $tipo): string
    {
        return in_array($tipo, self::TIPOS_VALIDOS, true) ? $tipo : 'corrente';
    }

    private function corValida(string $cor): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : '#3CC9C0';
    }

    public function salvar(): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $nome    = trim((string) $this->post('nome', ''));
        $tipo    = $this->tipoValidoOuPadrao((string) $this->post('tipo', ''));
        $saldo   = moeda_float($this->post('saldo_inicial', 0));
        $dataIni = (string) $this->post('data_saldo_inicial', '');
        $cor     = $this->corValida((string) $this->post('cor', ''));

        if ($nome === '') { $this->flash('error', 'Dê um nome pra conta.'); $this->redirect(url('/financeiro-pessoal/contas')); }
        $dataIniSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataIni) ? $dataIni : date('Y-m-d');

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_contas (usuario_id, perfil_id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
        )->execute([$this->uid, $this->perfil['id'], $nome, $tipo, $saldo, $dataIniSql, $cor]);

        $this->flash('success', 'Conta criada!');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }

    public function atualizar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $nome    = trim((string) $this->post('nome', ''));
        $tipo    = $this->tipoValidoOuPadrao((string) $this->post('tipo', ''));
        $saldo   = moeda_float($this->post('saldo_inicial', 0));
        $dataIni = (string) $this->post('data_saldo_inicial', '');
        $cor     = $this->corValida((string) $this->post('cor', ''));

        if ($nome === '') { $this->flash('error', 'Dê um nome pra conta.'); $this->redirect(url('/financeiro-pessoal/contas')); }
        $dataIniSql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataIni) ? $dataIni : date('Y-m-d');

        $this->db->prepare(
            "UPDATE financeiro_pessoal_contas SET nome = ?, tipo = ?, saldo_inicial = ?, data_saldo_inicial = ?, cor = ?
             WHERE id = ? AND perfil_id = ?"
        )->execute([$nome, $tipo, $saldo, $dataIniSql, $cor, (int) $id, $this->perfil['id']]);

        $this->flash('success', 'Conta atualizada!');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }

    /** Categoria usada pelo lançamento de ajuste de saldo — primeira categoria do perfil com o
     *  `tipo` certo (receita/despesa), igual o fallback já usado em
     *  FinanceiroPessoalController::categoriaValidaOuPadrao(). Nunca hardcoda 'outros' porque
     *  esse chave é só despesa (perfil PF) — uma receita de ajuste precisa de uma categoria de
     *  receita de verdade (ex. 'outras_receitas'/'vendas_servicos'), senão o ajuste apareceria
     *  com a categoria errada nos relatórios. */
    private function categoriaParaAjuste(string $tipoLancamento): string
    {
        $categorias = PerfilService::categoriasDoPerfil($this->db, (int) $this->perfil['id'], $this->perfil['tipo'] ?? 'pf');
        foreach ($categorias as $chave => $c) {
            if ($c['tipo'] === $tipoLancamento) { return $chave; }
        }
        return array_key_first($categorias) ?? 'outros';
    }

    /**
     * "Corrigir saldo" — pedido do usuário: deixar ajustar o saldo ATUAL da conta direto (ex.:
     * reconciliar com o extrato do banco), em vez de precisar calcular na mão o que
     * `saldo_inicial` deveria virar pra bater. `saldo_inicial`/`data_saldo_inicial` continuam
     * sendo só o PONTO DE PARTIDA histórico da conta (editável em atualizar()) — não são
     * reescritos aqui. A diferença entre o saldo atual calculado e o valor informado vira um
     * lançamento de ajuste (já pago, na data de hoje), mesmo princípio de qualquer outro
     * lançamento que move dinheiro de verdade nesta conta.
     */
    public function ajustarSaldo(string $id): void
    {
        $this->guard();
        $this->guardEscritaFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $contaId = (int) $id;
        $st = $this->db->prepare("SELECT id, saldo_inicial FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
        $st->execute([$contaId, $this->perfil['id']]);
        $conta = $st->fetch();
        if (!$conta) { $this->flash('error', 'Conta não encontrada.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $novoSaldoStr = trim((string) $this->post('novo_saldo', ''));
        if ($novoSaldoStr === '') {
            $this->flash('error', 'Informe o novo saldo da conta.');
            $this->redirect(url('/financeiro-pessoal/contas'));
        }
        $novoSaldo = moeda_float($novoSaldoStr);

        // Mesmo cálculo de saldo atual que index() já faz pra cada conta da lista — reaproveitado
        // aqui só pra esta conta, pra saber exatamente quanto de diferença lançar.
        $somas = $this->saldosPorConta([$contaId]);
        $s = $somas[$contaId] ?? ['receitas_pagas' => 0.0, 'despesas_pagas' => 0.0];
        $saldoAtual = fixa_saldo_atual((float) $conta['saldo_inicial'], $s['receitas_pagas'], $s['despesas_pagas']);
        $caixinhaPorConta = CaixinhaService::saldoPorConta($this->db, [$contaId]);
        $saldoAtual -= (($caixinhaPorConta[$contaId] ?? 0) / 100);
        $saldoAtual = round($saldoAtual, 2);

        $diferenca = round($novoSaldo - $saldoAtual, 2);
        if (abs($diferenca) < 0.005) {
            $this->flash('success', 'O saldo já estava certo — nada pra ajustar.');
            $this->redirect(url('/financeiro-pessoal/contas'));
        }

        $tipoLanc = $diferenca > 0 ? 'receita' : 'despesa';
        $categoria = $this->categoriaParaAjuste($tipoLanc);
        $hoje = date('Y-m-d');

        $this->db->prepare(
            "INSERT INTO financeiro_pessoal_lancamentos
                (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor,
                 data_hora, vencimento, pago_em, hora_informada, origem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'manual')"
        )->execute([
            $this->uid, $this->perfil['id'], $contaId, $tipoLanc, $categoria, 'Ajuste de saldo',
            abs($diferenca), $hoje . ' 00:00:00', $hoje, $hoje,
        ]);

        $this->flash('success', 'Saldo ajustado pra R$ ' . number_format($novoSaldo, 2, ',', '.') . '.');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }

    /** Arquiva/desarquiva — nunca exclui de verdade (lançamentos já ligados a essa conta não
     *  podem ficar órfãos de referência visual: "arquivada" só tira do select de conta NOVA).
     *  A conta `padrao` (criada automaticamente junto com o perfil — "Carteira"/"Conta da
     *  empresa") nunca pode ser arquivada — todo perfil precisa de pelo menos 1 conta sempre
     *  disponível. Reativar (`arquivar=0`) continua liberado pra qualquer conta, inclusive a
     *  padrão (não tem como ela estar arquivada, mas não custa não bloquear o caminho inverso). */
    public function arquivar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $arquivar = $this->post('arquivar', '1') === '1' ? 1 : 0;

        if ($arquivar === 1) {
            $st = $this->db->prepare("SELECT padrao FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
            $st->execute([(int) $id, $this->perfil['id']]);
            if ((int) $st->fetchColumn() === 1) {
                $this->flash('error', 'Essa é a conta padrão do perfil — ela não pode ser arquivada. Crie outra conta se quiser organizar diferente.');
                $this->redirect(url('/financeiro-pessoal/contas'));
            }
        }

        $this->db->prepare("UPDATE financeiro_pessoal_contas SET arquivada = ? WHERE id = ? AND perfil_id = ?")
            ->execute([$arquivar, (int) $id, $this->perfil['id']]);

        $this->flash('success', $arquivar ? 'Conta arquivada.' : 'Conta reativada.');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }

    /**
     * Exclui de verdade — só pra conta que NÃO é a `padrao` (mesma proteção de arquivar()).
     * Diferente de arquivar (que o projeto escolheu de propósito pra nunca "quebrar" a
     * referência de um lançamento), aqui é seguro apagar a linha porque
     * `financeiro_pessoal_lancamentos.conta_id` tem `ON DELETE SET NULL` (migration 085) — um
     * lançamento ligado a essa conta não é apagado nem fica com FK quebrada, só perde o vínculo
     * (vira "sem conta", editável depois); nenhum dado financeiro desaparece.
     */
    public function excluir(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $st = $this->db->prepare("SELECT padrao FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
        $st->execute([(int) $id, $this->perfil['id']]);
        $conta = $st->fetch();

        if (!$conta) { $this->flash('error', 'Conta não encontrada.'); $this->redirect(url('/financeiro-pessoal/contas')); }
        if ((int) $conta['padrao'] === 1) {
            $this->flash('error', 'Essa é a conta padrão do perfil — ela não pode ser excluída. Crie outra conta se quiser organizar diferente.');
            $this->redirect(url('/financeiro-pessoal/contas'));
        }

        $this->db->prepare("DELETE FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?")
            ->execute([(int) $id, $this->perfil['id']]);

        $this->flash('success', 'Conta excluída.');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }
}
