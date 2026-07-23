-- =====================================================
-- SISTEMA OFICINA ELETRÔNICA - SCHEMA COMPLETO
-- Versão 1.0 | MySQL 5.7+ / MariaDB 10.3+
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- =====================================================
-- MULTIEMPRESAS
-- =====================================================

CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `razao_social` VARCHAR(150) NOT NULL,
  `nome_fantasia` VARCHAR(100),
  `cnpj` VARCHAR(18),
  `cpf` VARCHAR(14),
  `ie` VARCHAR(30),
  `email` VARCHAR(100),
  `telefone` VARCHAR(20),
  `whatsapp` VARCHAR(20),
  `cep` VARCHAR(9),
  `logradouro` VARCHAR(150),
  `numero` VARCHAR(10),
  `complemento` VARCHAR(60),
  `bairro` VARCHAR(80),
  `cidade` VARCHAR(80),
  `uf` CHAR(2),
  `logo` VARCHAR(255),
  `plano` ENUM('basico','profissional','enterprise') DEFAULT 'basico',
  `max_usuarios` TINYINT UNSIGNED DEFAULT 3,
  `max_os_mes` SMALLINT UNSIGNED DEFAULT 100,
  `ativo` TINYINT(1) DEFAULT 1,
  `trial_ate` DATE,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- USUÁRIOS E AUTENTICAÇÃO
-- =====================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('superadmin','admin','gerente','recepcionista','tecnico','financeiro') DEFAULT 'tecnico',
  `telefone` VARCHAR(20),
  `avatar` VARCHAR(255),
  `token_reset` VARCHAR(64),
  `token_reset_expira` DATETIME,
  `dois_fatores` TINYINT(1) DEFAULT 0,
  `dois_fatores_segredo` VARCHAR(64),
  `ultimo_login` DATETIME,
  `ativo` TINYINT(1) DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_email_empresa` (`email`, `empresa_id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `usuario_permissoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `acao` SET('ver','criar','editar','excluir','relatorio') DEFAULT 'ver',
  UNIQUE KEY `uq_perm` (`usuario_id`, `modulo`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `log_acoes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `modulo` VARCHAR(50),
  `acao` VARCHAR(100),
  `registro_id` INT UNSIGNED,
  `detalhes` JSON,
  `ip` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CLIENTES / CRM
-- =====================================================

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('pf','pj') DEFAULT 'pf',
  `nome` VARCHAR(150) NOT NULL,
  `cpf_cnpj` VARCHAR(18),
  `rg_ie` VARCHAR(30),
  `email` VARCHAR(100),
  `telefone` VARCHAR(20),
  `whatsapp` VARCHAR(20),
  `cep` VARCHAR(9),
  `logradouro` VARCHAR(150),
  `numero` VARCHAR(10),
  `complemento` VARCHAR(60),
  `bairro` VARCHAR(80),
  `cidade` VARCHAR(80),
  `uf` CHAR(2),
  `data_nascimento` DATE,
  `profissao` VARCHAR(80),
  `indicado_por` INT UNSIGNED,
  `origem` ENUM('balcao','telefone','whatsapp','site','indicacao','outro') DEFAULT 'balcao',
  `observacoes` TEXT,
  `tags` VARCHAR(255),
  `status` ENUM('ativo','inativo','bloqueado') DEFAULT 'ativo',
  `saldo_credito` DECIMAL(10,2) DEFAULT 0.00,
  `limite_credito` DECIMAL(10,2) DEFAULT 0.00,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`indicado_por`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  INDEX `idx_cliente_empresa` (`empresa_id`),
  INDEX `idx_cliente_cpf` (`cpf_cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CRM Pipeline
CREATE TABLE IF NOT EXISTS `crm_estagios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `cor` VARCHAR(7) DEFAULT '#6c757d',
  `ordem` TINYINT UNSIGNED DEFAULT 0,
  `tipo` ENUM('aberto','ganho','perdido') DEFAULT 'aberto',
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `crm_oportunidades` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `estagio_id` INT UNSIGNED NOT NULL,
  `responsavel_id` INT UNSIGNED,
  `titulo` VARCHAR(150) NOT NULL,
  `valor_estimado` DECIMAL(10,2),
  `probabilidade` TINYINT UNSIGNED DEFAULT 50,
  `data_fechamento_prevista` DATE,
  `descricao` TEXT,
  `motivo_perda` VARCHAR(255),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`estagio_id`) REFERENCES `crm_estagios`(`id`),
  FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `crm_contatos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `oportunidade_id` INT UNSIGNED,
  `os_id` INT UNSIGNED,
  `usuario_id` INT UNSIGNED,
  `tipo` ENUM('ligacao','whatsapp','email','visita','sms','outro') DEFAULT 'ligacao',
  `direcao` ENUM('entrada','saida') DEFAULT 'saida',
  `assunto` VARCHAR(150),
  `descricao` TEXT,
  `proximo_contato` DATETIME,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- EQUIPAMENTOS (foco eletrônica/eletrodomésticos)
-- =====================================================

CREATE TABLE IF NOT EXISTS `categorias_equipamento` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `icone` VARCHAR(50) DEFAULT 'bi-cpu',
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `equipamentos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED,
  `tipo` VARCHAR(80) NOT NULL COMMENT 'TV, Geladeira, Celular, Notebook...',
  `marca` VARCHAR(60),
  `modelo` VARCHAR(100),
  `numero_serie` VARCHAR(80),
  `imei` VARCHAR(20),
  `cor` VARCHAR(40),
  `voltagem` ENUM('110v','220v','bivolt','bateria','outro'),
  `ano_fabricacao` YEAR,
  `estado_entrada` ENUM('otimo','bom','regular','ruim','danificado') DEFAULT 'regular',
  `descricao_defeito_cliente` TEXT,
  `acessorios` TEXT COMMENT 'o que veio junto',
  `senha_desbloqueio` VARCHAR(30),
  `fotos` JSON,
  `observacoes` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias_equipamento`(`id`) ON DELETE SET NULL,
  INDEX `idx_equip_ns` (`numero_serie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ORDENS DE SERVIÇO
-- =====================================================

CREATE TABLE IF NOT EXISTS `os_status` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(60) NOT NULL,
  `cor` VARCHAR(7) DEFAULT '#6c757d',
  `ordem` TINYINT UNSIGNED DEFAULT 0,
  `tipo` ENUM('aberta','em_andamento','aguardando','concluida','cancelada','entregue') DEFAULT 'aberta',
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `numero` VARCHAR(20) NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `equipamento_id` INT UNSIGNED NOT NULL,
  `status_id` INT UNSIGNED NOT NULL,
  `tecnico_id` INT UNSIGNED,
  `recepcionista_id` INT UNSIGNED,
  `prioridade` ENUM('baixa','normal','alta','urgente') DEFAULT 'normal',
  `tipo_servico` ENUM('orcamento','garantia','conserto','manutencao','instalacao') DEFAULT 'conserto',
  -- Diagnóstico
  `defeito_relatado` TEXT NOT NULL,
  `defeito_constatado` TEXT,
  `laudo_tecnico` TEXT,
  `solucao_aplicada` TEXT,
  -- Valores
  `valor_diagnostico` DECIMAL(10,2) DEFAULT 0.00,
  `desconto_percentual` DECIMAL(5,2) DEFAULT 0.00,
  `desconto_valor` DECIMAL(10,2) DEFAULT 0.00,
  `valor_total` DECIMAL(10,2) DEFAULT 0.00,
  `valor_pago` DECIMAL(10,2) DEFAULT 0.00,
  `situacao_pagamento` ENUM('pendente','parcial','pago') DEFAULT 'pendente',
  -- Garantia
  `garantia_dias` SMALLINT UNSIGNED DEFAULT 90,
  `garantia_ate` DATE,
  -- Datas
  `data_entrada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_previsao` DATETIME,
  `data_conclusao` DATETIME,
  `data_entrega` DATETIME,
  `data_retirada_limite` DATE,
  -- Observações
  `observacoes_internas` TEXT,
  `observacoes_cliente` TEXT,
  -- Checklist saída
  `checklist_saida` JSON,
  `assinatura_cliente` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_os_numero_empresa` (`empresa_id`, `numero`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`),
  FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos`(`id`),
  FOREIGN KEY (`status_id`) REFERENCES `os_status`(`id`),
  FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recepcionista_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_os_empresa` (`empresa_id`),
  INDEX `idx_os_status` (`status_id`),
  INDEX `idx_os_tecnico` (`tecnico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_historico` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `status_anterior_id` INT UNSIGNED,
  `status_novo_id` INT UNSIGNED,
  `descricao` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_servicos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `quantidade` DECIMAL(8,2) DEFAULT 1.00,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tecnico_id` INT UNSIGNED,
  `tempo_minutos` INT UNSIGNED,
  `concluido` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ESTOQUE / PRODUTOS
-- =====================================================

CREATE TABLE IF NOT EXISTS `categorias_produto` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `pai_id` INT UNSIGNED,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pai_id`) REFERENCES `categorias_produto`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `razao_social` VARCHAR(150) NOT NULL,
  `nome_fantasia` VARCHAR(100),
  `cnpj_cpf` VARCHAR(18),
  `email` VARCHAR(100),
  `telefone` VARCHAR(20),
  `whatsapp` VARCHAR(20),
  `site` VARCHAR(150),
  `contato_nome` VARCHAR(100),
  `cep` VARCHAR(9),
  `logradouro` VARCHAR(150),
  `numero` VARCHAR(10),
  `cidade` VARCHAR(80),
  `uf` CHAR(2),
  `observacoes` TEXT,
  `ativo` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED,
  `fornecedor_id` INT UNSIGNED,
  `codigo` VARCHAR(50),
  `codigo_barras` VARCHAR(50),
  `nome` VARCHAR(150) NOT NULL,
  `descricao` TEXT,
  `unidade` ENUM('un','kg','g','lt','ml','m','cm','par','cx','pct') DEFAULT 'un',
  `estoque_atual` DECIMAL(10,3) DEFAULT 0.000,
  `estoque_minimo` DECIMAL(10,3) DEFAULT 0.000,
  `estoque_maximo` DECIMAL(10,3),
  `localizacao` VARCHAR(60) COMMENT 'prateleira/gaveta',
  `valor_custo` DECIMAL(10,2) DEFAULT 0.00,
  `valor_venda` DECIMAL(10,2) DEFAULT 0.00,
  `margem_lucro` DECIMAL(5,2) DEFAULT 0.00,
  `ncm` VARCHAR(10),
  `ativo` TINYINT(1) DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias_produto`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL,
  INDEX `idx_produto_codigo` (`codigo`),
  INDEX `idx_produto_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `os_pecas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `produto_id` INT UNSIGNED,
  `descricao` VARCHAR(255) NOT NULL,
  `quantidade` DECIMAL(8,3) DEFAULT 1.000,
  `valor_custo` DECIMAL(10,2) DEFAULT 0.00,
  `valor_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estoque_baixado` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `movimentos_estoque` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `produto_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `tipo` ENUM('entrada','saida','ajuste','devolucao') NOT NULL,
  `quantidade` DECIMAL(10,3) NOT NULL,
  `saldo_anterior` DECIMAL(10,3),
  `saldo_posterior` DECIMAL(10,3),
  `valor_unitario` DECIMAL(10,2),
  `motivo` VARCHAR(150),
  `os_id` INT UNSIGNED,
  `compra_id` INT UNSIGNED,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- FINANCEIRO
-- =====================================================

CREATE TABLE IF NOT EXISTS `fin_categorias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('receita','despesa') NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `cor` VARCHAR(7) DEFAULT '#6c757d',
  `pai_id` INT UNSIGNED,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pai_id`) REFERENCES `fin_categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fin_contas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(80) NOT NULL,
  `tipo` ENUM('caixa','banco','cartao_credito','cartao_debito','outro') DEFAULT 'caixa',
  `banco` VARCHAR(60),
  `agencia` VARCHAR(10),
  `conta` VARCHAR(20),
  `saldo_inicial` DECIMAL(12,2) DEFAULT 0.00,
  `saldo_atual` DECIMAL(12,2) DEFAULT 0.00,
  `ativo` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fin_lancamentos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `conta_id` INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED,
  `os_id` INT UNSIGNED,
  `cliente_id` INT UNSIGNED,
  `fornecedor_id` INT UNSIGNED,
  `usuario_id` INT UNSIGNED,
  `tipo` ENUM('receita','despesa','transferencia') NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(12,2) NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATE,
  `status` ENUM('pendente','pago','cancelado','parcial') DEFAULT 'pendente',
  `forma_pagamento` ENUM('dinheiro','pix','cartao_credito','cartao_debito','boleto','transferencia','cheque','outro'),
  `parcelas` TINYINT UNSIGNED DEFAULT 1,
  `parcela_numero` TINYINT UNSIGNED DEFAULT 1,
  `grupo_parcela` VARCHAR(36),
  `numero_documento` VARCHAR(60),
  `observacoes` TEXT,
  `recorrente` TINYINT(1) DEFAULT 0,
  `recorrencia_tipo` ENUM('diaria','semanal','mensal','anual'),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`conta_id`) REFERENCES `fin_contas`(`id`),
  FOREIGN KEY (`categoria_id`) REFERENCES `fin_categorias`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL,
  INDEX `idx_lanc_empresa` (`empresa_id`),
  INDEX `idx_lanc_venc` (`data_vencimento`),
  INDEX `idx_lanc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fin_comissoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tecnico_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `percentual` DECIMAL(5,2) DEFAULT 0.00,
  `valor_base` DECIMAL(10,2) DEFAULT 0.00,
  `valor_comissao` DECIMAL(10,2) DEFAULT 0.00,
  `pago` TINYINT(1) DEFAULT 0,
  `data_pagamento` DATE,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- AGENDA
-- =====================================================

CREATE TABLE IF NOT EXISTS `agenda` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descricao` TEXT,
  `tipo` ENUM('visita','coleta','entrega','reuniao','ligacao','os','outro') DEFAULT 'outro',
  `cliente_id` INT UNSIGNED,
  `os_id` INT UNSIGNED,
  `usuario_id` INT UNSIGNED,
  `data_inicio` DATETIME NOT NULL,
  `data_fim` DATETIME,
  `dia_todo` TINYINT(1) DEFAULT 0,
  `cor` VARCHAR(7) DEFAULT '#0d6efd',
  `status` ENUM('agendado','confirmado','cancelado','concluido') DEFAULT 'agendado',
  `lembrete_minutos` INT UNSIGNED DEFAULT 30,
  `link_externo` VARCHAR(255),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_agenda_data` (`data_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CONFIGURAÇÕES
-- =====================================================

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `chave` VARCHAR(80) NOT NULL,
  `valor` TEXT,
  `tipo` ENUM('texto','numero','booleano','json','lista') DEFAULT 'texto',
  UNIQUE KEY `uq_config` (`empresa_id`, `chave`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
