<?php

namespace App\Controllers;

use App\Core\Controller;

class BuscaController extends Controller
{
    // Lista de seções vem de manual_secoes() (app/Helpers/functions.php) — mesma fonte da
    // sidebar do manual, pra busca nunca ficar pra trás quando uma seção nova é adicionada.
    public function buscar(): void
    {
        $termo     = trim((string) $this->get('q', ''));
        $resultado = ['manual' => []];

        if (mb_strlen($termo) < 2) {
            $this->json($resultado);
        }

        // Casa tanto no rótulo do artigo ("Lembretes") quanto no nome do grupo ("Agenda") —
        // sem isso, buscar pelo nome do módulo (o termo mais óbvio de se digitar) não achava
        // nada, já que nenhum rótulo de artigo individual repete o nome do grupo.
        foreach (manual_secoes() as $grupo => $links) {
            $grupoBate = mb_stripos($grupo, $termo) !== false;
            foreach ($links as [$anchor, $label]) {
                if (!$grupoBate && mb_stripos($label, $termo) === false) continue;
                $resultado['manual'][] = [
                    'label' => $label,
                    'url'   => url('/manual') . '#' . $anchor,
                ];
                if (count($resultado['manual']) >= 10) break 2;
            }
        }

        $this->json($resultado);
    }
}
