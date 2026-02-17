<?php

/**
 * Nom du Fichier : Request.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Http;

class Request
{
    private array $headers;
    private array $query;
    private array $body;
    private array $files;
    protected array $params = [];

    public function __construct()
    {
        $this->headers = getallheaders();
        $this->query = $this->sanitize($_GET);
        $this->files = $_FILES;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');
            $this->body = json_decode($json, true) ?? [];
        } else {
            $this->body = $this->sanitize($_POST);
        }
    }

    /**
     * Nettoyage récursif des entrées (XSS protection basique)
     */
    private function sanitize(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value))
                return $this->sanitize($value);
            return is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
        }, $data);
    }

    /**
     * Récupère TOUT (GET + POST/JSON)
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Cherche partout (Body en priorité, puis Query)
     */
    public function input(string $key, $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Récupère seulement certains champs (très utile pour UsersModel::validate)
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest');
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }
    public function query(string $key): mixed
    {
        return $this->query[$key] ?? null;
    }
    public function body(string $key): mixed
    {
        return $this->body[$key] ?? null;
    }

    /**
     * Définit les paramètres extraits de l'URL (appelé par le Router)
     */
    public function setRouteParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Récupère un paramètre spécifique (ex: $request->param('id'))
     */
    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Récupère tous les paramètres de la route
     */
    public function allParams(): array
    {
        return $this->params;
    }
}