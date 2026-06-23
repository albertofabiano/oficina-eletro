<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;

class ClienteController extends Controller
{
    private Cliente $model;

    public function __construct()
    {
        $this->model = new Cliente();
    }

    public function index(): void
    {
        $page   = (int) $this->get('page', 1);
        $busca  = trim($this->get('busca', ''));
        $result = $this->model->listarComContadores($page, 20, $busca);

        $this->view('clientes.index', [
            'titulo'    => 'Clientes',
            'paginator' => $result,
            'busca'     => $busca,
        ]);
    }

    public function criar(): void
    {
        $this->view('clientes.form', ['titulo' => 'Novo Cliente', 'cliente' => []]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $data = [
            'tipo'           => $this->post('tipo', 'pf'),
            'nome'           => trim($this->post('nome', '')),
            'cpf_cnpj'       => only_numbers($this->post('cpf_cnpj', '')),
            'email'          => trim($this->post('email', '')),
            'telefone'       => $this->post('telefone', ''),
            'whatsapp'       => $this->post('whatsapp', ''),
            'cep'            => only_numbers($this->post('cep', '')),
            'logradouro'     => $this->post('logradouro', ''),
            'numero'         => $this->post('numero', ''),
            'complemento'    => $this->post('complemento', ''),
            'bairro'         => $this->post('bairro', ''),
            'cidade'         => $this->post('cidade', ''),
            'uf'             => $this->post('uf', ''),
            'data_nascimento'=> $this->post('data_nascimento') ?: null,
            'origem'         => $this->post('origem', 'balcao'),
            'observacoes'    => $this->post('observacoes', ''),
            'status'         => 'ativo',
        ];

        $erros = $this->validate($data, ['nome' => 'required|max:150']);
        if ($erros) {
            $this->flash('error', implode(' ', $erros));
            $this->redirectBack();
        }

        $id = $this->model->insert($data);
        $this->flash('success', 'Cliente cadastrado com sucesso!');
        $this->redirect(url("/clientes/{$id}"));
    }

    public function editar(string $id): void
    {
        $cliente = $this->model->find((int) $id);
        if (!$cliente) { $this->flash('error', 'Cliente não encontrado.'); $this->redirect(url('/clientes')); }
        $this->view('clientes.form', ['titulo' => 'Editar Cliente', 'cliente' => $cliente]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $data = [
            'tipo'        => $this->post('tipo', 'pf'),
            'nome'        => trim($this->post('nome', '')),
            'cpf_cnpj'    => only_numbers($this->post('cpf_cnpj', '')),
            'email'       => trim($this->post('email', '')),
            'telefone'    => $this->post('telefone', ''),
            'whatsapp'    => $this->post('whatsapp', ''),
            'cep'         => only_numbers($this->post('cep', '')),
            'logradouro'  => $this->post('logradouro', ''),
            'numero'      => $this->post('numero', ''),
            'complemento' => $this->post('complemento', ''),
            'bairro'      => $this->post('bairro', ''),
            'cidade'      => $this->post('cidade', ''),
            'uf'          => $this->post('uf', ''),
            'origem'      => $this->post('origem', 'balcao'),
            'observacoes' => $this->post('observacoes', ''),
            'status'      => $this->post('status', 'ativo'),
        ];

        $this->model->update((int) $id, $data);
        $this->flash('success', 'Cliente atualizado!');
        $this->redirect(url("/clientes/{$id}"));
    }

    public function ver(string $id): void
    {
        $cliente = $this->model->find((int) $id);
        if (!$cliente) { $this->flash('error', 'Cliente não encontrado.'); $this->redirect(url('/clientes')); }

        $this->view('clientes.show', [
            'titulo'      => 'Cliente: ' . $cliente['nome'],
            'cliente'     => $cliente,
            'equipamentos'=> $this->model->equipamentos((int) $id),
            'historico'   => $this->model->historicoOS((int) $id),
            'contatos'    => $this->model->contatos((int) $id),
        ]);
    }

    public function excluir(string $id): void
    {
        $this->model->delete((int) $id);
        $this->flash('success', 'Cliente removido.');
        $this->redirect(url('/clientes'));
    }

    public function buscarAjax(): void
    {
        $termo = $this->get('q', '');
        $this->json($this->model->buscar($termo));
    }

    public function salvarAjax(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido.'], 403); }

        $nome = trim($this->post('nome', ''));
        if (!$nome) { $this->json(['error' => 'Nome obrigatório.'], 422); }

        $data = [
            'tipo'        => $this->post('tipo', 'pf'),
            'nome'        => $nome,
            'cpf_cnpj'    => only_numbers($this->post('cpf_cnpj', '')),
            'telefone'    => $this->post('telefone', ''),
            'whatsapp'    => $this->post('whatsapp', ''),
            'email'       => trim($this->post('email', '')),
            'origem'      => $this->post('origem', 'balcao'),
            'cep'         => only_numbers($this->post('cep', '')),
            'logradouro'  => trim($this->post('logradouro', '')),
            'numero'      => trim($this->post('numero', '')),
            'complemento' => trim($this->post('complemento', '')),
            'bairro'      => trim($this->post('bairro', '')),
            'cidade'      => trim($this->post('cidade', '')),
            'uf'          => strtoupper(trim($this->post('uf', ''))),
            'status'      => 'ativo',
        ];

        $id = $this->model->insert($data);
        $cliente = $this->model->find($id);
        $this->json(['success' => true, 'cliente' => $cliente]);
    }
}
