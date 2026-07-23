<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;

/**
 * Ordem personalizada dos atalhos do menu lateral — por USUÁRIO (salva na conta,
 * acompanha em qualquer dispositivo). Ver coluna usuarios.menu_ordem (JSON).
 */
class MenuController extends Controller
{
    public function salvarOrdem(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'token'], 419); return; }

        $ordem = $this->post('ordem', []);
        if (!is_array($ordem)) { $this->json(['error' => 'inválido'], 400); return; }

        // Sanitiza: só chaves curtas no formato slug (a-z, 0-9, hífen) e sem repetição.
        $limpo = [];
        foreach ($ordem as $k) {
            if (is_string($k) && preg_match('/^[a-z0-9\-]{1,30}$/', $k)) $limpo[] = $k;
        }
        $json = json_encode(array_values(array_unique($limpo)));

        try {
            DB::pdo()->prepare("UPDATE usuarios SET menu_ordem = ? WHERE id = ?")
                     ->execute([$json, Auth::id()]);
            $_SESSION['menu_ordem'] = $json; // reflete na hora, sem novo SELECT
        } catch (\Throwable $e) {
            $this->json(['error' => 'falha ao salvar'], 500); return;
        }

        $this->json(['success' => true]);
    }
}
