<?php
/*
 * Config da integração InfinitePay (Checkout API). Copie este arquivo para
 * config/infinitepay.php (gitignored — só existe no servidor) e preencha
 * com os dados reais da conta InfinitePay da empresa.
 *
 * 'ativo'  → true liga os botões de assinatura/compra em todo o sistema
 *            (app/Views/empresa/planos.php e afins). Com false ou sem
 *            'handle', o sistema mostra "Em breve" e bloqueia o checkout
 *            (ver InfinitePayService::ativo()).
 * 'handle' → InfiniteTag da conta InfinitePay, SEM o "$" (ex: 'minhaempresa').
 * 'api_url'→ endpoint base da Checkout API da InfinitePay.
 */
return [
    'ativo'   => false,
    'handle'  => '',
    'api_url' => 'https://api.checkout.infinitepay.io',
];
