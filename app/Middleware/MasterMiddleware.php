<?php

namespace App\Middleware;

class MasterMiddleware
{
    public function handle(): void
    {
        if (empty($_SESSION['master_id'])) {
            header('Location: ' . url('/master/login'));
            exit;
        }
    }
}
