<?php

/**
 * Nom du Fichier : Role.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades;

use App\Models\RolesModel;
use App\Models\UsersModel;
use Config\Facades\Auth;

class Role
{
    private static ?string $cachedRoleLabel = null;

    /**
     * Récupère le label du rôle de l'utilisateur connecté.
     */
    public static function getLabel(): ?string
    {
        if (self::$cachedRoleLabel !== null) {
            return self::$cachedRoleLabel;
        }

        $user = Auth::user();
        if (!$user || empty($user['role_id'])) {
            return null;
        }

        $role = RolesModel::where('id', '=', $user['role_id'])->first();

        if ($role) {
            self::$cachedRoleLabel = $role['label'];
            return self::$cachedRoleLabel;
        }

        return null;
    }

    /**
     * Vérifie si l'utilisateur a un rôle précis (par son label).
     * Usage : Role::is('admin')
     */
    public static function is(string $label): bool
    {
        return strtolower(self::getLabel()) === strtolower($label);
    }

    /**
     * Vérifie si l'utilisateur est dans une liste de rôles.
     * Usage : Role::in(['admin', 'editor'])
     */
    public static function in(array $labels): bool
    {
        $currentLabel = self::getLabel();
        if (!$currentLabel)
            return false;

        return in_array(strtolower($currentLabel), array_map('strtolower', $labels));
    }

    /**
     * Helper rapide pour l'admin
     */
    public static function isAdmin(): bool
    {
        return self::is('ADMIN');
    }

    /**
     * Récupère tous les utilisateurs possédant un rôle spécifique.
     * * @param string $label Le label du rôle (ex: 'admin')
     * @return array Liste des utilisateurs
     */
    public static function usersWith(string $label): array
    {
        $role = RolesModel::where('label', '=', $label)->first();

        if (!$role) {
            return [];
        }

        return UsersModel::where('role_id', '=', $role['id'])->get();
    }
}