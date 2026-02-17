<?php

/**
 * Nom du Fichier : Mail.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Mail;

use Config\Facades\Mail\MailBuilder; // Import the concrete builder class

/**
 * Mail Facade: A static helper to initialize and configure the MailBuilder
 * for easy, chainable use throughout the application.
 */
class Mail
{
    private static array $SMTP_CONFIG = [];

    /**
     * Retrieves and initializes the SMTP configuration using the env() helper.
     */
    private static function getConfig(): array
    {
        if (empty(self::$SMTP_CONFIG)) {
            self::$SMTP_CONFIG = [
                'host' => env('SMTP_HOST', 'smtp.example.com'),
                'port' => env('SMTP_PORT', 587),
                'username' => env('SMTP_USERNAME', 'your_smtp_username'),
                'password' => env('SMTP_PASSWORD', 'your_smtp_password'),
                'secure' => env('SMTP_SECURE', 'tls'),
                'default_from_email' => env('SMTP_FROM_DEFAULT', 'noreply@yourdomain.com'),
                'default_from_name' => env('SMTP_FROM_NAME_DEFAULT', 'Ledger'),
            ];
        }

        return self::$SMTP_CONFIG;
    }
    // --- End Configuration ---

    /**
     * Initializes and configures a new MailBuilder instance.
     * This is the entry point for all mail creation.
     *
     * @return MailBuilder A configured instance ready for chaining methods (->to(), ->subject(), etc.).
     */
    public static function build(): MailBuilder
    {
        $builder = new MailBuilder();

        $builder->applyConfig(self::getConfig());

        return $builder;
    }

    public static function to(string|array $emails): MailBuilder
    {
        return self::build()->to($emails);
    }
}