<?php

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $this->guardarRedirectPosLogin();
            header('Location: ' . url('/login'));
            exit;
        }

        // Sessão única: se essa mesma conta logou em outro dispositivo/navegador depois desta
        // sessão, o token foi sobrescrito e esta sessão é derrubada aqui.
        if (!Auth::sessaoValida()) {
            unset($_SESSION['usuario_id'], $_SESSION['usuario'], $_SESSION['empresa_id'], $_SESSION['permissoes'], $_SESSION['sessao_token'], $_SESSION['tipo_conta']);
            $_SESSION['flash']['error'] = 'Sua sessão foi encerrada porque esta conta foi acessada em outro dispositivo ou navegador.';
            $this->guardarRedirectPosLogin();
            header('Location: ' . url('/login'));
            exit;
        }

        // Conta "só diretório": acesso limitado ao perfil público + fórum da comunidade.
        // Bloqueia o sistema completo (OS, financeiro, etc.) e leva pro perfil.
        // O fórum é liberado para essas contas (membros da comunidade e perfis reivindicados).
        if (Auth::soDiretorio()) {
            $uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            // Logo e galeria de fotos são grátis pra qualquer empresa (reivindicada), inclusive
            // conta só-diretório sem plano nenhum — sem "/empresa/fotos" aqui, o upload de foto
            // (POST /empresa/fotos e as ações de excluir/tornar capa) nunca chegava a rodar:
            // esse middleware redirecionava de volta pra /empresa/perfil-publico antes mesmo do
            // controller, e a tela só via "a página recarregou e a foto não apareceu".
            $liberado = ['/empresa/perfil-publico', '/empresa/publicidade', '/empresa/logo', '/empresa/fotos', '/empresa/produtos-diretorio', '/empresa/exportar', '/logout', '/perfil', '/conta', '/forum', '/planos', '/assinar', '/pagamento'];
            $ok = false;
            foreach ($liberado as $p) { if ($uri === $p || str_starts_with($uri, $p . '/')) { $ok = true; break; } }
            if (!$ok) {
                if ($uri !== '/empresa/perfil-publico') {
                    $_SESSION['flash']['info'] = 'Sua conta é do plano Diretório. Para usar o sistema completo (OS, financeiro, estoque...), faça upgrade quando quiser.';
                }
                header('Location: ' . url('/empresa/perfil-publico'));
                exit;
            }
        }

        // Trial expirado e sem plano pago ativo: bloqueia o sistema inteiro, só libera
        // upgrade/pagamento e logout. Justo com quem paga — sem isso, quem nunca assina
        // usaria o sistema de graça pra sempre depois do teste.
        $emp = null;
        if (!Auth::soDiretorio() && Auth::empresaId() > 0) {
            try {
                // plano_atual também buscado aqui (não só trial_ate/licenca_ate) — reaproveitado
                // mais abaixo pelo bloqueio de módulo por plano, sem precisar de uma 2ª consulta.
                $st = \App\Core\DB::pdo()->prepare("SELECT trial_ate, licenca_ate, plano_atual FROM empresas WHERE id = ? LIMIT 1");
                $st->execute([Auth::empresaId()]);
                $emp = $st->fetch() ?: null;
            } catch (\Throwable $e) {
                $emp = null;
            }
            if ($emp && sistema_bloqueado($emp)) {
                $uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
                // Configurações (Técnicos, Status de OS, Usuários, Empresa, Exibição do texto,
                // Editor de Imagens etc.) continua acessível mesmo bloqueado — sem isso, uma
                // empresa travada nem conseguia corrigir um dado próprio (ex.: WhatsApp errado
                // impedindo o código de verificação da InfinitePay de chegar) sem antes pagar.
                $liberado = ['/planos', '/assinar', '/comprar-credito', '/comprar-credito-scan-equip', '/comprar-credito-scan-placa', '/pagamento', '/logout',
                             '/configuracoes', '/tecnicos', '/os/status', '/usuarios', '/empresa'];
                $ok = false;
                foreach ($liberado as $p) { if ($uri === $p || str_starts_with($uri, $p . '/')) { $ok = true; break; } }
                if (!$ok) {
                    $_SESSION['flash']['error'] = 'Sua assinatura venceu. Ative um plano para continuar usando o FixaOS.';
                    header('Location: ' . url('/planos'));
                    exit;
                }
            }
        }

        // Controle de acesso por papel (função). Se a URL pertence a um módulo
        // restrito e o papel do usuário não pode vê-lo, bloqueia e volta ao painel.
        $modulo = Auth::moduloDoUri($_SERVER['REQUEST_URI'] ?? '/');
        if ($modulo !== null && !Auth::can($modulo, 'ver')) {
            $_SESSION['flash']['error'] = 'Você não tem permissão para acessar essa área. Fale com o administrador da sua empresa.';
            header('Location: ' . url('/dashboard'));
            exit;
        }

        // Módulo inteiro fora do plano (hoje só o Básico — Agenda/CRM/Marketplace/PDV/
        // Marketing e as telas de Divulgação) — eixo diferente do papel/função, checado por
        // cima dele: mesmo um admin (que já tem '*' na MATRIZ de papéis) não contorna isso só
        // por ser admin da própria empresa.
        if ($emp !== null) {
            $uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            $bloqueadoPorPlano = false;
            if ($modulo !== null && !plano_permite_modulo($modulo, $emp)) {
                $bloqueadoPorPlano = true;
            } elseif ((plano_da_empresa($emp)['divulgacao_habilitado'] ?? true) === false) {
                $divulgacao = ['/empresa/perfil-publico', '/empresa/produtos-diretorio', '/empresa/anuncios-diretorio', '/empresa/publicidade', '/empresa/vagas'];
                foreach ($divulgacao as $p) { if ($uri === $p || str_starts_with($uri, $p . '/')) { $bloqueadoPorPlano = true; break; } }
            }
            if ($bloqueadoPorPlano) {
                $_SESSION['flash']['error'] = 'Esse recurso não está incluído no seu plano atual. Veja os planos disponíveis pra liberar.';
                header('Location: ' . url('/dashboard'));
                exit;
            }
        }
    }

    /**
     * Guarda a URL que a pessoa tentou acessar sem estar logada, pra AuthController::login()
     * poder voltar pra lá depois (em vez de sempre cair em /dashboard, que era o comportamento
     * de sempre — limitação já documentada, ex.: clicar em "Cadastrar produto" na ficha pública
     * do Diretório sem estar logado jogava de volta pro painel genérico, não pra tela de onde
     * veio). Só captura GET (POST/DELETE são quase sempre ação de formulário/AJAX, não uma
     * página pra "voltar depois") e ignora `/api/...` (chamada de fetch() que expirou a sessão
     * no meio da página, não uma navegação de verdade — guardar isso faria o login seguinte
     * cair numa URL de API/JSON, não numa tela).
     */
    private function guardarRedirectPosLogin(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if ($uri === '' || $uri === '/login' || str_starts_with($uri, '/api/')) return;
        $_SESSION['login_redirect'] = $uri;
    }
}
