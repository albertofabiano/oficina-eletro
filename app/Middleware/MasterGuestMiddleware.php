<?php

namespace App\Middleware;

class MasterGuestMiddleware
{
    public function handle(): void
    {
        if (!empty($_SESSION['master_id'])) {
            header('Location: ' . url('/master'));
            exit;
        }
    }
}
