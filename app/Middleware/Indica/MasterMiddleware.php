<?php

namespace App\Middleware\Indica;

/**
 * Guard do painel admin do Fixa Indica (indica.fixaos.com.br/admin/*). Mesma CONTA de
 * master_admins já usada em /master/* no FixaOS principal — mas NÃO é a mesma sessão: o
 * cookie de sessão é por domínio (public/index.php grava com domain='', escopo = host exato
 * da requisição), então logar em fixaos.com.br/master não autentica em indica.fixaos.com.br, e
 * vice-versa. Por isso este é um middleware próprio (não App\Middleware\MasterMiddleware, que
 * redireciona pra url('/master/login') — domínio errado pra quem está em indica.*), com login
 * dedicado em Indica\AdminAuthController.
 */
class MasterMiddleware
{
    public function handle(): void
    {
        if (empty($_SESSION['master_id'])) {
            header('Location: ' . indica_url('/admin/login'));
            exit;
        }
    }
}
