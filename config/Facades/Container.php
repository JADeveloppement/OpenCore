<?php

/**
 * Nom du Fichier : Container.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades;

class Container
{
    private array $services = [];
    private array $resolved = [];

    public function get(string $key)
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if (!isset($this->services[$key])) {
            throw new \Exception("Service introuvable dans le Container : [{$key}].");
        }

        $instance = call_user_func($this->services[$key], $this);

        $this->resolved[$key] = $instance;

        return $instance;
    }

    public function set(string $key, callable $resolver): void
    {
        $this->services[$key] = $resolver;
        unset($this->resolved[$key]);
    }
}