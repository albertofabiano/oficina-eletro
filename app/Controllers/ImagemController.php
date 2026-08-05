<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImageService;

/**
 * Editor de imagem pra web (SEO): recorta/redimensiona no navegador e converte pra WebP.
 */
class ImagemController extends Controller
{
    public function editor(): void
    {
        $this->view('imagem.editor', [
            'titulo' => 'Preparar imagem pra web',
        ], $this->layoutAtual());
    }

    /** Recebe a imagem (já recortada/redimensionada no navegador) e devolve o WebP em base64. */
    public function processar(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada — recarregue a página.'], 400); }

        if (empty($_FILES['imagem']['tmp_name']) || !is_uploaded_file($_FILES['imagem']['tmp_name'])) {
            $this->json(['ok' => false, 'erro' => 'Envie uma imagem.']);
        }
        $tmp  = $_FILES['imagem']['tmp_name'];
        $info = @getimagesize($tmp);
        $permit = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
        if (!$info || !in_array($info['mime'] ?? '', $permit, true)) {
            $this->json(['ok' => false, 'erro' => 'Formato não suportado. Use JPG, PNG ou WebP.']);
        }

        $dest = sys_get_temp_dir() . '/img_' . bin2hex(random_bytes(6)) . '.webp';
        $ok = ImageService::paraWebp($tmp, $dest, 85);
        if (!$ok || !is_file($dest)) {
            $this->json(['ok' => false, 'erro' => 'Não consegui processar a imagem. Tente outra.']);
        }

        $bytes = file_get_contents($dest);
        $final = @getimagesize($dest);
        @unlink($dest);
        $this->json([
            'ok'      => true,
            'imagem'  => 'data:image/webp;base64,' . base64_encode($bytes),
            'kb'      => round(strlen($bytes) / 1024, 1),
            'largura' => $final[0] ?? $info[0],
            'altura'  => $final[1] ?? $info[1],
        ]);
    }
}
