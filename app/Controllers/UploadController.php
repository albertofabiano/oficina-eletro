<?php

namespace App\Controllers;

use App\Core\Controller;

class UploadController extends Controller
{
    public function serve(string $file): void
    {
        // Sanitizar — apenas nome do arquivo, sem path traversal
        $file    = basename($file);
        $caminho = BASE_PATH . '/storage/uploads/logos/' . $file;

        if (!file_exists($caminho)) {
            http_response_code(404);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $caminho);
        finfo_close($finfo);

        $permitidos = ['image/jpeg','image/png','image/gif','image/svg+xml','image/webp'];
        if (!in_array($mime, $permitidos)) {
            http_response_code(403);
            exit;
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }
}
