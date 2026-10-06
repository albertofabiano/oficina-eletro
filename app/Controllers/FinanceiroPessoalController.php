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

    public const CATEGORIAS = [
        'alimentacao' => ['nome' => 'Alimentação', 'cor' => '#D9730D'],
        'transporte'  => ['nome' => 'Transporte',  'cor' => '#0E8F89'],
        'lazer'       => ['nome' => 'Lazer',       'cor' => '#8456E8'],
        'compras'     => ['nome' => 'Compras',     'cor' => '#3D6FD9'],
        'moradia'     => ['nome' => 'Moradia',     'cor' => '#5B9142'],
        'saude'       => ['nome' => 'Saúde',       'cor' => '#D9467C'],
        'outros'      => ['nome' => 'Outros',      'cor' => '#8C7A9E'],
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

    /** Tela principal — chat de lançamento + lista + resumo do mês. */
    public function index(): void
    {
        $liberado = financeiro_pessoal_liberado($this->empresa);

        $lancamentos = [];
        if ($liberado) {
            $st = $this->db->prepare(
                "SELECT id, tipo, categoria, descricao, valor, data_hora, origem
                 FROM financeiro_pessoal_lancamentos
                 WHERE usuario_id = ? ORDER BY data_hora DESC LIMIT 200"
            );
            $st->execute([$this->uid]);
            $lancamentos = $st->fetchAll();
        }

        $totalMes = 0.0;
        $mesAtual = date('Y-m');
        foreach ($lancamentos as $l) {
            if (substr($l['data_hora'], 0, 7) === $mesAtual && $l['tipo'] === 'despesa') {
                $totalMes += (float) $l['valor'];
            }
        }

        $this->view('financeiro_pessoal.index', [
            'titulo'      => 'Financeiro pessoal',
            'liberado'    => $liberado,
            'lancamentos' => $lancamentos,
            'totalMes'    => $totalMes,
            'categorias'  => self::CATEGORIAS,
        ], 'financeiro_pessoal');
    }

    /** Lista em JSON — usado pelo JS da própria tela depois de criar/excluir, sem reload. */
    public function listarAjax(): void
    {
        $this->guard();
        $st = $this->db->prepare(
            "SELECT id, tipo, categoria, descricao, valor, data_hora, origem
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? ORDER BY data_hora DESC LIMIT 200"
        );
        $st->execute([$this->uid]);
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
