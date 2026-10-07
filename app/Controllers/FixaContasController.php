<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Fixa\PerfilService;

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

    private const TIPOS_VALIDOS = ['corrente', 'poupanca', 'dinheiro', 'cartao_credito', 'investimento'];

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->empresaId()]);
        $this->empresa = $st->fetch() ?: [];

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
        $somas = $this->saldosPorConta(array_map(fn($c) => (int) $c['id'], $contas));

        foreach ($contas as &$c) {
            $s = $somas[(int) $c['id']] ?? ['receitas_pagas' => 0.0, 'despesas_pagas' => 0.0];
            $c['saldo_atual'] = fixa_saldo_atual((float) $c['saldo_inicial'], $s['receitas_pagas'], $s['despesas_pagas']);
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

    /** Arquiva/desarquiva — nunca exclui de verdade (lançamentos já ligados a essa conta não
     *  podem ficar órfãos de referência visual: "arquivada" só tira do select de conta NOVA). */
    public function arquivar(string $id): void
    {
        $this->guard();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/contas')); }

        $arquivar = $this->post('arquivar', '1') === '1' ? 1 : 0;
        $this->db->prepare("UPDATE financeiro_pessoal_contas SET arquivada = ? WHERE id = ? AND perfil_id = ?")
            ->execute([$arquivar, (int) $id, $this->perfil['id']]);

        $this->flash('success', $arquivar ? 'Conta arquivada.' : 'Conta reativada.');
        $this->redirect(url('/financeiro-pessoal/contas'));
    }
}
