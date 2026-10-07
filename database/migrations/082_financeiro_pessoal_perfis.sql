-- Fixa Fase 1 (PF/PJ) — tabela `perfis`: cada usuário pode ter mais de uma "identidade"
-- financeira dentro do MESMO módulo pessoal (ex.: "Pessoal", CPF, e um MEI/CNPJ que ele
-- também administra) — continua sendo o financeiro do USUÁRIO (ver 075_financeiro_pessoal.sql),
-- nunca o financeiro da EMPRESA (fin_lancamentos) nem integrado com `empresas`. Todo usuário já
-- existente ganha um perfil "Pessoal" (tipo pf) na migração de dados (ver
-- scripts/migrar_fixa_perfis.php) — essa tabela nasce vazia aqui, só o schema.
--
-- `documento` (CPF ou CNPJ, conforme `tipo`) é opcional e sem UNIQUE — nada impede duas pessoas
-- cadastrando o mesmo CNPJ de uma empresa que as duas administram, ou um usuário deixando em
-- branco. Validação de dígito verificador reaproveita cpf_valido()/cnpj_valido()/
-- documento_valido() (app/Helpers/functions.php), já existentes no projeto — não duplicadas
-- aqui.
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_perfis` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo`       ENUM('pf','pj') NOT NULL DEFAULT 'pf',
  `nome`       VARCHAR(80) NOT NULL,
  `documento`  VARCHAR(18) NULL,
  `cor`        VARCHAR(30) NOT NULL DEFAULT '#8C7CFF',
  -- Só faz sentido pra tipo='pj' — a aplicação nunca grava isso num perfil 'pf', mas o schema
  -- não impede (ENUM nullable, não um CHECK condicional — MySQL não tem CHECK dependente de
  -- outra coluna de forma portável nas versões que o projeto já roda).
  `regime`     ENUM('mei','simples','presumido','outro') NULL,
  `ordem`      INT NOT NULL DEFAULT 0,
  `arquivado`  TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
