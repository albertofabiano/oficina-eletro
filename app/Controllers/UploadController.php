<?php

namespace App\Controllers;

use App\Core\Controller;

class UploadController extends Controller
{
    public function serveMarketplace(string $file): void
    {
        $file    = basename($file);
        $caminho = BASE_PATH . '/storage/uploads/marketplace/' . $file;
        $this->servirArquivo($caminho);
    }

    public function serveProduto(string $file): void
    {
        $file    = basename($file);
        $caminho = BASE_PATH . '/storage/uploads/produtos/' . $file;
        $this->servirArquivo($caminho);
    }

    public function serveFoto(string $file): void
    {
        $file    = basename($file);
        $caminho = BASE_PATH . '/storage/uploads/fotos/' . $file;
        $this->servirArquivo($caminho);
    }

    private function servirArquivo(string $caminho): void
    {
        if (!file_exists($caminho)) { http_response_code(404); exit; }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $caminho);
        finfo_close($finfo);
        $permitidos = ['image/jpeg','image/png','image/gif','image/svg+xml','image/webp'];
        if (!in_array($mime, $permitidos)) { http_response_code(403); exit; }
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    public function serve(string $file): void
    {
        $file = basename($file);
        // Logos ficam em /logos; banners e comprovantes na raiz de uploads
        $logos = BASE_PATH . '/storage/uploads/logos/' . $file;
        $raiz  = BASE_PATH . '/storage/uploads/' . $file;
        $this->servirArquivo(file_exists($logos) ? $logos : $raiz);
    }

    public function serve_legado(string $file): void
    {
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
