<?php

namespace App\Models;

use App\Core\Model;

class Comissao extends Model
{
    protected string $table = 'fin_comissoes';

    public function listar(array $filtros = []): array
    {
        $eid = $this->empresaId();
        $where = "c.empresa_id = ?";
        $params = [$eid];

        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND c.tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (isset($filtros['pago']) && $filtros['pago'] !== '') {
            $where .= " AND c.pago = ?";
            $params[] = (int) $filtros['pago'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND DATE(c.criado_em) >= ?";
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND DATE(c.criado_em) <= ?";
            $params[] = $filtros['data_fim'];
        }

        return $this->query(
            "SELECT c.*, u.nome AS tecnico_nome, os.numero AS os_numero
             FROM fin_comissoes c
             JOIN usuarios u ON u.id = c.tecnico_id
             LEFT JOIN ordens_servico os ON os.id = c.os_id
             WHERE {$where}
             ORDER BY c.pago ASC, c.criado_em DESC",
            $params
        );
    }

    public function totais(array $filtros = []): array
    {
        $linhas = $this->listar($filtros);
        $pendente = 0.0;
        $pago = 0.0;
        foreach ($linhas as $l) {
            if ((int) $l['pago'] === 1) $pago += (float) $l['valor_comissao'];
            else $pendente += (float) $l['valor_comissao'];
        }
        return ['pendente' => $pendente, 'pago' => $pago, 'total' => $pendente + $pago];
    }
}
