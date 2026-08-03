-- Garante a etiqueta "sem acessórios" (protegida e exclusiva) no catálogo de
-- toda empresa já cadastrada. Empresas novas já recebem isso automaticamente
-- no cadastro (LandingController, ao criar a empresa) — este script é só o
-- backfill pra quem se cadastrou antes dessa etiqueta existir no formulário.
INSERT IGNORE INTO equip_acessorios (empresa_id, nome)
SELECT id, 'sem acessórios' FROM empresas;
