<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $viewData = [], ?string $layout = null): void
    {
        extract($viewData, EXTR_SKIP);

        $viewFile = BASE_PATH . "/src/Views/{$view}.php";
        if (!is_file($viewFile)) {
            http_response_code(500);
            exit("View {$view} not found.");
        }

        if ($layout) {
            $layoutFile = BASE_PATH . "/src/Views/layouts/{$layout}.php";
            if (is_file($layoutFile)) {
                ob_start();
                require $viewFile;
                $content = ob_get_clean();
                require $layoutFile;
                return;
            }
        }

        require $viewFile;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        if (isset($_POST[$key])) return $_POST[$key];
        if (isset($_GET[$key]))  return $_GET[$key];
        return $default;
    }
}
