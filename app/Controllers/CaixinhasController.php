<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\CaixinhaService;
use App\Services\Fixa\AssinaturaService;

/**
 * Carteira Fixa — módulo "Caixinhas" (reserva de dinheiro pra guardar, ver CaixinhaService pra
 * regras/cálculo). Mesmo padrão de controller de `FixaContasController` (resolve empresa→perfil
 * no construtor, guard por `financeiro_pessoal_liberado()`, form+redirect simples pro CRUD) —
 * só que ACRESCENTA o guard de escrita por assinatura bloqueada
 * (`AssinaturaService::somenteExportacao()`, igual `FinanceiroPessoalController::
 * guardEscrita()`), porque Caixinhas mexe em dinheiro de verdade (desconta saldo de conta) —
 * `FixaContasController` não tem esse guard hoje (gap pré-existente, fora de escopo corrigir
 * aqui).
 */
class CaixinhasController extends Controller
{
    private \PDO $db;
    private int $uid;
    private array $empresa;
    private array $perfil;
    private bool $apenasExportacao = false;

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->uid = $this->usuarioId();

        $st = $this->db->prepare("SELECT reivindicada, plano_atual FROM empresas WHERE id = ?");
        $st->execute([$this->empresaId()]);
        $this->empresa = $st->fetch() ?: [];

        $assinaturaFixa = [];
        try {
            $assinaturaFixa = AssinaturaService::doUsuario($this->db, $this->uid) ?? [];
        } catch (\Throwable $e) {
            error_log('CaixinhasController::__construct (assinatura) — ' . $e->getMessage());
        }
        $this->empresa['_fixa_standalone_liberado'] = $assinaturaFixa && AssinaturaService::acessoCompleto($assinaturaFixa);
        $this->apenasExportacao = $assinaturaFixa && AssinaturaService::somenteExportacao($assinaturaFixa);
        // Mesmo critério de FinanceiroPessoalController: Fixa de graça pelo plano da empresa
        // nunca fica "apenas exportação" por causa de uma assinatura standalone antiga bloqueada.
        if ($this->empresa['_fixa_standalone_liberado'] === false
            && !empty($this->empresa['reivindicada'])
            && in_array($this->empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true)) {
            $this->apenasExportacao = false;
        }

        $liberado = financeiro_pessoal_liberado($this->empresa);
        $this->perfil = $liberado ? PerfilService::perfilAtivo($this->db, $this->uid) : [];
    }

    private function guard(): void
    {
        if (empty($this->perfil)) {
            $this->flash('error', 'Financeiro pessoal ainda não está liberado pro seu plano.');
            $this->redirect(url('/financeiro-pessoal'));
        }
    }

    /** Mesmo espírito de guard(), pra endpoint AJAX (JSON em vez de flash+redirect). */
    private function guardAjax(): void
    {
        if (empty($this->perfil)) {
            $this->json(['ok' => false, 'erro' => 'Financeiro pessoal ainda não está liberado pro seu plano.'], 403);
        }
    }

    /** Bloqueio de escrita (assinatura standalone bloqueada/cancelada dentro da retenção) —
     *  guard à parte, porque o módulo continua liberado pra ver, só não pode mexer em dinheiro. */
    private function guardEscritaAjax(): void
    {
        if ($this->apenasExportacao) {
            $this->json(['ok' => false, 'erro' => 'Sua assinatura do Carteira Fixa está bloqueada. Você ainda pode ver suas caixinhas, mas não guardar/retirar. Regularize o pagamento pra voltar a usar normalmente.'], 403);
        }
    }

    private function guardEscritaFlash(): void
    {
        if ($this->apenasExportacao) {
            $this->flash('error', 'Sua assinatura do Carteira Fixa está bloqueada. Regularize o pagamento pra voltar a criar/editar caixinhas.');
            $this->redirect(url('/financeiro-pessoal/caixinhas'));
        }
    }

    public function index(): void
    {
        $this->guard();

        $caixinhas = CaixinhaService::listarComSaldo($this->db, (int) $this->perfil['id']);
        $contas    = PerfilService::contasDoPerfil($this->db, (int) $this->perfil['id']);

        $this->view('financeiro_pessoal.caixinhas', [
            'titulo'    => 'Financeiro pessoal — Caixinhas',
            'liberado'  => true,
            'perfil'    => $this->perfil,
            'caixinhas' => $caixinhas,
            'contas'    => $contas,
            'wrapFull'  => true,
        ], 'financeiro_pessoal');
    }

    private function corValida(string $cor): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : '#8C7CFF';
    }

    private function iconeValido(string $icone): string
    {
        // Whitelist curta — mesmos ícones já disponíveis no mapa de fp_icone() que fazem
        // sentido visual pra uma reserva de dinheiro; qualquer outra coisa cai no padrão.
        return in_array($icone, ['piggy-bank-fill', 'wallet2', 'bar-chart-fill', 'arrow-up-circle-fill'], true)
            ? $icone : 'piggy-bank-fill';
    }

    public function salvar(): void
    {
        $this->guard();
        $this->guardEscritaFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $nome  = trim((string) $this->post('nome', ''));
        $cor   = $this->corValida((string) $this->post('cor', ''));
        $icone = $this->iconeValido((string) $this->post('icone', ''));
        $meta  = trim((string) $this->post('meta', ''));
        $metaCentavos = $meta !== '' ? (int) round(moeda_float($meta) * 100) : null;
        if ($metaCentavos !== null && $metaCentavos <= 0) { $metaCentavos = null; }
        $dataMeta = $this->dataOpcionalOuNull((string) $this->post('data_meta', ''));

        if ($nome === '') { $this->flash('error', 'Dê um nome pra caixinha.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $this->db->prepare(
            "INSERT INTO caixinhas (usuario_id, perfil_id, nome, cor, icone, meta_centavos, data_meta, arquivada)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
        )->execute([$this->uid, $this->perfil['id'], mb_substr($nome, 0, 80), $cor, $icone, $metaCentavos, $dataMeta]);

        $this->flash('success', 'Caixinha criada!');
        $this->redirect(url('/financeiro-pessoal/caixinhas'));
    }

    public function atualizar(string $id): void
    {
        $this->guard();
        $this->guardEscritaFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $nome  = trim((string) $this->post('nome', ''));
        $cor   = $this->corValida((string) $this->post('cor', ''));
        $icone = $this->iconeValido((string) $this->post('icone', ''));
        $meta  = trim((string) $this->post('meta', ''));
        $metaCentavos = $meta !== '' ? (int) round(moeda_float($meta) * 100) : null;
        if ($metaCentavos !== null && $metaCentavos <= 0) { $metaCentavos = null; }
        $dataMeta = $this->dataOpcionalOuNull((string) $this->post('data_meta', ''));

        if ($nome === '') { $this->flash('error', 'Dê um nome pra caixinha.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $this->db->prepare(
            "UPDATE caixinhas SET nome = ?, cor = ?, icone = ?, meta_centavos = ?, data_meta = ?
             WHERE id = ? AND perfil_id = ?"
        )->execute([mb_substr($nome, 0, 80), $cor, $icone, $metaCentavos, $dataMeta, (int) $id, $this->perfil['id']]);

        $this->flash('success', 'Caixinha atualizada!');
        $this->redirect(url('/financeiro-pessoal/caixinhas'));
    }

    public function arquivar(string $id): void
    {
        $this->guard();
        $this->guardEscritaFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $arquivar = $this->post('arquivar', '1') === '1' ? 1 : 0;
        $this->db->prepare("UPDATE caixinhas SET arquivada = ? WHERE id = ? AND perfil_id = ?")
            ->execute([$arquivar, (int) $id, $this->perfil['id']]);

        $this->flash('success', $arquivar ? 'Caixinha arquivada.' : 'Caixinha reativada.');
        $this->redirect(url('/financeiro-pessoal/caixinhas'));
    }

    /** Só exclui de verdade com saldo zerado (pedido explícito: "pedir para retirar antes ou
     *  arquivar") — mesma cautela de FixaContasController::excluir() pra conta padrão. */
    public function excluir(string $id): void
    {
        $this->guard();
        $this->guardEscritaFlash();
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Recarregue a página.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        $st = $this->db->prepare("SELECT id FROM caixinhas WHERE id = ? AND perfil_id = ?");
        $st->execute([(int) $id, $this->perfil['id']]);
        if (!$st->fetchColumn()) { $this->flash('error', 'Caixinha não encontrada.'); $this->redirect(url('/financeiro-pessoal/caixinhas')); }

        if (!CaixinhaService::podeExcluir($this->db, (int) $id)) {
            $this->flash('error', 'Essa caixinha ainda tem saldo guardado — retire o valor ou arquive a caixinha antes de excluir.');
            $this->redirect(url('/financeiro-pessoal/caixinhas'));
        }

        $this->db->prepare("DELETE FROM caixinhas WHERE id = ? AND perfil_id = ?")->execute([(int) $id, $this->perfil['id']]);
        $this->flash('success', 'Caixinha excluída.');
        $this->redirect(url('/financeiro-pessoal/caixinhas'));
    }

    /** Modal "Guardar" — AJAX, pra mostrar o aviso de transferência sem recarregar a página. */
    public function guardar(string $id): void
    {
        $this->guardAjax();
        $this->guardEscritaAjax();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $st = $this->db->prepare("SELECT id, nome FROM caixinhas WHERE id = ? AND perfil_id = ?");
        $st->execute([(int) $id, $this->perfil['id']]);
        $caixinha = $st->fetch();
        if (!$caixinha) { $this->json(['ok' => false, 'erro' => 'Caixinha não encontrada.'], 404); }

        $valorCentavos = (int) round(moeda_float($this->post('valor', 0)) * 100);
        if ($valorCentavos <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }

        $contaId = $this->contaIdValidaOuNull((string) $this->post('conta_id', ''));
        $data    = $this->dataOpcionalOuNull((string) $this->post('data', '')) ?: date('Y-m-d');
        $obs     = trim((string) $this->post('observacao', ''));
        $obs     = $obs !== '' ? mb_substr($obs, 0, 300) : null;

        $movId = CaixinhaService::depositar($this->db, (int) $id, $this->uid, (int) $this->perfil['id'], $valorCentavos, $data, $contaId, $obs);

        $this->json([
            'ok'             => true,
            'movimento_id'   => $movId,
            'valor_formatado' => money($valorCentavos / 100),
            'saldo_centavos' => CaixinhaService::saldoCaixinha($this->db, (int) $id),
        ]);
    }

    /** Modal "Retirar" — AJAX. Servidor é sempre a fonte da verdade da validação de saldo
     *  (o JS do modal só replica o cálculo pra feedback imediato, nunca confia só nele). */
    public function retirar(string $id): void
    {
        $this->guardAjax();
        $this->guardEscritaAjax();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $st = $this->db->prepare("SELECT id FROM caixinhas WHERE id = ? AND perfil_id = ?");
        $st->execute([(int) $id, $this->perfil['id']]);
        if (!$st->fetchColumn()) { $this->json(['ok' => false, 'erro' => 'Caixinha não encontrada.'], 404); }

        $valorCentavos = (int) round(moeda_float($this->post('valor', 0)) * 100);
        if ($valorCentavos <= 0) { $this->json(['ok' => false, 'erro' => 'Informe um valor maior que zero.'], 400); }

        $contaId = $this->contaIdValidaOuNull((string) $this->post('conta_id', ''));
        $data    = $this->dataOpcionalOuNull((string) $this->post('data', '')) ?: date('Y-m-d');
        $obs     = trim((string) $this->post('observacao', ''));
        $obs     = $obs !== '' ? mb_substr($obs, 0, 300) : null;

        $movId = CaixinhaService::retirar($this->db, (int) $id, $this->uid, (int) $this->perfil['id'], $valorCentavos, $data, $contaId, $obs);
        if ($movId === null) {
            $this->json(['ok' => false, 'erro' => 'O valor da retirada não pode ser maior que o saldo guardado nessa caixinha.'], 400);
        }

        $this->json(['ok' => true, 'movimento_id' => $movId, 'saldo_centavos' => CaixinhaService::saldoCaixinha($this->db, (int) $id)]);
    }

    /** "Já transferi" — marca o depósito como efetivamente levado pro banco/investimento. */
    public function marcarTransferido(string $movimentoId): void
    {
        $this->guardAjax();
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página.'], 400); }

        $ok = CaixinhaService::marcarTransferido($this->db, (int) $movimentoId, $this->uid, (int) $this->perfil['id']);
        $this->json(['ok' => $ok]);
    }

    private function dataOpcionalOuNull(string $v): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    /** Conta precisa pertencer ao perfil atual — nunca aceita um conta_id de outro perfil/
     *  usuário vindo do POST sem checar (defesa contra IDOR, mesmo espírito de todo o módulo). */
    private function contaIdValidaOuNull(string $v): ?int
    {
        if ($v === '') return null;
        $st = $this->db->prepare("SELECT id FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
        $st->execute([(int) $v, $this->perfil['id']]);
        return $st->fetchColumn() ? (int) $v : null;
    }
}
