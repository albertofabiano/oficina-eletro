-- Fixa Fase 1 — `financeiro_pessoal_categorias` ganha escopo por perfil (não mais só por
-- usuário — perfil "Pessoal" e um eventual perfil PJ têm catálogos de categoria
-- INDEPENDENTES, mesmo que o usuário seja o mesmo) + os campos novos da spec (`tipo`, `icone`,
-- `grupo_dre`).
--
-- `perfil_id` nasce NULL (ALTER não consegue preencher com um valor válido de linha-a-linha
-- sozinho) — backfill de verdade é feito por scripts/migrar_fixa_perfis.php, que já faz a parte
-- de achar/criar o perfil "Pessoal" de cada usuário e apontar as categorias existentes pra ele.
-- Continua tendo `usuario_id` (não removido) pelo mesmo motivo de sempre: segunda camada de
-- filtro/isolamento sem depender de JOIN.
--
-- `tipo` decide se a categoria aparece nos chips quando o lançamento é Entrada (receita) ou
-- Gasto (despesa) — hoje (antes desta migration) a mesma lista inteira aparecia nos dois casos,
-- sem distinção; `categoriasDoUsuario()`/`categoriasDoPerfil()` passa a devolver separado por
-- tipo. As 7 categorias padrão JÁ existentes (migration 078) são todas 'despesa' por padrão
-- (reflete o que elas sempre foram na prática — nenhuma delas é usada hoje como categoria de
-- receita) — a migração de dados reclassifica isso quando aplicável.

ALTER TABLE `financeiro_pessoal_categorias`
  ADD COLUMN `perfil_id`  INT UNSIGNED NULL AFTER `usuario_id`,
  ADD COLUMN `tipo`       ENUM('receita','despesa') NOT NULL DEFAULT 'despesa' AFTER `nome`,
  ADD COLUMN `icone`      VARCHAR(30) NULL AFTER `cor`,
  ADD COLUMN `grupo_dre`  VARCHAR(60) NULL AFTER `icone`;

-- FK e índice em statement separado (não dá pra combinar ADD COLUMN + ADD CONSTRAINT de forma
-- portável) — se a migration rodar 2x por engano, só esta linha falha ("Duplicate foreign key"/
-- "Duplicate key name"), o resto acima já é idempotente o bastante pra ignorar o erro específico
-- desta linha.
ALTER TABLE `financeiro_pessoal_categorias`
  ADD KEY `idx_perfil` (`perfil_id`),
  ADD CONSTRAINT `fk_fpcat_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE;

-- A UNIQUE antiga era (usuario_id, chave) — com dois perfis do MESMO usuário podendo ter a
-- MESMA chave (ex.: "outros" existe tanto no perfil Pessoal quanto num perfil PJ), isso
-- precisa virar (perfil_id, chave). Também statement separado — se já tiver sido trocada antes
-- (rerun), a linha de DROP falha sozinha sem travar o resto.
ALTER TABLE `financeiro_pessoal_categorias` DROP INDEX `uq_usuario_chave`;
ALTER TABLE `financeiro_pessoal_categorias` ADD UNIQUE KEY `uq_perfil_chave` (`perfil_id`, `chave`);
