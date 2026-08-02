<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

/**
 * Página única "Configurações do Sistema": reúne em abas as telas que antes
 * ficavam espalhadas no submenu da sidebar. Cada aba de página própria
 * (Técnicos, Status de OS, Usuários, Empresa, Editor de Imagens) carrega o
 * controller/rota existente dentro de um iframe (?painel=1); Chat da Equipe,
 * Previsão de Entrega e Exibição do Texto — que nunca tiveram página própria,
 * só modais na sidebar — ganham aqui sua primeira aba de verdade.
 */
class ConfiguracoesController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('config') && !Auth::can('usuarios')) {
            $this->flash('error', 'Você não tem acesso às configurações do sistema.');
            $this->redirect(url('/dashboard'));
            return;
        }

        $eid = Auth::empresaId();
        $db  = DB::pdo();

        $textoMaiusculo = 0;
        try {
            $st = $db->prepare("SELECT texto_maiusculo FROM usuarios WHERE id = ?");
            $st->execute([Auth::id()]);
            $textoMaiusculo = (int) $st->fetchColumn();
        } catch (\Throwable $e) {}

        $chatHabilitado = 1; $chatSom = 1; $chatInsistente = 1; $mostrarPrevisao = 1;
        try {
            $st = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave IN ('chat_habilitado','chat_som','chat_insistente','mostrar_previsao')");
            $st->execute([$eid]);
            foreach ($st->fetchAll(\PDO::FETCH_KEY_PAIR) as $k => $v) {
                $iv = ($v === '' || $v === null) ? 1 : (int) $v;
                if ($k === 'chat_habilitado') $chatHabilitado = $iv;
                elseif ($k === 'chat_som') $chatSom = $iv;
                elseif ($k === 'chat_insistente') $chatInsistente = $iv;
                elseif ($k === 'mostrar_previsao') $mostrarPrevisao = $iv;
            }
        } catch (\Throwable $e) {}

        $abasValidas = ['tecnicos', 'status', 'chat', 'previsao', 'usuarios', 'exibicao', 'empresa', 'imagens'];
        $abaPedida   = $this->get('aba', '');
        $abaInicial  = in_array($abaPedida, $abasValidas, true) ? $abaPedida : null;

        $this->view('configuracoes.index', [
            'titulo'          => 'Configurações do Sistema',
            'podeUsuarios'    => Auth::can('usuarios'),
            'podeConfig'      => Auth::can('config'),
            'isAdmin'         => Auth::isAdmin(),
            'abaInicial'      => $abaInicial,
            'textoMaiusculo'  => $textoMaiusculo,
            'chatHabilitado'  => $chatHabilitado,
            'chatSom'         => $chatSom,
            'chatInsistente'  => $chatInsistente,
            'mostrarPrevisao' => $mostrarPrevisao,
        ]);
    }
}
