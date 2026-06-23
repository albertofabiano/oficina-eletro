<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class FornecedorController extends Controller
{
    public function index(): void
    {
        $eid    = $this->empresaId();
        $page   = (int) $this->get('page', 1);
        $busca  = $this->get('busca', '');
        $perPage = 20;

        $where  = "empresa_id = ?";
        $params = [$eid];
        if ($busca) {
            $where  .= " AND (razao_social LIKE ? OR cnpj_cpf LIKE ? OR email LIKE ?)";
            array_push($params, "%$busca%", "%$busca%", "%$busca%");
        }

        $db = DB::pdo();
        $stmtC = $db->prepare("SELECT COUNT(*) FROM fornecedores WHERE $where");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare("SELECT * FROM fornecedores WHERE $where ORDER BY razao_social LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);

        $this->view('fornecedores.index', [
            'titulo'    => 'Fornecedores',
            'dados'     => $stmt->fetchAll(),
            'busca'     => $busca,
            'paginator' => ['data' => $stmt->fetchAll(), 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => (int) ceil($total / $perPage)],
        ]);
    }

    public function criar(): void
    {
        $this->view('fornecedores.form', ['titulo' => 'Novo Fornecedor', 'fornecedor' => []]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "INSERT INTO fornecedores (empresa_id, razao_social, nome_fantasia, cnpj_cpf, email, telefone, whatsapp, site, contato_nome, cep, logradouro, numero, cidade, uf, observacoes, ativo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)"
        )->execute([
            $eid,
            $this->post('razao_social'),
            $this->post('nome_fantasia'),
            only_numbers($this->post('cnpj_cpf', '')),
            $this->post('email'),
            $this->post('telefone'),
            $this->post('whatsapp'),
            $this->post('site'),
            $this->post('contato_nome'),
            only_numbers($this->post('cep', '')),
            $this->post('logradouro'),
            $this->post('numero'),
            $this->post('cidade'),
            $this->post('uf'),
            $this->post('observacoes'),
        ]);

        $this->flash('success', 'Fornecedor cadastrado!');
        $this->redirect(url('/fornecedores'));
    }

    public function editar(string $id): void
    {
        $eid  = $this->empresaId();
        $stmt = DB::pdo()->prepare("SELECT * FROM fornecedores WHERE id = ? AND empresa_id = ?");
        $stmt->execute([(int) $id, $eid]);
        $fornecedor = $stmt->fetch();
        if (!$fornecedor) { $this->flash('error', 'Fornecedor não encontrado.'); $this->redirect(url('/fornecedores')); }
        $this->view('fornecedores.form', ['titulo' => 'Editar Fornecedor', 'fornecedor' => $fornecedor]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "UPDATE fornecedores SET razao_social=?, nome_fantasia=?, cnpj_cpf=?, email=?, telefone=?, whatsapp=?, site=?, contato_nome=?, cep=?, logradouro=?, numero=?, cidade=?, uf=?, observacoes=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $this->post('razao_social'),
            $this->post('nome_fantasia'),
            only_numbers($this->post('cnpj_cpf', '')),
            $this->post('email'),
            $this->post('telefone'),
            $this->post('whatsapp'),
            $this->post('site'),
            $this->post('contato_nome'),
            only_numbers($this->post('cep', '')),
            $this->post('logradouro'),
            $this->post('numero'),
            $this->post('cidade'),
            $this->post('uf'),
            $this->post('observacoes'),
            (int) $id, $eid,
        ]);

        $this->flash('success', 'Fornecedor atualizado!');
        $this->redirect(url('/fornecedores'));
    }

    public function excluir(string $id): void
    {
        DB::pdo()->prepare("DELETE FROM fornecedores WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->empresaId()]);
        $this->flash('success', 'Fornecedor removido.');
        $this->redirect(url('/fornecedores'));
    }
}
