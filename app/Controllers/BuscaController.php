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
        ['equip-scanner', 'Cadastro de equipamento por IA'],
        ['os-fotos-whatsapp', 'Fotos de entrada por WhatsApp'],
        ['os-status', 'Status e workflow'],
        ['os-servicos', 'Serviços e peças'],
        ['os-chat', 'Chat interno da equipe'],
        ['os-imprimir', 'Impressão e PDF'],
        ['os-fechar', 'Fechar OS'],
        ['os-garantia', 'Garantia e retorno'],
        ['os-reabrir', 'Reabrir OS'],
        ['os-offline', 'Modo offline'],
        ['clientes', 'Cadastro de clientes'],
        ['crm', 'Pipeline de vendas'],
        ['estoque-produtos', 'Cadastro de produtos'],
        ['estoque-mov', 'Movimentações'],
        ['pdv', 'PDV — Vendas rápidas'],
        ['fin-lancamentos', 'Lançamentos'],
        ['fin-fluxo', 'Fluxo de caixa'],
        ['fin-relatorios', 'Relatórios'],
        ['fin-comissoes', 'Comissão de técnico'],
        ['agenda', 'Agendamentos'],
        ['mkt-anuncios', 'Criar anúncio'],
        ['mkt-creditos', 'Créditos'],
        ['mkt-vitrine', 'Vitrine e Marketplace Público'],
        ['mkt-pedidos', 'Pedidos de Peças'],
        ['forum-usar', 'Como usar o Fórum'],
        ['editor-imagens', 'Editor de Imagens'],
        ['cfg-empresa', 'Logo e Dados da Empresa'],
        ['cfg-usuarios', 'Usuários'],
        ['cfg-tecnicos', 'Técnicos e % de comissão'],
        ['cfg-status', 'Status de OS'],
        ['cfg-limites', 'Limite de usuários e sessão'],
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
