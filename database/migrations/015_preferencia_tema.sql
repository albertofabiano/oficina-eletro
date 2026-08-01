-- Preferência de tema do usuário (claro/escuro/automático), por usuário — acompanha quem troca de máquina.
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS tema VARCHAR(10) NOT NULL DEFAULT 'auto' AFTER tutorial_visto;
