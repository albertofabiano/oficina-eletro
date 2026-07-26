-- Sessão única por usuário: ao logar, o token novo substitui o antigo em `sessao_token`
-- e a sessão anterior (com o token velho) é derrubada no próximo request (ver App\Core\Auth).
ALTER TABLE usuarios ADD COLUMN sessao_token VARCHAR(64) NULL AFTER ultimo_login;
