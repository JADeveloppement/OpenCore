<?php

/**
 * Nom du Fichier : MailBuilder.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Mail;

// Import the necessary PHPMailer classes using the full namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use Config\Facades\Log; // Assuming this exists for error logging

/**
 * MailBuilder: Constructs an email message in a programmatic, 
 * chainable manner and handles its safe transmission using PHPMailer.
 */
class MailBuilder
{
    protected array $recipients = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected string $subject = "";
    protected string $body = "";
    protected array $attachments = [];
    protected string $fromEmail = "noreply@yourdomain.com";
    protected string $fromName = "Your Application";
    protected bool $isHtml = true;
    protected bool $debug = false;

    protected array $config = [];


    public function to(string|array $emails): self
    {
        $this->recipients = array_merge($this->recipients, (array) $emails);
        return $this;
    }
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
    public function body(string $content): self
    {
        $this->body = $content;
        return $this;
    }
    public function attach(string $pathToFile, string $fileName = ''): self
    {
        $this->attachments[] = [
            'path' => $pathToFile,
            'name' => $fileName ?: basename($pathToFile)
        ];
        return $this;
    }

    public function plainText(): self
    {
        $this->isHtml = false;
        return $this;
    }

    /**
     * Applies SMTP configuration details to the builder state.
     * Called by the static Mail facade/helper class.
     * @param array $config Associative array of configuration values.
     * @return self
     */
    public function applyConfig(array $config): self
    {
        $this->fromEmail = $config['default_from_email'] ?? $this->fromEmail;
        $this->fromName = $config['default_from_name'] ?? $this->fromName;

        $this->config = $config;

        return $this;
    }



    public function send(): bool
    {
        if (empty($this->recipients)) {
            Log::warning("MailBuilder > send() : No recipients specified.");
            $this->resetState();
            return false;
        }

        try {
            $success = $this->executeDelivery();

            if ($this->debug) {
                Log::info("MailBuilder > send() : Status: " . ($success ? "SUCCESS" : "FAILURE"));
            }

            return $success;

        } catch (Exception $e) {
            Log::warning("MailBuilder > send() : Email sending failed. PHPMailer Error: " . $e->getMessage());
            return false;
        } finally {
            $this->resetState();
        }
    }

    /**
     * Integrates with PHPMailer to execute the email delivery.
     * @return bool True on successful transmission, false otherwise.
     * @throws Exception|\Exception PHPMailer exceptions if sending fails.
     */
    protected function executeDelivery(): bool
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $config = $this->config;

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        if (($config['secure'] ?? '') === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = $config['port'];

        if ($this->debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        // --- END SMTP Config ---

        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->isHTML($this->isHtml);
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;

        foreach ($this->recipients as $email)
            $mail->addAddress($email);
        foreach ($this->cc as $email)
            $mail->addCC($email);
        foreach ($this->bcc as $email)
            $mail->addBCC($email);

        foreach ($this->attachments as $attachment)
            $mail->addAttachment($attachment['path'], $attachment['name']);

        return $mail->send();
    }

    /**
     * Resets the builder's state after an execution.
     */
    protected function resetState(): void
    {
        $this->recipients = $this->cc = $this->bcc = $this->attachments = [];
        $this->subject = $this->body = "";
        $this->isHtml = true;
        $this->debug = false;
    }
}