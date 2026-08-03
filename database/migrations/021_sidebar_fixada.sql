-- Preferência de sidebar fixada (trilha de ícones expansível — ver refatoração
-- da sidebar). Mesma lógica de persistência da preferência de tema (`tema`):
-- salva por usuário, lida via SELECT u.* já usado no login (Auth::login).
ALTER TABLE usuarios ADD COLUMN sidebar_fixada TINYINT(1) NOT NULL DEFAULT 0 AFTER tema;
