<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $__view, array $__data = [], string $__layout = 'main'): void
    {
        // Nomes de parâmetro com prefixo "__" de propósito: extract($__data) roda neste mesmo
        // escopo (o layout precisa acessar as variáveis direto, ex. $titulo), então se algum
        // controller passar uma chave de dado chamada 'view'/'data'/'layout' (a Agenda passa
        // 'view' com o modo mês/semana/dia/técnicos), um extract($data) nomeado igual ao
        // parâmetro sobrescreveria o próprio parâmetro — foi exatamente isso que quebrou a
        // Agenda (tentava abrir app/Views/mes.php em vez de agenda/index.php).
        extract($__data);
        $content = function () use ($__view, $__data) {
            extract($__data);
            require BASE_PATH . '/app/Views/' . str_replace('.', '/', $__view) . '.php';
        };
        require BASE_PATH . '/app/Views/layouts/' . $__layout . '.php';
    }

    /** Igual ao view(), mas retorna o HTML como string (para gerar PDF, etc.). */
    protected function renderView(string $view, array $data = [], string $layout = 'main'): string
    {
        ob_start();
        $this->view($view, $data, $layout);
        return ob_get_clean();
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    protected function redirectBack(): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? url('/'));
    }

    /**
     * Redireciona preservando ?painel=1 quando a requisição atual já veio assim —
     * evita que uma ação disparada de dentro do iframe das abas de Configurações
     * (ex.: excluir num Excluir de uma listagem) caia no layout completo (com
     * sidebar/topbar) sendo renderizado empilhado dentro do próprio iframe.
     */
    protected function redirectPreservandoPainel(string $url): void
    {
        if ($this->get('painel')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'painel=1';
        }
        $this->redirect($url);
    }

    protected function backWithInput(string $error, array $data = null): void
    {
        $_SESSION['_old'] = $data ?? $this->post();
        $this->flash('error', $error);
        $this->redirectBack();
    }

    protected function jsonBody(): array
    {
        static $parsed = null;
        if ($parsed === null) {
            $ct = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($ct, 'application/json')) {
                $parsed = json_decode(file_get_contents('php://input'), true) ?? [];
            } else {
                $parsed = [];
            }
        }
        return $parsed;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $this->jsonBody()[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $_POST ?: $this->jsonBody();
        return $_POST[$key] ?? $this->jsonBody()[$key] ?? $default;
    }

    protected function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $_GET;
        return $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    /** 'painel' quando a página é carregada dentro de um iframe (aba de Configurações), senão 'main'. */
    protected function layoutAtual(): string
    {
        return $this->get('painel') ? 'painel' : 'main';
    }

    protected function empresaId(): int
    {
        return (int) ($_SESSION['empresa_id'] ?? 0);
    }

    protected function usuarioId(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    protected function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            foreach ($ruleList as $r) {
                if ($r === 'required' && empty($data[$field])) {
                    $errors[$field] = "Campo obrigatório.";
                }
                if (str_starts_with($r, 'max:')) {
                    $max = (int) substr($r, 4);
                    if (strlen($data[$field] ?? '') > $max) {
                        $errors[$field] = "Máximo de {$max} caracteres.";
                    }
                }
                if ($r === 'email' && !empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "E-mail inválido.";
                }
            }
        }
        return $errors;
    }
}
