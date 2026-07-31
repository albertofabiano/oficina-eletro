<?php

namespace App\Controllers;

use App\Core\Controller;

class AjudaController extends Controller
{
    public function central(): void
    {
        $this->view('ajuda.central', ['titulo' => 'Central de Ajuda'], 'landing');
    }

    public function manual(): void
    {
        $layout = \App\Core\Auth::check() ? 'main' : 'landing';
        $this->view('ajuda.manual', ['titulo' => 'Manual do Usuário'], $layout);
    }

    public function docs(): void
    {
        $this->view('ajuda.docs', ['titulo' => 'Documentação Técnica'], 'landing');
    }
}
