<?php

namespace App\Controllers;

/** Landing pública do Carteira Fixa (carteirafixa.fixaos.com.br) — servida por
 *  routes/carteira_fixa_landing.php, sem middleware/sessão nenhuma. Página única, sem
 *  controller "pesado": só monta o título e renderiza a view com o layout próprio. */
class CarteiraFixaLandingController extends \App\Core\Controller
{
    public function index(): void
    {
        $this->view(
            'carteira_fixa_landing.index',
            [
                'titulo' => 'Carteira Fixa — controle financeiro pessoal, grátis por 1 ano',
                'noindex' => false,
            ],
            'carteira_fixa_landing'
        );
    }
}
