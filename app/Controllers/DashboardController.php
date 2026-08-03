<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Dashboard;

class DashboardController extends Controller
{
    public function index(): void
    {
        $model = new Dashboard();

        // Tutorial de primeiros passos: exibe uma única vez por usuário.
        $stmt = DB::pdo()->prepare("SELECT tutorial_visto FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$this->usuarioId()]);
        $mostrarTutorial = ((int) $stmt->fetchColumn()) === 0;

        $this->view('dashboard.index', [
            'titulo'          => 'Dashboard',
            'resumo'          => $model->resumo(),
            'ultimasOS'       => $model->ultimasOS(8),
            'faturamento'     => $model->faturamentoPorMes(12),
            'osPorMes'        => $model->osPorMes(12),
            'agenda'          => $model->agendaHoje(),
            'topServicos'     => $model->topServicos(0, 5),
            'vencendo'        => $model->osVencendoHoje(),
            'fluxoResumo'     => $model->fluxoOsResumo(),
            'fluxoDiario'     => $model->fluxoOsDiario(30),
            'mostrarTutorial' => $mostrarTutorial,
        ]);
    }

    /** Marca o tutorial de primeiros passos como visto (1x por usuário). */
    public function tutorialVisto(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        DB::pdo()->prepare("UPDATE usuarios SET tutorial_visto = 1 WHERE id = ?")->execute([$this->usuarioId()]);
        $_SESSION['usuario']['tutorial_visto'] = 1;
        $this->json(['ok' => true]);
    }

    /** Preferência de exibição do usuário: informações em MAIÚSCULAS (1) ou texto normal (0). */
    public function salvarExibicao(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        $val = ((int) $this->post('maiusculo', 0)) === 1 ? 1 : 0;
        DB::pdo()->prepare("UPDATE usuarios SET texto_maiusculo = ? WHERE id = ?")->execute([$val, $this->usuarioId()]);
        $_SESSION['texto_maiusculo'] = $val;
        $this->json(['ok' => true, 'maiusculo' => $val]);
    }

    /** Preferência de tema do usuário: claro, escuro ou automático (acompanha o sistema operacional). */
    public function salvarTema(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        $tema = $this->post('tema', 'auto');
        if (!in_array($tema, ['light', 'dark', 'auto'], true)) { $tema = 'auto'; }
        DB::pdo()->prepare("UPDATE usuarios SET tema = ? WHERE id = ?")->execute([$tema, $this->usuarioId()]);
        $_SESSION['usuario']['tema'] = $tema;
        $this->json(['ok' => true, 'tema' => $tema]);
    }

    /** Preferência de sidebar fixada (aberta) ou recolhida (rail com hover). */
    public function salvarSidebarFixada(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        $val = ((int) $this->post('fixada', 0)) === 1 ? 1 : 0;
        DB::pdo()->prepare("UPDATE usuarios SET sidebar_fixada = ? WHERE id = ?")->execute([$val, $this->usuarioId()]);
        $_SESSION['usuario']['sidebar_fixada'] = $val;
        $this->json(['ok' => true, 'fixada' => $val]);
    }

    /** Habilita/desabilita o chat interno da equipe — configuração DA EMPRESA (só admin/config). */
    public function salvarChatConfig(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        if (!\App\Core\Auth::isAdmin()) { $this->json(['ok' => false, 'erro' => 'Apenas o administrador pode alterar essa configuração.'], 403); }
        $eid  = $this->empresaId();
        $vals = [
            'chat_habilitado' => ((int) $this->post('habilitado', 1)) === 1 ? 1 : 0,
            'chat_som'        => ((int) $this->post('som', 1)) === 1 ? 1 : 0,
            'chat_insistente' => ((int) $this->post('insistente', 1)) === 1 ? 1 : 0,
        ];
        $db  = DB::pdo();
        foreach ($vals as $chave => $val) {
            $st = $db->prepare("SELECT id FROM configuracoes WHERE empresa_id = ? AND chave = ? LIMIT 1");
            $st->execute([$eid, $chave]);
            if ($st->fetchColumn()) {
                $db->prepare("UPDATE configuracoes SET valor = ? WHERE empresa_id = ? AND chave = ?")
                   ->execute([(string) $val, $eid, $chave]);
            } else {
                $db->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, ?, ?)")
                   ->execute([$eid, $chave, (string) $val]);
            }
        }
        $_SESSION['chat_habilitado'] = $vals['chat_habilitado'];
        $this->json(['ok' => true] + $vals);
    }

    /** Mostra/oculta a "Previsão de entrega" nas telas de OS — configuração DA EMPRESA (só admin/config). */
    public function salvarPrevisaoConfig(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        if (!\App\Core\Auth::isAdmin()) { $this->json(['ok' => false, 'erro' => 'Apenas o administrador pode alterar essa configuração.'], 403); }
        $eid = $this->empresaId();
        $val = ((int) $this->post('mostrar', 1)) === 1 ? 1 : 0;
        $db  = DB::pdo();
        $st  = $db->prepare("SELECT id FROM configuracoes WHERE empresa_id = ? AND chave = 'mostrar_previsao' LIMIT 1");
        $st->execute([$eid]);
        if ($st->fetchColumn()) {
            $db->prepare("UPDATE configuracoes SET valor = ? WHERE empresa_id = ? AND chave = 'mostrar_previsao'")->execute([(string) $val, $eid]);
        } else {
            $db->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, 'mostrar_previsao', ?)")->execute([$eid, (string) $val]);
        }
        $_SESSION['mostrar_previsao'] = $val;
        $this->json(['ok' => true, 'mostrar' => $val]);
    }

    /** Liga/desliga os botões flutuantes de Calculadora e Mentor — configuração DA EMPRESA (só admin/config). */
    public function salvarFerramentasConfig(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false], 403); }
        if (!\App\Core\Auth::isAdmin()) { $this->json(['ok' => false, 'erro' => 'Apenas o administrador pode alterar essa configuração.'], 403); }
        $eid  = $this->empresaId();
        $vals = [
            'mostrar_calculadora' => ((int) $this->post('calculadora', 1)) === 1 ? 1 : 0,
            'mostrar_mentor'      => ((int) $this->post('mentor', 1)) === 1 ? 1 : 0,
        ];
        $db = DB::pdo();
        foreach ($vals as $chave => $val) {
            $st = $db->prepare("SELECT id FROM configuracoes WHERE empresa_id = ? AND chave = ? LIMIT 1");
            $st->execute([$eid, $chave]);
            if ($st->fetchColumn()) {
                $db->prepare("UPDATE configuracoes SET valor = ? WHERE empresa_id = ? AND chave = ?")
                   ->execute([(string) $val, $eid, $chave]);
            } else {
                $db->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, ?, ?)")
                   ->execute([$eid, $chave, (string) $val]);
            }
        }
        $this->json(['ok' => true, 'calculadora' => $vals['mostrar_calculadora'], 'mentor' => $vals['mostrar_mentor']]);
    }
}
