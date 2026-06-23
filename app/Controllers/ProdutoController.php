<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Produto;

class ProdutoController extends Controller
{
    private Produto $model;

    public function __construct() { $this->model = new Produto(); }

    public function index(): void
    {
        $page     = (int) $this->get('page', 1);
        $busca    = $this->get('busca', '');
        $cat      = $this->get('categoria_id') ?: null;
        $result   = $this->model->listar($page, 20, $busca, $cat ? (int) $cat : null);
        $alertas  = $this->model->emEstoqueMinimo();

        $eid = $this->empresaId();
        $stmtC = DB::pdo()->prepare("SELECT * FROM categorias_produto WHERE empresa_id = ? ORDER BY nome");
        $stmtC->execute([$eid]);

        $this->view('produtos.index', [
            'titulo'     => 'Estoque / Produtos',
            'paginator'  => $result,
            'busca'      => $busca,
            'categorias' => $stmtC->fetchAll(),
            'alertas'    => $alertas,
        ]);
    }

    public function criar(): void
    {
        $this->view('produtos.form', array_merge(
            ['titulo' => 'Novo Produto', 'produto' => []],
            $this->aux()
        ));
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $data = [
            'codigo_barras'  => $this->post('codigo_barras'),
            'estado_id'      => $this->post('estado_id') ?: null,
            'tipo_id'        => $this->post('tipo_id') ?: null,
            'marca_id'       => $this->post('marca_id') ?: null,
            'nome'           => trim($this->post('nome')),
            'codigo'         => $this->post('codigo'),
            'descricao'      => $this->post('descricao'),
            'categoria_id'   => $this->post('categoria_id') ?: null,
            'fornecedor_id'  => $this->post('fornecedor_id') ?: null,
            'unidade'        => $this->post('unidade', 'un'),
            'estoque_atual'  => (float) $this->post('estoque_atual', 0),
            'estoque_minimo' => (float) $this->post('estoque_minimo', 0),
            'localizacao'    => $this->post('localizacao'),
            'valor_custo'    => (float) str_replace(',', '.', $this->post('valor_custo', 0)),
            'valor_venda'    => (float) str_replace(',', '.', $this->post('valor_venda', 0)),
            'ativo'          => 1,
        ];

        $id = $this->model->insert($data);
        $this->flash('success', 'Produto cadastrado!');
        $this->redirect(url('/produtos'));
    }

    public function editar(string $id): void
    {
        $produto = $this->model->find((int) $id);
        if (!$produto) { $this->flash('error', 'Produto não encontrado.'); $this->redirect(url('/produtos')); }
        $this->view('produtos.form', array_merge(
            ['titulo' => 'Editar Produto', 'produto' => $produto],
            $this->aux()
        ));
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $data = [
            'codigo_barras'  => $this->post('codigo_barras'),
            'estado_id'      => $this->post('estado_id') ?: null,
            'tipo_id'        => $this->post('tipo_id') ?: null,
            'marca_id'       => $this->post('marca_id') ?: null,
            'nome'           => trim($this->post('nome')),
            'codigo'         => $this->post('codigo'),
            'descricao'      => $this->post('descricao'),
            'categoria_id'   => $this->post('categoria_id') ?: null,
            'fornecedor_id'  => $this->post('fornecedor_id') ?: null,
            'unidade'        => $this->post('unidade', 'un'),
            'estoque_minimo' => (float) $this->post('estoque_minimo', 0),
            'localizacao'    => $this->post('localizacao'),
            'valor_custo'    => (float) str_replace(',', '.', $this->post('valor_custo', 0)),
            'valor_venda'    => (float) str_replace(',', '.', $this->post('valor_venda', 0)),
        ];

        $this->model->update((int) $id, $data);
        $this->flash('success', 'Produto atualizado!');
        $this->redirect(url('/produtos'));
    }

    private function aux(): array
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $q   = fn(string $t) => $db->prepare("SELECT * FROM {$t} WHERE empresa_id=? ORDER BY nome")->execute([$eid])
               ?: $db->prepare("SELECT * FROM {$t} WHERE empresa_id=? ORDER BY nome");

        $se = $db->prepare("SELECT * FROM produto_estados WHERE empresa_id=? ORDER BY nome"); $se->execute([$eid]);
        $st = $db->prepare("SELECT * FROM produto_tipos   WHERE empresa_id=? ORDER BY nome"); $st->execute([$eid]);
        $sm = $db->prepare("SELECT * FROM produto_marcas  WHERE empresa_id=? ORDER BY nome"); $sm->execute([$eid]);
        $sc = $db->prepare("SELECT * FROM categorias_produto WHERE empresa_id=? ORDER BY nome"); $sc->execute([$eid]);
        $sf = $db->prepare("SELECT id, razao_social FROM fornecedores WHERE empresa_id=? AND ativo=1 ORDER BY razao_social"); $sf->execute([$eid]);

        return [
            'estados'     => $se->fetchAll(),
            'tipos'       => $st->fetchAll(),
            'marcas'      => $sm->fetchAll(),
            'categorias'  => $sc->fetchAll(),
            'fornecedores'=> $sf->fetchAll(),
        ];
    }

    public function buscarAjax(): void
    {
        $this->json($this->model->buscar($this->get('q', '')));
    }
}
