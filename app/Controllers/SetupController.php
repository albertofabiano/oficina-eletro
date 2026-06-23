<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class SetupController extends Controller
{
    public function onboarding(): void
    {
        // Só exibe se a empresa ainda não concluiu o setup
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare(
            "SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = 'setup_concluido'"
        );
        $stmt->execute([$eid]);
        if ($stmt->fetchColumn() === '1') {
            $this->redirect(url('/dashboard'));
        }

        // Busca configurações já salvas para pré-preencher
        $configs = $this->getConfigs($eid);

        // Renderiza o layout setup diretamente (sem view interna)
        extract(['titulo' => 'Configuração Inicial', 'configs' => $configs]);
        $content = function() {};
        require BASE_PATH . '/app/Views/layouts/setup.php';
        exit;
    }

    public function salvar(): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/setup'));
        }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $numeroInicial = max(1, (int) $this->post('os_numero_inicial', 1));
        $prefixo       = strtoupper(trim($this->post('os_prefixo', '')));
        $digitos       = min(10, max(4, (int) $this->post('os_digitos', 6)));
        $garantia      = max(1, (int) $this->post('garantia_padrao_dias', 90));
        $prazoRetirada = max(1, (int) $this->post('prazo_retirada_dias', 30));
        $nomeFant      = trim($this->post('nome_fantasia', ''));
        $telefone      = $this->post('telefone', '');
        $whatsapp      = $this->post('whatsapp', '');

        // Atualiza dados da empresa se informado
        if ($nomeFant) {
            $db->prepare(
                "UPDATE empresas SET nome_fantasia = ?, telefone = ?, whatsapp = ? WHERE id = ?"
            )->execute([$nomeFant, $telefone, $whatsapp, $eid]);
        }

        // Salva/atualiza configurações
        $configs = [
            'os_prefixo'            => $prefixo,
            'os_digitos'            => $digitos,
            'os_numero_inicial'     => $numeroInicial,
            'garantia_padrao_dias'  => $garantia,
            'prazo_retirada_dias'   => $prazoRetirada,
            'setup_concluido'       => '1',
        ];

        $stmt = $db->prepare(
            "INSERT INTO configuracoes (empresa_id, chave, valor)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($configs as $chave => $valor) {
            $stmt->execute([$eid, $chave, $valor]);
        }

        $this->flash('success', 'Configurações salvas! Bem-vindo ao OficinaTech 🎉');
        $this->redirect(url('/dashboard'));
    }

    private function getConfigs(int $eid): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT chave, valor FROM configuracoes WHERE empresa_id = ?"
        );
        $stmt->execute([$eid]);
        $rows = $stmt->fetchAll();
        $cfg  = [];
        foreach ($rows as $r) $cfg[$r['chave']] = $r['valor'];
        return $cfg;
    }
}
