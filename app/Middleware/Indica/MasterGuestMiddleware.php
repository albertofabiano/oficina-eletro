<?php

namespace App\Middleware\Indica;

/** Mesmo espírito de App\Middleware\MasterGuestMiddleware, mas redirecionando pro próprio
 *  domínio indica.* (ver Indica\MasterMiddleware pro porquê de não reaproveitar o original). */
class MasterGuestMiddleware
{
    public function handle(): void
    {
        if (!empty($_SESSION['master_id'])) {
            header('Location: ' . indica_url('/admin'));
            exit;
        }
    }
}
