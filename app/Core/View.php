<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $payload = [], string $layout = 'app'): string
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View {$view} tidak ditemukan.");
        }

        extract($payload, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        ob_start();
        require $layoutFile;

        return (string) ob_get_clean();
    }
}
