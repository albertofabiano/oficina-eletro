-- View unificada do financeiro (utf8mb4 corrigido)
DROP VIEW IF EXISTS v_financeiro_unificado;

CREATE VIEW v_financeiro_unificado AS

SELECT
  fl.empresa_id,
  fl.id AS ref_id,
  CONVERT('lancamento' USING utf8mb4) AS fonte,
  fl.tipo, fl.descricao, fl.valor, fl.data_vencimento, fl.data_pagamento,
  fl.status, fl.forma_pagamento, fl.os_id, fl.cliente_id,
  CONVERT(IFNULL(fc.nome,'') USING utf8mb4) AS categoria_nome,
  CONVERT(IFNULL(fc.cor,'#6c757d') USING utf8mb4) AS categoria_cor,
  CONVERT(IFNULL(c.nome,'') USING utf8mb4) AS cliente_nome,
  NULL AS numero_os
FROM fin_lancamentos fl
LEFT JOIN fin_categorias fc ON fc.id = fl.categoria_id
LEFT JOIN clientes c ON c.id = fl.cliente_id

UNION ALL

SELECT
  os.empresa_id, os.id AS ref_id,
  CONVERT('os' USING utf8mb4) AS fonte,
  CONVERT('receita' USING utf8mb4) AS tipo,
  CONVERT(CONCAT('OS #', os.numero, ' - ', c.nome) USING utf8mb4) AS descricao,
  os.valor_total AS valor, DATE(os.data_conclusao) AS data_vencimento,
  DATE(os.data_conclusao) AS data_pagamento,
  CONVERT(CASE WHEN os.situacao_pagamento='pago' THEN 'pago' WHEN os.situacao_pagamento='parcial' THEN 'parcial' ELSE 'pendente' END USING utf8mb4) AS status,
  os.forma_pagamento_fechamento AS forma_pagamento,
  os.id AS os_id, os.cliente_id,
  CONVERT('Servicos / OS' USING utf8mb4) AS categoria_nome,
  CONVERT('#198754' USING utf8mb4) AS categoria_cor,
  CONVERT(c.nome USING utf8mb4) AS cliente_nome,
  CONVERT(os.numero USING utf8mb4) AS numero_os
FROM ordens_servico os
JOIN os_status s ON s.id = os.status_id
JOIN clientes c ON c.id = os.cliente_id
WHERE s.tipo IN ('concluida','entregue') AND os.valor_total > 0
  AND NOT EXISTS (SELECT 1 FROM fin_lancamentos fl WHERE fl.os_id=os.id AND fl.tipo='receita' AND fl.empresa_id=os.empresa_id);