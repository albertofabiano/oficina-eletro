<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\IMEIService;

class ImeiController extends Controller
{
    /** POST /api/imei — consulta por IMEI (auto-preenche marca/modelo + status de bloqueio). */
    public function consultar(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido.'], 419); }
        $imei = trim((string) $this->post('imei', ''));
        if ($imei === '') { $this->json(['ok' => false, 'erro' => 'Informe o IMEI.'], 400); }
        $this->json(IMEIService::consultar($imei, Auth::empresaId()));
    }
}
