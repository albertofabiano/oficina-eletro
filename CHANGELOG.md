## Visão Geral

Sistema completo de gestão para assistências técnicas de eletrônicos e eletrodomésticos, construído do zero em PHP 8 com arquitetura MVC pura (sem frameworks), multiempresas e módulos integrados.

---

## Arquitetura e Stack

- **Backend:** PHP 8.0+ · MVC puro · PDO
- **Frontend:** Bootstrap 5.3 · Bootstrap Icons · IMask.js · Chart.js · SortableJS
- **Banco:** MySQL/MariaDB · utf8mb4
- **Ambiente:** XAMPP (Apache + PHP + MySQL)

---

## Módulos Implementados

### Autenticação e Multiempresas
- Registro público com onboarding pós-cadastro (prefixo OS, numeração, garantia padrão)
- Trial automático de 30 dias por empresa
- Sessões isoladas por empresa
- Painel Master Admin separado (`/master`) com gestão global de empresas

### CRM / Clientes
- Cadastro PF/PJ com busca AJAX por nome, CPF ou telefone
- Autopreenchimento de endereço via CEP (ViaCEP)
- Pipeline Kanban por estágios configuráveis
- Histórico de contatos (ligação, WhatsApp, e-mail, visita)
- Equipamentos vinculados por cliente

### Ordens de Serviço
- Abertura via modal 2 etapas: cliente (busca AJAX + cadastro inline) > equipamento
- Workflow de status configurável com CRUD e drag-and-drop para reordenar
- Serviços e peças com CRUD inline
- Fechamento com laudo, solução, garantia e lançamento automático no financeiro
- Impressão de abertura com 4 etiquetas de identificação e via da empresa
- Impressão de comprovante de entrega e de retorno em garantia

### Retorno de Garantia
- Validação automática do prazo (dias configurados por empresa)
- Busca de OS fechadas em garantia via modal com 3 passos
- OS de garantia vinculada à original com status exclusivo "Em Garantia"
- Comprovante de retorno com checklist de integridade

### Estoque / Produtos
- Cadastro com código de barras, estado, tipo e marca (todos com CRUD via offcanvas)
- Movimentação automática ao usar peças nas OS
- Alertas de estoque mínimo na sidebar e topbar

### Financeiro
- Saldo unificado: lançamentos manuais + OS fechadas (sem dupla contagem)
- OS pendentes de pagamento com botão de recebimento direto
- Filtro por origem (OS / lançamento manual)

### Outros Módulos
- Agenda: calendário mensal com eventos coloridos
- Relatórios: faturamento mensal, top serviços, top clientes, OS por status
- Técnicos: CRUD com métricas de desempenho individuais
- Fornecedores: CRUD completo com endereço

### Empresa e Configurações
- Upload de logo com redimensionamento automático
- Logo exibida na sidebar e em todos os documentos impressos
- Editor WYSIWYG inline para termos de entrada de equipamento e de garantia
- Configurações de numeração de OS por empresa

### Painel Master (`/master`)
- Dashboard global com métricas de todas as empresas
- Gestão de planos, trial e status
- CRUD de administradores master com sessão separada

---

## Banco de Dados

- 20+ tabelas com relacionamentos por `empresa_id`
- Script de instalação via browser (`/database/install.php`)
- Migrations SQL versionadas e seeds de dados iniciais

---

## Segurança

- Senhas com `password_hash` bcrypt (custo 12)
- CSRF token em todos os formulários POST
- Prepared statements PDO em todas as queries
- Validação de MIME type real no upload de imagens
- Isolamento total de dados por `empresa_id`
- Middlewares de autenticação por rota

---

## Acesso de demonstração

**Sistema:** `http://localhost/oficina-eletro/public`
**Login empresa:** `admin@techfix.com` / `Admin@123`
**Master Admin:** `http://localhost/oficina-eletro/public/master/login`
**Login master:** `master@oficina.com` / `Master@2025`

---

🤖 Generated with [Claude Code](https://claude.ai/claude-code)
