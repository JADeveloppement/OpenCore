<?php

/**
 * Nom du Fichier : Auth.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades;

use App\Models\UsersModel;
use Config\Facades\Sessions\SessionManager;

class Auth
{
    private static SessionManager $sessionManager;
    private static UsersModel $userModel;
    private static ?UsersModel $cachedUser = null;

    public static function boot(Container $container)
    {
        if (!isset(self::$sessionManager)) {
            self::$sessionManager = new SessionManager();
        }
        self::$userModel = new UsersModel($container->get(\PDO::class));
    }

    private static function ensureBooted()
    {
        if (!isset(self::$sessionManager)) {
            throw new \Exception("Le composant Auth n'a pas été initialisé. Vérifiez Application::bootApplication().");
        }
    }

    public static function check(): bool
    {
        self::ensureBooted();
        return self::$sessionManager->get('user_id') !== null;
    }

    public static function id()
    {
        self::ensureBooted();
        return self::$sessionManager->get('user_id');
    }

    public static function signin(int $userId)
    {
        self::ensureBooted();

        session_regenerate_id(true);

        self::$sessionManager->set('user_id', $userId);
        self::$sessionManager->set('csrf_token', bin2hex(random_bytes(32)));
    }

    public static function logout()
    {
        self::ensureBooted();
        self::$sessionManager->destroy();
        self::$cachedUser = null;
    }

    public static function attempt(string $signin, string $password): bool
    {
        self::ensureBooted();

        $results = self::$userModel::where('email', '=', $signin)->first();
        $user = $results ?? null;

        if ($user && password_verify($password, $user['password'])) {
            self::signin($user['id']);
            return true;
        }

        return false;
    }

    public static function user(): ?UsersModel
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = self::id();
        if ($userId === null) {
            return null;
        }

        $user = self::$userModel::where('id', '=', $userId)->first();

        if (!$user) {
            self::logout();
            return null;
        }

        unset($user['password'], $user['updated_at']);

        self::$cachedUser = $user;
        return $user;
    }

    public static function user_verified(): bool
    {
        $user = self::user();
        return self::check() && !empty($user['email_verified_at']);
    }
}
