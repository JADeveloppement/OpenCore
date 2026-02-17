<?php

/**
 * Nom du Fichier : ExceptionHandler.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Errors;

use Throwable;

class ExceptionHandler
{
    private Throwable $exception;

    public function __construct(Throwable $e)
    {
        $this->exception = $e;
    }

    public function render()
    {
        $code = ($this->exception instanceof HttpException) ? $this->exception->getStatusCode() : 500;
        $isDebug = env('APP_DEBUG', false);

        if (!headers_sent()) {
            http_response_code($code);
        }

        if ($isDebug) {
            $this->renderDebugPage($code);
        } else {
            $this->renderUserPage($code);
        }
        exit;
    }

    private function renderDebugPage($code)
    {
        echo "<html><body style='background:#1a1a1a; color:#f8f8f2; padding:2rem; font-family:sans-serif;'>";
        echo "<h1 style='color:#ff79c6;'>Erreur {$code}</h1>";
        echo "<p style='font-size:1.2rem;'><strong>Message :</strong> " . $this->exception->getMessage() . "</p>";
        echo "<p><strong>Fichier :</strong> " . $this->exception->getFile() . " (Ligne " . $this->exception->getLine() . ")</p>";
        echo "<h2 style='color:#8be9fd;'>Stack Trace :</h2>";
        echo "<pre style='background:#282a36; padding:1rem; border-radius:5px; overflow-x:auto;'>" . $this->exception->getTraceAsString() . "</pre>";
        echo "</body></html>";
    }

    private function renderUserPage($code)
    {
        $viewPath = dirname(__DIR__, 3) . "/app/Views/errors/{$code}.php";
        if (file_exists($viewPath)) {
            $message = $this->exception->getMessage();
            include $viewPath;
        } else {
            echo "<h1>Erreur {$code}</h1><p>Une erreur est survenue sur le serveur.</p>";
        }
    }
}