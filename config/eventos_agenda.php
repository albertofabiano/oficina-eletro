<?php
/*
 * Configuração visual dos eventos de agenda — rótulo, ícone (Bootstrap Icons)
 * e cores por TipoEvento e StatusEvento (ver app/Enums/TipoEvento.php e
 * app/Enums/StatusEvento.php — os enums são a lista de valores válidos,
 * este arquivo é onde se ajusta rótulo/ícone/cor sem tocar em código).
 *
 * Cada tipo tem duas variantes de cor:
 * - pill_bg / pill_texto: fundo claro + texto forte, pra badge/etiqueta (pill).
 * - barra: cor forte sólida, pra barra lateral de card/coluna do calendário.
 * Cada uma tem versão "light" e "dark" (tema claro/escuro do app — ver
 * public/css/tokens.css).
 */
return [

    'tipos' => [
        'ordem_servico' => [
            'rotulo' => 'Ordem de Serviço',
            'icone'  => 'bi-tools',
            'light'  => ['pill_bg' => '#e0e7ff', 'pill_texto' => '#3730a3', 'barra' => '#4f46e5'],
            'dark'   => ['pill_bg' => 'rgba(129,140,248,0.18)', 'pill_texto' => '#a5b4fc', 'barra' => '#818cf8'],
        ],
        'coleta' => [
            'rotulo' => 'Coleta',
            'icone'  => 'bi-box-seam',
            'light'  => ['pill_bg' => '#fef3c7', 'pill_texto' => '#92400e', 'barra' => '#f59e0b'],
            'dark'   => ['pill_bg' => 'rgba(251,191,36,0.18)', 'pill_texto' => '#fcd34d', 'barra' => '#fbbf24'],
        ],
        'entrega' => [
            'rotulo' => 'Entrega',
            'icone'  => 'bi-truck',
            'light'  => ['pill_bg' => '#dcfce7', 'pill_texto' => '#166534', 'barra' => '#16a34a'],
            'dark'   => ['pill_bg' => 'rgba(74,222,128,0.18)', 'pill_texto' => '#86efac', 'barra' => '#4ade80'],
        ],
        'financeiro' => [
            'rotulo' => 'Financeiro',
            'icone'  => 'bi-cash-coin',
            'light'  => ['pill_bg' => '#ccfbf1', 'pill_texto' => '#115e59', 'barra' => '#0d9488'],
            'dark'   => ['pill_bg' => 'rgba(45,212,191,0.18)', 'pill_texto' => '#5eead4', 'barra' => '#2dd4bf'],
        ],
        'garantia' => [
            'rotulo' => 'Garantia',
            'icone'  => 'bi-shield-check',
            'light'  => ['pill_bg' => '#ede9fe', 'pill_texto' => '#5b21b6', 'barra' => '#7c3aed'],
            'dark'   => ['pill_bg' => 'rgba(167,139,250,0.18)', 'pill_texto' => '#c4b5fd', 'barra' => '#a78bfa'],
        ],
        'pessoal' => [
            'rotulo' => 'Pessoal',
            'icone'  => 'bi-person-heart',
            'light'  => ['pill_bg' => '#fce7f3', 'pill_texto' => '#9d174d', 'barra' => '#db2777'],
            'dark'   => ['pill_bg' => 'rgba(244,114,182,0.18)', 'pill_texto' => '#f9a8d4', 'barra' => '#f472b6'],
        ],
        'outro' => [
            'rotulo' => 'Outro',
            'icone'  => 'bi-calendar-event',
            'light'  => ['pill_bg' => '#e2e8f0', 'pill_texto' => '#334155', 'barra' => '#64748b'],
            'dark'   => ['pill_bg' => 'rgba(148,163,184,0.18)', 'pill_texto' => '#cbd5e1', 'barra' => '#94a3b8'],
        ],
    ],

    // Cores semânticas por status: azul, verde, âmbar, cinza, cinza, vermelho
    // (concluído e cancelado usam o mesmo cinza de propósito — os dois são
    // estados "finais/inativos" na agenda, só o rótulo diferencia).
    'status' => [
        'agendado' => [
            'rotulo' => 'Agendado',
            'light'  => ['pill_bg' => '#dbeafe', 'pill_texto' => '#1e40af'],
            'dark'   => ['pill_bg' => 'rgba(96,165,250,0.18)', 'pill_texto' => '#93c5fd'],
        ],
        'confirmado' => [
            'rotulo' => 'Confirmado',
            'light'  => ['pill_bg' => '#dcfce7', 'pill_texto' => '#166534'],
            'dark'   => ['pill_bg' => 'rgba(74,222,128,0.18)', 'pill_texto' => '#86efac'],
        ],
        'em_andamento' => [
            'rotulo' => 'Em andamento',
            'light'  => ['pill_bg' => '#fef3c7', 'pill_texto' => '#92400e'],
            'dark'   => ['pill_bg' => 'rgba(251,191,36,0.18)', 'pill_texto' => '#fcd34d'],
        ],
        'concluido' => [
            'rotulo' => 'Concluído',
            'light'  => ['pill_bg' => '#e2e8f0', 'pill_texto' => '#334155'],
            'dark'   => ['pill_bg' => 'rgba(148,163,184,0.18)', 'pill_texto' => '#cbd5e1'],
        ],
        'cancelado' => [
            'rotulo' => 'Cancelado',
            'light'  => ['pill_bg' => '#e2e8f0', 'pill_texto' => '#334155'],
            'dark'   => ['pill_bg' => 'rgba(148,163,184,0.18)', 'pill_texto' => '#cbd5e1'],
        ],
        'atrasado' => [
            'rotulo' => 'Atrasado',
            'light'  => ['pill_bg' => '#fee2e2', 'pill_texto' => '#991b1b'],
            'dark'   => ['pill_bg' => 'rgba(248,113,113,0.18)', 'pill_texto' => '#fca5a5'],
        ],
    ],

    // Faixa de horário exibida nas visões Semana/Dia/Técnicos (eixo de horas) — também usada
    // como "jornada" de referência pra barra de ocupação da visão Técnicos (não existe campo de
    // jornada por usuário no banco hoje; é um único valor por empresa, ajustável só aqui).
    'jornada' => [
        'hora_inicio' => 7,
        'hora_fim'    => 20,
    ],

];
