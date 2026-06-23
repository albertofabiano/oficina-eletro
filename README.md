# OficinaTech — Sistema de Gestão para Assistência Técnica

Sistema completo em PHP MVC puro para assistência técnica de eletrônicos e eletrodomésticos.

## Requisitos
- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com mod_rewrite (XAMPP)

## Instalação (XAMPP)

1. Copie a pasta `oficina-eletro` para `C:\xampp\htdocs\`
2. Inicie Apache e MySQL no XAMPP
3. Acesse: `http://localhost/oficina-eletro/database/install.php`
4. Após instalar: `http://localhost/oficina-eletro/public`

**Login:** admin@techfix.com | **Senha:** Admin@123

## Configuração

Edite `config/database.php` para ajustar a senha do MySQL se necessário.
Edite `config/app.php` para ajustar a URL base se necessário.

## Estrutura
```
oficina-eletro/
├── app/
│   ├── Controllers/     # Lógica de requisições
│   ├── Core/            # Router, Model, Controller, DB, Auth
│   ├── Helpers/         # Funções auxiliares
│   ├── Middleware/      # Auth, Guest
│   ├── Models/          # Acesso ao banco
│   └── Views/           # Templates PHP + Bootstrap 5
├── config/              # Configurações
├── database/
│   ├── migrations/      # Schema SQL
│   ├── seeds/           # Dados iniciais
│   └── install.php      # Script de instalação
├── public/              # Ponto de entrada (index.php)
├── routes/web.php        # Definição de rotas
└── storage/             # Uploads e logs
```

## Módulos
- **Dashboard** com gráficos e KPIs
- **CRM** — clientes PF/PJ, pipeline Kanban, histórico de contatos
- **Ordens de Serviço** — abertura, workflow de status, serviços, peças
- **Equipamentos** — cadastro por tipo (TV, celular, geladeira, etc.)
- **Estoque** — produtos, movimentação, alertas de mínimo
- **Financeiro** — receitas, despesas, fluxo de caixa
- **Agenda** — calendário de atendimentos
- **Multiempresas** — dados isolados por empresa_id
