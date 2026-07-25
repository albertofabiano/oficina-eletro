-- Percentual de comissão configurável por técnico (usuário com perfil='tecnico' ou atende_os=1).
-- Quando NULL, o sistema usa o percentual padrão da empresa (configuracoes.comissao_tecnico_percentual).
ALTER TABLE usuarios ADD COLUMN comissao_percentual DECIMAL(5,2) NULL AFTER atende_os;
