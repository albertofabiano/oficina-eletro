<?php

// Informações da empresa/produto exibidas no rodapé do sistema.
// Igual em todos os ambientes (não é config de servidor). Fácil de atualizar.
return [
    'nome'      => 'FixaOS',
    'descricao' => 'Gestão para Assistências Técnicas',
    'versao'    => '1.0.0',

    // CNPJ (14 dígitos, validado na Receita). Deixe vazio para não exibir.
    // Para trocar no futuro: basta editar esta linha.
    'cnpj'      => '29.630.053/0001-78',
    'razao'     => '',   // Razão social (opcional — ex: 'TVSERVICE')

    'site'      => 'fixaos.com.br',
    'email'     => 'contato@fixaos.com.br',
];
