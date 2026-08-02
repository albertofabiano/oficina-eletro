-- =====================================================
-- SEEDS - DADOS INICIAIS
-- =====================================================

-- Empresa demo
INSERT INTO `empresas` (`id`, `razao_social`, `nome_fantasia`, `email`, `telefone`, `cidade`, `uf`, `plano`, `ativo`) VALUES
(1, 'Assistência Técnica Demo LTDA', 'TechFix Eletrônicos', 'admin@techfix.com', '(11) 99999-0000', 'São Paulo', 'SP', 'profissional', 1);

-- Usuário superadmin (senha: Admin@123)
INSERT INTO `usuarios` (`empresa_id`, `nome`, `email`, `senha`, `perfil`, `ativo`) VALUES
(1, 'Administrador', 'admin@techfix.com', '$2y$12$Kqe8IEMYjf3bVvpGFqcmguMHvdEXK9yYjFJRe5N8qEeLbR7Np8WXq', 'admin', 1);

-- Status OS padrão
INSERT INTO `os_status` (`empresa_id`, `nome`, `cor`, `ordem`, `tipo`) VALUES
(1, 'Aguardando Diagnóstico', '#6c757d', 1, 'aberta'),
(1, 'Em Diagnóstico', '#0dcaf0', 2, 'em_andamento'),
(1, 'Aguardando Aprovação', '#ffc107', 3, 'aguardando'),
(1, 'Aguardando Peças', '#fd7e14', 4, 'aguardando'),
(1, 'Em Reparo', '#0d6efd', 5, 'em_andamento'),
(1, 'Pronto para Retirada', '#198754', 6, 'concluida'),
(1, 'Entregue', '#20c997', 7, 'entregue'),
(1, 'Cancelado', '#dc3545', 8, 'cancelada'),
(1, 'Sem Conserto', '#6f42c1', 9, 'cancelada');

-- Estágios CRM
INSERT INTO `crm_estagios` (`empresa_id`, `nome`, `cor`, `ordem`, `tipo`) VALUES
(1, 'Primeiro Contato', '#0d6efd', 1, 'aberto'),
(1, 'Orçamento Enviado', '#ffc107', 2, 'aberto'),
(1, 'Em Negociação', '#fd7e14', 3, 'aberto'),
(1, 'Ganho', '#198754', 4, 'ganho'),
(1, 'Perdido', '#dc3545', 5, 'perdido');

-- Categorias de equipamento
INSERT INTO `categorias_equipamento` (`empresa_id`, `nome`, `icone`) VALUES
(1, 'Celular/Smartphone', 'bi-phone'),
(1, 'Tablet', 'bi-tablet'),
(1, 'Notebook/Laptop', 'bi-laptop'),
(1, 'Desktop/PC', 'bi-pc-display'),
(1, 'Televisão', 'bi-tv'),
(1, 'Monitor', 'bi-display'),
(1, 'Geladeira/Freezer', 'bi-snow'),
(1, 'Máquina de Lavar', 'bi-water'),
(1, 'Micro-ondas', 'bi-box2'),
(1, 'Ar Condicionado', 'bi-wind'),
(1, 'Impressora', 'bi-printer'),
(1, 'Videogame/Console', 'bi-joystick'),
(1, 'Som/Áudio', 'bi-speaker'),
(1, 'Câmera/Fotográfico', 'bi-camera'),
(1, 'Outro', 'bi-tools');

-- Categorias de produtos
INSERT INTO `categorias_produto` (`empresa_id`, `nome`) VALUES
(1, 'Telas e Displays'),
(1, 'Baterias'),
(1, 'Placas e Circuitos'),
(1, 'Conectores e Cabos'),
(1, 'Carcaças e Gabinetes'),
(1, 'Componentes Eletrônicos'),
(1, 'Ferramentas'),
(1, 'Materiais de Limpeza'),
(1, 'Acessórios Gerais');

-- Categorias financeiras
INSERT INTO `fin_categorias` (`empresa_id`, `tipo`, `nome`, `cor`) VALUES
(1, 'receita', 'Serviços de Reparo', '#198754'),
(1, 'receita', 'Venda de Peças', '#20c997'),
(1, 'receita', 'Diagnóstico', '#0dcaf0'),
(1, 'receita', 'Outros Serviços', '#0d6efd'),
(1, 'despesa', 'Compra de Peças', '#dc3545'),
(1, 'despesa', 'Aluguel', '#fd7e14'),
(1, 'despesa', 'Salários', '#ffc107'),
(1, 'despesa', 'Energia/Água/Net', '#6c757d'),
(1, 'despesa', 'Ferramentas/Equipamentos', '#6f42c1'),
(1, 'despesa', 'Marketing', '#d63384'),
(1, 'despesa', 'Impostos', '#212529'),
(1, 'despesa', 'Outros', '#adb5bd');

-- Conta financeira padrão
INSERT INTO `fin_contas` (`empresa_id`, `nome`, `tipo`, `saldo_inicial`, `saldo_atual`) VALUES
(1, 'Caixa', 'caixa', 0.00, 0.00),
(1, 'Conta Corrente', 'banco', 0.00, 0.00);

-- Configurações padrão
INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`, `tipo`) VALUES
(1, 'os_prefixo', 'OS', 'texto'),
(1, 'os_digitos', '6', 'numero'),
(1, 'garantia_padrao_dias', '90', 'numero'),
(1, 'prazo_retirada_dias', '30', 'numero'),
(1, 'comissao_tecnico_percentual', '20', 'numero'),
(1, 'moeda', 'BRL', 'texto'),
(1, 'notif_estoque_minimo', '1', 'booleano'),
(1, 'notif_os_prazo', '1', 'booleano'),
(1, 'tema', 'light', 'lista');
