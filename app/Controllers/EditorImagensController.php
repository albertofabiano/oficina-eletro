<?php

namespace App\Controllers;

use App\Core\Controller;

class EditorImagensController extends Controller
{
    public function index(): void
    {
        $empresa = \App\Core\DB::pdo()
            ->prepare("SELECT logo FROM empresas WHERE id = ?")
            ->execute([$this->empresaId()])
            ? \App\Core\DB::pdo()->query("SELECT logo FROM empresas WHERE id = {$this->empresaId()}")->fetchColumn()
            : null;

        $logoUrl = $empresa ? url('/uploads/' . $empresa) : null;

        $this->view('editor_imagens.index', [
            'titulo'   => 'Editor de Imagens',
            'logoUrl'  => $logoUrl,
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $dataUrl  = $this->post('imagem', '');
        $nomeBase = preg_replace('/[^a-z0-9_-]/i', '_', $this->post('nome', 'imagem_editada'));

        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
            $this->json(['error' => 'Imagem inválida'], 422);
        }

        // Extrair dados da imagem base64
        [$meta, $dados] = explode(',', $dataUrl, 2);
        preg_match('/data:(image\/\w+);base64/', $meta, $m);
        $mime = $m[1] ?? 'image/png';
        $ext  = match($mime) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default      => 'png',
        };

        $nomeArq = $nomeBase . '_editado_' . date('Ymd_His') . '.' . $ext;
        $dir     = BASE_PATH . '/storage/uploads/editadas/';

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $conteudo = base64_decode($dados);
        if (!$conteudo) { $this->json(['error' => 'Erro ao decodificar imagem'], 422); }

        file_put_contents($dir . $nomeArq, $conteudo);

        $this->json([
            'success' => true,
            'arquivo' => $nomeArq,
            'url'     => url('/storage/uploads/editadas/' . $nomeArq),
            'tamanho' => round(strlen($conteudo) / 1024, 1) . ' KB',
        ]);
    }
}
