-- Adiciona 'rh' ao ENUM de perfil de usuário. Mantém 'tecnico' no banco (usuários
-- técnicos existentes e a tela dedicada Configurações → Técnicos continuam usando
-- esse valor); ele só deixou de aparecer no select genérico de Usuários.
ALTER TABLE `usuarios`
  MODIFY COLUMN `perfil` ENUM('superadmin','admin','gerente','recepcionista','tecnico','financeiro','rh') DEFAULT 'tecnico';
