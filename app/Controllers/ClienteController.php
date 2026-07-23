<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
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
            'contato'        => trim($this->post('contato', '')),
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
            'estrelas'       => max(0, min(5, (int) $this->post('estrelas', 0))),
            'status'         => 'ativo',
        ];
        $this->espelharContato($data);
        $data['contato'] = $data['contato'] ?: $this->primeiroNome($data['nome']);

        if ($e = $this->erroDocumento($data['cpf_cnpj'])) { $this->flash('error', $e); $this->redirectBack(); }

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
            'contato'     => trim($this->post('contato', '')),
            'cep'         => only_numbers($this->post('cep', '')),
            'logradouro'  => $this->post('logradouro', ''),
            'numero'      => $this->post('numero', ''),
            'complemento' => $this->post('complemento', ''),
            'bairro'      => $this->post('bairro', ''),
            'cidade'      => $this->post('cidade', ''),
            'uf'          => $this->post('uf', ''),
            'origem'      => $this->post('origem', 'balcao'),
            'observacoes' => $this->post('observacoes', ''),
            'estrelas'    => max(0, min(5, (int) $this->post('estrelas', 0))),
            'status'      => $this->post('status', 'ativo'),
        ];
        $this->espelharContato($data);
        $data['contato'] = $data['contato'] ?: $this->primeiroNome($data['nome']);

        if ($e = $this->erroDocumento($data['cpf_cnpj'])) { $this->flash('error', $e); $this->redirectBack(); }

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
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Apenas o administrador da empresa pode excluir clientes.');
            $this->redirect(url('/clientes'));
            return;
        }
        try {
            $this->model->delete((int) $id);
            $this->flash('success', 'Cliente removido.');
        } catch (\PDOException $e) {
            $this->flash('error', 'Não é possível excluir este cliente: existem OS, equipamentos ou outros registros vinculados a ele.');
        }
        $this->redirect(url('/clientes'));
    }

    public function buscarAjax(): void
    {
        $termo = $this->get('q', '');
        $this->json($this->model->buscar($termo));
    }

    /** Consulta dados de um CNPJ na Receita Federal (via BrasilAPI) para autopreencher o cadastro. */
    public function buscarCnpj(string $cnpj): void
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) { $this->json(['error' => 'Informe um CNPJ com 14 dígitos.'], 400); }

        $ch = curl_init("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'FixaOS/1.0',
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) { $this->json(['error' => 'Falha ao consultar a Receita. Tente de novo.'], 502); }
        $d = json_decode($resp, true);
        if ($code !== 200 || !is_array($d) || empty($d['razao_social'])) {
            $msg = (is_array($d) && !empty($d['message'])) ? $d['message'] : 'CNPJ não encontrado.';
            $this->json(['error' => $msg], 404);
        }

        // Telefone (ddd_telefone_1 vem só com dígitos)
        $tel = preg_replace('/\D/', '', (string) ($d['ddd_telefone_1'] ?? ''));
        $telFmt = '';
        if (strlen($tel) === 10) $telFmt = sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 4), substr($tel, 6));
        elseif (strlen($tel) === 11) $telFmt = sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 5), substr($tel, 7));

        $logradouro = trim(($d['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($d['logradouro'] ?? ''));

        $this->json([
            'success'      => true,
            'razao_social' => $d['razao_social'] ?? '',
            'fantasia'     => $d['nome_fantasia'] ?? '',
            'situacao'     => $d['descricao_situacao_cadastral'] ?? '',
            'email'        => $d['email'] ?? '',
            'telefone'     => $telFmt,
            'cep'          => preg_replace('/\D/', '', (string) ($d['cep'] ?? '')),
            'logradouro'   => $logradouro,
            'numero'       => (string) ($d['numero'] ?? ''),
            'complemento'  => $d['complemento'] ?? '',
            'bairro'       => $d['bairro'] ?? '',
            'cidade'       => $d['municipio'] ?? '',
            'uf'           => $d['uf'] ?? '',
        ]);
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
            'contato'     => trim($this->post('contato', '')),
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
        $this->espelharContato($data);
        $data['contato'] = $data['contato'] ?: $this->primeiroNome($data['nome']);

        if ($e = $this->erroDocumento($data['cpf_cnpj'])) { $this->json(['error' => $e], 422); }

        $id = (int) $this->post('id');
        if ($id && $this->model->find($id)) {
            $this->model->update($id, $data);
        } else {
            $id = $this->model->insert($data);
        }
        $cliente = $this->model->find($id);
        $this->json(['success' => true, 'cliente' => $cliente]);
    }

    /** Valida CPF/CNPJ (vazio = ok). Retorna msg de erro ou null. */
    private function erroDocumento(string $cpfCnpj): ?string
    {
        return ($cpfCnpj !== '' && !documento_valido($cpfCnpj)) ? 'CPF/CNPJ inválido — confira os dígitos.' : null;
    }

    /**
     * Espelha WhatsApp x Telefone: se um estiver vazio, copia o outro.
     * Melhor duplicar o número do que deixar campo em branco — e conserta quem
     * digita o WhatsApp no campo "Telefone" e deixa o WhatsApp vazio (e vice-versa).
     */
    private function espelharContato(array &$data): void
    {
        $w = trim($data['whatsapp'] ?? '');
        $t = trim($data['telefone'] ?? '');
        if ($w !== '' && $t === '')      { $t = $w; }
        elseif ($t !== '' && $w === '')  { $w = $t; }
        $data['whatsapp'] = $w;
        $data['telefone'] = $t;
    }

    /** Primeiro nome (Title Case) — usado como contato padrão quando o campo vem vazio. */
    private function primeiroNome(string $nome): string
    {
        $p = preg_split('/\s+/', trim($nome))[0] ?? '';
        return $p === '' ? '' : mb_convert_case(mb_strtolower($p, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
