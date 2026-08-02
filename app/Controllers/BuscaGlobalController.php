<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

/** Busca global do topbar: OS, Clientes e Produtos da empresa logada, num resultado só. */
class BuscaGlobalController extends Controller
{
    public function buscar(): void
    {
        $termo = trim($this->get('q', ''));
        if (mb_strlen($termo) < 2) { $this->json(['resultados' => []]); }

        $eid  = Auth::empresaId();
        $db   = DB::pdo();
        $b    = "%{$termo}%";
        $bNum = "%" . preg_replace('/\D/', '', $termo) . "%";
        $resultados = [];

        $stmt = $db->prepare(
            "SELECT os.id, os.numero, c.nome AS cliente_nome, eq.marca, eq.modelo, st.nome AS status_nome
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status st ON st.id = os.status_id
             WHERE os.empresa_id = ?
               AND (os.numero LIKE ? OR c.nome LIKE ? OR eq.marca LIKE ? OR eq.modelo LIKE ?)
             ORDER BY os.id DESC LIMIT 6"
        );
        $stmt->execute([$eid, $b, $b, $b, $b]);
        foreach ($stmt->fetchAll() as $r) {
            $equip = trim(($r['marca'] ?? '') . ' ' . ($r['modelo'] ?? ''));
            $resultados[] = [
                'categoria' => 'OS',
                'icone'     => 'bi-clipboard2-pulse',
                'titulo'    => 'OS ' . $r['numero'] . ($equip !== '' ? ' — ' . $equip : ''),
                'subtitulo' => trim(($r['cliente_nome'] ?? '') . ($r['status_nome'] ? ' · ' . $r['status_nome'] : '')),
                'url'       => url('/os/' . $r['id']),
            ];
        }

        $stmt = $db->prepare(
            "SELECT id, nome, telefone, cpf_cnpj FROM clientes
             WHERE empresa_id = ? AND status != 'bloqueado'
               AND (nome LIKE ? OR telefone LIKE ? OR cpf_cnpj LIKE ?
                    OR REPLACE(REPLACE(REPLACE(REPLACE(telefone,'(',''),')',''),'-',''),' ','') LIKE ?)
             ORDER BY nome LIMIT 6"
        );
        $stmt->execute([$eid, $b, $b, $b, $bNum]);
        foreach ($stmt->fetchAll() as $r) {
            $resultados[] = [
                'categoria' => 'Cliente',
                'icone'     => 'bi-person-vcard',
                'titulo'    => $r['nome'],
                'subtitulo' => $r['telefone'] ?: ($r['cpf_cnpj'] ?: ''),
                'url'       => url('/clientes/' . $r['id']),
            ];
        }

        $stmt = $db->prepare(
            "SELECT id, nome, codigo, estoque_atual, unidade FROM produtos
             WHERE empresa_id = ? AND (nome LIKE ? OR codigo LIKE ? OR codigo_barras LIKE ?)
             ORDER BY nome LIMIT 6"
        );
        $stmt->execute([$eid, $b, $b, $b]);
        foreach ($stmt->fetchAll() as $r) {
            $estoque = rtrim(rtrim(number_format((float) $r['estoque_atual'], 3, ',', '.'), '0'), ',');
            $resultados[] = [
                'categoria' => 'Produto',
                'icone'     => 'bi-box-seam',
                'titulo'    => $r['nome'],
                'subtitulo' => ($r['codigo'] ? 'Cód. ' . $r['codigo'] . ' · ' : '') . 'estoque: ' . $estoque . ' ' . $r['unidade'],
                'url'       => url('/produtos/' . $r['id'] . '/editar'),
            ];
        }

        $this->json(['resultados' => $resultados]);
    }
}
