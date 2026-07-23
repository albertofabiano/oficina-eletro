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
        $this->view('ajuda.manual', ['titulo' => 'Manual do Usuário'], 'main');
    }

    public function docs(): void
    {
        $this->view('ajuda.docs', ['titulo' => 'Documentação Técnica'], 'landing');
    }
}
