<?php

namespace App\Models;

use App\Core\Model;

class OrdemServico extends Model
{
    protected string $table = 'ordens_servico';

    public function listar(int $page = 1, int $perPage = 20, array $filtros = []): array
    {
        $eid = $this->empresaId();
        $where = "os.empresa_id = ?";
        $params = [$eid];

        if (!empty($filtros['status_id'])) {
            $where .= " AND os.status_id = ?";
            $params[] = $filtros['status_id'];
        }
        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND os.tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (!empty($filtros['prioridade'])) {
            $where .= " AND os.prioridade = ?";
            $params[] = $filtros['prioridade'];
        }
        if (!empty($filtros['busca'])) {
            $b    = "%" . $filtros['busca'] . "%";
            $bNum = "%" . preg_replace('/\D/', '', $filtros['busca']) . "%";
            $where .= " AND (
              os.numero    LIKE ?
              OR c.nome    LIKE ?
              OR c.cpf_cnpj LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(c.cpf_cnpj,'.',''),'-',''),'/',''),' ','') LIKE ?
              OR c.telefone LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.telefone,'(',''),')',''),'-',''),' ',''),'+','') LIKE ?
              OR c.whatsapp LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.whatsapp,'(',''),')',''),'-',''),' ',''),'+','') LIKE ?
              OR eq.modelo LIKE ?
              OR eq.marca  LIKE ?
              OR eq.numero_serie LIKE ?
            )";
            // b para texto, bNum para campos com dÃ­gitos
            $bN = strlen($bNum) > 2 ? $bNum : $b;
            array_push($params, $b, $b, $b, $bN, $b, $bN, $b, $bN, $b, $b, $b);
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND DATE(os.data_entrada) >= ?";
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND DATE(os.data_entrada) <= ?";
            $params[] = $filtros['data_fim'];
        }
        if (!empty($filtros['em_garantia'])) {
            $where .= " AND os.tipo_servico = 'garantia' AND os.os_origem_id IS NOT NULL
              AND os.status_id NOT IN (SELECT id FROM os_status WHERE empresa_id = os.empresa_id AND tipo IN ('concluida','entregue','cancelada'))";
        }

        if (!empty($filtros['fechadas'])) {
            $where .= " AND os.status_id IN (SELECT id FROM os_status WHERE empresa_id = os.empresa_id AND tipo = 'entregue')";
        } elseif (empty($filtros['status_id']) && empty($filtros['busca'])) {
            // Só esconde as fechadas na lista "padrão" (sem busca nem status escolhido).
            // Com busca preenchida, o usuário quer achar algo específico -- fechada ou não.
            $where .= " AND os.status_id NOT IN (SELECT id FROM os_status WHERE empresa_id = os.empresa_id AND tipo = 'entregue')";
        }

        // "Sem Conserto/Recusado" nunca conta no total, mesmo com valor_total preenchido (orçamento
        // recusado) — vale tanto pra quem já fechou como "Fechado" (fechada_sem_receita=1) quanto
        // pra quem só mudou o status pra um tipo 'cancelada' sem passar pelo fluxo de fechamento.
        $stmtCount = $this->db->prepare(
            "SELECT COUNT(*), COALESCE(SUM(CASE WHEN os.fechada_sem_receita = 1 OR s.tipo = 'cancelada' THEN 0 ELSE os.valor_total END), 0)
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE {$where}"
        );
        $stmtCount->execute($params);
        [$total, $somaValor] = $stmtCount->fetch(\PDO::FETCH_NUM);
        $total     = (int) $total;
        $somaValor = (float) $somaValor;

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT os.*, c.nome AS cliente_nome, c.telefone AS cliente_tel, c.whatsapp AS cliente_whats,
             c.contato AS cliente_contato,
             eq.tipo AS equip_tipo, eq.marca AS equip_marca, eq.modelo AS equip_modelo, eq.acessorios AS equip_acessorios,
             s.nome AS status_nome, s.cor AS status_cor, s.cor_fonte AS status_cor_fonte, s.tipo AS status_tipo,
             u.nome AS tecnico_nome
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status s ON s.id = os.status_id
             LEFT JOIN usuarios u ON u.id = os.tecnico_id
             WHERE {$where}
             ORDER BY os.criado_em DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'soma_valor'   => $somaValor,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    public function findCompleto(int $id): ?array
    {
        $eid = $this->empresaId();
        $os = $this->queryOne(
            "SELECT os.*, c.nome AS cliente_nome, c.telefone AS cliente_tel, c.whatsapp AS cliente_whats,
             c.email AS cliente_email, c.cpf_cnpj, c.contato AS cliente_contato,
             c.logradouro AS cli_logradouro, c.numero AS cli_numero,
             c.complemento AS cli_complemento, c.bairro AS cli_bairro,
             c.cidade AS cli_cidade, c.uf AS cli_uf, c.cep AS cli_cep,
             eq.tipo AS equip_tipo, eq.marca AS equip_marca, eq.modelo AS equip_modelo,
             eq.numero_serie, eq.imei, eq.cor AS equip_cor, eq.voltagem, eq.especificacao, eq.estado_entrada,
             eq.observacoes AS equip_observacoes,
             eq.acessorios, eq.senha_desbloqueio, eq.categoria_id,
             eq.tipo_armazenamento, eq.memoria_ram, eq.placa_video, eq.placa_mae, eq.processador,
             s.nome AS status_nome, s.cor AS status_cor, s.cor_fonte AS status_cor_fonte, s.tipo AS status_tipo,
             s.codigo AS status_codigo, s.permite_fechar AS status_permite_fechar,
             u.nome AS tecnico_nome, r.nome AS recepcionista_nome,
             e.nome_fantasia AS empresa_nome, e.razao_social AS empresa_razao,
             e.cnpj AS empresa_cnpj, e.email AS emp_email,
             e.telefone AS emp_tel, e.whatsapp AS emp_whatsapp,
             e.logradouro AS emp_logradouro, e.numero AS emp_numero,
             e.complemento AS emp_complemento, e.bairro AS emp_bairro,
             e.cidade AS emp_cidade, e.uf AS emp_uf, e.cep AS emp_cep,
             e.logo AS emp_logo,
             orig.numero AS os_origem_numero
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status s ON s.id = os.status_id
             LEFT JOIN usuarios u ON u.id = os.tecnico_id
             LEFT JOIN usuarios r ON r.id = os.recepcionista_id
             LEFT JOIN empresas e ON e.id = os.empresa_id
             LEFT JOIN ordens_servico orig ON orig.id = os.os_origem_id
             WHERE os.id = ? AND os.empresa_id = ?",
            [$id, $eid]
        );
        if (!$os) return null;

        $os['servicos'] = $this->query(
            "SELECT oss.*, u.nome AS tecnico_nome FROM os_servicos oss
             LEFT JOIN usuarios u ON u.id = oss.tecnico_id
             WHERE oss.os_id = ? AND oss.empresa_id = ?",
            [$id, $eid]
        );
        $os['pecas'] = $this->query(
            "SELECT osp.*, p.codigo AS prod_codigo FROM os_pecas osp
             LEFT JOIN produtos p ON p.id = osp.produto_id
             WHERE osp.os_id = ? AND osp.empresa_id = ?",
            [$id, $eid]
        );
        $os['historico'] = $this->query(
            "SELECT h.*, u.nome AS usuario_nome, sa.nome AS status_ant, sn.nome AS status_nov
             FROM os_historico h
             LEFT JOIN usuarios u ON u.id = h.usuario_id
             LEFT JOIN os_status sa ON sa.id = h.status_anterior_id
             LEFT JOIN os_status sn ON sn.id = h.status_novo_id
             WHERE h.os_id = ? ORDER BY h.criado_em DESC",
            [$id]
        );
        // OS filhas de garantia
        $os['garantias'] = $this->query(
            "SELECT g.*, s.nome AS status_nome, s.cor AS status_cor, s.cor_fonte AS status_cor_fonte, s.tipo AS status_tipo
             FROM ordens_servico g
             LEFT JOIN os_status s ON s.id = g.status_id
             WHERE g.os_origem_id = ? AND g.empresa_id = ? ORDER BY g.criado_em DESC",
            [$id, $eid]
        );
        // Status de garantia calculado
        $os['em_garantia'] = !empty($os['garantia_ate']) && strtotime($os['garantia_ate']) >= strtotime('today');
        $os['dias_garantia_restantes'] = !empty($os['garantia_ate'])
            ? (int) ceil((strtotime($os['garantia_ate']) - strtotime('today')) / 86400)
            : null;
        return $os;
    }

    public function proximoNumero(): string
    {
        $eid = $this->empresaId();

        // Busca todas as configs de uma vez
        $stmtCfg = $this->db->prepare(
            "SELECT chave, valor FROM configuracoes
             WHERE empresa_id = ? AND chave IN ('os_prefixo','os_digitos','os_numero_inicial')"
        );
        $stmtCfg->execute([$eid]);
        $cfg = [];
        foreach ($stmtCfg->fetchAll() as $row) $cfg[$row['chave']] = $row['valor'];

        $prefixo       = ''; // sem prefixo
        $digitos       = (int) ($cfg['os_digitos']  ?? 6);
        $numeroInicial = (int) ($cfg['os_numero_inicial'] ?? 1);

        // Maior nÃºmero jÃ¡ usado no banco
        $stmt = $this->db->prepare(
            "SELECT MAX(CAST(numero AS UNSIGNED)) FROM ordens_servico WHERE empresa_id = ?"
        );
        $stmt->execute([$eid]);
        $ultimoNoBanco = (int) $stmt->fetchColumn();

        // PrÃ³ximo = maior entre (Ãºltimo do banco + 1) e o nÃºmero inicial configurado
        $proximo = max($ultimoNoBanco + 1, $numeroInicial);

        return $prefixo . str_pad($proximo, $digitos, '0', STR_PAD_LEFT);
    }

    public function registrarHistorico(int $osId, ?int $statusAnt, int $statusNov, string $descricao = ''): void
    {
        $this->db->prepare(
            "INSERT INTO os_historico (empresa_id, os_id, usuario_id, status_anterior_id, status_novo_id, descricao)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$this->empresaId(), $osId, $_SESSION['usuario_id'] ?? null, $statusAnt, $statusNov, $descricao]);
    }

    public function totalEmGarantia(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
               AND os.tipo_servico = 'garantia' AND os.os_origem_id IS NOT NULL
               AND s.tipo NOT IN ('concluida','entregue','cancelada')"
        );
        $stmt->execute([$this->empresaId()]);
        return (int) $stmt->fetchColumn();
    }

    /** Total de OS atrasadas (previsão vencida, ainda não concluída/entregue/cancelada) — mesma regra do dashboard. */
    public function totalAtrasadas(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
               AND os.data_previsao < NOW()
               AND s.tipo NOT IN ('concluida','entregue','cancelada')"
        );
        $stmt->execute([$this->empresaId()]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Resumo ao vivo das OS atrasadas pro painel de notificações — total +
     * data prevista da mais antiga, direto na tabela de origem (não na
     * notificacoes, que só guarda quando a notificação foi gerada).
     */
    public function resumoAtrasadas(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, MIN(os.data_previsao) AS mais_antiga
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
               AND os.data_previsao < NOW()
               AND s.tipo NOT IN ('concluida','entregue','cancelada')"
        );
        $stmt->execute([$this->empresaId()]);
        $r = $stmt->fetch();
        return ['total' => (int) ($r['total'] ?? 0), 'mais_antiga' => $r['mais_antiga'] ?? null];
    }

    /** Resumo ao vivo dos orçamentos aguardando aprovação (mesma regra da notificação, sem o corte de 2 dias). */
    public function resumoAguardandoAprovacao(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, MIN(os.atualizado_em) AS mais_antiga
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
               AND s.tipo = 'aguardando'
               AND os.status_id NOT IN (SELECT id FROM os_status WHERE tipo IN ('entregue','cancelada'))"
        );
        $stmt->execute([$this->empresaId()]);
        $r = $stmt->fetch();
        return ['total' => (int) ($r['total'] ?? 0), 'mais_antiga' => $r['mais_antiga'] ?? null];
    }

    public function totalFechadas(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND s.tipo = 'entregue'"
        );
        $stmt->execute([$this->empresaId()]);
        return (int) $stmt->fetchColumn();
    }

    public function totaisPorStatus(): array
    {
        return $this->query(
            "SELECT s.id, s.nome, s.cor, s.cor_fonte, s.tipo,
             COUNT(CASE WHEN s.tipo != 'garantia'
                         AND (os.tipo_servico != 'garantia' OR os.tipo_servico IS NULL
                              OR s.tipo IN ('concluida','entregue','cancelada'))
                        THEN os.id END) AS total
             FROM os_status s
             LEFT JOIN ordens_servico os ON os.status_id = s.id AND os.empresa_id = s.empresa_id
             WHERE s.empresa_id = ? AND s.tipo NOT IN ('garantia','entregue')
             GROUP BY s.id ORDER BY s.ordem",
            [$this->empresaId()]
        );
    }

    public function calcularTotal(int $osId): array
    {
        $eid = $this->empresaId();

        $s = $this->db->prepare("SELECT COALESCE(SUM(valor_total),0) FROM os_servicos WHERE os_id = ? AND empresa_id = ?");
        $s->execute([$osId, $eid]);
        $totalServicos = (float) $s->fetchColumn();

        $p = $this->db->prepare("SELECT COALESCE(SUM(valor_total),0) FROM os_pecas WHERE os_id = ? AND empresa_id = ?");
        $p->execute([$osId, $eid]);
        $totalPecas = (float) $p->fetchColumn();

        return ['servicos' => $totalServicos, 'pecas' => $totalPecas, 'total' => $totalServicos + $totalPecas];
    }
}

