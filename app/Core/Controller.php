<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $content = function () use ($view, $data) {
            extract($data);
            require BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        };
        require BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function redirectBack(): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? url('/'));
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
