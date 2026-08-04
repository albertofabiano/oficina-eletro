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
        $layout = \App\Core\Auth::check() ? 'main' : 'manual_publico';
        $this->view('ajuda.manual', ['titulo' => 'Manual do Usuário'], $layout);
    }

    public function manualPdf(): void
    {
        $html = $this->renderView('ajuda.manual', ['titulo' => 'Manual do Usuário', 'modoPdf' => true], 'manual_pdf');
        $pdf  = \App\Services\PdfService::fromHtml($html);
        if ($pdf === null) {
            $this->flash('error', 'Não foi possível gerar o PDF agora. Tente novamente.');
            $this->redirect(url('/manual'));
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="manual-fixaos.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    public function docs(): void
    {
        $this->view('ajuda.docs', ['titulo' => 'Documentação Técnica'], 'landing');
    }
}
