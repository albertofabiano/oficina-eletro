<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Dashboard;

class DashboardController extends Controller
{
    public function index(): void
    {
        $model = new Dashboard();
        $this->view('dashboard.index', [
            'titulo'      => 'Dashboard',
            'resumo'      => $model->resumo(),
            'ultimasOS'   => $model->ultimasOS(8),
            'faturamento' => $model->faturamentoPorMes(6),
            'agenda'      => $model->agendaHoje(),
            'topServicos' => $model->topServicos(0, 5),
        ]);
    }
}
