<?php

namespace App\Middleware;

use App\Core\Auth;

class GuestMiddleware
{
    public function handle(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
    }
}
