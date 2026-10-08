-- Aviso de vencimento da licença/trial do plano COMPLETO (3 dias antes + no dia do
-- vencimento, com link de pagamento real — ver scripts/avisar_vencimento_licenca.php). Mesmo
-- princípio de dedup já usado em `fixa_assinatura_avisos` (migration 088): uma linha por
-- (empresa, tipo de aviso, data do vencimento específica) — a data entra na UNIQUE de
-- propósito, não só o tipo, porque se a empresa renovar e `licenca_ate` virar uma data futura
-- NOVA, ela precisa voltar a ser elegível pros dois avisos dessa nova data, sem precisar
-- resetar nada manualmente (uma UNIQUE só por tipo nunca mais avisaria essa empresa de novo
-- depois da primeira vez na vida dela).
CREATE TABLE IF NOT EXISTS `empresa_avisos_vencimento` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id`      INT UNSIGNED NOT NULL,
  `tipo`            ENUM('3_dias_antes','vencimento') NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `enviado_em`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa_tipo_vencimento` (`empresa_id`, `tipo`, `data_vencimento`),
  KEY `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FK em statement separado (mesma cautela documentada em outras migrations deste projeto —
-- 084/085 do módulo Fixa: se a migration rodar 2x por engano, só esta linha falha,
-- "Duplicate foreign key", sem travar o CREATE TABLE acima, que já é idempotente via
-- IF NOT EXISTS). `empresas.id` é INT UNSIGNED, mesmo tipo de `empresa_id` aqui, então a FK é
-- segura (diferente do caso de `cobrancas`, que tem mismatch de signedness confirmado via
-- DESCRIBE real).
ALTER TABLE `empresa_avisos_vencimento`
  ADD CONSTRAINT `fk_eav_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE;
