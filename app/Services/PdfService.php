<?php

namespace App\Services;

/**
 * Geração de PDF a partir de HTML (Dompdf — PHP puro, em lib/dompdf).
 */
class PdfService
{
    /** Gera os bytes de um PDF a partir de HTML. Retorna null em falha. */
    public static function fromHtml(string $html, string $paper = 'A4', string $orient = 'portrait'): ?string
    {
        $autoload = BASE_PATH . '/lib/dompdf/autoload.inc.php';
        if (!is_file($autoload)) return null;
        require_once $autoload;

        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);       // permite carregar logo por URL
            $options->set('defaultMediaType', 'print');    // aplica @media print (esconde .no-print)
            $options->set('isHtml5ParserEnabled', true);
            $options->set('chroot', BASE_PATH);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper($paper, $orient);
            $dompdf->render();
            return $dompdf->output();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
