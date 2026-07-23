<?php

namespace App\Controllers;

use App\Core\Controller;

class BuscaController extends Controller
{
    private const MANUAL = [
        ['inicio', 'Visão geral do sistema'],
        ['dashboard', 'Dashboard'],
        ['navegacao', 'Navegação e atalhos'],
        ['os-abrir', 'Abrir nova OS'],
        ['os-status', 'Status e workflow'],
        ['os-servicos', 'Serviços e peças'],
        ['os-imprimir', 'Impressão e PDF'],
        ['os-fechar', 'Fechar OS'],
        ['os-garantia', 'Garantia e retorno'],
        ['os-reabrir', 'Reabrir OS'],
        ['clientes', 'Cadastro de clientes'],
        ['crm', 'Pipeline de vendas'],
        ['estoque-produtos', 'Cadastro de produtos'],
        ['estoque-mov', 'Movimentações'],
        ['pdv', 'PDV — Vendas rápidas'],
        ['fin-lancamentos', 'Lançamentos'],
        ['fin-fluxo', 'Fluxo de caixa'],
        ['fin-relatorios', 'Relatórios'],
        ['agenda', 'Agendamentos'],
        ['mkt-anuncios', 'Criar anúncio'],
        ['mkt-creditos', 'Créditos'],
        ['mkt-vitrine', 'Vitrine e Marketplace Público'],
        ['mkt-pedidos', 'Pedidos de Peças'],
        ['forum-usar', 'Como usar o Fórum'],
        ['editor-imagens', 'Editor de Imagens'],
        ['cfg-empresa', 'Logo e Dados da Empresa'],
        ['cfg-usuarios', 'Usuários'],
        ['cfg-status', 'Status de OS'],
    ];

    public function buscar(): void
    {
        $termo     = trim((string) $this->get('q', ''));
        $resultado = ['manual' => []];

        if (mb_strlen($termo) < 2) {
            $this->json($resultado);
        }

        foreach (self::MANUAL as [$anchor, $label]) {
            if (mb_stripos($label, $termo) !== false) {
                $resultado['manual'][] = [
                    'label' => $label,
                    'url'   => url('/manual') . '#' . $anchor,
                ];
                if (count($resultado['manual']) >= 10) break;
            }
        }

        $this->json($resultado);
    }
}
