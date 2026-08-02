<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImageService;

/**
 * Editor de imagem pra web (SEO): remove fundo + fundo branco + tamanho padrão + WebP.
 */
class ImagemController extends Controller
{
    public function editor(): void
    {
        $this->view('imagem.editor', [
            'titulo'  => 'Preparar imagem pra web',
            'rembgOk' => ImageService::rembgDisponivel(),
        ], $this->layoutAtual());
    }

    /** Recebe a imagem + opções, processa e devolve o WebP em base64. */
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

        $tamanho = (int) $this->post('tamanho', '800');
        if (!in_array($tamanho, [400, 600, 800, 1000, 1200], true)) $tamanho = 800;
        $removerFundo = $this->post('remover_fundo', '0') === '1';
        $fundo = ($this->post('fundo', 'branco') === 'transparente') ? 'transparente' : 'branco';

        $dest = sys_get_temp_dir() . '/img_' . bin2hex(random_bytes(6)) . '.webp';
        $ok = ImageService::padronizar($tmp, $dest, [
            'tamanho'      => $tamanho,
            'removerFundo' => $removerFundo,
            'fundo'        => $fundo,
            'qualidade'    => 82,
        ]);
        if (!$ok || !is_file($dest)) {
            $this->json(['ok' => false, 'erro' => 'Não consegui processar a imagem. Tente outra.']);
        }

        $bytes = file_get_contents($dest);
        @unlink($dest);
        $this->json([
            'ok'          => true,
            'imagem'      => 'data:image/webp;base64,' . base64_encode($bytes),
            'kb'          => round(strlen($bytes) / 1024, 1),
            'dimensao'    => $tamanho,
            'removeuFundo' => $removerFundo && ImageService::rembgDisponivel(),
        ]);
    }
}
